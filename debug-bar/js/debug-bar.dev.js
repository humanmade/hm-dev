var wpDebugBar;

(function($) {

var debugBar, bounds, api, $win;

bounds = {
	adminBarHeight: 0,
	minHeight: 0,
	marginBottom: 0,

	inUpper: function(){
		return debugBar.offset().top - $win.scrollTop() >= bounds.adminBarHeight;
	},
	inLower: function(){
		return debugBar.outerHeight() >= bounds.minHeight
			&& $win.height() >= bounds.minHeight;
	},
	update: function( to ){
		if ( typeof to == "number" || to == 'auto' )
			debugBar.height( to );
		if ( ! bounds.inUpper() || to == 'upper' )
			debugBar.height( $win.height() - bounds.adminBarHeight );
		if ( ! bounds.inLower() || to == 'lower' )
			debugBar.height( bounds.minHeight );
		api.spacer.css( 'margin-bottom', debugBar.height() + bounds.marginBottom );
	},
	restore: function(){
		api.spacer.css( 'margin-bottom', bounds.marginBottom );
	}
};

wpDebugBar = api = {
	// The element that we will pad to prevent the debug bar
	// from overlapping the bottom of the page.
	spacer: undefined,

	init: function(){
		// Initialize variables.
		debugBar = $('#querylist');
		$win = $(window);

		// In the admin, we need to pad the footer.
		api.spacer = $('.wp-admin #footer');
		// If we're not in the admin, pad the body.
		if ( ! api.spacer.length )
			api.spacer = $(document.body);

		bounds.minHeight = $('#debug-bar-handle').outerHeight() + $('#debug-bar-menu').outerHeight();
		bounds.adminBarHeight = $('#wpadminbar').outerHeight();
		bounds.marginBottom = parseInt( api.spacer.css('margin-bottom'), 10 );

		api.dock();
		api.toggle.init();
		api.tabs();
		api.actions.init();
		api.cookie.restore();
	},

	cookie: {
		get: function() {
			var cookie = wpCookies.getHash('wp-debug-bar-' + userSettings.uid);

			if ( ! cookie )
				return;

			// Convert the properties to the correct types.
			cookie.visible = cookie.visible == 'true';
			cookie.height = parseInt( cookie.height, 10 );
			return cookie;
		},
		update: function() {
			var name = 'wp-debug-bar-' + userSettings.uid,
				expires = new Date(),
				path = userSettings.url,
				value = {
					visible: debugBar.is(':visible'),
					height: debugBar.height()
				};

			expires.setTime( expires.getTime() + 31536000000 );

			wpCookies.setHash( name, value, expires, path );
		},
		restore: function() {
			var cookie = api.cookie.get();

			if ( ! cookie )
				return;

			api.toggle.pending = cookie.height;
			api.toggle.visibility( cookie.visible );
		}
	},

	dock: function(){
		debugBar.dockable({
			handle: '#debug-bar-handle',
			resize: function( e, ui ) {
				return bounds.inUpper() && bounds.inLower();
			},
			resized: function( e, ui ) {
				bounds.update();
			},
			stop: function( e, ui ) {
				api.cookie.update();
			}
		});

		// If the window is resized, make sure the debug bar isn't too large.
		$win.resize( function(){
			if ( debugBar.is(':visible') && ! debugBar.dockable('option', 'disabled') )
				bounds.update();
		});
	},

	toggle: {
		pending: '',
		init: function() {
			$('#wp-admin-bar-debug-bar').click( function(e) {
				e.preventDefault();
				api.toggle.visibility();
			});
		},
		visibility: function( show ){
			show = typeof show == 'undefined' ? debugBar.is(':hidden') : show;

			debugBar.toggle( show );
			$(this).toggleClass( 'active', show );

			if ( show ) {
				bounds.update( api.toggle.pending );
				api.toggle.pending = '';
			} else {
				bounds.restore();
			}
			api.cookie.update();
		}
	},

	tabs: function(){
		var debugMenuLinks = $('.debug-menu-link'),
			debugMenuTargets = $('.debug-menu-target');

		debugMenuLinks.click( function(e){
			var t = $(this);

			e.preventDefault();

			if ( t.hasClass('current') )
				return;

			// Deselect other tabs and hide other panels.
			debugMenuTargets.hide();
			debugMenuLinks.removeClass('current');

			// Select the current tab and show the current panel.
			t.addClass('current');
			// The hashed component of the href is the id that we want to display.
			$('#' + this.href.substr( this.href.indexOf( '#' ) + 1 ) ).show();
		});
	},

	actions: {
		height: 0,
		overflow: 'auto',
		buttons: {},

		init: function() {
			var actions = $('#debug-bar-actions');

			api.actions.height = debugBar.height();
			api.actions.overflow = api.spacer.css( 'overflow' );

			api.actions.buttons.max = $('.plus', actions).click( api.actions.maximize );
			api.actions.buttons.res = $('.minus', actions).click( api.actions.restore );
		},
		maximize: function() {
			api.actions.height = debugBar.height();
			api.spacer.css( 'overflow', 'hidden' );
			bounds.update( 'auto' );
			api.actions.buttons.max.hide();
			api.actions.buttons.res.show();
			debugBar.dockable('disable');
		},
		restore: function() {
			api.spacer.css( 'overflow', api.actions.overflow );
			bounds.update( api.actions.height );
			api.actions.buttons.res.hide();
			api.actions.buttons.max.show();
			debugBar.dockable('enable');
		}
	}
};

$(document).ready( wpDebugBar.init );

})(jQuery);

jQuery( document ).ready( function( $ ) {
	
	$( '#debug-bar-php ol.debug-bar-php-list li a' ).click( function() {
	
		ignored = JSON.parse( readCookie( 'debug-bar-ignored-notices' ) );
		
		if ( ignored == null ) {
			ignored = Array();
		}
		
		key = $( this ).parent().attr( 'data-key' ).replace( 'key_', '' );

		if ( $.inArray( key, ignored ) == -1 ) {
			ignored.push( key );
		}
		
		$( '#debug-bar-php h2:nth-child(2) strong' ).text( Number( $( '#debug-bar-php h2:nth-child(2) strong' ).text() ) - 1 );
		$( '#debug-bar-php h2:nth-child(3) strong' ).text( Number( $( '#debug-bar-php h2:nth-child(3) strong' ).text() ) + 1 );
				
		eraseCookie( 'debug-bar-ignored-notices' );

		createCookie( 'debug-bar-ignored-notices', JSON.stringify( ignored ), 324 );
		
		$( this ).parent().remove();
		
	} );
	
	$( '.reset-ignored' ).click( function() {
		eraseCookie( 'debug-bar-ignored-notices' );
		window.location.reload;
	} );
	
} );

function createCookie(name,value,days) {
	if (days) {
		var date = new Date();
		date.setTime(date.getTime()+(days*24*60*60*1000));
		var expires = "; expires="+date.toGMTString();
	}
	else var expires = "";
	document.cookie = name+"="+value+expires+"; path=/";
}

function readCookie(name) {
	var nameEQ = name + "=";
	var ca = document.cookie.split(';');
	for(var i=0;i < ca.length;i++) {
		var c = ca[i];
		while (c.charAt(0)==' ') c = c.substring(1,c.length);
		if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
	}
	return null;
}

function eraseCookie(name) {
	createCookie(name,"",-1);
}