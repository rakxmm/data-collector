<?php
/** @var string $pageTitle */
/** @var string $activeTab e.g. 'index.php' */

$tabs = [
    'index.php' => _t('Nav.MEASURE', 'Meranie'),
    'records.php' => _t('Nav.RECORDS', 'Záznamy'),
    'settings.php' => _t('Nav.SETTINGS', 'Nastavenie'),
];
?>
<!doctype html>
<html lang="<?= current_language() ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#15171a">
    <title><?= e($pageTitle) ?> · Data Collector</title>
    <link rel="icon" href="icon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="app.css">
</head>
<body data-server-time="<?= now() ?>">

<header class="topbar">
    <div class="topbar-inner">
        <a class="brand" href="index.php">
            <svg class="brand-mark" viewBox="0 0 24 24" aria-hidden="true">
                <rect x="1" y="1" width="22" height="22" rx="5"/>
                <circle cx="12" cy="13" r="6.2"/>
                <path d="M12 13V9.4M10.4 3.8h3.2"/>
            </svg>
            <span>Data Collector</span>
        </a>

        <nav class="tabs">
            <?php foreach ($tabs as $url => $label): ?>
                <a href="<?= $url ?>" <?= $url === $activeTab ? 'aria-current="page"' : '' ?>><?= e($label) ?></a>
            <?php endforeach ?>
        </nav>

        <form class="observer <?= current_observer() === '' ? 'is-missing' : '' ?>" method="post" action="preferences.php">
            <input type="hidden" name="back" value="<?= e($activeTab) ?>">
            <label for="observer" class="observer-label"><?= _t('Nav.OBSERVER', 'Pozorovateľ') ?></label>
            <input id="observer" type="text" name="observer" value="<?= e(current_observer()) ?>"
                   placeholder="<?= e(_t('Nav.NAME', 'Meno')) ?>" maxlength="60" autocomplete="name" data-autosubmit>
        </form>

        <form class="language" method="post" action="preferences.php">
            <input type="hidden" name="back" value="<?= e($activeTab) ?>">
            <?php foreach (LANGUAGES as $code => $name): ?>
                <button name="lang" value="<?= $code ?>" title="<?= e($name) ?>"
                        aria-pressed="<?= $code === current_language() ? 'true' : 'false' ?>"><?= strtoupper($code) ?></button>
            <?php endforeach ?>
        </form>
    </div>
</header>

<main class="page">
