<?php
declare(strict_types=1);

/** Изображения обложек материалов: отдельный защищённый каталог и таблица. */
function cms_content_images_table(): void {
    static $ready=false;
    if($ready)return;
    database()->exec("CREATE TABLE IF NOT EXISTS content_images (
        content_id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
        filename VARCHAR(64) NOT NULL,
        alt_text VARCHAR(300) NOT NULL DEFAULT '',
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT fk_content_cover FOREIGN KEY (content_id) REFERENCES content(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $ready=true;
}

function cms_content_image(int $id): ?array {
    if($id<1)return null;
    cms_content_images_table();
    $q=database()->prepare('SELECT content_id,filename,alt_text,updated_at FROM content_images WHERE content_id=?');
    $q->execute([$id]);
    return $q->fetch()?:null;
}
function cms_content_image_map(array $items): array {
    $ids=[];
    foreach($items as $item) {
        $id=(int)($item['id']??0);
        if($id>0)$ids[$id]=$id;
    }
    if(!$ids)return [];
    cms_content_images_table();
    $ids=array_slice(array_values($ids),0,100);
    $q=database()->prepare('SELECT content_id,filename,alt_text,updated_at FROM content_images WHERE content_id IN ('.implode(',',array_fill(0,count($ids),'?')).')');
    $q->execute($ids);
    $result=[];
    foreach($q->fetchAll() as $row)$result[(int)$row['content_id']]=$row;
    return $result;
}
function cms_content_image_url(int $id,array $cover): string {
    return '/content-image.php?id='.$id.'&v='.rawurlencode((string)($cover['updated_at']??''));
}
function cms_content_image_upload(array $file): array {
    if((int)($file['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK||
       !is_uploaded_file((string)($file['tmp_name']??''))) {
        throw new RuntimeException('Не удалось загрузить изображение. Проверьте лимиты PHP.');
    }
    $size=(int)($file['size']??0);
    if($size<1||$size>8*1024*1024)throw new RuntimeException('Обложка должна быть не больше 8 МБ.');
    $info=@getimagesize((string)$file['tmp_name']);
    if(!$info||($info[0]??0)<1||($info[1]??0)<1||$info[0]>6500||$info[1]>6500)
        throw new RuntimeException('Нужно изображение размером до 6500 × 6500 пикселей.');
    $mime=(new finfo(FILEINFO_MIME_TYPE))->file((string)$file['tmp_name']);
    $extensions=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
    if(!is_string($mime)||!isset($extensions[$mime])||
       !isset($info['mime'])||$info['mime']!==$mime)
        throw new RuntimeException('Для обложки используйте JPG, PNG или WebP.');
    $filename=bin2hex(random_bytes(16)).'.'.$extensions[$mime];
    $dir=dirname(__DIR__).'/storage/content-images';
    if(!is_dir($dir)&&!mkdir($dir,0700,true)&&!is_dir($dir))
        throw new RuntimeException('Не удалось создать каталог обложек.');
    if(!is_writable($dir))throw new RuntimeException('Каталог обложек недоступен для записи.');
    if(!move_uploaded_file((string)$file['tmp_name'],$dir.'/'.$filename))
        throw new RuntimeException('Не удалось сохранить файл обложки.');
    @chmod($dir.'/'.$filename,0600);
    return [$filename,$mime];
}
function cms_content_image_file(string $filename): ?string {
    if(!preg_match('/^[a-f0-9]{32}\.(?:jpg|png|webp)$/D',$filename))return null;
    $path=dirname(__DIR__).'/storage/content-images/'.$filename;
    return is_file($path)?$path:null;
}
function cms_content_image_delete_file(?array $cover): void {
    if(!$cover)return;
    $path=cms_content_image_file((string)($cover['filename']??''));
    if($path!==null)@unlink($path);
}
