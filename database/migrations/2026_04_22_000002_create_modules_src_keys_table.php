<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const ULID = 26;

    public function up(): void
    {
        // ── inst_modules_src_keys ────────────────────────────────────────────────
        Schema::connection('webkernel_sqlite')->create('inst_modules_src_keys', function (Blueprint $table) {
            $table->char('id', self::ULID)->primary();
            $table->string('registry');          // e.g. "github", "gitlab", "http"
            $table->string('vendor')->nullable(); // org / username
            $table->longText('token_encrypted'); // CryptData::encrypt()
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['registry', 'vendor']);
            $table->index('registry');
        });

        // ── inst_registry_accounts ───────────────────────────────────────────────
        Schema::connection('webkernel_sqlite')->create('inst_registry_accounts', function (Blueprint $table) {
            $table->char('id', self::ULID)->primary();
            $table->string('registry');                    // "github", "gitlab", "http"
            $table->string('account_name');
            $table->string('account_email')->nullable();
            $table->string('account_type');                // "personal" | "organization"
            $table->longText('token_encrypted');
            $table->longText('metadata_encrypted')->nullable();
            $table->boolean('verified')->default(false);
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['registry', 'account_name']);
            $table->index('registry');
            $table->index(['active', 'registry']);
        });

        // ── inst_webkernel_releases ──────────────────────────────────────────────
        // Local mirror of every release/tag fetched from any registry.
        // Stores all GitHub data so we never need to re-fetch known releases.
        Schema::connection('webkernel_sqlite')->create('inst_webkernel_releases', function (Blueprint $table) {
            $table->char('id', self::ULID)->primary();
            $table->string('target_type');              // "webkernel" | "module"
            $table->string('target_slug');              // "foundation" | module composer slug
            $table->string('registry');                 // "github" | "gitlab" | "http"

            // ── version fields ───────────────────────────────────────────────────
            $table->string('tag_name');                 // raw tag: "1.9.3+1"
            $table->string('version');                  // semver part: "1.9.3"
            $table->string('build')->nullable();        // build meta: "1"

            // ── git data ─────────────────────────────────────────────────────────
            $table->string('commit_sha', 40)->nullable();
            $table->string('node_id')->nullable();      // GitHub node_id
            $table->string('zipball_url')->nullable();
            $table->string('tarball_url')->nullable();

            // ── release data (only for full GitHub releases, not bare tags) ──────
            $table->bigInteger('github_release_id')->nullable()->unsigned();
            $table->string('release_name')->nullable();  // "v1.9.3+1"
            $table->text('release_notes')->nullable();   // body markdown
            $table->boolean('is_prerelease')->default(false);
            $table->boolean('is_draft')->default(false);
            $table->timestamp('published_at')->nullable();

            $table->timestamps();

            $table->unique(['target_type', 'target_slug', 'tag_name']);
            $table->index(['target_type', 'target_slug', 'is_prerelease', 'is_draft', 'published_at']);
            $table->index('github_release_id');
        });

        // ── inst_webkernel_update_checks ─────────────────────────────────────────
        // Append-only log of every update check attempt.
        // Used for: rate-limit enforcement, scheduler auditing, auto-update triggers.
        Schema::connection('webkernel_sqlite')->create('inst_webkernel_update_checks', function (Blueprint $table) {
            $table->char('id', self::ULID)->primary();
            $table->string('target_type');              // "webkernel" | "module"
            $table->string('target_slug');              // "foundation" | module slug
            $table->string('registry');                 // "github" | "gitlab" | "http"

            // ── outcome ──────────────────────────────────────────────────────────
            $table->string('status');                   // "success" | "error" | "rate_limited" | "skipped"
            $table->string('latest_tag_found')->nullable();  // newest tag discovered
            $table->integer('releases_synced')->default(0);  // new rows written to inst_webkernel_releases
            $table->text('error_message')->nullable();

            // ── rate-limit tracking (from GitHub API response headers) ────────────
            $table->integer('http_status')->nullable();
            $table->integer('rate_limit_remaining')->nullable();
            $table->timestamp('rate_limit_reset_at')->nullable();

            // ── timing ───────────────────────────────────────────────────────────
            $table->timestamp('checked_at');

            $table->index(['target_type', 'target_slug', 'checked_at']);
            $table->index(['status', 'checked_at']);
        });
    }

    public function down(): void
    {
        Schema::connection('webkernel_sqlite')->dropIfExists('inst_webkernel_update_checks');
        Schema::connection('webkernel_sqlite')->dropIfExists('inst_webkernel_releases');
        Schema::connection('webkernel_sqlite')->dropIfExists('inst_registry_accounts');
        Schema::connection('webkernel_sqlite')->dropIfExists('inst_modules_src_keys');
    }
};
