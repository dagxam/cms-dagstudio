(function () {
  'use strict';
  document.addEventListener('DOMContentLoaded', function () {
    var form=document.querySelector('[data-leader-form]');
    if(!form)return;
    var container=document.querySelector('.gov-leader-editor');
    var photo=form.querySelector('[data-leader-photo]');
    var remove=form.querySelector('[data-leader-remove]');
    var img=container.querySelector('[data-leader-preview-img]');
    var placeholder=container.querySelector('[data-leader-preview-placeholder]');
    var status=form.querySelector('[data-leader-upload-status]');
    var existing=img && !img.hidden ? img.getAttribute('src') : '';
    var previewUrl=null;
    function imagePreview(source) {
      if(previewUrl){ URL.revokeObjectURL(previewUrl); previewUrl=null; }
      if(source && typeof source!=='string') {
        previewUrl=URL.createObjectURL(source);
        source=previewUrl;
      }
      if(img){img.hidden=!source;if(source)img.src=source;else img.removeAttribute('src');}
      if(placeholder)placeholder.hidden=!!source;
    }
    [['title','title','Глава администрации'],['name','name','Фамилия Имя Отчество'],
     ['initials','initials','Инициалы руководителя'],['description','description','']].forEach(function(item) {
      var source=form.querySelector('[data-leader-'+item[0]+']');
      var target=container.querySelector('[data-leader-preview-'+item[1]+']');
      if(!source||!target)return;
      source.addEventListener('input',function(){target.textContent=source.value.trim()||item[2];});
    });
    if(photo)photo.addEventListener('change',function(){
      var file=photo.files && photo.files[0];
      if(file){
        if(file.size>2*1024*1024 || !['image/jpeg','image/png','image/webp'].includes(file.type)){
          photo.value='';
          if(status)status.textContent='Выберите JPG, PNG или WebP размером до 2 МБ.';
          imagePreview(remove && remove.checked ? '' : existing);
          return;
        }
        if(remove)remove.checked=false;
        imagePreview(file);
        if(status)status.textContent='Выбрано: '+file.name+'. Изображение сохранится после нажатия кнопки.';
      }else{
        imagePreview(remove && remove.checked ? '' : existing);
        if(status)status.textContent='Новое фото не выбрано.';
      }
    });
    if(remove)remove.addEventListener('change',function(){
      if(remove.checked && photo)photo.value='';
      imagePreview(remove.checked?'':existing);
      if(status)status.textContent=remove.checked?'Фото будет удалено после сохранения.':'Сохранённая фотография останется на сайте.';
    });
    window.addEventListener('pagehide',function(){if(previewUrl)URL.revokeObjectURL(previewUrl);});
  });
}());
