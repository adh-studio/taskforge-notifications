<?php

declare(strict_types=1);

namespace TaskForge\Notifications\Support;

use DateTimeImmutable;

/**
 * Abstraction du temps courant.
 *
 * Injectee dans les services pour rendre le comportement temporel
 * deterministe et testable (on substitue une horloge figee en test).
 */
interface Clock
{
    public function maintenant(): DateTimeImmutable;
}
