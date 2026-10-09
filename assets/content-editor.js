(function(){
  'use strict';
  function attach(){
    var input=document.querySelector('.cms-cover-editor input[name="cover_image"]');
    var preview=document.querySelector('.cms-cover-editor-preview');
    if(!input||!preview)return;
    var image=preview.querySelector('img');
    var currentUrl=null;
    input.addEventListener('change',function(){
      if(currentUrl){URL.revokeObjectURL(currentUrl);currentUrl=null;}
      var file=input.files && input.files[0];
      if(!file)return;
      if(!['image/jpeg','image/png','image/webp'].includes(file.type) || file.size>8*1024*1024){
        input.setCustomValidity('Выберите JPG, PNG или WebP размером до 8 МБ.');
        input.reportValidity();
        return;
      }
      input.setCustomValidity('');
      currentUrl=URL.createObjectURL(file);
      if(!image){
        image=document.createElement('img');
        image.loading='eager';
        image.alt='Предпросмотр новой обложки';
        preview.appendChild(image);
      }
      image.src=currentUrl;
      image.style.display='block';
      preview.classList.add('has-preview');
    });
    input.addEventListener('input',function(){input.setCustomValidity('');});
    window.addEventListener('pagehide',function(){if(currentUrl)URL.revokeObjectURL(currentUrl);},{once:true});
  }
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',attach);
  else attach();
}());
