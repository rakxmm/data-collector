<?php

// Taps closer together than this are treated as an accidental double tap.
const DOUBLE_TAP_SECONDS = 1.0;

function get_customer(int $id): ?array
{
    return query('SELECT * FROM customers WHERE id = ?', [$id])->fetch() ?: null;
}

function get_active_customers(): array
{
    return query("SELECT * FROM customers WHERE status = 'active' ORDER BY arrived_at")->fetchAll();
}

function get_finished_customers(string $day = ''): array
{
    $customers = query("SELECT * FROM customers WHERE status != 'active' ORDER BY arrived_at DESC")->fetchAll();
    return filter_by_day($customers, $day);
}

function get_all_customers(string $day = ''): array
{
    $customers = query('SELECT * FROM customers ORDER BY arrived_at')->fetchAll();
    return filter_by_day($customers, $day);
}

function filter_by_day(array $customers, string $day): array
{
    if ($day === '') {
        return $customers;
    }
    return array_values(array_filter($customers, fn($customer) => day_of($customer['arrived_at']) === $day));
}

// ['2026-10-28' => number of finished customers, ...], newest first.
function get_days(): array
{
    $days = [];
    foreach (get_finished_customers() as $customer) {
        $day = day_of($customer['arrived_at']);
        $days[$day] = ($days[$day] ?? 0) + 1;
    }
    return $days;
}

function get_marks(int $customerId): array
{
    return query('SELECT * FROM marks WHERE customer_id = ? ORDER BY started_at, id', [$customerId])->fetchAll();
}

// [step_id => 1 or 0]
function get_answers(int $customerId): array
{
    return query('SELECT step_id, value FROM answers WHERE customer_id = ?', [$customerId])->fetchAll(PDO::FETCH_KEY_PAIR);
}

function get_progress(array $customer): array
{
    $phases = get_phases();
    $marks = get_marks($customer['id']);
    $lastMark = end($marks);
    $current = get_step($lastMark['step_id']);
    $visitedIds = array_column($marks, 'step_id');

    $ahead = array_values(array_filter(
        $phases,
        fn($phase) => $phase['position'] > $current['position'] && !in_array($phase['id'], $visitedIds)
    ));

    return [
        'phases' => $phases,
        'marks' => $marks,
        'current' => $current,
        'current_since' => $lastMark['started_at'],
        'next' => $ahead[0] ?? null,
        'after_next' => $ahead[1] ?? null,
    ];
}

// [step_id => ['start', 'end', 'duration', 'skipped']] for every phase the customer went through.
function get_timeline(array $customer, array $marks): array
{
    $timeline = [];

    foreach ($marks as $i => $mark) {
        $end = $marks[$i + 1]['started_at'] ?? $customer['left_at'];

        $timeline[$mark['step_id']] = [
            'start' => $mark['started_at'],
            'end' => $end,
            'duration' => ($mark['skipped'] || $end === null) ? null : $end - $mark['started_at'],
            'skipped' => (bool) $mark['skipped'],
        ];
    }

    return $timeline;
}

function total_duration(array $customer): ?float
{
    return $customer['left_at'] === null ? null : $customer['left_at'] - $customer['arrived_at'];
}

function arrive(string $observer): ?int
{
    $firstPhase = get_phases()[0] ?? null;
    if (!$firstPhase) {
        return null;
    }

    $time = now();
    query('INSERT INTO customers (observer, arrived_at) VALUES (?, ?)', [$observer, $time]);
    $customerId = (int) db()->lastInsertId();
    add_mark($customerId, $firstPhase['id'], $time);

    return $customerId;
}

function add_mark(int $customerId, int $stepId, float $time, bool $skipped = false): void
{
    query(
        'INSERT INTO marks (customer_id, step_id, started_at, skipped) VALUES (?, ?, ?, ?)',
        [$customerId, $stepId, $time, $skipped ? 1 : 0]
    );
}

function go_to_next_phase(array $customer): void
{
    $progress = get_progress($customer);
    if (now() - $progress['current_since'] < DOUBLE_TAP_SECONDS) {
        return;
    }

    if ($progress['next']) {
        add_mark($customer['id'], $progress['next']['id'], now());
    } else {
        customer_left($customer['id']);
    }
}

// The skipped phase gets a mark without duration and the phase after it starts right away.
function skip_next_phase(array $customer): void
{
    $progress = get_progress($customer);
    if (!$progress['next'] || now() - $progress['current_since'] < DOUBLE_TAP_SECONDS) {
        return;
    }

    $time = now();
    add_mark($customer['id'], $progress['next']['id'], $time, true);

    if ($progress['after_next']) {
        add_mark($customer['id'], $progress['after_next']['id'], $time);
    } else {
        customer_left($customer['id'], $time);
    }
}

// Removes the last mark, and also the skipped one before it if the last step was a skip.
// The arrival (first mark) stays.
function undo_last_step(array $customer): void
{
    $marks = get_marks($customer['id']);

    while (count($marks) > 1) {
        $mark = array_pop($marks);
        query('DELETE FROM marks WHERE id = ?', [$mark['id']]);

        if (!end($marks)['skipped']) {
            break;
        }
    }
}

// Clicking the already selected answer clears it.
function toggle_answer(int $customerId, int $stepId, int $value): void
{
    $current = get_answers($customerId)[$stepId] ?? null;

    if ($current === $value) {
        query('DELETE FROM answers WHERE customer_id = ? AND step_id = ?', [$customerId, $stepId]);
    } else {
        query('INSERT OR REPLACE INTO answers (customer_id, step_id, value) VALUES (?, ?, ?)', [$customerId, $stepId, $value]);
    }
}

function set_label(int $customerId, string $label): void
{
    query('UPDATE customers SET label = ? WHERE id = ?', [mb_substr(trim($label), 0, 200), $customerId]);
}

function set_note(int $customerId, string $note): void
{
    query('UPDATE customers SET note = ? WHERE id = ?', [mb_substr(trim($note), 0, 2000), $customerId]);
}

function customer_left(int $customerId, ?float $time = null): void
{
    query("UPDATE customers SET status = 'done', left_at = ? WHERE id = ?", [$time ?? now(), $customerId]);
}

// We lost sight of the customer, so there is no departure time.
function customer_lost(int $customerId): void
{
    query("UPDATE customers SET status = 'lost', left_at = NULL WHERE id = ?", [$customerId]);
}

function reopen_customer(int $customerId): void
{
    query("UPDATE customers SET status = 'active', left_at = NULL WHERE id = ?", [$customerId]);
}

function delete_customer(int $customerId): void
{
    query('DELETE FROM customers WHERE id = ?', [$customerId]);
}

function delete_all_customers(): void
{
    query('DELETE FROM customers');
}
