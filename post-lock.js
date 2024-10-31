var Post_Lock = window.Post_Lock || {};

(function( pl, $, d, undefined ) {

	pl = {
		
		button : $( d.getElementById( 'publish' ) ),
		
		wrap   : $( d.getElementById( 'publishing-action' ) ),
		
		ticks  : 0,
		
		lock   : function() {
			this.button.attr( 'disabled', 'disabled' );
			this.button.attr( 'title', post_lock_l10n.strings.title );
			this.wrap.removeClass( 'post-lock--post-unlocked' );
			this.wrap.addClass( 'post-lock--post-locked' );
		},

		unlock : function( require_key ) {

			var can_unlock = false, confirmed = false;

			if ( require_key ) {
				confirmed  = prompt( post_lock_l10n.strings.prompt );
				can_unlock = confirmed == post_lock_l10n.strings.key;
			} else {
				can_unlock = true;
			}

			if ( can_unlock ) {
				this.button.removeAttr( 'disabled' );
				this.wrap.removeClass( 'post-lock--post-locked' );
				this.wrap.addClass( 'post-lock--post-unlocked' );
			}
		}
	};

	pl.lock();

	pl.wrap.on( 'click', function(e) {
		if ( 'post-lock--post-locked' == e.target.className ) {
			pl.unlock( true )
		} else if ( 'post-lock--post-unlocked' == e.target.className ) {
			pl.lock();
		}
	});

	// If unlocked for two wp_heartbeat API ticks, re-lock.
	$(d).on( 'heartbeat-tick', function(e, data ) {

		var unlocked = pl.wrap.is( '.post-lock--post-unlocked' );

		if ( unlocked )
			pl.ticks += 1;
		else
			pl.ticks = 0;

		if ( pl.ticks >= 2 ) {
			pl.lock() 
		}
	});

})( Post_Lock, jQuery, document );