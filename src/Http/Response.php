<?php

declare(strict_types=1);

namespace TaskForge\Notifications\Http;

/**
 * Reponse HTTP JSON immuable.
 *
 * Centralise le code de statut, la charge et les en-tetes de securite
 * appliques systematiquement a chaque reponse (OWASP A05).
 */
final class Response
{
    /**
     * @param array<string, mixed>|null $donnees
     */
    public function __construct(
        public readonly int $statut,
        public readonly ?array $donnees,
    ) {
    }

    /**
     * @param array<string, mixed> $donnees
     */
    public static function json(int $statut, array $donnees): self
    {
        return new self($statut, $donnees);
    }

    public static function vide(int $statut): self
    {
        return new self($statut, null);
    }

    /**
     * Emet la reponse (en-tetes puis corps) vers le client.
     */
    public function emettre(): void
    {
        http_response_code($this->statut);

        header('Content-Type: application/json; charset=utf-8');
        // En-tetes de securite appliques a toutes les reponses.
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Cache-Control: no-store');
        header('Referrer-Policy: no-referrer');

        if ($this->donnees !== null) {
            echo json_encode($this->donnees, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        }
    }
}
