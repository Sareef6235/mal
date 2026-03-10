document.addEventListener('DOMContentLoaded',()=>{
  document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el=>new bootstrap.Tooltip(el));

  document.querySelectorAll('[data-table-search]').forEach(inp=>{
    const table=document.querySelector(inp.dataset.tableSearch);
    if(!table) return;
    inp.addEventListener('input',()=>{
      const q=inp.value.toLowerCase().trim();
      table.querySelectorAll('tbody tr').forEach(tr=>tr.style.display=tr.innerText.toLowerCase().includes(q)?'':'none');
    });
  });

  const menuSearch=document.getElementById('menuSearch');
  if(menuSearch){
    menuSearch.addEventListener('input',()=>{
      const q=menuSearch.value.toLowerCase().trim();
      document.querySelectorAll('#erpNav .menu-group-dropdown').forEach(group=>{
        let hasVisible=false;
        group.querySelectorAll('.menu-link').forEach(link=>{
          const matched=!q || (link.dataset.menuLabel||'').includes(q);
          link.closest('li').style.display=matched?'':'none';
          if(matched) hasVisible=true;
        });
        group.style.display=hasVisible?'':'none';
      });
    });
  }

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
