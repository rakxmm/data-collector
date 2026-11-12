<?php
// ?day=YYYY-MM-DD&measure=whole|arrival|<phase id>&cols[]=<column key>&sep=semicolon|comma&format=csv|txt

require __DIR__ . '/../src/bootstrap.php';

$day = requested_day();
$measure = requested_measure();
$separator = ($_GET['sep'] ?? '') === 'comma' ? ',' : ';';
$keys = array_filter((array) ($_GET['cols'] ?? []), 'is_string');
$format = ($_GET['format'] ?? '') === 'txt' ? 'txt' : 'csv';

// e.g. data-collector-cakanie-v-rade-2026-11-12.csv
$parts = array_filter(['data-collector', slug(measure_label($measure)), $day]);
send_export(implode('-', $parts), export_csv_rows($day, $measure, $keys), $separator, $format);
