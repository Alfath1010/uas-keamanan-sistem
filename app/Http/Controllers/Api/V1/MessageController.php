<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMessageRequest;
use App\Http\Responses\ApiResponse;
use App\Models\Message;
use App\Models\User;
use App\Services\Messaging\MessageServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Reference: api_spec.md §6.6 (Messages), FR-005, FR-006
 *
 * Both actions run behind the DecryptAlsPayload middleware (see
 * routes/api.php) — $request already contains the decrypted
 * plaintext JSON payload by the time it reaches here.
 */
class MessageController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly MessageServiceInterface $messages,
    ) {}

    public function store(StoreMessageRequest $request): JsonResponse
    {
        // `signature` arrives as a validated {e, s} array (or null);
        // stored as a JSON string in the longText `signature` column.
        $signature = $request->input('signature');

        $message = $this->messages->send(
            $request->user(),
            $request->string('conversation_uuid'),
            $request->string('ciphertext_for_recipient'),
            $request->string('ciphertext_for_sender'),
            $signature !== null ? json_encode($signature) : null,
            (bool) $request->input('signed'),
        );

        return $this->success(
            'Pesan berhasil disimpan.',
            ['message_uuid' => $message->uuid],
            201,
        );
    }

    public function index(Request $request, string $conversationUuid): JsonResponse
    {
        $messages = $this->messages->listForConversation($request->user(), $conversationUuid);

        return $this->success('Daftar pesan berhasil diambil.', [
            'messages' => $messages->map(fn (Message $m) => $this->transform($m, $request->user()))->all(),
        ]);
    }

    /**
     * Resolves which of the two stored ciphertexts belongs to the
     * requesting user and exposes it as a single `ciphertext` field —
     * the dual-encryption detail (see the messages migration's
     * docblock) is entirely hidden from API consumers.
     */
    private function transform(Message $message, User $requestingUser): array
    {
        $ciphertext = $message->sender_id === $requestingUser->id
            ? $message->ciphertext_for_sender
            : $message->ciphertext_for_recipient;

        return [
            'uuid' => $message->uuid,
            'sender_uuid' => $message->sender->uuid,
            'ciphertext' => $ciphertext,
            'signature' => $message->signature ? json_decode($message->signature, true) : null,
            'signed' => $message->signed,
            'created_at' => $message->created_at->toIso8601String(),
        ];
    }
}
