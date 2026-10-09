<?php
declare(strict_types=1);
if (getenv('DAG_CMS_TEST_MODE') !== '1') exit(1);
$config = [
    'site_type' => 'company',
    'site_template' => 'company',
    'template_design' => '{"accent":"#aa6633","font":"system"}',
    'template_content' => '{"title":"Тестовая компания","header_links":[{"label":"О нас","url":"/?p=about"}]}',
    'template_sections' => '["features","news","contact"]',
];
function config_value(string $key, string $default=''): string {
    global $config;
    return $config[$key] ?? $default;
}
require dirname(__DIR__) . '/app/templates.php';
function test_check(bool $ok, string $message): void {
    if (!$ok) { fwrite(STDERR,'[FAIL] '.$message.PHP_EOL);exit(1); }
    echo '[OK] '.$message.PHP_EOL;
}
$catalog=template_catalog();
test_check(array_keys($catalog)===['organization','company','store','government'],'Четыре шаблона в каталоге');
foreach($catalog as $id=>$preset){
    test_check(isset($preset['title'],$preset['accent'],$preset['sections']), 'Данные шаблона '.$id);
    $preview=template_design($id);
    test_check(preg_match('/^#[0-9a-fA-F]{6}$/',$preview['accent'])===1,'Цвет шаблона '.$id);
    $markup=template_style($preview,$id);
    test_check(str_contains($markup,'--site-accent:')&&str_contains($markup,'--site-font:'),'Тема шаблона '.$id);
}
test_check(site_template()==='company','Текущий шаблон');
test_check(template_content()['title']==='Тестовая компания','Редактируемый заголовок');
test_check(template_content('government')['title']==='Тестовая компания','Предпросмотр сохраняет пользовательский текст');
test_check(template_design()['accent']==='#aa6633','Пользовательский акцент');
test_check(template_design('store')['accent']===$catalog['store']['accent'],'Изолированный предпросмотр');
test_check(template_active_sections()===['features','news','contact'],'Порядок блоков');
test_check(template_active_sections('store')===$catalog['store']['sections'],'Блоки предпросмотра');
foreach(['/','/?p=about','/?kind=news','#contact','https://example.ru/page'] as $url) {
    test_check(safe_template_url($url), 'Допустимый адрес '.$url);
}
foreach(['javascript:alert(1)','data:text/html,foo','//evil.example','https://example.org/\nheader','/bad link'] as $url) {
    test_check(!safe_template_url($url), 'Запрещённый адрес '.$url);
}
echo "Шаблоны CMS: тесты успешно завершены.\n";
