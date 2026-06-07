/**
 * Neximan Furniture Builder - admin configuration UI.
 *
 * Builds an unlimited models -> layouts -> colors editor on top of a single
 * JSON field. No nested-repeater limits: add as many models/layouts as needed.
 */
( function ( $ ) {
	'use strict';

	var admin = window.NeximanAdmin || { i18n: {}, standardLayouts: [] };
	var i18n = admin.i18n || {};

	var $root = $( '#neximan-admin' );
	if ( ! $root.length ) {
		return;
	}

	var $json = $( '#neximan-config-json' );
	var $colors = $( '#neximan-colors' );
	var $models = $( '#neximan-models' );

	var config;
	try {
		config = JSON.parse( $json.val() || '{}' );
	} catch ( e ) {
		config = {};
	}
	config.colors = config.colors || [];
	config.models = config.models || [];

	/**
	 * Generates a short unique id.
	 *
	 * @param {string} prefix Id prefix.
	 * @return {string} Unique id.
	 */
	function uid( prefix ) {
		return prefix + Math.random().toString( 36 ).slice( 2, 8 );
	}

	/**
	 * Escapes a value for safe insertion into an attribute.
	 *
	 * @param {*} value Value.
	 * @return {string} Escaped string.
	 */
	function attr( value ) {
		return $( '<div>' ).text( value == null ? '' : String( value ) ).html().replace( /"/g, '&quot;' );
	}

	// ----- Color rows -------------------------------------------------------

	/**
	 * Builds a color row element.
	 *
	 * @param {Object} color Color data.
	 * @return {jQuery} Row element.
	 */
	function colorRow( color ) {
		color = color || {};
		var id = color.id || uid( 'c' );
		var value = color.value || '#cccccc';

		return $(
			'<div class="neximan-row neximan-color-row" data-id="' + attr( id ) + '">' +
				'<input type="color" class="nx-color-value" value="' + attr( value ) + '" />' +
				'<input type="text" class="nx-color-name" placeholder="' + attr( i18n.color || 'Color' ) + '" value="' + attr( color.name || '' ) + '" />' +
				'<input type="number" class="nx-color-price" step="1" min="0" placeholder="+0" value="' + attr( color.price || 0 ) + '" />' +
				'<button type="button" class="button-link nx-remove" title="x">&times;</button>' +
			'</div>'
		);
	}

	// ----- Layout rows ------------------------------------------------------

	/**
	 * Builds a layout row element.
	 *
	 * @param {Object} layout Layout data.
	 * @return {jQuery} Row element.
	 */
	function layoutRow( layout ) {
		layout = layout || {};
		var id = layout.id || uid( 'l' );
		var image = layout.image || '';

		var $row = $(
			'<div class="neximan-row neximan-layout-row" data-id="' + attr( id ) + '" data-image-id="' + attr( layout.imageId || 0 ) + '">' +
				'<span class="nx-thumb" style="' + ( image ? 'background-image:url(\'' + attr( image ) + '\')' : '' ) + '"></span>' +
				'<input type="text" class="nx-layout-label" placeholder="' + attr( i18n.layout || 'Layout' ) + '" value="' + attr( layout.label || '' ) + '" />' +
				'<input type="hidden" class="nx-layout-image" value="' + attr( image ) + '" />' +
				'<button type="button" class="button nx-pick-image">' + ( i18n.selectImage || 'Image' ) + '</button>' +
				'<input type="number" class="nx-layout-price" step="1" min="0" placeholder="+0" value="' + attr( layout.price || 0 ) + '" />' +
				'<button type="button" class="button-link nx-remove" title="x">&times;</button>' +
			'</div>'
		);

		return $row;
	}

	// ----- Model blocks -----------------------------------------------------

	/**
	 * Builds a model block element with its layouts.
	 *
	 * @param {Object} model Model data.
	 * @return {jQuery} Block element.
	 */
	function modelBlock( model ) {
		model = model || {};
		var id = model.id || uid( 'm' );

		var types = { sofa: 'Sofa', table: 'Table', bed: 'Bed', custom: 'Custom' };
		var typeOptions = '';
		Object.keys( types ).forEach( function ( key ) {
			typeOptions += '<option value="' + key + '"' + ( model.type === key ? ' selected' : '' ) + '>' + types[ key ] + '</option>';
		} );

		var $block = $(
			'<div class="neximan-model" data-id="' + attr( id ) + '">' +
				'<div class="neximan-model-head">' +
					'<input type="text" class="nx-model-name" placeholder="' + attr( i18n.model || 'Model' ) + '" value="' + attr( model.name || '' ) + '" />' +
					'<select class="nx-model-type">' + typeOptions + '</select>' +
					'<label class="nx-inline">Base <input type="number" class="nx-model-base" step="1" min="0" value="' + attr( model.basePrice || 0 ) + '" /></label>' +
					'<label class="nx-inline">Woo ID <input type="number" class="nx-model-woo" step="1" min="0" value="' + attr( model.wooId || 0 ) + '" /></label>' +
					'<button type="button" class="button-link nx-remove-model" title="x">&times;</button>' +
				'</div>' +
				'<div class="neximan-layouts"></div>' +
				'<div class="neximan-model-actions">' +
					'<button type="button" class="button nx-add-layout">+ ' + ( i18n.layout || 'Layout' ) + '</button>' +
					'<button type="button" class="button nx-add-standard">+ Standard layouts</button>' +
				'</div>' +
			'</div>'
		);

		var $layouts = $block.find( '.neximan-layouts' );
		( model.layouts || [] ).forEach( function ( layout ) {
			$layouts.append( layoutRow( layout ) );
		} );

		return $block;
	}

	// ----- Initial render ---------------------------------------------------

	config.colors.forEach( function ( color ) {
		$colors.append( colorRow( color ) );
	} );
	config.models.forEach( function ( model ) {
		$models.append( modelBlock( model ) );
	} );

	// ----- Serialization ----------------------------------------------------

	/**
	 * Reads the DOM back into the JSON field.
	 *
	 * @return {void}
	 */
	function serialize() {
		var data = {
			wooProductId: parseInt( $( '#neximan-woo-id' ).val(), 10 ) || 0,
			priceMode: $( '#neximan-price-mode' ).val() || 'dynamic',
			colors: [],
			models: []
		};

		$colors.children( '.neximan-color-row' ).each( function () {
			var $r = $( this );
			data.colors.push( {
				id: $r.data( 'id' ),
				value: $r.find( '.nx-color-value' ).val(),
				name: $r.find( '.nx-color-name' ).val(),
				price: parseFloat( $r.find( '.nx-color-price' ).val() ) || 0
			} );
		} );

		$models.children( '.neximan-model' ).each( function () {
			var $m = $( this );
			var model = {
				id: $m.data( 'id' ),
				name: $m.find( '.nx-model-name' ).val(),
				type: $m.find( '.nx-model-type' ).val(),
				basePrice: parseFloat( $m.find( '.nx-model-base' ).val() ) || 0,
				wooId: parseInt( $m.find( '.nx-model-woo' ).val(), 10 ) || 0,
				layouts: []
			};

			$m.find( '.neximan-layout-row' ).each( function () {
				var $l = $( this );
				model.layouts.push( {
					id: $l.data( 'id' ),
					label: $l.find( '.nx-layout-label' ).val(),
					image: $l.find( '.nx-layout-image' ).val(),
					imageId: parseInt( $l.attr( 'data-image-id' ), 10 ) || 0,
					price: parseFloat( $l.find( '.nx-layout-price' ).val() ) || 0
				} );
			} );

			data.models.push( model );
		} );

		$json.val( JSON.stringify( data ) );
	}

	// ----- Events -----------------------------------------------------------

	$root.on( 'input change', 'input, select', serialize );

	$( '#neximan-add-color' ).on( 'click', function () {
		$colors.append( colorRow( { id: uid( 'c' ) } ) );
		serialize();
	} );

	$( '#neximan-add-model' ).on( 'click', function () {
		$models.append( modelBlock( { id: uid( 'm' ) } ) );
		serialize();
	} );

	// Delegated remove (color / layout rows).
	$root.on( 'click', '.nx-remove', function () {
		$( this ).closest( '.neximan-row' ).remove();
		serialize();
	} );

	// Remove a model.
	$models.on( 'click', '.nx-remove-model', function () {
		if ( window.confirm( i18n.confirmDelete || 'Remove?' ) ) {
			$( this ).closest( '.neximan-model' ).remove();
			serialize();
		}
	} );

	// Add a layout to a model.
	$models.on( 'click', '.nx-add-layout', function () {
		$( this ).closest( '.neximan-model' ).find( '.neximan-layouts' ).append( layoutRow( { id: uid( 'l' ) } ) );
		serialize();
	} );

	// Add the standard layout preset to a model.
	$models.on( 'click', '.nx-add-standard', function () {
		var $layouts = $( this ).closest( '.neximan-model' ).find( '.neximan-layouts' );
		( admin.standardLayouts || [] ).forEach( function ( label ) {
			$layouts.append( layoutRow( { id: uid( 'l' ), label: label } ) );
		} );
		serialize();
	} );

	// Media uploader for layout images.
	var frame = null;
	$models.on( 'click', '.nx-pick-image', function () {
		var $row = $( this ).closest( '.neximan-layout-row' );

		frame = wp.media( {
			title: i18n.selectImage || 'Select Image',
			button: { text: i18n.useImage || 'Use this image' },
			multiple: false
		} );

		frame.on( 'select', function () {
			var attachment = frame.state().get( 'selection' ).first().toJSON();
			$row.find( '.nx-layout-image' ).val( attachment.url );
			$row.attr( 'data-image-id', attachment.id );
			$row.find( '.nx-thumb' ).css( 'background-image', "url('" + attachment.url + "')" );
			serialize();
		} );

		frame.open();
	} );

	// Ensure the JSON is current before submit.
	$( 'form#post' ).on( 'submit', serialize );

	// Initialise the JSON field once.
	serialize();
} )( jQuery );
