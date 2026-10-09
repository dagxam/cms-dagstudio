<?php
declare(strict_types=1);
if(!defined('DAG_CMS_ADMIN_VIEW')){http_response_code(404);exit;}
$mediaItems=cms_media_list(false);
?>
<div class="eyebrow">ЕДИНАЯ МЕДИАТЕКА</div>
<div class="heading-row"><div><h1>Файлы, документы, фото и видео</h1><p class="muted">Материалы доступны во всех четырёх шаблонах. Перед публикацией укажите название, описание, возрастную категорию и альтернативный текст для изображений.</p></div><a class="button button-outline" href="/media.php" target="_blank" rel="noopener">Открыть медиатеку ↗</a></div>
<div class="box form-panel cms-media-upload">
<h2>Загрузить материал</h2>
<form method="post" action="/admin/actions.php" enctype="multipart/form-data">
<?=csrf()?><input type="hidden" name="action" value="upload_media">
<div class="two">
<label>Раздел<select name="category" required>
<option value="document">Документы и файлы</option><option value="photo">Фотоальбом</option><option value="video">Видеогалерея</option></select></label>
<label>Возрастная маркировка<select name="age_rating"><?php foreach(['0+','6+','12+','16+','18+'] as $age):?><option value="<?=h($age)?>"><?=h($age)?></option><?php endforeach;?></select></label>
</div>
<label>Название<input name="title" maxlength="190" required></label>
<label>Описание материала<textarea name="description" rows="3" maxlength="2000" placeholder="Для документов и видео добавьте краткое текстовое описание."></textarea></label>
<label>Альтернативный текст изображения<input name="alt_text" maxlength="300" placeholder="Что изображено на фотографии?"></label>
<label>Выбрать файл<input type="file" name="media_file" required accept=".pdf,.docx,.xlsx,.pptx,.jpg,.jpeg,.png,.webp,.mp4,.webm"></label>
<p class="muted">Разрешены PDF, DOCX, XLSX, PPTX (до 20 МБ); JPG, PNG, WebP (до 8 МБ); MP4, WebM (до 50 МБ). Лимиты PHP хостинга могут быть меньше. Для доступности PDF предусмотрите машиночитаемый текст либо альтернативную HTML-версию.</p>
<label class="check"><input type="checkbox" name="publish" value="1"> Опубликовать сразу (иначе будет черновик)</label>
<button type="submit" class="button">Загрузить материал</button>
</form></div>
<h2>Загруженные материалы</h2>
<div class="cms-media-admin-list">
<?php foreach($mediaItems as $media):?>
<article class="box cms-media-admin-item">
 <div class="cms-media-admin-heading"><strong><?=h($media['title'])?></strong><span class="tag <?=$media['status']==='published'?'tag-green':''?>"><?=$media['status']==='published'?'Опубликовано':'Черновик'?></span></div>
 <p class="muted"><?=h($media['original_name'])?> · <?=h(['photo'=>'Фото','document'=>'Документ','video'=>'Видео'][$media['category']]??'Файл')?> · <?=h($media['age_rating'])?> · <?=number_format(((int)$media['size_bytes'])/1024/1024,2,',',' ')?> МБ</p>
 <div class="cms-media-admin-actions">
 <a href="/media.php?file=<?=(int)$media['id']?>" target="_blank" rel="noopener">Открыть файл ↗</a>
 <form method="post" action="/admin/actions.php"><?=csrf()?>
   <input type="hidden" name="action" value="update_media">
   <input type="hidden" name="media_id" value="<?=(int)$media['id']?>">
   <label>Название<input name="title" maxlength="190" required value="<?=h($media['title'])?>"></label>
   <label>Описание<textarea name="description" rows="2" maxlength="2000"><?=h((string)($media['description']??''))?></textarea></label>
   <label>Альтернативный текст<input name="alt_text" maxlength="300" value="<?=h($media['alt_text'])?>"></label>
   <div class="two">
     <label>Возраст<select name="age_rating"><?php foreach(['0+','6+','12+','16+','18+'] as $age):?><option value="<?=h($age)?>" <?=$media['age_rating']===$age?'selected':''?>><?=h($age)?></option><?php endforeach;?></select></label>
     <label>Статус<select name="status"><option value="draft" <?=$media['status']==='draft'?'selected':''?>>Черновик</option><option value="published" <?=$media['status']==='published'?'selected':''?>>Опубликовано</option></select></label>
   </div>
   <button class="button button-outline" type="submit">Сохранить карточку</button>
 </form>
 <form method="post" action="/admin/actions.php" onsubmit="return confirm('Безвозвратно удалить файл и его карточку?')"><?=csrf()?>
   <input type="hidden" name="action" value="delete_media">
   <input type="hidden" name="media_id" value="<?=(int)$media['id']?>">
   <button class="link-danger" type="submit">Удалить файл</button>
 </form>
 </div>
</article>
<?php endforeach;?>
<?php if(!$mediaItems):?><div class="box empty-state">Файлов пока нет. Добавьте первый документ, фотографию или видео.</div><?php endif;?>
</div>
