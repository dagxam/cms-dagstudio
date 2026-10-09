<?php
declare(strict_types=1);
if(getenv('DAG_CMS_TEST_MODE')!=='1')exit(1);
$fake=['site_age_rating'=>'12+','accessibility_options'=>json_encode([
 'enabled'=>'1','font_scale'=>'200','contrast'=>'blackwhite','line_spacing'=>'wide',
 'letter_spacing'=>'wide','show_images'=>'0','grayscale'=>'1','underlines'=>'1'
])];
function config_value(string $name,string $default=''): string{global $fake;return $fake[$name]??$default;}
function h(?string $value): string{return htmlspecialchars($value??'',ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
require dirname(__DIR__).'/app/compliance.php';
require dirname(__DIR__).'/app/media.php';
function check_compliance(bool $ok,string $label): void{if(!$ok){fwrite(STDERR,"[FAIL] $label\n");exit(1);}echo "[OK] $label\n";}
check_compliance(cms_age_rating()==='12+','12+ допустимо');
check_compliance(str_contains(cms_age_mark(),'12+'),'Маркировка выводится на странице');
check_compliance(cms_accessibility()['font_scale']==='200','Редактируемое увеличение 200%');
check_compliance(str_contains(cms_accessibility_control(),'data-accessibility-toggle'),'Переключатель режима');
check_compliance(str_contains(cms_accessibility_attributes(),'data-vision-contrast="blackwhite"'),'Атрибуты доступности');
check_compliance(cms_age_gate()==='','Для 12+ нет всплывающей проверки 18+');
$fake['site_age_rating']='18+';
check_compliance(str_contains(cms_age_gate(),'data-age-confirm'),'Для 18+ требуется подтверждение');
$fake['site_age_rating']='47+';
check_compliance(cms_age_rating()==='0+','Недопустимая маркировка сбрасывается');
foreach(['photo','document','video'] as $kind){check_compliance(!empty(cms_media_formats()[$kind]),'Форматы '.$kind);}
check_compliance(!isset(cms_media_formats()['document']['php']),'Скрипты запрещены');
check_compliance(cms_media_age_valid('18+')&&!cms_media_age_valid('21+'),'Возрастная категория медиа проверяется');
echo "Возрастная маркировка, доступность и типы медиа проверены.\n";
