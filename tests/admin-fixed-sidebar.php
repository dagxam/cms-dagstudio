<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'||getenv('DAG_CMS_TEST_MODE')!=='1')exit(1);
$css=file_get_contents(dirname(__DIR__).'/assets/templates.css');
$admin=file_get_contents(dirname(__DIR__).'/admin/index.php');
if(!is_string($css)||!is_string($admin))throw new RuntimeException('Не найдены стили или админка.');
function check_fixed_sidebar(bool $ok,string $message):void{
    if(!$ok){fwrite(STDERR,"[FAIL] $message\n");exit(1);}
    echo "[OK] $message\n";
}
$marker='/* Панель администратора: левая навигация фиксирована на компьютерах.';
$start=strrpos($css,$marker);
check_fixed_sidebar($start!==false,'Отдельные правила закреплённой панели созданы');
$styles=substr($css,$start);
check_fixed_sidebar(str_contains($styles,'@media(min-width:721px)') &&
    preg_match('/body\\.admin-layout \\.sidebar\\s*\\{[^}]*position:fixed;[^}]*top:0;left:0;bottom:0;/s',$styles)===1,
    'Левая панель фиксируется у левого края на компьютере');
check_fixed_sidebar(str_contains($styles,'height:100dvh') &&
    str_contains($styles,'overflow-y:auto') &&
    str_contains($styles,'overscroll-behavior-y:contain'),
    'Длинное меню прокручивается самостоятельно в пределах экрана');
check_fixed_sidebar(str_contains($styles,'margin-left:254px;width:calc(100% - 254px)') &&
    str_contains($styles,'@media(min-width:721px) and (max-width:1050px)') &&
    str_contains($styles,'margin-left:220px;width:calc(100% - 220px)'),
    'Содержимое сдвигается вправо на ширину меню на стандартном и узком экране');
check_fixed_sidebar(str_contains($styles,'@media(max-width:720px)') &&
    str_contains($styles,'position:relative;top:auto;left:auto;bottom:auto') &&
    str_contains($styles,'height:auto;max-height:none;overflow:visible') &&
    str_contains($styles,'margin-left:0;width:100%'),
    'На телефонах фиксированное меню отключено, содержимое не перекрывается');
check_fixed_sidebar(str_contains($admin,'<aside class="sidebar">') &&
    str_contains($admin,'<div class="workspace">') &&
    str_contains($admin,'/assets/templates.css?v=fixed-sidebar1'),
    'Шаблон админки подключает обновлённые стили без старого кеша');
check_fixed_sidebar(str_contains($admin,'data-settings-flyout') &&
    str_contains($admin,'admin-settings-menu.js'),
    'Всплывающее подменю Настройки сохранено');
echo "Закреплённая левая панель проверена.\n";
