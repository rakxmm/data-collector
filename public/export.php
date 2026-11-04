<?php
// ?type=customers|events&day=YYYY-MM-DD&measure=whole|arrival|<phase id>&sep=semicolon|comma

require __DIR__ . '/../src/bootstrap.php';

$day = requested_day();
$measure = requested_measure();
$separator = ($_GET['sep'] ?? '') === 'comma' ? ',' : ';';
$suffix = $day === '' ? '' : "-$day";

if (($_GET['type'] ?? '') === 'events') {
    $name = _t('Export.FILE_EVENTS', 'udalosti');
    send_csv("data-collector-$name$suffix.csv", events_csv_rows($day, $measure), $separator);
}

$name = _t('Export.FILE_CUSTOMERS', 'zakaznici');
send_csv("data-collector-$name$suffix.csv", customers_csv_rows($day, $measure), $separator);
