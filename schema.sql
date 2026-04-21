-- =============================================================
--  Voting System — Database Schema
--  Engine: InnoDB (ACID), charset: utf8mb4
-- =============================================================

CREATE DATABASE IF NOT EXISTS voting_system
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE voting_system;

-- ---------------------------------------------------------
-- Users
-- ---------------------------------------------------------
CREATE TABLE users (
    id           INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    username     VARCHAR(50)     NOT NULL,
    email        VARCHAR(100)    NOT NULL,
    password_hash VARCHAR(255)   NOT NULL,
    created_at   TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_username (username),
    UNIQUE KEY uq_email    (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- Votes
--   • UNIQUE(user_id)   → one-vote-per-user at DB level
--   • UNIQUE(hash)      → no duplicate hash entries
--   • FK → users        → referential integrity
--   • INDEX(previous_hash) → fast chain traversal
-- ---------------------------------------------------------
CREATE TABLE votes (
    id            INT UNSIGNED   NOT NULL AUTO_INCREMENT,
    user_id       INT UNSIGNED   NOT NULL,
    choice        VARCHAR(100)   NOT NULL,
    hash          CHAR(64)       NOT NULL,   -- SHA-256 hex
    previous_hash CHAR(64)       NOT NULL,   -- SHA-256 hex of predecessor
    voted_at      TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_user_id       (user_id),  -- one vote per user
    UNIQUE KEY uq_hash          (hash),
    INDEX  idx_previous_hash    (previous_hash),
    INDEX  idx_voted_at         (voted_at),

    CONSTRAINT fk_vote_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- Audit Logs
-- ---------------------------------------------------------
CREATE TABLE audit_logs (
    id         INT UNSIGNED   NOT NULL AUTO_INCREMENT,
    user_id    INT UNSIGNED,                   -- NULL for anonymous actions
    action     VARCHAR(50)    NOT NULL,        -- REGISTER, LOGIN, VOTE, DUPLICATE_VOTE, etc.
    detail     TEXT,
    ip_address VARCHAR(45),                   -- supports IPv6
    created_at TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    INDEX idx_action    (action),
    INDEX idx_user_id   (user_id),
    INDEX idx_created_at(created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
