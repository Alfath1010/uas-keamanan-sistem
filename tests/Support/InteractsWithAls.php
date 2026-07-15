<?php

namespace Tests\Support;

use App\Models\User;
use App\Services\Security\BlockCipher\BlockCipherService;
use App\Services\Security\ECC\ECCService;
use Illuminate\Testing\TestResponse;

/**
 * Reference: crypto_design.md §4.2 (Application Layer Security)
 *
 * Feature tests for ALS-protected endpoints (POST/GET /messages) need
 * something acting as the CLIENT half of the protocol: generating an
 * ephemeral key pair, performing the real handshake endpoint, then
 * independently deriving the same session key to encrypt requests and
 * decrypt responses — otherwise the DecryptAlsPayload middleware has
 * nothing valid to operate on.
 *
 * The HKDF derivation here intentionally mirrors ALSService's private
 * deriveSessionKey() exactly (same `info` string format) — if that
 * derivation is ever changed, this helper must change with it, or
 * every ALS feature test will start failing (which is itself a useful
 * regression signal).
 */
trait InteractsWithAls
{
    private function establishAlsSession(User $user, string $token): array
    {
        $ecc = new ECCService();
        $client = $ecc->generateKeyPair();

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/als/handshake', [
                'client_public_key' => $client['public_key'],
            ]);

        $response->assertOk();

        $data = $response->json('data');

        $sharedSecret = $ecc->sharedSecret($client['private_key'], $data['server_public_key']);
        $sessionKey = $this->deriveSessionKey($sharedSecret, $client['public_key'], $data['server_public_key']);

        return [
            'session_uuid' => $data['session_uuid'],
            'session_key' => $sessionKey,
        ];
    }

    private function deriveSessionKey(string $sharedSecret, string $clientPublicKeyB64, string $serverPublicKeyB64): string
    {
        $info = 'secure-messaging-app-als-session-key|'.$clientPublicKeyB64.'|'.$serverPublicKeyB64;

        return hash_hkdf('sha256', $sharedSecret, 32, $info);
    }

    private function alsPostJson(string $uri, array $payload, string $token, array $session): TestResponse
    {
        $cipher = new BlockCipherService();
        $envelope = $cipher->encrypt(json_encode($payload), $session['session_key']);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-ALS-Session' => $session['session_uuid'],
        ])->postJson($uri, $envelope);

        return $this->decryptAlsResponse($response, $session);
    }

    private function alsGetJson(string $uri, string $token, array $session): TestResponse
    {
        // BUG HISTORY: this used to do
        // $this->withHeaders([...])->call('GET', ...) — but Laravel's
        // raw call() method does NOT automatically apply
        // $this->defaultHeaders (set by withHeaders()); only the
        // convenience wrappers (get(), post(), json(), etc.) transform
        // defaultHeaders into server vars before calling call().
        // Bypassing those wrappers silently dropped the Authorization
        // and X-ALS-Session headers entirely.
        $cipher = new BlockCipherService();
        $envelope = $cipher->encrypt('{}', $session['session_key']);

        $server = $this->transformHeadersToServerVars([
            'Authorization' => "Bearer {$token}",
            'X-ALS-Session' => $session['session_uuid'],
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ]);

        $response = $this->call('GET', $uri, content: json_encode($envelope), server: $server);

        return $this->decryptAlsResponse($response, $session);
    }

    /**
     * Decrypts an ALS-wrapped response and swaps its content into the
     * same TestResponse so the usual ->assertJson()/->json() helpers
     * keep working transparently in the test body.
     */
    private function decryptAlsResponse(TestResponse $response, array $session): TestResponse
    {
        $cipher = new BlockCipherService();
        $envelope = json_decode($response->getContent(), true);

        if (! is_array($envelope) || ! isset($envelope['iv'], $envelope['ciphertext'], $envelope['tag'])) {
            throw new \RuntimeException(sprintf(
                "Expected an ALS-encrypted {iv, ciphertext, tag} envelope but got something else.\nHTTP status: %d\nRaw content: %s",
                $response->getStatusCode(),
                $response->getContent(),
            ));
        }

        $plaintext = $cipher->decrypt(
            $envelope['iv'],
            $envelope['ciphertext'],
            $envelope['tag'],
            $session['session_key'],
        );

        $response->baseResponse->setContent($plaintext);

        return $response;
    }
}
