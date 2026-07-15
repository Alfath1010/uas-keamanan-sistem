<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * users table
 *
 * Reference: db_design.md §5.4 (users)
 *
 * - Internal PK is auto-incrementing BIGINT for relational efficiency.
 * - Public-facing identifier is a UUID (db_design.md §5.1, ADR-001 in
 *   system_requirements.md).
 * - Passwords are hashed via Laravel's password hashing facilities
 *   (never stored in plaintext) per db_design.md notes and SEC-007.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
