<?php
declare(strict_types=1);
require __DIR__.'/app/core.php';
$p=cms_privacy();
if(!$p['ready']){http_response_code(503);header('X-Robots-Tag: noindex');}
$active=site_template();$design=template_design($active);$siteName=config_value('site_name','Сайт');
$title='Политика обработки персональных данных';
?>
<!doctype html><html lang="ru" data-theme-storage-key="dagstudio-template-<?=h($active)?>" data-theme-default="<?=h(template_default_mode($active))?>">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=h($title)?> — <?=h($siteName)?></title>
<link rel="stylesheet" href="/assets/style.css"><link rel="stylesheet" href="/assets/templates.css?v=dark6">
<link rel="stylesheet" href="/assets/media.css?v=privacy6">
<style id="dag-site-palettes"><?=template_palette_css($design,$active)?></style>
<script>try{const k='dagstudio-template-<?=h($active)?>';const t=localStorage.getItem(k);document.documentElement.dataset.theme=t==='dark'||t==='light'?t:'<?=h(template_default_mode($active))?>'}catch(e){document.documentElement.dataset.theme='<?=h(template_default_mode($active))?>'}</script>
<script src="/assets/theme.js" defer></script><script src="/assets/accessibility.js?v=a11y4" defer></script><script src="/assets/privacy.js?v=privacy4" defer></script>
</head><body class="site-page site-template-<?=h($active)?> cms-legal-page" <?=cms_accessibility_attributes()?> style="<?=h(template_style($design,$active))?>">
<a class="cms-skip-link" href="#cms-legal-main">Перейти к основному содержимому</a>
<header class="cms-legal-header"><a href="/">← На главную</a><strong><?=h($siteName)?></strong><div><?=cms_accessibility_control()?><button type="button" class="theme-toggle" data-theme-toggle aria-label="Переключить цветовую тему">☾/☼</button></div></header>
<main id="cms-legal-main" class="cms-legal-content">
<h1><?=h($title)?></h1>
<?php if(!$p['ready']):?>
<div class="cms-legal-alert" role="status">Политика ещё не опубликована владельцем сайта. Форма обращений должна оставаться недоступной до настройки документа.</div>
<?php else:?>
<p>Редакция: <?=h($p['version'])?>. Настоящая политика касается данных, которые посетители передают через формы этого сайта.</p>
<h2>1. Оператор персональных данных</h2>
<p><strong><?=h($p['operator'])?></strong><br>Адрес: <?=h($p['address'])?><br>Обращения по вопросам персональных данных: <a href="mailto:<?=h($p['email'])?>"><?=h($p['email'])?></a>.</p>
<h2>2. Цели и правовые основания</h2>
<p><?=nl2br(h($p['purpose']))?></p>
<p>Обработка осуществляется в соответствии с Федеральным законом от 27.07.2006 № 152-ФЗ «О персональных данных» на применимом правовом основании. Когда требуется согласие, оно запрашивается отдельно от настоящей политики.</p>
<h2>3. Состав обрабатываемых данных</h2>
<p>При отправке обращения: имя, адрес электронной почты, содержание сообщения; технические данные, необходимые для работы сессии и защиты форм. Не направляйте через обычную форму специальные категории данных, номера документов и иную избыточную информацию.</p>
<h2>4. Операции и сроки обработки</h2>
<p>Получение, запись, систематизация, хранение, уточнение, использование для ответа, удаление и уничтожение при достижении целей либо в предусмотренных законом случаях.</p><p><?=nl2br(h($p['retention']))?></p>
<h2>5. Передача данных и технологии сайта</h2>
<p><?=nl2br(h($p['processors']))?></p>
<p>Для функционирования сайта используются необходимые сессионные cookie. Встраиваемые ролики VK Видео и Rutube могут передавать данные этим сервисам, поэтому автоматическая загрузка таких роликов до отдельного выбора посетителя отключена.</p>
<h2>6. Права посетителя и отзыв согласия</h2>
<p>Можно запросить сведения об обработке данных, их уточнение, удаление при наличии законных оснований или отзыв согласия. Направьте обращение оператору по адресу <a href="mailto:<?=h($p['email'])?>"><?=h($p['email'])?></a>. Отзыв согласия не отменяет обработку, необходимую в силу иных законных оснований.</p>
<p>Отдельный документ: <a href="/consent.php">Согласие на обработку персональных данных</a>.</p>
<?php endif;?>
</main><footer class="cms-legal-footer"><?=cms_privacy_links()?> <span>© <?=date('Y')?> <?=h($siteName)?></span></footer>
<?=cms_cookie_controls()?>
</body></html>
