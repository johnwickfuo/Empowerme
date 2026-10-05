<?php
declare(strict_types=1);

function run_schema_upgrades(PDO $db): void
{
    $db->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS testimonials (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  beneficiary_name VARCHAR(180) NULL,
  story TEXT NULL,
  image_original_name VARCHAR(255) NULL,
  image_stored_name VARCHAR(255) NULL,
  image_mime_type VARCHAR(120) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_testimonials_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sponsors (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(180) NOT NULL,
  logo_original_name VARCHAR(255) NOT NULL,
  logo_stored_name VARCHAR(255) NOT NULL,
  logo_mime_type VARCHAR(120) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_sponsors_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO settings (setting_key, setting_value)
VALUES ('public_email', 'support@empowermeprogram.org')
ON DUPLICATE KEY UPDATE setting_value =
  CASE
    WHEN setting_value IN ('', 'empowermegrantprogram.usa@gmail.com')
      THEN 'support@empowermeprogram.org'
    ELSE setting_value
  END;

INSERT INTO settings (setting_key, setting_value)
VALUES ('smtp_from_email', 'support@empowermeprogram.org')
ON DUPLICATE KEY UPDATE setting_value =
  CASE
    WHEN setting_value IN ('', 'empowermegrantprogram.usa@gmail.com')
      THEN 'support@empowermeprogram.org'
    ELSE setting_value
  END;
SQL);
}
