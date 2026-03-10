document.addEventListener('DOMContentLoaded',()=>{
  document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el=>new bootstrap.Tooltip(el));
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

  const helpSearch=document.getElementById('helpSearch');
  if(helpSearch){
    helpSearch.addEventListener('input',()=>{
      const q=helpSearch.value.toLowerCase().trim();
      document.querySelectorAll('.help-topic').forEach(topic=>{
        const text=(topic.dataset.help||'').toLowerCase();
        topic.style.display = text.includes(q) ? '' : 'none';
      });
    });
  }
});
