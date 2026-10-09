<?php
declare(strict_types=1);
if (getenv('DAG_CMS_TEST_MODE')!=='1') exit(1);
$settings=[];
function config_value(string $key,string $default=''): string {global $settings;return $settings[$key]??$default;}
require dirname(__DIR__).'/app/templates.php';
require dirname(__DIR__).'/app/government.php';
function check_gov(bool $pass,string $msg): void {
 if (!$pass) {fwrite(STDERR,'[FAIL] '.$msg.PHP_EOL);exit(1);}
 echo '[OK] '.$msg.PHP_EOL;
}
$gov=government_layout();
check_gov($gov['layout']==='both' && count($gov['left_menu'])>=5,'Три колонки и меню по умолчанию');
check_gov($gov['left_width']==='240' && $gov['right_width']==='248','Начальные размеры боковых колонок');
check_gov(government_links_as_text($gov['left_menu'])!=='','Меню редактируется строками');
$links=government_parse_link_text("Новости | /?kind=news\nОбращения | #contact\nПравовой портал | https://example.org");
check_gov(count($links)===3 && $links[1]['url']==='#contact','Разбор пользовательских ссылок и порядка');
foreach(["Ссылка без адреса","Опасно | javascript:alert(1)","Обход | //evil.example","Ошибка | /bad link"] as $bad) {
 try {government_parse_link_text($bad);check_gov(false,'Запрещённая ссылка');}
 catch (RuntimeException $error) {check_gov(true,'Небезопасная ссылка отклонена');}
}
$settings['government_layout']=json_encode([
 'layout'=>'swap', 'left_width'=>'300','right_width'=>'220',
 'leader_name'=>'Тестовый руководитель','left_menu'=>[['label'=>'Новости','url'=>'/?kind=news']],
 'right_links'=>[['label'=>'Подмена','url'=>'javascript:alert(1)']],
 'leader_image'=>'https://example.org/evil.jpg',
],JSON_UNESCAPED_UNICODE);
$gov=government_layout();
check_gov($gov['layout']==='swap' && $gov['left_width']==='300','Смена колонок и ширины сохранена');
check_gov($gov['leader_name']==='Тестовый руководитель','Редактируемые данные руководителя');
check_gov(count($gov['left_menu'])===1 && count($gov['right_links'])===0,'Меню проверяется при загрузке');
check_gov($gov['leader_image']==='','Недостоверный URL изображения отклоняется');
$settings['government_layout']='{"layout":"unexpected","left_width":"999999"}';
$gov=government_layout();
check_gov($gov['layout']==='both' && $gov['left_width']==='240','Некорректная конфигурация безопасно нормализуется');
echo "Настройки администрации проверены.\n";
