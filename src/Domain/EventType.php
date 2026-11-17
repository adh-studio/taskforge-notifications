<?php

declare(strict_types=1);

namespace TaskForge\Notifications\Domain;

/**
 * Types d'evenements metier recus depuis taskforge-api.
 *
 * Chaque type decrit un fait survenu sur une tache et declenche
 * la generation d'une notification pour l'utilisateur concerne.
 */
enum EventType: string
{
    case TaskCreated = 'task.created';
    case TaskCompleted = 'task.completed';

    /**
     * Libelle lisible utilise pour composer le message de notification.
     */
    public function libelle(): string
    {
        return match ($this) {
            self::TaskCreated => 'Nouvelle tache creee',
            self::TaskCompleted => 'Tache terminee',
        };
    }
}
