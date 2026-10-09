(function () {
  'use strict';
  document.addEventListener('DOMContentLoaded', function () {
    var clock=document.querySelector('[data-government-datetime]');
    if(clock){
      var renderClock=function(){
        var now=new Date();
        clock.textContent=new Intl.DateTimeFormat('ru-RU',{
          day:'2-digit',month:'2-digit',year:'numeric',hour:'2-digit',minute:'2-digit'
        }).format(now);
        clock.dateTime=now.toISOString();
      };
      renderClock();
      window.setInterval(renderClock,60000);
    }
    var print=document.querySelector('[data-government-print]');
    if(print)print.addEventListener('click',function(){window.print()});
    var accessible=document.querySelector('[data-government-accessibility]');
    var key='dagstudio-government-accessibility';
    var active=false;
    try{active=localStorage.getItem(key)==='yes'}catch(e){}
    function apply(){
      document.body.classList.toggle('government-large-text',active);
      if(accessible)accessible.setAttribute('aria-pressed',String(active));
    }
    apply();
    if(accessible)accessible.addEventListener('click',function(){
      active=!active;
      try{localStorage.setItem(key,active?'yes':'no')}catch(e){}
      apply();
    });
  });
}());
