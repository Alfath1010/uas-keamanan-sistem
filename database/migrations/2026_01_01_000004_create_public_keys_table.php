<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * public_keys table
 *
 * Reference: db_design.md §5.4 (public_keys)
 *
 * Stores only public cryptographic material. Private keys never exist
 * server-side (FR-009, ADR-007, SEC-005).
 *
 * - ecc_public_key: X25519 public key (base64), used for E2EE
 *   (crypto_design.md §4.3, sodium_crypto_box_seal).
 * - schnorr_public_key: classical discrete-log Schnorr public key
 *   v = alpha^-x mod p (base64/decimal string), used for signature
 *   verification (crypto_design.md §4.4).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('public_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->unique()
                ->constrained('users')
                ->cascadeOnDelete();
            $table->text('ecc_public_key');
            $table->text('schnorr_public_key');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('public_keys');
    }
};
