<?php declare(strict_types=1);

defined('WEBKERNEL_OFFICIAL_REGISTRY')   || define('WEBKERNEL_OFFICIAL_REGISTRY',   'webkernelphp.com');
defined('WEBKERNEL_APTITUDE_REGISTRY')   || define('WEBKERNEL_APTITUDE_REGISTRY',   'webkernel');
defined('WEBKERNEL_APTITUDE_VENDOR')     || define('WEBKERNEL_APTITUDE_VENDOR',     'aptitudes');

defined('WEBKERNEL_MODULE_REGISTRIES') || define('WEBKERNEL_MODULE_REGISTRIES', [
    'webkernelphp.com'     => 'https://webkernelphp.com/api/v1/modules',
    'github.com'           => 'https://api.github.com',
    'gitlab.com'           => 'https://gitlab.com/api/v4',
    'bitbucket.org'        => 'https://api.bitbucket.org/2.0',
    'git.numerimondes.com' => 'https://git.numerimondes.com/api/v1',
    'assembla.com'         => null,
    'aws.amazon.com'       => null,
    'azure.com'            => null,
]);

defined('WEBKERNEL_MARKETPLACE_API')   || define('WEBKERNEL_MARKETPLACE_API',   'https://webkernelphp.com/api/v1');
defined('WEBKERNEL_UPDATE_CHECK_URL')  || define('WEBKERNEL_UPDATE_CHECK_URL',  'https://webkernelphp.com/api/v1/releases/latest');
defined('WEBKERNEL_MODULES_API')       || define('WEBKERNEL_MODULES_API',       'https://webkernelphp.com/api/v1/modules');
defined('WEBKERNEL_PHP_RELEASES_API')  || define('WEBKERNEL_PHP_RELEASES_API',  'https://www.php.net/releases/active.php');
defined('WEBKERNEL_PHP_RELEASES_TTL')  || define('WEBKERNEL_PHP_RELEASES_TTL',  86400);

defined('WEBKERNEL_WS_CHANNEL_SYSTEM')  || define('WEBKERNEL_WS_CHANNEL_SYSTEM',  'webkernel.system');
defined('WEBKERNEL_WS_CHANNEL_AUDIT')   || define('WEBKERNEL_WS_CHANNEL_AUDIT',   'webkernel.audit');
defined('WEBKERNEL_WS_CHANNEL_METRICS') || define('WEBKERNEL_WS_CHANNEL_METRICS', 'webkernel.metrics');

defined('WEBKERNEL_CONTEXT_MEDICAL')   || define('WEBKERNEL_CONTEXT_MEDICAL',   'medical');
defined('WEBKERNEL_CONTEXT_SURGICAL')  || define('WEBKERNEL_CONTEXT_SURGICAL',  'surgical');
defined('WEBKERNEL_CONTEXT_GOV')       || define('WEBKERNEL_CONTEXT_GOV',       'governmental');
defined('WEBKERNEL_CONTEXT_FINANCIAL') || define('WEBKERNEL_CONTEXT_FINANCIAL', 'financial');
defined('WEBKERNEL_CONTEXT_STANDARD')  || define('WEBKERNEL_CONTEXT_STANDARD',  'standard');

defined('WEBKERNEL_AUDIT_RETENTION_DAYS') || define('WEBKERNEL_AUDIT_RETENTION_DAYS', 365);
