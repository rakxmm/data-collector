<?php

require __DIR__ . '/i18n.php';
require __DIR__ . '/helpers.php';
require __DIR__ . '/database.php';
require __DIR__ . '/steps.php';
require __DIR__ . '/customers.php';
require __DIR__ . '/export.php';

date_default_timezone_set(getenv('TZ') ?: 'Europe/Bratislava');

require_login();

// Login is only required when BASIC_AUTH="user:password" is set.
function require_login(): void
{
    $expected = getenv('BASIC_AUTH');
    if (!$expected) {
        return;
    }

    $given = ($_SERVER['PHP_AUTH_USER'] ?? '') . ':' . ($_SERVER['PHP_AUTH_PW'] ?? '');
    if (hash_equals($expected, $given)) {
        return;
    }

    header('WWW-Authenticate: Basic realm="Data Collector", charset="UTF-8"');
    http_response_code(401);
    exit(_t('Auth.REQUIRED', 'Prihlásenie je povinné.'));
}
