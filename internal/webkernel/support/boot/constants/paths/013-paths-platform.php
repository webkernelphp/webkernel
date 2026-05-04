<?php declare(strict_types=1);

/* --- Platform --- */

defined('WEBKERNEL_PLATFORM_ROOT')
    || define('WEBKERNEL_PLATFORM_ROOT', BASE_PATH . '/platform');

defined('WEBKERNEL_ARCANES_ROOT')
    || define('WEBKERNEL_ARCANES_ROOT', WEBKERNEL_PATH . '/codebase/Arcanes');

defined('WEBKERNEL_PLATFORM_CMD_OVERRIDES')
    || define('WEBKERNEL_PLATFORM_CMD_OVERRIDES', WEBKERNEL_PATH . '/support/boot-actions/commands-overrides.php');

defined('WEBKERNEL_PLATFORM_LOCATIONS')
    || define('WEBKERNEL_PLATFORM_LOCATIONS', [
    WEBKERNEL_PATH . '/codebase/CP',
    ]);
