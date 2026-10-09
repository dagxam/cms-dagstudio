<?php
declare(strict_types=1);
if (PHP_SAPI!=='cli'||getenv('DAG_CMS_TEST_MODE')!=='1') exit(1);
$config=['site_template'=>'company','site_type'=>'company'];
function config_value(string $name,string $default=''): string {
    global $config;
    return $config[$name]??$default;
}
function csrf(): string {return '<input type="hidden" name="csrf" value="test">';}
function h(?string $value): string {return htmlspecialchars($value??'',ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
require dirname(__DIR__).'/app/templates.php';
require dirname(__DIR__).'/app/government.php';
define('DAG_CMS_ADMIN_VIEW',true);
function editor_render(string $template,string $view='',string $tab='appearance'): string {
    global $config;
    $config['site_template']=$template;
    $config['site_type']=$template;
    $_GET=['section'=>'templates','view'=>$view,'tab'=>$tab];
    ob_start();
    try {require dirname(__DIR__).'/admin/template-editor.php';return (string)ob_get_clean();}
    catch(Throwable $e){ob_end_clean();throw $e;}
}
function assert_editor(bool $ok,string $label): void {
    if(!$ok){fwrite(STDERR,'[FAIL] '.$label.PHP_EOL);exit(1);}
    echo '[OK] '.$label.PHP_EOL;
}
foreach (array_keys(template_catalog()) as $type) {
    $select=editor_render($type);
    assert_editor(str_contains($select,'template-gallery') &&
        str_contains($select,'Выберите тип вашего сайта'),$type.' — экран выбора');
    assert_editor(str_contains($select,'Выбрать шаблон') &&
        str_contains($select,'Настроить шаблон'),$type.' — выбор или настройка активного');
    assert_editor(!str_contains($select,'save_template_design') &&
        !str_contains($select,'save_template_content') &&
        !str_contains($select,'save_template_sections') &&
        !str_contains($select,'save_government_layout'),$type.' — формы скрыты до выбора');
    $appearance=editor_render($type,'edit','appearance');
    assert_editor(str_contains($appearance,'template-editor-tabs') &&
        str_contains($appearance,'save_template_design') &&
        !str_contains($appearance,'save_template_content'),$type.' — вкладка оформления');
    $content=editor_render($type,'edit','content');
    assert_editor(str_contains($content,'save_template_content') &&
        !str_contains($content,'save_template_design'),$type.' — вкладка содержимого');
    $blocks=editor_render($type,'edit','blocks');
    assert_editor(str_contains($blocks,'save_template_sections') &&
        !str_contains($blocks,'save_template_content'),$type.' — вкладка блоков');
    assert_editor(!str_contains($content,'template-gallery'),$type.' — каталог скрыт в редакторе');
    $gov=editor_render($type,'edit','government');
    if($type==='government'){
        assert_editor(str_contains($gov,'save_government_layout') &&
            str_contains($gov,'government-editor') &&
            str_contains($gov,'Конструктор администрации'),$type.' — свои поля настроек');
    }else{
        assert_editor(!str_contains($gov,'save_government_layout') &&
            str_contains($gov,'save_template_design'),$type.' — чужой конструктор недоступен');
    }
}
$invalid=editor_render('company','edit','<script>alert(1)</script>');
assert_editor(!str_contains($invalid,'<script>alert(1)</script>') &&
    str_contains($invalid,'save_template_design'),'Недопустимая вкладка безопасно заменяется');
echo "Двухэтапный выбор и редактирование всех шаблонов проверены.\n";
