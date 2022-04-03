

<?php
/**
 * The following snippets uses `PLUGIN` to prefix
 * the constants and class names. You should replace
 * it with something that matches your plugin name.
 */
// define test environment
define( 'PLUGIN_PHPUNIT', true );

// define fake ABSPATH
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', sys_get_temp_dir() );
}
// define fake PLUGIN_ABSPATH
if ( ! defined( 'PLUGIN_ABSPATH' ) ) {
	define( 'PLUGIN_ABSPATH', sys_get_temp_dir() . '/wp-content/plugins/scss-library-kniff/' );
}
// define fake WP_CONTENT_DIR
if ( ! defined( 'WP_CONTENT_DIR' ) ) {
	define( 'WP_CONTENT_DIR', dirname( __FILE__ ) . '/assets/' ) ;
}
// define fake WP_CONTENT_URL
if ( ! defined( 'WP_CONTENT_URL' ) ) {
	define( 'WP_CONTENT_URL', 'file://' . dirname( __FILE__ ) . '/assets/' );
}
// define fake PATH_CURRENT_SITE
if ( ! defined( 'PATH_CURRENT_SITE' ) ) {
	define( 'PATH_CURRENT_SITE', '/' );
}

require_once __DIR__ . '/../vendor/autoload.php';

// Include the class for PluginTestCase
require_once __DIR__ . '/phpunit/BaseTestCase.php';
