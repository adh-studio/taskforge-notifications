<?php

declare(strict_types=1);

namespace TaskForge\Notifications\Domain;

/**
 * Statut de lecture d'une notification.
 */
enum NotificationStatus: string
{
    case Unread = 'unread';
    case Read = 'read';
}
