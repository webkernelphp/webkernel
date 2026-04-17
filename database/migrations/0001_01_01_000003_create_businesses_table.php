<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Webkernel core businesses schema.
 *
 * A Business is one isolated tenant workspace inside a Webkernel instance.
 * The App Owner creates Businesses and invites a Business-Admin per Business.
 * Business-Admins manage their own users, departments, and modules without
 * any access to the infrastructure layer.
 *
 * ── ID strategy ──────────────────────────────────────────────────────────────
 * char(26) cuid2 string PK — matches users.id for cross-table consistency.
 *
 * ── Connection ───────────────────────────────────────────────────────────────
 * webkernel_sqlite — same core connection as users and user_privileges.
 */
return new class extends Migration
{
    protected $connection = 'webkernel_sqlite';

    private const ID_LENGTH = 26;

    public function up(): void
    {
        Schema::connection($this->connection)->create('businesses', function (Blueprint $table): void {
            $table->char('id', self::ID_LENGTH)->primary();

            $table->string('name');
            $table->string('slug', 63)->unique();
            $table->string('status', 20)->default('pending');

            // Email of the invited Business-Admin.
            // Stored independently so the record exists before the admin accepts.
            $table->string('admin_email');

            // The user who created this Business (always the App Owner
            // during the installer; can be a Super-User later).
            $table->char('created_by', self::ID_LENGTH)->nullable();

            $table->foreign('created_by')
                  ->references('id')
                  ->on('users')
                  ->nullOnDelete();

            $table->timestamps();

            $table->index('status');
            $table->index('created_by');
            $table->index('admin_email');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('businesses');
    }
};
