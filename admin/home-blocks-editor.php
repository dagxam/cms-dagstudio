<?php
declare(strict_types=1);
if(!defined('DAG_CMS_ADMIN_VIEW')){http_response_code(404);exit;}
$homeTemplate=(string)($_GET['tpl']??site_template());
if(!isset(template_catalog()[$homeTemplate]))$homeTemplate=site_template();
$homeBlocks=cms_home_blocks($homeTemplate);
$homeEdit=(string)($_GET['block']??'');
$selectedBlock=null;
foreach($homeBlocks as $item)if($item['id']===$homeEdit)$selectedBlock=$item;
$showHomeForm=$homeEdit==='new'||$selectedBlock!==null;
$emptyHome=['id'=>'','type'=>'cards','placement'=>'top','enabled'=>true,
            'title'=>'','kicker'=>'','text'=>'','button'=>'','url'=>'','items'=>[]];
$editingHome=$selectedBlock??$emptyHome;
?>
<section class="template-edit-card box cms-home-editor" id="home-editor">
  <div class="template-edit-head"><span class="template-section-index"><i class="fa-solid fa-layer-group"></i></span><div>
    <h2>Собственные блоки главной</h2>
    <p class="muted">Создавайте текстовые блоки, карточки со ссылками, показатели и баннеры. Каждый тип сайта хранит свои блоки, их порядок и видимость отдельно.</p>
  </div></div>
  <nav class="cms-home-template-picker" aria-label="Шаблон для блоков">
    <?php foreach(template_catalog() as $key=>$info):?>
      <a class="<?=$homeTemplate===$key?'active':''?>" href="/admin/index.php?section=templates&amp;view=edit&amp;tab=blocks&amp;tpl=<?=h($key)?>"
         <?=$homeTemplate===$key?'aria-current="page"':''?>><?=h($info['label'])?></a>
    <?php endforeach;?>
  </nav>
  <div class="cms-home-toolbar">
    <div><strong><?=h(template_catalog()[$homeTemplate]['label'])?></strong>
      <p class="muted">Добавлено блоков: <?=count($homeBlocks)?> из 16. При переключении шаблона тексты остаются на своём месте.</p>
    </div>
    <div class="cms-home-toolbar-buttons">
      <a class="button button-outline" href="/?preview_template=<?=h($homeTemplate)?>" target="_blank" rel="noopener"><i class="fa-solid fa-eye" aria-hidden="true"></i> Предпросмотр</a>
      <?php if(count($homeBlocks)<16):?><a class="button" href="?section=templates&amp;view=edit&amp;tab=blocks&amp;tpl=<?=h($homeTemplate)?>&amp;block=new#home-editor"><i class="fa-solid fa-plus" aria-hidden="true"></i> Добавить блок</a><?php endif;?>
    </div>
  </div>
  <?php if(!$homeBlocks):?>
    <div class="cms-home-empty"><i class="fa-solid fa-layer-group" aria-hidden="true"></i><strong>Пока нет собственных блоков</strong><p>Нажмите «Добавить блок», выберите вид секции и заполните поля. Уже работающие блоки новостей и других модулей сохраняются.</p></div>
  <?php endif;?>
  <div class="cms-home-editor-list">
  <?php foreach($homeBlocks as $i=>$block):?>
    <article class="cms-home-editor-item <?=$homeEdit===$block['id']?'selected':''?>">
      <div class="cms-home-editor-number"><?=($i+1)?></div>
      <div class="cms-home-editor-summary">
        <strong><?=h($block['title'])?></strong>
        <div class="cms-home-editor-meta"><span><?=h(cms_home_types()[$block['type']])?></span>
          <span><?=h(cms_home_places()[$block['placement']])?></span>
          <span class="<?=$block['enabled']?'cms-home-status-on':'cms-home-status-off'?>"><?=$block['enabled']?'Показан':'Скрыт'?></span></div>
      </div>
      <div class="cms-home-item-actions">
        <a class="cms-home-action" href="?section=templates&amp;view=edit&amp;tab=blocks&amp;tpl=<?=h($homeTemplate)?>&amp;block=<?=h($block['id'])?>#home-editor" aria-label="Редактировать <?=h($block['title'])?>" title="Редактировать"><i class="fa-solid fa-pen"></i></a>
        <?php foreach(['up'=>'arrow-up','down'=>'arrow-down'] as $direction=>$icon):?>
        <form action="/admin/actions.php" method="post"><?=csrf()?>
          <input type="hidden" name="action" value="move_home_block"><input type="hidden" name="template" value="<?=h($homeTemplate)?>">
          <input type="hidden" name="id" value="<?=h($block['id'])?>"><input type="hidden" name="direction" value="<?=h($direction)?>">
          <button class="cms-home-action" type="submit" title="<?=$direction==='up'?'Поднять':'Опустить'?>" aria-label="<?=$direction==='up'?'Поднять':'Опустить'?> блок"><i class="fa-solid fa-<?=h($icon)?>"></i></button>
        </form>
        <?php endforeach;?>
        <form action="/admin/actions.php" method="post"><?=csrf()?>
          <input type="hidden" name="action" value="toggle_home_block"><input type="hidden" name="template" value="<?=h($homeTemplate)?>">
          <input type="hidden" name="id" value="<?=h($block['id'])?>">
          <button class="cms-home-action" type="submit" title="<?=$block['enabled']?'Скрыть':'Показать'?>" aria-label="<?=$block['enabled']?'Скрыть':'Показать'?> блок"><i class="fa-solid <?=$block['enabled']?'fa-eye-slash':'fa-eye'?>"></i></button>
        </form>
        <form action="/admin/actions.php" method="post" onsubmit="return confirm('Удалить этот блок без возможности восстановления?')"><?=csrf()?>
          <input type="hidden" name="action" value="delete_home_block"><input type="hidden" name="template" value="<?=h($homeTemplate)?>">
          <input type="hidden" name="id" value="<?=h($block['id'])?>">
          <button class="cms-home-action cms-home-delete" type="submit" title="Удалить" aria-label="Удалить блок"><i class="fa-solid fa-trash"></i></button>
        </form>
      </div>
    </article>
  <?php endforeach;?>
  </div>
  <?php if($showHomeForm):?>
  <form class="cms-home-edit-form" action="/admin/actions.php" method="post">
    <?=csrf()?><input type="hidden" name="action" value="save_home_block"><input type="hidden" name="template" value="<?=h($homeTemplate)?>">
    <input type="hidden" name="id" value="<?=h($editingHome['id'])?>">
    <div class="cms-home-form-heading"><h3><?=$selectedBlock?'Редактирование блока':'Новый блок'?></h3><a href="?section=templates&amp;view=edit&amp;tab=blocks&amp;tpl=<?=h($homeTemplate)?>#home-editor">Закрыть <i class="fa-solid fa-xmark"></i></a></div>
    <div class="template-fields">
      <label>Вид блока
        <select name="type" data-home-block-type>
          <?php foreach(cms_home_types() as $id=>$label):?><option value="<?=h($id)?>" <?=$editingHome['type']===$id?'selected':''?>><?=h($label)?></option><?php endforeach;?>
        </select>
      </label>
      <label>Расположение
        <select name="placement"><?php foreach(cms_home_places() as $id=>$label):?>
          <option value="<?=h($id)?>" <?=$editingHome['placement']===$id?'selected':''?>><?=h($label)?></option><?php endforeach;?></select>
      </label>
      <label class="check cms-home-checkbox"><input type="checkbox" name="enabled" value="1" <?=$editingHome['enabled']?'checked':''?>> Показывать на сайте</label>
      <label>Надзаголовок <input name="kicker" maxlength="80" value="<?=h($editingHome['kicker'])?>" placeholder="Например: НАШИ ВОЗМОЖНОСТИ"></label>
      <label>Название блока * <input name="title" maxlength="140" required value="<?=h($editingHome['title'])?>" placeholder="Название раздела"></label>
      <label>Основной текст <textarea name="text" maxlength="1000" rows="3"><?=h($editingHome['text'])?></textarea></label>
      <label>Текст кнопки (необязательно)<input name="button" maxlength="70" value="<?=h($editingHome['button'])?>" placeholder="Подробнее"></label>
      <label>Ссылка кнопки <input name="url" maxlength="300" value="<?=h($editingHome['url'])?>" placeholder="/?kind=news или https://..."></label>
    </div>
    <fieldset class="cms-home-items-editor" data-home-items-panel>
      <legend>Карточки или показатели (максимум 3)</legend>
      <p class="muted">Для карточек можно указать ссылку. Для показателей в поле «Название» пишите число, например 25+, а в описании — подпись. Пустые карточки не выводятся.</p>
      <div class="cms-home-item-fields">
      <?php for($i=0;$i<3;$i++):$item=$editingHome['items'][$i]??['title'=>'','text'=>'','url'=>''];?>
        <div class="cms-home-item-field">
          <strong>Элемент <?=$i+1?></strong>
          <label>Название или число <input name="items[<?=$i?>][title]" maxlength="100" value="<?=h($item['title'])?>"></label>
          <label>Описание <textarea name="items[<?=$i?>][text]" maxlength="300" rows="2"><?=h($item['text'])?></textarea></label>
          <label data-home-item-url>Ссылка (для карточек) <input name="items[<?=$i?>][url]" maxlength="300" value="<?=h($item['url'])?>" placeholder="/?kind=service"></label>
        </div>
      <?php endfor;?>
      </div>
    </fieldset>
    <div class="cms-home-form-actions">
      <button class="button" type="submit"><i class="fa-solid fa-floppy-disk"></i> <?=$selectedBlock?'Сохранить изменения':'Создать блок'?></button>
      <a class="button button-outline" href="?section=templates&amp;view=edit&amp;tab=blocks&amp;tpl=<?=h($homeTemplate)?>#home-editor">Отмена</a>
    </div>
  </form>
  <?php endif;?>
</section>
