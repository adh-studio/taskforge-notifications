<?php

declare(strict_types=1);

namespace TaskForge\Notifications\Http;

use TaskForge\Notifications\Auth\JwtValidator;
use TaskForge\Notifications\Domain\Notification;
use TaskForge\Notifications\Service\NotificationService;
use TaskForge\Notifications\Service\ReportService;

/**
 * Controleur HTTP du microservice de notifications.
 *
 * Fin et sans logique metier : il authentifie l'appelant, delegue aux
 * services puis formate la reponse JSON. Les erreurs metier remontent
 * sous forme d'exceptions traitees de maniere centralisee par le noyau.
 */
final class NotificationController
{
    public function __construct(
        private readonly NotificationService $notifications,
        private readonly ReportService $rapports,
        private readonly JwtValidator $jwt,
    ) {
    }

    /**
     * POST /events : enregistre un evenement et cree la notification.
     *
     * @param array<string, string> $params
     */
    public function enregistrerEvenement(Request $requete, array $params): Response
    {
        // L'appel provient d'un service de confiance (taskforge-api) :
        // on exige un JWT valide avant toute ecriture.
        $this->jwt->utilisateurDepuisEntete($requete->entete('authorization'));

        $notification = $this->notifications->enregistrerEvenement($requete->corps);

        return Response::json(201, $notification->enTableau());
    }

    /**
     * GET /notifications : liste les notifications de l'utilisateur du jeton.
     *
     * @param array<string, string> $params
     */
    public function lister(Request $requete, array $params): Response
    {
        $userId = $this->jwt->utilisateurDepuisEntete($requete->entete('authorization'));

        $notifications = $this->notifications->listerPourUtilisateur(
            $userId,
            $requete->parametreQuery('status'),
        );

        return Response::json(200, [
            'data' => array_map(
                static fn (Notification $n): array => $n->enTableau(),
                $notifications,
            ),
        ]);
    }

    /**
     * PATCH /notifications/{id}/read : marque une notification comme lue.
     *
     * @param array<string, string> $params
     */
    public function marquerCommeLue(Request $requete, array $params): Response
    {
        $userId = $this->jwt->utilisateurDepuisEntete($requete->entete('authorization'));

        // Le service verifie la propriete avant toute mutation : un
        // utilisateur ne peut agir que sur ses propres notifications.
        $notification = $this->notifications->marquerCommeLue((int) $params['id'], $userId);

        return Response::json(200, $notification->enTableau());
    }

    /**
     * GET /reports/summary : rapport agrege par statut pour l'utilisateur.
     *
     * @param array<string, string> $params
     */
    public function rapport(Request $requete, array $params): Response
    {
        $userId = $this->jwt->utilisateurDepuisEntete($requete->entete('authorization'));

        return Response::json(200, $this->rapports->rapportParStatut($userId));
    }
}
