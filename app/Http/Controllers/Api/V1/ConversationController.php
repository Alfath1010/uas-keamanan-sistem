<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreConversationRequest;
use App\Http\Responses\ApiResponse;
use App\Models\Conversation;
use App\Services\Messaging\ConversationServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Reference: api_spec.md §6.5 (Conversations), FR-003
 */
class ConversationController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly ConversationServiceInterface $conversations,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $conversations = $this->conversations->listForUser($request->user());

        return $this->success('Daftar percakapan berhasil diambil.', [
            'conversations' => $conversations->map(
                fn (Conversation $c) => $this->transform($c)
            )->all(),
        ]);
    }

    public function store(StoreConversationRequest $request): JsonResponse
    {
        $conversation = $this->conversations->create(
            $request->user(),
            $request->string('recipient_email'),
        );

        return $this->success(
            'Percakapan berhasil dibuat.',
            $this->transform($conversation),
            201,
        );
    }

    public function show(Request $request, string $conversationUuid): JsonResponse
    {
        $conversation = $this->conversations->getForUser($request->user(), $conversationUuid);

        return $this->success('Percakapan berhasil diambil.', $this->transform($conversation));
    }

    private function transform(Conversation $conversation): array
    {
        return [
            'uuid' => $conversation->uuid,
            'members' => $conversation->members->map(fn ($m) => [
                'uuid' => $m->uuid,
                'name' => $m->name,
                'email' => $m->email,
            ])->all(),
            'created_at' => $conversation->created_at->toIso8601String(),
        ];
    }
}
