<?php
declare(strict_types=1);
if (!defined('DAG_CMS_ADMIN_VIEW') || site_template() !== 'government') { http_response_code(404); exit; }
$gov=government_layout();
$govLabels=[
 'show_topbar'=>'Верхняя строка с адресом и временем',
 'show_banner'=>'Баннер в шапке',
 'show_search'=>'Поисковая строка и быстрые ссылки',
 'show_date'=>'Дата и часы',
 'show_accessibility'=>'Кнопка для слабовидящих',
 'show_leader'=>'Блок руководителя',
 'show_schedule'=>'График приёма',
 'show_announcements'=>'Анонсы',
 'show_links'=>'Полезные ссылки',
];
?>
<section class="template-edit-card box government-editor" id="government-editor">
<div class="template-edit-head">
  <span class="template-section-index">05</span>
  <div><h2>Конструктор администрации</h2>
    <p class="muted">Редактируйте верхнюю панель, логотип, боковые колонки и их содержимое. Всё, что показано на официальном сайте, берётся из настроек.</p>
  </div>
</div>
<div class="government-editor-help">
 <strong>Оформление по образцу официального сайта</strong>
 <p>Логотип и фотография верхнего баннера загружаются выше в разделе «Содержимое и бренд». Цвета синей навигации, фона, текста и границ меняются в «Внешний вид» отдельно для светлой и тёмной темы.</p>
 <a href="/" target="_blank" rel="noopener">Посмотреть текущий сайт ↗</a>
</div>
<form method="post" action="/admin/actions.php" enctype="multipart/form-data">
 <?=csrf()?><input type="hidden" name="action" value="save_government_layout">
 <fieldset class="government-editor-group">
   <legend>Расположение колонок</legend>
   <div class="template-fields">
    <label>Схема главной страницы
      <select name="layout">
      <?php foreach(['both'=>'Меню слева, информация справа','swap'=>'Информация слева, меню справа','left'=>'Только левое меню','right'=>'Только правые блоки','none'=>'Без боковых колонок'] as $value=>$label):?>
      <option value="<?=h($value)?>" <?=$gov['layout']===$value?'selected':''?>><?=h($label)?></option>
      <?php endforeach;?>
      </select>
    </label>
    <?php foreach(['left_width'=>'Ширина колонки меню','right_width'=>'Ширина информационной колонки'] as $key=>$label):?>
    <label><?=h($label)?>
      <select name="<?=h($key)?>">
        <?php foreach(['200','220','240','260','280','300'] as $px):?>
        <option value="<?=$px?>" <?=$gov[$key]===$px?'selected':''?>><?=$px?> px</option>
        <?php endforeach;?>
      </select>
    </label>
    <?php endforeach;?>
   </div>
 </fieldset>
 <fieldset class="government-editor-group">
  <legend>Шапка и верхняя информационная панель</legend>
  <div class="template-fields">
   <?php foreach([
    'name'=>'Надпись над названием сайта',
    'name_detail'=>'Подпись под названием',
    'location'=>'Адрес администрации',
    'working_hours'=>'Режим работы',
    'office_phone'=>'Телефон',
    'top_note'=>'Надпись на баннере',
    'banner_title'=>'Заголовок баннера',
    'banner_text'=>'Подзаголовок баннера'
   ] as $key=>$label):?>
   <label><?=h($label)?><input name="<?=h($key)?>" maxlength="240" value="<?=h($gov[$key])?>"></label>
   <?php endforeach;?>
  </div>
 </fieldset>
 <fieldset class="government-editor-group">
  <legend>Поиск и интернет-приёмная</legend>
  <div class="template-fields">
    <?php foreach([
      'search_placeholder'=>'Подсказка в поиске',
      'search_button'=>'Название кнопки поиска',
      'quick_title'=>'Название кнопки приёмной',
      'quick_url'=>'Ссылка приёмной'
    ] as $key=>$label):?>
    <label><?=h($label)?><input name="<?=h($key)?>" maxlength="300" value="<?=h($gov[$key])?>"></label>
    <?php endforeach;?>
    <label class="template-field-wide">Быстрые ссылки возле поиска
      <textarea name="quick_links" rows="4" maxlength="12000"><?=h(government_links_as_text($gov['quick_links']))?></textarea>
      <small>По одной ссылке в строке: Название | /?kind=page. Порядок строк = порядок на сайте.</small>
    </label>
  </div>
 </fieldset>
 <fieldset class="government-editor-group">
  <legend>Левое меню и центральная колонка</legend>
  <div class="template-fields">
    <label>Заголовок бокового меню<input name="left_title" maxlength="190" value="<?=h($gov['left_title'])?>"></label>
    <label>Заголовок центральной колонки<input name="center_title" maxlength="190" value="<?=h($gov['center_title'])?>"></label>
    <label>Дополнительная подпись в подвале<input name="footer_note" maxlength="240" value="<?=h($gov['footer_note'])?>"></label>
    <label class="template-field-wide">Разделы левого меню
      <textarea name="left_menu" rows="11" maxlength="12000"><?=h(government_links_as_text($gov['left_menu']))?></textarea>
      <small>Можно добавлять, удалять, переименовывать и переставлять до 24 пунктов. Ссылки: /?kind=news, /?kind=page, /?p=slug, #contact или https://...</small>
    </label>
    <label class="template-field-wide">Текст на главной странице
      <textarea name="center_intro" rows="5" maxlength="5000"><?=h($gov['center_intro'])?></textarea>
    </label>
  </div>
 </fieldset>
 <fieldset class="government-editor-group">
  <legend>Правый информационный столбец</legend>
  <div class="template-fields">
    <label>Заголовок руководителя<input name="leader_title" maxlength="190" value="<?=h($gov['leader_title'])?>"></label>
    <label>ФИО руководителя<input name="leader_name" maxlength="190" value="<?=h($gov['leader_name'])?>"></label>
    <label class="template-field-wide">Описание должности / краткая информация
      <textarea name="leader_description" rows="2" maxlength="5000"><?=h($gov['leader_description'])?></textarea>
    </label>
    <label>Фотография руководителя (PNG/JPG/WebP, до 2 МБ)
      <input type="file" name="leader_photo" accept="image/png,image/jpeg,image/webp">
    </label>
    <?php if($gov['leader_image']!==''):?>
    <div class="template-current-logo"><img src="<?=h($gov['leader_image'])?>" alt="Фото руководителя">
      <label class="check"><input type="checkbox" value="1" name="remove_leader_photo"> Удалить фотографию</label></div>
    <?php endif;?>
    <label>Заголовок графика приёма<input name="schedule_title" maxlength="190" value="<?=h($gov['schedule_title'])?>"></label>
    <label>Заголовок анонсов<input name="announcements_title" maxlength="190" value="<?=h($gov['announcements_title'])?>"></label>
    <label class="template-field-wide">График приёма
      <textarea name="schedule_text" rows="3" maxlength="5000"><?=h($gov['schedule_text'])?></textarea></label>
    <label class="template-field-wide">Текст блока анонсов
      <textarea name="announcements_text" rows="3" maxlength="5000"><?=h($gov['announcements_text'])?></textarea></label>
    <label>Заголовок полезных ссылок<input name="links_title" maxlength="190" value="<?=h($gov['links_title'])?>"></label>
    <label class="template-field-wide">Полезные ссылки в правой колонке
      <textarea name="right_links" rows="6" maxlength="12000"><?=h(government_links_as_text($gov['right_links']))?></textarea>
      <small>По одной ссылке в строке: Название | /адрес. Можно добавлять до 12 ссылок.</small>
    </label>
  </div>
 </fieldset>
 <fieldset class="government-editor-group">
  <legend>Включить или скрыть элементы</legend>
  <div class="government-visibility-grid">
   <?php foreach($govLabels as $key=>$label):?>
   <label class="check"><input type="checkbox" name="<?=h($key)?>" value="1" <?=$gov[$key]==='1'?'checked':''?>>
    <span><?=h($label)?></span></label>
   <?php endforeach;?>
  </div>
 </fieldset>
 <button type="submit" class="button template-save">Сохранить настройки администрации</button>
</form>
</section>
