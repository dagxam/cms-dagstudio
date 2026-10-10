(function(){
 'use strict';
 var body=document.body;if(!body)return;
 var controls=document.querySelectorAll('[data-accessibility-toggle]');
 var toolbar=document.querySelector('[data-vision-toolbar]');
 var defaults={scale:String(body.dataset.visionScale||'150'),contrast:String(body.dataset.visionContrast||'high'),
   underlines:body.dataset.visionUnderline!=='0',motion:body.dataset.visionMotion!=='0'};
 var prefs={scale:defaults.scale,contrast:defaults.contrast,underlines:defaults.underlines,motion:defaults.motion};
 var active=false;
 try{
   active=localStorage.getItem('dagstudio-vision-active')==='1';
   var saved=JSON.parse(localStorage.getItem('dagstudio-vision-options')||'null');
   if(saved&&typeof saved==='object'){
     if(['125','150','175','200'].includes(saved.scale))prefs.scale=saved.scale;
     if(['high','blackwhite','yellowblack'].includes(saved.contrast))prefs.contrast=saved.contrast;
     if(typeof saved.underlines==='boolean')prefs.underlines=saved.underlines;
     if(typeof saved.motion==='boolean')prefs.motion=saved.motion;
   }
 }catch(e){}
 function write(){
   try{localStorage.setItem('dagstudio-vision-active',active?'1':'0');
       localStorage.setItem('dagstudio-vision-options',JSON.stringify(prefs));}catch(e){}
 }
 function apply(){
   body.classList.toggle('cms-vision-active',active);
   body.classList.toggle('cms-vision-wide-lines',active&&body.dataset.visionSpacing==='wide');
   body.classList.toggle('cms-vision-wide-letters',active&&body.dataset.visionLetters==='wide');
   body.classList.toggle('cms-vision-underlines',active&&prefs.underlines);
   body.classList.toggle('cms-vision-grayscale',active&&body.dataset.visionGrayscale==='1');
   body.classList.toggle('cms-vision-hide-decorations',active&&body.dataset.visionImages==='0');
   body.classList.toggle('cms-vision-motion-off',active&&prefs.motion);
   if(active){
     body.style.setProperty('--cms-vision-scale',String(Number(prefs.scale)/100));
     body.dataset.visionActiveContrast=prefs.contrast;
   }else{
     body.style.removeProperty('--cms-vision-scale');
     delete body.dataset.visionActiveContrast;
   }
   controls.forEach(function(b){
     b.setAttribute('aria-pressed',String(active));
     b.setAttribute('aria-expanded',String(active));
     b.setAttribute('aria-label',active?'Выключить версию для слабовидящих':'Включить версию для слабовидящих');
     var span=b.querySelector('span');if(span)span.textContent=active?'Обычная версия':'Для слабовидящих';
   });
   if(toolbar){
     toolbar.hidden=!active;
     toolbar.querySelectorAll('[data-vision-option]').forEach(function(el){
       var key=el.getAttribute('data-vision-option');
       if(el.type==='checkbox')el.checked=!!prefs[key];else el.value=String(prefs[key]);
     });
   }
 }
 controls.forEach(function(b){b.addEventListener('click',function(){
   active=!active;apply();write();
   if(active&&toolbar)toolbar.querySelector('select')?.focus();
 });});
 if(toolbar){
   toolbar.addEventListener('change',function(event){
     var el=event.target;
     if(!el||!el.matches('[data-vision-option]'))return;
     var key=el.getAttribute('data-vision-option');
     if(key==='scale'&&['125','150','175','200'].includes(el.value))prefs.scale=el.value;
     else if(key==='contrast'&&['high','blackwhite','yellowblack'].includes(el.value))prefs.contrast=el.value;
     else if(key==='underlines'||key==='motion')prefs[key]=el.checked;
     apply();write();
   });
   var reset=toolbar.querySelector('[data-vision-reset]');
   if(reset)reset.addEventListener('click',function(){prefs=Object.assign({},defaults);apply();write();});
 }
 apply();
 var gate=document.querySelector('[data-age-gate]');
 if(gate){
   var granted=false;try{granted=sessionStorage.getItem('dagstudio-age-18-confirmed')==='yes';}catch(e){}
   if(!granted){
     gate.hidden=false;body.classList.add('cms-age-modal-open');
     var confirm=gate.querySelector('[data-age-confirm]');
     if(confirm){confirm.focus();confirm.addEventListener('click',function(){
       try{sessionStorage.setItem('dagstudio-age-18-confirmed','yes');}catch(e){}
       gate.hidden=true;body.classList.remove('cms-age-modal-open');
     });}
     gate.addEventListener('keydown',function(event){
       if(event.key==='Tab'){
         var focusables=Array.from(gate.querySelectorAll('button,a[href]')).filter(function(x){return !x.disabled;});
         if(focusables.length){
           var i=focusables.indexOf(document.activeElement);
           if(event.shiftKey&&i<=0){event.preventDefault();focusables[focusables.length-1].focus();}
           else if(!event.shiftKey&&i===focusables.length-1){event.preventDefault();focusables[0].focus();}
         }
       }
     });
   }
 }
}());
