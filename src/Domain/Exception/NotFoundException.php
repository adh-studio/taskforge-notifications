<?php

declare(strict_types=1);

namespace TaskForge\Notifications\Domain\Exception;

use RuntimeException;

/**
 * Levee lorsqu'une ressource demandee est introuvable.
 * Correspond a une reponse HTTP 404.
 */
final class NotFoundException extends RuntimeException
{
}
