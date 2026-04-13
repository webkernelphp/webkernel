<?php declare(strict_types=1);
/**
 * Backward-compatibility shim.
 * All classes have been split into bootstrap/webkernel/support/boot-services/*.php
 * and are now loaded individually by fast-boot.php in dependency order.
 *
 * This file is kept so that any external require_once of this path continues to work.
 * Do not add logic here — edit the individual boot-services files instead.
 */

$_bs = dirname(__DIR__) . '/boot-services/';
require_once $_bs . '010-hmac-signer.php';
require_once $_bs . '020-webkernel-session.php';
require_once $_bs . '030-webkernel-router.php';
require_once $_bs . '040-branding.php';
require_once $_bs . '050-emergency-page-builder.php';
require_once $_bs . '060-server-side-validator.php';
require_once $_bs . '070-http-client.php';
require_once $_bs . '080-setup-flow.php';
require_once $_bs . '090-global-helpers.php';
unset($_bs);
