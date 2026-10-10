<?php
declare(strict_types=1);
if(!defined('DAG_CMS_ADMIN_VIEW')){http_response_code(404);exit;}
$selected=(string)($_GET['tpl']??site_template());
if(!isset(template_catalog()[$selected]))$selected=site_template();
$c=cms_contacts($selected);
?>
<div class="eyebrow">НАСТРОЙКИ / КОНТАКТЫ</div>
<div class="heading-row"><div><h1>Контакты сайта</h1>
<p class="muted">Укажите телефоны, адреса, email, часы работы и дополнительные реквизиты. У каждого шаблона свои данные.</p></div><a href="/?module=contact" target="_blank" rel="noopener" class="button button-outline">Открыть страницу контактов <i class="fa-solid fa-arrow-up-right-from-square cms-icon-inline" aria-hidden="true"></i></a></div>
<nav class="cms-menu-tabs" aria-label="Выбор шаблона контактов">
<?php foreach(template_catalog() as $id=>$tpl):?>
<a href="?section=contacts&amp;tpl=<?=h($id)?>" class="<?=$selected===$id?'active':''?>"><?=h($tpl['label'])?> <?=$id===site_template()?'· активен':''?></a>
<?php endforeach;?></nav>
<section class="box form-panel cms-contact-settings">
<h2>Контакты: <?=h(template_catalog()[$selected]['label'])?></h2>
<form method="post" action="/admin/actions.php"><?=csrf()?>
<input type="hidden" name="action" value="save_contacts">
<input type="hidden" name="template" value="<?=h($selected)?>">
<div class="template-fields">
<label>Название контактного раздела <input name="title" required maxlength="140" value="<?=h($c['title'])?>"></label>
<label>Описание и приветствие<textarea name="intro" maxlength="1200" rows="3"><?=h($c['intro'])?></textarea></label>
</div>
<div class="cms-contact-edit-grid">
<label>Телефоны — по одному на строке
<textarea name="phones" rows="5" maxlength="5000" placeholder="+7 (000) 000-00-00"><?=h(cms_contact_lines($c['phones']))?></textarea></label>
<label>Электронная почта — по одному адресу на строке
<textarea name="emails" rows="5" maxlength="5000" placeholder="info@example.ru"><?=h(cms_contact_lines($c['emails']))?></textarea></label>
<label>Адреса — по одному адресу на строке
<textarea name="addresses" rows="5" maxlength="5000" placeholder="Республика Дагестан, ..."><?=h(cms_contact_lines($c['addresses']))?></textarea></label>
<label>Часы и график работы
<textarea name="hours" rows="5" maxlength="1000" placeholder="Пн–Пт: 09:00–18:00"><?=h($c['hours'])?></textarea></label>
</div>
<label>Другие сведения (например, приёмная, факс, дополнительный отдел)
<textarea name="details" rows="5" maxlength="5000" placeholder="Приёмная | +7 ...&#10;Отдел образования | ..."><?=h(cms_contact_details_lines($c['details']))?></textarea></label>
<p class="muted">Дополнительные сведения: каждая строка в формате «Название | Значение». Сохраняются отдельно для каждого шаблона. Номера телефонов и email на сайте будут кликабельными.</p>
<button type="submit" class="button">Сохранить контакты этого шаблона</button>
</form>
</section>
