<?php

declare(strict_types=1);

namespace TaskForge\Notifications\Service;

use TaskForge\Notifications\Repository\NotificationRepositoryInterface;

/**
 * Produit des rapports agreges sur les notifications d'un utilisateur.
 *
 * Service separe du NotificationService pour respecter la responsabilite
 * unique : la lecture analytique n'a pas les memes raisons de changer
 * que la logique de cycle de vie des notifications.
 */
final class ReportService
{
    public function __construct(
        private readonly NotificationRepositoryInterface $repository,
    ) {
    }

    /**
     * Rapport du nombre de notifications par statut pour un utilisateur.
     *
     * @return array{user_id: int, total: int, par_statut: array<string, int>}
     */
    public function rapportParStatut(int $userId): array
    {
        $parStatut = $this->repository->compterParStatut($userId);
        $total = array_sum($parStatut);

        return [
            'user_id' => $userId,
            'total' => $total,
            'par_statut' => $parStatut,
        ];
    }
}
