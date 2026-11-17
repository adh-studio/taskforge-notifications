<?php

declare(strict_types=1);

namespace TaskForge\Notifications\Tests\Repository;

use DateTimeImmutable;
use TaskForge\Notifications\Domain\EventType;
use TaskForge\Notifications\Domain\Notification;
use TaskForge\Notifications\Domain\NotificationStatus;
use TaskForge\Notifications\Repository\PdoNotificationRepository;
use TaskForge\Notifications\Tests\DatabaseTestCase;

final class PdoNotificationRepositoryTest extends DatabaseTestCase
{
    private function depot(): PdoNotificationRepository
    {
        return new PdoNotificationRepository($this->pdo);
    }

    private function nouvelle(int $userId, string $titre, string $date): Notification
    {
        return Notification::depuisEvenement(
            userId: $userId,
            eventType: EventType::TaskCreated,
            taskId: 1,
            taskTitle: $titre,
            createdAt: new DateTimeImmutable($date),
        );
    }

    public function testEnregistrerAttribueUnIdentifiantEtRelitLaDonnee(): void
    {
        $depot = $this->depot();

        $enregistree = $depot->enregistrer($this->nouvelle(10, 'Tache A', '2026-01-01T09:00:00+00:00'));

        self::assertNotNull($enregistree->id);

        $relue = $depot->parId($enregistree->id);
        self::assertNotNull($relue);
        self::assertSame('Nouvelle tache creee : Tache A', $relue->message);
        self::assertSame(10, $relue->userId);
    }

    public function testParUtilisateurRenvoieLesPlusRecentesDAbord(): void
    {
        $depot = $this->depot();

        $depot->enregistrer($this->nouvelle(10, 'Ancienne', '2026-01-01T09:00:00+00:00'));
        $depot->enregistrer($this->nouvelle(10, 'Recente', '2026-01-03T09:00:00+00:00'));
        $depot->enregistrer($this->nouvelle(99, 'Autre utilisateur', '2026-01-05T09:00:00+00:00'));

        $liste = $depot->parUtilisateur(10);

        self::assertCount(2, $liste);
        self::assertSame('Nouvelle tache creee : Recente', $liste[0]->message);
        self::assertSame('Nouvelle tache creee : Ancienne', $liste[1]->message);
    }

    public function testParUtilisateurFiltreParStatut(): void
    {
        $depot = $this->depot();

        $a = $depot->enregistrer($this->nouvelle(10, 'A', '2026-01-01T09:00:00+00:00'));
        $depot->enregistrer($this->nouvelle(10, 'B', '2026-01-02T09:00:00+00:00'));

        $depot->mettreAJour($a->marquerCommeLue(new DateTimeImmutable('2026-01-02T10:00:00+00:00')));

        $nonLues = $depot->parUtilisateur(10, NotificationStatus::Unread);
        $lues = $depot->parUtilisateur(10, NotificationStatus::Read);

        self::assertCount(1, $nonLues);
        self::assertSame('Nouvelle tache creee : B', $nonLues[0]->message);
        self::assertCount(1, $lues);
        self::assertSame('Nouvelle tache creee : A', $lues[0]->message);
    }

    public function testMettreAJourPersisteLeStatutLu(): void
    {
        $depot = $this->depot();

        $notification = $depot->enregistrer($this->nouvelle(10, 'A', '2026-01-01T09:00:00+00:00'));
        $depot->mettreAJour($notification->marquerCommeLue(new DateTimeImmutable('2026-01-02T10:00:00+00:00')));

        $relue = $depot->parId($notification->id);
        self::assertNotNull($relue);
        self::assertTrue($relue->estLue());
        self::assertNotNull($relue->readAt);
    }

    public function testCompterParStatutGarantitLesDeuxCles(): void
    {
        $depot = $this->depot();

        $a = $depot->enregistrer($this->nouvelle(10, 'A', '2026-01-01T09:00:00+00:00'));
        $depot->enregistrer($this->nouvelle(10, 'B', '2026-01-02T09:00:00+00:00'));
        $depot->enregistrer($this->nouvelle(10, 'C', '2026-01-03T09:00:00+00:00'));
        $depot->mettreAJour($a->marquerCommeLue(new DateTimeImmutable('2026-01-04T10:00:00+00:00')));

        $comptes = $depot->compterParStatut(10);

        self::assertSame(2, $comptes[NotificationStatus::Unread->value]);
        self::assertSame(1, $comptes[NotificationStatus::Read->value]);
    }

    public function testParIdRenvoieNullSiAbsente(): void
    {
        self::assertNull($this->depot()->parId(123));
    }
}
