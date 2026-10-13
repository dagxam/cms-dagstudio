CREATE TABLE IF NOT EXISTS users (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 email VARCHAR(190) NOT NULL UNIQUE,
 name VARCHAR(120) NOT NULL,
 password_hash VARCHAR(255) NOT NULL,
 role ENUM('admin','editor') NOT NULL DEFAULT 'editor',
 permissions TEXT NULL,
 active TINYINT(1) NOT NULL DEFAULT 1,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS settings (
 name VARCHAR(100) PRIMARY KEY,
 value TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS content (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 kind ENUM('page','news','service','product') NOT NULL,
 title VARCHAR(250) NOT NULL,
 slug VARCHAR(190) NOT NULL UNIQUE,
 summary TEXT NULL,
 body MEDIUMTEXT NULL,
 price DECIMAL(12,2) NULL,
 status ENUM('draft','published') NOT NULL DEFAULT 'draft',
 created_by BIGINT UNSIGNED NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 KEY idx_kind_status (kind,status,created_at),
 FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS messages (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(120) NOT NULL,
 email VARCHAR(190) NOT NULL,
 body TEXT NOT NULL,
 is_read TINYINT(1) NOT NULL DEFAULT 0,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS login_attempts (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 fingerprint CHAR(64) NOT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY idx_fingerprint (fingerprint,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_log (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id BIGINT UNSIGNED NULL,
 action VARCHAR(80) NOT NULL,
 target VARCHAR(190) NOT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cms_media (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 category ENUM('photo','document','video') NOT NULL,
 title VARCHAR(190) NOT NULL,
 description TEXT NULL,
 alt_text VARCHAR(300) NOT NULL DEFAULT '',
 age_rating ENUM('0+','6+','12+','16+','18+') NOT NULL DEFAULT '0+',
 status ENUM('draft','published') NOT NULL DEFAULT 'draft',
 filename VARCHAR(100) NOT NULL,
 original_name VARCHAR(190) NOT NULL,
 mime VARCHAR(150) NOT NULL,
 size_bytes BIGINT UNSIGNED NOT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 KEY idx_media_status(status,category,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Доступ к внешнему видео ограничен только разрешёнными провайдерами VK и Rutube.
CREATE TABLE IF NOT EXISTS cms_video_links (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 title VARCHAR(190) NOT NULL,
 description TEXT NULL,
 provider ENUM('vk','rutube') NOT NULL,
 embed_url VARCHAR(500) NOT NULL,
 original_url VARCHAR(500) NOT NULL,
 age_rating ENUM('0+','6+','12+','16+','18+') NOT NULL DEFAULT '0+',
 status ENUM('draft','published') NOT NULL DEFAULT 'draft',
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 KEY idx_video_status(status,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Обложки страниц, новостей, услуг и товаров. Таблица также создаётся при первом обращении.
CREATE TABLE IF NOT EXISTS content_images (
 content_id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
 filename VARCHAR(64) NOT NULL,
 alt_text VARCHAR(300) NOT NULL DEFAULT '',
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 CONSTRAINT fk_content_cover FOREIGN KEY (content_id) REFERENCES content(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Подтверждение отдельного согласия при отправке обращения
CREATE TABLE IF NOT EXISTS cms_message_consents (
 message_id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
 consent_version VARCHAR(50) NOT NULL,
 consent_time TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 consent_text_hash CHAR(64) NOT NULL,
 FOREIGN KEY(message_id) REFERENCES messages(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Магазин: категории и параметры товаров.
CREATE TABLE IF NOT EXISTS cms_shop_categories (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(120) NOT NULL,
 slug VARCHAR(120) NOT NULL UNIQUE,
 sort_order INT NOT NULL DEFAULT 100,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cms_shop_product_meta (
 product_id BIGINT UNSIGNED PRIMARY KEY,
 category_id BIGINT UNSIGNED NULL,
 featured TINYINT(1) NOT NULL DEFAULT 0,
 sort_order INT NOT NULL DEFAULT 100,
 FOREIGN KEY (product_id) REFERENCES content(id) ON DELETE CASCADE,
 FOREIGN KEY (category_id) REFERENCES cms_shop_categories(id) ON DELETE SET NULL,
 KEY idx_shop_featured(featured,sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Заказ сохраняет снимок цены и товара, банковские реквизиты покупателя не собираются.
CREATE TABLE IF NOT EXISTS cms_shop_orders (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 public_number CHAR(16) NOT NULL UNIQUE,
 product_id BIGINT UNSIGNED NULL,
 product_title VARCHAR(250) NOT NULL,
 unit_price DECIMAL(12,2) NOT NULL,
 quantity SMALLINT UNSIGNED NOT NULL,
 total_price DECIMAL(14,2) NOT NULL,
 customer_name VARCHAR(120) NOT NULL,
 customer_phone VARCHAR(40) NOT NULL,
 customer_email VARCHAR(190) NOT NULL,
 customer_address VARCHAR(500) NOT NULL DEFAULT '',
 customer_note TEXT NULL,
 payment_method VARCHAR(30) NOT NULL DEFAULT 'standard',
 payment_status VARCHAR(20) NOT NULL DEFAULT 'unpaid',
 order_status VARCHAR(20) NOT NULL DEFAULT 'new',
 consent_version VARCHAR(50) NOT NULL,
 consent_text_hash CHAR(64) NOT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 KEY idx_orders_status(order_status,created_at),
 FOREIGN KEY(product_id) REFERENCES content(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
