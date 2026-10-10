<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'||getenv('DAG_CMS_TEST_MODE')!=='1')exit(1);
$values=[
  'site_age_rating'=>'12+',
  'privacy_operator'=>'',
  'privacy_address'=>'',
  'privacy_email'=>'privacy@example.test',
  'privacy_purpose'=>'Ответы на обращения',
  'privacy_retention'=>'До отзыва или достижения цели',
  'privacy_processors'=>'Российский хостинг',
  'privacy_version'=>'v2026-10',
  'privacy_published'=>'0',
  'accessibility_options'=>'{}',
];
function config_value(string $name,string $default=''):string{global $values;return $values[$name]??$default;}
function h(?string $value):string{return htmlspecialchars($value??'',ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function site_template():string{return 'government';}
function safe_template_url(string $url):bool{return str_starts_with($url,'/');}
function ck(bool $flag,string $msg):void{if(!$flag){fwrite(STDERR,"[FAIL] ".$msg."\n");exit(1);}echo "[OK] ".$msg."\n";}
require dirname(__DIR__).'/app/privacy.php';
require dirname(__DIR__).'/app/compliance.php';
ck(!cms_privacy()['ready'],'Политика не считается опубликованной без реквизитов оператора');
$values['privacy_operator']='Местная администрация';
$values['privacy_address']='Российская Федерация, тестовый адрес';
$values['privacy_published']='1';
ck(cms_privacy()['ready'],'Заполненная опубликованная политика активна');
ck(cms_privacy_url()==='/privacy.php','Встроенная политика используется после публикации');
ck(str_contains(cms_consent_text(cms_privacy()),'Ответы на обращения'),'Согласие включает цель обработки');
ck(str_contains(cms_privacy_links(),'/consent.php'),'Политика и согласие отображаются раздельно');
ck(str_contains(cms_cookie_controls(),'data-privacy-reject'),'Есть возможность отказаться от стороннего видео');
ck(str_contains(cms_cookie_controls(),'data-privacy-accept'),'Подключение стороннего видео только после выбора');
ck(str_contains(cms_accessibility_control(),'data-vision-toolbar'),'Встроенная панель доступности');
ck(str_contains(cms_accessibility_control(),'value="200"'),'Шрифт 200% настраивается посетителем');
$values['accessibility_options']=json_encode(['enabled'=>'0']);
ck(str_contains(cms_accessibility_control(),'data-accessibility-toggle'),'В шаблоне администрации кнопка доступности не скрывается');
$media=file_get_contents(dirname(__DIR__).'/media.php');
ck(is_string($media)&&str_contains($media,'data-privacy-video-url'),'Видеоплеер ждёт разрешения');
ck(!str_contains($media,'<iframe src="<?=h($embedded'),'Нет автоматического iframe внешнего видео');
echo "Защита конфиденциальности и доступность проверены.\n";
