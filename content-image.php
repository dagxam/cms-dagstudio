<?php
declare(strict_types=1);
require __DIR__.'/app/core.php';
header('X-Content-Type-Options: nosniff');
$id=max(0,(int)($_GET['id']??0));
if($id<1){http_response_code(404);exit;}
$q=database()->prepare('SELECT kind,status FROM content WHERE id=? LIMIT 1');
$q->execute([$id]);$content=$q->fetch();
if(!$content||(!($content['status']==='published'&&module_enabled($content['kind']))&&!allowed($content['kind']))){
    http_response_code(404);exit;
}
$cover=cms_content_image($id);
if(!$cover){http_response_code(404);exit;}
$path=cms_content_image_file((string)$cover['filename']);
if($path===null){http_response_code(404);exit;}
$mime=['jpg'=>'image/jpeg','png'=>'image/png','webp'=>'image/webp'][pathinfo($path,PATHINFO_EXTENSION)]??null;
if($mime===null){http_response_code(404);exit;}
header('Content-Type: '.$mime);
header('Content-Length: '.filesize($path));
header('Cache-Control: public, max-age=300');
header('X-Content-Type-Options: nosniff');
if($_SERVER['REQUEST_METHOD']!=='HEAD')readfile($path);
