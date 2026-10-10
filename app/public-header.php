<?php
declare(strict_types=1);

/**
 * Одна и та же шапка и одно главное меню на всех публичных страницах.
 * Макет повторяет разделы /media.php: логотип + инструменты, меню ниже.
 */
function cms_public_menu_links(string $template): array {
    $links=[];
    $used=[];
    $add=static function(string $label,string $url) use (&$links,&$used):void {
        $label=trim($label);$url=trim($url);
        if($label===''||$url===''||!safe_template_url($url)||isset($used[$url]))return;
        $used[$url]=true;
        $links[]=['label'=>$label,'url'=>$url];
    };
    foreach(cms_menu_visible($template) as $link) {
        $add((string)($link['label']??''),(string)($link['url']??''));
    }
    foreach(cms_module_ids($template,'nav') as $id) {
        $add(cms_module_label($id),cms_module_href($id));
    }
    // Ссылки, добавленные в старом редакторе, остаются до сохранения нового меню.
    if(!cms_menu_configured($template)){
        foreach(template_content($template)['header_links'] as $link){
            if(is_array($link))$add((string)($link['label']??''),(string)($link['url']??''));
        }
    }
    return $links;
}
function cms_render_public_header(string $template): void {
    $content=template_content($template);
    $name=config_value('site_name','DAG STUDIO CMS');
    $logo=(string)($content['logo_path']??'');
    if(!preg_match('~^/assets/uploads/logo-[a-f0-9]{32}\\.(png|jpg|webp)$~D',$logo))$logo='';
    $label=template_catalog()[$template]['label']??'Сайт';
    $links=cms_public_menu_links($template);
    ?>
    <?php if($template==='government'):?>
    <div class="cms-media-official-strip cms-unified-official-strip">ОФИЦИАЛЬНЫЙ САЙТ <span>Информация для граждан и организаций</span></div>
    <?php endif;?>
    <header class="cms-media-header cms-unified-header">
      <div class="cms-media-header-inner">
        <a class="cms-media-brand" href="/">
          <?php if($logo!==''):?>
            <img class="cms-header-uploaded-logo" src="<?=h($logo)?>" alt="" loading="eager">
          <?php else:?>
            <span class="cms-media-brand-mark" aria-hidden="true">
              <img class="logo-on-light" src="/assets/ornament-light.svg" alt="">
              <img class="logo-on-dark" src="/assets/ornament-dark.svg" alt="">
            </span>
          <?php endif;?>
          <span><small><?=h($label)?></small><strong><?=h($name)?></strong></span>
        </a>
        <div class="cms-media-header-actions">
          <?=cms_accessibility_control()?>
          <button class="theme-toggle" type="button" data-theme-toggle aria-label="Переключить светлую и тёмную тему" title="Переключить тему">
            <span class="theme-toggle-dark" aria-hidden="true">☾</span>
            <span class="theme-toggle-light" aria-hidden="true">☼</span>
          </button>
          <?=cms_age_mark()?>
          <a class="cms-header-login" href="/admin/login.php">Вход</a>
        </div>
      </div>
      <nav class="cms-media-site-nav" aria-label="Главное меню">
        <?php foreach($links as $link): ?>
        <a href="<?=h($link['url'])?>" <?=($_SERVER['REQUEST_URI']??'/')===$link['url']?'aria-current="page"':''?>><?=h($link['label'])?></a>
        <?php endforeach;?>
      </nav>
    </header>
    <?php
}
