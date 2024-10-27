jQuery(document).ajaxStart(function() {
    jQuery('body').css('cursor', 'wait');
}).ajaxComplete(function() {
    jQuery('body').css('cursor', 'default');
});

jQuery(document).ready(function(){

   jQuery('#content-list-sortable').sortable(
        { cursor:"move",
          axis: "y",
          containment:"#articleforge_content-list",
          forcePlaceholderSize: true,
          placeholder: "content-list-placeholder",
          opacity: 0.65,
          update: content_order_update,
//          start: function(e, ui){
//               ui.placeholder.height(ui.item.height() - 1);
//          }
        }
   );

function content_order_update(event, ui) {
	var items = ui.item.parent().children();
	var list = [];
	var re = /^content-(\d+)$/;
	jQuery.each(items, function(key, value) {
		list[key] = re.exec(value.id)[1];
//		console.log("key", key, "value", value.id);
	});
	jQuery('#content_order').val(list.join());
//	var value = list.join();
//	console.log(value);
//	jQuery.each(list, function(key,value) {
//		console.log("key", key, "value", value);
//	});
//jQuery.each( ui.item.parent(), function( key, value ) {
//console.log( "key", key, "value", value );
//});
//for(key in ui) {
//		alert(key: ui.key);
//	}
}

});
