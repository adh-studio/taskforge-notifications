<?php

declare(strict_types=1);

namespace TaskForge\Notifications\Tests\Domain;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use TaskForge\Notifications\Domain\EventType;
use TaskForge\Notifications\Domain\Notification;
use TaskForge\Notifications\Domain\NotificationStatus;

final class NotificationTest extends TestCase
{
    public function testDepuisEvenementComposeUnMessageEtUnStatutNonLu(): void
    {
        $notification = Notification::depuisEvenement(
            userId: 7,
            eventType: EventType::TaskCompleted,
            taskId: 42,
            taskTitle: 'Livrer la maquette',
            createdAt: new DateTimeImmutable('2026-01-01T10:00:00+00:00'),
        );

        self::assertNull($notification->id);
        self::assertSame(7, $notification->userId);
        self::assertSame(42, $notification->taskId);
        self::assertSame('Tache terminee : Livrer la maquette', $notification->message);
        self::assertSame(NotificationStatus::Unread, $notification->status);
        self::assertFalse($notification->estLue());
    }

    public function testMarquerCommeLueProduitUneNouvelleInstanceImmuable(): void
    {
        $origine = Notification::depuisEvenement(
            userId: 1,
            eventType: EventType::TaskCreated,
            taskId: 1,
            taskTitle: 'Tache',
            createdAt: new DateTimeImmutable('2026-01-01T10:00:00+00:00'),
        );

        $lue = $origine->marquerCommeLue(new DateTimeImmutable('2026-01-02T08:00:00+00:00'));

        // L'originale reste non lue : l'entite est immuable.
        self::assertFalse($origine->estLue());
        self::assertTrue($lue->estLue());
        self::assertSame(NotificationStatus::Read, $lue->status);
        self::assertNotNull($lue->readAt);
    }

    public function testEnTableauExposeLesChampsAttendus(): void
    {
        $notification = new Notification(
            id: 5,
            userId: 3,
            eventType: EventType::TaskCreated,
            taskId: 9,
            message: 'Nouvelle tache creee : Preparer le sprint',
            status: NotificationStatus::Unread,
            createdAt: new DateTimeImmutable('2026-01-01T10:00:00+00:00'),
        );

        $tableau = $notification->enTableau();

        self::assertSame(5, $tableau['id']);
        self::assertSame('task.created', $tableau['event_type']);
        self::assertSame('unread', $tableau['status']);
        self::assertNull($tableau['read_at']);
    }
}
