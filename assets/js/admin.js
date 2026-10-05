document.addEventListener('DOMContentLoaded',()=>{
 const btn=document.querySelector('[data-admin-menu]'); const side=document.querySelector('.sidebar'); if(btn&&side)btn.addEventListener('click',()=>side.classList.toggle('open'));
 document.querySelectorAll('[data-confirm]').forEach(el=>el.addEventListener('click',e=>{if(!confirm(el.dataset.confirm||'Are you sure?'))e.preventDefault()}));
});
