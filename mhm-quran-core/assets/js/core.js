(function($){
  $(document).on('click','.js-qc-daily',function(){const w=$(this).closest('.qc-daily');$.post(mhmQC.ajax,{action:'mhm_qc_daily',nonce:mhmQC.nonce},r=>{if(r.success){w.find('.qc-daily-out').html('<p>'+r.data.arabic_text+'</p><small>'+r.data.name_en+' '+r.data.ayah_number+'</small>')}})});
  $(document).on('click','.js-qc-load-audio',function(){const b=$(this),w=b.closest('.qc-audio');fetch(mhmQC.rest+'audio/1?reciter_id=1').then(r=>r.json()).then(rows=>{w.find('.qc-audio-list').html(rows.slice(0,10).map(x=>`<div>Ayah ${x.ayah_number} <a href="${x.audio_url||'#'}">Play</a></div>`).join(''))})});
})(jQuery);
