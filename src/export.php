<?php

function status_label(string $status): string
{
    return match ($status) {
        'active' => _t('Status.ACTIVE', 'prebieha'),
        'done' => _t('Status.DONE', 'dokončený'),
        default => _t('Status.LOST', 'nedokončený'),
    };
}

// One row per customer, phase durations in seconds.
function customers_csv_rows(string $day): array
{
    $phases = get_steps_for_export('phase');
    $questions = get_steps_for_export('question');
    $seconds = _t('Export.SECONDS', '(s)');

    $header = [
        _t('Export.NUMBER', 'Č.'),
        _t('Export.DATE', 'Dátum'),
        _t('Export.ARRIVAL', 'Príchod'),
        _t('Export.DEPARTURE', 'Odchod'),
        _t('Export.STATUS', 'Stav'),
        _t('Export.OBSERVER', 'Pozorovateľ'),
        _t('Export.LABEL', 'Označenie'),
        _t('Export.TOTAL', 'Celkom') . " $seconds",
    ];
    foreach ($phases as $phase) {
        $header[] = $phase['name'] . " $seconds";
    }
    foreach ($questions as $question) {
        $header[] = $question['name'];
    }
    $header[] = _t('Export.NOTE', 'Poznámka');

    $rows = [$header];

    foreach (get_all_customers($day) as $customer) {
        $timeline = get_timeline($customer, get_marks($customer['id']));
        $answers = get_answers($customer['id']);

        $row = [
            $customer['id'],
            day_of($customer['arrived_at']),
            format_time($customer['arrived_at']),
            $customer['left_at'] === null ? '' : format_time($customer['left_at']),
            status_label($customer['status']),
            $customer['observer'],
            $customer['label'],
            whole_seconds(total_duration($customer)),
        ];
        foreach ($phases as $phase) {
            $row[] = whole_seconds($timeline[$phase['id']]['duration'] ?? null);
        }
        foreach ($questions as $question) {
            $row[] = answer_label($answers[$question['id']] ?? null);
        }
        $row[] = $customer['note'];

        $rows[] = $row;
    }

    return $rows;
}

// One row per phase with exact start and end times.
function events_csv_rows(string $day): array
{
    $stepNames = get_step_names();
    $rows = [[
        _t('Export.NUMBER', 'Č.'),
        _t('Export.LABEL', 'Označenie'),
        _t('Export.OBSERVER', 'Pozorovateľ'),
        _t('Export.ORDER', 'Poradie'),
        _t('Export.PHASE', 'Fáza'),
        _t('Export.START', 'Začiatok'),
        _t('Export.END', 'Koniec'),
        _t('Export.DURATION', 'Trvanie') . ' ' . _t('Export.SECONDS', '(s)'),
        _t('Export.SKIPPED', 'Preskočené'),
    ]];

    foreach (get_all_customers($day) as $customer) {
        $marks = get_marks($customer['id']);
        $timeline = get_timeline($customer, $marks);

        foreach ($marks as $i => $mark) {
            $phase = $timeline[$mark['step_id']];
            $rows[] = [
                $customer['id'],
                $customer['label'],
                $customer['observer'],
                $i + 1,
                $stepNames[$mark['step_id']] ?? '',
                format_datetime($phase['start']),
                $phase['skipped'] ? '' : format_datetime($phase['end']),
                whole_seconds($phase['duration']),
                $phase['skipped'] ? _t('Common.YES_LOWER', 'áno') : '',
            ];
        }

        if ($customer['left_at'] !== null) {
            $rows[] = [
                $customer['id'], $customer['label'], $customer['observer'], count($marks) + 1,
                _t('Export.DEPARTURE', 'Odchod'), format_datetime($customer['left_at']), '', '', '',
            ];
        }
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
