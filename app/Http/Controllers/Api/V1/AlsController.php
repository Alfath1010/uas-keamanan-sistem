<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\HandshakeRequest;
use App\Http\Responses\ApiResponse;
use App\Services\CryptoManagerInterface;
use Illuminate\Http\JsonResponse;

/**
 * Reference: api_spec.md §6.4 (Application Layer Security)
 *
 * Neither endpoint here runs behind the DecryptAlsPayload middleware
 * (it would be circular — there's no session yet to decrypt with).
 * Both require Bearer authentication only.
 */
class AlsController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly CryptoManagerInterface $crypto,
    ) {}

    public function handshake(HandshakeRequest $request): JsonResponse
    {
        $result = $this->crypto->negotiateSession(
            $request->user(),
            $request->string('client_public_key'),
        );

        return $this->success('Sesi ALS berhasil dibuat.', [
            'session_uuid' => $result['session']->uuid,
            'server_public_key' => $result['server_public_key'],
            'expires_at' => $result['session']->expires_at->toIso8601String(),
        ]);
    }

    public function renew(HandshakeRequest $request): JsonResponse
    {
        $result = $this->crypto->renewSession(
            $request->user(),
            $request->string('client_public_key'),
        );

        return $this->success('Sesi ALS berhasil diperbarui.', [
            'session_uuid' => $result['session']->uuid,
            'server_public_key' => $result['server_public_key'],
            'expires_at' => $result['session']->expires_at->toIso8601String(),
        ]);
    }
}
