<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Reference: db_design.md §5.4 (conversations)
 *
 * Represents a one-to-one conversation (FR-003). Participation is
 * resolved through the conversation_members pivot.
 */
class Conversation extends Model
{
    use HasFactory;
    use HasUuid;

    protected $fillable = [];

    /**
     * Users participating in this conversation.
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'conversation_members'
        );
    }

    /**
     * Membership rows (used internally for authorization checks).
     */
    public function conversationMembers(): HasMany
    {
        return $this->hasMany(ConversationMember::class);
    }

    /**
     * Encrypted messages belonging to this conversation.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }
}
