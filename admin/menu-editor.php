<?php
declare(strict_types=1);
if(!defined('DAG_CMS_ADMIN_VIEW')){http_response_code(404);exit;}
$menuTpl=(string)($_GET['tpl']??site_template());
if(!isset(template_catalog()[$menuTpl]))$menuTpl=site_template();
?>
<div class="eyebrow">НАВИГАЦИЯ ПО ШАБЛОНАМ</div><div class="heading-row"><div>
<h1>Главное меню сайта</h1><p class="muted">У каждого шаблона собственные пункты меню. Переключение темы не стирает ссылки других вариантов.</p>
</div><a class="button button-outline" href="/" target="_blank" rel="noopener">Просмотр сайта <i class="fa-solid fa-arrow-up-right-from-square cms-icon-inline" aria-hidden="true"></i></a></div>
<nav class="cms-menu-tabs" aria-label="Шаблоны меню"><?php foreach(template_catalog() as $id=>$tpl):?>
<a class="<?=$id===$menuTpl?'active':''?>" href="?section=menus&amp;tpl=<?=h($id)?>"><?=h($tpl['label'])?> <?=$id===site_template()?'· активен':''?></a>
<?php endforeach;?></nav>
<div class="box form-panel cms-menu-editor">
<h2>Меню: <?=h(template_catalog()[$menuTpl]['label'])?></h2>
<p class="muted">Введите по одной строке «Название | /адрес», расположите строки в нужном порядке. Для разделов подходят /?kind=news, /?kind=page, /media.php?type=document, /media.php?type=photo, /media.php?type=video и внешние HTTPS-ссылки.</p>
<form method="post" action="/admin/actions.php"><?=csrf()?>
<input type="hidden" name="action" value="save_template_menu"><input type="hidden" name="template" value="<?=h($menuTpl)?>">
<label>Пункты меню <textarea name="menu_lines" rows="13" maxlength="15000" placeholder="Главная | /&#10;Новости | /?kind=news"><?=h(cms_menu_lines($menuTpl))?></textarea></label>
<p class="muted">До 20 пунктов. Модули, настроенные для области «Верхнее меню», дополняют это меню без повторения одинаковых ссылок.</p>
<button class="button" type="submit">Сохранить меню этого шаблона</button>
</form></div>
