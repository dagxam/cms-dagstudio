<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'||getenv('DAG_CMS_TEST_MODE')!=='1')exit(1);
$config=['site_type'=>'company','site_template'=>'company',
    'template_content'=>'{}','template_content_by_type'=>'{}',
    'contact_email'=>'help@example.org','cms_contacts_by_type'=>'{}','cms_socials_by_type'=>'{}'];
function config_value(string $key,string $default=''):string {global $config;return $config[$key]??$default;}
function h(?string $str):string{return htmlspecialchars($str??'',ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function check_c(bool $pass,string $title):void{if(!$pass){fwrite(STDERR,'[FAIL] '.$title.PHP_EOL);exit(1);}echo '[OK] '.$title.PHP_EOL;}
require dirname(__DIR__).'/app/templates.php';
require dirname(__DIR__).'/app/contact-social.php';
foreach(array_keys(template_catalog()) as $type){
    $c=cms_contacts($type);
    check_c(isset($c['title'],$c['phones'],$c['emails'],$c['addresses']),'Стандартные контакты '.$type);
    check_c(cms_socials($type)['links']===[],'Нет вымышленных соцсетей '.$type);
}
$config['cms_contacts_by_type']=json_encode(['company'=>[
 'title'=>'Связаться с нашей компанией','intro'=>'Адрес и телефон','phones'=>['+7 (999) 222-33-44'],
 'emails'=>['company@example.ru'],'addresses'=>['Республика Дагестан'],
 'hours'=>'Пн–Пт','details'=>[['label'=>'Отдел','value'=>'Продажи']]],
 'store'=>['title'=>'Контакты магазина','phones'=>['+7 888 222-11-11'],'emails'=>[],'addresses'=>[],'hours'=>'','details'=>[]]
],JSON_UNESCAPED_UNICODE);
check_c(cms_contacts('company')['title']==='Связаться с нашей компанией','Название контактов компании');
check_c(cms_contacts('store')['title']==='Контакты магазина','Контакты другого шаблона отдельные');
check_c(cms_contacts('government')['title']!==cms_contacts('company')['title'],'Администрация не наследует контакты компании');
check_c(str_contains(cms_render_contact_details('company'),'tel:+79992223344'),'Телефон становится ссылкой');
check_c(str_contains(cms_render_contact_details('company'),'mailto:company@example.ru'),'Email становится ссылкой');
check_c(cms_contact_parse_lines("test@example.ru\ninfo@example.ru",'emails')===['test@example.ru','info@example.ru'],'Список email');
check_c(count(cms_contact_parse_details("Факс | 123\nПриёмная | 321"))===2,'Дополнительные реквизиты');
foreach(['javascript:alert(1)','https://evil.com/','https://vk.com.evil.ru/u','http://vk.com/test','https://user:pass@vk.com/profile'] as $bad)
 check_c(!cms_social_valid_url('vk',$bad),'Недоверенный адрес соцсети отклонён');
check_c(cms_social_valid_url('vk','https://vk.com/company'),'Ссылка ВКонтакте');
check_c(cms_social_valid_url('telegram','https://t.me/company'),'Ссылка Telegram');
check_c(cms_social_valid_url('rutube','https://rutube.ru/channel/123'),'Ссылка Rutube');
$config['cms_socials_by_type']=json_encode([
 'company'=>['enabled'=>true,'location'=>'both','links'=>['vk'=>'https://vk.com/company','telegram'=>'https://t.me/company']],
 'store'=>['enabled'=>true,'location'=>'footer','links'=>['vk'=>'https://vk.com/shop']],
 'government'=>['enabled'=>false,'location'=>'both','links'=>['vk'=>'https://vk.com/administration']]
]);
check_c(str_contains(cms_render_social_links('company','contact'),'https://vk.com/company'),'Соцсети на странице контактов');
check_c(str_contains(cms_render_social_links('company','footer'),'https://t.me/company'),'Соцсети в подвале');
check_c(cms_render_social_links('store','contact')==='','Для магазина выбран только подвал');
check_c(cms_render_social_links('government','footer')==='','Отключённые ссылки скрыты');
check_c(!str_contains(cms_render_social_links('store','footer'),'https://vk.com/company'),'Нет смешивания шаблонов');
$config['template_content_by_type']=json_encode(['company'=>['hero_logo_path'=>'/assets/uploads/hero-logo-'.str_repeat('a',32).'.png'],
 'store'=>['hero_logo_path'=>'/assets/uploads/hero-logo-'.str_repeat('b',32).'.webp']]);
check_c(template_content('company')['hero_logo_path']!==template_content('store')['hero_logo_path'],'Эмблемы каждого шаблона независимы');
check_c(template_content('organization')['hero_logo_path']==='','Стандартная эмблема остаётся по умолчанию');
echo "Индивидуальные контакты, соцсети и логотипы проверены.\n";
