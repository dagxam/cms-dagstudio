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
<p class="muted">Каждая основная строка задаёт пункт меню. Чтобы добавить подменю, начинайте следующую строку с двух пробелов и дефиса. Переставляйте строки для сортировки. Для разделов подходят /?kind=news, /?kind=page, /media.php?type=document, /media.php?type=photo, /media.php?type=video и внешние HTTPS-ссылки.</p>
<form method="post" action="/admin/actions.php"><?=csrf()?>
<input type="hidden" name="action" value="save_template_menu"><input type="hidden" name="template" value="<?=h($menuTpl)?>">
<label>Пункты меню <textarea name="menu_lines" rows="15" maxlength="15000" placeholder="Главная | /&#10;Каталог | /?kind=product&#10;  - Все товары | /?kind=product&#10;  - Акции | /?kind=product&amp;sort=price_asc"><?=h(cms_menu_lines($menuTpl))?></textarea></label>
<p class="muted">До 20 основных пунктов и 10 подпунктов на каждый. Пример: «Каталог | /?kind=product» и следующей строкой «  - Новинки | /?kind=product». Модули, настроенные для области «Верхнее меню», дополняют это меню без повторения одинаковых ссылок.</p>
<button class="button" type="submit">Сохранить меню этого шаблона</button>
</form></div>

<?php
$menuModuleEntries=cms_menu_module_entries($menuTpl);
$menuModuleSelected=[];
foreach($menuModuleEntries as $entry)$menuModuleSelected[$entry['id']]=$entry;
$menuModuleParents=cms_menu_for_template($menuTpl);
?>
<section class="box cms-menu-modules">
  <div class="cms-menu-modules-heading">
    <span class="cms-menu-modules-heading-icon"><i class="fa-solid fa-puzzle-piece" aria-hidden="true"></i></span>
    <div><h2>Модули в главном меню — <?=h(template_catalog()[$menuTpl]['label'])?></h2>
    <p class="muted">Добавляйте подключённые модули прямо в меню этого шаблона. Их расположение в колонках или на главной странице не изменится. Каждый шаблон настраивается независимо.</p></div>
  </div>
  <form method="post" action="/admin/actions.php">
    <?=csrf()?>
    <input type="hidden" name="action" value="save_menu_modules">
    <input type="hidden" name="template" value="<?=h($menuTpl)?>">
    <div class="cms-menu-module-grid">
      <?php foreach(cms_modules() as $id=>$module):
        $chosen=$menuModuleSelected[$id]??null;
        $available=cms_module_enabled($id);
      ?>
      <article class="cms-menu-module-card <?=$available?'':'cms-menu-module-unavailable'?>">
        <div class="cms-menu-module-top">
          <span class="cms-menu-module-icon" aria-hidden="true"><?=cms_fa_icon($id)?></span>
          <div class="cms-menu-module-title"><strong><?=h($module['label'])?></strong>
            <small><?=h($module['description'])?></small></div>
          <?php if(!$available):?><span class="cms-menu-module-state">Отключён</span><?php endif;?>
        </div>
        <label class="cms-menu-module-check">
          <input type="checkbox" name="menu_modules[<?=h($id)?>][enabled]" value="1"
            <?=$chosen!==null&&$available?'checked':''?> <?=$available?'':'disabled'?>>
          <span>Показывать в главном меню</span>
        </label>
        <div class="cms-menu-module-fields">
          <label>Порядок
            <input type="number" name="menu_modules[<?=h($id)?>][order]" min="1" max="999"
              value="<?=(int)($chosen['order']??100)?>" <?=$available?'':'disabled'?>>
          </label>
          <label>Расположение ссылки
            <select name="menu_modules[<?=h($id)?>][parent]" <?=$available?'':'disabled'?>>
              <option value="">Основное меню</option>
              <?php foreach($menuModuleParents as $parent):
                if($parent['url']===cms_module_href($id))continue;
              ?>
                <option value="<?=h($parent['url'])?>" <?=($chosen['parent']??'')===$parent['url']?'selected':''?>>
                  Подменю: <?=h($parent['label'])?>
                </option>
              <?php endforeach;?>
            </select>
          </label>
        </div>
      </article>
      <?php endforeach;?>
    </div>
    <div class="cms-menu-modules-actions">
      <p class="muted">Отключённый в CMS модуль не показывается на сайте. Для его подключения перейдите в <a href="/admin/index.php?section=modules"><strong>«Модули»</strong></a>. Порядок задаётся числом: меньшее значение — раньше в меню. Для подменю сначала сохраните родительский пункт выше.</p>
      <button class="button" type="submit"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Сохранить модули меню «<?=h(template_catalog()[$menuTpl]['label'])?>»</button>
    </div>
  </form>
</section>
