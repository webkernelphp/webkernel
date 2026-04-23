<?php declare(strict_types=1);

namespace Webkernel\BackOffice\System\Domain\Updates\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Local mirror of a single release / git tag fetched from any registry.
 *
 * @property string       $id
 * @property string       $target_type        "webkernel" | "module"
 * @property string       $target_slug        "foundation" | composer slug
 * @property string       $registry           "github" | "gitlab" | "http"
 * @property string       $tag_name           raw tag e.g. "1.9.3+1"
 * @property string       $version            semver part e.g. "1.9.3"
 * @property string|null  $build              build meta e.g. "1"
 * @property string|null  $commit_sha
 * @property string|null  $node_id
 * @property string|null  $zipball_url
 * @property string|null  $tarball_url
 * @property string|null  $tag_annotation     raw annotated tag message
 * @property string|null  $tagger_name
 * @property string|null  $tagger_email
 * @property Carbon|null  $tagged_at
 * @property string|null  $codename           e.g. "Sovereign"
 * @property string|null  $meta_notes         release notes markdown
 * @property string|null  $meta_features      JSON — features[]
 * @property string|null  $meta_doc_links     JSON — doc_links[]
 * @property string|null  $meta_video_url     video URL
 * @property int|null     $github_release_id
 * @property string|null  $release_name
 * @property string|null  $release_notes      GitHub release body
 * @property bool         $is_prerelease
 * @property bool         $is_draft
 * @property Carbon|null  $created_at_github
 * @property Carbon|null  $published_at
 * @property string|null  $author_login
 * @property string|null  $author_avatar_url
 * @property string|null  $assets_json        JSON array of asset objects
 * @property string|null  $discussion_url
 * @property int|null     $reactions_total
 * @property Carbon       $created_at
 * @property Carbon       $updated_at
 */
class WebkernelRelease extends Model
{
    protected $table      = 'inst_webkernel_releases';
    protected $connection = 'webkernel_sqlite';
    protected $keyType    = 'string';
    public    $incrementing = false;

    protected $fillable = [
        'id',
        'target_type',
        'target_slug',
        'registry',
        'tag_name',
        'version',
        'build',
        'commit_sha',
        'node_id',
        'zipball_url',
        'tarball_url',
        // annotated tag
        'tag_annotation',
        'tagger_name',
        'tagger_email',
        'tagged_at',
        // release-meta (decoded from tag annotation)
        'codename',
        'meta_notes',
        'meta_features',
        'meta_doc_links',
        'meta_video_url',
        // GitHub release
        'github_release_id',
        'release_name',
        'release_notes',
        'is_prerelease',
        'is_draft',
        'created_at_github',
        'published_at',
        // author + assets
        'author_login',
        'author_avatar_url',
        'assets_json',
        'discussion_url',
        'reactions_total',
    ];

    protected $casts = [
        'is_prerelease'     => 'boolean',
        'is_draft'          => 'boolean',
        'github_release_id' => 'integer',
        'reactions_total'   => 'integer',
        'tagged_at'         => 'datetime',
        'created_at_github' => 'datetime',
        'published_at'      => 'datetime',
    ];

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeForTarget(Builder $query, string $type, string $slug): Builder
    {
        return $query->where('target_type', $type)->where('target_slug', $slug);
    }

    public function scopeStable(Builder $query): Builder
    {
        return $query->where('is_prerelease', false)->where('is_draft', false);
    }

    // ── Meta accessors ────────────────────────────────────────────────────────

    /** Decoded features array from the tag annotation. */
    public function metaFeatures(): array
    {
        return json_decode($this->meta_features ?? '[]', true) ?: [];
    }

    /** Decoded doc_links array from the tag annotation. */
    public function metaDocLinks(): array
    {
        return json_decode($this->meta_doc_links ?? '[]', true) ?: [];
    }

    /** YouTube video ID extracted from meta_video_url, or empty string. */
    public function metaVideoId(): string
    {
        if (!$this->meta_video_url) {
            return '';
        }
        if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&\n?]+)/', $this->meta_video_url, $m)) {
            return $m[1];
        }
        return '';
    }

    /** Decoded assets array. */
    public function assets(): array
    {
        return json_decode($this->assets_json ?? '[]', true) ?: [];
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Latest stable release for a given target, ordered by published_at then created_at.
     * Single source of truth for "what is newest?" — never stored as a boolean in the DB.
     */
    public static function latestStable(string $targetType, string $targetSlug): ?self
    {
        return static::forTarget($targetType, $targetSlug)
            ->stable()
            ->orderByDesc('published_at')
            ->orderByDesc('created_at')
            ->first();
    }

    /**
     * Build an unsaved WebkernelRelease from a raw GitHub tag payload.
     */
    public static function fromGitHubTag(array $tag, string $targetType, string $targetSlug): self
    {
        [$version, $build] = static::parseTag($tag['name'] ?? '');

        return new self([
            'target_type'  => $targetType,
            'target_slug'  => $targetSlug,
            'registry'     => 'github',
            'tag_name'     => $tag['name'],
            'version'      => $version,
            'build'        => $build,
            'commit_sha'   => $tag['commit']['sha'] ?? null,
            'node_id'      => $tag['node_id'] ?? null,
            'zipball_url'  => $tag['zipball_url'] ?? null,
            'tarball_url'  => $tag['tarball_url'] ?? null,
        ]);
    }

    /**
     * Enrich an existing record with data from a GitHub release payload.
     */
    public function applyGitHubRelease(array $release): self
    {
        $this->github_release_id = $release['id'] ?? null;
        $this->release_name      = $release['name'] ?? null;
        $this->release_notes     = $release['body'] ?? null;
        $this->is_prerelease     = (bool) ($release['prerelease'] ?? false);
        $this->is_draft          = (bool) ($release['draft'] ?? false);
        $this->published_at      = isset($release['published_at'])
            ? Carbon::parse($release['published_at'])
            : null;

        return $this;
    }

    // ── Internal ─────────────────────────────────────────────────────────────

    private static function parseTag(string $tagName): array
    {
        $tagName = ltrim($tagName, 'v');

        if (str_contains($tagName, '+')) {
            [$version, $build] = explode('+', $tagName, 2);
            return [$version, $build];
        }

        return [$tagName, null];
    }
}
