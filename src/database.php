<?php

function db(): PDO
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $dataDir = getenv('DATA_DIR') ?: __DIR__ . '/../data';
    if (!is_dir($dataDir)) {
        mkdir($dataDir, 0775, true);
    }

    $pdo = new PDO('sqlite:' . $dataDir . '/data-collector.sqlite');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA journal_mode = WAL');
    $pdo->exec('PRAGMA foreign_keys = ON');

    create_tables($pdo);

    return $pdo;
}

function query(string $sql, array $params = []): PDOStatement
{
    $statement = db()->prepare($sql);
    $statement->execute($params);
    return $statement;
}

function create_tables(PDO $pdo): void
{
    // Times are unix timestamps in seconds.
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS steps (
            id       INTEGER PRIMARY KEY,
            type     TEXT    NOT NULL,             -- 'phase' or 'question'
            name     TEXT    NOT NULL,
            position INTEGER NOT NULL,
            archived INTEGER NOT NULL DEFAULT 0    -- removed in settings, kept because it has data
        );

        CREATE TABLE IF NOT EXISTS customers (
            id         INTEGER PRIMARY KEY,
            label      TEXT NOT NULL DEFAULT '',
            observer   TEXT NOT NULL DEFAULT '',
            note       TEXT NOT NULL DEFAULT '',
            status     TEXT NOT NULL DEFAULT 'active', -- 'active', 'done' or 'lost'
            arrived_at REAL NOT NULL,
            left_at    REAL
        );

        -- Start of a phase. The phase ends when the next one starts or the customer leaves.
        CREATE TABLE IF NOT EXISTS marks (
            id          INTEGER PRIMARY KEY,
            customer_id INTEGER NOT NULL REFERENCES customers(id) ON DELETE CASCADE,
            step_id     INTEGER NOT NULL REFERENCES steps(id),
            started_at  REAL    NOT NULL,
            skipped     INTEGER NOT NULL DEFAULT 0
        );

        CREATE TABLE IF NOT EXISTS answers (
            customer_id INTEGER NOT NULL REFERENCES customers(id) ON DELETE CASCADE,
            step_id     INTEGER NOT NULL REFERENCES steps(id),
            value       INTEGER NOT NULL,          -- 1 = yes, 0 = no
            PRIMARY KEY (customer_id, step_id)
        );
    ");

    if ($pdo->query('SELECT COUNT(*) FROM steps')->fetchColumn() == 0) {
        insert_default_steps($pdo);
    }
}

function insert_default_steps(PDO $pdo): void
{
    $phases = [
        'Výber jedla',
        'Čakanie v rade',
        'Obsluha',
        'Presun k stolu',
        'Jedenie',
        'Presun k odnášaniu tácky',
        'Čakanie v rade pri odnášaní',
        'Upratanie tácky',
    ];
    $questions = ['Polievka'];

    $insert = $pdo->prepare('INSERT INTO steps (type, name, position) VALUES (?, ?, ?)');
    foreach ($phases as $position => $name) {
        $insert->execute(['phase', $name, $position]);
    }
    foreach ($questions as $position => $name) {
        $insert->execute(['question', $name, $position]);
    }
}
