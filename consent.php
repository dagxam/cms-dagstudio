<?php
declare(strict_types=1);
require __DIR__.'/app/core.php';
$p=cms_privacy();$template=site_template();
if(!$p['ready']){http_response_code(503);header('X-Robots-Tag: noindex');}
$design=template_design($template);
?>
<!doctype html><html lang="ru" data-theme-storage-key="dagstudio-template-<?=h($template)?>" data-theme-default="<?=h(template_default_mode($template))?>">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Согласие на обработку персональных данных — <?=h(config_value('site_name'))?></title>
<link rel="stylesheet" href="/assets/style.css"><link rel="stylesheet" href="/assets/templates.css?v=dark6"><link rel="stylesheet" href="/assets/media.css?v=unified7">
<style id="dag-site-palettes"><?=template_palette_css($design,$template)?></style>
<script>try{const k='dagstudio-template-<?=h($template)?>';const t=localStorage.getItem(k);document.documentElement.dataset.theme=t==='light'||t==='dark'?t:'<?=h(template_default_mode($template))?>'}catch(e){document.documentElement.dataset.theme='<?=h(template_default_mode($template))?>'}</script>
<script src="/assets/theme.js" defer></script><script src="/assets/accessibility.js?v=a11y4" defer></script><script src="/assets/privacy.js?v=privacy4" defer></script>
</head><body class="site-page site-template-<?=h($template)?> cms-legal-page" <?=cms_accessibility_attributes()?> style="<?=h(template_style($design,$template))?>">
<a class="cms-skip-link" href="#cms-legal-main">Перейти к содержимому</a>
<?php cms_render_public_header($template); ?>
<main class="cms-legal-content" id="cms-legal-main"><h1>Согласие на обработку персональных данных</h1>
<?php if(!$p['ready']):?><div class="cms-legal-alert" role="status">Оператор ещё не опубликовал необходимые реквизиты и политику обработки данных.</div>
<?php else:?>
<p>Редакция: <?=h($p['version'])?>. Это отдельный документ, относящийся к форме обращения.</p>
<p><?=h(cms_consent_text($p))?></p>
<p>Согласие не распространяется на рекламную рассылку или публикацию персональных данных. Оно даётся путём самостоятельного выбора отдельного пустого флажка и отправки формы обращения.</p>
<p>Оператор: <?=h($p['operator'])?>. Адрес: <?=h($p['address'])?>. Отозвать согласие или запросить сведения: <a href="mailto:<?=h($p['email'])?>"><?=h($p['email'])?></a>.</p>
<p>Политика обработки данных: <a href="<?=h(cms_privacy_url())?>">читать документ</a>.</p>
<?php endif;?>
</main><footer class="cms-legal-footer"><?=cms_privacy_links()?><span>© <?=date('Y')?> <?=h(config_value('site_name'))?></span></footer><?=cms_cookie_controls()?>
</body></html>
