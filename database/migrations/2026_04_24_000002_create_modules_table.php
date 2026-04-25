<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Webkernel modules table.
 *
 * Tracks every module installed on this instance.
 * Modules are first-class citizens: each can have its own domain, its own
 * Filament panel, and optionally its own database connection.
 *
 * status values: 'enabled' | 'disabled' | 'installing' | 'error'
 */
return new class extends Migration
{
    protected $connection = 'webkernel_sqlite';

    private const ID_LENGTH = 26;

    public function up(): void
    {
        Schema::connection($this->connection)->create('modules', function (Blueprint $table): void {
            $table->char('id', self::ID_LENGTH)->primary();

            // Human-readable name: "CRM", "HR Suite", "Finance Desk"
            $table->string('name');

            // Composer vendor: "webkernel", "acme", "mycompany"
            $table->string('vendor');

            // URL/machine-safe slug: "crm", "hr-suite", "finance-desk"
            $table->string('slug')->unique();

            // Semver: "1.0.0"
            $table->string('version', 30);

            // 'enabled' | 'disabled' | 'installing' | 'error'
            $table->string('status', 20)->default('enabled');

            // Module-level configuration (JSON blob, module defines its own schema)
            $table->json('config_json')->nullable();

            $table->timestamps();

            $table->index(['vendor', 'slug']);
            $table->index('status');
        });

        // ── business_module_map ───────────────────────────────────────────────
        // Which modules are enabled for which businesses, and their per-business
        // configuration overrides.
        Schema::connection($this->connection)->create('business_module_map', function (Blueprint $table): void {
            $table->char('id', self::ID_LENGTH)->primary();

            $table->char('business_id', self::ID_LENGTH);
            $table->foreign('business_id')
                  ->references('id')
                  ->on('businesses')
                  ->cascadeOnDelete();

            $table->char('module_id', self::ID_LENGTH);
            $table->foreign('module_id')
                  ->references('id')
                  ->on('modules')
                  ->cascadeOnDelete();

            $table->boolean('is_enabled')->default(true);

            // Per-business config overrides (merged with module-level config_json)
            $table->json('config_json')->nullable();

            $table->timestamp('created_at')->nullable();

            $table->unique(['business_id', 'module_id'], 'bmm_unique');
            $table->index(['business_id', 'is_enabled'], 'bmm_business_enabled');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('business_module_map');
        Schema::connection($this->connection)->dropIfExists('modules');
    }
};
