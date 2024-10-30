'use strict';

function madAsyncUpdate(el) {
    var el = jQuery(el);
    var id = el.attr("data-id");
    var id_cont = el.attr("data-id_cont");
    var type = el.attr("data-type");
    var url = '/wp-admin/admin-ajax.php';
    
    el.css({'pointer-events': 'none'});
    
    jQuery.ajax({
        type: 'POST',
        datatype: 'json',
        url: ajaxurl,
        data: {action: "madeep_async_page_update",madeep_t:type,madeep_id:id,madeep_id_cont:id_cont},
        success: function (data) {
            el.css({'pointer-events': 'auto'});
        },
        error: function (data) {
        }
    });
}

jQuery(document).ready(function(){
    jQuery('.mad-asyncUpdate').click(function(e){
        e.preventDefault();
    });
});