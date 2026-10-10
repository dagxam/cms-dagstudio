<?php
declare(strict_types=1);
if(!defined('DAG_CMS_ADMIN_VIEW')){http_response_code(404);exit;}
$p=cms_privacy();
?>
<div class="eyebrow">НАСТРОЙКИ / КОНФИДЕНЦИАЛЬНОСТЬ</div>
<div class="heading-row"><div><h1>Персональные данные и конфиденциальность</h1>
<p class="muted">Реквизиты оператора, срок обработки, отдельный документ согласия и настройки сторонних видео. Данные каждой организации настраиваются владельцем сайта.</p>
</div><a class="button button-outline" href="/privacy.php" target="_blank" rel="noopener">Просмотреть политику <i class="fa-solid fa-arrow-up-right-from-square cms-icon-inline" aria-hidden="true"></i></a></div>
<div class="box form-panel cms-privacy-admin">
<form method="post" action="/admin/actions.php"><?=csrf()?><input type="hidden" name="action" value="save_privacy">
<fieldset><legend>Сведения об операторе</legend>
<div class="two">
<label>Полное наименование юридического лица / ИП / органа *
<input name="privacy_operator" value="<?=h($p['operator'])?>" required maxlength="250" placeholder="Полное юридическое наименование"></label>
<label>Адрес оператора *
<input name="privacy_address" value="<?=h($p['address'])?>" required maxlength="500" placeholder="Адрес для обращений"></label>
</div>
<label>Email для запросов субъектов персональных данных *
<input name="privacy_email" type="email" required maxlength="190" value="<?=h($p['email'])?>"></label>
</fieldset>
<fieldset><legend>Цели, срок, средства обработки</legend>
<label>Цель обработки данных *
<textarea name="privacy_purpose" rows="3" maxlength="2000" required><?=h($p['purpose'])?></textarea></label>
<label>Срок хранения, критерии удаления и прекращения обработки *
<textarea name="privacy_retention" rows="3" maxlength="2000" required><?=h($p['retention'])?></textarea></label>
<label>Используемые подрядчики / размещение и передача данных
<textarea name="privacy_processors" rows="3" maxlength="2000"><?=h($p['processors'])?></textarea></label>
<p class="muted">Опишите фактический хостинг, подрядчиков, цели и передачу данных, а не предполагаемые. Нельзя объявлять о полном соответствии закону без проверки реальных процессов и договоров.</p>
</fieldset>
<fieldset><legend>Публикация</legend>
<label>Версия документа
<input name="privacy_version" maxlength="50" required value="<?=h($p['version'])?>" placeholder="2026-10-10"></label>
<label class="check"><input type="checkbox" name="privacy_published" value="1" <?=$p['published']?'checked':''?>> Публиковать политику и согласие с этими реквизитами</label>
<p class="muted">Форма обращений активируется только при наличии опубликованной политики. Флажок согласия не предустановлен, факт предоставления согласия записывается отдельно для каждого обращения.</p>
</fieldset>
<button class="button" type="submit">Сохранить настройки конфиденциальности</button>
</form></div>
<div class="box cms-privacy-guidance"><h2>Что ещё необходимо проверить владельцу</h2><p>Уведомление Роскомнадзора (если требуется), нахождение баз ПДн российских граждан в РФ, актуальность целей и сроков, работа с отзывами, доступность текста согласия и наличие надлежащих договоров с обработчиками. Обновление CMS не заменяет юридическую проверку.</p></div>
