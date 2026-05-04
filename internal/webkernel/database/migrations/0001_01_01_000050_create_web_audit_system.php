<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Instance-level audit log.
 *
 * Append-only. No updated_at — records are never mutated after creation.
 *
 * actor_id is nullable to allow system-initiated actions (cron jobs, queue
 * workers, CLI commands) that have no authenticated user.
 */
return new class extends Migration
{
    protected $connection = 'webkernel_sqlite';

    private const ID_LENGTH = 26;

    public function up(): void
    {
        Schema::connection($this->connection)->create('audit_logs', function (Blueprint $table): void {
            $table->char('id', self::ID_LENGTH)->primary();

            // NULL = system/automated action
            $table->char('actor_id', self::ID_LENGTH)->nullable();
            $table->foreign('actor_id')
                  ->references('id')
                  ->on('users')
                  ->nullOnDelete();

            // Verb-noun action: 'domain_created', 'module_installed', 'business_suspended'
            $table->string('action', 80);

            // Polymorphic resource reference
            $table->string('resource_type', 60)->nullable();
            $table->char('resource_id', self::ID_LENGTH)->nullable();

            // JSON diff: {before: {...}, after: {...}}
            $table->json('changes_json')->nullable();

            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();

            $table->timestamp('created_at')->nullable();

            // Query patterns: resource timeline + actor history
            $table->index(['resource_type', 'resource_id', 'created_at'], 'audit_resource');
            $table->index(['actor_id', 'created_at'], 'audit_actor');
            $table->index(['action', 'created_at'], 'audit_action');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('audit_logs');
    }
};
