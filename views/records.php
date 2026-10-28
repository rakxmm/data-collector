<?php
/** @var string $day '' = all days */
/** @var string $separator 'semicolon' or 'comma' */
/** @var array $days [day => number of customers] */
/** @var array $phases */
/** @var array $questions */
/** @var array $rows */
/** @var array $summary [name => list of durations] */
/** @var string $customersCsvUrl */
/** @var string $eventsCsvUrl */

require __DIR__ . '/header.php';
?>

<div class="view-head">
    <div>
        <h1><?= _t('Records.TITLE', 'Záznamy') ?></h1>
        <p class="view-sub"><?= _t('Records.COUNT', 'Ukončené záznamy: {count}', ['count' => count($rows)]) ?></p>
    </div>

    <div class="view-actions">
        <form class="filters" method="get">
            <label class="field-inline">
                <?= _t('Records.DAY', 'Deň') ?>
                <select name="day" data-autosubmit>
                    <option value=""><?= _t('Records.ALL_DAYS', 'Všetky dni') ?></option>
                    <?php foreach ($days as $value => $dayCount): ?>
                        <option value="<?= $value ?>" <?= $value === $day ? 'selected' : '' ?>><?= format_day($value) ?> (<?= $dayCount ?>)</option>
                    <?php endforeach ?>
                </select>
            </label>
            <label class="field-inline">
                <?= _t('Records.SEPARATOR', 'Oddeľovač') ?>
                <select name="sep" data-autosubmit>
                    <option value="semicolon"><?= _t('Records.SEPARATOR_EXCEL', '; (Excel)') ?></option>
                    <option value="comma" <?= $separator === 'comma' ? 'selected' : '' ?>><?= _t('Records.SEPARATOR_STANDARD', ', (štandard)') ?></option>
                </select>
            </label>
        </form>

        <div class="btn-group">
            <a class="btn btn-primary" href="<?= e($customersCsvUrl) ?>">
                <svg viewBox="0 0 16 16" aria-hidden="true"><path d="M8 2v8m0 0L5 7m3 3 3-3M3 13h10"/></svg>
                <?= _t('Records.EXPORT_CUSTOMERS', 'CSV – zákazníci') ?>
            </a>
            <a class="btn" href="<?= e($eventsCsvUrl) ?>" title="<?= e(_t('Records.EXPORT_EVENTS_HINT', 'Jeden riadok na každú fázu s presnými časmi')) ?>">
                <?= _t('Records.EXPORT_EVENTS', 'CSV – udalosti') ?>
            </a>
        </div>
    </div>
</div>

<?php if (!$rows): ?>
    <div class="empty">
        <strong><?= _t('Records.EMPTY_TITLE', 'Zatiaľ žiadne záznamy') ?></strong>
        <?= _t('Records.EMPTY_TEXT', 'Keď u zákazníka zaznamenáte odchod, objaví sa tu.') ?>
    </div>
<?php else: ?>

    <section class="panel summary">
        <h2><?= _t('Records.SUMMARY', 'Priemerné časy') ?></h2>
        <table class="summary-table">
            <thead>
            <tr>
                <th><?= _t('Records.PHASE', 'Fáza') ?></th>
                <th class="n"><?= _t('Records.AVERAGE', 'Priemer') ?></th>
                <th class="n"><?= _t('Records.MEDIAN', 'Medián') ?></th>
                <th class="n" title="<?= e(_t('Records.MEASURED_HINT', 'Počet zákazníkov s nameraným časom')) ?>"><?= _t('Records.MEASURED', 'Počet') ?></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($summary as $name => $durations): ?>
                <tr>
                    <td><?= e($name) ?></td>
                    <td class="n"><?= format_duration(average($durations)) ?></td>
                    <td class="n"><?= format_duration(median($durations)) ?></td>
                    <td class="n"><?= count($durations) ?></td>
                </tr>
            <?php endforeach ?>
            </tbody>
        </table>
    </section>

    <!-- On narrow screens CSS turns every row into a card. -->
    <div class="table-wrap">
        <table class="records">
            <thead>
            <tr>
                <th><?= _t('Records.CUSTOMER', 'Zákazník') ?></th>
                <th class="n"><?= _t('Records.ARRIVAL', 'Príchod') ?></th>
                <?php foreach ($phases as $phase): ?>
                    <th class="n phase"><?= e($phase['name']) ?><?= $phase['archived'] ? ' *' : '' ?></th>
                <?php endforeach ?>
                <th class="n"><?= _t('Records.TOTAL', 'Celkom') ?></th>
                <?php foreach ($questions as $question): ?>
                    <th><?= e($question['name']) ?></th>
                <?php endforeach ?>
                <th><?= _t('Records.OBSERVER', 'Pozorovateľ') ?></th>
                <th></th>
            </tr>
            </thead>

            <tbody>
            <?php foreach ($rows as $row): ?>
                <?php $customer = $row['customer']; ?>
                <tr>
                    <td class="customer-cell">
                        <span class="seq">#<?= $customer['id'] ?></span>
                        <?php if ($customer['label'] !== ''): ?>
                            <?= e($customer['label']) ?>
                        <?php else: ?>
                            <span class="muted"><?= _t('Records.NO_LABEL', 'bez označenia') ?></span>
                        <?php endif ?>
                        <?php if ($customer['status'] === 'lost'): ?>
                            <span class="badge badge-warn" title="<?= e(_t('Records.LOST_HINT', 'Stratený z dohľadu, bez času odchodu')) ?>">
                                <?= _t('Records.LOST', 'nedokončený') ?>
                            </span>
                        <?php endif ?>
                        <?php if ($customer['note'] !== ''): ?>
                            <small class="note" title="<?= e($customer['note']) ?>"><?= e($customer['note']) ?></small>
                        <?php endif ?>
                    </td>

                    <td class="n" data-label="<?= e(_t('Records.ARRIVAL', 'Príchod')) ?>">
                        <span>
                            <?= format_time($customer['arrived_at']) ?>
                            <?php if ($day === ''): ?>
                                <small><?= format_day(day_of($customer['arrived_at'])) ?></small>
                            <?php endif ?>
                        </span>
                    </td>

                    <?php foreach ($phases as $phase): ?>
                        <?php $phaseTime = $row['timeline'][$phase['id']] ?? null; ?>
                        <?php if ($phaseTime === null): ?>
                            <td class="n no-data" data-label="<?= e($phase['name']) ?>">–</td>
                        <?php elseif ($phaseTime['skipped']): ?>
                            <td class="n skipped" data-label="<?= e($phase['name']) ?>"><?= _t('Common.SKIPPED', 'preskočené') ?></td>
                        <?php else: ?>
                            <td class="n" data-label="<?= e($phase['name']) ?>"><?= format_duration($phaseTime['duration']) ?></td>
                        <?php endif ?>
                    <?php endforeach ?>

                    <td class="n total" data-label="<?= e(_t('Records.TOTAL', 'Celkom')) ?>"><?= format_duration($row['total']) ?></td>

                    <?php foreach ($questions as $question): ?>
                        <?php $answer = answer_label($row['answers'][$question['id']] ?? null); ?>
                        <td class="<?= $answer === '' ? 'no-data' : '' ?>" data-label="<?= e($question['name']) ?>"><?= $answer ?: '–' ?></td>
                    <?php endforeach ?>

                    <td class="<?= $customer['observer'] === '' ? 'no-data' : '' ?>" data-label="<?= e(_t('Records.OBSERVER', 'Pozorovateľ')) ?>">
                        <?= e($customer['observer']) ?: '–' ?>
                    </td>

                    <td class="actions">
                        <form method="post" action="customer.php"
                              data-confirm="<?= e(_t('Records.DELETE_CONFIRM', 'Vymazať záznam #{id}? Táto akcia sa nedá vrátiť.', ['id' => $customer['id']])) ?>">
                            <?= customer_fields('delete', $customer['id']) ?>
                            <input type="hidden" name="back" value="records">
                            <input type="hidden" name="day" value="<?= e($day) ?>">
                            <button class="btn btn-sm btn-icon btn-ghost" title="<?= e(_t('Records.DELETE', 'Vymazať záznam')) ?>">
                                <svg viewBox="0 0 16 16" aria-hidden="true"><path d="M3 4.5h10M6.5 4.5V3h3v1.5M4.5 4.5l.6 8.5h5.8l.6-8.5"/></svg>
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach ?>
            </tbody>
        </table>
    </div>
<?php endif ?>

<p class="hint">
    <?= _t('Records.HINT', 'Export „zákazníci“ obsahuje jeden riadok na zákazníka s trvaním fáz v sekundách. Export „udalosti“ obsahuje presné časy začiatku a konca každej fázy. Oba zahŕňajú aj práve sledovaných zákazníkov. Fázy s hviezdičkou boli odstránené v nastavení.') ?>
</p>

<?php require __DIR__ . '/footer.php'; ?>
