document.addEventListener('click',e=>{
  const t=e.target;
  if(t.matches('[data-remove]')){
    e.preventDefault();
    const id=t.getAttribute('data-remove');
    const f=document.getElementById('cart-form');
    const i=document.createElement('input');
    i.type='hidden';i.name='remove';i.value=id;f.appendChild(i);f.submit();
  }
});
document.addEventListener('DOMContentLoaded',()=>{
  const tiles=document.querySelectorAll('.tile');
  tiles.forEach((el,idx)=>{
    el.style.animationDelay=(idx%5)*0.4+'s';
  });
});