<?php
/**
 * Hooks trait.
 *
 * Allows for using protected and private methods for hook callbacks.
 *
 * @package CPTArchives
 * @since   3.0.0
 * @author  John P. Bloch
 * @license MIT
 * @link    https://github.com/johnpbloch/wordpress-dev/blob/master/src/Hooks.php
 */

namespace Cedaro\CPTArchives;

/**
 * Hooks trait.
 *
 * @package CPTArchives
 * @since   3.0.0
 * @author  John P. Bloch
 * @license MIT
 * @link    https://github.com/johnpbloch/wordpress-dev/blob/master/src/Hooks.php
 */
trait Hooks {
	/**
	 * Internal property to track closures attached to WordPress hooks.
	 *
	 * @var array
	 */
	protected $__filterMap = [];

	/**
	 * Add a WordPress filter.
	 *
	 * @param  string   $hook
	 * @param  callable $method
	 * @param  int      $priority
	 * @param  int      $arg_count
	 * @return bool true
	 */
	protected function add_filter( $hook, $method, $priority = 10, $arg_count = 1 ) {
		return add_filter(
			$hook,
			$this->map_filter( $this->get_wp_filter_id( $hook, $method, $priority ), $method, $arg_count ),
			$priority,
			$arg_count
		);
	}

	/**
	 * Add a WordPress action.
	 *
	 * This is an alias of add_filter().
	 *
	 * @param  string   $hook
	 * @param  callable $method
	 * @param  int      $priority
	 * @param  int      $arg_count
	 * @return bool true
	 */
	protected function add_action( $hook, $method, $priority = 10, $arg_count = 1 ) {
		return $this->add_filter( $hook, $method, $priority, $arg_count );
	}

	/**
	 * Remove a WordPress filter.
	 *
	 * @param  string   $hook
	 * @param  callable $method
	 * @param  int      $priority
	 * @param  int      $arg_count
	 * @return bool Whether the function existed before it was removed.
	 */
	protected function remove_filter( $hook, $method, $priority = 10, $arg_count = 1 ) {
		return remove_filter(
			$hook,
			$this->map_filter( $this->get_wp_filter_id( $hook, $method, $priority ), $method, $arg_count ),
			$priority,
			$arg_count
		);
	}

	/**
	 * Remove a WordPress action.
	 *
	 * This is an alias of remove_filter().
	 *
	 * @param  string   $hook
	 * @param  callable $method
	 * @param  int      $priority
	 * @param  int      $arg_count
	 * @return bool Whether the function is removed.
	 */
	protected function remove_action( $hook, $method, $priority = 10, $arg_count = 1 ) {
		return $this->remove_filter( $hook, $method, $priority, $arg_count );
	}

	/**
	 * Get a unique ID for a hook based on the internal method, hook, and priority.
	 *
	 * @param  string $hook
	 * @param  string $method
	 * @param  int    $priority
	 * @return bool|string
	 */
	protected function get_wp_filter_id( $hook, $method, $priority ) {
		return _wp_filter_build_unique_id( $hook, [ $this, $method ], $priority );
	}

	/**
	 * Map a filter to a closure that inherits the class' internal scope.
	 *
	 * This allows hooks to use protected and private methods.
	 *
	 * @param  $id
	 * @param  $method
	 * @param  $arg_count
	 * @return \Closure The callable actually attached to a WP hook
	 */
	protected function map_filter( $id, $method, $arg_count ) {
		if ( empty( $this->__filterMap[ $id ] ) ) {
			$this->__filterMap[ $id ] = function () use ( $method, $arg_count ) {
				return call_user_func_array( [ $this, $method ], array_slice( func_get_args(), 0, $arg_count ) );
			};
		}

		return $this->__filterMap[ $id ];
	}
}
