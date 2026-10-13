<?php
declare(strict_types=1);

/**
 * Одна и та же шапка и одно главное меню на всех публичных страницах.
 * Макет повторяет разделы /media.php: логотип + инструменты, меню ниже.
 */
function cms_public_menu_links(string $template): array {
    $links=[];
    $used=[];
    $add=static function(string $label,string $url,array $children=[]) use (&$links,&$used):void {
        $label=trim($label);$url=trim($url);
        if($label===''||$url===''||!safe_template_url($url)||isset($used[$url]))return;
        $used[$url]=true;
        $childLinks=[];
        foreach($children as $child){
            $title=(string)($child['label']??'');
            $target=(string)($child['url']??'');
            if($title===''||$target===''||!safe_template_url($target)||isset($used[$target]))continue;
            $used[$target]=true;$childLinks[]=['label'=>$title,'url'=>$target];
        }
        $links[]=['label'=>$label,'url'=>$url,'children'=>$childLinks];
    };
    foreach(cms_menu_visible($template) as $link) {
        $add((string)($link['label']??''),(string)($link['url']??''),$link['children']??[]);
    }
    foreach(cms_menu_module_entries($template) as $entry) {
        $id=$entry['id'];
        if(!cms_module_enabled($id))continue;
        $url=cms_module_href($id);
        // Модуль уже может быть указан вручную, в том числе как подпункт.
        if(isset($used[$url]))continue;
        $parent=$entry['parent'];
        $inserted=false;
        if($parent!==''){
            foreach($links as &$link){
                if($link['url']!==$parent)continue;
                if(count($link['children'])>=10)break;
                $link['children'][]=['label'=>cms_module_label($id),'url'=>$url];
                $used[$url]=true;
                $inserted=true;
                break;
            }
            unset($link);
        }
        if(!$inserted)$add(cms_module_label($id),$url);
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
    <?php if($template==='government'):
      $govHeader=government_layout();?>
    <div class="cms-media-official-strip cms-unified-official-strip">
      <strong>ОФИЦИАЛЬНЫЙ САЙТ</strong>
      <?php if($govHeader['show_topbar']==='1'):?>
        <?php if($govHeader['location']!==''):?><span><?=h($govHeader['location'])?></span><?php endif;?>
        <?php if($govHeader['show_date']==='1'):?><time data-government-datetime datetime="<?=h(date('Y-m-d'))?>"><?=h(date('d.m.Y'))?></time><?php endif;?>
        <?php if($govHeader['office_phone']!==''):?><span><?=h($govHeader['office_phone'])?></span><?php endif;?>
      <?php else:?><span>Информация для граждан и организаций</span><?php endif;?>
    </div>
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
            <span class="theme-toggle-dark" aria-hidden="true"><i class="fa-solid fa-moon"></i></span>
            <span class="theme-toggle-light" aria-hidden="true"><i class="fa-solid fa-sun"></i></span>
          </button>
          <?=cms_age_mark()?>
          <a class="cms-header-login" href="/admin/login.php">Вход</a>
        </div>
      </div>
      <nav class="cms-media-site-nav" aria-label="Главное меню">
        <?php foreach($links as $link): ?>
        <?php if(!empty($link['children'])):?>
        <details class="cms-nav-dropdown">
          <summary><?=h($link['label'])?> <i class="fa-solid fa-chevron-down" aria-hidden="true"></i></summary>
          <div class="cms-nav-submenu">
            <?php if($link['url']!=='#submenu'):?><a href="<?=h($link['url'])?>" <?=($_SERVER['REQUEST_URI']??'/')===$link['url']?'aria-current="page"':''?>><?=h($link['label'])?> — обзор</a><?php endif;?>
            <?php foreach($link['children'] as $child):?><a href="<?=h($child['url'])?>" <?=($_SERVER['REQUEST_URI']??'/')===$child['url']?'aria-current="page"':''?>><?=h($child['label'])?></a><?php endforeach;?>
          </div>
        </details>
        <?php else:?>
        <a href="<?=h($link['url'])?>" <?=($_SERVER['REQUEST_URI']??'/')===$link['url']?'aria-current="page"':''?>><?=h($link['label'])?></a>
        <?php endif;?>
        <?php endforeach;?>
      </nav>
      <script src="/assets/menu-dropdown.js?v=1" defer></script>
    </header>
    <?php
}
