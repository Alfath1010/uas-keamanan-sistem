<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Reference: db_design.md §5.4 (conversation_members)
 *
 * Explicit pivot model (rather than a bare pivot table) so it can be
 * queried directly by repositories for authorization checks — e.g.
 * "is user X a participant of conversation Y" (FT-CONV-003, MSG002).
 *
 * No UUID: this is an internal join record, never exposed directly
 * through the API (only via conversation/user UUIDs).
 */
class ConversationMember extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'conversation_id',
        'user_id',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
