<?php
/** @var array $cards */
/** @var int $totalCount tracked customers before the "only mine" filter */
/** @var array|null $closedCustomer */

require __DIR__ . '/header.php';
?>

<div class="view-head">
    <div>
        <h1><?= _t('Measure.TITLE', 'Meranie') ?></h1>
        <p class="view-sub">
            <?= _t('Measure.TRACKED', 'Sledovaní zákazníci: {count}', ['count' => $totalCount]) ?>
            <?php if (count($cards) !== $totalCount): ?>
                · <?= _t('Measure.SHOWN', 'zobrazení: {count}', ['count' => count($cards)]) ?>
            <?php endif ?>
        </p>
    </div>

    <form class="view-actions" method="post" action="preferences.php">
        <input type="hidden" name="back" value="index.php">
        <input type="hidden" name="only_mine" value="0">
        <label class="switch">
            <input type="checkbox" name="only_mine" value="1" <?= only_mine() ? 'checked' : '' ?> data-autosubmit>
            <span class="switch-track"></span>
            <span><?= _t('Measure.ONLY_MINE', 'Len moji') ?></span>
        </label>
    </form>
</div>

<div class="board">
    <?php foreach ($cards as $card): ?>
        <?php require __DIR__ . '/customer-card.php'; ?>
    <?php endforeach ?>

    <?php if (!$cards): ?>
        <div class="empty">
            <strong><?= _t('Measure.EMPTY_TITLE', 'Nikto sa práve nesleduje') ?></strong>
            <?= _t('Measure.EMPTY_TEXT', 'Keď zákazník vojde, ťuknite na „Príchod zákazníka“. Čas sa zaznamená v momente ťuknutia.') ?>
        </div>
    <?php endif ?>
</div>

<form class="arrive-bar" id="arrive-form" method="post" action="customer.php">
    <input type="hidden" name="action" value="arrive">
    <button class="btn-arrive">
        <svg viewBox="0 0 20 20" aria-hidden="true"><path d="M10 4v12M4 10h12"/></svg>
        <span><?= _t('Measure.ARRIVE', 'Príchod zákazníka') ?></span>
        <kbd>N</kbd>
    </button>
</form>

<?php if ($closedCustomer): ?>
    <form class="toast" method="post" action="customer.php">
        <?= customer_fields('reopen', $closedCustomer['id']) ?>
        <span>
            <?php if ($closedCustomer['status'] === 'done'): ?>
                <?= _t('Measure.LEFT', '#{id} – odchod zaznamenaný ({time})', [
                    'id' => $closedCustomer['id'],
                    'time' => format_duration(total_duration($closedCustomer)),
                ]) ?>
            <?php else: ?>
                <?= _t('Measure.STOPPED', '#{id} – sledovanie ukončené', ['id' => $closedCustomer['id']]) ?>
            <?php endif ?>
        </span>
        <button><?= _t('Common.UNDO', 'Späť') ?></button>
    </form>
<?php endif ?>

<?php require __DIR__ . '/footer.php'; ?>
