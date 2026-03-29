<?php declare(strict_types=1);
namespace Webkernel\Arcanes\Scaffold;

final class ScaffoldResult
{
    /** @var list<string> */
    private array $files = [];
    /** @var list<string> */
    private array $dirs  = [];

    public function recordFile(string $path): void { $this->files[] = $path; }
    public function recordDir(string $path): void  { $this->dirs[]  = $path; }

    /** @return list<string> */
    public function files(): array     { return $this->files; }
    /** @return list<string> */
    public function dirs(): array      { return $this->dirs; }
    public function fileCount(): int   { return count($this->files); }
    public function dirCount(): int    { return count($this->dirs); }
}
