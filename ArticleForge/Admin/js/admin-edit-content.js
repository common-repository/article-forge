jQuery(document).ajaxStart(function() {
    jQuery('body').css('cursor', 'wait');
}).ajaxComplete(function() {
    jQuery('body').css('cursor', 'default');
});

jQuery(document).ready(function(){

   jQuery('#title').change(function(){
	var post_id = jQuery('#post_ID').val();
	var items = jQuery('#content-order-sortable').children();
	var re = /^content-(\d+)$/;
	jQuery.each(items, function(key, value) {
		id = re.exec(value.id)[1];
		if (id == post_id) {
			items[key].innerHTML = "<h2>" + jQuery('#title').val() + "</h2>";
			return false;
		}
	});
   });

   jQuery('#content-order-sortable').sortable(
        { cursor:"move",
          axis: "y",
          containment:"#articleforge_content-order",
          forcePlaceholderSize: true,
          placeholder: "content-order-placeholder",
          opacity: 0.65,
          cancel: '.not-sortable',
          update: content_order_update,
          start: function(e, ui){
               ui.placeholder.height(ui.item.height() );
          }
        }
   );

   function content_order_update(event, ui) {
	var post_id = jQuery('#post_ID').val();
	var items = ui.item.parent().children();
	var list = [];
	var re = /^content-(\d+)$/;
	jQuery.each(items, function(key, value) {
		list[key] = re.exec(value.id)[1];
		if (list[key] == post_id) {
			jQuery('#new_menu_order').val(key + 1);
		}
	});
	//jQuery('#content_order').val(list.join());
   }

});
