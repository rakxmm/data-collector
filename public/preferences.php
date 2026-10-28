<?php
// Per-device settings, kept in cookies for a year.

require __DIR__ . '/../src/bootstrap.php';

$expires = time() + 365 * 24 * 60 * 60;

if (isset($_POST['observer'])) {
    setcookie('observer', mb_substr(trim($_POST['observer']), 0, 60), $expires);
}
if (isset($_POST['only_mine'])) {
    setcookie('only_mine', $_POST['only_mine'] === '1' ? '1' : '0', $expires);
}
if (isset($_POST['lang']) && isset(LANGUAGES[$_POST['lang']])) {
    setcookie('lang', $_POST['lang'], $expires);
}

$back = $_POST['back'] ?? '';
redirect(in_array($back, ['index.php', 'records.php', 'settings.php'], true) ? $back : 'index.php');
