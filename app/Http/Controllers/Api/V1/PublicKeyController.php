<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\Business\UserNotFoundException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePublicKeyRequest;
use App\Http\Responses\ApiResponse;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\CryptoManagerInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Reference: api_spec.md §6.7 (Public Key Management), FR-008
 */
class PublicKeyController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly CryptoManagerInterface $crypto,
        private readonly UserRepositoryInterface $users,
    ) {}

    public function show(string $userUuid): JsonResponse
    {
        $user = $this->users->findByUuid($userUuid);

        if ($user === null) {
            throw new UserNotFoundException();
        }

        $keys = $this->crypto->getPublicKeys($user);

        return $this->success('Kunci publik berhasil diambil.', [
            'ecc_public_key' => $keys?->ecc_public_key,
            'schnorr_public_key' => $keys?->schnorr_public_key,
        ]);
    }

    public function store(StorePublicKeyRequest $request): JsonResponse
    {
        $keys = $this->crypto->uploadPublicKeys(
            $request->user(),
            $request->string('ecc_public_key'),
            $request->string('schnorr_public_key'),
        );

        return $this->success('Kunci publik berhasil diunggah.', [
            'ecc_public_key' => $keys->ecc_public_key,
            'schnorr_public_key' => $keys->schnorr_public_key,
        ]);
    }
}
