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
file_put_contents($config,'<?php return '.var_export($c,true).';');
try {
    $_SERVER['REQUEST_METHOD']='GET';
    $_GET=[];
    ob_start();
    include $root.'/index.php';
    $html=ob_get_clean();
    $layoutOk=$id==='government'
        ? (str_contains($html,'government-columns') &&
           str_contains($html,'government-left-nav') &&
           str_contains($html,'government-right') &&
           str_contains($html,'government-search') &&
           str_contains($html,'/assets/government.css') &&
           str_contains($html,'data-accessibility-toggle'))
        : (str_contains($html,'id="site-main-title"') &&
           str_contains($html,'site-sections'));
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
    if (!str_contains($html,'Шаблон не найден') && strlen($html)<2000) {
        throw new RuntimeException('Недостаточный HTML шаблона '.$id);
    }
    echo '[OK] Сайт успешно сформирован: '.$id.PHP_EOL;
} finally {
    if (is_file($config)) unlink($config);
}
