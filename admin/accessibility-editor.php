<?php
declare(strict_types=1);
if(!defined('DAG_CMS_ADMIN_VIEW')){http_response_code(404);exit;}
$vision=cms_accessibility();$rating=cms_age_rating();
?>
<div class="eyebrow">НАСТРОЙКИ ДОСТУПНОСТИ И МАРКИРОВКИ</div>
<div class="heading-row"><div><h1>Версия для слабовидящих и возрастные ограничения</h1>
<p class="muted">Единые параметры для всех шаблонов, включая официальный сайт администрации. Посетитель может включить и отключить режим самостоятельно.</p></div></div>
<div class="box form-panel">
<form method="post" action="/admin/actions.php"><?=csrf()?>
<input type="hidden" name="action" value="save_compliance">
<fieldset><legend>Возрастная маркировка сайта</legend>
<label>Возрастная категория по 436-ФЗ
<select name="site_age_rating"><?php foreach(['0+','6+','12+','16+','18+'] as $age):?><option value="<?=h($age)?>" <?=$rating===$age?'selected':''?>><?=h($age)?></option><?php endforeach;?></select></label>
<p class="muted">Знак отображается на публичных страницах. Для 18+ добавляется окно подтверждения совершеннолетия. Это не проверка документов: ответственность за правильную классификацию контента остаётся у владельца сайта.</p>
</fieldset>
<fieldset><legend>Версия для слабовидящих</legend>
<label class="check"><input type="checkbox" name="vision_enabled" value="1" <?=$vision['enabled']==='1'?'checked':''?>> Показывать кнопку «Для слабовидящих»</label>
<div class="two">
<label>Увеличение текста
<select name="font_scale"><?php foreach(['125','150','175','200'] as $size):?><option value="<?=h($size)?>" <?=$vision['font_scale']===$size?'selected':''?>><?=h($size)?>%</option><?php endforeach;?></select></label>
<label>Контраст
<select name="contrast"><?php foreach(['high'=>'Повышенный контраст','blackwhite'=>'Чёрный на белом','yellowblack'=>'Жёлтый на чёрном'] as $value=>$label):?><option value="<?=h($value)?>" <?=$vision['contrast']===$value?'selected':''?>><?=h($label)?></option><?php endforeach;?></select></label>
<label>Межстрочный интервал
<select name="line_spacing"><option value="normal" <?=$vision['line_spacing']==='normal'?'selected':''?>>Обычный</option><option value="wide" <?=$vision['line_spacing']==='wide'?'selected':''?>>Увеличенный</option></select></label>
<label>Интервал между буквами
<select name="letter_spacing"><option value="normal" <?=$vision['letter_spacing']==='normal'?'selected':''?>>Обычный</option><option value="wide" <?=$vision['letter_spacing']==='wide'?'selected':''?>>Увеличенный</option></select></label>
</div>
<label class="check"><input type="checkbox" name="show_images" value="1" <?=$vision['show_images']==='1'?'checked':''?>> Сохранять изображения в специальной версии</label>
<label class="check"><input type="checkbox" name="underlines" value="1" <?=$vision['underlines']==='1'?'checked':''?>> Подчёркивать ссылки</label>
<label class="check"><input type="checkbox" name="grayscale" value="1" <?=$vision['grayscale']==='1'?'checked':''?>> Переводить в оттенки серого</label>
<p class="muted">Режим использует семантическую разметку, доступные кнопки и навигацию с клавиатуры. Перед вводом муниципального сайта в эксплуатацию обязательны отдельное тестирование при масштабе браузера 200%, проверка программами экранного доступа и доступности самих PDF-файлов.</p>
</fieldset>
<button class="button" type="submit">Сохранить параметры</button></form></div>
