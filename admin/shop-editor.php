<?php
declare(strict_types=1);
if(!defined('DAG_CMS_ADMIN_VIEW')){http_response_code(404);exit;}
cms_shop_tables();$settings=cms_shop_settings();$categories=cms_shop_categories();
?>
<div class="heading-row"><div><div class="eyebrow">МАГАЗИН / ВИТРИНА</div><h1>Настройки магазина</h1>
<p class="muted">Настройте каталог, лучшие товары и категории, которые появятся на главной магазина. Все изменения сохраняются отдельно от других шаблонов.</p></div>
<a class="button button-outline" href="/?kind=product" target="_blank" rel="noopener"><i class="fa-solid fa-arrow-up-right-from-square"></i> Каталог</a></div>
<div class="cms-shop-admin-grid">
<section class="box cms-shop-admin-panel"><h2><i class="fa-solid fa-border-all"></i> Категории товаров</h2>
<p class="muted">Создайте группы товаров. Категорию можно назначать при редактировании товара.</p>
<div class="cms-shop-category-list">
<?php foreach($categories as $cat):?>
<form class="cms-shop-category-row" action="/admin/actions.php" method="post">
 <?=csrf()?><input type="hidden" name="action" value="shop_save_category"><input type="hidden" name="id" value="<?=(int)$cat['id']?>">
 <label>Название<input name="name" maxlength="120" value="<?=h($cat['name'])?>" required></label>
 <label>Порядок<input type="number" name="order" min="1" max="9999" value="<?=(int)$cat['sort_order']?>" required></label>
 <button class="button button-outline" type="submit" title="Сохранить категорию"><i class="fa-solid fa-floppy-disk"></i></button>
 <button class="button button-outline cms-shop-category-delete" type="submit" name="action" value="shop_delete_category" formnovalidate onclick="return confirm('Удалить категорию? Товары останутся в каталоге без категории.')" title="Удалить категорию"><i class="fa-solid fa-trash"></i></button>
</form>
<?php endforeach;?>
</div>
<form class="cms-shop-category-new" action="/admin/actions.php" method="post">
 <?=csrf()?><input type="hidden" name="action" value="shop_save_category"><input type="hidden" name="id" value="0">
 <label>Новая категория<input name="name" maxlength="120" required placeholder="Например: Одежда"></label>
 <label>Порядок<input name="order" type="number" min="1" max="9999" value="100" required></label>
 <button class="button" type="submit"><i class="fa-solid fa-plus"></i> Добавить</button>
</form>
</section>
<section class="box cms-shop-admin-panel"><h2><i class="fa-solid fa-star"></i> Главная и каталог</h2>
<form action="/admin/actions.php" method="post" class="cms-shop-settings-form">
 <?=csrf()?><input type="hidden" name="action" value="shop_save_settings">
 <label class="check"><input name="home_categories" value="1" type="checkbox" <?=$settings['home_categories']?'checked':''?>> Показывать категории на главной</label>
 <label class="check"><input name="home_featured" value="1" type="checkbox" <?=$settings['home_featured']?'checked':''?>> Показывать лучшие товары</label>
 <label class="check"><input name="home_catalog" value="1" type="checkbox" <?=$settings['home_catalog']?'checked':''?>> Показывать каталог на главной</label>
 <label>Заголовок лучших товаров<input name="featured_title" maxlength="120" value="<?=h($settings['featured_title'])?>"></label>
 <label>Лучших товаров на главной<input name="featured_limit" type="number" min="1" max="24" value="<?=(int)$settings['featured_limit']?>" required></label>
 <label>Заголовок каталога<input name="catalog_title" maxlength="120" value="<?=h($settings['catalog_title'])?>"></label>
 <label>Число товаров на главной<input name="catalog_limit" type="number" min="1" max="24" value="<?=(int)$settings['catalog_limit']?>" required></label>
 <fieldset><legend>Доступные способы оплаты / оформления</legend>
 <?php foreach(cms_shop_payment_methods() as $id=>$label):?>
 <label class="check"><input type="checkbox" name="methods[]" value="<?=h($id)?>" <?=in_array($id,$settings['methods'],true)?'checked':''?>> <?=h($label)?></label>
 <?php endforeach;?>
 </fieldset>
 <label>Условия банковского перевода (необязательно)<textarea name="transfer_text" rows="4" maxlength="1500"><?=h($settings['transfer_text'])?></textarea>
 <small class="muted">В CMS пока нет интернет-эквайринга. Эти способы создают заявку; статус оплаты у всех новых заказов «Не оплачен». Не просите покупателей вводить реквизиты карт.</small></label>
 <button class="button" type="submit"><i class="fa-solid fa-floppy-disk"></i> Сохранить настройки магазина</button>
</form></section>
</div>
