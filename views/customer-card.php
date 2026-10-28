<?php
/** @var array $card ['customer', 'progress', 'timeline', 'answers'] */
/** @var array $questions */
/** @var array $stepNames [step id => name] */

$customer = $card['customer'];
$progress = $card['progress'];
$id = $customer['id'];
$current = $progress['current'];
$next = $progress['next'];

$phaseIds = array_column($progress['phases'], 'id');
$phaseNumber = array_search($current['id'], $phaseIds);
?>
<article class="card" id="customer-<?= $id ?>">

    <header class="card-head">
        <span class="seq">#<?= $id ?></span>

        <form method="post" action="customer.php">
            <?= customer_fields('label', $id) ?>
            <input type="text" name="label" class="label-input" value="<?= e($customer['label']) ?>"
                   placeholder="<?= e(_t('Card.LABEL_PLACEHOLDER', 'Označenie, napr. modrá bunda')) ?>"
                   maxlength="200" enterkeyhint="done" data-autosave>
        </form>

        <div class="card-total" title="<?= e(_t('Card.SINCE_ARRIVAL', 'Čas od príchodu')) ?>">
            <span class="num" data-since="<?= $customer['arrived_at'] ?>"></span>
            <small><?= _t('Card.ARRIVED_AT', 'príchod {time}', ['time' => format_time($customer['arrived_at'])]) ?></small>
        </div>
    </header>

    <ol class="track" aria-hidden="true">
        <?php foreach ($progress['phases'] as $phase): ?>
            <?php
            $visited = $card['timeline'][$phase['id']] ?? null;
            $state = match (true) {
                $visited === null => '',
                $visited['skipped'] => 'skipped',
                $phase['id'] === $current['id'] => 'current',
                default => 'done',
            };
            ?>
            <li class="<?= $state ?>" title="<?= e($phase['name']) ?>"></li>
        <?php endforeach ?>
    </ol>

    <div class="now">
        <div class="now-meta">
            <span class="live"><?= _t('Card.RUNNING', 'Prebieha') ?></span>
            <span><?= _t('Card.PHASE_OF', 'Fáza {number} / {total}', [
                'number' => $phaseNumber === false ? '–' : $phaseNumber + 1,
                'total' => count($phaseIds),
            ]) ?></span>
        </div>
        <div class="now-name"><?= e($current['name']) ?></div>
        <div class="now-time" data-since="<?= $progress['current_since'] ?>"></div>
    </div>

    <div class="card-body">
        <form method="post" action="customer.php">
            <?= customer_fields('next', $id) ?>
            <?php if ($next): ?>
                <button class="btn-next">
                    <span><span class="lead"><?= _t('Card.NEXT', 'Ďalej') ?></span><strong><?= e($next['name']) ?></strong></span>
                    <svg viewBox="0 0 20 20" aria-hidden="true"><path d="M4 10h12m0 0-4-4m4 4-4 4"/></svg>
                </button>
            <?php else: ?>
                <button class="btn-next is-final">
                    <span>
                        <span class="lead"><?= _t('Card.LAST_PHASE', 'Posledná fáza') ?></span>
                        <strong><?= _t('Card.DEPARTURE', 'Odchod zákazníka') ?></strong>
                    </span>
                    <svg viewBox="0 0 20 20" aria-hidden="true"><path d="m4.5 10.5 3.5 3.5 7.5-8"/></svg>
                </button>
            <?php endif ?>
        </form>

        <?php foreach ($questions as $question): ?>
            <?php $answer = $card['answers'][$question['id']] ?? null; ?>
            <form class="question" method="post" action="customer.php">
                <?= customer_fields('answer', $id) ?>
                <input type="hidden" name="step" value="<?= $question['id'] ?>">
                <span class="question-name"><?= e($question['name']) ?></span>
                <span class="seg">
                    <button name="value" value="1" aria-pressed="<?= $answer === 1 ? 'true' : 'false' ?>"><?= _t('Common.YES', 'Áno') ?></button>
                    <button name="value" value="0" aria-pressed="<?= $answer === 0 ? 'true' : 'false' ?>"><?= _t('Common.NO', 'Nie') ?></button>
                </span>
            </form>
        <?php endforeach ?>

        <div class="tools">
            <form method="post" action="customer.php">
                <?= customer_fields('undo', $id) ?>
                <button class="btn btn-sm" <?= count($progress['marks']) <= 1 ? 'disabled' : '' ?>
                        title="<?= e(_t('Card.UNDO_HINT', 'Vráti posledný krok')) ?>">
                    <svg viewBox="0 0 16 16" aria-hidden="true"><path d="M5.5 3.5 2.5 6.5l3 3M2.8 6.5H10a3.5 3.5 0 0 1 0 7H7"/></svg>
                    <?= _t('Common.UNDO', 'Späť') ?>
                </button>
            </form>
            <form method="post" action="customer.php">
                <?= customer_fields('skip', $id) ?>
                <button class="btn btn-sm" <?= $next ? '' : 'disabled' ?>
                        title="<?= $next ? e(_t('Card.SKIP_HINT', 'Preskočí fázu „{name}“', ['name' => $next['name']])) : '' ?>">
                    <svg viewBox="0 0 16 16" aria-hidden="true"><path d="m3 4 4.5 4L3 12M9 4l4.5 4L9 12"/></svg>
                    <?= _t('Card.SKIP', 'Preskočiť') ?>
                </button>
            </form>
        </div>
    </div>

    <details class="more">
        <summary><?= _t('Card.MORE', 'Priebeh, poznámka a ďalšie') ?></summary>

        <ol class="history">
            <?php foreach ($card['timeline'] as $stepId => $phase): ?>
                <?php $isCurrent = $phase['end'] === null; ?>
                <li class="<?= $phase['skipped'] ? 'is-skipped' : '' ?> <?= $isCurrent ? 'is-current' : '' ?>">
                    <span><?= e($stepNames[$stepId] ?? '?') ?></span>
                    <?php if ($phase['skipped']): ?>
                        <span class="num"><?= _t('Common.SKIPPED', 'preskočené') ?></span>
                    <?php elseif ($isCurrent): ?>
                        <span class="num" data-since="<?= $phase['start'] ?>"></span>
                    <?php else: ?>
                        <span class="num"><?= format_duration($phase['duration']) ?></span>
                    <?php endif ?>
                </li>
            <?php endforeach ?>
        </ol>

        <form method="post" action="customer.php">
            <?= customer_fields('note', $id) ?>
            <label class="field-label" for="note-<?= $id ?>"><?= _t('Card.NOTE', 'Poznámka') ?></label>
            <textarea id="note-<?= $id ?>" name="note" rows="2" maxlength="2000"
                      placeholder="<?= e(_t('Card.NOTE_PLACEHOLDER', 'Napr. skupina 3 osôb, vracal sa po príbor…')) ?>"
                      data-autosave><?= e($customer['note']) ?></textarea>
        </form>

        <?php if ($customer['observer'] !== '' && mb_strtolower($customer['observer']) !== mb_strtolower(current_observer())): ?>
            <p class="observer-tag"><?= _t('Card.TRACKED_BY', 'Sleduje: {name}', ['name' => e($customer['observer'])]) ?></p>
        <?php endif ?>

        <div class="more-actions">
            <form method="post" action="customer.php">
                <?= customer_fields('finish', $id) ?>
                <button class="btn btn-sm" title="<?= e(_t('Card.LEAVE_NOW_HINT', 'Zaznamená odchod teraz, zvyšné fázy ostanú prázdne')) ?>">
                    <?= _t('Card.LEAVE_NOW', 'Odchod teraz') ?>
                </button>
            </form>
            <form method="post" action="customer.php">
                <?= customer_fields('lost', $id) ?>
                <button class="btn btn-sm" title="<?= e(_t('Card.LOST_HINT', 'Zákazník sa stratil z dohľadu – bez času odchodu')) ?>">
                    <?= _t('Card.LOST', 'Stratený z dohľadu') ?>
                </button>
            </form>
            <form method="post" action="customer.php"
                  data-confirm="<?= e(_t('Card.DELETE_CONFIRM', 'Vymazať zákazníka #{id}? Namerané časy sa stratia.', ['id' => $id])) ?>">
                <?= customer_fields('delete', $id) ?>
                <button class="btn btn-sm btn-danger"><?= _t('Common.DELETE', 'Vymazať') ?></button>
            </form>
        </div>
    </details>
</article>
