(function(){
  'use strict';
  function luminance(hex) {
    var value=hex.replace('#','');
    var rgb=[0,2,4].map(function(i){
      var v=parseInt(value.slice(i,i+2),16)/255;
      return v<=0.04045?v/12.92:Math.pow((v+0.055)/1.055,2.4);
    });
    return rgb[0]*0.2126+rgb[1]*0.7152+rgb[2]*0.0722;
  }
  function ratio(a,b) {
    var x=luminance(a),y=luminance(b);
    return (Math.max(x,y)+0.05)/(Math.min(x,y)+0.05);
  }
  function paint(panel) {
    var preview=panel.querySelector('[data-palette-preview]');
    if(!preview)return;
    var vals={};
    panel.querySelectorAll('[data-palette-color]').forEach(function(input){
      var key=input.getAttribute('data-palette-color');
      vals[key]=input.value.toLowerCase();
      preview.style.setProperty('--preview-'+key, vals[key]);
      var hex=input.closest('.template-palette-color-line').querySelector('.template-palette-hex');
      if(hex)hex.textContent=vals[key].toUpperCase();
    });
    preview.style.setProperty('--preview-button-ink', ratio(vals.accent,'#ffffff')>=ratio(vals.accent,'#101820')?'#ffffff':'#101820');
    var status=panel.querySelector('[data-palette-contrast]');
    if(status){
      var score=ratio(vals.ink,vals.background);
      status.textContent='Контраст текста: '+score.toFixed(1)+':1'+(score>=4.5?' ✓':' — рекомендуется от 4.5:1');
      status.style.color=score>=4.5?'var(--muted)':'#df7159';
    }
  }
  document.addEventListener('DOMContentLoaded',function(){
    document.querySelectorAll('[data-palette-panel]').forEach(function(panel){
      panel.querySelectorAll('[data-palette-color]').forEach(function(input){
        input.addEventListener('input',function(){paint(panel)});
        input.addEventListener('change',function(){paint(panel)});
      });
      var reset=panel.querySelector('[data-palette-reset]');
      if(reset)reset.addEventListener('click',function(){
        panel.querySelectorAll('[data-palette-color]').forEach(function(input){
          var original=input.getAttribute('data-default-color');
          if(original)input.value=original;
        });
        paint(panel);
      });
      paint(panel);
    });
  });
}());
