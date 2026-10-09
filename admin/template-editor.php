<?php
declare(strict_types=1);
if (!defined('DAG_CMS_ADMIN_VIEW')) { http_response_code(404); exit; }
$catalog = template_catalog();
$selectedTemplate = site_template();
$design = template_design();
$content = template_content();
$sections = template_active_sections();
?>
<div class="eyebrow">DAG STUDIO / ДИЗАЙН САЙТА</div>
<div class="heading-row">
  <div><h1>Шаблоны и оформление</h1>
    <p class="muted">Выберите основу сайта, затем настройте каждый блок под свои задачи. Материалы и пользователи сохраняются при смене шаблона.</p></div>
  <a class="button button-outline" href="/" target="_blank" rel="noopener">↗ Посмотреть сайт</a>
</div>
<h2 class="template-heading">1. Выберите шаблон</h2>
<div class="template-gallery">
<?php foreach ($catalog as $code => $tpl): ?>
  <article class="template-choice box <?=$selectedTemplate===$code?'is-selected':''?>">
    <?php $samplePalettes=template_design($code)['palettes']; $sampleMode=template_default_mode($code); $sample=$samplePalettes[$sampleMode]; ?>
    <div class="template-visual template-visual-<?=h($code)?>" style="--sample-accent:<?=h($sample['accent'])?>;--sample-bg:<?=h($sample['background'])?>;--sample-ink:<?=h($sample['ink'])?>">
      <div class="template-mini-top"><span class="template-mini-mark">◈</span><span class="template-mini-lines">━━━━ &nbsp; ━━━ &nbsp; ━━</span></div>
      <div class="template-mini-hero">
        <span class="template-mini-kicker"><?=h($tpl['eyebrow'])?></span>
        <strong><?=h($tpl['title'])?></strong>
        <i></i>
      </div>
      <div class="template-mini-tiles"><span></span><span></span><span></span></div>
    </div>
    <div class="template-choice-palettes" aria-label="Цвета светлой и тёмной темы">
      <span><i style="--swatch:<?=h($samplePalettes['light']['background'])?>"></i> Светлая</span>
      <span><i style="--swatch:<?=h($samplePalettes['dark']['background'])?>"></i> Тёмная</span>
    </div>
    <div class="template-choice-body">
      <div class="template-choice-name"><h3><?=h($tpl['label'])?></h3>
        <?php if($selectedTemplate===$code):?><span class="tag tag-green">Активный</span><?php endif;?></div>
      <p class="template-choice-sub"><?=h($tpl['caption'])?></p>
      <p class="muted template-choice-description"><?=h($tpl['description'])?></p>
      <div class="template-choice-actions">
        <form method="post" action="/admin/actions.php"><?=csrf()?>
          <input type="hidden" name="action" value="select_template">
          <input type="hidden" name="template" value="<?=h($code)?>">
          <button class="button <?=$selectedTemplate===$code?'button-outline':''?>" type="submit" <?=$selectedTemplate===$code?'disabled':''?>><?=$selectedTemplate===$code?'Выбран':'Применить шаблон'?></button>
        </form>
        <a href="/?preview_template=<?=h($code)?>" class="template-preview-link" target="_blank" rel="noopener">Предпросмотр ↗</a>
      </div>
    </div>
  </article>
<?php endforeach; ?>
</div>

<div class="template-edit-grid">
  <section class="template-edit-card box">
    <div class="template-edit-head"><span class="template-section-index">02</span><div><h2>Внешний вид</h2><p class="muted">Отдельные палитры для светлого и тёмного режима, шрифт и композиция.</p></div></div>
    <form method="post" action="/admin/actions.php"><?=csrf()?>
      <input type="hidden" name="action" value="save_template_design">
      <p class="muted template-tip">У выбранного шаблона собственные цвета для светлого и тёмного режимов. Изменения одной темы не затрагивают другую.</p>
      <div class="template-palette-grid">
        <?php foreach (['light'=>'Светлая тема','dark'=>'Тёмная тема'] as $mode=>$title):
          $palette=$design['palettes'][$mode];
        ?>
        <fieldset class="template-palette-panel" data-palette-panel="<?=h($mode)?>">
          <legend><span class="template-mode-symbol" aria-hidden="true"><?=$mode==='light'?'☼':'☾'?></span> <?=h($title)?></legend>
          <div class="template-palette-preview" data-palette-preview
            style="--preview-accent:<?=h($palette['accent'])?>;--preview-bg:<?=h($palette['background'])?>;--preview-ink:<?=h($palette['ink'])?>;--preview-surface:<?=h($palette['surface'])?>;--preview-border:<?=h($palette['border'])?>">
            <div class="template-palette-demo-header"><span>◈ <?=h($catalog[$selectedTemplate]['label'])?></span><span>☰</span></div>
            <div class="template-palette-demo-content"><strong>Пример оформления</strong><p>Заголовок и текст на фоне выбранной темы.</p>
              <span class="template-palette-demo-button">Подробнее →</span>
              <span class="template-palette-demo-tile">Карточка содержимого</span>
            </div>
          </div>
          <div class="template-palette-inputs">
            <?php foreach (['accent'=>'Акцентный цвет','background'=>'Фон сайта','ink'=>'Цвет текста','surface'=>'Карточки и меню','border'=>'Границы блоков'] as $key=>$label): ?>
            <label><?=h($label)?><span class="template-palette-color-line">
              <input type="color" name="palette[<?=h($mode)?>][<?=h($key)?>]" value="<?=h($palette[$key])?>" data-palette-color="<?=h($key)?>" data-default-color="<?=h(template_default_palettes($selectedTemplate)[$mode][$key])?>">
              <span class="template-palette-hex"><?=h(strtoupper($palette[$key]))?></span>
            </span></label>
            <?php endforeach; ?>
          </div>
          <div class="template-palette-bottom">
            <span class="template-palette-contrast" data-palette-contrast></span>
            <button type="button" class="template-palette-reset" data-palette-reset>Вернуть стандартные цвета</button>
          </div>
        </fieldset>
        <?php endforeach; ?>
      </div>
      <div class="template-fields">
        <label>Шрифт интерфейса<select name="font">
          <?php foreach(['montserrat'=>'Montserrat','manrope'=>'Manrope','system'=>'Системный','georgia'=>'Georgia (с засечками)'] as $key=>$label): ?>
          <option value="<?=h($key)?>" <?=$design['font']===$key?'selected':''?>><?=h($label)?></option><?php endforeach; ?>
        </select></label>
        <label>Первый экран<select name="hero">
          <?php foreach(['split'=>'Две колонки','banner'=>'Широкая витрина','official'=>'Официальный заголовок','centered'=>'По центру'] as $key=>$label):?>
          <option value="<?=h($key)?>" <?=$design['hero']===$key?'selected':''?>><?=h($label)?></option><?php endforeach;?>
        </select></label>
        <label>Карточки материалов<select name="cards">
          <?php foreach(['soft'=>'Мягкие','outlined'=>'Контурные','elevated'=>'С тенью'] as $key=>$label): ?>
          <option value="<?=h($key)?>" <?=$design['cards']===$key?'selected':''?>><?=h($label)?></option><?php endforeach;?>
        </select></label>
        <label>Верхнее меню<select name="header">
          <?php foreach(['classic'=>'Классическое','catalog'=>'С акцентом на каталог','official'=>'Официальное'] as $key=>$label): ?>
          <option value="<?=h($key)?>" <?=$design['header']===$key?'selected':''?>><?=h($label)?></option><?php endforeach;?>
        </select></label>
        <label>Скругление элементов<select name="radius">
          <?php foreach(['0'=>'Без скругления','6'=>'6 px','12'=>'12 px','16'=>'16 px','18'=>'18 px','24'=>'24 px'] as $key=>$label): ?>
          <option value="<?=h($key)?>" <?=$design['radius']===$key?'selected':''?>><?=h($label)?></option><?php endforeach;?>
        </select></label>
        <label>Ширина контента<select name="width">
          <?php foreach(['1120'=>'Узкая • 1120 px','1240'=>'Обычная • 1240 px','1320'=>'Широкая • 1320 px','1380'=>'Широкая + • 1380 px','1480'=>'Максимальная • 1480 px'] as $key=>$label): ?>
          <option value="<?=h($key)?>" <?=$design['width']===$key?'selected':''?>><?=h($label)?></option><?php endforeach;?>
        </select></label>
      </div>
      <p class="muted template-tip">Вы можете изменить любой параметр, не затрагивая опубликованные материалы и базу данных.</p>
      <button class="button template-save" type="submit">Сохранить цвета и оформление</button>
    </form>
  </section>

  <section class="template-edit-card box">
    <div class="template-edit-head"><span class="template-section-index">03</span><div><h2>Содержимое и бренд</h2><p class="muted">Заголовки, логотип, кнопки и контактная информация.</p></div></div>
    <form method="post" action="/admin/actions.php" enctype="multipart/form-data"><?=csrf()?>
      <input type="hidden" name="action" value="save_template_content">
      <div class="template-fields">
        <label>Логотип сайта (PNG, JPG или WebP до 2 МБ)
          <input type="file" accept="image/png,image/jpeg,image/webp" name="site_logo"></label>
        <?php if($content['logo_path']!==''): ?>
          <div class="template-current-logo"><img src="<?=h($content['logo_path'])?>" alt="Текущий логотип"><label class="check"><input type="checkbox" name="remove_logo" value="1"> Убрать логотип</label></div>
        <?php endif;?>
        <label>Изображение для первого экрана (PNG, JPG или WebP до 5 МБ)
          <input type="file" accept="image/png,image/jpeg,image/webp" name="hero_image"></label>
        <?php if($content['hero_image_path']!==''): ?>
          <div class="template-current-logo"><img src="<?=h($content['hero_image_path'])?>" alt="Текущий фон первого экрана"><label class="check"><input type="checkbox" name="remove_hero_image" value="1"> Убрать фоновое изображение</label></div>
        <?php endif;?>
        <label>Надпись над заголовком<input name="eyebrow" maxlength="140" value="<?=h($content['eyebrow'])?>"></label>
        <label>Заголовок первого экрана<input name="title" maxlength="190" value="<?=h($content['title'])?>" required></label>
        <label class="template-field-wide">Описание первого экрана<textarea name="description" rows="3" maxlength="700"><?=h($content['description'])?></textarea></label>
        <label>Главная кнопка<input name="cta" maxlength="70" value="<?=h($content['cta'])?>"></label>
        <label>Адрес главной кнопки<input name="cta_url" maxlength="300" value="<?=h($content['cta_url'])?>" placeholder="/?kind=service"></label>
        <label>Вторая кнопка<input name="secondary" maxlength="70" value="<?=h($content['secondary'])?>"></label>
        <label>Адрес второй кнопки<input name="secondary_url" maxlength="300" value="<?=h($content['secondary_url'])?>" placeholder="#contact"></label>
        <label>Заголовок преимуществ<input name="features_title" maxlength="140" value="<?=h($content['features_title'])?>"></label>
        <label>Заголовок новостей<input name="news_title" maxlength="140" value="<?=h($content['news_title'])?>"></label>
        <label>Заголовок контактов<input name="contact_title" maxlength="140" value="<?=h($content['contact_title'])?>"></label>
        <label>Текст в подвале<input name="footer_text" maxlength="220" value="<?=h($content['footer_text'])?>"></label>
        <label>Телефон организации<input name="phone" maxlength="45" value="<?=h($content['phone'])?>" placeholder="+7 (999) 000-00-00"></label>
        <label>Адрес организации<input name="address" maxlength="240" value="<?=h($content['address'])?>"></label>
      </div>
      <h3 class="template-subheading">Три информационных карточки</h3>
      <div class="template-feature-edit">
        <?php for($i=1;$i<=3;$i++): ?>
        <div class="template-feature-edit-item">
          <strong>Карточка <?=$i?></strong>
          <label>Название<input name="feature_<?=$i?>_title" maxlength="90" value="<?=h($content['feature_'.$i.'_title'])?>"></label>
          <label>Описание<textarea name="feature_<?=$i?>_text" rows="2" maxlength="240"><?=h($content['feature_'.$i.'_text'])?></textarea></label>
        </div>
        <?php endfor; ?>
      </div>
      <h3 class="template-subheading">Дополнительные пункты меню</h3>
      <div class="template-fields">
        <?php for($i=0;$i<4;$i++):
          $link=$content['header_links'][$i]??['label'=>'','url'=>''];
          if (!is_array($link)) $link=['label'=>'','url'=>''];
        ?>
        <label>Название ссылки <?=$i+1?><input name="nav_label[]" maxlength="50" value="<?=h((string)($link['label']??''))?>" placeholder="О нас"></label>
        <label>Адрес ссылки <?=$i+1?><input name="nav_url[]" maxlength="300" value="<?=h((string)($link['url']??''))?>" placeholder="/?p=about"></label>
        <?php endfor; ?>
      </div>
      <button class="button template-save" type="submit">Сохранить содержимое</button>
    </form>
  </section>
</div>

<section class="template-edit-card box template-sections-panel">
  <div class="template-edit-head"><span class="template-section-index">04</span><div><h2>Блоки главной страницы</h2><p class="muted">Включайте, выключайте и меняйте порядок отображения. Пустые разделы не показывают фиктивные публикации.</p></div></div>
  <form method="post" action="/admin/actions.php"><?=csrf()?>
    <input type="hidden" name="action" value="save_template_sections">
    <div class="template-section-order">
      <?php foreach(template_sections() as $key=>$label):
        $pos=array_search($key,$sections,true);
        $position=$pos===false?count($sections)+1:$pos+1;
      ?>
      <div class="template-section-order-item">
        <label class="check"><input type="checkbox" name="active_sections[]" value="<?=h($key)?>" <?=$pos!==false?'checked':''?>>
          <span><?=h($label)?></span></label>
        <label>Порядок <select name="section_order[<?=h($key)?>]">
          <?php for($i=1;$i<=6;$i++): ?><option value="<?=$i?>" <?=$position===$i?'selected':''?>><?=$i?></option><?php endfor;?>
        </select></label>
      </div>
      <?php endforeach;?>
    </div>
    <button class="button template-save" type="submit">Сохранить расположение блоков</button>
  </form>
</section>

<?php if ($selectedTemplate === 'government'): ?>
<?php require __DIR__ . '/government-editor.php'; ?>
<?php endif; ?>
