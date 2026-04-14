<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Webkernel core schema.
 *
 * Storage strategy
 * ----------------
 * This migration targets the *central* SQLite connection used by Webkernel
 * itself (kernel bootstrap, installer, privilege system).
 *
 * Each additional Webkernel module (or the host application) may later
 * declare its own connection and run its own migrations independently.
 * The APP_OWNER decides at install time whether extra modules share this
 * SQLite file or point to a dedicated PostgreSQL / MySQL database.
 *
 * Columns added beyond the Laravel default
 * -----------------------------------------
 * users.avatar_url                  -- Filament HasAvatar contract
 * users.app_authentication_secret   -- Filament MFA (app/TOTP)
 * users.app_authentication_recovery_codes -- Filament MFA recovery
 * users.has_email_authentication    -- Filament MFA (email OTP)
 */
return new class extends Migration
{
    // -------------------------------------------------------------------------
    // Connection
    // -------------------------------------------------------------------------

    /**
     * Force this migration to run on the central Webkernel connection.
     * Override in the host app if the installer stores core data elsewhere.
     */
    protected $connection = 'webkernel';

    // -------------------------------------------------------------------------
    // Up
    // -------------------------------------------------------------------------

    public function up(): void
    {
        // -- users ------------------------------------------------------------
        Schema::connection($this->connection)->create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();

            // Filament HasAvatar
            $table->string('avatar_url')->nullable();

            // Filament MFA -- app (TOTP)
            $table->text('app_authentication_secret')->nullable();
            $table->text('app_authentication_recovery_codes')->nullable();

            // Filament MFA -- email OTP
            $table->boolean('has_email_authentication')->default(false);

            $table->timestamps();
        });

        // -- user_privileges --------------------------------------------------
        // One-to-one: a user has at most one privilege record.
        // The privilege column stores the UserPrivilegeLevel enum value.
        // Allowed values: 'app-owner' | 'super-user' | 'member'
        Schema::connection($this->connection)->create('user_privileges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->unique()
                ->constrained('users')
                ->cascadeOnDelete();
            $table->string('privilege');
            $table->timestamps();

            $table->index('privilege');
        });

        // -- password_reset_tokens --------------------------------------------
        Schema::connection($this->connection)->create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        // -- sessions ---------------------------------------------------------
        Schema::connection($this->connection)->create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    // -------------------------------------------------------------------------
    // Down
    // -------------------------------------------------------------------------

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('sessions');
        Schema::connection($this->connection)->dropIfExists('password_reset_tokens');
        Schema::connection($this->connection)->dropIfExists('user_privileges');
        Schema::connection($this->connection)->dropIfExists('users');
    }
};
