<?php declare(strict_types=1);

namespace Webkernel\Databases\Enum;

enum DbDriver: string
{
    case MYSQL  = 'mysql';
    case PGSQL  = 'pgsql';
    case SQLITE = 'sqlite';

    public function label(): string
    {
        return match ($this) {
            self::MYSQL  => 'MySQL',
            self::PGSQL  => 'PostgreSQL',
            self::SQLITE => 'SQLite',
        };
    }

    public function defaultPort(): ?int
    {
        return match ($this) {
            self::MYSQL  => 3306,
            self::PGSQL  => 5432,
            self::SQLITE => null,
        };
    }

    public function requiresHost(): bool
    {
        return $this !== self::SQLITE;
    }
}
