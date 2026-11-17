<?php

declare(strict_types=1);

namespace TaskForge\Notifications\Tests\Support;

use DateTimeImmutable;
use TaskForge\Notifications\Support\Clock;

/**
 * Horloge figee pour des tests deterministes.
 */
final class FrozenClock implements Clock
{
    public function __construct(private DateTimeImmutable $instant)
    {
    }

    public static function a(string $datetime): self
    {
        return new self(new DateTimeImmutable($datetime));
    }

    public function maintenant(): DateTimeImmutable
    {
        return $this->instant;
    }

    public function avancerA(string $datetime): void
    {
        $this->instant = new DateTimeImmutable($datetime);
    }
}
