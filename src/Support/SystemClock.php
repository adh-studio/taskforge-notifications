<?php

declare(strict_types=1);

namespace TaskForge\Notifications\Support;

use DateTimeImmutable;
use DateTimeZone;

/**
 * Horloge de production basee sur l'heure systeme, normalisee en UTC
 * pour un stockage et des comparaisons independants du fuseau serveur.
 */
final class SystemClock implements Clock
{
    public function maintenant(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }
}
