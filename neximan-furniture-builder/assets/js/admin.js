/**
 * Neximan Furniture Builder - admin configuration UI.
 *
 * Builds an unlimited models -> layouts editor plus fabric colors and generic
 * option groups (e.g. Size), all on top of a single JSON field.
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
	var $options = $( '#neximan-options' );
	var $models = $( '#neximan-models' );

	var config;
	try {
		config = JSON.parse( $json.val() || '{}' );
	} catch ( e ) {
		config = {};
	}
	config.colors = config.colors || [];
	config.options = config.options || [];
	config.models = config.models || [];

	/**
	 * Generates a short unique id.
	 *
	 * @param {string} prefix Prefix.
	 * @return {string} Id.
	 */
	function uid( prefix ) {
		return prefix + Math.random().toString( 36 ).slice( 2, 8 );
	}

	/**
	 * Escapes a value for an attribute.
	 *
	 * @param {*} value Value.
	 * @return {string} Escaped.
	 */
	function attr( value ) {
		return $( '<div>' ).text( value == null ? '' : String( value ) ).html().replace( /"/g, '&quot;' );
	}

	// ----- Colors -----------------------------------------------------------

	/**
	 * Builds a color row.
	 *
	 * @param {Object} color Color data.
	 * @return {jQuery} Row.
	 */
	function colorRow( color ) {
		color = color || {};
		var id = color.id || uid( 'c' );

		return $(
			'<div class="neximan-row neximan-color-row" data-id="' + attr( id ) + '">' +
				'<input type="color" class="nx-color-value" value="' + attr( color.value || '#cccccc' ) + '" />' +
				'<input type="text" class="nx-color-name" placeholder="' + attr( i18n.color || 'Color' ) + '" value="' + attr( color.name || '' ) + '" />' +
				'<input type="number" class="nx-color-price" step="1" min="0" placeholder="+0" value="' + attr( color.price || 0 ) + '" />' +
				'<input type="text" class="nx-color-var" placeholder="var value" value="' + attr( color.varValue || '' ) + '" />' +
				'<button type="button" class="button-link nx-remove" title="x">&times;</button>' +
			'</div>'
		);
	}

	// ----- Option groups ----------------------------------------------------

	/**
	 * Builds an option-choice row.
	 *
	 * @param {Object} choice Choice data.
	 * @return {jQuery} Row.
	 */
	function choiceRow( choice ) {
		choice = choice || {};
		var id = choice.id || uid( 'o' );

		return $(
			'<div class="neximan-row neximan-choice-row" data-id="' + attr( id ) + '">' +
				'<input type="text" class="nx-choice-name" placeholder="choice" value="' + attr( choice.name || '' ) + '" />' +
				'<input type="number" class="nx-choice-price" step="1" min="0" placeholder="+0" value="' + attr( choice.price || 0 ) + '" />' +
				'<input type="text" class="nx-choice-var" placeholder="var value" value="' + attr( choice.varValue || '' ) + '" />' +
				'<button type="button" class="button-link nx-remove" title="x">&times;</button>' +
			'</div>'
		);
	}

	/**
	 * Builds an option group block.
	 *
	 * @param {Object} group Group data.
	 * @return {jQuery} Block.
	 */
	function optionGroup( group ) {
		group = group || {};
		var id = group.id || uid( 'g' );

		var $block = $(
			'<div class="neximan-model neximan-option-group" data-id="' + attr( id ) + '">' +
				'<div class="neximan-model-head">' +
					'<input type="text" class="nx-group-label" placeholder="Group label (e.g. سایز)" value="' + attr( group.label || '' ) + '" />' +
					'<label class="nx-inline">Attr <input type="text" class="nx-group-var" placeholder="pa_size" value="' + attr( group.varAttr || '' ) + '" /></label>' +
					'<button type="button" class="button-link nx-remove-group" title="x">&times;</button>' +
				'</div>' +
				'<div class="neximan-choices"></div>' +
				'<div class="neximan-model-actions">' +
					'<button type="button" class="button nx-add-choice">+ Choice</button>' +
				'</div>' +
			'</div>'
		);

		var $choices = $block.find( '.neximan-choices' );
		( group.choices || [] ).forEach( function ( choice ) {
			$choices.append( choiceRow( choice ) );
		} );

		return $block;
	}

	// ----- Layouts ----------------------------------------------------------

	/**
	 * Builds a layout row.
	 *
	 * @param {Object} layout Layout data.
	 * @return {jQuery} Row.
	 */
	function layoutRow( layout ) {
		layout = layout || {};
		var id = layout.id || uid( 'l' );
		var image = layout.image || '';

		return $(
			'<div class="neximan-row neximan-layout-row" data-id="' + attr( id ) + '" data-image-id="' + attr( layout.imageId || 0 ) + '">' +
				'<span class="nx-thumb" style="' + ( image ? 'background-image:url(\'' + attr( image ) + '\')' : '' ) + '"></span>' +
				'<input type="text" class="nx-layout-label" placeholder="' + attr( i18n.layout || 'Layout' ) + '" value="' + attr( layout.label || '' ) + '" />' +
				'<input type="hidden" class="nx-layout-image" value="' + attr( image ) + '" />' +
				'<button type="button" class="button nx-pick-image">' + ( i18n.selectImage || 'Image' ) + '</button>' +
				'<input type="number" class="nx-layout-price" step="1" min="0" placeholder="+0" value="' + attr( layout.price || 0 ) + '" />' +
				'<input type="text" class="nx-layout-var" placeholder="var value" value="' + attr( layout.varValue || '' ) + '" />' +
				'<button type="button" class="button-link nx-remove" title="x">&times;</button>' +
			'</div>'
		);
	}

	// ----- Models -----------------------------------------------------------

	/**
	 * Builds a model block.
	 *
	 * @param {Object} model Model data.
	 * @return {jQuery} Block.
	 */
	function modelBlock( model ) {
		model = model || {};
		var id = model.id || uid( 'm' );

		var types = { sofa: 'Sofa', table: 'Table', bed: 'Bed', chair: 'Chair', custom: 'Custom' };
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
	config.options.forEach( function ( group ) {
		$options.append( optionGroup( group ) );
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
			varAttrs: {
				layout: $( '#neximan-var-layout' ).val() || '',
				color: $( '#neximan-var-color' ).val() || ''
			},
			colors: [],
			options: [],
			models: []
		};

		$colors.children( '.neximan-color-row' ).each( function () {
			var $r = $( this );
			data.colors.push( {
				id: $r.data( 'id' ),
				value: $r.find( '.nx-color-value' ).val(),
				name: $r.find( '.nx-color-name' ).val(),
				price: parseFloat( $r.find( '.nx-color-price' ).val() ) || 0,
				varValue: $r.find( '.nx-color-var' ).val() || ''
			} );
		} );

		$options.children( '.neximan-option-group' ).each( function () {
			var $g = $( this );
			var group = {
				id: $g.data( 'id' ),
				label: $g.find( '.nx-group-label' ).val(),
				varAttr: $g.find( '.nx-group-var' ).val() || '',
				choices: []
			};
			$g.find( '.neximan-choice-row' ).each( function () {
				var $c = $( this );
				group.choices.push( {
					id: $c.data( 'id' ),
					name: $c.find( '.nx-choice-name' ).val(),
					price: parseFloat( $c.find( '.nx-choice-price' ).val() ) || 0,
					varValue: $c.find( '.nx-choice-var' ).val() || ''
				} );
			} );
			data.options.push( group );
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
					price: parseFloat( $l.find( '.nx-layout-price' ).val() ) || 0,
					varValue: $l.find( '.nx-layout-var' ).val() || ''
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

	$( '#neximan-add-option' ).on( 'click', function () {
		var $g = optionGroup( { id: uid( 'g' ) } );
		$g.find( '.neximan-choices' ).append( choiceRow( { id: uid( 'o' ) } ) );
		$options.append( $g );
		serialize();
	} );

	$( '#neximan-add-model' ).on( 'click', function () {
		$models.append( modelBlock( { id: uid( 'm' ) } ) );
		serialize();
	} );

	// Remove a simple row (color / choice / layout).
	$root.on( 'click', '.nx-remove', function () {
		$( this ).closest( '.neximan-row' ).remove();
		serialize();
	} );

	// Remove an option group.
	$options.on( 'click', '.nx-remove-group', function () {
		if ( window.confirm( i18n.confirmDelete || 'Remove?' ) ) {
			$( this ).closest( '.neximan-option-group' ).remove();
			serialize();
		}
	} );

	// Add a choice to a group.
	$options.on( 'click', '.nx-add-choice', function () {
		$( this ).closest( '.neximan-option-group' ).find( '.neximan-choices' ).append( choiceRow( { id: uid( 'o' ) } ) );
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

	// Add standard layouts preset.
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

	// Keep JSON current before submit.
	$( 'form#post' ).on( 'submit', serialize );

	serialize();
} )( jQuery );
