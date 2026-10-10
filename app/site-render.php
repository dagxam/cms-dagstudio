<?php
declare(strict_types=1);
// Этот файл подключается только из index.php после инициализации ядра и запросов контента.
if (!defined('DAG_CMS_SITE_VIEW')) { http_response_code(404); exit; }

$requestedTemplate = (string)($_GET['preview_template'] ?? '');
$previewMode = $requestedTemplate !== '' && isset(template_catalog()[$requestedTemplate]) && allowed('settings');
$activeTemplate = $previewMode ? $requestedTemplate : site_template();
$design = template_design($activeTemplate);
$siteContent = template_content($activeTemplate);
$layout = template_active_sections($activeTemplate);
$moduleLabels = kinds();
$siteLogo = $siteContent['logo_path'];
if (!preg_match('~^/assets/uploads/logo-[a-f0-9]{32}\\.(png|jpg|webp)$~D', $siteLogo)) $siteLogo = '';
$heroImage = (string)($siteContent['hero_image_path'] ?? '');
if (!preg_match('~^/assets/uploads/hero-[a-f0-9]{32}\\.(png|jpg|webp)$~D', $heroImage)) $heroImage = '';
$heroLogo=(string)($siteContent['hero_logo_path']??'');
if(!preg_match('~^/assets/uploads/hero-logo-[a-f0-9]{32}\\.(png|jpg|webp)$~D',$heroLogo))$heroLogo='';
$siteContacts=cms_contacts($activeTemplate);
$requestedWidget=(string)($_GET['module']??'');
$visibleSections = $kind !== '' ? [$kind] : ((in_array($requestedWidget,['features','contact'],true)&&cms_module_enabled($requestedWidget))?[$requestedWidget]:cms_module_ids($activeTemplate,'main'));
$visibleSections = array_values(array_filter($visibleSections, static fn(string $s): bool => cms_module_enabled($s)));
$leftModules=cms_module_ids($activeTemplate,'left');
$rightModules=cms_module_ids($activeTemplate,'right');
$siteClass = 'site-page site-template-' . $activeTemplate .
    ' site-hero-' . (in_array($design['hero'],['split','banner','official','centered'],true)?$design['hero']:'split') .
    ' site-cards-' . (in_array($design['cards'],['soft','outlined','elevated'],true)?$design['cards']:'soft') .
    ' site-header-' . (in_array($design['header'],['classic','catalog','official'],true)?$design['header']:'classic');
$navLinks = $siteContent['header_links'];
if ($activeTemplate === 'government') {
    require __DIR__ . '/government-render.php';
    return;
}
?>
<!doctype html>
<html lang="ru" data-theme-storage-key="dagstudio-template-<?=h($activeTemplate)?>" data-theme-default="<?=h(template_default_mode($activeTemplate))?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="description" content="<?=h(mb_substr($metaDescription,0,250))?>">
  <meta name="theme-color" content="<?=h($design['palettes'][template_default_mode($activeTemplate)]['background'])?>">
  <title><?=h($metaTitle)?></title>
  <link id="dag-favicon" rel="icon" type="image/svg+xml" href="/assets/ornament-dark.svg">
  <script>var dagDefaultTheme=<?=json_encode(template_default_mode($activeTemplate))?>;try{var dagSavedTheme=localStorage.getItem("dagstudio-template-<?=h($activeTemplate)?>");document.documentElement.dataset.theme=dagSavedTheme==="light"||dagSavedTheme==="dark"?dagSavedTheme:dagDefaultTheme}catch(e){document.documentElement.dataset.theme=dagDefaultTheme}</script>
  <script src="/assets/theme.js?v=palette4" defer></script>
  <link rel="stylesheet" href="/assets/style.css?v=templates3">
  <link rel="stylesheet" href="/assets/templates.css?v=contacts8">
  <link rel="stylesheet" href="/assets/media.css?v=unified7">
  <script src="/assets/accessibility.js?v=a11y4" defer></script>
<script src="/assets/privacy.js?v=privacy4" defer></script>
  <style id="dag-site-palettes"><?=template_palette_css($design,$activeTemplate)?></style>
</head>
<body class="<?=h($siteClass)?>"<?=cms_accessibility_attributes()?> style="<?=h(template_style($design,$activeTemplate))?>">
<a class="cms-skip-link" href="#cms-main-content">Перейти к основному содержимому</a>
<?php if($previewMode): ?><div class="site-preview-banner"><strong>Предпросмотр: <?=h(template_catalog()[$activeTemplate]['label'])?></strong> · Это предварительный вид. Шаблон не применён. <a href="/admin/index.php?section=templates">Вернуться к выбору</a></div><?php endif;?>
<?php cms_render_public_header($activeTemplate); ?>
<main id="cms-main-content" class="container site-body">
<?php if($record): ?>
  <?php $pageConfig=$record['kind']==='page'?cms_page_options((int)$record['id']):null; ?>
  <?php if($pageConfig):?><div class="cms-page-detail-layout">
  <?php if($pageConfig['left']):?><aside class="cms-page-detail-sidebar" aria-label="Боковые блоки слева"><?php foreach($pageConfig['left'] as $widget):cms_module_sidebar($widget,$activeTemplate);endforeach;?></aside><?php endif;?>
  <div class="cms-page-detail-center"><?php cms_page_module($pageConfig['before'],$activeTemplate); ?>
  <?php endif;?>
  <article class="article-page">
    <a class="back" href="/?kind=<?=h($record['kind'])?>"><i class="fa-solid fa-arrow-left cms-icon-inline" aria-hidden="true"></i> Назад к разделу</a>
    <div class="eyebrow"><?=h($moduleLabels[$record['kind']]??'Материал')?></div>
    <h1><?=h($record['title'])?></h1>
    <?php $articleCover=cms_content_image((int)$record['id']); if($articleCover):?>
    <figure class="cms-article-cover"><img src="<?=h(cms_content_image_url((int)$record['id'],$articleCover))?>" alt="<?=h($articleCover['alt_text']?:$record['title'])?>" loading="eager"></figure>
    <?php endif;?>
    <?php if($record['summary']): ?><p class="lead"><?=h($record['summary'])?></p><?php endif;?>
    <?php if($record['kind']==='product' && $record['price']!==null): ?><p class="price"><?=h(number_format((float)$record['price'],2,',',' '))?> ₽</p><?php endif;?>
    <div class="article-body"><?=nl2br(h($record['body']))?></div>
  </article>
  <?php if($pageConfig):cms_page_module($pageConfig['after'],$activeTemplate); ?></div>
  <?php if($pageConfig['right']):?><aside class="cms-page-detail-sidebar" aria-label="Боковые блоки справа"><?php foreach($pageConfig['right'] as $widget):cms_module_sidebar($widget,$activeTemplate);endforeach;?></aside><?php endif;?>
  </div><?php endif;?>
<?php elseif($slug!==''): ?>
  <section class="site-hero site-hero-error"><div class="eyebrow">404 / НЕ НАЙДЕНО</div><h1>Такой страницы нет</h1>
    <p class="lead">Возможно, материал ещё не опубликован.</p><a class="button" href="/">На главную</a></section>
<?php else: ?>
  <section class="site-hero" aria-labelledby="site-main-title">
    <div class="site-hero-copy">
      <div class="eyebrow"><?=h($kind!==''?$moduleLabels[$kind]:$siteContent['eyebrow'])?></div>
      <h1 id="site-main-title"><?=h($kind!==''?$moduleLabels[$kind]:$siteContent['title'])?></h1>
      <p class="lead"><?=h($kind!==''?$siteDescription:$siteContent['description'])?></p>
      <div class="hero-actions">
        <?php if($siteContent['cta']!==''): ?><a class="button" href="<?=h(safe_template_url($siteContent['cta_url'])&&$siteContent['cta_url']!==''?$siteContent['cta_url']:'#materials')?>"><?=h($siteContent['cta'])?> <i class="fa-solid fa-arrow-up-right-from-square cms-icon-inline" aria-hidden="true"></i></a><?php endif;?>
        <?php if($siteContent['secondary']!==''): ?><a class="button button-outline" href="<?=h(safe_template_url($siteContent['secondary_url'])&&$siteContent['secondary_url']!==''?$siteContent['secondary_url']:'#contact')?>"><?=h($siteContent['secondary'])?></a><?php endif;?>
      </div>
    </div>
    <div class="site-hero-visual <?=($heroImage!==''?'has-custom-hero ':'').($heroLogo!==''?'has-custom-logo':'')?>" aria-hidden="true">
      <?php if($heroImage!==''): ?><img class="site-custom-hero-image" src="<?=h($heroImage)?>" alt="" loading="eager"><?php endif;?>
      <div class="site-hero-visual-inner">
        <?php if($heroLogo!==''):?><img class="cms-custom-hero-logo" src="<?=h($heroLogo)?>" alt="" loading="eager">
        <?php else:?><img class="logo-on-dark" src="/assets/ornament-dark.svg" alt=""><img class="logo-on-light" src="/assets/ornament-light.svg" alt=""><?php endif;?>
      </div>
      <span><?=h(template_catalog()[$activeTemplate]['label'])?></span>
    </div>
  </section>

  <div id="materials" class="cms-module-layout <?=($leftModules||$rightModules)?'cms-module-layout-with-sidebars':''?> <?=($leftModules?'cms-has-left ':'').($rightModules?'cms-has-right':'')?>">
  <?php if($leftModules):?><aside class="cms-module-sidebar cms-module-sidebar-left" aria-label="Левая колонка модулей">
  <?php foreach($leftModules as $id):?><?php cms_module_sidebar($id,$activeTemplate); ?><?php endforeach;?></aside><?php endif;?>
  <div class="site-sections cms-module-main">
  <?php foreach($visibleSections as $section): ?>
    <?php if($section==='features'): ?>
    <section class="section-block site-block site-benefits" id="features">
      <div class="section-heading"><div><div class="eyebrow">НАШ ПОДХОД</div><h2><?=h($siteContent['features_title'])?></h2></div></div>
      <div class="cards site-feature-cards">
        <?php for($i=1;$i<=3;$i++): ?>
        <article class="box content-card site-feature-card"><span class="site-feature-mark" aria-hidden="true"><i class="fa-solid <?=['','fa-gem','fa-bolt','fa-shield-halved'][$i]?>" aria-hidden="true"></i></span>
          <h3><?=h($siteContent['feature_'.$i.'_title'])?></h3><p><?=h($siteContent['feature_'.$i.'_text'])?></p></article>
        <?php endfor;?>
      </div>
    </section>
    <?php elseif(in_array($section,['documents','photos','videos'],true)): ?>
      <?php cms_module_media_block($section); ?>
    <?php elseif($section==='contact'): ?>
    <section class="section-block contact-section site-block" id="contact">
      <div><div class="eyebrow">ОБРАТНАЯ СВЯЗЬ</div><h2><?=h($siteContacts['title'])?></h2>
        <?php if($siteContacts['intro']!==''):?><p class="muted"><?=nl2br(h($siteContacts['intro']))?></p><?php endif;?>
        <?=cms_render_contact_details($activeTemplate)?>
      </div>
      <?php if($privacyReady): ?><div class="box contact-form">
        <?php if($message): ?><div class="notice" role="status"><?=h($message)?></div><?php endif;?>
        <?php if($error): ?><div class="error" role="alert"><?=h($error)?></div><?php endif;?>
        <form method="post" action="/?module=contact#contact"><?=csrf()?>
          <input type="hidden" name="action" value="contact">
          <div class="honeypot" aria-hidden="true"><label>Сайт<input tabindex="-1" name="website" autocomplete="off"></label></div>
          <label>Ваше имя<input required maxlength="120" name="name" value="<?=h($_POST['name'] ?? '')?>"></label>
          <label>Электронная почта<input required type="email" name="email" value="<?=h($_POST['email'] ?? '')?>"></label>
          <label>Сообщение<textarea required minlength="10" maxlength="5000" rows="5" name="body"><?=h($_POST['body'] ?? '')?></textarea></label>
          <label class="check privacy-check"><input type="checkbox" name="consent" value="1" required> Даю отдельное согласие на обработку данных для ответа на обращение. <a href="/consent.php" target="_blank" rel="noopener">Текст согласия</a>. <a href="/privacy.php" target="_blank" rel="noopener">Политика обработки персональных данных</a>.</label>
          <button class="button" type="submit">Отправить сообщение</button>
        </form>
      </div><?php else: ?><div class="box site-contact-prompt"><p>Форма обращений станет доступна после публикации политики обработки персональных данных.</p></div><?php endif;?>
    </section>
    <?php elseif(isset($moduleLabels[$section]) && module_enabled($section)):
      $q=database()->prepare("SELECT * FROM content WHERE kind=? AND status='published' ORDER BY created_at DESC LIMIT 8");
      $q->execute([$section]);$items=$q->fetchAll();
      $covers=cms_content_image_map($items);
      $sectionTitle=$section==='news'?$siteContent['news_title']:$moduleLabels[$section];
    ?>
    <section class="section-block site-block site-block-<?=h($section)?>">
      <div class="section-heading"><div><div class="eyebrow"><?=h($moduleLabels[$section])?></div><h2><?=h($sectionTitle)?></h2></div>
        <a class="site-see-all" href="/?kind=<?=h($section)?>">Все материалы <i class="fa-solid fa-arrow-up-right-from-square cms-icon-inline" aria-hidden="true"></i></a></div>
      <div class="cards site-content-grid">
        <?php foreach($items as $item): ?>
        <a class="content-card box cms-visual-card" href="/?p=<?=rawurlencode($item['slug'])?>">
          <span class="cms-card-media">
          <?php if(isset($covers[(int)$item['id']])):?>
            <img src="<?=h(cms_content_image_url((int)$item['id'],$covers[(int)$item['id']]))?>" alt="<?=h($covers[(int)$item['id']]['alt_text']?:$item['title'])?>" loading="lazy" decoding="async">
          <?php else:?>
            <span class="cms-card-media-placeholder" aria-hidden="true"><?=cms_fa_icon($item['kind'])?></span>
          <?php endif;?>
          </span>
          <span class="cms-card-copy"><span class="card-symbol" aria-hidden="true"><?=cms_fa_icon($item['kind'])?></span>
          <span class="eyebrow"><?=h($moduleLabels[$item['kind']])?></span>
          <?php if($item['kind']==='news'):?>
          <time class="cms-card-date" datetime="<?=h(date('Y-m-d',strtotime((string)$item['created_at'])?:time()))?>"><?=h(date('d.m.Y',strtotime((string)$item['created_at'])?:time()))?></time>
          <?php endif;?>
          <h3><?=h($item['title'])?></h3>
          <p><?=h(mb_strimwidth((string)($item['summary'] ?: ($item['body'] ?? '')),0,180,'…','UTF-8'))?></p>
          <?php if($item['kind']==='product' && $item['price']!==null): ?><span class="price"><?=h(number_format((float)$item['price'],2,',',' '))?> ₽</span><?php endif;?>
          <span class="card-link"><?=h(['product'=>'Посмотреть товар','service'=>'Подробнее об услуге','news'=>'Читать новость','page'=>'Открыть страницу'][$item['kind']])?> <span><i class="fa-solid fa-arrow-up-right-from-square cms-icon-inline" aria-hidden="true"></i></span></span>
          </span>
        </a>
        <?php endforeach;?>
      </div>
      <?php if(!$items): ?><div class="box empty-state">Материалы появятся здесь после публикации в панели управления.</div><?php endif;?>
    </section>
    <?php endif;?>
  <?php endforeach;?>
  </div>
  <?php if($rightModules):?><aside class="cms-module-sidebar cms-module-sidebar-right" aria-label="Правая колонка модулей">
  <?php foreach($rightModules as $id):?><?php cms_module_sidebar($id,$activeTemplate); ?><?php endforeach;?></aside><?php endif;?>
  </div>
<?php endif;?>
</main>
<footer class="site-footer"><div class="container footer-inner"><?=cms_age_mark()?>
  <div><strong><?=h($siteName)?></strong><br><?=h($siteContent['footer_text'])?></div>
  <div><?=date('Y')?> · Работает на <strong>DAG STUDIO CMS</strong>
  <?php foreach(cms_module_ids($activeTemplate,'footer') as $id):?><?=cms_module_compact($id,'footer')?><?php endforeach;?></div>
</div><div class="container cms-social-footer"><?=cms_render_social_links($activeTemplate,'footer')?></div><div class="container cms-legal-footer-links"><?=cms_privacy_links()?></div></footer>
<?=cms_cookie_controls()?>
<?=cms_age_gate()?>
</body></html>
