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

  });
}());
