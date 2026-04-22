<?php declare(strict_types=1);
namespace Webkernel\Modules\Core;

final class Config
{
  public const string MODULE_DIR = 'Modules';
  public const string BOOTSTRAP_DIR = 'bootstrap';
  public const string BACKUP_DIR = 'storage/system/backups';
  public const string LOCK_DIR = 'storage/system/locks';

  public const int BACKUP_KEEP_COUNT = 3;
  public const int HOOK_TIMEOUT = 300;
  public const int MAX_DOWNLOAD_SIZE = 104857600;

  public const string CONFIG_FILE = 'storage/system/keys/config.json';
  public const string COMPOSER_JSON = 'composer.json';
  public const int LOCK_TIMEOUT = 300;
}
