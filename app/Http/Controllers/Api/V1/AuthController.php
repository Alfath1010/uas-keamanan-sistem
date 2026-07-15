<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Responses\ApiResponse;
use App\Services\Auth\AuthServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Reference: api_spec.md §6.3 (Authentication), FR-001, FR-002
 */
class AuthController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly AuthServiceInterface $auth,
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $user = $this->auth->register(
            $request->string('name'),
            $request->string('email'),
            $request->string('password'),
        );

        return $this->success(
            'Akun berhasil dibuat.',
            ['uuid' => $user->uuid, 'email' => $user->email],
            201,
        );
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->auth->login(
            $request->string('email'),
            $request->string('password'),
        );

        return $this->success('Berhasil masuk.', [
            'uuid' => $result['user']->uuid,
            'token' => $result['token'],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->auth->logout($request->user());

        return $this->success('Berhasil keluar.');
    }
}
