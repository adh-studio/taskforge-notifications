<?php

declare(strict_types=1);

namespace TaskForge\Notifications\Tests\Service;

use TaskForge\Notifications\Domain\Exception\NotFoundException;
use TaskForge\Notifications\Domain\Exception\ValidationException;
use TaskForge\Notifications\Domain\NotificationStatus;
use TaskForge\Notifications\Repository\PdoNotificationRepository;
use TaskForge\Notifications\Service\NotificationService;
use TaskForge\Notifications\Tests\DatabaseTestCase;
use TaskForge\Notifications\Tests\Support\FrozenClock;

final class NotificationServiceTest extends DatabaseTestCase
{
    private NotificationService $service;
    private FrozenClock $clock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clock = FrozenClock::a('2026-01-10T12:00:00+00:00');
        $this->service = new NotificationService(
            new PdoNotificationRepository($this->pdo),
            $this->clock,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function chargeValide(): array
    {
        return [
            'user_id' => 10,
            'task_id' => 55,
            'event_type' => 'task.created',
            'task_title' => 'Rediger la specification',
        ];
    }

    public function testEnregistrerEvenementCreeUneNotificationNonLue(): void
    {
        $notification = $this->service->enregistrerEvenement($this->chargeValide());

        self::assertNotNull($notification->id);
        self::assertSame(10, $notification->userId);
        self::assertSame('Nouvelle tache creee : Rediger la specification', $notification->message);
        self::assertSame(NotificationStatus::Unread, $notification->status);
        self::assertSame(
            '2026-01-10T12:00:00+00:00',
            $notification->createdAt->format(DATE_ATOM),
        );
    }

    public function testEnregistrerEvenementRefuseUnUserIdManquant(): void
    {
        $charge = $this->chargeValide();
        unset($charge['user_id']);

        $this->expectException(ValidationException::class);
        $this->service->enregistrerEvenement($charge);
    }

    public function testEnregistrerEvenementRefuseUnTypeInconnu(): void
    {
        $charge = $this->chargeValide();
        $charge['event_type'] = 'task.exploded';

        $this->expectException(ValidationException::class);
        $this->service->enregistrerEvenement($charge);
    }

    public function testEnregistrerEvenementRefuseUnTitreVide(): void
    {
        $charge = $this->chargeValide();
        $charge['task_title'] = '   ';

        $this->expectException(ValidationException::class);
        $this->service->enregistrerEvenement($charge);
    }

    public function testListerPourUtilisateurFiltreParStatut(): void
    {
        $this->service->enregistrerEvenement($this->chargeValide());
        $deuxieme = $this->service->enregistrerEvenement($this->chargeValide());
        $this->service->marquerCommeLue($deuxieme->id, 10);

        $nonLues = $this->service->listerPourUtilisateur(10, 'unread');
        $lues = $this->service->listerPourUtilisateur(10, 'read');

        self::assertCount(1, $nonLues);
        self::assertCount(1, $lues);
    }

    public function testListerPourUtilisateurRejetteUnStatutInvalide(): void
    {
        $this->expectException(ValidationException::class);
        $this->service->listerPourUtilisateur(10, 'archive');
    }

    public function testMarquerCommeLueEstIdempotent(): void
    {
        $notification = $this->service->enregistrerEvenement($this->chargeValide());

        $this->clock->avancerA('2026-01-11T09:00:00+00:00');
        $premierePassage = $this->service->marquerCommeLue($notification->id, 10);

        $this->clock->avancerA('2026-01-12T09:00:00+00:00');
        $secondPassage = $this->service->marquerCommeLue($notification->id, 10);

        self::assertTrue($premierePassage->estLue());
        self::assertTrue($secondPassage->estLue());
        // Le second passage ne doit pas ecraser la date de lecture initiale.
        self::assertEquals($premierePassage->readAt, $secondPassage->readAt);
    }

    public function testMarquerCommeLueRefuseUneNotificationDUnAutreUtilisateur(): void
    {
        $notification = $this->service->enregistrerEvenement($this->chargeValide());

        // L'utilisateur 999 ne possede pas cette notification : traitee
        // comme introuvable pour ne pas divulguer son existence.
        $this->expectException(NotFoundException::class);
        $this->service->marquerCommeLue($notification->id, 999);
    }

    public function testMarquerCommeLueLeveNotFoundSiAbsente(): void
    {
        $this->expectException(NotFoundException::class);
        $this->service->marquerCommeLue(4242, 10);
    }
}
