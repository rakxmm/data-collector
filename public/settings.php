<?php

require __DIR__ . '/../src/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    $name = mb_substr(trim($_POST['name'] ?? ''), 0, 80);
    $type = ($_POST['type'] ?? '') === 'question' ? 'question' : 'phase';
    $options = mb_substr($_POST['options'] ?? '', 0, 200);

    switch ($_POST['action'] ?? '') {
        case 'add':
            if ($name !== '') {
                add_step($type, $name, $options);
            }
            break;
        case 'rename':
            if ($name !== '') {
                rename_step($id, $name);
            }
            if (isset($_POST['options'])) {
                set_step_options($id, $options);
            }
            break;
        case 'up':
            move_step($id, -1);
            break;
        case 'down':
            move_step($id, 1);
            break;
        case 'remove':
            remove_step($id);
            break;
        case 'restore':
            restore_step($id);
            break;
        case 'wipe':
            delete_all_customers();
            break;
    }

    redirect('settings.php');
}

$sections = [
    [
        'type' => 'phase',
        'title' => _t('Settings.PHASES', 'Fázy'),
        'description' => _t('Settings.PHASES_TEXT', 'Poradie, v akom zákazník prechádza prevádzkou. Príchod spustí prvú fázu, tlačidlo „Ďalej“ ukončí aktuálnu a spustí nasledujúcu, odchod ukončí poslednú.'),
        'placeholder' => _t('Settings.PHASE_PLACEHOLDER', 'Nová fáza, napr. Pokladňa'),
        'button' => _t('Settings.ADD_PHASE', 'Pridať fázu'),
        'steps' => get_steps('phase'),
        'archived' => get_steps('phase', archived: true),
    ],
    [
        'type' => 'question',
        'title' => _t('Settings.QUESTIONS', 'Doplňujúce otázky'),
        'description' => _t('Settings.QUESTIONS_TEXT', 'Odpovede áno / nie alebo výber z možností (napr. pult: objednaný, neobjednaný, buffet). Odpovede sa ukladajú podľa poradia možností, preto ho po začatí merania nemeňte.'),
        'placeholder' => _t('Settings.QUESTION_PLACEHOLDER', 'Nová otázka, napr. Dezert'),
        'button' => _t('Settings.ADD_QUESTION', 'Pridať otázku'),
        'steps' => get_steps('question'),
        'archived' => get_steps('question', archived: true),
    ],
];

$pageTitle = _t('Nav.SETTINGS', 'Nastavenie');
$activeTab = 'settings.php';
require __DIR__ . '/../views/settings.php';
