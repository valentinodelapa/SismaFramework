-- =====================================================
-- Script SQL per Setup Database Sample SismaFramework
-- =====================================================
-- Questo script crea le tabelle necessarie per il framework Sample.
-- I dati di esempio vengono caricati tramite le Fixtures:
--   php SismaFramework/Console/sisma fixtures

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
-- FINE SCRIPT
-- =====================================================
-- Schema creato. Per popolare il database esegui:
--   php SismaFramework/Console/sisma fixtures
--
-- NOTE: Gli indici FULLTEXT richiedono tabelle InnoDB con MySQL 5.6+
