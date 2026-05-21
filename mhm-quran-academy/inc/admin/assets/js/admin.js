(function($){
  function ajax(action,data){return $.post(mhmAdmin.ajaxUrl,Object.assign({action:action,nonce:mhmAdmin.nonce},data||{}));}
  ajax('mhm_admin_activity').done(function(r){ if(r.success){ $('#mhm-activity-log').html(r.data.map(x=>`<p>${x}</p>`).join('')); }});

  const dz=document.getElementById('mhm-dropzone'); const fi=document.getElementById('mhm-audio-file');
  if(dz&&fi){ dz.addEventListener('click',()=>fi.click()); ['dragenter','dragover'].forEach(e=>dz.addEventListener(e,(ev)=>{ev.preventDefault();dz.classList.add('drag')})); ['dragleave','drop'].forEach(e=>dz.addEventListener(e,(ev)=>{ev.preventDefault();dz.classList.remove('drag')})); dz.addEventListener('drop',(e)=>upload(e.dataTransfer.files)); fi.addEventListener('change',()=>upload(fi.files)); }
  function upload(files){ if(!files||!files.length) return; const fd=new FormData(); fd.append('action','mhm_admin_audio_upload'); fd.append('nonce',mhmAdmin.nonce); [...files].forEach(f=>fd.append('audio_files[]',f)); fetch(mhmAdmin.ajaxUrl,{method:'POST',body:fd}).then(r=>r.json()).then(r=>alert(r.success?'Uploaded':'Upload failed')); }

  if(window.Chart && document.getElementById('mhmAnalyticsChart')){
    new Chart(document.getElementById('mhmAnalyticsChart'),{type:'line',data:{labels:['Mon','Tue','Wed','Thu','Fri','Sat','Sun'],datasets:[{label:'Ayah Plays',data:[52,61,70,65,82,91,110],borderColor:'#53e7b8',tension:.35}]}});
    new Chart(document.getElementById('mhmUsageChart'),{type:'bar',data:{labels:['Surah','Ayah','Audio','Quiz'],datasets:[{label:'Usage',data:[12,19,27,8],backgroundColor:['#53e7b8','#f3d79f','#9bf6ff','#ffd166']}]}});
  }
})(jQuery);
