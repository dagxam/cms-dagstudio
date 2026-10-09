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
$visibleSections = $kind !== '' ? [$kind] : $layout;
$visibleSections = array_values(array_filter($visibleSections, static fn(string $s): bool =>
    $s === 'features' || $s === 'contact' || (array_key_exists($s, $moduleLabels) && module_enabled($s))
));
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
  <link rel="stylesheet" href="/assets/templates.css?v=palette4">
  <link rel="stylesheet" href="/assets/media.css?v=legal2">
  <script src="/assets/accessibility.js?v=legal2" defer></script>
  <style id="dag-site-palettes"><?=template_palette_css($design,$activeTemplate)?></style>
</head>
<body class="<?=h($siteClass)?>"<?=cms_accessibility_attributes()?> style="<?=h(template_style($design,$activeTemplate))?>">
<?php if($previewMode): ?><div class="site-preview-banner"><strong>Предпросмотр: <?=h(template_catalog()[$activeTemplate]['label'])?></strong> · Это предварительный вид. Шаблон не применён. <a href="/admin/index.php?section=templates">Вернуться к выбору</a></div><?php endif;?>
<?php if ($activeTemplate==='government'): ?><div class="site-official-bar"><div class="container">ОФИЦИАЛЬНЫЙ САЙТ <span>Информация для граждан и организаций</span></div></div><?php endif;?>
<header class="site-header"><div class="container header-inner">
  <a class="brand site-brand" href="/">
    <?php if ($siteLogo!==''): ?><img class="site-uploaded-logo" src="<?=h($siteLogo)?>" alt="" loading="eager"><?php else: ?>
      <span class="brand-symbol" aria-hidden="true"><img class="logo-on-dark" src="/assets/ornament-dark.svg" alt=""><img class="logo-on-light" src="/assets/ornament-light.svg" alt=""></span>
    <?php endif; ?>
    <span><?=h($siteName)?></span>
  </a>
  <nav class="site-nav" aria-label="Главное меню">
    <a href="/" <?=$kind===''?'class="current"':''?>>Главная</a>
    <?php foreach ($moduleLabels as $key=>$label): if(!module_enabled($key))continue; ?>
    <a href="/?kind=<?=h($key)?>" <?=$kind===$key?'class="current"':''?>><?=h($label)?></a>
    <?php endforeach;?>
    <a href="/media.php">Медиатека</a>
    <?php foreach ($navLinks as $link): if(!is_array($link) || !safe_template_url((string)($link['url']??'')))continue; ?>
    <a href="<?=h((string)$link['url'])?>"><?=h((string)($link['label']??''))?></a>
    <?php endforeach;?>
  </nav>
  <div class="header-actions">
    <?=cms_accessibility_control()?>
    <?=cms_age_mark()?>
    <button type="button" class="theme-toggle" data-theme-toggle aria-label="Переключить тему" title="Переключить тему"><span class="theme-toggle-dark" aria-hidden="true">☾</span><span class="theme-toggle-light" aria-hidden="true">☼</span></button>
    <?php if($activeTemplate==='store' && module_enabled('product')): ?><a class="small-link shop-shortcut" href="/?kind=product">Каталог ↗</a><?php endif;?>
    <a class="small-link" href="/admin/login.php">Вход</a>
  </div>
</div></header>
<main class="container site-body">
<?php if($record): ?>
  <article class="article-page">
    <a class="back" href="/?kind=<?=h($record['kind'])?>">← Назад к разделу</a>
    <div class="eyebrow"><?=h($moduleLabels[$record['kind']]??'Материал')?></div>
    <h1><?=h($record['title'])?></h1>
    <?php if($record['summary']): ?><p class="lead"><?=h($record['summary'])?></p><?php endif;?>
    <?php if($record['kind']==='product' && $record['price']!==null): ?><p class="price"><?=h(number_format((float)$record['price'],2,',',' '))?> ₽</p><?php endif;?>
    <div class="article-body"><?=nl2br(h($record['body']))?></div>
  </article>
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
        <?php if($siteContent['cta']!==''): ?><a class="button" href="<?=h(safe_template_url($siteContent['cta_url'])&&$siteContent['cta_url']!==''?$siteContent['cta_url']:'#materials')?>"><?=h($siteContent['cta'])?> ↗</a><?php endif;?>
        <?php if($siteContent['secondary']!==''): ?><a class="button button-outline" href="<?=h(safe_template_url($siteContent['secondary_url'])&&$siteContent['secondary_url']!==''?$siteContent['secondary_url']:'#contact')?>"><?=h($siteContent['secondary'])?></a><?php endif;?>
      </div>
    </div>
    <div class="site-hero-visual <?=$heroImage!==''?'has-custom-hero':''?>" aria-hidden="true">
      <?php if($heroImage!==''): ?><img class="site-custom-hero-image" src="<?=h($heroImage)?>" alt="" loading="eager"><?php endif;?>
      <div class="site-hero-visual-inner"><img class="logo-on-dark" src="/assets/ornament-dark.svg" alt=""><img class="logo-on-light" src="/assets/ornament-light.svg" alt=""></div>
      <span><?=h(template_catalog()[$activeTemplate]['label'])?></span>
    </div>
  </section>

  <div id="materials" class="site-sections">
  <?php foreach($visibleSections as $section): ?>
    <?php if($section==='features'): ?>
    <section class="section-block site-block site-benefits">
      <div class="section-heading"><div><div class="eyebrow">НАШ ПОДХОД</div><h2><?=h($siteContent['features_title'])?></h2></div></div>
      <div class="cards site-feature-cards">
        <?php for($i=1;$i<=3;$i++): ?>
        <article class="box content-card site-feature-card"><span class="site-feature-mark" aria-hidden="true"><?=['','◇','✧','⬡'][$i]?></span>
          <h3><?=h($siteContent['feature_'.$i.'_title'])?></h3><p><?=h($siteContent['feature_'.$i.'_text'])?></p></article>
        <?php endfor;?>
      </div>
    </section>
    <?php elseif($section==='contact'): ?>
    <section class="section-block contact-section site-block" id="contact">
      <div><div class="eyebrow">ОБРАТНАЯ СВЯЗЬ</div><h2><?=h($siteContent['contact_title'])?></h2>
        <p class="muted">Свяжитесь с нами по указанным контактам или оставьте сообщение через сайт.</p>
        <?php if($siteContent['phone']!==''): ?><p><strong>Телефон:</strong> <?=h($siteContent['phone'])?></p><?php endif;?>
        <?php if($siteContent['address']!==''): ?><p><strong>Адрес:</strong> <?=h($siteContent['address'])?></p><?php endif;?>
        <p><?=h(config_value('contact_email'))?></p>
      </div>
      <?php if($privacyUrl): ?><div class="box contact-form">
        <?php if($message): ?><div class="notice"><?=h($message)?></div><?php endif;?>
        <?php if($error): ?><div class="error"><?=h($error)?></div><?php endif;?>
        <form method="post" action="/#contact"><?=csrf()?>
          <input type="hidden" name="action" value="contact">
          <div class="honeypot" aria-hidden="true"><label>Сайт<input tabindex="-1" name="website" autocomplete="off"></label></div>
          <label>Ваше имя<input required maxlength="120" name="name" value="<?=h($_POST['name'] ?? '')?>"></label>
          <label>Электронная почта<input required type="email" name="email" value="<?=h($_POST['email'] ?? '')?>"></label>
          <label>Сообщение<textarea required minlength="10" maxlength="5000" rows="5" name="body"><?=h($_POST['body'] ?? '')?></textarea></label>
          <label class="check privacy-check"><input type="checkbox" name="consent" value="1" required> Даю согласие на обработку данных согласно <a href="<?=h($privacyUrl)?>" target="_blank" rel="noopener noreferrer">политике обработки персональных данных</a>.</label>
          <button class="button" type="submit">Отправить сообщение</button>
        </form>
      </div><?php else: ?><div class="box site-contact-prompt"><p>Форма обращений станет доступна после публикации политики обработки персональных данных.</p></div><?php endif;?>
    </section>
    <?php elseif(isset($moduleLabels[$section]) && module_enabled($section)):
      $q=database()->prepare("SELECT * FROM content WHERE kind=? AND status='published' ORDER BY created_at DESC LIMIT 8");
      $q->execute([$section]);$items=$q->fetchAll();
      $sectionTitle=$section==='news'?$siteContent['news_title']:$moduleLabels[$section];
    ?>
    <section class="section-block site-block site-block-<?=h($section)?>">
      <div class="section-heading"><div><div class="eyebrow"><?=h($moduleLabels[$section])?></div><h2><?=h($sectionTitle)?></h2></div>
        <a class="site-see-all" href="/?kind=<?=h($section)?>">Все материалы ↗</a></div>
      <div class="cards site-content-grid">
        <?php foreach($items as $item): ?>
        <a class="content-card box" href="/?p=<?=rawurlencode($item['slug'])?>">
          <span class="card-symbol" aria-hidden="true"><?=['page'=>'▤','news'=>'▣','service'=>'◇','product'=>'▦'][$item['kind']]?></span>
          <span class="eyebrow"><?=h($moduleLabels[$item['kind']])?></span>
          <h3><?=h($item['title'])?></h3>
          <p><?=h(mb_strimwidth((string)($item['summary'] ?: ($item['body'] ?? '')),0,180,'…','UTF-8'))?></p>
          <?php if($item['kind']==='product' && $item['price']!==null): ?><span class="price"><?=h(number_format((float)$item['price'],2,',',' '))?> ₽</span><?php endif;?>
          <span class="card-link">Подробнее <span>↗</span></span>
        </a>
        <?php endforeach;?>
      </div>
      <?php if(!$items): ?><div class="box empty-state">Материалы появятся здесь после публикации в панели управления.</div><?php endif;?>
    </section>
    <?php endif;?>
  <?php endforeach;?>
  </div>
<?php endif;?>
</main>
<footer class="site-footer"><div class="container footer-inner">
  <div><strong><?=h($siteName)?></strong><br><?=h($siteContent['footer_text'])?></div>
  <div><?=date('Y')?> · Работает на <strong>DAG STUDIO CMS</strong></div>
</div></footer>
<?=cms_age_gate()?>
</body></html>
