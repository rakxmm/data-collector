<?php

// Columns the export offers for a measure filter, [key => header], in export order.
// Arrivals only: number and arrival time. Otherwise: number, phase durations in seconds (two decimals),
// total time (whole visits only) and answers to the questions.
function export_columns(string $measure): array
{
    if ($measure === 'arrival') {
        return ['number' => _t('Export.NUMBER', 'Č.'), 'arrival' => _t('Export.ARRIVAL', 'Príchod')];
    }

    $seconds = _t('Export.SECONDS', '(s)');
    $columns = ['number' => _t('Export.NUMBER', 'Č.')];
    foreach (phases_for_measure($measure) as $phase) {
        $columns['phase-' . $phase['id']] = $phase['name'] . " $seconds";
    }
    if (in_array($measure, ['', 'whole'], true)) {
        $columns['total'] = _t('Export.TOTAL', 'Celkom') . " $seconds";
    }
    foreach (get_steps_for_export('question') as $question) {
        $columns['question-' . $question['id']] = $question['name'];
    }
    return $columns;
}

// [column key => value] for one customer, see export_columns().
function export_values(array $customer, array $questions): array
{
    $values = [
        'number' => $customer['id'],
        'arrival' => format_time($customer['arrived_at']),
        'total' => decimal_seconds(total_duration($customer)),
    ];
    foreach (get_timeline($customer, get_marks($customer['id'])) as $stepId => $phase) {
        $values["phase-$stepId"] = decimal_seconds($phase['duration']);
    }
    $answers = get_answers($customer['id']);
    foreach ($questions as $question) {
        $values['question-' . $question['id']] = answer_label($question, $answers[$question['id']] ?? null);
    }
    return $values;
}

// One row per customer, filtered like the records table. $keys picks columns, [] = all.
// A single column is exported as a plain list of values: no header and no empty rows.
// Durations are seconds with two decimals and a decimal point.
function export_csv_rows(string $day, string $measure, array $keys = []): array
{
    $available = export_columns($measure);
    $columns = array_intersect_key($available, array_flip($keys)) ?: $available;
    $onlyValues = count($columns) === 1;
    $questions = get_steps_for_export('question');

    $rows = $onlyValues ? [] : [array_values($columns)];

    foreach (get_all_customers($day, $measure) as $customer) {
        // Arrival-only records have no phases, they have their own export.
        if ($customer['arrival_only'] && $measure !== 'arrival') {
            continue;
        }

        $values = export_values($customer, $questions);
        $row = array_map(fn($key) => $values[$key] ?? '', array_keys($columns));
        if ($onlyValues && $row[0] === '') {
            continue;
        }
        $rows[] = $row;
    }

    return $rows;
}

function send_csv(string $filename, array $rows, string $separator): never
{
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');
    fwrite($output, "\u{FEFF}"); // BOM, so Excel shows diacritics correctly
    foreach ($rows as $row) {
        fputcsv($output, $row, $separator, '"', '');
    }
    fclose($output);
    exit;
}
