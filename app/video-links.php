<?php
declare(strict_types=1);
/** Безопасные внешние видео: только vk.com/vkvideo.ru и rutube.ru. */
function cms_video_embed(string $url): ?array {
    $parts=parse_url($url);
    if(!is_array($parts) || ($parts['scheme']??'')!=='https' || !isset($parts['host']) ||
       isset($parts['user']) || isset($parts['pass']) || isset($parts['port']) ||
       isset($parts['fragment']))return null;
    $host=strtolower($parts['host']);
    $path=$parts['path']??'';
    if(in_array($host,['rutube.ru','www.rutube.ru'],true)){
        if(!preg_match('~^/(?:video|play/embed)/([a-zA-Z0-9_-]{8,64})/?$~D',$path,$m))return null;
        return ['provider'=>'rutube','embed'=>'https://rutube.ru/play/embed/'.$m[1].'/'];
    }
    if(!in_array($host,['vk.com','www.vk.com','vkvideo.ru','www.vkvideo.ru'],true))return null;
    if(preg_match('~^/video(-?[0-9]{1,15})_([0-9]{1,15})$~D',$path,$m)){
        $params=['oid'=>$m[1],'id'=>$m[2]];
        parse_str((string)($parts['query']??''),$query);
        if(isset($query['hash']) && is_string($query['hash']) && preg_match('/^[a-zA-Z0-9]{3,100}$/D',$query['hash']))$params['hash']=$query['hash'];
        return ['provider'=>'vk','embed'=>'https://vkvideo.ru/video_ext.php?'.http_build_query($params,'','&',PHP_QUERY_RFC3986)];
    }
    if($path==='/video_ext.php'){
        parse_str((string)($parts['query']??''),$q);
        if(!isset($q['oid'],$q['id']) || !preg_match('/^-?[0-9]{1,15}$/D',(string)$q['oid']) || !preg_match('/^[0-9]{1,15}$/D',(string)$q['id']))return null;
        $params=['oid'=>(string)$q['oid'],'id'=>(string)$q['id']];
        if(isset($q['hash']) && preg_match('/^[a-zA-Z0-9]{3,100}$/D',(string)$q['hash']))$params['hash']=(string)$q['hash'];
        return ['provider'=>'vk','embed'=>'https://vkvideo.ru/video_ext.php?'.http_build_query($params,'','&',PHP_QUERY_RFC3986)];
    }
    return null;
}
function cms_video_table(): void {
    database()->exec("CREATE TABLE IF NOT EXISTS cms_video_links (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}
function cms_video_links(bool $published=true): array {
    cms_video_table();
    return database()->query("SELECT * FROM cms_video_links ".($published?"WHERE status='published' ":"")."ORDER BY created_at DESC LIMIT 100")->fetchAll();
}
