<?php
// Handles all buttons on the customer cards.

require __DIR__ . '/../src/bootstrap.php';

$action = $_POST['action'] ?? '';

if ($action === 'arrive') {
    $newId = arrive(current_observer(), single_phase());
    redirect($newId ? "index.php#customer-$newId" : 'settings.php');
}

$customer = get_customer((int) ($_POST['id'] ?? 0));
if (!$customer) {
    redirect('index.php');
}
$id = $customer['id'];

// A finished customer can still be on an old page on another device.
if ($customer['status'] !== 'active' && !in_array($action, ['reopen', 'delete'], true)) {
    redirect('index.php');
}

switch ($action) {
    case 'next':
        go_to_next_phase($customer);
        break;
    case 'skip':
        skip_next_phase($customer);
        break;
    case 'undo':
        undo_last_step($customer);
        break;
    case 'answer':
        toggle_answer($id, (int) $_POST['step'], (int) $_POST['value']);
        break;
    case 'label':
        set_label($id, $_POST['label'] ?? '');
        break;
    case 'note':
        set_note($id, $_POST['note'] ?? '');
        break;
    case 'finish':
        customer_left($id);
        break;
    case 'lost':
        customer_lost($id);
        break;
    case 'discard':
        discard_customer($id);
        break;
    case 'reopen':
        reopen_customer($id);
        break;
    case 'delete':
        delete_customer($id);
        if (($_POST['back'] ?? '') === 'records') {
            redirect('records.php?day=' . urlencode($_POST['day'] ?? ''));
        }
        redirect('index.php');
}

// A customer who just left disappears from the board, so show the undo message instead.
if (get_customer($id)['status'] !== 'active') {
    redirect("index.php?closed=$id");
}
redirect("index.php#customer-$id");
