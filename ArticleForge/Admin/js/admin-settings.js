jQuery(document).ajaxStart(function() {
    jQuery('body').css('cursor', 'wait');
}).ajaxComplete(function() {
    jQuery('body').css('cursor', 'default');
});

function setDefaults() {
//    jQuery('body').css('cursor' , 'wait');
	jQuery.post(
		ajaxurl, 
		{
			action: 'articleforge_settings_defaults',
			nonce: articleforge.nonce
		},
		function( response ) {
			applyDefaults( response );
		}
	);
}

function applyDefaults( defaults ) {
	for(var propt in defaults.data){
		jQuery('#_articleforge_' + propt).val(defaults.data[propt]);
	    //alert(propt + ': ' + defaults.data[propt]);
	}
//    jQuery('body').css('cursor', 'auto');
}
