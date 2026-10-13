<?php
declare(strict_types=1);
function cms_shop_category_id(string $slug):?int{
    foreach(cms_shop_categories() as $category)if($category['slug']===$slug)return (int)$category['id'];
    return null;
}
function cms_shop_card_list(array $items):void{
    $covers=cms_content_image_map($items);
    echo '<div class="cms-shop-products">';
    foreach($items as $item){
        $id=(int)$item['id'];$url='/?p='.rawurlencode($item['slug']);
        echo '<article class="cms-shop-product-card">';
        echo '<a href="'.h($url).'" class="cms-shop-product-media">';
        if(isset($covers[$id]))echo '<img src="'.h(cms_content_image_url($id,$covers[$id])).'" loading="lazy" decoding="async" alt="'.h($covers[$id]['alt_text']?:$item['title']).'">';
        else echo '<span class="cms-shop-product-placeholder" aria-hidden="true"><i class="fa-solid fa-bag-shopping"></i></span>';
        echo '</a><div class="cms-shop-product-info">';
        if(!empty($item['featured']))echo '<span class="cms-shop-product-badge"><i class="fa-solid fa-star"></i> Лучший товар</span>';
        if(($item['category_name']??'')!=='')echo '<small>'.h($item['category_name']).'</small>';
        echo '<h3><a href="'.h($url).'">'.h($item['title']).'</a></h3>';
        if(!empty($item['summary']))echo '<p>'.h(mb_strimwidth($item['summary'],0,115,'…','UTF-8')).'</p>';
        if($item['price']!==null){
            echo '<strong>'.h(number_format((float)$item['price'],2,',',' ')).' ₽</strong>';
            if((float)$item['price']>0)echo '<a class="cms-shop-buy" href="/order.php?product='.$id.'"><i class="fa-solid fa-bag-shopping"></i> Заказать</a>';
        }else echo '<a class="cms-shop-buy cms-shop-more" href="'.h($url).'">Подробнее</a>';
        echo '</div></article>';
    }
    echo '</div>';
}
function cms_shop_render_home():void{
    if(!cms_module_enabled('product'))return;
    $s=cms_shop_settings();
    if($s['home_categories']){
        $categories=cms_shop_categories();
        if($categories){
            echo '<section class="cms-shop-home-section cms-shop-categories" aria-labelledby="shop-categories-title">';
            echo '<div class="cms-shop-title-line"><span class="eyebrow">МАГАЗИН</span><h2 id="shop-categories-title">Категории товаров</h2></div><div class="cms-shop-category-grid">';
            foreach($categories as $c){
                echo '<a class="cms-shop-category-link" href="/?kind=product&amp;category='.rawurlencode($c['slug']).'"><i class="fa-solid fa-layer-group" aria-hidden="true"></i><span>'.h($c['name']).'</span><i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>';
            }
            echo '</div></section>';
        }
    }
    if($s['home_featured']){
        $featured=cms_shop_products(null,'custom',true,$s['featured_limit']);
        if($featured){
            echo '<section class="cms-shop-home-section" aria-labelledby="shop-featured-title">';
            echo '<div class="cms-shop-title-line"><div><span class="eyebrow">РЕКОМЕНДУЕМ</span><h2 id="shop-featured-title">'.h($s['featured_title']).'</h2></div>';
            echo '<a href="/?kind=product">Все товары <i class="fa-solid fa-arrow-right"></i></a></div>';
            cms_shop_card_list($featured);echo '</section>';
        }
    }
}
function cms_shop_render_catalog(bool $home):void{
    $s=cms_shop_settings();
    if($home && !$s['home_catalog'])return;
    if(!$home){
        $selected=(string)($_GET['category']??'');
        $sort=(string)($_GET['sort']??'custom');
        if(!isset(cms_shop_sort_types()[$sort]))$sort='custom';
        $cat=$selected===''?null:cms_shop_category_id($selected);
        $items=($selected!=='' && $cat===null)?[]:cms_shop_products($cat,$sort,false,60);
    }else{$selected='';$sort='custom';$items=cms_shop_products(null,'custom',false,$s['catalog_limit']);}
    echo '<section class="cms-shop-home-section cms-shop-catalog" id="shop-catalog">';
    echo '<div class="cms-shop-title-line"><div><span class="eyebrow">КАТАЛОГ</span><h2>'.h($s['catalog_title']).'</h2></div>';
    if($home)echo '<a href="/?kind=product">Открыть каталог <i class="fa-solid fa-arrow-right"></i></a>';
    echo '</div>';
    if(!$home){
        echo '<form method="get" class="cms-shop-filters" action="/">';
        echo '<input type="hidden" name="kind" value="product"><label>Категория<select name="category"><option value="">Все категории</option>';
        foreach(cms_shop_categories() as $cat)echo '<option value="'.h($cat['slug']).'" '.($selected===$cat['slug']?'selected':'').'>'.h($cat['name']).'</option>';
        echo '</select></label><label>Сортировка<select name="sort">';
        foreach(cms_shop_sort_types() as $id=>$label)echo '<option value="'.h($id).'" '.($id===$sort?'selected':'').'>'.h($label).'</option>';
        echo '</select></label><button class="button" type="submit"><i class="fa-solid fa-filter"></i> Показать</button></form>';
    }
    if($items)cms_shop_card_list($items);
    else echo '<div class="box empty-state">В этой категории пока нет опубликованных товаров.</div>';
    echo '</section>';
}
