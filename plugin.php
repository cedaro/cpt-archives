<?php
/**
 * Plugin initialization.
 *
 * @package   CPTArchives
 * @since     3.0.0
 * @link      https://github.com/cedaro/cpt-archives
 * @copyright Copyright (c) 2015 Cedaro, LLC
 * @license   GPL-2.0+
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Cedaro\CPTArchives\Plugin;
use Cedaro\CPTArchives\PostType\ArchiveHookProvider;

/**
 * Load the autoloader.
 *
 * @since 3.0.0
 */
require( __DIR__ . '/autoload.php' );

/**
 * Retrieve the main plugin instance.
 *
 * @since 3.0.1
 *
 * @return Plugin
 */
function cptarchives() {
	global $cptarchives;

	if ( null === $cptarchives ) {
		$cptarchives = new Plugin();
	}

	return $cptarchives;
}

cptarchives()->set_plugin_file( __DIR__ . '/cpt-archives.php' )
             ->set_archive_class( '\Cedaro\CPTArchives\PostType\Archive' );

/**
 * Load the plugin.
 *
 * @since 3.0.0
 */
add_action( 'plugins_loaded', function() {
	cptarchives()->register_hooks( new ArchiveHookProvider );
	cptarchives()->load();
} );
