document.addEventListener('DOMContentLoaded',()=>{
  const sidebar=document.getElementById('sidebarNav');
  const tgl=document.getElementById('sidebarToggle');
  if(tgl&&sidebar) tgl.addEventListener('click',()=>sidebar.classList.toggle('open'));
  document.querySelectorAll('[data-table-search]').forEach(inp=>{
    const table=document.querySelector(inp.dataset.tableSearch);
    if(!table) return;
    inp.addEventListener('input',()=>{
      const q=inp.value.toLowerCase().trim();
      table.querySelectorAll('tbody tr').forEach(tr=>tr.style.display=tr.innerText.toLowerCase().includes(q)?'':'none');
    });
  });
});
