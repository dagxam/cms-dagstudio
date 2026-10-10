<?php
declare(strict_types=1);
if(!defined('DAG_CMS_ADMIN_VIEW')){http_response_code(404);exit;}
$selected=(string)($_GET['tpl']??site_template());
if(!isset(template_catalog()[$selected]))$selected=site_template();
$s=cms_socials($selected);
?>
<div class="eyebrow">НАСТРОЙКИ / СОЦИАЛЬНЫЕ СЕТИ</div>
<div class="heading-row"><div><h1>Социальные сети</h1>
<p class="muted">Ссылки для каждого шаблона настраиваются независимо. Пустые ссылки не появляются на сайте.</p></div><a href="/" target="_blank" rel="noopener" class="button button-outline">Посмотреть сайт ↗</a></div>
<nav class="cms-menu-tabs" aria-label="Выбор шаблона соцсетей">
<?php foreach(template_catalog() as $id=>$tpl):?>
<a href="?section=socials&amp;tpl=<?=h($id)?>" class="<?=$selected===$id?'active':''?>"><?=h($tpl['label'])?> <?=$id===site_template()?'· активен':''?></a>
<?php endforeach;?></nav>
<section class="box form-panel cms-social-settings">
<h2>Социальные сети: <?=h(template_catalog()[$selected]['label'])?></h2>
<form method="post" action="/admin/actions.php"><?=csrf()?>
<input type="hidden" name="action" value="save_socials">
<input type="hidden" name="template" value="<?=h($selected)?>">
<label class="check cms-social-toggle"><input type="checkbox" name="enabled" value="1" <?=$s['enabled']?'checked':''?>> Отображать социальные сети на сайте</label>
<label>Где показывать ссылки
<select name="location">
<option value="both" <?=$s['location']==='both'?'selected':''?>>На странице контактов и в подвале</option>
<option value="contact" <?=$s['location']==='contact'?'selected':''?>>Только на странице контактов</option>
<option value="footer" <?=$s['location']==='footer'?'selected':''?>>Только в подвале сайта</option>
</select></label>
<div class="cms-social-editor-grid">
<?php foreach(cms_social_catalog() as $id=>$social):?>
<label class="cms-social-editor-row"><span class="cms-social-symbol" aria-hidden="true"><i class="<?=h($social['icon'])?>"></i></span>
<span><?=h($social['name'])?><input name="social[<?=h($id)?>]" type="url" maxlength="500" placeholder="https://<?=h($social['host'][0])?>/..." value="<?=h($s['links'][$id]??'')?>"></span></label>
<?php endforeach;?></div>
<p class="muted">Принимаются только HTTPS-адреса соответствующей площадки. Можно включить VK, Telegram, MAX, Rutube, Одноклассники, Дзен, YouTube, WhatsApp и Instagram. Сохраняются только введённые ссылки, без автоматической загрузки внешних виджетов.</p>
<button type="submit" class="button">Сохранить социальные сети</button>
</form>
</section>
