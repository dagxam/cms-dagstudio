(function(){
  'use strict';
  document.addEventListener('DOMContentLoaded',function(){
    var composer=document.querySelector('[data-gov-composer]');
    if(!composer)return;
    var list=composer.querySelector('[data-gov-order-list]');
    var empty=composer.querySelector('[data-gov-order-empty]');
    var icons={page:'fa-file-lines',news:'fa-newspaper',service:'fa-screwdriver-wrench',
      product:'fa-box-open',documents:'fa-file-pdf',photos:'fa-images',
      videos:'fa-video',features:'fa-star',contact:'fa-envelope'};
    function icon(className){
      var i=document.createElement('i');
      i.className='fa-solid '+className;
      i.setAttribute('aria-hidden','true');
      return i;
    }
    function makeButton(direction,label){
      var button=document.createElement('button');
      button.type='button';
      button.dataset.govMove=direction;
      button.title=direction==='up'?'Выше':'Ниже';
      button.setAttribute('aria-label',(direction==='up'?'Поднять ':'Опустить ')+label);
      button.appendChild(icon(direction==='up'?'fa-arrow-up':'fa-arrow-down'));
      return button;
    }
    function createRow(id,label){
      var row=document.createElement('div');
      row.className='gov-composer-order-row';
      row.dataset.govToken='m:'+id;
      var hidden=document.createElement('input');
      hidden.type='hidden';hidden.name='home_order[]';hidden.value='m:'+id;
      row.appendChild(hidden);
      var grip=document.createElement('div');grip.className='gov-composer-order-grip';
      grip.appendChild(icon('fa-grip-lines'));row.appendChild(grip);
      var mark=document.createElement('span');mark.className='gov-composer-order-icon';
      mark.appendChild(icon(icons[id]||'fa-layer-group'));row.appendChild(mark);
      var block=document.createElement('div');block.className='gov-composer-order-label';
      var title=document.createElement('strong');title.textContent=label;block.appendChild(title);
      var category=document.createElement('small');category.textContent='Встроенный модуль';
      block.appendChild(category);row.appendChild(block);
      var arrows=document.createElement('div');arrows.className='gov-composer-arrows';
      arrows.appendChild(makeButton('up',label));arrows.appendChild(makeButton('down',label));
      row.appendChild(arrows);
      return row;
    }
    function refresh(){
      var rows=Array.from(list.querySelectorAll('[data-gov-token]'));
      if(empty)empty.hidden=rows.length!==0;
      rows.forEach(function(row,index){
        var up=row.querySelector('[data-gov-move="up"]');
        var down=row.querySelector('[data-gov-move="down"]');
        if(up)up.disabled=index===0;
        if(down)down.disabled=index===rows.length-1;
      });
    }
    composer.querySelectorAll('[data-gov-area]').forEach(function(select){
      var id=select.dataset.moduleId;
      var label=select.dataset.moduleLabel;
      var titleInput=composer.querySelector('[name="title['+id+']"]');
      function sync(){
        var selector='[data-gov-token="m:'+id+'"]';
        var row=list.querySelector(selector);
        var shouldShow=select.value==='main'&&select.dataset.isEnabled==='1';
        if(shouldShow&&!row){
          list.appendChild(createRow(id,(titleInput&&titleInput.value.trim())||label));
        }else if(!shouldShow&&row)row.remove();
        refresh();
      }
      select.addEventListener('change',sync);
      if(titleInput)titleInput.addEventListener('input',function(){
        var row=list.querySelector('[data-gov-token="m:'+id+'"]');
        if(!row)return;
        var name=titleInput.value.trim()||label;
        row.querySelector('.gov-composer-order-label strong').textContent=name;
        row.querySelectorAll('[data-gov-move]').forEach(function(button){
          button.setAttribute('aria-label',(button.dataset.govMove==='up'?'Поднять ':'Опустить ')+name);
        });
      });
      sync();
    });
    list.addEventListener('click',function(event){
      var target=event.target.closest('[data-gov-move]');
      if(!target||target.disabled)return;
      var row=target.closest('[data-gov-token]');
      if(!row)return;
      if(target.dataset.govMove==='up'&&row.previousElementSibling){
        list.insertBefore(row,row.previousElementSibling);
      }else if(target.dataset.govMove==='down'&&row.nextElementSibling){
        list.insertBefore(row.nextElementSibling,row);
      }
      refresh();
    });
    refresh();
  });
}());
