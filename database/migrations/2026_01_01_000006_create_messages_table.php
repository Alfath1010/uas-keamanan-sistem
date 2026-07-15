<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * messages table
 *
 * Reference: db_design.md §5.4 (messages)
 *
 * Security notes (db_design.md, FR-006, SEC-002): this table SHALL
 * NEVER contain plaintext, session keys, or private keys. Only
 * ciphertext (E2EE, ECC-sealed) and an optional Schnorr signature are
 * persisted.
 *
 * DEVIATION: db_design.md specifies a single `ciphertext` column.
 * Since ECC sealed-box encryption is one-directional (a message
 * sealed to the recipient's public key cannot be opened by ANY other
 * keypair, including the sender's own), the sender would otherwise be
 * permanently unable to read their own sent messages — unlike every
 * mainstream E2E messenger, which encrypts each outgoing message
 * twice: once to the recipient, once to the sender's own key. This
 * table stores both ciphertexts; the API layer picks whichever one
 * matches the requesting user and exposes it as a single `ciphertext`
 * field, so this is invisible to API consumers (see
 * MessageController::transform()).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('conversation_id')
                ->constrained('conversations')
                ->restrictOnDelete();
            $table->foreignId('sender_id')
                ->constrained('users')
                ->restrictOnDelete();
            $table->longText('ciphertext_for_recipient');
            $table->longText('ciphertext_for_sender');
            $table->longText('signature')->nullable();
            $table->boolean('signed')->default(false);
            $table->timestamps();

            $table->index('conversation_id');
            $table->index('sender_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
