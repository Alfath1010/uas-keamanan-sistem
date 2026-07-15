<?php

namespace App\Services\Auth;

use App\Exceptions\Business\EmailAlreadyExistsException;
use App\Exceptions\Business\InvalidCredentialsException;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\Hash;

/**
 * Reference: FR-001, FR-002, ADR-004/ADR-005 (via the exceptions
 * thrown, translated into the standard envelope by the HTTP layer).
 */
class AuthService implements AuthServiceInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
    ) {}

    public function register(string $name, string $email, string $password): User
    {
        if ($this->users->findByEmail($email) !== null) {
            throw new EmailAlreadyExistsException();
        }

        return $this->users->create([
            'name' => $name,
            'email' => $email,
            'password' => $password, // hashed transparently by User's 'hashed' cast
        ]);
    }

    public function login(string $email, string $password): array
    {
        $user = $this->users->findByEmail($email);

        if ($user === null || ! Hash::check($password, $user->password)) {
            throw new InvalidCredentialsException();
        }

        $token = $user->createToken('api')->plainTextToken;

        return ['user' => $user, 'token' => $token];
    }

    public function logout(User $user): void
    {
        // $user is expected to be the request-resolved authenticated
        // user (i.e. $request->user()), so currentAccessToken()
        // reflects the specific bearer token used for this request —
        // only that session is terminated, not every device/token the
        // user holds.
        $user->currentAccessToken()?->delete();
    }
}
