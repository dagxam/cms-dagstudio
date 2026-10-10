<?php
declare(strict_types=1);
if(!defined('DAG_CMS_ADMIN_VIEW')){http_response_code(404);exit;}
$catalog=cms_modules();
$allTemplates=template_catalog();
$selected=(string)($_GET['tpl']??site_template());
if(!isset($allTemplates[$selected]))$selected=site_template();
$placements=cms_module_layout($selected);
$enabled=cms_enabled_module_keys();
?>
<div class="eyebrow">DAG STUDIO / ПОДКЛЮЧЕНИЕ И РАСПОЛОЖЕНИЕ</div>
<div class="heading-row"><div>
  <h1>Модули сайта</h1>
  <p class="muted">Включайте нужные функции и независимо настраивайте, где они отображаются в каждом шаблоне. Отключение не удаляет содержимое.</p>
</div><a class="button button-outline" href="/" target="_blank" rel="noopener"><i class="fa-solid fa-arrow-up-right-from-square cms-icon-inline" aria-hidden="true"></i> Посмотреть сайт</a></div>
<section class="box cms-module-manager">
  <div class="cms-module-manager-heading"><span class="template-section-index">01</span><div><h2>Подключение модулей</h2><p class="muted">Это общие переключатели для всех шаблонов. Выключенный модуль скрывается на сайте, но файлы и публикации остаются в базе.</p></div></div>
  <form method="post" action="/admin/actions.php">
  <?=csrf()?><input type="hidden" name="action" value="save_cms_modules">
  <div class="cms-modules-catalog">
   <?php foreach($catalog as $id=>$mod): ?>
   <label class="cms-modules-option">
    <span class="cms-modules-icon" aria-hidden="true"><?=cms_fa_icon($id)?></span>
    <span class="cms-modules-info"><strong><?=h($mod['label'])?></strong><small><?=h($mod['description'])?></small></span>
    <input type="checkbox" name="enabled[]" value="<?=h($id)?>" <?=in_array($id,$enabled,true)?'checked':''?>>
   </label>
   <?php endforeach;?>
  </div>
  <button class="button" type="submit">Сохранить подключённые модули</button>
  </form>
</section>
<section class="box cms-module-manager">
 <div class="cms-module-manager-heading"><span class="template-section-index">02</span><div>
   <h2>Расположение в шаблонах</h2>
   <p class="muted">Выберите шаблон и место для каждого модуля. Порядок определяет последовательность внутри выбранной зоны.</p>
 </div></div>
 <nav class="cms-module-template-tabs" aria-label="Выберите шаблон для настройки модулей">
 <?php foreach($allTemplates as $id=>$tpl):?>
   <a class="<?=$id===$selected?'active':''?>" <?=$id===$selected?'aria-current="page"':''?> href="/admin/index.php?section=modules&amp;tpl=<?=h($id)?>"><?=h($tpl['label'])?><?=$id===site_template()?' · активен':''?></a>
 <?php endforeach;?>
 </nav>
 <div class="cms-module-preview">
  <div class="cms-module-preview-top">Верхнее меню</div>
  <div class="cms-module-preview-cols">
   <div>Левая колонка</div><div>Центр страницы</div><div>Правая колонка</div>
  </div>
  <div class="cms-module-preview-footer">Подвал сайта</div>
 </div>
 <form method="post" action="/admin/actions.php">
 <?=csrf()?><input type="hidden" name="action" value="save_cms_layout">
 <input type="hidden" name="template" value="<?=h($selected)?>">
 <div class="cms-module-rows">
 <?php foreach($catalog as $id=>$mod):$choice=$placements[$id];?>
  <div class="cms-module-row">
    <div class="cms-module-row-title"><strong><?=h($mod['label'])?></strong>
       <span class="<?=$enabled && in_array($id,$enabled,true)?'cms-module-on':'cms-module-off'?>"><?=in_array($id,$enabled,true)?'Подключён':'Отключён глобально'?></span>
    </div>
    <label>Место вывода
      <select name="position[<?=h($id)?>][area]">
       <?php foreach(cms_module_areas() as $area=>$label):?>
        <option value="<?=h($area)?>" <?=$choice['area']===$area?'selected':''?>><?=h($label)?></option>
       <?php endforeach;?>
      </select>
    </label>
    <label>Порядок
      <input type="number" min="1" max="99" step="1" name="position[<?=h($id)?>][order]" value="<?=(int)$choice['order']?>" required>
    </label>
  </div>
 <?php endforeach;?>
 </div>
 <p class="muted">Если модуль поставлен в верхнее меню, боковую колонку или подвал, отображается ссылка на его страницу. В центральной части выводятся полноценные карточки и содержимое. Скрытие действует только для выбранного шаблона.</p>
 <button class="button" type="submit">Сохранить расположение для «<?=h($allTemplates[$selected]['label'])?>»</button>
 </form>
</section>
