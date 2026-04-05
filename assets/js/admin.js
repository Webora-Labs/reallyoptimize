( function ( $ ) {
	'use strict';

	// -----------------------------------------------------------------------
	// Quality range ↔ number sync
	// -----------------------------------------------------------------------
	var range  = document.getElementById( 'img_quality_range' );
	var number = document.getElementById( 'img_quality' );

	if ( range && number ) {
		range.addEventListener( 'input', function () { number.value = range.value; } );
		number.addEventListener( 'input', function () { range.value = number.value; } );
	}

	// -----------------------------------------------------------------------
	// Bulk optimization
	// -----------------------------------------------------------------------
	var bulk = {
		running : false,
		paused  : false,
		offset  : 0,

		els: {
			start     : $( '#webora-bulk-start' ),
			pause     : $( '#webora-bulk-pause' ),
			reset     : $( '#webora-bulk-reset' ),
			status    : $( '#webora-bulk-status' ),
			bar       : $( '#webora-progress-bar' ),
			pct       : $( '#webora-progress-pct' ),
			total     : $( '#webora-bulk-total' ),
			done      : $( '#webora-bulk-done' ),
			remaining : $( '#webora-bulk-remaining' ),
			log       : $( '#webora-bulk-log' ),
			skipDone  : $( '#webora-skip-done' ),
		},

		init: function () {
			if ( ! this.els.start.length ) return;

			this.els.start.on( 'click', $.proxy( this.start, this ) );
			this.els.pause.on( 'click', $.proxy( this.pause, this ) );
			this.els.reset.on( 'click', $.proxy( this.reset, this ) );
		},

		start: function () {
			if ( this.running ) return;
			this.running = true;
			this.paused  = false;
			this.offset  = 0;

			this.els.start.prop( 'disabled', true );
			this.els.pause.show();
			this.els.log.empty();
			this.setStatus( weboraImageOptimizer.i18n.starting );

			this.runBatch();
		},

		pause: function () {
			this.paused = true;
			this.running = false;
			this.els.pause.hide();
			this.els.start.prop( 'disabled', false ).text( 'Resume' );
			this.setStatus( weboraImageOptimizer.i18n.paused );
		},

		reset: function () {
			if ( ! confirm( 'Clear all optimization marks? Images will be treated as unoptimized.' ) ) return;

			$.post( weboraImageOptimizer.ajaxUrl, {
				action : 'webora_bulk_reset',
				nonce  : weboraImageOptimizer.nonce,
			}, $.proxy( function ( res ) {
				if ( res.success ) {
					this.updateCounters( res.data.total, res.data.done );
					this.updateBar( 0 );
					this.els.log.empty().append(
						$( '<p>' ).text( weboraImageOptimizer.i18n.resetDone )
					);
					this.els.start.text( 'Start Optimization' );
				}
			}, this ) );
		},

		runBatch: function () {
			if ( this.paused ) return;

			var self     = this;
			var skipDone = this.els.skipDone.is( ':checked' ) ? 1 : 0;

			$.post( weboraImageOptimizer.ajaxUrl, {
				action    : 'webora_bulk_run',
				nonce     : weboraImageOptimizer.nonce,
				offset    : this.offset,
				skip_done : skipDone,
			} )
			.done( function ( res ) {
				if ( ! res.success ) {
					self.finish( true );
					return;
				}

				var d = res.data;
				self.updateCounters( d.total, d.done );
				var pct = d.total > 0 ? Math.round( d.done / d.total * 100 ) : 100;
				self.updateBar( pct );
				self.setStatus( weboraImageOptimizer.i18n.processing + ' ' + d.done + ' / ' + d.total );
				self.appendLog( d.log );

				if ( d.finished || d.processed === 0 ) {
					self.finish( false );
				} else {
					self.offset += d.processed;
					self.runBatch();
				}
			} )
			.fail( function () {
				self.finish( true );
			} );
		},

		finish: function ( isError ) {
			this.running = false;
			this.els.pause.hide();
			this.els.start.prop( 'disabled', false ).text( 'Start Optimization' );
			this.setStatus( isError ? weboraImageOptimizer.i18n.error : weboraImageOptimizer.i18n.done );
			this.updateBar( isError ? null : 100 );
		},

		updateCounters: function ( total, done ) {
			this.els.total.text( total );
			this.els.done.text( done );
			this.els.remaining.text( Math.max( 0, total - done ) );
		},

		updateBar: function ( pct ) {
			if ( pct === null ) return;
			pct = Math.min( 100, Math.max( 0, pct ) );
			this.els.bar.css( 'width', pct + '%' );
			this.els.pct.text( pct + '%' );
		},

		setStatus: function ( msg ) {
			this.els.status.text( msg );
		},

		appendLog: function ( entries ) {
			if ( ! entries || ! entries.length ) return;

			var $log = this.els.log;
			$log.find( '.webora-log__empty' ).remove();

			$.each( entries, function ( i, item ) {
				var cls = 'webora-log__line webora-log__line--' + item.status;
				var txt = '#' + item.id + ' ' + item.file + ' — ' + item.message;
				$log.prepend( $( '<div>' ).addClass( cls ).text( txt ) );
			} );
		},
	};

	bulk.init();

}( jQuery ) );
