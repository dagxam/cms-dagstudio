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
    <link rel="stylesheet" href="/assets/style.css?v=fa672-local2"><title>18+ — подтверждение возраста</title>
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
if(in_array($filter,['photo','video','document'],true))
    $files=array_values(array_filter($files,static fn(array $f):bool=>$f['category']===$filter));
$linkedVideos=$filter==='video' && cms_module_enabled('videos')?cms_video_links(true):[];
$rating=cms_age_rating();
$tpl=site_template();
$design=template_design($tpl);
$siteContent=template_content($tpl);
$siteName=config_value('site_name','DAG STUDIO CMS');
$siteLogo=(string)$siteContent['logo_path'];
if(!preg_match('~^/assets/uploads/logo-[a-f0-9]{32}\\.(png|jpg|webp)$~D',$siteLogo))$siteLogo='';
$sectionLabels=['document'=>'Документы','photo'=>'Фотогалерея','video'=>'Видеогалерея','all'=>'Материалы сайта'];
$activeSection=$item['category']??($external?'video':$filter);
$sectionTitle=$sectionLabels[$activeSection]??'Материалы сайта';
$pageTitle=(string)($item['title']??($external['title']??$sectionTitle));
$sectionDescriptions=[
    'document'=>'Официальные документы, отчёты, положения и полезные файлы.',
    'photo'=>'Фотографии, события и галереи нашего сайта.',
    'video'=>'Видеоматериалы, репортажи и публикации.',
    'all'=>'Документы, фотографии и видеоматериалы сайта.',
];
$menuUrls=[];$menuLinks=[];
foreach(cms_menu_visible($tpl) as $link){$menuLinks[]=$link;$menuUrls[]=$link['url'];}
foreach(cms_module_ids($tpl,'nav') as $module){
    $url=cms_module_href($module);
    if(!in_array($url,$menuUrls,true)){$menuLinks[]=['url'=>$url,'label'=>cms_module_label($module)];$menuUrls[]=$url;}
}
?>
<!doctype html>
<html lang="ru" data-theme-storage-key="dagstudio-template-<?=h($tpl)?>" data-theme-default="<?=h(template_default_mode($tpl))?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="<?=h($sectionDescriptions[$activeSection]??$sectionDescriptions['all'])?>">
<title><?=h($pageTitle)?> — <?=h($siteName)?></title>
<link id="dag-favicon" rel="icon" type="image/svg+xml" href="/assets/ornament-<?=template_default_mode($tpl)==='light'?'light':'dark'?>.svg">
<script>try{const k='dagstudio-template-<?=h($tpl)?>';const t=localStorage.getItem(k);document.documentElement.dataset.theme=t==='light'||t==='dark'?t:'<?=h(template_default_mode($tpl))?>'}catch(e){document.documentElement.dataset.theme='<?=h(template_default_mode($tpl))?>'}</script>
<script src="/assets/theme.js?v=gallery4" defer></script>
<script src="/assets/accessibility.js?v=a11y4" defer></script>
<script src="/assets/privacy.js?v=privacy4" defer></script>
<link rel="stylesheet" href="/assets/style.css?v=fa672-local2">
<link rel="stylesheet" href="/assets/templates.css?v=contacts8">
<link rel="stylesheet" href="/assets/media.css?v=fa672-local2">
<style id="dag-site-palettes"><?=template_palette_css($design,$tpl)?></style>
</head>
<body class="site-page site-template-<?=h($tpl)?> cms-media-page cms-media-layout-<?=h($activeSection)?> <?=$tpl==='government'?'government-page cms-media-official':''?>" <?=cms_accessibility_attributes()?> style="<?=h(template_style($design,$tpl))?>">
<a class="cms-skip-link" href="#cms-media-content">Перейти к содержимому</a>
<?php cms_render_public_header($tpl); ?>
<main id="cms-media-content" class="cms-media-public">
  <nav class="cms-media-breadcrumb" aria-label="Навигационная цепочка">
    <a href="/">Главная</a><span aria-hidden="true">›</span>
    <?php if($item || $external):?><a href="/media.php?type=<?=h($activeSection)?>"><?=h($sectionTitle)?></a><span aria-hidden="true">›</span><span><?=h($pageTitle)?></span>
    <?php else:?><span aria-current="page"><?=h($sectionTitle)?></span><?php endif;?>
  </nav>
  <div class="cms-media-head">
    <div class="cms-media-head-copy">
      <span class="eyebrow"><?=h($tpl==='government'?'РАЗДЕЛ ОФИЦИАЛЬНОГО САЙТА':'ПУБЛИКАЦИИ И МАТЕРИАЛЫ')?></span>
      <h1><?=h($pageTitle)?></h1>
      <p><?=h($sectionDescriptions[$activeSection]??$sectionDescriptions['all'])?></p>
    </div>
    <span class="cms-media-head-decoration" aria-hidden="true"><?=cms_fa_icon($activeSection)?></span>
  </div>
  <?php if($external):
    $embedded=cms_video_embed((string)$external['original_url']);?>
    <article class="cms-media-detail cms-media-detail-video">
      <div class="cms-media-detail-meta"><span class="cms-media-topic">Видеогалерея · <?=h($external['provider']==='vk'?'VK Видео':'Rutube')?></span><span class="cms-age-mark"><?=h($external['age_rating'])?></span></div>
      <?php if($embedded):?><div class="cms-video-frame cms-video-consent" data-privacy-video-url="<?=h($embedded['embed'])?>" data-video-title="<?=h($external['title'])?>"><div class="cms-video-permission"><p>Видео размещено на сторонней платформе. Передача данных сервису начинается только после вашего разрешения.</p><button type="button" data-privacy-video-enable>Разрешить и воспроизвести</button><a href="<?=h($external['original_url'])?>" target="_blank" rel="noopener noreferrer">Открыть видео на платформе <i class="fa-solid fa-arrow-up-right-from-square cms-icon-inline" aria-hidden="true"></i></a></div></div><?php endif;?>
      <?php if($external['description']):?><p class="cms-media-description"><?=nl2br(h($external['description']))?></p><?php endif;?>
      <a class="cms-media-detail-link" href="<?=h($external['original_url'])?>" target="_blank" rel="noopener noreferrer">Смотреть на видеоплатформе <i class="fa-solid fa-arrow-up-right-from-square cms-icon-inline" aria-hidden="true"></i></a>
    </article>
  <?php elseif($item):?>
    <article class="cms-media-detail cms-media-detail-<?=h($item['category'])?>">
      <div class="cms-media-detail-meta"><span class="cms-media-topic"><?=h($sectionTitle)?></span><span class="cms-age-mark"><?=h($item['age_rating'])?></span></div>
      <?php if($item['category']==='photo'):?>
        <figure class="cms-media-full-photo"><img src="/media.php?file=<?=(int)$item['id']?>" alt="<?=h($item['alt_text']?:$item['title'])?>"></figure>
      <?php elseif($item['category']==='video'):?>
        <video controls preload="metadata" playsinline aria-label="<?=h($item['title'])?>"><source src="/media.php?file=<?=(int)$item['id']?>" type="<?=h($item['mime'])?>">Браузер не поддерживает видео.</video>
      <?php else:?>
        <div class="cms-media-document-download">
          <span class="cms-media-document-icon" aria-hidden="true"><?=cms_fa_icon('documents')?></span>
          <div><strong><?=h($item['original_name'])?></strong><p><?=number_format(((int)$item['size_bytes'])/1048576,2,',',' ')?> МБ · <?=h(strtoupper(pathinfo((string)$item['original_name'],PATHINFO_EXTENSION)))?></p></div>
          <a class="cms-media-action" href="/media.php?file=<?=(int)$item['id']?>">Скачать документ <span aria-hidden="true"><i class="fa-solid fa-download cms-icon-inline" aria-hidden="true"></i></span></a>
        </div>
      <?php endif;?>
      <?php if($item['description']):?><p class="cms-media-description"><?=nl2br(h((string)$item['description']))?></p><?php endif;?>
    </article>
  <?php else:?>
    <nav class="cms-media-filters" aria-label="Выберите тип материалов">
      <?php foreach(['document'=>'Документы','photo'=>'Фотогалерея','video'=>'Видеогалерея'] as $type=>$label):
        if(!cms_module_enabled(['document'=>'documents','photo'=>'photos','video'=>'videos'][$type]))continue;?>
        <a class="<?=$filter===$type?'current':''?>" <?=$filter===$type?'aria-current="page"':''?> href="/media.php?type=<?=h($type)?>"><?=h($label)?></a>
      <?php endforeach;?>
    </nav>
    <div class="cms-media-grid">
      <?php if($filter==='video'):foreach($linkedVideos as $v):?>
        <article class="cms-media-tile cms-media-tile-video">
          <a class="cms-media-tile-media" href="/media.php?type=video&amp;external=<?=(int)$v['id']?>" aria-label="Смотреть: <?=h($v['title'])?>"><span class="cms-media-tile-symbol" aria-hidden="true"><?=cms_fa_icon('video')?></span></a>
          <div class="cms-media-tile-copy">
            <div class="cms-media-tile-meta"><span><?=h($v['provider']==='vk'?'VK Видео':'Rutube')?></span><span><?=h($v['age_rating'])?></span></div>
            <h2><a href="/media.php?type=video&amp;external=<?=(int)$v['id']?>"><?=h($v['title'])?></a></h2>
            <?php if($v['description']):?><p><?=h(mb_strimwidth((string)$v['description'],0,150,'…','UTF-8'))?></p><?php endif;?>
            <a class="cms-media-tile-cta" href="/media.php?type=video&amp;external=<?=(int)$v['id']?>">Смотреть <span aria-hidden="true"><i class="fa-solid fa-arrow-up-right-from-square cms-icon-inline" aria-hidden="true"></i></span></a>
          </div>
        </article>
      <?php endforeach;endif;?>
      <?php foreach($files as $f):$id=(int)$f['id'];$link='/media.php?view='.$id;?>
      <article class="cms-media-tile cms-media-tile-<?=h($f['category'])?>">
        <a class="cms-media-tile-media" href="<?=h($link)?>" aria-label="<?=h($f['title'])?>">
          <?php if($f['category']==='photo' && $f['age_rating']!=='18+'):?>
            <img loading="lazy" decoding="async" src="/media.php?file=<?=$id?>" alt="<?=h($f['alt_text']?:$f['title'])?>">
          <?php else:?><span class="cms-media-tile-symbol" aria-hidden="true"><?= $f['age_rating']==='18+'?'18+':cms_fa_icon($f['category']) ?></span><?php endif;?>
        </a>
        <div class="cms-media-tile-copy">
          <div class="cms-media-tile-meta"><span><?=h(['photo'=>'Фотография','document'=>'Документ','video'=>'Видео'][$f['category']])?></span><span><?=h($f['age_rating'])?></span></div>
          <h2><a href="<?=h($link)?>"><?=h($f['title'])?></a></h2>
          <?php if($f['description']):?><p><?=h(mb_strimwidth((string)$f['description'],0,150,'…','UTF-8'))?></p><?php endif;?>
          <a class="cms-media-tile-cta" href="<?=h($link)?>"><?=$f['category']==='document'?'Открыть документ':($f['category']==='video'?'Смотреть видео':'Смотреть фото')?><span aria-hidden="true"><i class="fa-solid fa-arrow-up-right-from-square cms-icon-inline" aria-hidden="true"></i></span></a>
        </div>
      </article>
      <?php endforeach;?>
    </div>
    <?php if(!$files&&!$linkedVideos):?><div class="cms-media-empty"><span aria-hidden="true"><i class="fa-solid fa-folder-open"></i></span><h2>Материалов пока нет</h2><p>Здесь появятся опубликованные материалы этого раздела.</p></div><?php endif;?>
  <?php endif;?>
  <?php if($item||$external):?><a class="cms-media-back" href="/media.php?type=<?=h($activeSection)?>"><i class="fa-solid fa-arrow-left cms-icon-inline" aria-hidden="true"></i> К разделу «<?=h($sectionTitle)?>»</a><?php endif;?>
</main>
<footer class="cms-media-footer">
  <div><strong><?=h($siteName)?></strong><span>© <?=date('Y')?> · <?=h($siteContent['footer_text'])?></span></div>
  <div><span><?=cms_age_mark()?></span><span>Создано на DAG STUDIO CMS</span></div>
</footer>
<div class="cms-media-social-footer"><?=cms_render_social_links($tpl,'footer')?></div>
<div class="cms-media-bottom-privacy"><?=cms_privacy_links()?></div>
<?=cms_cookie_controls()?>
<?=cms_age_gate()?>
</body></html>
