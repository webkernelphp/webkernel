<?php declare(strict_types=1);

/* --- Installation & routing --- */

defined('WEBKERNEL_DEPLOYMENT_FILE')
    || define('WEBKERNEL_DEPLOYMENT_FILE', BASE_PATH . '/deployment.php');

defined('WEBKERNEL_HTTP_ROOT')
    || define('WEBKERNEL_HTTP_ROOT', '/');

defined('WEBKERNEL_INSTALLER_PATH_PREFIX')
    || define('WEBKERNEL_INSTALLER_PATH_PREFIX', 'installer');

defined('WEBKERNEL_INSTALLER_URL')
    || define('WEBKERNEL_INSTALLER_URL', WEBKERNEL_HTTP_ROOT . WEBKERNEL_INSTALLER_PATH_PREFIX);

defined('WEBKERNEL_HEALTH_PATH')
    || define('WEBKERNEL_HEALTH_PATH', 'up');
