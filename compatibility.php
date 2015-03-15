<?php
/**
 * Environment compatibility checks and notices.
 *
 * @package   CPTArchives
 * @since     3.0.0
 * @link      https://github.com/cedaro/cpt-archives
 * @copyright Copyright (c) 2015 Cedaro, LLC
 * @license   GPL-2.0+
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Display an admin notice when the current verison of PHP is unsupported.
 *
 * @since 3.0.0
 */
function cptarchives_php_compatibility_notice() {
	$plugin_rel_path = dirname( plugin_basename( __FILE__ ) ) . '/languages';
	load_plugin_textdomain( 'cpt-archives', false, $plugin_rel_path );
	?>
	<style type="text/css">#cptarchives-required-notice { display: none;}</style>
	<div id="cptarchives-php-compatibility-notice" class="error">
		<p>
			<?php
			printf(
				__( 'CPT Archives requires PHP 5.4 or later to run. Your current version is %s.', 'cpt-archives' ),
				phpversion()
			);
			?>
			<a href="http://www.wpupdatephp.com/update/" target="_blank"><?php _e( 'Learn more.', 'cpt-archives' ); ?></a>
		</p>
	</div>
	<?php
}
add_action( 'admin_notices', 'cptarchives_php_compatibility_notice' );
