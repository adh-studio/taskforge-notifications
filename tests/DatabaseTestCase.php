<?php

declare(strict_types=1);

namespace TaskForge\Notifications\Tests;

use PDO;
use PHPUnit\Framework\TestCase;
use TaskForge\Notifications\Database\DatabaseFactory;
use TaskForge\Notifications\Database\SchemaSqlite;

/**
 * Cas de test de base fournissant une base SQLite en memoire,
 * recreee a neuf avant chaque test pour une isolation totale.
 */
abstract class DatabaseTestCase extends TestCase
{
    protected PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pdo = new PDO('sqlite::memory:', null, null, DatabaseFactory::options());
        SchemaSqlite::creer($this->pdo);
    }
}
