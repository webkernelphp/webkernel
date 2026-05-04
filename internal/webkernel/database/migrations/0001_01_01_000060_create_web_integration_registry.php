<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Webkernel integration registry system.
 *
 * Stores module source registry credentials, accounts, release metadata,
 * and update check logs for managing module installations and updates.
 */
return new class extends Migration
{
    private const ULID = 26;

    public function up(): void
    {
        // ── instance_module_source_keys ────────────────────────────────────────────────
        // Registry authentication credentials for pulling modules.
        Schema::connection('webkernel_sqlite')->create('instance_module_source_keys', function (Blueprint $table) {
            $table->char('id', self::ULID)->primary();
            $table->string('registry');          // e.g. "github", "gitlab", "http"
            $table->string('vendor')->nullable(); // org / username
            $table->longText('token_encrypted'); // CryptData::encrypt()
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['registry', 'vendor']);
            $table->index('registry');
        });

        // ── instance_registry_accounts ───────────────────────────────────────────────
        // Authenticated accounts on external registries.
        Schema::connection('webkernel_sqlite')->create('instance_registry_accounts', function (Blueprint $table) {
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

        // ── instance_releases ──────────────────────────────────────────────
        // Local mirror of every release/tag fetched from any registry.
        // Stores all GitHub data so we never need to re-fetch known releases.
        Schema::connection('webkernel_sqlite')->create('instance_releases', function (Blueprint $table) {
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

            // ── annotated tag data (from GET /repos/{owner}/{repo}/git/tags/{sha}) ──
            $table->longText('tag_annotation')->nullable();   // raw annotated tag message
            $table->string('tagger_name')->nullable();        // git tagger display name
            $table->string('tagger_email')->nullable();       // git tagger email
            $table->timestamp('tagged_at')->nullable();       // when the tag was created

            // ── release-meta embedded in the tag annotation (JSON) ───────────────
            // Structure mirrors bootstrap/webkernel/release-meta.php:
            //   codename, notes, features[], doc_links[], video
            $table->string('codename')->nullable();           // e.g. "Sovereign"
            $table->longText('meta_notes')->nullable();       // release notes (markdown)
            $table->longText('meta_features')->nullable();    // JSON — features[]
            $table->longText('meta_doc_links')->nullable();   // JSON — doc_links[]
            $table->string('meta_video_url')->nullable();     // video URL

            // ── release data (only for full GitHub releases, not bare tags) ──────
            $table->bigInteger('github_release_id')->nullable()->unsigned();
            $table->string('release_name')->nullable();       // "v1.9.3+1"
            $table->text('release_notes')->nullable();        // GitHub release body markdown
            $table->boolean('is_prerelease')->default(false);
            $table->boolean('is_draft')->default(false);
            $table->timestamp('created_at_github')->nullable(); // GitHub release created_at
            $table->timestamp('published_at')->nullable();

            // ── release author ────────────────────────────────────────────────────
            $table->string('author_login')->nullable();       // GitHub login
            $table->string('author_avatar_url')->nullable();  // GitHub avatar URL

            // ── release assets ────────────────────────────────────────────────────
            $table->longText('assets_json')->nullable();      // JSON array of asset objects

            // ── extra GitHub metadata ─────────────────────────────────────────────
            $table->string('discussion_url')->nullable();
            $table->unsignedInteger('reactions_total')->nullable();

            $table->timestamps();

            $table->unique(['target_type', 'target_slug', 'tag_name']);
            $table->index(['target_type', 'target_slug', 'is_prerelease', 'is_draft', 'published_at']);
            $table->index('github_release_id');
        });

        // ── instance_update_checks ─────────────────────────────────────────
        // Append-only log of every update check attempt.
        // Used for: rate-limit enforcement, scheduler auditing, auto-update triggers.
        Schema::connection('webkernel_sqlite')->create('instance_update_checks', function (Blueprint $table) {
            $table->char('id', self::ULID)->primary();
            $table->string('target_type');              // "webkernel" | "module"
            $table->string('target_slug');              // "foundation" | module slug
            $table->string('registry');                 // "github" | "gitlab" | "http"

            // ── outcome ──────────────────────────────────────────────────────────
            $table->string('status');                   // "success" | "error" | "rate_limited" | "skipped"
            $table->string('latest_tag_found')->nullable();  // newest tag discovered
            $table->integer('releases_synced')->default(0);  // new rows written to instance_releases
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
        Schema::connection('webkernel_sqlite')->dropIfExists('instance_update_checks');
        Schema::connection('webkernel_sqlite')->dropIfExists('instance_releases');
        Schema::connection('webkernel_sqlite')->dropIfExists('instance_registry_accounts');
        Schema::connection('webkernel_sqlite')->dropIfExists('instance_module_source_keys');
    }
};
