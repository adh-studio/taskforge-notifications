<?php

declare(strict_types=1);

namespace TaskForge\Notifications\Domain\Exception;

use RuntimeException;

/**
 * Levee lorsqu'une charge utile entrante est invalide (donnees manquantes
 * ou mal typees). Correspond a une reponse HTTP 422.
 */
final class ValidationException extends RuntimeException
{
}
