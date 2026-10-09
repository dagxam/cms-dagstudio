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
    test_check(str_contains($markup,'--site-radius:')&&str_contains($markup,'--site-font:'),'Размеры и шрифты шаблона '.$id);
    $css=template_palette_css($preview,$id);
    test_check(str_contains($css,'html[data-theme="light"] body.site-page{') &&
      str_contains($css,'html[data-theme="dark"] body.site-page{'),'Две отдельные палитры '.$id);
    test_check(str_contains($css,'--site-surface:') && str_contains($css,'--site-border:') &&
      str_contains($css,'--site-button-ink:'),'Цвета карточек и кнопок '.$id);
    foreach(['light','dark'] as $mode) {
      foreach(['accent','background','ink','surface','border'] as $key) {
        test_check(preg_match('/^#[0-9a-fA-F]{6}$/',$preview['palettes'][$mode][$key])===1,
          "Поле $id $mode $key");
      }
    }
}
test_check(site_template()==='company','Текущий шаблон');
test_check(template_content()['title']==='Тестовая компания','Редактируемый заголовок');
test_check(template_content('government')['title']===$catalog['government']['title'],'Предпросмотр показывает тексты выбранного шаблона');
test_check(template_design()['accent']==='#aa6633','Пользовательский акцент');
test_check(template_design()['palettes']['dark']['accent']==='#aa6633','Сохранён старый цвет компании в тёмной теме');
test_check(template_design()['palettes']['light']['accent']!== '#aa6633','Светлая тема не наследует старый тёмный цвет');
test_check(template_design('store')['accent']===$catalog['store']['accent'],'Изолированный предпросмотр');
test_check(template_active_sections()===['features','news','contact'],'Порядок блоков');
test_check(template_active_sections('store')===$catalog['store']['sections'],'Блоки предпросмотра');
$config['template_design_by_type']='{"store":{"accent":"#112233","palettes":{"light":{"accent":"#123456","background":"#fefefe","ink":"#101010","surface":"#fafafa","border":"#cccccc"},"dark":{"accent":"#fedcba","background":"#10151a","ink":"#ffffff","surface":"#20272f","border":"#424850"}}}}';
$config['template_content_by_type']='{"store":{"title":"Товары нашей компании"}}';
$config['template_sections_by_type']='{"store":["product","contact"]}';
test_check(template_design('store')['accent']==='#112233','Обратная совместимость старого значения магазина');
test_check(template_design('store')['palettes']['light']['accent']==='#123456','Независимый акцент светлого магазина');
test_check(template_design('store')['palettes']['dark']['accent']==='#fedcba','Независимый акцент тёмного магазина');
test_check(str_contains(template_palette_css(template_design('store'),'store'),'--site-bg:#10151a'),'Тёмная тема применяет собственный фон');
test_check(template_button_text('#ffffff')==='#101820','Тёмный текст на светлой кнопке');
test_check(template_button_text('#000000')==='#ffffff','Белый текст на тёмной кнопке');
$attempt = template_design('store');
$attempt['palettes']['light']['accent'] = 'red; background:url(javascript:alert(1))';
$attempt['palettes']['dark']['background'] = 'transparent';
$safeCss = template_palette_css($attempt, 'store');
test_check(!str_contains($safeCss,'javascript:') && !str_contains($safeCss,'transparent'),
  'В CSS не попадают недопустимые значения цветов');
test_check(template_default_mode('company')==='dark' && template_default_mode('government')==='light',
  'Стандартные темы соответствуют характеру сайтов');

test_check(template_content('store')['title']==='Товары нашей компании','Индивидуальный заголовок магазина');
test_check(template_active_sections('store')===['product','contact'],'Индивидуальный набор блоков магазина');
foreach(['/','/?p=about','/?kind=news','#contact','https://example.ru/page'] as $url) {
    test_check(safe_template_url($url), 'Допустимый адрес '.$url);
}
foreach(['javascript:alert(1)','data:text/html,foo','//evil.example',"https://example.org/\nheader",'/bad link'] as $url) {
    test_check(!safe_template_url($url), 'Запрещённый адрес '.$url);
}
echo "Шаблоны CMS: тесты успешно завершены.\n";
