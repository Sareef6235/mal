(function($){
  $(document).on('click','.js-mhm-daily',function(){
    const wrap=$(this).closest('.mhm-qlp-daily');
    $.post(mhmQlp.ajaxUrl,{action:'mhm_qlp_daily_ayah',nonce:mhmQlp.nonce},function(r){
      if(r.success&&r.data){wrap.find('.js-mhm-daily-output').html('<p>'+r.data.arabic_text+'</p><small>'+r.data.name_en+' '+r.data.ayah_number+'</small>');}
    });
  });
})(jQuery);
