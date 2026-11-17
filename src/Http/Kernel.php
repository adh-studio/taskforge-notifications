<?php

declare(strict_types=1);

namespace TaskForge\Notifications\Http;

use Throwable;
use TaskForge\Notifications\Domain\Exception\AuthenticationException;
use TaskForge\Notifications\Domain\Exception\NotFoundException;
use TaskForge\Notifications\Domain\Exception\ValidationException;

/**
 * Noyau HTTP : transforme une requete en reponse et convertit toute
 * exception metier en reponse JSON au code de statut adapte.
 *
 * Centraliser la gestion d'erreurs garantit qu'aucune trace technique
 * (message systeme, requete SQL) ne fuite vers le client (OWASP A05).
 */
final class Kernel
{
    public function __construct(private readonly Router $router)
    {
    }

    public function traiter(Request $requete): Response
    {
        try {
            return $this->router->resoudre($requete);
        } catch (ValidationException $e) {
            return Response::json(422, ['error' => $e->getMessage()]);
        } catch (AuthenticationException $e) {
            return Response::json(401, ['error' => $e->getMessage()]);
        } catch (NotFoundException $e) {
            return Response::json(404, ['error' => $e->getMessage()]);
        } catch (Throwable) {
            // Les details sont journalises cote serveur, jamais exposes.
            error_log('Erreur interne du microservice de notifications.');

            return Response::json(500, ['error' => 'Erreur interne.']);
        }
    }
}
