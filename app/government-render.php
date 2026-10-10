<?php
declare(strict_types=1);
if (!defined('DAG_CMS_SITE_VIEW') || $activeTemplate !== 'government') { http_response_code(404); exit; }

$gov=government_layout();
$govLayout=$gov['layout'];
$govLeftModules=cms_module_ids('government','left');
$govRightModules=cms_module_ids('government','right');
$govLeft=in_array($govLayout,['both','swap','left'],true) || (bool)$govLeftModules;
$govRight=in_array($govLayout,['both','swap','right'],true) || (bool)$govRightModules;
if($govLeft && $govRight && !in_array($govLayout,['both','swap'],true))$govLayout='both';
elseif($govLeft && !$govRight)$govLayout='left';
elseif(!$govLeft && $govRight)$govLayout='right';
elseif(!$govLeft && !$govRight)$govLayout='none';
$govArticle=isset($record) && is_array($record) ? $record : null;
$search=trim((string)($_GET['q']??''));
$govResults=[];
if ($search!=='' && mb_strlen($search)<=120) {
    $term='%'.str_replace(['\\','%','_'],['\\\\','\\%','\\_'],$search).'%';
    $q=database()->prepare("SELECT * FROM content WHERE status='published' AND (title LIKE ? OR summary LIKE ? OR body LIKE ?) ORDER BY created_at DESC LIMIT 50");
    $q->execute([$term,$term,$term]);
    $govResults=array_values(array_filter($q->fetchAll(),static fn(array $item): bool => module_enabled($item['kind'])));
}
$requestedWidget=(string)($_GET['module']??'');
$govSections=$kind!==''?[$kind]:((in_array($requestedWidget,['features','contact'],true)&&cms_module_enabled($requestedWidget))?[$requestedWidget]:cms_module_ids('government','main'));
$govSections=array_values(array_filter($govSections,static fn(string $id):bool=>cms_module_enabled($id)));
$govLogo=$siteLogo;
$govBanner=$heroImage;
$govTitle=$kind!==''?($moduleLabels[$kind]??$gov['center_title']):$gov['center_title'];
if ($search!=='')$govTitle='Результаты поиска';
?>
<!doctype html>
<html lang="ru" data-theme-storage-key="dagstudio-template-government" data-theme-default="light">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="description" content="<?=h(mb_substr($metaDescription,0,250))?>">
<meta name="theme-color" content="<?=h($design['palettes']['light']['background'])?>">
<title><?=h($metaTitle)?></title>
<link id="dag-favicon" rel="icon" type="image/svg+xml" href="/assets/ornament-light.svg">
<script>try{const t=localStorage.getItem('dagstudio-template-government');document.documentElement.dataset.theme=t==='dark'?'dark':'light'}catch(e){document.documentElement.dataset.theme='light'}</script>
<script src="/assets/theme.js?v=government5" defer></script>
<script src="/assets/government.js?v=government5" defer></script>
<link rel="stylesheet" href="/assets/style.css?v=government5">
<link rel="stylesheet" href="/assets/templates.css?v=covers1">
<link rel="stylesheet" href="/assets/government.css?v=government5">
<link rel="stylesheet" href="/assets/media.css?v=legal2">
<script src="/assets/accessibility.js?v=a11y4" defer></script>
<script src="/assets/privacy.js?v=privacy4" defer></script>
<style id="dag-site-palettes"><?=template_palette_css($design,'government')?></style>
</head>
<body class="site-page site-template-government government-page government-layout-<?=h($govLayout)?>"
  <?=cms_accessibility_attributes()?> style="<?=h(template_style($design,'government'))?>;--gov-left-width:<?=h($gov['left_width'])?>px;--gov-right-width:<?=h($gov['right_width'])?>px">
<a class="cms-skip-link" href="#main-content">Перейти к основному содержимому</a>
<?php if($previewMode): ?>
<div class="site-preview-banner"><strong>Предпросмотр шаблона «Администрация»</strong>. Настройки ещё не применены. <a href="/admin/index.php?section=templates">Вернуться в редактор</a></div>
<?php endif;?>
<?php if($gov['show_topbar']==='1'):?>
<div class="government-utility"><div class="government-container government-utility-inner">
  <div class="government-utility-info">
    <span><span aria-hidden="true">⌖</span> <?=h($gov['location'])?></span>
    <?php if($gov['show_date']==='1'):?><time data-government-datetime aria-label="Текущая дата и время"><?=h(date('d.m.Y'))?></time><?php endif;?>
    <?php if($gov['office_phone']!==''):?><span>☎ <?=h($gov['office_phone'])?></span><?php endif;?>
  </div>
  <div class="government-utility-actions">
    <?=cms_accessibility_control()?>
    <?=cms_age_mark()?>
    <button type="button" class="government-utility-button" data-government-print>▣ Печать</button>
    <button type="button" class="theme-toggle" data-theme-toggle aria-label="Переключить тему"><span class="theme-toggle-dark" aria-hidden="true">☾</span><span class="theme-toggle-light" aria-hidden="true">☼</span></button>
  </div>
</div></div>
<?php endif;?>
<?php if($gov['show_topbar']!=='1'):?><div class="cms-government-a11y-bar"><?=cms_accessibility_control()?> <button type="button" class="theme-toggle" data-theme-toggle aria-label="Переключить тему">☾/☼</button></div><?php endif;?>
<div class="government-container">
<header class="government-masthead">
  <a class="government-identity" href="/">
    <?php if($govLogo!==''):?><img class="government-identity-logo" src="<?=h($govLogo)?>" alt="" loading="eager"><?php else:?>
    <span class="government-symbol"><img class="logo-on-light" src="/assets/ornament-light.svg" alt=""><img class="logo-on-dark" src="/assets/ornament-dark.svg" alt=""></span>
    <?php endif;?>
    <span class="government-identity-copy"><small><?=h($gov['name'])?></small><strong><?=h($siteName)?></strong><span><?=h($gov['name_detail'])?></span></span>
  </a>
  <?php if($gov['show_banner']==='1'):?>
  <div class="government-banner">
    <img src="<?=h($govBanner!==''?$govBanner:'/assets/mountain-scene.svg')?>" alt="" loading="eager">
    <div class="government-banner-content">
      <span><?=h($gov['top_note'])?></span>
      <strong><?=h($gov['banner_title'])?></strong>
      <small><?=h($gov['banner_text'])?></small>
    </div>
  </div>
  <?php else:?>
  <div class="government-masthead-note"><span><?=h($gov['top_note'])?></span><strong><?=h($gov['banner_title'])?></strong></div>
  <?php endif;?>
</header>
<nav class="government-custom-menu" aria-label="Главное меню администрации">
<?php $govMenuUrls=[]; foreach(cms_menu_visible('government') as $link):$govMenuUrls[]=$link['url'];?>
<a href="<?=h($link['url'])?>"><?=h($link['label'])?></a>
<?php endforeach;?>
<?php foreach(cms_module_ids('government','nav') as $id):if(in_array(cms_module_href($id),$govMenuUrls,true))continue;?>
<a href="<?=h(cms_module_href($id))?>"><?=h(cms_module_label($id))?></a>
<?php endforeach;?></nav>
<?php if($gov['show_search']==='1'):?>
<div class="government-searchbar">
  <form method="get" action="/" role="search" class="government-search">
    <label for="government-search-input" class="government-search-icon" aria-label="Поиск">⌕</label>
    <input id="government-search-input" type="search" name="q" value="<?=h($search)?>" placeholder="<?=h($gov['search_placeholder'])?>" maxlength="120">
    <button type="submit"><?=h($gov['search_button'])?></button>
  </form>
  <div class="government-quick-actions">
    <?php foreach($gov['quick_links'] as $quick):?>
    <a class="government-quick-link" href="<?=h($quick['url'])?>"><?=h($quick['label'])?></a>
    <?php endforeach;?>
    <?php if($gov['quick_title']!=='' && safe_template_url($gov['quick_url'])):?>
    <a class="government-reception" href="<?=h($gov['quick_url'])?>"><?=h($gov['quick_title'])?> ↗</a>
    <?php endif;?>
  </div>
</div>
<?php endif;?>
<div class="government-columns">
  <?php if($govLeft):?>
  <aside class="government-left" aria-label="Навигация администрации">
    <h2 class="government-sidebar-heading"><?=h($gov['left_title'])?></h2>
    <nav class="government-left-nav" aria-label="Разделы администрации">
      <?php foreach($gov['left_menu'] as $link):?><a href="<?=h($link['url'])?>"><?=h($link['label'])?><span aria-hidden="true">›</span></a><?php endforeach;?>

    </nav>
    <?php if($govLeftModules):?><section class="government-module-extra" aria-label="Модули слева">
    <?php foreach($govLeftModules as $id):?><?php cms_module_sidebar($id,'government'); ?><?php endforeach;?>
    </section><?php endif;?>
  </aside>
  <?php endif;?>
  <main class="government-main" id="main-content">
    <?php if($govArticle):?>
      <?php $pageConfig=$govArticle['kind']==='page'?cms_page_options((int)$govArticle['id']):null; ?>
      <?php if($pageConfig):?><div class="cms-page-detail-layout">
      <?php if($pageConfig['left']):?><aside class="cms-page-detail-sidebar" aria-label="Боковые блоки слева"><?php foreach($pageConfig['left'] as $widget):cms_module_sidebar($widget,'government');endforeach;?></aside><?php endif;?>
      <div class="cms-page-detail-center"><?php cms_page_module($pageConfig['before'],'government'); ?>
      <?php endif;?>
      <div class="government-breadcrumb"><a href="/">Главная</a><span>›</span><?=h($govArticle['title'])?></div>
      <h1><?=h($govArticle['title'])?></h1>
      <?php $govCover=cms_content_image((int)$govArticle['id']);if($govCover):?>
      <figure class="cms-article-cover government-article-cover">
      <img src="<?=h(cms_content_image_url((int)$govArticle['id'],$govCover))?>" alt="<?=h($govCover['alt_text']?:$govArticle['title'])?>" loading="eager">
      </figure>
      <?php endif;?>
      <?php if(!empty($govArticle['summary'])):?><p class="government-lead"><?=h((string)$govArticle['summary'])?></p><?php endif;?>
      <div class="government-article-body"><?=nl2br(h((string)($govArticle['body']??'')))?></div>
      <?php if($govArticle['kind']==='product' && $govArticle['price']!==null):?><strong class="price"><?=h(number_format((float)$govArticle['price'],2,',',' '))?> ₽</strong><?php endif;?>
      <a class="government-return" href="/">← Вернуться на главную</a>
      <?php if($pageConfig):cms_page_module($pageConfig['after'],'government'); ?></div>
      <?php if($pageConfig['right']):?><aside class="cms-page-detail-sidebar" aria-label="Боковые блоки справа"><?php foreach($pageConfig['right'] as $widget):cms_module_sidebar($widget,'government');endforeach;?></aside><?php endif;?>
      </div><?php endif;?>
    <?php elseif($slug!==''):?>
      <h1>Страница не найдена</h1><p>Материал не опубликован или адрес изменился.</p><a class="government-return" href="/">Вернуться на главную</a>
    <?php elseif($search!==''):?>
      <h1>Поиск по сайту</h1>
      <p class="government-subline">Запрос: <strong><?=h($search)?></strong> · Найдено материалов: <?=count($govResults)?></p>
      <?php if(mb_strlen($search)>120):?><p>Поисковый запрос слишком длинный.</p><?php endif;?>
      <?php foreach($govResults as $item):?><article class="government-search-result"><h2><a href="/?p=<?=rawurlencode($item['slug'])?>"><?=h($item['title'])?></a></h2><p><?=h(mb_strimwidth((string)($item['summary']?:($item['body']??'')),0,190,'…','UTF-8'))?></p></article><?php endforeach;?>
      <?php if(!$govResults):?><p>По вашему запросу публикаций пока нет.</p><?php endif;?>
    <?php else:?>
      <div class="government-breadcrumb"><a href="/">Главная</a><?php if($kind!==''):?><span>›</span><?=h($moduleLabels[$kind]??$kind)?><?php endif;?></div>
      <h1><?=h($govTitle)?></h1>
      <?php if($kind===''):?>
      <div class="government-introduction">
        <h2><?=h($siteContent['title'])?></h2>
        <p><?=nl2br(h($gov['center_intro']))?></p>
        <?php if($siteContent['description']!==''):?><p><?=h($siteContent['description'])?></p><?php endif;?>
      </div>
      <?php endif;?>
      <?php foreach($govSections as $section):?>
        <?php if($section==='features'):?>
        <section class="government-section" id="features">
          <h2><?=h($siteContent['features_title'])?></h2>
          <div class="government-feature-grid">
            <?php for($i=1;$i<=3;$i++):?><div class="government-feature"><strong><?=h($siteContent['feature_'.$i.'_title'])?></strong><p><?=h($siteContent['feature_'.$i.'_text'])?></p></div><?php endfor;?>
          </div>
        </section>
        <?php elseif(in_array($section,['documents','photos','videos'],true)):?>
        <section class="government-section"><?php cms_module_media_block($section); ?></section>
        <?php elseif($section==='contact'):?>
        <section class="government-section" id="contact">
          <h2><?=h($siteContent['contact_title'])?></h2>
          <?php if($message!==''):?><div class="notice" role="status"><?=h($message)?></div><?php endif;?>
          <?php if($error!==''):?><div class="error" role="alert"><?=h($error)?></div><?php endif;?>
          <?php if($privacyReady):?>
          <form class="government-contact" method="post" action="/?module=contact#contact"><?=csrf()?>
            <input type="hidden" name="action" value="contact">
            <div class="honeypot" aria-hidden="true"><label>Сайт<input tabindex="-1" name="website" autocomplete="off"></label></div>
            <label>Ваше имя<input required name="name" maxlength="120" value="<?=h($_POST['name']??'')?>"></label>
            <label>E-mail<input required name="email" type="email" maxlength="190" value="<?=h($_POST['email']??'')?>"></label>
            <label>Сообщение<textarea required name="body" minlength="10" maxlength="5000" rows="4"><?=h($_POST['body']??'')?></textarea></label>
            <label class="check privacy-check"><input type="checkbox" name="consent" value="1" required> Даю отдельное согласие на обработку данных для ответа на обращение. <a href="/consent.php" target="_blank" rel="noopener">Текст согласия</a>. <a href="/privacy.php" target="_blank" rel="noopener">Политика обработки данных</a>.</label>
            <button type="submit" class="button">Отправить обращение</button>
          </form>
          <?php else:?><p>Электронная форма обращений будет доступна после публикации политики обработки персональных данных. Контакты указаны в правой колонке.</p><?php endif;?>
        </section>
        <?php elseif(isset($moduleLabels[$section]) && module_enabled($section)):
          $q=database()->prepare("SELECT * FROM content WHERE kind=? AND status='published' ORDER BY created_at DESC LIMIT 7");
          $q->execute([$section]);$items=$q->fetchAll();
          $covers=cms_content_image_map($items);
        ?>
        <section class="government-section government-section-<?=h($section)?>">
          <div class="government-section-title"><h2><?=h($section==='news'?$siteContent['news_title']:$moduleLabels[$section])?></h2><a href="/?kind=<?=h($section)?>">Все материалы →</a></div>
          <?php if($items):?><div class="government-materials">
            <?php foreach($items as $item):?><article class="government-material cms-government-material">
              <a class="cms-government-material-thumb" href="/?p=<?=rawurlencode($item['slug'])?>" aria-label="<?=h($item['title'])?>">
              <?php if(isset($covers[(int)$item['id']])):?>
                <img src="<?=h(cms_content_image_url((int)$item['id'],$covers[(int)$item['id']]))?>" alt="<?=h($covers[(int)$item['id']]['alt_text']?:$item['title'])?>" loading="lazy" decoding="async">
              <?php else:?>
                <span aria-hidden="true"><?=['page'=>'▤','news'=>'▣','service'=>'◇','product'=>'▦'][$item['kind']]?></span>
              <?php endif;?></a>
              <div class="cms-government-material-copy">
                <span class="government-material-kind"><?=h($moduleLabels[$section])?></span>
                <h3><a href="/?p=<?=rawurlencode($item['slug'])?>"><?=h($item['title'])?></a></h3>
                <p><?=h(mb_strimwidth((string)($item['summary']?:($item['body']??'')),0,170,'…','UTF-8'))?></p>
                <?php if($item['kind']==='product' && $item['price']!==null):?><strong class="price"><?=h(number_format((float)$item['price'],2,',',' '))?> ₽</strong><?php endif;?>
              </div>
              <time class="government-material-date" datetime="<?=h(date('Y-m-d',strtotime((string)$item['created_at'])?:time()))?>"><?=h(date('d.m.Y',strtotime((string)$item['created_at'])?:time()))?></time>
            </article><?php endforeach;?>
          </div><?php else:?><p class="government-empty">Материалы этого раздела появятся после публикации в панели управления.</p><?php endif;?>
        </section>
        <?php endif;?>
      <?php endforeach;?>
    <?php endif;?>
  </main>
  <?php if($govRight):?>
  <aside class="government-right" aria-label="Информация администрации">
    <?php if($gov['show_leader']==='1'):?>
    <section class="government-widget government-leader">
      <h2><?=h($gov['leader_title'])?></h2>
      <?php if($gov['leader_image']!==''):?><img class="government-leader-photo" src="<?=h($gov['leader_image'])?>" alt="<?=h($gov['leader_name']!==''?'Фотография: '.$gov['leader_name']:'Фотография руководителя')?>" loading="lazy"><?php endif;?>
      <?php if($gov['leader_initials']!==''):?><strong class="government-leader-initials"><?=h($gov['leader_initials'])?></strong><?php endif;?>
      <?php if($gov['leader_name']!==''):?><p class="government-leader-fullname"><?=h($gov['leader_name'])?></p><?php endif;?>
      <?php if($gov['leader_description']!==''):?><p><?=h($gov['leader_description'])?></p><?php endif;?>
    </section>
    <?php endif;?>
    <?php if($gov['show_schedule']==='1'):?>
    <section class="government-widget"><h2 class="government-widget-bar"><span aria-hidden="true">✉</span> <?=h($gov['schedule_title'])?></h2><p><?=nl2br(h($gov['schedule_text']))?></p><small><?=h($gov['working_hours'])?></small></section>
    <?php endif;?>
    <?php if($gov['show_announcements']==='1'):?>
    <section class="government-widget"><h2 class="government-widget-muted"><?=h($gov['announcements_title'])?></h2><p><?=nl2br(h($gov['announcements_text']))?></p>
      <?php if(module_enabled('news')):?><a href="/?kind=news">Все новости →</a><?php endif;?></section>
    <?php endif;?>
    <?php if($gov['show_links']==='1' && $gov['right_links']):?>
    <section class="government-widget government-widget-links"><h2><?=h($gov['links_title'])?></h2>
      <nav aria-label="Полезные ссылки"><?php foreach($gov['right_links'] as $link):?><a href="<?=h($link['url'])?>"><?=h($link['label'])?> <span>↗</span></a><?php endforeach;?></nav>
    </section>
    <?php endif;?>
    <?php if($gov['office_phone']!==''):?><div class="government-phone"><span>Телефон администрации</span><strong><?=h($gov['office_phone'])?></strong></div><?php endif;?>
    <?php if($govRightModules):?><section class="government-module-extra" aria-label="Модули справа">
    <?php foreach($govRightModules as $id):?><?php cms_module_sidebar($id,'government'); ?><?php endforeach;?>
    </section><?php endif;?>
  </aside>
  <?php endif;?>
</div>
<footer class="government-footer"><?=cms_age_mark()?><span>© <?=date('Y')?> <?=h($siteName)?>. <?=h($siteContent['footer_text'])?> <?=h($gov['footer_note'])?></span><span>Работает на DAG STUDIO CMS
<?php foreach(cms_module_ids('government','footer') as $id):?><?=cms_module_compact($id,'footer')?><?php endforeach;?></span></footer>
<div class="cms-legal-footer-links"><?=cms_privacy_links()?></div>
</div>
<?=cms_cookie_controls()?>
<?=cms_age_gate()?>
</body></html>
