<?php

declare(strict_types=1);

namespace TaskForge\Notifications\Repository;

use DateTimeImmutable;
use DateTimeZone;
use PDO;
use TaskForge\Notifications\Domain\EventType;
use TaskForge\Notifications\Domain\Notification;
use TaskForge\Notifications\Domain\NotificationStatus;

/**
 * Implementation PDO du depot de notifications.
 *
 * Toutes les requetes sont preparees et parametrees : aucune donnee
 * entrante n'est concatenee dans le SQL, ce qui neutralise les injections
 * (OWASP A03). Compatible MySQL (production) et SQLite (tests).
 */
final class PdoNotificationRepository implements NotificationRepositoryInterface
{
    private const FORMAT_DATE = 'Y-m-d H:i:s';

    public function __construct(private readonly PDO $pdo)
    {
    }

    public function enregistrer(Notification $notification): Notification
    {
        $sql = 'INSERT INTO notifications
                    (user_id, event_type, task_id, message, status, created_at, read_at)
                VALUES
                    (:user_id, :event_type, :task_id, :message, :status, :created_at, :read_at)';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':user_id' => $notification->userId,
            ':event_type' => $notification->eventType->value,
            ':task_id' => $notification->taskId,
            ':message' => $notification->message,
            ':status' => $notification->status->value,
            ':created_at' => $this->formaterUtc($notification->createdAt),
            ':read_at' => $notification->readAt !== null ? $this->formaterUtc($notification->readAt) : null,
        ]);

        $id = (int) $this->pdo->lastInsertId();

        return new Notification(
            id: $id,
            userId: $notification->userId,
            eventType: $notification->eventType,
            taskId: $notification->taskId,
            message: $notification->message,
            status: $notification->status,
            createdAt: $notification->createdAt,
            readAt: $notification->readAt,
        );
    }

    public function parId(int $id): ?Notification
    {
        $stmt = $this->pdo->prepare('SELECT * FROM notifications WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $ligne = $stmt->fetch(PDO::FETCH_ASSOC);

        return $ligne === false ? null : $this->hydrater($ligne);
    }

    public function parUtilisateur(int $userId, ?NotificationStatus $statut = null): array
    {
        $sql = 'SELECT * FROM notifications WHERE user_id = :user_id';
        $params = [':user_id' => $userId];

        if ($statut !== null) {
            $sql .= ' AND status = :status';
            $params[':status'] = $statut->value;
        }

        $sql .= ' ORDER BY created_at DESC, id DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return array_map(
            fn (array $ligne): Notification => $this->hydrater($ligne),
            $stmt->fetchAll(PDO::FETCH_ASSOC),
        );
    }

    public function mettreAJour(Notification $notification): void
    {
        $sql = 'UPDATE notifications
                   SET status = :status, read_at = :read_at
                 WHERE id = :id';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':status' => $notification->status->value,
            ':read_at' => $notification->readAt !== null ? $this->formaterUtc($notification->readAt) : null,
            ':id' => $notification->id,
        ]);
    }

    public function compterParStatut(int $userId): array
    {
        $sql = 'SELECT status, COUNT(*) AS total
                  FROM notifications
                 WHERE user_id = :user_id
              GROUP BY status';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':user_id' => $userId]);

        // On garantit la presence de chaque statut, meme a zero,
        // pour un rapport stable cote client.
        $resultat = [
            NotificationStatus::Unread->value => 0,
            NotificationStatus::Read->value => 0,
        ];

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $ligne) {
            $resultat[(string) $ligne['status']] = (int) $ligne['total'];
        }

        return $resultat;
    }

    /**
     * Reconstruit une entite Notification depuis une ligne SQL.
     *
     * @param array<string, mixed> $ligne
     */
    private function hydrater(array $ligne): Notification
    {
        $readAt = $ligne['read_at'] !== null
            ? $this->analyserUtc((string) $ligne['read_at'])
            : null;

        return new Notification(
            id: (int) $ligne['id'],
            userId: (int) $ligne['user_id'],
            eventType: EventType::from((string) $ligne['event_type']),
            taskId: (int) $ligne['task_id'],
            message: (string) $ligne['message'],
            status: NotificationStatus::from((string) $ligne['status']),
            createdAt: $this->analyserUtc((string) $ligne['created_at']),
            readAt: $readAt,
        );
    }

    /**
     * Formate une date en UTC pour un stockage independant du fuseau
     * horaire du serveur. Garantit un comportement identique en test
     * (SQLite) et en production (MySQL).
     */
    private function formaterUtc(DateTimeImmutable $date): string
    {
        return $date->setTimezone(new DateTimeZone('UTC'))->format(self::FORMAT_DATE);
    }

    /**
     * Relit une date stockee en la rattachant explicitement a UTC.
     */
    private function analyserUtc(string $valeur): DateTimeImmutable
    {
        return new DateTimeImmutable($valeur, new DateTimeZone('UTC'));
    }
}
