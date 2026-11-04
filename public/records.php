<?php

require __DIR__ . '/../src/bootstrap.php';

$day = requested_day();
$measure = requested_measure();
$separator = ($_GET['sep'] ?? '') === 'comma' ? 'comma' : 'semicolon';

$days = get_days();
$measuredPhases = get_steps_for_export('phase');
// Phase columns that would stay empty for the chosen measure are left out.
$phases = match (true) {
    $measure === 'arrival' => [],
    ctype_digit($measure) => array_values(array_filter($measuredPhases, fn($phase) => (string) $phase['id'] === $measure)),
    default => $measuredPhases,
};
$questions = get_steps_for_export('question');

$rows = [];
foreach (get_finished_customers($day, $measure) as $customer) {
    $rows[] = [
        'customer' => $customer,
        'timeline' => get_timeline($customer, get_marks($customer['id'])),
        'answers' => get_answers($customer['id']),
        'total' => total_duration($customer),
    ];
}

// Measured durations of every phase and of the whole visit, for the average/median table.
$summary = [];
foreach ($phases as $phase) {
    $durations = array_map(fn($row) => $row['timeline'][$phase['id']]['duration'] ?? null, $rows);
    $summary[$phase['name']] = array_filter($durations, fn($duration) => $duration !== null);
}
if (in_array($measure, ['', 'whole'], true)) {
    $totals = array_column($rows, 'total');
    $summary[_t('Records.TOTAL_TIME', 'Celkom od príchodu po odchod')] = array_filter($totals, fn($total) => $total !== null);
}

$exportParams = ['day' => $day, 'measure' => $measure, 'sep' => $separator];
$customersCsvUrl = 'export.php?' . http_build_query(['type' => 'customers'] + $exportParams);
$eventsCsvUrl = 'export.php?' . http_build_query(['type' => 'events'] + $exportParams);

$pageTitle = _t('Nav.RECORDS', 'Záznamy');
$activeTab = 'records.php';
require __DIR__ . '/../views/records.php';
