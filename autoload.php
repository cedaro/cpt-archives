<?php
/**
 * Plugin autoloader.
 *
 * @package CPTArchives
 * @since   3.0.0
 * @link    https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-4-autoloader-examples.md
 * @license MIT
 */

/**
 * Autoloader.
 *
 * @since 3.0.0
 *
 * @param string $class The fully-qualified class name.
 */
spl_autoload_register( function ( $class ) {
	$prefix         = 'Cedaro\\CPTArchives\\';
	$base_directory = __DIR__ . '/php/';

	$length = strlen( $prefix );
	if ( 0 !== strncmp( $prefix, $class, $length ) ) {
		return;
	}

	$relative_class = substr( $class, $length );
	$file           = $base_directory . str_replace( '\\', '/', $relative_class ) . '.php';

	if ( file_exists( $file ) ) {
		require( $file );
	}
} );
