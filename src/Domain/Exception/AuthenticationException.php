<?php

declare(strict_types=1);

namespace TaskForge\Notifications\Domain\Exception;

use RuntimeException;

/**
 * Levee lorsqu'un jeton JWT est absent, malforme, expire ou dote
 * d'une signature invalide. Correspond a une reponse HTTP 401.
 */
final class AuthenticationException extends RuntimeException
{
}
