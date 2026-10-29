<?php

require __DIR__ . '/../src/bootstrap.php';

$customers = get_active_customers();
$totalCount = count($customers);

if (only_mine()) {
    $me = mb_strtolower(current_observer());
    $customers = array_filter($customers, fn($customer) => mb_strtolower($customer['observer']) === $me);
}

$phases = get_phases();
$singlePhase = single_phase();
$questions = get_questions();
$stepNames = get_step_names();

$cards = [];
foreach ($customers as $customer) {
    $progress = get_progress($customer);
    $cards[] = [
        'customer' => $customer,
        'progress' => $progress,
        'timeline' => get_timeline($customer, $progress['marks']),
        'answers' => get_answers($customer['id']),
    ];
}

$closedCustomer = isset($_GET['closed']) ? get_customer((int) $_GET['closed']) : null;
if ($closedCustomer && $closedCustomer['status'] === 'active') {
    $closedCustomer = null;
}

$pageTitle = _t('Nav.MEASURE', 'Meranie');
$activeTab = 'index.php';
require __DIR__ . '/../views/measure.php';
