/**
 * Neximan Furniture Builder - front-end behaviour.
 *
 * Builds a two-level configurator (series -> models -> layouts + colors) from
 * the embedded JSON config, calculates the live price and handles the
 * WooCommerce AJAX add-to-cart flow.
 */
( function () {
	'use strict';

	var settings = window.NeximanBuilderConfig || {};

	/**
	 * Formats a numeric price using the widget currency settings.
	 *
	 * @param {number} amount   Price amount.
	 * @param {Object} currency Currency config.
	 * @return {string} Formatted price string.
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
	 * Creates a button element.
	 *
	 * @param {string} cls   Class name.
	 * @param {string} text  Text content.
	 * @param {Object} attrs Data attributes.
	 * @return {HTMLButtonElement} Button.
	 */
	function makeButton( cls, text, attrs ) {
		var btn = document.createElement( 'button' );
		btn.type = 'button';
		btn.className = cls;
		btn.textContent = text;
		Object.keys( attrs || {} ).forEach( function ( key ) {
			btn.setAttribute( key, attrs[ key ] );
		} );
		return btn;
	}

	/**
	 * Finds an item by id in a list.
	 *
	 * @param {Array}  list List of objects with id.
	 * @param {string} id   Id to find.
	 * @return {Object|null} Found item or null.
	 */
	function byId( list, id ) {
		for ( var i = 0; i < ( list || [] ).length; i++ ) {
			if ( String( list[ i ].id ) === String( id ) ) {
				return list[ i ];
			}
		}
		return null;
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

		if ( ! config.series || ! config.series.length ) {
			return;
		}

		// The signed pricing manifest is echoed back verbatim to the server.
		var manifestNode = root.querySelector( '.neximan-manifest' );
		var manifestJson = manifestNode ? manifestNode.textContent : '';
		var signature = root.getAttribute( 'data-sig' ) || '';

		var els = {
			assembly: root.querySelector( '.neximan-assembly' ),
			seriesTabs: root.querySelector( '.neximan-series-tabs' ),
			modelTabs: root.querySelector( '.neximan-model-tabs' ),
			layouts: root.querySelector( '.neximan-layout-options' ),
			parts: root.querySelector( '.neximan-parts' ),
			options: root.querySelector( '.neximan-options' ),
			colors: root.querySelector( '.neximan-color-options' ),
			infoLayout: root.querySelector( '.neximan-info-layout' ),
			infoModule: root.querySelector( '.neximan-info-module' ),
			priceValue: root.querySelector( '.neximan-price-value' ),
			cta: root.querySelector( '.neximan-cta' ),
			feedback: root.querySelector( '.neximan-feedback' )
		};

		var state = { seriesIndex: 0, modelId: '', layoutId: '', colorId: '', options: {}, partSel: {} };

		/**
		 * Returns a module (building block) from the active series by id.
		 *
		 * @param {string} id Module id.
		 * @return {Object|null} Module.
		 */
		function moduleById( id ) {
			return byId( activeSeries().modules || [], id );
		}

		/**
		 * Returns the active layout object.
		 *
		 * @return {Object|null} Layout.
		 */
		function activeLayout() {
			var model = activeModel();
			return model ? byId( model.layouts, state.layoutId ) : null;
		}

		/**
		 * Returns the active series object.
		 *
		 * @return {Object} Active series.
		 */
		function activeSeries() {
			return config.series[ state.seriesIndex ];
		}

		/**
		 * Returns the active model object.
		 *
		 * @return {Object|null} Active model.
		 */
		function activeModel() {
			return byId( activeSeries().models, state.modelId );
		}

		/**
		 * Calculates the current price.
		 *
		 * @return {number} Total price.
		 */
		function calcPrice() {
			var total = 0;
			var model = activeModel();
			if ( model ) {
				total += parseFloat( model.basePrice ) || 0;
				var layout = byId( model.layouts, state.layoutId );
				if ( layout ) {
					total += parseFloat( layout.price ) || 0;
					// Composition parts: qty x selected module price (modular mode only).
					if ( activeSeries().pricingMode !== 'simple' ) {
						( layout.parts || [] ).forEach( function ( part ) {
							var modId = selectedModuleForPart( part );
							var mod = modId ? moduleById( modId ) : null;
							if ( mod ) {
								total += ( parseFloat( mod.price ) || 0 ) * ( parseInt( part.qty, 10 ) || 1 );
							}
						} );
					}
				}
			}
			var color = byId( activeSeries().colors, state.colorId );
			if ( color ) {
				total += parseFloat( color.price ) || 0;
			}
			( activeSeries().options || [] ).forEach( function ( group ) {
				var choice = byId( group.choices, state.options[ group.id ] );
				if ( choice ) {
					total += parseFloat( choice.price ) || 0;
				}
			} );
			return total;
		}

		/**
		 * Returns the selected module id for a composition part (or its default).
		 *
		 * @param {Object} part Part object.
		 * @return {string} Module id.
		 */
		function selectedModuleForPart( part ) {
			var ids = part.moduleIds || [];
			if ( ! ids.length ) {
				return '';
			}
			var sel = state.partSel[ part.id ];
			return ( sel && ids.indexOf( sel ) !== -1 ) ? sel : ids[ 0 ];
		}

		/**
		 * Renders the preview image/color, info labels and price.
		 *
		 * @return {void}
		 */
		function renderPreview() {
			var model = activeModel();
			var layout = model ? byId( model.layouts, state.layoutId ) : null;
			var color = byId( activeSeries().colors, state.colorId );
			var colorVal = color ? color.value : '';

			renderAssembly( layout, colorVal );

			if ( els.infoLayout ) {
				els.infoLayout.textContent = layout ? layout.label : '';
			}
			if ( els.infoModule ) {
				els.infoModule.textContent = model ? model.name : '';
			}
			if ( config.showPrice && els.priceValue ) {
				els.priceValue.textContent = formatPrice( calcPrice(), config.currency );
			}
		}

		/**
		 * Renders the visual assembly. In modular mode each composition part's
		 * selected module image is placed side by side (repeated by qty); in
		 * simple mode (or when modules have no image) the layout image is shown.
		 * Each piece is tinted with the selected fabric color via a CSS mask.
		 *
		 * @param {Object} layout   Active layout.
		 * @param {string} colorVal Fabric color value.
		 * @return {void}
		 */
		function renderAssembly( layout, colorVal ) {
			if ( ! els.assembly ) {
				return;
			}
			els.assembly.innerHTML = '';

			var urls = [];
			var modular = activeSeries().pricingMode !== 'simple';

			if ( modular && layout && layout.parts && layout.parts.length ) {
				layout.parts.forEach( function ( part ) {
					var mod = moduleById( selectedModuleForPart( part ) );
					var qty = parseInt( part.qty, 10 ) || 1;
					for ( var i = 0; i < qty; i++ ) {
						if ( mod && mod.image ) {
							urls.push( mod.image );
						}
					}
				} );
			}

			if ( ! urls.length && layout && layout.image ) {
				urls = [ layout.image ];
			}

			urls.forEach( function ( url ) {
				var piece = document.createElement( 'div' );
				piece.className = 'neximan-piece' + ( colorVal ? ' is-tinted' : '' );
				piece.style.setProperty( '--nx-bg-image', 'url("' + url + '")' );
				if ( colorVal ) {
					piece.style.setProperty( '--nx-sofa-color', colorVal );
				}
				var img = document.createElement( 'img' );
				img.src = url;
				img.alt = '';
				piece.appendChild( img );
				els.assembly.appendChild( piece );
			} );
		}

		/**
		 * Renders the layout buttons for the active model.
		 *
		 * @return {void}
		 */
		function renderLayouts() {
			if ( ! els.layouts ) {
				return;
			}
			els.layouts.innerHTML = '';
			var model = activeModel();
			if ( ! model ) {
				return;
			}

			model.layouts.forEach( function ( layout ) {
				var btn = makeButton( 'neximan-layout-btn', layout.label, { 'data-layout': layout.id } );
				if ( layout.id === state.layoutId ) {
					btn.classList.add( 'is-active' );
				}
				btn.addEventListener( 'click', function () {
					state.layoutId = layout.id;
					markActive( els.layouts, 'neximan-layout-btn', btn );
					applyLayout();
				} );
				els.layouts.appendChild( btn );
			} );
		}

		/**
		 * Resets part selections for the active layout to defaults and renders
		 * the composition selectors and preview.
		 *
		 * @return {void}
		 */
		function applyLayout() {
			var layout = activeLayout();
			state.partSel = {};
			if ( layout ) {
				( layout.parts || [] ).forEach( function ( part ) {
					if ( part.moduleIds && part.moduleIds.length ) {
						state.partSel[ part.id ] = part.moduleIds[ 0 ];
					}
				} );
			}
			renderParts();
			renderPreview();
		}

		/**
		 * Renders selectable composition parts (e.g. seat size 60/85) for the
		 * active layout. Parts with a single module are fixed and not shown.
		 *
		 * @return {void}
		 */
		function renderParts() {
			if ( ! els.parts ) {
				return;
			}
			els.parts.innerHTML = '';
			var layout = activeLayout();
			if ( ! layout || activeSeries().pricingMode === 'simple' ) {
				return;
			}

			( layout.parts || [] ).forEach( function ( part ) {
				if ( ! part.moduleIds || part.moduleIds.length < 2 ) {
					return; // Fixed part: contributes to price but no selector.
				}

				var section = document.createElement( 'div' );
				section.className = 'neximan-control-section';

				var label = document.createElement( 'label' );
				label.className = 'neximan-control-label';
				label.textContent = part.label || '';
				section.appendChild( label );

				var row = document.createElement( 'div' );
				row.className = 'neximan-option-choices';

				part.moduleIds.forEach( function ( modId ) {
					var mod = moduleById( modId );
					if ( ! mod ) {
						return;
					}
					var btn = makeButton( 'neximan-option-btn', mod.name, {
						'data-part': part.id,
						'data-module': modId
					} );
					if ( state.partSel[ part.id ] === modId ) {
						btn.classList.add( 'is-active' );
					}
					btn.addEventListener( 'click', function () {
						state.partSel[ part.id ] = modId;
						markActive( row, 'neximan-option-btn', btn );
						renderPreview();
					} );
					row.appendChild( btn );
				} );

				section.appendChild( row );
				els.parts.appendChild( section );
			} );
		}

		/**
		 * Renders the color swatches for the active series.
		 *
		 * @return {void}
		 */
		function renderColors() {
			if ( ! els.colors ) {
				return;
			}
			els.colors.innerHTML = '';

			activeSeries().colors.forEach( function ( color ) {
				var btn = makeButton( 'neximan-color-swatch', '', {
					'data-color': color.id,
					title: color.name,
					style: 'background-color:' + color.value
				} );
				if ( color.id === state.colorId ) {
					btn.classList.add( 'is-active' );
				}
				btn.addEventListener( 'click', function () {
					state.colorId = color.id;
					markActive( els.colors, 'neximan-color-swatch', btn );
					renderPreview();
				} );
				els.colors.appendChild( btn );
			} );
		}

		/**
		 * Renders the option groups (e.g. size) for the active series.
		 *
		 * @return {void}
		 */
		function renderOptions() {
			if ( ! els.options ) {
				return;
			}
			els.options.innerHTML = '';
			var groups = activeSeries().options || [];

			groups.forEach( function ( group ) {
				if ( ! group.choices || ! group.choices.length ) {
					return;
				}

				var section = document.createElement( 'div' );
				section.className = 'neximan-control-section';

				var label = document.createElement( 'label' );
				label.className = 'neximan-control-label';
				label.textContent = group.label || '';
				section.appendChild( label );

				var row = document.createElement( 'div' );
				row.className = 'neximan-option-choices';

				group.choices.forEach( function ( choice ) {
					var btn = makeButton( 'neximan-option-btn', choice.name, {
						'data-group': group.id,
						'data-choice': choice.id
					} );
					if ( state.options[ group.id ] === choice.id ) {
						btn.classList.add( 'is-active' );
					}
					btn.addEventListener( 'click', function () {
						state.options[ group.id ] = choice.id;
						markActive( row, 'neximan-option-btn', btn );
						renderPreview();
					} );
					row.appendChild( btn );
				} );

				section.appendChild( row );
				els.options.appendChild( section );
			} );
		}

		/**
		 * Renders the model tabs for the active series.
		 *
		 * @return {void}
		 */
		function renderModelTabs() {
			if ( ! els.modelTabs ) {
				return;
			}
			els.modelTabs.innerHTML = '';
			var models = activeSeries().models;
			var wrap = els.modelTabs.closest( '.neximan-model-tabs-wrap' );

			if ( models.length <= 1 ) {
				if ( wrap ) {
					wrap.style.display = 'none';
				}
				return; // No tabs needed for a single model.
			}
			if ( wrap ) {
				wrap.style.display = '';
			}

			models.forEach( function ( model ) {
				var btn = makeButton( 'neximan-model-tab', model.name, { 'data-model': model.id } );
				if ( model.id === state.modelId ) {
					btn.classList.add( 'is-active' );
				}
				btn.addEventListener( 'click', function () {
					selectModel( model.id );
					markActive( els.modelTabs, 'neximan-model-tab', btn );
				} );
				els.modelTabs.appendChild( btn );
			} );
		}

		/**
		 * Renders the series tabs (only when more than one series).
		 *
		 * @return {void}
		 */
		function renderSeriesTabs() {
			if ( ! els.seriesTabs ) {
				return;
			}
			els.seriesTabs.innerHTML = '';
			if ( config.series.length <= 1 ) {
				return;
			}

			config.series.forEach( function ( series, index ) {
				var btn = makeButton( 'neximan-series-tab', series.name || ( '#' + ( index + 1 ) ), { 'data-series': index } );
				if ( index === state.seriesIndex ) {
					btn.classList.add( 'is-active' );
				}
				btn.addEventListener( 'click', function () {
					selectSeries( index );
					markActive( els.seriesTabs, 'neximan-series-tab', btn );
				} );
				els.seriesTabs.appendChild( btn );
			} );
		}

		/**
		 * Toggles the active class within a container.
		 *
		 * @param {HTMLElement} container Wrapper.
		 * @param {string}      cls       Item class.
		 * @param {HTMLElement} active    Element to activate.
		 * @return {void}
		 */
		function markActive( container, cls, active ) {
			container.querySelectorAll( '.' + cls ).forEach( function ( el ) {
				el.classList.remove( 'is-active' );
			} );
			active.classList.add( 'is-active' );
		}

		/**
		 * Selects a model and resets the layout to its first option.
		 *
		 * @param {string} modelId Model id.
		 * @return {void}
		 */
		function selectModel( modelId ) {
			state.modelId = modelId;
			var model = activeModel();
			state.layoutId = ( model && model.layouts.length ) ? model.layouts[ 0 ].id : '';
			renderLayouts();
			applyLayout();
		}

		/**
		 * Selects a series and resets model/color defaults.
		 *
		 * @param {number} index Series index.
		 * @return {void}
		 */
		function selectSeries( index ) {
			state.seriesIndex = index;
			var series = activeSeries();
			state.colorId = series.colors.length ? series.colors[ 0 ].id : '';

			// Default each option group to its first choice.
			state.options = {};
			( series.options || [] ).forEach( function ( group ) {
				if ( group.choices && group.choices.length ) {
					state.options[ group.id ] = group.choices[ 0 ].id;
				}
			} );

			renderModelTabs();
			renderOptions();
			renderColors();
			selectModel( series.models.length ? series.models[ 0 ].id : '' );
		}

		/**
		 * Sends the current selection to the server to add it to the cart.
		 *
		 * @param {boolean} redirectToCheckout Redirect after adding.
		 * @return {void}
		 */
		function addToCart( redirectToCheckout ) {
			if ( ! settings.ajaxUrl || ! settings.hasWoo ) {
				setFeedback( ( settings.i18n && settings.i18n.error ) || 'Error', 'error' );
				return;
			}

			els.cta.classList.add( 'is-loading' );
			setFeedback( ( settings.i18n && settings.i18n.adding ) || 'Adding...', '' );

			var series = activeSeries();
			var body = new URLSearchParams();
			body.append( 'action', 'neximan_add_to_cart' );
			body.append( 'nonce', settings.nonce );
			body.append( 'manifest', manifestJson );
			body.append( 'sig', signature );
			body.append( 'series', series.id );
			body.append( 'model', state.modelId );
			body.append( 'layout', state.layoutId );
			body.append( 'color', state.colorId );
			Object.keys( state.options ).forEach( function ( groupId ) {
				body.append( 'options[' + groupId + ']', state.options[ groupId ] );
			} );
			Object.keys( state.partSel ).forEach( function ( partId ) {
				body.append( 'parts[' + partId + ']', state.partSel[ partId ] );
			} );

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
						var msg = ( json && json.data && json.data.message ) || ( settings.i18n && settings.i18n.error );
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
		 * @param {string} text     Message.
		 * @param {string} type     '', 'success' or 'error'.
		 * @param {string} htmlLink Optional trailing HTML.
		 * @return {void}
		 */
		function setFeedback( text, type, htmlLink ) {
			if ( ! els.feedback ) {
				return;
			}
			els.feedback.className = 'neximan-feedback' + ( type ? ' is-' + type : '' );
			els.feedback.innerHTML = ( text || '' ) + ( htmlLink || '' );
		}

		if ( els.cta ) {
			els.cta.addEventListener( 'click', function () {
				var action = els.cta.getAttribute( 'data-action' );
				if ( action === 'add_to_cart' || action === 'buy_now' ) {
					addToCart( action === 'buy_now' );
				}
			} );
		}

		// Initial render.
		renderSeriesTabs();
		selectSeries( 0 );
	}

	/**
	 * Initialises every builder on the page.
	 *
	 * @return {void}
	 */
	function initAll() {
		document.querySelectorAll( '[data-neximan-builder]' ).forEach( initBuilder );
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
