<?php

declare(strict_types=1);

namespace TaskForge\Notifications\Tests\Service;

use TaskForge\Notifications\Repository\PdoNotificationRepository;
use TaskForge\Notifications\Service\NotificationService;
use TaskForge\Notifications\Service\ReportService;
use TaskForge\Notifications\Tests\DatabaseTestCase;
use TaskForge\Notifications\Tests\Support\FrozenClock;

final class ReportServiceTest extends DatabaseTestCase
{
    public function testRapportParStatutAgregeLesTotaux(): void
    {
        $depot = new PdoNotificationRepository($this->pdo);
        $clock = FrozenClock::a('2026-01-10T12:00:00+00:00');
        $notifications = new NotificationService($depot, $clock);
        $rapports = new ReportService($depot);

        $charge = [
            'user_id' => 10,
            'task_id' => 1,
            'event_type' => 'task.created',
            'task_title' => 'Tache',
        ];

        $premiere = $notifications->enregistrerEvenement($charge);
        $notifications->enregistrerEvenement($charge);
        $notifications->enregistrerEvenement($charge);
        $notifications->marquerCommeLue($premiere->id, 10);

        $rapport = $rapports->rapportParStatut(10);

        self::assertSame(10, $rapport['user_id']);
        self::assertSame(3, $rapport['total']);
        self::assertSame(2, $rapport['par_statut']['unread']);
        self::assertSame(1, $rapport['par_statut']['read']);
    }

    public function testRapportVideRenvoieDesCompteursAZero(): void
    {
        $depot = new PdoNotificationRepository($this->pdo);
        $rapports = new ReportService($depot);

        $rapport = $rapports->rapportParStatut(777);

        self::assertSame(0, $rapport['total']);
        self::assertSame(0, $rapport['par_statut']['unread']);
        self::assertSame(0, $rapport['par_statut']['read']);
    }
}
