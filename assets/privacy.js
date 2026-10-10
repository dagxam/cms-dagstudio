(function(){
'use strict';
var banner=document.querySelector('[data-privacy-banner]');
var prefs=null;
function read(){try{var s=localStorage.getItem('dagstudio-video-privacy-v1');return s==='allow'?'allow':s==='deny'?'deny':null;}catch(e){return null;}}
function save(value){prefs=value;try{localStorage.setItem('dagstudio-video-privacy-v1',value);}catch(e){} apply();}
function renderVideos(){
  document.querySelectorAll('[data-privacy-video-url]').forEach(function(holder){
    if(holder.dataset.privacyLoaded==='1')return;
    var url=holder.dataset.privacyVideoUrl;
    if(!url||!url.startsWith('https://'))return;
    if(prefs!=='allow')return;
    var parsed;try{parsed=new URL(url);}catch(e){return;}
    if(!['vkvideo.ru','rutube.ru'].includes(parsed.hostname))return;
    var f=document.createElement('iframe');
    f.src=url;f.loading='lazy';f.title=holder.dataset.videoTitle||'Видеоматериал';
    f.allow='autoplay; fullscreen; picture-in-picture';f.allowFullscreen=true;
    f.referrerPolicy='strict-origin-when-cross-origin';
    holder.dataset.privacyLoaded='1';holder.replaceChildren(f);
  });
}
function apply(){
 if(banner)banner.hidden=prefs!==null;
 renderVideos();
 document.querySelectorAll('[data-privacy-video-enable]').forEach(function(button){
   button.hidden=prefs==='allow';
 });
}
prefs=read();apply();
document.querySelectorAll('[data-privacy-open]').forEach(function(btn){btn.addEventListener('click',function(){
 if(banner){banner.hidden=false;banner.querySelector('[data-privacy-reject]')?.focus();}
});});
if(banner){
 banner.querySelector('[data-privacy-reject]')?.addEventListener('click',function(){save('deny');});
 banner.querySelector('[data-privacy-accept]')?.addEventListener('click',function(){save('allow');});
}
document.querySelectorAll('[data-privacy-video-enable]').forEach(function(btn){btn.addEventListener('click',function(){
 save('allow');
});});
}());
