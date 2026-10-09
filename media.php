<?php
declare(strict_types=1);
require __DIR__.'/app/core.php';
$requestedCategory=(string)($_GET['type']??'');
$accessModule=['document'=>'documents','photo'=>'photos','video'=>'videos'][$requestedCategory]??'';
if($accessModule!==''&&!cms_module_enabled($accessModule)){http_response_code(404);exit('Раздел отключён.');}
if($accessModule===''){
    if(!array_filter(['documents','photos','videos'],'cms_module_enabled')){http_response_code(404);exit('Галереи отключены.');}
}
header('X-Content-Type-Options: nosniff');
$mediaId=max(0,(int)($_GET['file']??0));
$showId=max(0,(int)($_GET['view']??0));
$preview=account()!==null && allowed('media');
$externalId=max(0,(int)($_GET['external']??0));
if(!$mediaId && !$showId && !$externalId && $requestedCategory===''){
    foreach(['documents'=>'document','photos'=>'photo','videos'=>'video'] as $module=>$category){
        if(cms_module_enabled($module))go('/media.php?type='.$category);
    }
}
$external=null;
if($externalId){
    cms_video_table();
    $q=database()->prepare('SELECT * FROM cms_video_links WHERE id=?'.($preview?'':" AND status='published"));
    $q->execute([$externalId]);$external=$q->fetch()?:null;
    if(!$external || !cms_module_enabled('videos')){http_response_code(404);exit('Видео не найдено.');}
}
$item=($mediaId||$showId)?cms_media_get($mediaId?:$showId,!$preview):null;
if(($mediaId||$showId)&&!$item){http_response_code(404);exit('Материал не найден.');}
if($item && !cms_module_enabled(['document'=>'documents','photo'=>'photos','video'=>'videos'][$item['category']]??'')){
    http_response_code(404);exit('Раздел отключён.');
}
if((($item && $item['age_rating']==='18+') || ($external && $external['age_rating']==='18+')) && empty($_SESSION['media_age_confirmed'])) {
    if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['confirm_age'])){
        verify_token();
        $_SESSION['media_age_confirmed']=true;
        go('/media.php?'.($externalId?'type=video&external='.$externalId:($mediaId?'file='.$mediaId:'view='.$showId)));
    }
    http_response_code(403);
    ?><!doctype html><html lang="ru"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="stylesheet" href="/assets/style.css"><title>18+ — подтверждение возраста</title>
    <body class="cms-media-page"><main class="cms-media-public box"><span class="cms-age-mark">18+</span>
    <h1>Информация для совершеннолетних</h1><p>Материал имеет возрастную маркировку 18+. Подтвердите, что вам исполнилось 18 лет.</p>
    <form method="post"><?=csrf()?><input type="hidden" name="confirm_age" value="1"><button class="button" type="submit">Мне исполнилось 18 лет</button></form>
    <a href="/media.php">Вернуться к медиатеке</a>
    <p><small>Подтверждение не является документальной проверкой возраста.</small></p></main></body></html><?php exit;
}
if($mediaId){
    $filename=(string)$item['filename'];
    if(!preg_match('/^[a-f0-9]{32}\.(?:jpg|png|webp|pdf|docx|xlsx|pptx|mp4|webm)$/D',$filename)){http_response_code(404);exit;}
    $path=__DIR__.'/storage/media/'.$filename;
    if(!is_file($path)){http_response_code(404);exit('Файл отсутствует.');}
    $mime=(string)$item['mime'];
    $formats=cms_media_formats()[$item['category']]??[];
    if(!in_array($mime,array_values($formats),true)){http_response_code(404);exit;}
    $size=filesize($path);
    $inline=in_array($item['category'],['photo','video'],true);
    header('Content-Type: '.$mime);
    header('Content-Disposition: '.($inline?'inline':'attachment').'; filename="material-'.(int)$item['id'].'.'.pathinfo($filename,PATHINFO_EXTENSION).'"');
    header('Cache-Control: private, max-age=300');
    header('X-Content-Type-Options: nosniff');
    if($item['category']==='video'){
        header('Accept-Ranges: bytes');
        $start=0;$end=$size-1;
        if(isset($_SERVER['HTTP_RANGE']) && preg_match('/^bytes=(\d*)-(\d*)$/',$_SERVER['HTTP_RANGE'],$m)){
            if($m[1]!=='' && $m[2]===''){$start=(int)$m[1];}
            elseif($m[1]==='' && $m[2]!==''){$start=max(0,$size-(int)$m[2]);}
            else{$start=(int)$m[1];$end=(int)$m[2];}
            if($start>$end || $start>=$size){header('Content-Range: bytes */'.$size);http_response_code(416);exit;}
            $end=min($end,$size-1);
            http_response_code(206);header('Content-Range: bytes '.$start.'-'.$end.'/'.$size);
        }
        header('Content-Length: '.($end-$start+1));
        if($_SERVER['REQUEST_METHOD']==='HEAD')exit;
        $handle=fopen($path,'rb');fseek($handle,$start);
        $remaining=$end-$start+1;
        while($remaining>0&&!feof($handle)){ $buf=fread($handle,min(65536,$remaining));$len=strlen($buf);if($len===0)break;echo $buf;$remaining-=$len; }
        fclose($handle);exit;
    }
    header('Content-Length: '.$size);
    if($_SERVER['REQUEST_METHOD']==='HEAD')exit;
    readfile($path);exit;
}
$files=array_values(array_filter(cms_media_list(true),
    static fn(array $x):bool=>cms_module_enabled(['document'=>'documents','photo'=>'photos','video'=>'videos'][$x['category']]??'')));
$filter=(string)($_GET['type']??'all');
if(in_array($filter,['photo','video','document'],true))$files=array_values(array_filter($files,static fn(array $f):bool=>$f['category']===$filter));
$rating=cms_age_rating();
?><!doctype html>
<html lang="ru" data-theme-storage-key="dagstudio-template-<?=h(site_template())?>" data-theme-default="<?=h(template_default_mode(site_template()))?>"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=h($item['title']??($external['title']??['document'=>'Документы','photo'=>'Фотогалерея','video'=>'Видеогалерея'][$requestedCategory]??'Разделы сайта'))?> — <?=h(config_value('site_name','DAG STUDIO CMS'))?></title>
<meta name="robots" content="index,follow"><link rel="stylesheet" href="/assets/style.css?v=media1">
<link rel="stylesheet" href="/assets/media.css?v=legal3">
<script>try{const k='dagstudio-template-<?=h(site_template())?>';const t=localStorage.getItem(k);document.documentElement.dataset.theme=t==='light'||t==='dark'?t:'<?=h(template_default_mode(site_template()))?>'}catch(e){document.documentElement.dataset.theme='<?=h(template_default_mode(site_template()))?>'}</script>
<script src="/assets/theme.js?v=modules7" defer></script>
<script src="/assets/accessibility.js?v=legal2" defer></script></head>
<body class="cms-media-page"<?=cms_accessibility_attributes()?>>
<header class="cms-media-header"><a href="/">← На главную</a><strong><?=h(config_value('site_name','DAG STUDIO CMS'))?></strong><div class="cms-media-header-actions"><button class="theme-toggle" type="button" data-theme-toggle aria-label="Переключить цветовую тему" aria-pressed="false">☾/☼</button><?=cms_accessibility_control()?><?=cms_age_mark()?></div></header>
<main class="cms-media-public"><div class="cms-media-head"><span class="eyebrow">DAG STUDIO CMS</span><h1><?=h($item['title']??($external['title']??(['document'=>'Документы','photo'=>'Фотогалерея','video'=>'Видеогалерея'][$requestedCategory]??'Файлы сайта')))?></h1>
<p>Документы, фотографии и видео, опубликованные администрацией сайта.</p></div>
<?php if($external):
  $embedded=cms_video_embed((string)$external['original_url']);
?>
<article class="box cms-media-detail"><span class="cms-age-mark"><?=h($external['age_rating'])?></span>
<?php if($embedded):?><div class="cms-video-frame"><iframe src="<?=h($embedded['embed'])?>" title="<?=h($external['title'])?>" loading="lazy" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe></div><?php endif;?>
<?php if($external['description']):?><p><?=nl2br(h($external['description']))?></p><?php endif;?>
<p><a href="<?=h($external['original_url'])?>" target="_blank" rel="noopener noreferrer">Открыть на видеоплатформе ↗</a></p></article>
<?php elseif($item):?>
<article class="box cms-media-detail"><span class="cms-age-mark"><?=h($item['age_rating'])?></span>
<?php if($item['category']==='photo'):?><img src="/media.php?file=<?=(int)$item['id']?>" alt="<?=h($item['alt_text']?:$item['title'])?>">
<?php elseif($item['category']==='video'):?><video controls preload="metadata" playsinline aria-label="<?=h($item['title'])?>"><source src="/media.php?file=<?=(int)$item['id']?>" type="<?=h($item['mime'])?>">Ваш браузер не поддерживает видео.</video>
<?php else:?><p>Файл документа: <?=h($item['original_name'])?></p><a class="button" href="/media.php?file=<?=(int)$item['id']?>">Скачать документ ↓</a><?php endif;?>
<?php if($item['description']):?><p><?=nl2br(h((string)$item['description']))?></p><?php endif;?>
<p><a href="/media.php">← Все материалы</a></p></article>
<?php else:?>
<nav class="cms-media-filters" aria-label="Тип файлов">
<?php foreach(['all'=>'Все','document'=>'Документы','photo'=>'Фотогалерея','video'=>'Видеогалерея'] as $type=>$label): if($type!=='all'&&!cms_module_enabled(['document'=>'documents','photo'=>'photos','video'=>'videos'][$type]))continue;?>
<a class="<?=$filter===$type?'current':''?>" href="/media.php?type=<?=h($type)?>"><?=h($label)?></a>
<?php endforeach;?></nav>
<div class="cms-media-grid">
<?php if($filter==='video'):
  foreach(cms_video_links(true) as $v):?>
  <article class="box cms-media-tile"><div class="cms-media-type">▶</div><span class="cms-age-mark"><?=h($v['age_rating'])?></span><h2><a href="/media.php?type=video&amp;external=<?=(int)$v['id']?>"><?=h($v['title'])?></a></h2><p><?=h(mb_strimwidth((string)$v['description'],0,150,'…','UTF-8'))?></p><a href="/media.php?type=video&amp;external=<?=(int)$v['id']?>">Смотреть видео ↗</a></article>
  <?php endforeach; endif;?>
<?php foreach($files as $f):?><article class="box cms-media-tile">
<?php if($f['category']==='photo'):?><a href="/media.php?view=<?=(int)$f['id']?>">
<?php if($f['age_rating']==='18+'):?><div class="cms-media-type">18+</div><?php else:?><img loading="lazy" src="/media.php?file=<?=(int)$f['id']?>" alt="<?=h($f['alt_text']?:$f['title'])?>"><?php endif;?></a>
<?php else:?><div class="cms-media-type"><?=['document'=>'▤','video'=>'▣'][$f['category']]?></div><?php endif;?>
<span class="cms-age-mark"><?=h($f['age_rating'])?></span><h2><a href="/media.php?view=<?=(int)$f['id']?>"><?=h($f['title'])?></a></h2>
<p><?=h(mb_strimwidth((string)($f['description']??''),0,150,'…','UTF-8'))?></p>
<a href="/media.php?view=<?=(int)$f['id']?>">Открыть ↗</a>
</article><?php endforeach;?>
</div>
<?php if(!$files && !($filter==='video'&&cms_video_links(true))):?><div class="box">Опубликованных материалов пока нет.</div><?php endif;?>
<?php endif;?>
</main><footer class="cms-media-footer">© <?=date('Y')?> <?=h(config_value('site_name','DAG STUDIO CMS'))?> · DAG STUDIO CMS · <?=h($rating)?></footer>
<?=cms_age_gate()?>
</body></html>
