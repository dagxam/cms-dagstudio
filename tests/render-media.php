<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'||getenv('DAG_CMS_TEST_MODE')!=='1')exit(1);
$tpl=(string)($argv[1]??'');
$type=(string)($argv[2]??'');
if(!in_array($tpl,['organization','company','store','government'],true)||
   !in_array($type,['document','photo','video'],true))exit(1);
$root=dirname(__DIR__);
$config=$root.'/storage/config.php';
if(is_file($config))throw new RuntimeException('Не перезаписывать действующую конфигурацию.');
$db=['host'=>getenv('TEST_DB_HOST')?:'127.0.0.1','port'=>3306,
     'name'=>getenv('TEST_DB_NAME')?:'dagcms_test','user'=>getenv('TEST_DB_USER')?:'root',
     'pass'=>getenv('TEST_DB_PASS')?:''];
$pdo=new PDO('mysql:host='.$db['host'].';port=3306;dbname='.$db['name'].';charset=utf8mb4',$db['user'],$db['pass']);
$up=$pdo->prepare('INSERT INTO settings(name,value) VALUES (?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)');
foreach(['site_name'=>'Тестовый сайт','site_type'=>$tpl,'site_template'=>$tpl,
    'enabled_modules'=>'["page","news","service","product"]',
    'cms_modules_enabled'=>'["page","news","service","product","documents","photos","videos","features","contact"]',
    'template_design'=>'{}','template_content'=>'{}','cms_menus_by_type'=>'{}',
    'cms_module_layouts'=>'{}','site_age_rating'=>'0+'
] as $key=>$value)$up->execute([$key,$value]);
file_put_contents($config,'<?php return '.var_export($db,true).';');
try{
    $_GET=['type'=>$type];
    $_SERVER['REQUEST_METHOD']='GET';
    ob_start();
    include $root.'/media.php';
    $html=(string)ob_get_clean();
    $checks=['site-template-'.$tpl,'cms-media-site-nav','cms-media-grid',
             'cms-media-head','cms-media-filters','/assets/templates.css',
             'dag-site-palettes','data-theme-storage-key="dagstudio-template-'.$tpl.'"',
             'cms-media-footer','cms-media-header'];
    foreach($checks as $part)if(!str_contains($html,$part))
        throw new RuntimeException('Не найден '. $part . ' при выводе '.$tpl.'/'.$type);
    $names=['document'=>'Документы','photo'=>'Фотогалерея','video'=>'Видеогалерея'];
    if(!str_contains($html,$names[$type]))throw new RuntimeException('Неправильный заголовок.');
    if(!str_contains($html,'cms-media-tile')&&!str_contains($html,'cms-media-empty'))
        throw new RuntimeException('Нет ни карточек, ни пустого состояния.');
    if(substr_count($html,'class="cms-media-header cms-unified-header"')!==1 ||
       substr_count($html,'<nav class="cms-media-site-nav"')!==1 ||
       substr_count($html,'aria-label="Главное меню"')!==1)
       throw new RuntimeException('В медиаразделе должно быть одно общее меню: '.$tpl.'/'.$type);
    if(strlen($html)<2500)throw new RuntimeException('HTML неожиданно короткий.');
    echo '[OK] Медиараздел '.$type.' в шаблоне '.$tpl.PHP_EOL;
}finally{
    if(ob_get_level()>0)ob_end_clean();
    if(is_file($config))unlink($config);
}
