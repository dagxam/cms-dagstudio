<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'||getenv('DAG_CMS_TEST_MODE')!=='1')exit(1);
function check_leader(bool $pass,string $label):void {
 if(!$pass){fwrite(STDERR,"[FAIL] $label\n");exit(1);}echo "[OK] $label\n";
}
function reject_leader(callable $call,string $label):void {
 try{$call();}catch(RuntimeException $e){check_leader(true,$label);return;}
 check_leader(false,$label);
}
require dirname(__DIR__).'/app/government-leader.php';
$current=['name'=>'Администрация','leader_image'=>'/assets/uploads/leader-'.str_repeat('a',32).'.jpg',
 'leader_name'=>'Старый руководитель','show_leader'=>'1','show_banner'=>'1'];
$input=['leader_title'=>'Глава муниципального образования',
 'leader_name'=>'Иванов Иван Иванович','leader_initials'=>'Иванов И. И.',
 'leader_description'=>'Руководитель администрации','show_leader'=>'1'];
$changed=government_leader_values($input,$current);
check_leader($changed['leader_name']==='Иванов Иван Иванович'&&$changed['leader_initials']==='Иванов И. И.',
 'ФИО и инициалы сохраняются отдельно');
check_leader($changed['leader_image']===$current['leader_image']&&$changed['show_banner']==='1',
 'Сохранение профиля не затрагивает фото и другие настройки');
$hidden=$input;unset($hidden['show_leader']);
check_leader(government_leader_values($hidden,$current)['show_leader']==='0',
 'Блок руководителя можно скрыть без потери данных');
reject_leader(fn()=>government_leader_values([...$input,'leader_name'=>str_repeat('x',161)],$current),
 'Очень длинные ФИО отклоняются');
reject_leader(fn()=>government_leader_values([...$input,'leader_initials'=>"<script>\0"],$current),
 'Служебные управляющие символы в инициалах отклоняются');
reject_leader(fn()=>government_leader_values([...$input,'leader_title'=>['invalid']],$current),
 'Некорректные типы полей отклоняются');
check_leader(government_leader_uploaded_photo(null)===null,
 'Без файла текущая фотография сохраняется');
check_leader(government_leader_uploaded_photo(['error'=>UPLOAD_ERR_NO_FILE])===null,
 'Отсутствие загружаемой фотографии не считается ошибкой');
reject_leader(fn()=>government_leader_uploaded_photo(['error'=>UPLOAD_ERR_OK,'tmp_name'=>'/tmp/nonexistent','size'=>120]),
 'Неавторизованные файлы не принимаются');
$controller=file_get_contents(dirname(__DIR__).'/admin/actions.php');
$editor=file_get_contents(dirname(__DIR__).'/admin/government-leader-editor.php');
$tpl=file_get_contents(dirname(__DIR__).'/admin/template-editor.php');
$public=file_get_contents(dirname(__DIR__).'/app/government-render.php');
$legacy=file_get_contents(dirname(__DIR__).'/admin/government-editor.php');
check_leader(str_contains($controller,"$"."action === 'save_government_leader'")&&
 str_contains($controller,"require_module('settings')"),'Изменение данных главы разрешено только администратору');
check_leader(str_contains($controller,'government_leader_uploaded_photo')&&
 str_contains($controller,'government_leader_values'),'Изолированная загрузка и сохранение настроек');
check_leader(!str_contains($legacy,'name="leader_photo"')&&
 str_contains($legacy,'tab=leader'),'Отдельная вкладка вместо дублирующей формы');
check_leader(str_contains($tpl,"$"."editorTabs['leader']='Глава администрации'")&&
 str_contains($editor,'name="leader_photo"')&&str_contains($editor,'name="leader_initials"')&&
 str_contains($editor,'data-leader-preview-img'),'Доступна вкладка фото, инициалов и предпросмотра');
check_leader(str_contains($public,'government-leader-photo')&&
 str_contains($public,'government-leader-initials')&&
 str_contains($public,"$"."gov['leader_image']"),'Фото и инициалы выводятся на публичном сайте');
echo "Карточка главы администрации проверена.\n";
