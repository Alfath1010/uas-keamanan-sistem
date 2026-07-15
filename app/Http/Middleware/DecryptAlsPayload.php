<?php

namespace App\Http\Middleware;

use App\Exceptions\Crypto\ALSPayloadDecryptionException;
use App\Exceptions\Crypto\InvalidAlsSessionException;
use App\Services\CryptoManagerInterface;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Reference: architecture.md §3.8 (Middleware Pipeline),
 * crypto_design.md §4.2.5 (Payload Encryption)
 *
 * Runs AFTER authentication middleware (needs $request->user()) and
 * BEFORE the controller. Per architecture.md §3.8, controllers SHALL
 * process only decrypted request payloads and SHALL return plaintext
 * responses — this middleware performs both halves of that contract:
 *
 *   Request:  {iv, ciphertext, tag} -> decrypt -> plaintext JSON ->
 *             merged into $request so the controller sees normal
 *             input()/validated() data.
 *   Response: controller's plaintext JSON response -> encrypt ->
 *             {iv, ciphertext, tag} returned to the client.
 *
 * The entire response body (the standard {success, message, data}
 * envelope) is encrypted as a unit — not just a `data` sub-field —
 * matching "Responses SHALL follow the reverse sequence" in
 * crypto_design.md §4.2.5.
 */
class DecryptAlsPayload
{
    public function __construct(
        private readonly CryptoManagerInterface $crypto,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $sessionUuid = $request->header('X-ALS-Session');

        if (empty($sessionUuid)) {
            throw InvalidAlsSessionException::missingHeader();
        }

        // Throws InvalidAlsSessionException / AlsSessionExpiredException.
        $session = $this->crypto->resolveActiveSession($user, $sessionUuid);

        // BUG HISTORY: this used to unconditionally require and decrypt a
        // request body for every ALS-protected route, including GET. That
        // works against Laravel's low-level test client (which can attach a
        // body to any HTTP method), but real browsers enforce the Fetch
        // spec's rule that GET/HEAD requests MUST NOT carry a body —
        // calling fetch() with one throws a TypeError before the request is
        // even sent. Since a GET request has no meaningful client-to-server
        // payload anyway (everything it needs is in the URL), only methods
        // that can legitimately carry a body are required to supply a
        // decryptable one; GET/HEAD skip straight to encrypting the
        // response.
        if (! in_array($request->method(), ['GET', 'HEAD'], true)) {
            $envelope = json_decode($request->getContent(), true);

            if (! is_array($envelope) || ! isset($envelope['iv'], $envelope['ciphertext'], $envelope['tag'])) {
                throw new ALSPayloadDecryptionException();
            }

            $plaintext = $this->crypto->decryptRequestPayload($session, $envelope);
            $decoded = json_decode($plaintext, true) ?? [];

            // Replace the request's input with the decrypted payload so
            // controllers/form requests see normal, plaintext JSON input.
            $request->replace($decoded);
        }

        // Stash the session for the controller/response phase without
        // widening the middleware's public surface.
        $request->attributes->set('als_session', $session);

        $response = $this->handleNext($next, $request);

        $encrypted = $this->crypto->encryptResponsePayload($session, $response->getContent());
        $response->setContent(json_encode($encrypted));

        return $response;
    }

    /**
     * Runs the rest of the pipeline, converting any exception thrown
     * by the controller/business layer into the same JSON error
     * response Laravel's normal exception handling would produce
     * (respecting every custom render() callback registered in
     * bootstrap/app.php), WITHOUT letting it bypass this middleware's
     * encryption step.
     *
     * BUG HISTORY: originally this just called $next($request)
     * directly. Any exception thrown by the controller (a business
     * exception, a validation failure, anything) unwound straight
     * past the encryption step below, since a thrown exception never
     * "returns" — Laravel's global handler would render it correctly,
     * but that rendering happens OUTSIDE this middleware, so the
     * error response left this method as plain, unencrypted JSON on
     * a route that's supposed to be fully ALS-protected. A client
     * expecting every response on this route to be an
     * {iv, ciphertext, tag} envelope would fail to parse it.
     */
    private function handleNext(Closure $next, Request $request): Response
    {
        try {
            return $next($request);
        } catch (\Throwable $e) {
            return app(\Illuminate\Contracts\Debug\ExceptionHandler::class)->render($request, $e);
        }
    }
}
