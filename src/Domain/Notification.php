<?php

declare(strict_types=1);

namespace TaskForge\Notifications\Domain;

use DateTimeImmutable;

/**
 * Entite representant une notification destinee a un utilisateur.
 *
 * Objet immuable sur l'identite : seul le statut de lecture evolue,
 * via une transition explicite qui renvoie une nouvelle instance.
 */
final class Notification
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $userId,
        public readonly EventType $eventType,
        public readonly int $taskId,
        public readonly string $message,
        public readonly NotificationStatus $status,
        public readonly DateTimeImmutable $createdAt,
        public readonly ?DateTimeImmutable $readAt = null,
    ) {
    }

    /**
     * Fabrique une notification non lue a partir d'un evenement metier.
     */
    public static function depuisEvenement(
        int $userId,
        EventType $eventType,
        int $taskId,
        string $taskTitle,
        DateTimeImmutable $createdAt,
    ): self {
        $message = sprintf('%s : %s', $eventType->libelle(), $taskTitle);

        return new self(
            id: null,
            userId: $userId,
            eventType: $eventType,
            taskId: $taskId,
            message: $message,
            status: NotificationStatus::Unread,
            createdAt: $createdAt,
        );
    }

    /**
     * Renvoie une copie marquee comme lue a l'instant fourni.
     */
    public function marquerCommeLue(DateTimeImmutable $readAt): self
    {
        return new self(
            id: $this->id,
            userId: $this->userId,
            eventType: $this->eventType,
            taskId: $this->taskId,
            message: $this->message,
            status: NotificationStatus::Read,
            createdAt: $this->createdAt,
            readAt: $readAt,
        );
    }

    public function estLue(): bool
    {
        return $this->status === NotificationStatus::Read;
    }

    /**
     * Representation serialisable pour les reponses JSON de l'API.
     *
     * @return array<string, mixed>
     */
    public function enTableau(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'event_type' => $this->eventType->value,
            'task_id' => $this->taskId,
            'message' => $this->message,
            'status' => $this->status->value,
            'created_at' => $this->createdAt->format(DATE_ATOM),
            'read_at' => $this->readAt?->format(DATE_ATOM),
        ];
    }
}
