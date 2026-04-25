<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Webkernel domains table.
 *
 * THE critical routing table. Every incoming HTTP request resolves its Host
 * header against this table to determine which business panel to render.
 *
 * panel_type discriminator
 * ────────────────────────
 * 'system'   → Webkernel System Panel (App Owner only)
 * 'business' → Business Panel for a specific business
 * 'module'   → Module Panel (module_id required)
 *
 * SSL fields
 * ──────────
 * Populated by CertificateManager after Let's Encrypt issuance.
 * Wildcard subdomain certs leave these null (covered by the root cert).
 */
return new class extends Migration
{
    protected $connection = 'webkernel_sqlite';

    private const ID_LENGTH = 26;

    public function up(): void
    {
        Schema::connection($this->connection)->create('domains', function (Blueprint $table): void {
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
                  ->on('modules')
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
        Schema::connection($this->connection)->dropIfExists('domains');
    }
};
