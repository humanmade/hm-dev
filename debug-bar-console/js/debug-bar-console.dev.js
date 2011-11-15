jQuery( function($) {
	var submit = $('#debug-bar-console-submit'),
		input = $('#debug-bar-console-input'),
		output = $('#debug-bar-console-output'),
		nonce = $('#_wpnonce_debug_bar_console').val(),
		iframe = {},
		iframeCSS = $('#debug-bar-console-iframe-css').val();

	iframe.container = $('iframe', output);
	iframe.contents = iframe.container.contents();
	iframe.document = iframe.contents[0];
	iframe.body = $( iframe.document.body );

	$('head', iframe.contents).append('<link type="text/css" href="' + iframeCSS + '" rel="stylesheet" />');

	submit.click( function(){
		$.post( ajaxurl, {
			action: 'debug_bar_console',
			data:   input.val(),
			nonce:  nonce
		}, function( data ) {
			iframe.body.html( data );
		});
		return false;
	});

	input.keydown( function( event ) {
		if ( event.which == 13 && event.shiftKey ) {
			submit.click();
			event.preventDefault();
		}
	});
});