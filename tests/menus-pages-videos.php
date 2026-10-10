<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'||getenv('DAG_CMS_TEST_MODE')!=='1')exit(1);
$cfg=['site_template'=>'company','site_type'=>'company',
      'cms_menus_by_type'=>'{}','cms_page_options'=>'{}'];
function config_value(string $key,string $default=''):string{global $cfg;return $cfg[$key]??$default;}
function h(?string $str):string{return htmlspecialchars($str??'',ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function check_ext(bool $value,string $label):void{
 if(!$value){fwrite(STDERR,"[FAIL] $label\n");exit(1);}
 echo "[OK] $label\n";
}
require dirname(__DIR__).'/app/templates.php';
require dirname(__DIR__).'/app/modules.php';
require dirname(__DIR__).'/app/menus.php';
require dirname(__DIR__).'/app/public-header.php';
require dirname(__DIR__).'/app/page-options.php';
require dirname(__DIR__).'/app/video-links.php';
check_ext(count(cms_menu_for_template('company'))>2,'Стандартное главное меню');
check_ext(cms_menu_parse("Главная | /\nНовости | /?kind=news")[1]['label']==='Новости','Пункты меню и порядок');
foreach(['javascript:alert(1)','https://evil.org/thing\nfoo','//bad.com','/bad url'] as $bad){
 try{cms_menu_parse('Тест | '.$bad);check_ext(false,'Запрещённая ссылка');}
 catch(RuntimeException $e){check_ext(true,'Небезопасная ссылка отклонена');}
}
$cfg['cms_menus_by_type']=json_encode(['company'=>[['label'=>'Связь','url'=>'/?module=contact']],
                                       'government'=>[['label'=>'Документы','url'=>'/media.php?type=document']]]);
check_ext(cms_menu_for_template('company')[0]['label']==='Связь','Независимое меню компании');
check_ext(cms_menu_for_template('government')[0]['label']==='Документы','Независимое меню администрации');
foreach(['organization','company','store','government'] as $type) {
    $nav=cms_public_menu_links($type);
    $urls=array_column($nav,'url');
    check_ext(count($urls)===count(array_unique($urls)),'Без дублей ссылок: '.$type);
    check_ext(count($nav)>0,'Верхнее меню шаблона не пустое: '.$type);
}
check_ext(cms_public_menu_links('company')[0]['label']==='Связь','Свой порядок пунктов компании сохраняется в общей шапке');

$cfg['cms_page_options']=json_encode(['42'=>['before'=>'news','after'=>'photos',
    'left'=>['documents','page'],'right'=>['videos','contact']]]);
$o=cms_page_options(42);
check_ext($o['before']==='news'&&$o['after']==='photos','Блоки до и после содержимого страницы');
check_ext($o['left']===['page','documents']&&$o['right']===['videos','contact'],
    'Независимые боковые колонки страницы');
check_ext(cms_page_options(0)['before']==='','Новая страница не унаследовала чужой блок');
$vk=cms_video_embed('https://vkvideo.ru/video-123456_789012?hash=abc12');
$rt=cms_video_embed('https://rutube.ru/video/a8b9c0d1e2f34a56b78c9d0e1f2a3b4c/');
check_ext(($vk['provider']??'')==='vk'&&str_contains($vk['embed'],'video_ext.php?'),'Безопасный адрес VK Видео');
check_ext(($rt['provider']??'')==='rutube'&&str_contains($rt['embed'],'/play/embed/'),'Безопасный адрес Rutube');
foreach(['https://evil.rutube.ru/video/a8b9c0d1e2f34a56b78c9d0e1f2a3b4c/',
 'http://rutube.ru/video/a8b9c0d1e2f34a56b78c9d0e1f2a3b4c/',
 'https://vkvideo.ru@evil.example/video-2_3',
 'https://vkvideo.ru/video-2_3#bad',
 'javascript:alert(1)'] as $bad){
 check_ext(cms_video_embed($bad)===null,'Недоверенное видео отклонено');
}
echo "Меню, страницы и ссылки видео успешно проверены.\n";
