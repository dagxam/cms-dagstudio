<?php
declare(strict_types=1);
function cms_media_formats(): array {
 return [
 'photo'=>['jpg'=>'image/jpeg','png'=>'image/png','webp'=>'image/webp'],
 'document'=>['pdf'=>'application/pdf','docx'=>'application/vnd.openxmlformats-officedocument.wordprocessingml.document','xlsx'=>'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet','pptx'=>'application/vnd.openxmlformats-officedocument.presentationml.presentation'],
 'video'=>['mp4'=>'video/mp4','webm'=>'video/webm'],
 ];
}
function cms_media_table(): void {
 database()->exec("CREATE TABLE IF NOT EXISTS cms_media (
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
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}
function cms_media_list(bool $onlyPublished=true): array {
 cms_media_table();
 return database()->query("SELECT * FROM cms_media ".($onlyPublished?"WHERE status='published' ":"")."ORDER BY created_at DESC LIMIT 100")->fetchAll();
}
function cms_media_get(int $id,bool $onlyPublished=true): ?array {
 cms_media_table();
 $q=database()->prepare('SELECT * FROM cms_media WHERE id=?'.($onlyPublished?" AND status='published'":''));
 $q->execute([$id]);
 return $q->fetch()?:null;
}
function cms_media_age_valid(string $value): bool {return in_array($value,['0+','6+','12+','16+','18+'],true);}
