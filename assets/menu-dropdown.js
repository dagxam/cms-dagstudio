document.addEventListener('DOMContentLoaded',function(){
  const menu=document.querySelector('.cms-media-site-nav');
  if(!menu)return;
  const groups=Array.from(menu.querySelectorAll('.cms-nav-dropdown'));
  groups.forEach(group=>group.addEventListener('toggle',()=>{
    if(group.open)groups.forEach(other=>{if(other!==group)other.open=false;});
  }));
  document.addEventListener('pointerdown',event=>{
    groups.forEach(group=>{if(group.open&&!group.contains(event.target))group.open=false;});
  });
  document.addEventListener('keydown',event=>{
    if(event.key==='Escape')groups.forEach(group=>{
      if(group.open){group.open=false;const summary=group.querySelector('summary');if(summary)summary.focus();}
    });
  });
});