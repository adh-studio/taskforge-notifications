<?php

declare(strict_types=1);

namespace TaskForge\Notifications\Service;

use TaskForge\Notifications\Domain\EventType;
use TaskForge\Notifications\Domain\Exception\NotFoundException;
use TaskForge\Notifications\Domain\Exception\ValidationException;
use TaskForge\Notifications\Domain\Notification;
use TaskForge\Notifications\Domain\NotificationStatus;
use TaskForge\Notifications\Repository\NotificationRepositoryInterface;
use TaskForge\Notifications\Support\Clock;

/**
 * Logique metier des notifications.
 *
 * Responsabilite unique (principe S de SOLID) : transformer un evenement
 * metier en notification, lister et marquer comme lue. La persistance et
 * le temps sont injectes via des abstractions.
 */
final class NotificationService
{
    public function __construct(
        private readonly NotificationRepositoryInterface $repository,
        private readonly Clock $clock,
    ) {
    }

    /**
     * Enregistre un evenement entrant et cree la notification associee.
     *
     * @param array<string, mixed> $charge Charge utile deja decodee du JSON
     *
     * @throws ValidationException si la charge est incomplete ou invalide
     */
    public function enregistrerEvenement(array $charge): Notification
    {
        $userId = $this->entierPositif($charge, 'user_id');
        $taskId = $this->entierPositif($charge, 'task_id');
        $eventType = $this->typeEvenement($charge);
        $taskTitle = $this->chaineNonVide($charge, 'task_title');

        $notification = Notification::depuisEvenement(
            userId: $userId,
            eventType: $eventType,
            taskId: $taskId,
            taskTitle: $taskTitle,
            createdAt: $this->clock->maintenant(),
        );

        return $this->repository->enregistrer($notification);
    }

    /**
     * Liste les notifications d'un utilisateur, filtrables par statut.
     *
     * @return list<Notification>
     */
    public function listerPourUtilisateur(int $userId, ?string $statutBrut = null): array
    {
        $statut = null;

        if ($statutBrut !== null && $statutBrut !== '') {
            $statut = NotificationStatus::tryFrom($statutBrut);

            if ($statut === null) {
                throw new ValidationException(
                    sprintf('Statut de filtre invalide : "%s".', $statutBrut),
                );
            }
        }

        return $this->repository->parUtilisateur($userId, $statut);
    }

    /**
     * Marque une notification comme lue pour un utilisateur donne.
     *
     * L'operation est idempotente : marquer une notification deja lue
     * renvoie l'etat courant sans nouvelle ecriture. Le controle de
     * propriete est fait avant toute mutation : une notification
     * appartenant a un autre utilisateur est traitee comme introuvable
     * afin de ne pas divulguer son existence (OWASP A01).
     *
     * @throws NotFoundException si la notification n'existe pas ou n'appartient pas a l'utilisateur
     */
    public function marquerCommeLue(int $notificationId, int $userId): Notification
    {
        $notification = $this->repository->parId($notificationId);

        if ($notification === null || $notification->userId !== $userId) {
            throw new NotFoundException(
                sprintf('Notification %d introuvable.', $notificationId),
            );
        }

        if ($notification->estLue()) {
            return $notification;
        }

        $lue = $notification->marquerCommeLue($this->clock->maintenant());
        $this->repository->mettreAJour($lue);

        return $lue;
    }

    /**
     * Extrait un entier strictement positif, ou leve une exception.
     *
     * @param array<string, mixed> $charge
     */
    private function entierPositif(array $charge, string $cle): int
    {
        if (!isset($charge[$cle]) || !is_numeric($charge[$cle])) {
            throw new ValidationException(sprintf('Le champ "%s" est requis et numerique.', $cle));
        }

        $valeur = (int) $charge[$cle];

        if ($valeur <= 0) {
            throw new ValidationException(sprintf('Le champ "%s" doit etre strictement positif.', $cle));
        }

        return $valeur;
    }

    /**
     * Extrait une chaine non vide et bornee, ou leve une exception.
     *
     * @param array<string, mixed> $charge
     */
    private function chaineNonVide(array $charge, string $cle): string
    {
        if (!isset($charge[$cle]) || !is_string($charge[$cle])) {
            throw new ValidationException(sprintf('Le champ "%s" est requis.', $cle));
        }

        $valeur = trim($charge[$cle]);

        if ($valeur === '') {
            throw new ValidationException(sprintf('Le champ "%s" ne peut pas etre vide.', $cle));
        }

        if (mb_strlen($valeur) > 255) {
            throw new ValidationException(sprintf('Le champ "%s" depasse 255 caracteres.', $cle));
        }

        return $valeur;
    }

    /**
     * Resout le type d'evenement a partir de la charge utile.
     *
     * @param array<string, mixed> $charge
     */
    private function typeEvenement(array $charge): EventType
    {
        if (!isset($charge['event_type']) || !is_string($charge['event_type'])) {
            throw new ValidationException('Le champ "event_type" est requis.');
        }

        $type = EventType::tryFrom($charge['event_type']);

        if ($type === null) {
            throw new ValidationException(
                sprintf('Type d\'evenement non supporte : "%s".', $charge['event_type']),
            );
        }

        return $type;
    }
}
