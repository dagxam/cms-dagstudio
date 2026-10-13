<?php
declare(strict_types=1);

/** Изолированные поля руководителя: настройки оформления не изменяют эти данные. */
function government_leader_values(array $input, array $existing): array {
    $limits=['leader_title'=>120,'leader_name'=>160,'leader_initials'=>100,'leader_description'=>1000];
    foreach($limits as $field=>$limit){
        $value=$input[$field]??null;
        if(!is_string($value)||mb_strlen($value)>$limit||
           preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u',$value))
            throw new RuntimeException('Проверьте поле руководителя: '.$field.'.');
        $existing[$field]=trim($value);
    }
    $existing['show_leader']=isset($input['show_leader']) && $input['show_leader']==='1'?'1':'0';
    return $existing;
}

/** Файл подготавливается строго из формы загрузки; никакие URL пользователя не принимаются. */
function government_leader_uploaded_photo(?array $file): ?string {
    if($file===null || (int)($file['error']??UPLOAD_ERR_NO_FILE)===UPLOAD_ERR_NO_FILE)return null;
    if((int)($file['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK||
       !is_string($file['tmp_name']??null) ||
       !is_uploaded_file($file['tmp_name']) ||
       (int)($file['size']??0)<1 || (int)($file['size']??0)>2*1024*1024)
        throw new RuntimeException('Фотография руководителя: загрузите JPG, PNG или WebP размером до 2 МБ.');
    $mime=(new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $extensions=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
    $dimensions=@getimagesize($file['tmp_name']);
    if(!isset($extensions[$mime])||!$dimensions||($dimensions['mime']??'')!==$mime||
       ($dimensions[0]??0)<1||($dimensions[1]??0)<1||
       ($dimensions[0]??0)>2800||($dimensions[1]??0)>2800)
        throw new RuntimeException('Фотография: допустимы JPG, PNG или WebP с размерами до 2800 × 2800 пикселей.');
    $directory=dirname(__DIR__).'/assets/uploads';
    if(!is_dir($directory)||!is_writable($directory))
        throw new RuntimeException('Каталог для фотографий недоступен.');
    $relative='/assets/uploads/leader-'.bin2hex(random_bytes(16)).'.'.$extensions[$mime];
    if(!move_uploaded_file($file['tmp_name'],dirname(__DIR__).$relative))
        throw new RuntimeException('Не удалось сохранить фотографию главы администрации.');
    @chmod(dirname(__DIR__).$relative,0644);
    return $relative;
}
