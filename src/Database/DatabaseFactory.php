<?php

declare(strict_types=1);

namespace TaskForge\Notifications\Database;

use PDO;
use RuntimeException;

/**
 * Fabrique de connexions PDO configurees de maniere securisee.
 *
 * Les identifiants proviennent exclusivement de l'environnement (jamais
 * codes en dur). Le mode d'erreur est fixe sur les exceptions et
 * l'emulation des requetes preparees est desactivee pour garantir de
 * vraies requetes parametrees cote serveur (defense contre l'injection).
 */
final class DatabaseFactory
{
    /**
     * Cree une connexion MySQL depuis les variables d'environnement.
     */
    public static function depuisEnvironnement(): PDO
    {
        $hote = self::env('DB_HOST', '127.0.0.1');
        $port = self::env('DB_PORT', '3306');
        $base = self::env('DB_NAME', 'taskforge_notifications');
        $utilisateur = self::env('DB_USER', 'root');
        $motDePasse = self::env('DB_PASSWORD', '');

        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $hote, $port, $base);

        return new PDO($dsn, $utilisateur, $motDePasse, self::options());
    }

    /**
     * @return array<int, mixed>
     */
    public static function options(): array
    {
        return [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
    }

    private static function env(string $cle, string $defaut): string
    {
        $valeur = getenv($cle);

        if ($valeur === false || $valeur === '') {
            if ($defaut === '' && $cle === 'DB_PASSWORD') {
                return '';
            }

            return $defaut;
        }

        return $valeur;
    }
}
