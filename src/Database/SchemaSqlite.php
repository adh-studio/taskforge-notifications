<?php

declare(strict_types=1);

namespace TaskForge\Notifications\Database;

use PDO;

/**
 * Schema equivalent pour SQLite, utilise par la suite de tests.
 *
 * La production tourne sur MySQL (voir migrations/), mais les tests
 * unitaires s'appuient sur une base SQLite en memoire : rapide, isolee
 * et sans service externe. Le repository etant ecrit en SQL portable
 * (requetes preparees standard), le meme code sert les deux moteurs.
 */
final class SchemaSqlite
{
    public static function creer(PDO $pdo): void
    {
        $pdo->exec(
            'CREATE TABLE notifications (
                id          INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id     INTEGER NOT NULL,
                event_type  TEXT    NOT NULL,
                task_id     INTEGER NOT NULL,
                message     TEXT    NOT NULL,
                status      TEXT    NOT NULL DEFAULT \'unread\',
                created_at  TEXT    NOT NULL,
                read_at     TEXT    NULL
            )',
        );

        $pdo->exec('CREATE INDEX idx_user_created ON notifications (user_id, created_at)');
        $pdo->exec('CREATE INDEX idx_user_status ON notifications (user_id, status)');
    }
}
