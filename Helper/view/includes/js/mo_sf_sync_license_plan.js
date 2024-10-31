function dropdown(get){
	let child = jQuery( get ).find( 'div' )
	if (child.css( "display" ) === "none") {
		child.css( {"display":"block"} );
	} else {
		child.css( {"display":"none"} );
	}
}
jQuery( document ).ready(
	function(e) {
		jQuery( '#compareplan' ).click(
			function(){
				if (jQuery( '#table' ).css( "display" ) === "none") {
					jQuery( '#expand' ).css( {"display":"none"} );
					jQuery( '#collapse' ).css( {"display":"block"} );
					jQuery( '#table' ).css( {"display":"block"} );
				} else {
					jQuery( '#collapse' ).css( {"display":"none"} );
					jQuery( '#expand' ).css( {"display":"block"} );
					jQuery( '#table' ).css( {"display":"none"} );
				}
			}
		);

	}
)
