<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'||getenv('DAG_CMS_TEST_MODE')!=='1')exit(1);
$root=dirname(__DIR__);
$markup=file_get_contents($root.'/admin/index.php');
$css=file_get_contents($root.'/assets/templates.css');
$js=file_get_contents($root.'/assets/admin-settings-menu.js');
if(!is_string($markup)||!is_string($css)||!is_string($js))
    throw new RuntimeException('Не загружены файлы административного меню.');
$sidebarStart=strpos($markup,'<aside class="sidebar">');
$sidebarEnd=strpos($markup,'<div class="sidebar-bottom">',$sidebarStart);
if($sidebarStart===false||$sidebarEnd===false)throw new RuntimeException('Нет бокового меню.');
$sidebar=substr($markup,$sidebarStart,$sidebarEnd-$sidebarStart);
foreach(['class="cms-settings-group" data-settings-flyout',
         'data-settings-trigger','aria-controls="cms-settings-submenu"',
         'aria-expanded="false"',
         '<nav id="cms-settings-submenu" class="cms-settings-popover"',
         'data-settings-popover','cms-settings-popover-link'] as $needle){
    if(!str_contains($sidebar,$needle))throw new RuntimeException('Нет всплывающего подменю: '.$needle);
}
if(str_contains($sidebar,'<details class="cms-settings-group"')||
   str_contains($sidebar,'cms-settings-summary')||
   str_contains($sidebar,'cms-settings-children'))
    throw new RuntimeException('Осталось раскрывающееся меню вместо отдельного подменю.');
$values=[
'settings'=>'Основные настройки',
'templates'=>'Выбор темы',
'menus'=>'Главное меню',
'modules'=>'Модули',
'accessibility'=>'Доступность и возраст',
'privacy'=>'Конфиденциальность',
'contacts'=>'Контакты',
'socials'=>'Социальные сети',
'messages'=>'Обращения',
'users'=>'Пользователи',
];
foreach($values as $key=>$label){
    if(!str_contains($sidebar,"'$key'=>['$label'"))
        throw new RuntimeException('Не найден подраздел «'.$label.'».');
}
if(!str_contains($sidebar,"if(!allowed(\$entry[1]))continue;"))
    throw new RuntimeException('Подменю не проверяет права сотрудников.');
if(!str_contains($sidebar,"$"."settingsOpen?'active':''"))
    throw new RuntimeException('Пункт Настройки не отмечается активным в своих разделах.');
foreach(['document.body.appendChild(panel)','getBoundingClientRect()',
         "trigger.setAttribute('aria-expanded', 'true')",
         "trigger.setAttribute('aria-expanded', 'false')",
         "event.key === 'Escape'",
         "event.key === 'ArrowDown'",
         "'pointerdown'","'pointerenter'","'resize'","'scroll'"] as $needle){
    if(!str_contains($js,$needle))throw new RuntimeException('Нет сценария управления подменю: '.$needle);
}
if(!str_contains($markup,'/assets/admin-settings-menu.js?v=1'))
    throw new RuntimeException('На странице не подключён сценарий подменю.');
foreach(['.cms-settings-popover[hidden]','.cms-settings-popover-link','.cms-settings-trigger',
         'position:fixed','max-height:85vh'] as $needle){
    if(!str_contains($css,$needle))throw new RuntimeException('Отсутствуют стили подменю: '.$needle);
}
echo "[OK] Всплывающее подменю Настройки, роли, клавиатура, мобильный экран и светлая тема.\n";
