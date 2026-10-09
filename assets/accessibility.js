(function(){
  'use strict';
  var body=document.body;
  if(!body)return;
  var button=document.querySelector('[data-accessibility-toggle]');
  var saved=false;
  try{saved=localStorage.getItem('dagstudio-vision-active')==='1';}catch(e){}
  function applyVision(active){
    body.classList.toggle('cms-vision-active',active);
    body.classList.toggle('cms-vision-wide-lines',active && body.dataset.visionSpacing==='wide');
    body.classList.toggle('cms-vision-wide-letters',active && body.dataset.visionLetters==='wide');
    body.classList.toggle('cms-vision-underlines',active && body.dataset.visionUnderline==='1');
    body.classList.toggle('cms-vision-grayscale',active && body.dataset.visionGrayscale==='1');
    body.classList.toggle('cms-vision-hide-decorations',active && body.dataset.visionImages==='0');
    if(active){
      body.style.setProperty('--cms-vision-scale',String(Number(body.dataset.visionScale||150)/100));
      body.dataset.visionActiveContrast=body.dataset.visionContrast||'high';
    }else{
      body.style.removeProperty('--cms-vision-scale');
      delete body.dataset.visionActiveContrast;
    }
    if(button){
      button.setAttribute('aria-pressed',String(active));
      button.setAttribute('aria-label',active?'Выключить специальный режим':'Включить версию для слабовидящих');
      var title=button.querySelector('span');
      if(title)title.textContent=active?'Обычная версия':'Для слабовидящих';
    }
  }
  var isActive=saved && !!button;
  applyVision(isActive);
  if(button)button.addEventListener('click',function(){
    isActive=!isActive;
    try{localStorage.setItem('dagstudio-vision-active',isActive?'1':'0')}catch(e){}
    applyVision(isActive);
  });
  var gate=document.querySelector('[data-age-gate]');
  if(gate){
    var granted=false;
    try{granted=sessionStorage.getItem('dagstudio-age-18-confirmed')==='yes'}catch(e){}
    if(!granted){
      gate.hidden=false;
      document.body.classList.add('cms-age-modal-open');
      var confirm=gate.querySelector('[data-age-confirm]');
      if(confirm){
        confirm.focus();
        confirm.addEventListener('click',function(){
          try{sessionStorage.setItem('dagstudio-age-18-confirmed','yes')}catch(e){}
          gate.hidden=true;
          document.body.classList.remove('cms-age-modal-open');
        });
      }
    }
  }
}());
