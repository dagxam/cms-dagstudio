<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'||getenv('DAG_CMS_TEST_MODE')!=='1')exit(1);
function icon_assert(bool $check,string $description):void{
    if(!$check){fwrite(STDERR,'[FAIL] '.$description.PHP_EOL);exit(1);}
    echo '[OK] '.$description.PHP_EOL;
}
require dirname(__DIR__).'/app/modules.php';
foreach(['dashboard','page','news','service','product','documents','photos','videos','features','contact','all'] as $name){
    $html=cms_fa_icon($name);
    icon_assert(str_contains($html,'fa-solid ')&&str_contains($html,'aria-hidden="true"'),$name.' — иконка Font Awesome');
}
$css=file_get_contents(dirname(__DIR__).'/assets/style.css');
$media=file_get_contents(dirname(__DIR__).'/assets/media.css');
$theme=file_get_contents(dirname(__DIR__).'/assets/theme.js');
$header=file_get_contents(dirname(__DIR__).'/app/public-header.php');
$admin=file_get_contents(dirname(__DIR__).'/admin/index.php');
$login=file_get_contents(dirname(__DIR__).'/admin/login.php');
$install=file_get_contents(dirname(__DIR__).'/install.php');
$social=file_get_contents(dirname(__DIR__).'/app/contact-social.php');
icon_assert(str_contains($css,'/assets/fontawesome/css/all.min.css?v=6.7.2')&&!str_contains($css,'cdnjs.cloudflare.com'),'Библиотека подключена локально, а не через CDN');
icon_assert(str_contains($css,'border-radius:999px')&&str_contains($media,'border-radius:999px!important'),'Овальная кнопка во всех шаблонах');
icon_assert(is_file(dirname(__DIR__).'/assets/fontawesome/css/all.min.css'),'Локальная CSS-библиотека действительно установлена');
foreach(['fa-solid-900.woff2','fa-regular-400.woff2','fa-brands-400.woff2'] as $font){
    icon_assert(is_file(dirname(__DIR__).'/assets/fontawesome/webfonts/'.$font)
        && filesize(dirname(__DIR__).'/assets/fontawesome/webfonts/'.$font)>0,
        'Локальный шрифт '.$font.' находится на сервере');
}
icon_assert(str_contains(file_get_contents(dirname(__DIR__).'/assets/fontawesome/css/all.min.css'),'../webfonts/'),
    'CSS Font Awesome использует локальные шрифты');
$head=file_get_contents(dirname(__DIR__).'/admin/index.php');
$navStart=strpos($head,'<aside class="sidebar">');
$navEnd=strpos($head,'</aside>',$navStart);
$nav=substr($head,$navStart,$navEnd-$navStart);
$headerStart=strpos($head,'<header class="topbar">');
$headerEnd=strpos($head,'</header>',$headerStart);
$topbar=substr($head,$headerStart,$headerEnd-$headerStart);
icon_assert(!str_contains($nav,'Вы вошли как')&&!str_contains($nav,'name="logout"'),
    'Имя пользователя и выход удалены из боковой панели');
icon_assert(str_contains($topbar,'name="logout"')&&str_contains($topbar,'admin-logout-button'),
    'Форма выхода перенесена в правую часть верхней панели');
icon_assert(str_contains($topbar,'user-pill')&&str_contains($topbar,'<?=h($me[\'name\'])?>'),
    'Имя пользователя показывается в правой части верхней панели');
icon_assert(str_contains($css,'.admin-header-controls .theme-toggle')&&str_contains($css,'width:76px!important'),
    'Овальный переключатель администратора имеет полную ширину');
icon_assert(str_contains($css,'.theme-toggle::before')&&str_contains($css,'translate(30px,-50%)'),'Перемещение бегунка переключателя');
icon_assert(str_contains($header,'fa-moon')&&str_contains($header,'fa-sun'),'Сайт: иконки переключения темы');
icon_assert(str_contains($admin,'fa-moon')&&str_contains($admin,'fa-sun'),'Панель управления: иконки переключения темы');
icon_assert(str_contains($login,'fa-eye')&&str_contains($install,'fa-eye'),'Вход и установка: иконки пароля');
icon_assert(str_contains($theme,'fa-eye-slash')&&str_contains($theme,'fa-eye'),'Состояние видимости пароля меняет иконку');
icon_assert(str_contains($social,"'icon'=>'fa-brands fa-vk'")&&str_contains($social,"'icon'=>'fa-brands fa-telegram'"),'Соцсети показываются фирменными значками');
echo "Иконки и переключатели темы успешно проверены.\n";
