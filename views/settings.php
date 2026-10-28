<?php
/** @var array $sections phases and questions */

require __DIR__ . '/header.php';
?>

<div class="view-head">
    <div>
        <h1><?= _t('Settings.TITLE', 'Nastavenie') ?></h1>
        <p class="view-sub"><?= _t('Settings.SUBTITLE', 'Zmeny sa prejavia okamžite na všetkých zariadeniach.') ?></p>
    </div>
</div>

<div class="settings-grid">
    <?php foreach ($sections as $section): ?>
        <section class="panel">
            <header class="panel-head">
                <h2><?= e($section['title']) ?></h2>
                <p><?= e($section['description']) ?></p>
            </header>

            <ol class="step-list">
                <?php foreach ($section['steps'] as $index => $step): ?>
                    <?php
                    $isFirst = $index === 0;
                    $isLast = $index === count($section['steps']) - 1;
                    $isOnlyPhase = $section['type'] === 'phase' && count($section['steps']) === 1;
                    ?>
                    <li>
                        <span class="step-index"><?= $index + 1 ?></span>

                        <form method="post">
                            <input type="hidden" name="action" value="rename">
                            <input type="hidden" name="id" value="<?= $step['id'] ?>">
                            <input type="text" name="name" value="<?= e($step['name']) ?>" maxlength="80"
                                   aria-label="<?= e(_t('Settings.NAME', 'Názov')) ?>" data-autosubmit>
                        </form>

                        <form method="post">
                            <input type="hidden" name="action" value="up">
                            <input type="hidden" name="id" value="<?= $step['id'] ?>">
                            <button class="btn btn-sm btn-icon btn-ghost" title="<?= e(_t('Settings.MOVE_UP', 'Posunúť vyššie')) ?>" <?= $isFirst ? 'disabled' : '' ?>>
                                <svg viewBox="0 0 16 16" aria-hidden="true"><path d="M8 12.5v-9m0 0L4.5 7M8 3.5 11.5 7"/></svg>
                            </button>
                        </form>

                        <form method="post">
                            <input type="hidden" name="action" value="down">
                            <input type="hidden" name="id" value="<?= $step['id'] ?>">
                            <button class="btn btn-sm btn-icon btn-ghost" title="<?= e(_t('Settings.MOVE_DOWN', 'Posunúť nižšie')) ?>" <?= $isLast ? 'disabled' : '' ?>>
                                <svg viewBox="0 0 16 16" aria-hidden="true"><path d="M8 3.5v9m0 0L4.5 9M8 12.5 11.5 9"/></svg>
                            </button>
                        </form>

                        <form method="post"
                              data-confirm="<?= e(_t('Settings.REMOVE_CONFIRM', 'Odstrániť „{name}“? Ak už obsahuje namerané údaje, zostanú zachované v exporte.', ['name' => $step['name']])) ?>">
                            <input type="hidden" name="action" value="remove">
                            <input type="hidden" name="id" value="<?= $step['id'] ?>">
                            <button class="btn btn-sm btn-icon btn-ghost" title="<?= e(_t('Settings.REMOVE', 'Odstrániť')) ?>" <?= $isOnlyPhase ? 'disabled' : '' ?>>
                                <svg viewBox="0 0 16 16" aria-hidden="true"><path d="m4 4 8 8m0-8-8 8"/></svg>
                            </button>
                        </form>
                    </li>
                <?php endforeach ?>
            </ol>

            <form class="step-add" method="post">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="type" value="<?= $section['type'] ?>">
                <input type="text" name="name" maxlength="80" placeholder="<?= e($section['placeholder']) ?>" required>
                <button class="btn"><?= e($section['button']) ?></button>
            </form>

            <?php if ($section['archived']): ?>
                <div class="archived">
                    <?= _t('Settings.ARCHIVED', 'Odstránené, s uloženými údajmi (zostávajú v exporte):') ?>
                    <ul>
                        <?php foreach ($section['archived'] as $step): ?>
                            <li>
                                <?= e($step['name']) ?>
                                <form method="post">
                                    <input type="hidden" name="action" value="restore">
                                    <input type="hidden" name="id" value="<?= $step['id'] ?>">
                                    <button class="btn btn-sm btn-ghost"><?= _t('Settings.RESTORE', 'Obnoviť') ?></button>
                                </form>
                            </li>
                        <?php endforeach ?>
                    </ul>
                </div>
            <?php endif ?>
        </section>
    <?php endforeach ?>

    <section class="panel panel-danger">
        <header class="panel-head">
            <h2><?= _t('Settings.DATA', 'Údaje') ?></h2>
            <p><?= _t('Settings.DATA_TEXT', 'Vymaže všetkých zákazníkov a namerané časy. Nastavenie fáz zostane. Pred vymazaním si údaje vyexportujte.') ?></p>
        </header>
        <form method="post" data-confirm="<?= e(_t('Settings.WIPE_CONFIRM', 'Naozaj vymazať všetky záznamy a namerané časy? Táto akcia sa nedá vrátiť.')) ?>">
            <input type="hidden" name="action" value="wipe">
            <button class="btn btn-danger"><?= _t('Settings.WIPE', 'Vymazať všetky záznamy') ?></button>
        </form>
    </section>
</div>

<?php require __DIR__ . '/footer.php'; ?>
