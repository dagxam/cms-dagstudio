<?php
declare(strict_types=1);
if(!defined('DAG_CMS_ADMIN_VIEW')||site_template()!=='government'){http_response_code(404);exit;}
$govMainLayout=cms_module_layout('government');
$govStyles=cms_government_home_settings();
$govOrder=cms_government_home_order();
$govBlocks=[];
foreach(cms_home_blocks('government') as $block)$govBlocks[$block['id']]=$block;
$enabledModules=cms_enabled_module_keys();
?>
<div class="eyebrow">КОНСТРУКТОР / ОФИЦИАЛЬНЫЙ САЙТ</div>
<div class="gov-composer-intro">
 <div class="gov-composer-intro-icon" aria-hidden="true"><i class="fa-solid fa-landmark"></i></div>
 <div>
  <h2>Главная страница администрации</h2>
  <p>Расположите любой подключённый модуль в центре, колонках, меню или подвале. Настройте заголовки, оформление и порядок отображения, не изменяя остальные темы.</p>
 </div>
 <a class="button button-outline" href="/" target="_blank" rel="noopener"><i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i> Открыть сайт</a>
</div>
<div class="gov-composer-shortcuts">
 <a href="/admin/index.php?section=templates&amp;view=edit&amp;tab=government"><i class="fa-solid fa-sliders" aria-hidden="true"></i><span><strong>Шапка и колонки</strong><small>Логотип, баннер, навигация, руководитель</small></span></a>
 <a href="/admin/index.php?section=templates&amp;view=edit&amp;tab=blocks&amp;tpl=government&amp;block=new#home-editor"><i class="fa-solid fa-square-plus" aria-hidden="true"></i><span><strong>Создать свой блок</strong><small>Текст, баннер, карточки и показатели</small></span></a>
 <a href="/admin/index.php?section=templates&amp;view=edit&amp;tab=leader#government-leader"><i class="fa-solid fa-user-tie" aria-hidden="true"></i><span><strong>Глава администрации</strong><small>Фотография, ФИО, инициалы и должность</small></span></a>
 <a href="/admin/index.php?section=modules&amp;tpl=government"><i class="fa-solid fa-puzzle-piece" aria-hidden="true"></i><span><strong>Подключить модули</strong><small>Включение функций без удаления материалов</small></span></a>
</div>
<form class="gov-composer-form" method="post" action="/admin/actions.php" data-gov-composer>
 <?=csrf()?>
 <input type="hidden" name="action" value="save_government_home">
 <section class="template-edit-card box gov-composer-panel">
  <div class="gov-composer-head"><div class="gov-composer-step">01</div><div>
    <h3>Модули и места размещения</h3>
    <p>Измените место вывода любого модуля. Для центральной части ниже можно переставлять секции стрелками. Отключённые глобально модули появятся после включения в разделе «Модули».</p>
  </div></div>
  <div class="gov-composer-modules">
  <?php foreach(cms_modules() as $moduleId=>$module):
    $choice=$govMainLayout[$moduleId];
    $active=in_array($moduleId,$enabledModules,true);
    $style=$govStyles[$moduleId];
  ?>
   <article class="gov-composer-module <?=$active?'':'is-disabled'?>">
    <span class="gov-composer-module-icon" aria-hidden="true"><?=cms_fa_icon($moduleId)?></span>
    <div class="gov-composer-module-top">
      <strong><?=h($module['label'])?></strong>
      <small><?=h($module['description'])?></small>
      <?php if(!$active):?><span class="gov-composer-disabled-note"><i class="fa-solid fa-circle-info" aria-hidden="true"></i> Модуль отключён в общих настройках</span><?php endif;?>
    </div>
    <div class="gov-composer-module-fields">
      <label>Размещение
        <select name="position[<?=h($moduleId)?>][area]" data-gov-area data-module-id="<?=h($moduleId)?>" data-module-label="<?=h($module['label'])?>" data-module-icon="<?=h(strip_tags(cms_fa_icon($moduleId)))?>" data-is-enabled="<?=$active?'1':'0'?>">
          <?php foreach(cms_module_areas() as $area=>$name):?>
            <option value="<?=h($area)?>" <?=$choice['area']===$area?'selected':''?>><?=h($name)?></option>
          <?php endforeach;?>
        </select>
      </label>
      <label>Приоритет в других зонах
        <input type="number" name="position[<?=h($moduleId)?>][order]" min="1" max="99" step="1" value="<?=(int)$choice['order']?>" required>
      </label>
      <label>Оформление центра
        <select name="style[<?=h($moduleId)?>]">
          <?php foreach(cms_government_home_looks() as $styleId=>$styleName):?>
            <option value="<?=h($styleId)?>" <?=$style['style']===$styleId?'selected':''?>><?=h($styleName)?></option>
          <?php endforeach;?>
        </select>
      </label>
      <label>Заголовок на главной
        <input name="title[<?=h($moduleId)?>]" maxlength="120" value="<?=h($style['title'])?>" placeholder="<?=h($module['label'])?>">
      </label>
    </div>
   </article>
  <?php endforeach;?>
  </div>
 </section>
 <section class="template-edit-card box gov-composer-panel" id="gov-composer-order">
  <div class="gov-composer-head"><div class="gov-composer-step">02</div><div>
    <h3>Порядок блоков на главной</h3>
    <p>Стрелками перемещайте секции вверх и вниз. Созданные в CMS собственные блоки можно располагать среди встроенных модулей. Скрытые блоки не выводятся.</p>
  </div></div>
  <div class="gov-composer-preview-heading">
    <i class="fa-solid fa-desktop" aria-hidden="true"></i>
    <strong>Предпросмотр последовательности центральной колонки</strong>
    <small>Содержимое появится на сайте после сохранения</small>
  </div>
  <div class="gov-composer-order-list" data-gov-order-list aria-live="polite">
  <?php foreach($govOrder as $token):
    $moduleToken=str_starts_with($token,'m:');
    $key=substr($token,2);
    if($moduleToken && !isset(cms_modules()[$key]))continue;
    if(!$moduleToken && !isset($govBlocks[$key]))continue;
    $label=$moduleToken?(cms_government_home_resolve_title($key,cms_module_label($key))):$govBlocks[$key]['title'];
    $icon=$moduleToken?cms_fa_icon($key):'<i class="fa-solid fa-layer-group" aria-hidden="true"></i>';
  ?>
    <div class="gov-composer-order-row" data-gov-token="<?=h($token)?>">
      <input type="hidden" name="home_order[]" value="<?=h($token)?>">
      <div class="gov-composer-order-grip"><i class="fa-solid fa-grip-lines" aria-hidden="true"></i></div>
      <span class="gov-composer-order-icon" aria-hidden="true"><?=$icon?></span>
      <div class="gov-composer-order-label"><strong><?=h($label)?></strong><small><?=$moduleToken?'Встроенный модуль':'Собственный блок'?></small></div>
      <?php if(!$moduleToken):?>
        <a class="gov-composer-order-edit" href="/admin/index.php?section=templates&amp;view=edit&amp;tab=blocks&amp;tpl=government&amp;block=<?=h($key)?>#home-editor" aria-label="Редактировать собственный блок"><i class="fa-solid fa-pen"></i></a>
      <?php endif;?>
      <div class="gov-composer-arrows">
        <button type="button" data-gov-move="up" aria-label="Поднять <?=h($label)?>" title="Выше"><i class="fa-solid fa-arrow-up"></i></button>
        <button type="button" data-gov-move="down" aria-label="Опустить <?=h($label)?>" title="Ниже"><i class="fa-solid fa-arrow-down"></i></button>
      </div>
    </div>
  <?php endforeach;?>
  </div>
  <p class="gov-composer-empty" data-gov-order-empty hidden>В центральной колонке пока нет модулей. Выберите «Центральная часть» у любого подключённого модуля или создайте собственный блок.</p>
 </section>
 <div class="gov-composer-savebar">
   <div><strong>Сохранить оформление главной</strong><small>Обновятся только настройки шаблона «Администрация». Материалы и настройки остальных шаблонов останутся на месте.</small></div>
   <button class="button" type="submit"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Сохранить главную</button>
 </div>
</form>
