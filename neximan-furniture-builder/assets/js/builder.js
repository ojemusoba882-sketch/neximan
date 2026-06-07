/**
 * Neximan Furniture Builder - front-end behaviour.
 *
 * Handles module/layout/color selection, live price calculation, image masking
 * preview and the WooCommerce AJAX add-to-cart flow.
 */
( function () {
	'use strict';

	var settings = window.NeximanBuilderConfig || {};

	/**
	 * Formats a numeric price using the widget currency settings.
	 *
	 * @param {number} amount   Price amount.
	 * @param {Object} currency Currency config { symbol, position, separator }.
	 * @return {string} Formatted price.
	 */
	function formatPrice( amount, currency ) {
		var sep = currency && typeof currency.separator === 'string' ? currency.separator : ',';
		var rounded = Math.round( ( amount + Number.EPSILON ) * 100 ) / 100;
		var parts = rounded.toString().split( '.' );

		parts[ 0 ] = parts[ 0 ].replace( /\B(?=(\d{3})+(?!\d))/g, sep );
		var num = parts.join( '.' );

		var symbol = currency && currency.symbol ? currency.symbol : '';
		if ( ! symbol ) {
			return num;
		}

		return currency.position === 'before' ? symbol + ' ' + num : num + ' ' + symbol;
	}

	/**
	 * Initialises a single builder instance.
	 *
	 * @param {HTMLElement} root Builder container.
	 * @return {void}
	 */
	function initBuilder( root ) {
		if ( root.dataset.neximanReady === '1' ) {
			return;
		}
		root.dataset.neximanReady = '1';

		var configNode = root.querySelector( '.neximan-config' );
		if ( ! configNode ) {
			return;
		}

		var config;
		try {
			config = JSON.parse( configNode.textContent );
		} catch ( e ) {
			return;
		}

		var els = {
			image: root.querySelector( '.neximan-main-image' ),
			display: root.querySelector( '.neximan-sofa-display' ),
			moduleTabs: root.querySelectorAll( '.neximan-module-tab' ),
			layoutBtns: root.querySelectorAll( '.neximan-layout-btn' ),
			colorSwatches: root.querySelectorAll( '.neximan-color-swatch' ),
			infoLayout: root.querySelector( '.neximan-info-layout' ),
			infoModule: root.querySelector( '.neximan-info-module' ),
			priceValue: root.querySelector( '.neximan-price-value' ),
			cta: root.querySelector( '.neximan-cta' ),
			feedback: root.querySelector( '.neximan-feedback' )
		};

		var state = {
			module: config.defaults ? config.defaults.module : '',
			layout: config.defaults ? config.defaults.layout : '',
			color: config.defaults ? config.defaults.color : ''
		};

		/**
		 * Returns the layout key for a given slot for the active module, or '' if
		 * the layout exists. Helper to pick a fallback layout when switching modules.
		 *
		 * @return {void}
		 */
		function ensureLayoutForModule() {
			var module = config.modules[ state.module ];
			if ( ! module ) {
				return;
			}

			var current = config.layouts[ state.layout ];
			if ( current && module.images[ current.slot ] ) {
				return; // Current layout still valid for this module.
			}

			// Pick the first layout that has an image for this module.
			var keys = Object.keys( config.layouts );
			for ( var i = 0; i < keys.length; i++ ) {
				var slot = config.layouts[ keys[ i ] ].slot;
				if ( module.images[ slot ] ) {
					state.layout = keys[ i ];
					return;
				}
			}
		}

		/**
		 * Calculates the current total price.
		 *
		 * @return {number} Total price.
		 */
		function calcPrice() {
			var total = 0;
			var module = config.modules[ state.module ];
			if ( module ) {
				total += parseFloat( module.basePrice ) || 0;
			}
			var layout = config.layouts[ state.layout ];
			if ( layout ) {
				total += parseFloat( layout.price ) || 0;
			}
			var color = config.colors[ state.color ];
			if ( color ) {
				total += parseFloat( color.price ) || 0;
			}
			return total;
		}

		/**
		 * Shows/hides layout buttons depending on the active module's images.
		 *
		 * @return {void}
		 */
		function syncLayoutButtons() {
			var module = config.modules[ state.module ];
			els.layoutBtns.forEach( function ( btn ) {
				var key = btn.getAttribute( 'data-layout' );
				var layout = config.layouts[ key ];
				var hasImage = module && layout && module.images[ layout.slot ];

				btn.classList.toggle( 'is-hidden', ! hasImage );
				btn.classList.toggle( 'is-active', key === state.layout );
			} );
		}

		/**
		 * Renders the preview image, info text and price.
		 *
		 * @return {void}
		 */
		function render() {
			var module = config.modules[ state.module ];
			var layout = config.layouts[ state.layout ];
			var color = config.colors[ state.color ];

			if ( module && layout && module.images[ layout.slot ] && els.image && els.display ) {
				var url = module.images[ layout.slot ];
				els.image.src = url;
				els.display.style.setProperty( '--nx-bg-image', 'url("' + url + '")' );
			}

			if ( color && els.display ) {
				els.display.style.setProperty( '--nx-sofa-color', color.value );
			}

			if ( els.infoLayout ) {
				els.infoLayout.textContent = layout ? layout.label : '';
			}
			if ( els.infoModule ) {
				els.infoModule.textContent = module ? module.name : '';
			}

			if ( config.showPrice && els.priceValue ) {
				els.priceValue.textContent = formatPrice( calcPrice(), config.currency );
			}
		}

		// --- Events ---------------------------------------------------------

		els.moduleTabs.forEach( function ( tab ) {
			tab.addEventListener( 'click', function () {
				els.moduleTabs.forEach( function ( t ) {
					t.classList.remove( 'is-active' );
				} );
				tab.classList.add( 'is-active' );
				state.module = tab.getAttribute( 'data-module' );
				ensureLayoutForModule();
				syncLayoutButtons();
				render();
			} );
		} );

		els.layoutBtns.forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				state.layout = btn.getAttribute( 'data-layout' );
				syncLayoutButtons();
				render();
			} );
		} );

		els.colorSwatches.forEach( function ( swatch ) {
			swatch.addEventListener( 'click', function () {
				els.colorSwatches.forEach( function ( s ) {
					s.classList.remove( 'is-active' );
				} );
				swatch.classList.add( 'is-active' );
				state.color = swatch.getAttribute( 'data-color' );
				render();
			} );
		} );

		if ( els.cta ) {
			els.cta.addEventListener( 'click', function () {
				var action = els.cta.getAttribute( 'data-action' );
				if ( action === 'add_to_cart' || action === 'buy_now' ) {
					addToCart( action === 'buy_now' );
				}
			} );
		}

		/**
		 * Sends the current selection to the server and adds it to the cart.
		 *
		 * @param {boolean} redirectToCheckout Whether to redirect to checkout after.
		 * @return {void}
		 */
		function addToCart( redirectToCheckout ) {
			if ( ! settings.ajaxUrl || ! settings.hasWoo ) {
				setFeedback( ( settings.i18n && settings.i18n.error ) || 'Error', 'error' );
				return;
			}

			els.cta.classList.add( 'is-loading' );
			setFeedback( ( settings.i18n && settings.i18n.adding ) || 'Adding...', '' );

			var body = new URLSearchParams();
			body.append( 'action', 'neximan_add_to_cart' );
			body.append( 'nonce', settings.nonce );
			body.append( 'post_id', root.getAttribute( 'data-post-id' ) || '' );
			body.append( 'widget_id', root.getAttribute( 'data-widget-id' ) || '' );
			body.append( 'module', state.module );
			body.append( 'layout', state.layout );
			body.append( 'color', state.color );

			fetch( settings.ajaxUrl, {
				method: 'POST',
				credentials: 'same-origin',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body: body.toString()
			} )
				.then( function ( res ) {
					return res.json();
				} )
				.then( function ( json ) {
					els.cta.classList.remove( 'is-loading' );

					if ( ! json || ! json.success ) {
						var msg = json && json.data && json.data.message ? json.data.message : ( settings.i18n && settings.i18n.error );
						setFeedback( msg, 'error' );
						return;
					}

					if ( redirectToCheckout && json.data.checkout_url ) {
						window.location.href = json.data.checkout_url;
						return;
					}

					var added = ( settings.i18n && settings.i18n.added ) || 'Added';
					var cartUrl = json.data.cart_url || settings.cartUrl;
					var link = cartUrl ? ' <a href="' + cartUrl + '">' + cartUrl + '</a>' : '';
					setFeedback( added, 'success', link );

					document.body.dispatchEvent( new CustomEvent( 'neximan:added', { detail: json.data } ) );
				} )
				.catch( function () {
					els.cta.classList.remove( 'is-loading' );
					setFeedback( ( settings.i18n && settings.i18n.error ) || 'Error', 'error' );
				} );
		}

		/**
		 * Updates the feedback area.
		 *
		 * @param {string} text     Message text.
		 * @param {string} type     '', 'success' or 'error'.
		 * @param {string} htmlLink Optional trailing HTML (already escaped).
		 * @return {void}
		 */
		function setFeedback( text, type, htmlLink ) {
			if ( ! els.feedback ) {
				return;
			}
			els.feedback.className = 'neximan-feedback' + ( type ? ' is-' + type : '' );
			els.feedback.innerHTML = ( text || '' ) + ( htmlLink || '' );
		}

		// --- Initial render -------------------------------------------------

		ensureLayoutForModule();
		syncLayoutButtons();

		if ( els.colorSwatches.length ) {
			var activeColor = root.querySelector( '.neximan-color-swatch[data-color="' + state.color + '"]' );
			( activeColor || els.colorSwatches[ 0 ] ).classList.add( 'is-active' );
			if ( ! activeColor ) {
				state.color = els.colorSwatches[ 0 ].getAttribute( 'data-color' );
			}
		}

		render();
	}

	/**
	 * Initialises every builder on the page.
	 *
	 * @return {void}
	 */
	function initAll() {
		var builders = document.querySelectorAll( '[data-neximan-builder]' );
		builders.forEach( initBuilder );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', initAll );
	} else {
		initAll();
	}

	// Elementor editor / preview support.
	if ( window.jQuery ) {
		window.jQuery( window ).on( 'elementor/frontend/init', function () {
			if ( window.elementorFrontend && window.elementorFrontend.hooks ) {
				window.elementorFrontend.hooks.addAction(
					'frontend/element_ready/neximan_furniture_builder.default',
					function ( $scope ) {
						var node = $scope[ 0 ].querySelector( '[data-neximan-builder]' );
						if ( node ) {
							node.dataset.neximanReady = '';
							initBuilder( node );
						}
					}
				);
			}
		} );
	}
} )();
