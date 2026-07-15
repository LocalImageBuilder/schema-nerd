<?php
/*
Plugin Name: Schema Nerd
Plugin URI: https://localimageco.com/
Description: API interface for Schema Nerd organizations
Version: 1.2.1
Author: Local Image
Author URI: https://localimageco.com/contact/
Text Domain: schema-nerd
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'SCHEMA_NERD_PLUGIN_FILE', __FILE__ );

// Let's Initialize Everything
if ( file_exists( plugin_dir_path( __FILE__ ) . 'core-init.php' ) ) {
	require_once plugin_dir_path( __FILE__ ) . 'core-init.php';
}
