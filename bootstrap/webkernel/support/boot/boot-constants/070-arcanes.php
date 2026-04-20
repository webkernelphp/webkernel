<?php declare(strict_types=1);

/*
 * support/constants/arcanes.php
 *
 * All naming, discovery, and validation rules for the Arcanes subsystem.
 *
 * Adding a new artifact kind tomorrow requires only:
 *   1. A new entry in WEBKERNEL_ARTIFACT_KINDS
 *   2. A new entry in WEBKERNEL_ARTIFACT_NAMESPACE_RULES (if namespace validation needed)
 *   3. Zero changes in Modules.php, NamingHelper.php, or ArtifactMatrix.php
 */

/* --- Manifest filenames per artifact kind ---------------------------------- */
/*
 * Maps _type => manifest filename.
 * Discovery loops use this to know which file to look for in each directory.
 */
defined('WEBKERNEL_MANIFEST_FILES') || define('WEBKERNEL_MANIFEST_FILES', [
    'module'   => 'module.php',
    'platform' => 'platform.php',
]);

/* --- Id format templates --------------------------------------------------- */
/*
 * sprintf-compatible patterns used by NamingHelper::buildId().
 *
 * module   : <registry>::<vendor>/<slug>    (dots in registry become dashes)
 * platform : webkernel::<scope>/<slug>      where scope is the parent folder name
 */
defined('WEBKERNEL_ID_FORMATS') || define('WEBKERNEL_ID_FORMATS', [
    'module'   => '%s::%s/%s',   // sprintf($fmt, $registry, $vendor, $slug)
    'platform' => 'webkernel::%s/%s',  // sprintf($fmt, $scope, $slug)
]);

/* --- Default namespace prefix per artifact kind ---------------------------- */
/*
 * Used when a manifest does not declare its own namespace.
 * NamingHelper appends PascalCase(slug) to these prefixes automatically.
 */
defined('WEBKERNEL_NAMESPACE_DEFAULTS') || define('WEBKERNEL_NAMESPACE_DEFAULTS', [
    'module'   => 'WebModule\\',
    'platform' => 'Webkernel\\',
]);

/* --- Namespace validation rules per artifact kind -------------------------- */
/*
 * Maps _type => required namespace prefix string (str_starts_with check).
 * Set to null to skip namespace validation for that kind.
 */
defined('WEBKERNEL_NAMESPACE_RULES') || define('WEBKERNEL_NAMESPACE_RULES', [
    'module'   => 'WebModule\\',
    'platform' => 'Webkernel\\',
]);

/* --- Required manifest keys per artifact kind ------------------------------ */
/*
 * Validation in Modules::validate() calls ArtifactMatrix::required($type).
 * These are the authoritative lists; ArtifactMatrix::REQUIRED is populated
 * from this constant by ArtifactMatrix::required() at runtime.
 */
defined('WEBKERNEL_ARTIFACT_REQUIRED_KEYS') || define('WEBKERNEL_ARTIFACT_REQUIRED_KEYS', [
    'module'   => ['id', 'namespace', 'version', 'vendor', 'slug', 'party', 'active', 'registry'],
    'platform' => ['label', 'version', 'active'],
]);

/* --- Official registry name ------------------------------------------------ */
defined('WEBKERNEL_OFFICIAL_REGISTRY') || define('WEBKERNEL_OFFICIAL_REGISTRY', 'webkernelphp-com');

/* --- Platform scope label -------------------------------------------------- */
/*
 * Scope segment inserted into platform IDs.
 * platform.php inside WEBKERNEL_PLATFORM_LOCATIONS will get IDs like:
 *   webkernel::aptitudes/<slug>
 * If you add a second location set, override _scope in the manifest to
 * a different value (e.g. 'plugins') or rely on the folder parent name.
 */
defined('WEBKERNEL_PLATFORM_DEFAULT_SCOPE') || define('WEBKERNEL_PLATFORM_DEFAULT_SCOPE', 'aptitudes');

/* --- Scaffold artifact kinds ----------------------------------------------- */
/*
 * Used by MakeModule command to present kind choices.
 * 'dev_only' restricts the kind to IS_DEVMODE sessions.
 */
defined('WEBKERNEL_SCAFFOLD_KINDS') || define('WEBKERNEL_SCAFFOLD_KINDS', [
    'module'   => ['label' => 'Module',                'dev_only' => false],
    'platform' => ['label' => 'Platform capability (dev only)', 'dev_only' => true],
]);

/* --- Scaffold aptitudes root ----------------------------------------------- */
defined('WEBKERNEL_APTITUDES_ROOT') || define('WEBKERNEL_APTITUDES_ROOT', WEBKERNEL_PATH . '/aptitudes');
