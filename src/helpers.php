<?php

function e(mixed $text): string
{
    return htmlspecialchars((string) $text, ENT_QUOTES);
}

// Unix timestamp in seconds, with fractions.
function now(): float
{
    return microtime(true);
}

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function current_observer(): string
{
    return $_COOKIE['observer'] ?? '';
}

function only_mine(): bool
{
    return ($_COOKIE['only_mine'] ?? '') === '1';
}

// ?day=YYYY-MM-DD from the URL, '' means all days.
function requested_day(): string
{
    $day = $_GET['day'] ?? '';
    return preg_match('/^\d{4}-\d{2}-\d{2}$/', $day) ? $day : '';
}

// 125 -> "02:05", 3725 -> "1:02:05"
function format_duration(?float $seconds): string
{
    if ($seconds === null) {
        return '–';
    }

    $total = max(0, (int) floor($seconds));
    $hours = intdiv($total, 3600);
    $minutes = intdiv($total % 3600, 60);
    $secs = $total % 60;

    if ($hours > 0) {
        return sprintf('%d:%02d:%02d', $hours, $minutes, $secs);
    }
    return sprintf('%02d:%02d', $minutes, $secs);
}

function whole_seconds(?float $seconds): int|string
{
    return $seconds === null ? '' : (int) round($seconds);
}

function format_time(float $timestamp): string
{
    return date('H:i:s', (int) $timestamp);
}

function format_datetime(?float $timestamp): string
{
    return $timestamp === null ? '' : date('Y-m-d H:i:s', (int) $timestamp);
}

function format_day(string $day): string
{
    return date(_t('Format.DAY', 'j. n. Y'), strtotime($day));
}

function day_of(float $timestamp): string
{
    return date('Y-m-d', (int) $timestamp);
}

function answer_label(?int $value): string
{
    return match ($value) {
        1 => _t('Common.YES_LOWER', 'áno'),
        0 => _t('Common.NO_LOWER', 'nie'),
        default => '',
    };
}

function average(array $values): ?float
{
    return $values ? array_sum($values) / count($values) : null;
}

function median(array $values): ?float
{
    if (!$values) {
        return null;
    }

    sort($values);
    $middle = intdiv(count($values), 2);

    if (count($values) % 2 === 1) {
        return $values[$middle];
    }
    return ($values[$middle - 1] + $values[$middle]) / 2;
}

// Hidden inputs that every form on a customer card sends to customer.php.
function customer_fields(string $action, int $customerId): string
{
    return '<input type="hidden" name="action" value="' . e($action) . '">'
        . '<input type="hidden" name="id" value="' . $customerId . '">';
}
