<?php declare(strict_types=1);
namespace Webkernel\Arcanes\Scaffold;

/**
 * Typed, immutable parameter object for one scaffold run.
 *
 * Module-only fields default to empty string — they are simply
 * unused for aptitudes. One DTO for two artifact types.
 */
final readonly class ScaffoldParams
{
    public function __construct(
        public string $type,
        public string $targetPath,
        public string $slug,
        public string $pascal,
        public string $folderName,
        public string $namespace,
        public string $id,
        public string $label,
        public string $description,
        public string $version,
        public string $phpVersion,
        public string $laravelVersion,
        /** @var list<string> */
        public array  $enabledSlots,
        /** @var list<string> */
        public array  $extraDirs,
        public string $vendor      = '',
        public string $registry    = '',
        public string $party       = 'third',
        public string $authorName  = '',
        public string $authorEmail = '',
        public string $authorUrl   = '',
        public string $license     = 'proprietary',
        public string $presetKey   = '',
        public string $presetLabel = '',
    ) {}

    public function isModule(): bool   { return $this->type === 'module'; }
    public function isAptitude(): bool { return $this->type === 'aptitude'; }

    /** Handle used in view_namespaces and lang_paths keys. */
    public function handle(): string
    {
        return $this->isModule()
            ? "{$this->vendor}-{$this->slug}"
            : "webkernel-{$this->slug}";
    }
}
