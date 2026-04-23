<?php declare(strict_types=1);

namespace Webkernel\System\Ops\Providers;

use Illuminate\Support\Collection;
use Webkernel\System\Ops\Contracts\Provider;

/**
 * File provider for reading CSV, JSON, XLSX, etc.
 *
 * Usage:
 *   webkernel()->do()
 *       ->from(FileProvider::csv('data.csv'))
 *       ->filter(fn($r) => $r['active'])
 *       ->run();
 */
final class FileProvider implements Provider
{
    public function __construct(
        private readonly string $path,
        private readonly string $type = 'json',
    ) {
        if (!is_file($path)) {
            throw new \InvalidArgumentException("File not found: $path");
        }
    }

    public static function csv(string $path): self
    {
        return new self($path, 'csv');
    }

    public static function json(string $path): self
    {
        return new self($path, 'json');
    }

    public static function jsonl(string $path): self
    {
        return new self($path, 'jsonl');
    }

    public function fetch(): Collection
    {
        return match ($this->type) {
            'csv' => $this->readCsv(),
            'json' => collect(json_decode(file_get_contents($this->path), true) ?? []),
            'jsonl' => $this->readJsonl(),
            default => collect([]),
        };
    }

    private function readCsv(): Collection
    {
        $rows = [];
        $fp = fopen($this->path, 'r');
        $headers = fgetcsv($fp);

        while ($row = fgetcsv($fp)) {
            $rows[] = array_combine($headers, $row);
        }

        fclose($fp);
        return collect($rows);
    }

    private function readJsonl(): Collection
    {
        $rows = [];
        $fp = fopen($this->path, 'r');

        while ($line = fgets($fp)) {
            if (trim($line)) {
                $rows[] = json_decode($line, true);
            }
        }

        fclose($fp);
        return collect($rows);
    }

    public function name(): string
    {
        return "File::" . basename($this->path);
    }

    public function headers(): array
    {
        // Files don't have HTTP headers, but can return file metadata
        return [
            'Content-Type' => mime_content_type($this->path),
            'Content-Length' => filesize($this->path),
            'Last-Modified' => filemtime($this->path),
        ];
    }
}
