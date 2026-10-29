<?php
// Steps are the phases we time and the yes/no questions, both editable in settings.

function get_steps(string $type, bool $archived = false): array
{
    return query(
        'SELECT * FROM steps WHERE type = ? AND archived = ? ORDER BY position, id',
        [$type, $archived ? 1 : 0]
    )->fetchAll();
}

function get_phases(): array
{
    return get_steps('phase');
}

function get_questions(): array
{
    return get_steps('question');
}

function get_step(int $id): ?array
{
    return query('SELECT * FROM steps WHERE id = ?', [$id])->fetch() ?: null;
}

// [id => name] including removed steps.
function get_step_names(): array
{
    return query('SELECT id, name FROM steps')->fetchAll(PDO::FETCH_KEY_PAIR);
}

// Active steps plus removed ones that still have data.
function get_steps_for_export(string $type): array
{
    return query(
        'SELECT * FROM steps
         WHERE type = ?
           AND (archived = 0 OR id IN (SELECT step_id FROM marks UNION SELECT step_id FROM answers))
         ORDER BY archived, position, id',
        [$type]
    )->fetchAll();
}

function next_position(string $type): int
{
    return query('SELECT COALESCE(MAX(position), -1) + 1 FROM steps WHERE type = ?', [$type])->fetchColumn();
}

function add_step(string $type, string $name, string $options = ''): void
{
    query(
        'INSERT INTO steps (type, name, position, options) VALUES (?, ?, ?, ?)',
        [$type, $name, next_position($type), clean_options($options)]
    );
}

function rename_step(int $id, string $name): void
{
    query('UPDATE steps SET name = ? WHERE id = ?', [$name, $id]);
}

function set_step_options(int $id, string $options): void
{
    query('UPDATE steps SET options = ? WHERE id = ?', [clean_options($options), $id]);
}

// " objednaný,neobjednaný , " -> "objednaný, neobjednaný"
function clean_options(string $options): string
{
    return implode(', ', question_options(['options' => $options]));
}

// Choices of a question, [] means a yes/no question. Answers store the position of the choice (1, 2, …).
function question_options(array $question): array
{
    $options = array_map('trim', explode(',', $question['options']));
    return array_values(array_filter($options, fn($option) => $option !== ''));
}

// $direction: -1 = up, 1 = down
function move_step(int $id, int $direction): void
{
    $step = get_step($id);
    if (!$step) {
        return;
    }

    $ids = array_column(get_steps($step['type']), 'id');
    $from = array_search($id, $ids);
    $to = $from + $direction;
    if ($from === false || !isset($ids[$to])) {
        return;
    }

    [$ids[$from], $ids[$to]] = [$ids[$to], $ids[$from]];

    foreach ($ids as $position => $stepId) {
        query('UPDATE steps SET position = ? WHERE id = ?', [$position, $stepId]);
    }
}

// A step with measured data is only archived, so exports stay complete.
function remove_step(int $id): void
{
    $step = get_step($id);
    if (!$step || ($step['type'] === 'phase' && count(get_phases()) <= 1)) {
        return;
    }

    $usage = query('SELECT COUNT(*) FROM marks WHERE step_id = ?', [$id])->fetchColumn()
        + query('SELECT COUNT(*) FROM answers WHERE step_id = ?', [$id])->fetchColumn();

    if ($usage > 0) {
        query('UPDATE steps SET archived = 1 WHERE id = ?', [$id]);
    } else {
        query('DELETE FROM steps WHERE id = ?', [$id]);
    }
}

function restore_step(int $id): void
{
    $step = get_step($id);
    if ($step) {
        query('UPDATE steps SET archived = 0, position = ? WHERE id = ?', [next_position($step['type']), $id]);
    }
}
