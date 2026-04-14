<?php declare(strict_types=1);

// Planned SQLCipher / Encryption for Sqlite Natively

// Database path
defined('WEBKERNEL_MAIN_SQLITE_DB_PATH')
    || define('WEBKERNEL_MAIN_SQLITE_DB_PATH', BASE_PATH . '/database/database.sqlite');

// Webkernel Configuration Constants
defined('WEBKERNEL_DB_DRIVER')       || define('WEBKERNEL_DB_DRIVER', 'sqlite');
defined('WEBKERNEL_DB_PREFIX')       || define('WEBKERNEL_DB_PREFIX', '');
defined('WEBKERNEL_DB_FOREIGN_KEYS') || define('WEBKERNEL_DB_FOREIGN_KEYS', true);
defined('WEBKERNEL_DB_TIMEOUT')      || define('WEBKERNEL_DB_TIMEOUT', null);
defined('WEBKERNEL_DB_JOURNAL')      || define('WEBKERNEL_DB_JOURNAL', null);
defined('WEBKERNEL_DB_SYNC')         || define('WEBKERNEL_DB_SYNC', null);
defined('WEBKERNEL_DB_TX_MODE')      || define('WEBKERNEL_DB_TX_MODE', 'DEFERRED');
