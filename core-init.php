<?php 
/*
*
*	***** Schema Nerd *****
*
*	This file initializes all SN Core components
*	
*/
// If this file is called directly, abort. //
if ( ! defined( 'WPINC' ) ) {die;} // end if
// Define Our Constants
define('SN_CORE_INC',dirname( __FILE__ ).'/assets/inc/');
define('SN_CORE_IMG',plugins_url( 'assets/img/', __FILE__ ));
define('SN_CORE_CSS',plugins_url( 'assets/css/', __FILE__ ));
define('SN_CORE_JS',plugins_url( 'assets/js/', __FILE__ ));
define('SN_API_URL', 'https://schemanerd.app/wp-json/sn/v2/organization');
define('SN_ACCOUNT_URL', 'https://schemanerd.app/wp-json/sn/v2/account');
/*
*
*  Register CSS
*
*/
function schema_nerd_register_core_css() {
wp_enqueue_style('sn-core', SN_CORE_CSS . 'sn-core.css',null,time(),'all');
};
add_action( 'wp_enqueue_scripts', 'schema_nerd_register_core_css' );    
/*
*
*  Register JS/Jquery Ready
*
*/
function schema_nerd_register_core_js() {
// Register Core Plugin JS	
wp_enqueue_script('sn-core', SN_CORE_JS . 'sn-core.js','jquery',time(),true);
};
add_action( 'wp_enqueue_scripts', 'schema_nerd_register_core_js' );    
/*
*
*  Includes
*
*/ 
// Load the Functions

// API / schema init (load before settings — settings uses these helpers)
if ( file_exists( SN_CORE_INC . 'sn_init.php' ) ) {
	require_once SN_CORE_INC . 'sn_init.php';
}
// Plugin Settings
if ( file_exists( SN_CORE_INC . 'settings.php' ) ) {
	require_once SN_CORE_INC . 'settings.php';
}
// Shortcodes
if ( file_exists( SN_CORE_INC . 'shortcodes.php' ) ) {
	require_once SN_CORE_INC . 'shortcodes.php';
}
// Location builder (shared render, widget, block)
if ( file_exists( SN_CORE_INC . 'location-builder-render.php' ) ) {
	require_once SN_CORE_INC . 'location-builder-render.php';
}
if ( file_exists( SN_CORE_INC . 'widget-location-builder.php' ) ) {
	require_once SN_CORE_INC . 'widget-location-builder.php';
}
if ( file_exists( SN_CORE_INC . 'block-location-builder.php' ) ) {
	require_once SN_CORE_INC . 'block-location-builder.php';
}
