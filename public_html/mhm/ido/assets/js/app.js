const $=(s,c=document)=>c.querySelector(s),$$=(s,c=document)=>[...c.querySelectorAll(s)];
function toast(icon,title){Swal.fire({toast:true,position:'top-end',timer:2600,showConfirmButton:false,icon,title});}
function togglePassword(id){const el=document.getElementById(id); el.type=el.type==='password'?'text':'password';}
function strength(p){let n=0;if(p.length>=6)n++;if(/[A-Z]/.test(p))n++;if(/[a-z]/.test(p))n++;if(/\d/.test(p))n++;if(/[^A-Za-z0-9]/.test(p))n++;return n;}
document.addEventListener('input',e=>{if(e.target.matches('[data-strength]')){const v=strength(e.target.value),bar=document.querySelector(e.target.dataset.strength); if(bar){bar.style.width=(v*20)+'%';bar.className='progress-bar bg-'+(v<3?'danger':v<5?'warning':'success');}}});
async function availability(type,value){const url=`../ajax/check-${type}.php?${type}=${encodeURIComponent(value)}`; const r=await fetch(url); return r.json();}
$$('[data-check]').forEach(el=>el.addEventListener('blur',async()=>{if(!el.value)return; const j=await availability(el.dataset.check,el.value); el.classList.toggle('is-invalid',!j.available); el.classList.toggle('is-valid',j.available);}));
function confirmDelete(form){Swal.fire({title:'Delete this record?',text:'This action cannot be undone.',icon:'warning',showCancelButton:true,confirmButtonColor:'#d33'}).then(r=>{if(r.isConfirmed)form.submit();});return false;}
function setTheme(t){document.documentElement.dataset.theme=t;localStorage.setItem('theme',t);} setTheme(localStorage.getItem('theme')||'light');
