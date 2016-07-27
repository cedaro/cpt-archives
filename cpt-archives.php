<?php
/**
 * CPT Archives
 *
 * @package   CPTArchives
 * @author    Brady Vercher
 * @link      https://github.com/cedaro/cpt-archives
 * @copyright Copyright (c) 2015 Cedaro, LLC
 * @license   GPL-2.0+
 *
 * @wordpress-plugin
 * Plugin Name:       CPT Archives
 * Plugin URI:        https://github.com/cedaro/cpt-archives
 * Description:       Manage post type archive titles, descriptions, and permalinks from the dashboard.
 * Version:           3.0.2
 * Author:            Cedaro
 * Author URI:        http://www.cedaro.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       cpt-archives
 * Domain Path:       /languages
 * GitHub Plugin URI: cedaro/cpt-archives
 */

/**
 * Load the plugin or display a notice about requirements.
 */
if ( version_compare( phpversion(), '5.4', '>=' ) ) {
	require( 'plugin.php' );
} else {
	require( 'compatibility.php' );
}
