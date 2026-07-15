<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * als_sessions table
 *
 * Reference: db_design.md §5.4 (als_sessions)
 *
 * Stores active Application Layer Security sessions established via
 * the X25519 ECDH handshake (crypto_design.md §4.2). The session_key
 * column holds the HKDF-derived AES-256-GCM key, encrypted at rest
 * using Laravel's encryption facilities (Crypt::encryptString), per
 * the "Implementation Note" in db_design.md and SEC-007.
 *
 * Sessions are temporary data (db_design.md §5.8) and MAY be purged by
 * a scheduled maintenance job once expired.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('als_sessions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->text('session_key');
            $table->timestamp('expires_at');
            $table->timestamp('created_at')->useCurrent();

            $table->index('user_id');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('als_sessions');
    }
};
