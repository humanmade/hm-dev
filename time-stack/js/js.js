var viewModel = { 
	timeStacks: ko.observable({})
}

var getTimeStack = function( callback ) {

	jQuery.getJSON( 'http://127.0.0.1:1337/?jsoncallback=?', callback );
}


getTimeStack( function( data ) {
	
	viewModel = { timeStacks: data }

	viewModel = ko.mapping.fromJS(viewModel);
	
	ko.applyBindings(viewModel);
	
	setTimeout( runGetter, 1000 );
} )


var runGetter = function() {
	
	getTimeStack( function( data ) {
		
		foo = { timeStacks: data }
		
		ko.mapping.fromJS(foo, viewModel);
		
		setTimeout( runGetter, 300 );
	} );
	
}