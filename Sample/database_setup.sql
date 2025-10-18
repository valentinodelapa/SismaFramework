-- =====================================================
-- Script SQL per Setup Database Sample SismaFramework
-- =====================================================
-- Questo script crea le tabelle necessarie e le popola con dati di esempio
-- per testare tutte le funzionalità del framework Sample

-- Elimina le tabelle se esistono (per permettere re-run dello script)
DROP TABLE IF EXISTS `sample_dependent_entity`;
DROP TABLE IF EXISTS `sample_base_entity`;
DROP TABLE IF EXISTS `sample_referenced_entity`;
DROP TABLE IF EXISTS `sample_self_referenced_entity`;
DROP TABLE IF EXISTS `sample_multiple_dependent_entity`;

-- =====================================================
-- Tabella: sample_referenced_entity (Autori)
-- =====================================================
-- Questa tabella rappresenta gli autori degli articoli
-- È una ReferencedEntity, quindi può essere referenziata da altre entità

CREATE TABLE `sample_referenced_entity` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `full_name` VARCHAR(255) NOT NULL COMMENT 'Nome completo autore',
  `email` TEXT NOT NULL COMMENT 'Email crittografata',
  `email_iv` VARCHAR(32) NOT NULL COMMENT 'Initialization Vector per crittografia email',
  `bio` TEXT NULL COMMENT 'Biografia autore',
  `verified` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Flag autore verificato',
  PRIMARY KEY (`id`),
  INDEX `idx_full_name` (`full_name`),
  INDEX `idx_verified` (`verified`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Tabella autori - ReferencedEntity per articoli';

-- =====================================================
-- Tabella: sample_base_entity (Articoli base)
-- =====================================================
-- Questa tabella rappresenta articoli senza dipendenze
-- Mostra tutti i tipi di dati supportati dal framework

CREATE TABLE `sample_base_entity` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL COMMENT 'Titolo articolo',
  `content` TEXT NULL COMMENT 'Contenuto articolo',
  `rating` DECIMAL(3,2) NOT NULL DEFAULT 0.00 COMMENT 'Rating articolo (0.00-5.00)',
  `featured` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Flag articolo in evidenza',
  `published_at` DATETIME NOT NULL COMMENT 'Data pubblicazione',
  `status` CHAR(1) NOT NULL DEFAULT 'D' COMMENT 'Stato: D=Draft, P=Published, A=Archived',
  `internal_notes` TEXT NULL COMMENT 'Note interne crittografate',
  `internal_notes_iv` VARCHAR(32) NULL COMMENT 'Initialization Vector per crittografia note',
  PRIMARY KEY (`id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_featured` (`featured`),
  INDEX `idx_published_at` (`published_at`),
  FULLTEXT INDEX `ft_title_content` (`title`, `content`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Tabella articoli base - BaseEntity senza dipendenze';

-- =====================================================
-- Tabella: sample_dependent_entity (Articoli con autore)
-- =====================================================
-- Questa tabella rappresenta articoli con relazione Many-to-One verso autori
-- Dimostra Lazy Loading e gestione foreign key

CREATE TABLE `sample_dependent_entity` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL COMMENT 'Titolo articolo',
  `content` TEXT NOT NULL COMMENT 'Contenuto articolo',
  `created_at` DATETIME NOT NULL COMMENT 'Data creazione',
  `status` CHAR(1) NOT NULL DEFAULT 'D' COMMENT 'Stato: D=Draft, P=Published, A=Archived',
  `views` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Numero visualizzazioni',
  `sample_referenced_entity_id` INT UNSIGNED NOT NULL COMMENT 'ID Autore (FK)',
  PRIMARY KEY (`id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_views` (`views`),
  INDEX `idx_created_at` (`created_at`),
  INDEX `idx_author` (`sample_referenced_entity_id`),
  FULLTEXT INDEX `ft_title_content` (`title`, `content`),
  CONSTRAINT `fk_dependent_referenced`
    FOREIGN KEY (`sample_referenced_entity_id`)
    REFERENCES `sample_referenced_entity` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Tabella articoli con autore - DependentEntity con relazione Many-to-One';

-- =====================================================
-- Tabella: sample_self_referenced_entity (Categorie ad albero)
-- =====================================================
-- Questa tabella rappresenta una struttura gerarchica (albero)
-- Dimostra self-reference per parent-child

CREATE TABLE `sample_self_referenced_entity` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL COMMENT 'Nome categoria',
  `parent_sample_self_referenced_entity_id` INT UNSIGNED NULL COMMENT 'ID Categoria padre (FK self-reference)',
  PRIMARY KEY (`id`),
  INDEX `idx_parent` (`parent_sample_self_referenced_entity_id`),
  CONSTRAINT `fk_self_referenced_parent`
    FOREIGN KEY (`parent_sample_self_referenced_entity_id`)
    REFERENCES `sample_self_referenced_entity` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Tabella categorie gerarchiche - SelfReferencedEntity';

-- =====================================================
-- Tabella: sample_multiple_dependent_entity (Multiple FK)
-- =====================================================
-- Questa tabella dimostra multiple foreign key verso la stessa entità

CREATE TABLE `sample_multiple_dependent_entity` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL COMMENT 'Nome',
  `sample_referenced_entity_one_id` INT UNSIGNED NOT NULL COMMENT 'Prima FK verso autore',
  `sample_referenced_entity_two_id` INT UNSIGNED NOT NULL COMMENT 'Seconda FK verso autore',
  PRIMARY KEY (`id`),
  INDEX `idx_ref_one` (`sample_referenced_entity_one_id`),
  INDEX `idx_ref_two` (`sample_referenced_entity_two_id`),
  CONSTRAINT `fk_multiple_ref_one`
    FOREIGN KEY (`sample_referenced_entity_one_id`)
    REFERENCES `sample_referenced_entity` (`id`)
    ON DELETE RESTRICT
    ON UPDATE CASCADE,
  CONSTRAINT `fk_multiple_ref_two`
    FOREIGN KEY (`sample_referenced_entity_two_id`)
    REFERENCES `sample_referenced_entity` (`id`)
    ON DELETE RESTRICT
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Tabella con multiple foreign key - Esempio FK multiple';

-- =====================================================
-- POPOLAMENTO DATI DI ESEMPIO
-- =====================================================

-- Inserimento Autori (sample_referenced_entity)
-- NOTA: email e internal_notes sono crittografate, quindi inseriamo placeholder
-- Il framework le crittograferà automaticamente quando inserite tramite ORM

INSERT INTO `sample_referenced_entity` (`full_name`, `email`, `email_iv`, `bio`, `verified`) VALUES
('Mario Rossi', 'ENCRYPTED_DATA_PLACEHOLDER', 'iv_placeholder_001', 'Sviluppatore PHP senior con 10+ anni di esperienza in framework MVC e architetture enterprise.', 1),
('Laura Bianchi', 'ENCRYPTED_DATA_PLACEHOLDER', 'iv_placeholder_002', 'Tech writer e content creator specializzata in tutorial di programmazione e best practices.', 1),
('Giuseppe Verdi', 'ENCRYPTED_DATA_PLACEHOLDER', 'iv_placeholder_003', 'Full-stack developer appassionato di ORM e database optimization.', 0),
('Anna Neri', 'ENCRYPTED_DATA_PLACEHOLDER', 'iv_placeholder_004', NULL, 0),
('Paolo Ferrari', 'ENCRYPTED_DATA_PLACEHOLDER', 'iv_placeholder_005', 'DevOps engineer con focus su CI/CD e cloud infrastructure.', 1);

-- Inserimento Articoli Base (sample_base_entity)
INSERT INTO `sample_base_entity` (`title`, `content`, `rating`, `featured`, `published_at`, `status`, `internal_notes`, `internal_notes_iv`) VALUES
('Introduzione a SismaFramework', 'SismaFramework è un framework PHP moderno che sfrutta le potenzialità di PHP 8.1+ per creare applicazioni web robuste e manutenibili. In questo articolo esploreremo le caratteristiche principali.', 4.5, 1, '2025-01-15 10:00:00', 'P', NULL, NULL),
('ORM e Data Mapper Pattern', 'Il pattern Data Mapper separa la logica di business dalla persistenza dei dati, permettendo un codice più pulito e testabile. Scopriamo come implementarlo correttamente.', 4.8, 1, '2025-01-16 14:30:00', 'P', 'ENCRYPTED_NOTES_PLACEHOLDER', 'iv_notes_001'),
('Lazy Loading nelle Relazioni', 'Il lazy loading è una tecnica per ottimizzare le performance caricando i dati solo quando necessario. Vediamo come SismaFramework lo implementa automaticamente.', 4.2, 0, '2025-01-17 09:15:00', 'P', NULL, NULL),
('BackedEnum in PHP 8.1', 'Gli enum tipizzati di PHP 8.1 permettono di rappresentare valori fissi in modo type-safe. Un must-have per ogni applicazione moderna.', 4.6, 1, '2025-01-18 16:45:00', 'P', NULL, NULL),
('Query Builder Avanzato', 'Costruire query complesse in modo programmatico e sicuro usando il Query Builder del framework.', 3.9, 0, '2025-01-19 11:20:00', 'P', NULL, NULL),
('Crittografia a Livello di Entity', 'Come proteggere dati sensibili crittografandoli automaticamente a livello di entity properties.', 4.7, 1, '2025-01-20 13:00:00', 'P', 'ENCRYPTED_NOTES_PLACEHOLDER', 'iv_notes_002'),
('Bozza: Dependency Injection', 'Articolo in lavorazione sulla dependency injection automatica nel framework...', 0.0, 0, '2025-01-21 08:00:00', 'D', NULL, NULL),
('Archiviato: Old Tutorial', 'Vecchio tutorial non più attuale, mantenuto per riferimento storico.', 3.5, 0, '2024-12-01 10:00:00', 'A', NULL, NULL),
('Routing Avanzato', 'Gestione del routing con parametri tipizzati, entity injection ed enum binding.', 4.4, 0, '2025-01-22 15:30:00', 'P', NULL, NULL),
('Testing con PHPUnit', 'Strategie per testare applicazioni costruite con SismaFramework usando PHPUnit e mock objects.', 4.1, 0, '2025-01-23 12:00:00', 'P', NULL, NULL);

-- Inserimento Articoli con Autore (sample_dependent_entity)
INSERT INTO `sample_dependent_entity` (`title`, `content`, `created_at`, `status`, `views`, `sample_referenced_entity_id`) VALUES
('Il mio primo articolo', 'Contenuto del primo articolo scritto da Mario Rossi. Parla di architettura software e best practices.', '2025-01-10 10:00:00', 'P', 150, 1),
('Pattern MVC Spiegato', 'Una guida completa al pattern Model-View-Controller scritta da Laura Bianchi.', '2025-01-11 14:00:00', 'P', 320, 2),
('Database Optimization Tips', 'Giuseppe Verdi condivide i suoi trucchi per ottimizzare query e indici database.', '2025-01-12 09:30:00', 'P', 280, 3),
('Sicurezza nelle Web App', 'Mario Rossi spiega come proteggere le applicazioni web da vulnerabilità comuni.', '2025-01-13 16:00:00', 'P', 510, 1),
('Tutorial: Costruire un Blog', 'Laura Bianchi guida passo-passo nella creazione di un blog con SismaFramework.', '2025-01-14 11:00:00', 'P', 890, 2),
('Bozza: Performance Tuning', 'Articolo in bozza di Giuseppe Verdi su come migliorare le performance.', '2025-01-15 08:00:00', 'D', 5, 3),
('CI/CD per PHP', 'Paolo Ferrari presenta una pipeline completa per continuous integration e deployment.', '2025-01-16 13:30:00', 'P', 420, 5),
('Docker per Sviluppatori', 'Come containerizzare applicazioni PHP usando Docker e Docker Compose.', '2025-01-17 10:15:00', 'P', 680, 5),
('Note di Anna', 'Un breve articolo di test scritto da Anna Neri.', '2025-01-18 15:00:00', 'D', 2, 4),
('Migrazione Legacy Code', 'Mario Rossi condivide strategie per migrare vecchio codice a framework moderni.', '2025-01-19 12:45:00', 'P', 340, 1);

-- Inserimento Categorie Gerarchiche (sample_self_referenced_entity)
INSERT INTO `sample_self_referenced_entity` (`name`, `parent_sample_self_referenced_entity_id`) VALUES
('Programming', NULL),
('PHP', 1),
('JavaScript', 1),
('Frameworks', 2),
('Laravel', 4),
('Symfony', 4),
('SismaFramework', 4),
('Frontend', 3),
('React', 8),
('Vue.js', 8),
('Database', NULL),
('MySQL', 11),
('PostgreSQL', 11),
('DevOps', NULL),
('Docker', 14),
('Kubernetes', 14);

-- Inserimento Multiple Dependencies (sample_multiple_dependent_entity)
INSERT INTO `sample_multiple_dependent_entity` (`name`, `sample_referenced_entity_one_id`, `sample_referenced_entity_two_id`) VALUES
('Collaborazione Mario-Laura', 1, 2),
('Review Giuseppe-Paolo', 3, 5),
('Pair Programming Mario-Giuseppe', 1, 3);

-- =====================================================
-- VERIFICA DATI INSERITI
-- =====================================================

SELECT
    'sample_referenced_entity' as tabella,
    COUNT(*) as record_inseriti
FROM sample_referenced_entity
UNION ALL
SELECT
    'sample_base_entity',
    COUNT(*)
FROM sample_base_entity
UNION ALL
SELECT
    'sample_dependent_entity',
    COUNT(*)
FROM sample_dependent_entity
UNION ALL
SELECT
    'sample_self_referenced_entity',
    COUNT(*)
FROM sample_self_referenced_entity
UNION ALL
SELECT
    'sample_multiple_dependent_entity',
    COUNT(*)
FROM sample_multiple_dependent_entity;

-- =====================================================
-- FINE SCRIPT
-- =====================================================
-- Database popolato con successo!
-- Puoi ora testare tutte le funzionalità del Sample.
--
-- NOTE IMPORTANTI:
-- - Email e internal_notes contengono placeholder perché vengono
--   crittografate automaticamente dal framework quando inserite tramite ORM
-- - Per avere dati reali crittografati, usa il framework per inserire i dati
-- - Gli indici FULLTEXT richiedono tabelle InnoDB con MySQL 5.6+
