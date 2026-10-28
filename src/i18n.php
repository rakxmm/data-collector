<?php

const LANGUAGES = [
    'sk' => 'Slovensky',
    'en' => 'English',
];

function current_language(): string
{
    $language = $_COOKIE['lang'] ?? 'sk';
    return isset(LANGUAGES[$language]) ? $language : 'sk';
}

// _t('Records.TITLE', 'Záznamy') – the Slovak default is used when lang/<language>.php has no translation.
function _t(string $key, string $default, array $params = []): string
{
    static $translations = null;
    if ($translations === null) {
        $file = __DIR__ . '/../lang/' . current_language() . '.php';
        $translations = is_file($file) ? require $file : [];
    }

    $text = $translations[$key] ?? $default;

    foreach ($params as $name => $value) {
        $text = str_replace('{' . $name . '}', (string) $value, $text);
    }

    return $text;
}
