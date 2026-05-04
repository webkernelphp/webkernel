<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Webkernel module system — modules, their business assignments, and routing.
 */
return new class extends Migration
{
    protected $connection = 'webkernel_sqlite';

    private const ID_LENGTH = 26;

    public function up(): void
    {
        // ── system_modules ────────────────────────────────────────────────────
        // Tracks every module installed on this instance.
        // Modules are first-class citizens: each can have its own domain, its own
        // Filament panel, and optionally its own database connection.
        Schema::connection($this->connection)->create('system_modules', function (Blueprint $table): void {
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

        // ── business_module_mappings ──────────────────────────────────────────
        // Which modules are enabled for which businesses, and their per-business
        // configuration overrides.
        Schema::connection($this->connection)->create('business_module_mappings', function (Blueprint $table): void {
            $table->char('id', self::ID_LENGTH)->primary();

            $table->char('business_id', self::ID_LENGTH);
            $table->foreign('business_id')
                  ->references('id')
                  ->on('businesses')
                  ->cascadeOnDelete();

            $table->char('module_id', self::ID_LENGTH);
            $table->foreign('module_id')
                  ->references('id')
                  ->on('system_modules')
                  ->cascadeOnDelete();

            $table->boolean('is_enabled')->default(true);

            // Per-business config overrides (merged with module-level config_json)
            $table->json('config_json')->nullable();

            $table->timestamp('created_at')->nullable();

            $table->unique(['business_id', 'module_id'], 'bmm_unique');
            $table->index(['business_id', 'is_enabled'], 'bmm_business_enabled');
        });

        // ── base_domains ──────────────────────────────────────────────────────
        // THE critical routing table. Every incoming HTTP request resolves its Host
        // header against this table to determine which business panel to render.
        //
        // panel_type discriminator
        // ─────────────────────────
        // 'system'   → Webkernel System Panel (App Owner only)
        // 'business' → Business Panel for a specific business
        // 'module'   → Module Panel (module_id required)
        //
        // SSL fields
        // ──────────
        // Populated by CertificateManager after Let's Encrypt issuance.
        // Wildcard subdomain certs leave these null (covered by the root cert).
        Schema::connection($this->connection)->create('base_domains', function (Blueprint $table): void {
            $table->char('id', self::ID_LENGTH)->primary();

            // The fully-qualified domain name matched against Host header.
            $table->string('domain')->unique();

            $table->char('business_id', self::ID_LENGTH);
            $table->foreign('business_id')
                  ->references('id')
                  ->on('businesses')
                  ->cascadeOnDelete();

            // 'system' | 'business' | 'module'
            $table->string('panel_type', 20);

            // NULL unless panel_type='module'
            $table->char('module_id', self::ID_LENGTH)->nullable();
            $table->foreign('module_id')
                  ->references('id')
                  ->on('system_modules')
                  ->nullOnDelete();

            $table->boolean('is_primary')->default(false);
            $table->boolean('is_active')->default(true);

            // SSL certificate paths (null = covered by wildcard cert)
            $table->string('ssl_cert_path')->nullable();
            $table->string('ssl_key_path')->nullable();
            $table->timestamp('ssl_expires_at')->nullable();

            $table->timestamps();

            // Primary lookup: host resolution (hot path on every request)
            $table->index(['domain', 'is_active'], 'domains_host_active');
            $table->index('business_id', 'domains_business');
            $table->index('panel_type', 'domains_panel_type');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('base_domains');
        Schema::connection($this->connection)->dropIfExists('business_module_mappings');
        Schema::connection($this->connection)->dropIfExists('system_modules');
    }
};
