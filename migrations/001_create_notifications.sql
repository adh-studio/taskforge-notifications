-- Migration 001 : creation de la table des notifications (MySQL 8).
-- Schema versionne : chaque evolution fait l'objet d'un nouveau fichier
-- numerote, applique dans l'ordre.

CREATE TABLE IF NOT EXISTS notifications (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id     BIGINT UNSIGNED NOT NULL,
    event_type  VARCHAR(64)     NOT NULL,
    task_id     BIGINT UNSIGNED NOT NULL,
    message     VARCHAR(512)    NOT NULL,
    status      VARCHAR(16)     NOT NULL DEFAULT 'unread',
    created_at  DATETIME        NOT NULL,
    read_at     DATETIME        NULL,
    PRIMARY KEY (id),
    -- Index destine a la liste par utilisateur, triee par recence.
    KEY idx_user_created (user_id, created_at),
    -- Index destine au rapport agrege par statut.
    KEY idx_user_status (user_id, status)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
