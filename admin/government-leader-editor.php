<?php
declare(strict_types=1);
if(!defined('DAG_CMS_ADMIN_VIEW') || site_template()!=='government'){http_response_code(404);exit;}
$leader=government_layout();
?>
<section class="gov-leader-editor" id="government-leader">
  <div class="gov-leader-head">
    <div class="gov-leader-head-icon" aria-hidden="true"><i class="fa-solid fa-user-tie"></i></div>
    <div>
      <div class="eyebrow">ШАБЛОН «АДМИНИСТРАЦИЯ» / РУКОВОДСТВО</div>
      <h2>Глава администрации</h2>
      <p>Загрузите официальную фотографию, укажите фамилию, имя, отчество и инициалы. Данные отображаются в информационном блоке администрации.</p>
    </div>
  </div>
  <div class="gov-leader-editor-grid">
    <section class="gov-leader-preview-box" aria-label="Предпросмотр карточки главы">
      <div class="gov-leader-preview-label"><i class="fa-solid fa-eye" aria-hidden="true"></i> Предпросмотр карточки</div>
      <div class="gov-leader-preview-card">
        <h3 data-leader-preview-title><?=h($leader['leader_title']?:'Глава администрации')?></h3>
        <div class="gov-leader-preview-picture">
          <?php if($leader['leader_image']!==''):?>
            <img data-leader-preview-img src="<?=h($leader['leader_image'])?>" alt="Текущая фотография главы администрации">
            <i class="fa-solid fa-user-tie" data-leader-preview-placeholder hidden aria-hidden="true"></i>
          <?php else:?>
            <img data-leader-preview-img alt="Предпросмотр выбранной фотографии" hidden>
            <i class="fa-solid fa-user-tie" data-leader-preview-placeholder aria-hidden="true"></i>
          <?php endif;?>
        </div>
        <strong data-leader-preview-initials><?=h($leader['leader_initials']?:'Инициалы руководителя')?></strong>
        <p data-leader-preview-name><?=h($leader['leader_name']?:'Фамилия Имя Отчество')?></p>
        <small data-leader-preview-description><?=h($leader['leader_description'])?></small>
      </div>
      <p class="gov-leader-preview-note"><i class="fa-solid fa-circle-info" aria-hidden="true"></i> Предпросмотр помогает оценить оформление. На сайте карточка выводится с учётом выбранной схемы колонок.</p>
    </section>
    <form class="gov-leader-form" action="/admin/actions.php" method="post" enctype="multipart/form-data" data-leader-form>
      <?=csrf()?>
      <input type="hidden" name="action" value="save_government_leader">
      <div class="gov-leader-form-title"><i class="fa-solid fa-id-card" aria-hidden="true"></i><h3>Данные руководителя</h3></div>
      <label>Название блока
        <input name="leader_title" maxlength="120" value="<?=h($leader['leader_title'])?>" placeholder="Глава администрации" data-leader-title>
      </label>
      <label>Фамилия, имя, отчество
        <input name="leader_name" maxlength="160" value="<?=h($leader['leader_name'])?>" placeholder="Фамилия Имя Отчество" autocomplete="off" data-leader-name>
      </label>
      <label>Фамилия и инициалы
        <input name="leader_initials" maxlength="100" value="<?=h($leader['leader_initials'])?>" placeholder="Иванов И. И." data-leader-initials>
        <small>Например: Иванов И. И. Показывается отдельной строкой на карточке.</small>
      </label>
      <label>Должность и краткая информация
        <textarea name="leader_description" maxlength="1000" rows="3" placeholder="Должность или краткие сведения о руководителе" data-leader-description><?=h($leader['leader_description'])?></textarea>
      </label>
      <div class="gov-leader-upload">
        <label for="gov-leader-image"><i class="fa-solid fa-camera" aria-hidden="true"></i> Фотография главы администрации
          <span class="gov-leader-upload-help">JPG, PNG или WebP · не более 2 МБ · максимум 2800 × 2800 пикселей</span>
        </label>
        <input id="gov-leader-image" type="file" name="leader_photo" accept="image/jpeg,image/png,image/webp" data-leader-photo>
        <p class="gov-leader-selected" data-leader-upload-status role="status"><?= $leader['leader_image']!==''?'Фотография сохранена. Можно выбрать новую для замены.':'Фотография пока не загружена.' ?></p>
        <?php if($leader['leader_image']!==''):?>
          <label class="check gov-leader-remove"><input type="checkbox" name="remove_leader_photo" value="1" data-leader-remove> Удалить текущую фотографию при сохранении</label>
        <?php endif;?>
      </div>
      <label class="gov-leader-visibility"><input type="checkbox" name="show_leader" value="1" <?=$leader['show_leader']==='1'?'checked':''?>>
        <span><strong>Показывать блок руководителя на сайте</strong><small>При выключении данные и фото сохраняются, но карточка скрывается.</small></span>
      </label>
      <div class="gov-leader-actions">
        <button type="submit" class="button"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Сохранить данные главы</button>
        <a href="/admin/index.php?section=templates&amp;view=edit&amp;tab=government#government-editor"><i class="fa-solid fa-sliders" aria-hidden="true"></i> Настроить правую колонку</a>
      </div>
    </form>
  </div>
</section>
