<?php
declare(strict_types=1);
function cms_media_formats(): array {
 return [
 'photo'=>['jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png','webp'=>'image/webp'],
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

function cms_media_upload_validation(array $file,string $category): array {
    $formats=cms_media_formats()[$category]??null;
    if(!$formats)throw new RuntimeException('Неверная категория файла.');
    if(($file['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK || !is_uploaded_file((string)($file['tmp_name']??'')))
        throw new RuntimeException('Ошибка загрузки. Проверьте ограничения размера файлов на хостинге.');
    $size=(int)($file['size']??0);
    $max=['photo'=>8,'document'=>20,'video'=>50][$category]*1024*1024;
    if($size<1||$size>$max)throw new RuntimeException('Размер превышает '.(int)($max/1048576).' МБ.');
    $original=mb_substr((string)($file['name']??'material'),0,190);
    $ext=strtolower(pathinfo($original,PATHINFO_EXTENSION));
    if(!isset($formats[$ext]))throw new RuntimeException('Формат файла не разрешён.');
    $mime=(new finfo(FILEINFO_MIME_TYPE))->file((string)$file['tmp_name']);
    $expected=$formats[$ext];
    if(in_array($ext,['docx','pptx','xlsx'],true)){
        if(!in_array($mime,['application/zip','application/octet-stream',$expected],true))
            throw new RuntimeException('Некорректный Office-документ.');
        $signature=file_get_contents((string)$file['tmp_name'],false,null,0,2);
        if($signature!=='PK')throw new RuntimeException('Файл Office не является ZIP-пакетом.');
    }elseif($mime!==$expected&&!($ext==='mp4'&&$mime==='application/octet-stream')){
        throw new RuntimeException('Тип содержимого файла не совпадает с расширением.');
    }
    if($category==='photo'){
        $dimensions=@getimagesize((string)$file['tmp_name']);
        if(!$dimensions||($dimensions[0]??0)<1||($dimensions[1]??0)<1||
            $dimensions[0]>6500||$dimensions[1]>6500)
            throw new RuntimeException('Недопустимые размеры изображения.');
    }
    if($category==='video'){
        $header=file_get_contents((string)$file['tmp_name'],false,null,0,16);
        if(!is_string($header)||($ext==='mp4'&&substr($header,4,4)!=='ftyp')||
            ($ext==='webm'&&bin2hex(substr($header,0,4))!=='1a45dfa3'))
            throw new RuntimeException('Неверная структура видео.');
    }
    if($ext==='jpeg')$ext='jpg';
    return [$expected,$ext,$size,$original];
}
