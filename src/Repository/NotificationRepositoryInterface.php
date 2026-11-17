<?php

declare(strict_types=1);

namespace TaskForge\Notifications\Repository;

use TaskForge\Notifications\Domain\Notification;
use TaskForge\Notifications\Domain\NotificationStatus;

/**
 * Contrat de persistance des notifications.
 *
 * L'abstraction permet d'inverser la dependance (principe D de SOLID) :
 * les services dependent de cette interface, pas d'une implementation PDO.
 */
interface NotificationRepositoryInterface
{
    /**
     * Persiste une nouvelle notification et renvoie l'entite dotee de son id.
     */
    public function enregistrer(Notification $notification): Notification;

    /**
     * Recupere une notification par son identifiant, ou null si absente.
     */
    public function parId(int $id): ?Notification;

    /**
     * Liste les notifications d'un utilisateur, les plus recentes d'abord.
     *
     * @return list<Notification>
     */
    public function parUtilisateur(int $userId, ?NotificationStatus $statut = null): array;

    /**
     * Met a jour le statut de lecture d'une notification existante.
     */
    public function mettreAJour(Notification $notification): void;

    /**
     * Compte les notifications d'un utilisateur regroupees par statut.
     *
     * @return array<string, int> Cle = valeur du statut, valeur = total
     */
    public function compterParStatut(int $userId): array;
}
