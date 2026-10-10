<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli' || getenv('DAG_CMS_TEST_MODE') !== '1') exit(1);
$id = $argv[1] ?? '';
if (!in_array($id, ['organization','company','store','government'], true)) exit(1);
$root = dirname(__DIR__);
$config = $root . '/storage/config.php';
if (is_file($config)) throw new RuntimeException('Отказ: существующая конфигурация не может быть перезаписана.');
$c = [
    'host'=>getenv('TEST_DB_HOST') ?: '127.0.0.1',
    'port'=>3306,
    'name'=>getenv('TEST_DB_NAME') ?: 'dagcms_test',
    'user'=>getenv('TEST_DB_USER') ?: 'root',
    'pass'=>getenv('TEST_DB_PASS') ?: '',
];
$pdo = new PDO("mysql:host={$c['host']};port=3306;dbname={$c['name']};charset=utf8mb4",$c['user'],$c['pass']);
$up = $pdo->prepare('INSERT INTO settings(name,value) VALUES (?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)');
foreach ([
    'site_name'=>'Тестовый сайт',
    'site_type'=>$id,
    'site_template'=>$id,
    'site_description'=>'Тестовое описание',
    'contact_email'=>'test@example.test',
    'enabled_modules'=>'["page","news","service","product"]',
    'template_design'=>'{}',
    'template_content'=>'{}',
    'template_sections'=>'',
] as $k=>$v) $up->execute([$k,$v]);
if(getenv('TEST_MODULE_LAYOUT_TEST')==='1') {
    $positions=[
        'page'=>['area'=>'right','order'=>10],
        'news'=>['area'=>'left','order'=>20],
        'service'=>['area'=>'nav','order'=>30],
        'product'=>['area'=>'footer','order'=>40],
        'photos'=>['area'=>'main','order'=>50],
        'documents'=>['area'=>'nav','order'=>55],
        'videos'=>['area'=>'nav','order'=>56],
        'features'=>['area'=>'main','order'=>10],
        'contact'=>['area'=>'main','order'=>60],
    ];
    $up->execute(['cms_modules_enabled',json_encode(array_keys($positions))]);
    $up->execute(['cms_module_layouts',json_encode([$id=>$positions])]);
}
if(getenv('TEST_COVER_IMAGE_TEST')==='1') {
    $up->execute(['cms_modules_enabled',json_encode(array_keys([
        'page'=>true,'news'=>true,'service'=>true,'product'=>true,
        'documents'=>true,'photos'=>true,'videos'=>true,'features'=>true,'contact'=>true
    ]))]);
    $placements=[
        'news'=>['area'=>'main','order'=>10],
        'page'=>['area'=>'main','order'=>20],
        'service'=>['area'=>'main','order'=>30],
        'product'=>['area'=>'main','order'=>40],
        'documents'=>['area'=>'nav','order'=>50],
        'photos'=>['area'=>'nav','order'=>55],
        'videos'=>['area'=>'nav','order'=>60],
        'features'=>['area'=>'main','order'=>70],
        'contact'=>['area'=>'main','order'=>80],
    ];
    $up->execute(['cms_module_layouts',json_encode([$id=>$placements])]);
}
file_put_contents($config,'<?php return '.var_export($c,true).';');
try {
    $_SERVER['REQUEST_METHOD']='GET';
    $_GET=[];
    ob_start();
    include $root.'/index.php';
    $html=ob_get_clean();
    // Подключаемые PHP-шаблоны работают в области видимости include и используют $id в foreach.
    $id=(string)$argv[1];
    $layoutOk=$id==='government'
        ? (str_contains($html,'government-columns') &&
           str_contains($html,'government-left-nav') &&
           str_contains($html,'government-right') &&
           str_contains($html,'government-search') &&
           str_contains($html,'/assets/government.css') &&
           str_contains($html,'data-accessibility-toggle'))
        : (str_contains($html,'id="site-main-title"') &&
           str_contains($html,'site-sections'));
    if(substr_count($html,'class="cms-media-header cms-unified-header"')!==1 ||
       substr_count($html,'<nav class="cms-media-site-nav"')!==1 ||
       substr_count($html,'aria-label="Главное меню"')!==1)
        throw new RuntimeException('Главная и разделы должны иметь единую шапку и одно главное меню: '.$id);
    if (!$layoutOk ||
        !str_contains($html,'site-template-'.$id) ||
        !str_contains($html,'/assets/templates.css') ||
        !str_contains($html,'/media.php') ||
        !str_contains($html,'cms-age-mark') ||
        !str_contains($html,'data-vision-scale') ||
        !str_contains($html,'dag-site-palettes') ||
        !str_contains($html,'data-theme-storage-key="dagstudio-template-'.$id.'"') ||
        !str_contains($html,'html[data-theme="light"] body.site-page') ||
        !str_contains($html,'html[data-theme="dark"] body.site-page')) {
        throw new RuntimeException('HTML не прошёл проверку для шаблона '.$id);
    }
    if(getenv('TEST_MODULE_LAYOUT_TEST')==='1') {
        foreach(['cms-module-widget-news','cms-module-widget-page',
                 'cms-media-module-grid','cms-module-link-footer'] as $needle) {
            if(!str_contains($html,$needle))
                throw new RuntimeException('Модуль не попал в нужную зону: '.$id.' / '.$needle);
        }
    }
    if(getenv('TEST_COVER_IMAGE_TEST')==='1') {
        $newsId=(int)$pdo->query("SELECT id FROM content WHERE slug='test-news'")->fetchColumn();
        if(!$newsId || !str_contains($html,'/content-image.php?id='.$newsId) ||
           !str_contains($html,'alt="Изображение тестовой новости"')) {
            throw new RuntimeException('Обложка тестовой новости не показана в шаблоне '.$id);
        }
        if($id==='government' && !str_contains($html,'cms-government-material-thumb'))
            throw new RuntimeException('Муниципальная карточка новости не показана');
        if($id!=='government' && !str_contains($html,'cms-visual-card'))
            throw new RuntimeException('Карточка новости с обложкой не показана');
    }
    if (!str_contains($html,'Шаблон не найден') && strlen($html)<2000) {
        throw new RuntimeException('Недостаточный HTML шаблона '.$id);
    }
    echo '[OK] Сайт успешно сформирован: '.$id.PHP_EOL;
} finally {
    if (is_file($config)) unlink($config);
}
