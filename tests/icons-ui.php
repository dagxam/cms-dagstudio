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
icon_assert(str_contains($css,'font-awesome/6.7.2/css/all.min.css'),'Шрифтовая библиотека подключена к общим стилям');
icon_assert(str_contains($css,'border-radius:999px')&&str_contains($media,'border-radius:999px!important'),'Овальная кнопка во всех шаблонах');
icon_assert(str_contains($css,'.theme-toggle::before')&&str_contains($css,'translate(30px,-50%)'),'Перемещение бегунка переключателя');
icon_assert(str_contains($header,'fa-moon')&&str_contains($header,'fa-sun'),'Сайт: иконки переключения темы');
icon_assert(str_contains($admin,'fa-moon')&&str_contains($admin,'fa-sun'),'Панель управления: иконки переключения темы');
icon_assert(str_contains($login,'fa-eye')&&str_contains($install,'fa-eye'),'Вход и установка: иконки пароля');
icon_assert(str_contains($theme,'fa-eye-slash')&&str_contains($theme,'fa-eye'),'Состояние видимости пароля меняет иконку');
icon_assert(str_contains($social,"'icon'=>'fa-brands fa-vk'")&&str_contains($social,"'icon'=>'fa-brands fa-telegram'"),'Соцсети показываются фирменными значками');
echo "Иконки и переключатели темы успешно проверены.\n";
