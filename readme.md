# WP Simple Asset Optimizer

WP Simple Asset Optimizer is a helper drop-in WordPress projects to optimize the loading of scripts and styles. It helps you intelligently move scrips to bottom and inline scripts and styles.

## Examples

### Moving scripts

    add_filter( 'wpsao_move', function () {
    	return array(
    		'jquery_json',
    		'gform_placeholder',
    		'gform_gravityforms',
    		'optin-monster-api-script',
    		'wp-mediaelement',
    		'visualizer-google-jsapi',
    		'visualizer-render'
    	);
    } );

### Moving scripts if others are not enqueued

    add_filter( 'wpsao_move_if_not_enqueued', function () {
    	return array(
    		array(
    			array(
    				'jquery-core',
    				'jquery-migrate'
    			),
    			'gform_gravityforms'
    		)
    	);
    } );

### Inlining

    add_filter( 'wpsao_inline', function () {
    	return array(
    		'modernizr',
    		'h1-stylesheet' => array(
    			'replace' => '../../',
    			'with' => get_template_directory_uri() . '/assets/'
    		)
    	);
    } );

## About

This helper was created by [Aki Björklund](http://akibjorklund.com/).

## Changelog ##

**0.1.1**
* Fix a PHP warning.

**0.1**
* Initial version.