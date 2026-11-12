<?php
// ?day=YYYY-MM-DD&measure=whole|arrival|<phase id>&cols[]=<column key>&sep=semicolon|comma

require __DIR__ . '/../src/bootstrap.php';

$day = requested_day();
$measure = requested_measure();
$separator = ($_GET['sep'] ?? '') === 'comma' ? ',' : ';';
// Excel with the semicolon separator expects a decimal comma.
$decimalMark = $separator === ';' ? ',' : '.';
$keys = array_filter((array) ($_GET['cols'] ?? []), 'is_string');

// e.g. data-collector-cakanie-v-rade-2026-11-12.csv
$parts = array_filter(['data-collector', slug(measure_label($measure)), $day]);
send_csv(implode('-', $parts) . '.csv', export_csv_rows($day, $measure, $keys, $decimalMark), $separator);
