<?php

/*
------------------------------------------------------------------------

Copyright 2015–2016 Aki Björklund.

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA

-----------------------------------------------------------------------
*/

class WP_Simple_Asset_Optimizer {

	protected $move;
	protected $move_if_not_enqueued;
	protected $inline;

	protected $wp_scripts;
	protected $wp_styles;

	/**
	 * The constructor.
	 * @param object $wp_scripts WordPress global scripts object
	 * @param object $wp_styles WordPress global styles object
	 */
	function __construct( &$wp_scripts, &$wp_styles ) {

		$this->wp_scripts = $wp_scripts;
		$this->wp_styles  = $wp_styles;

		// Hook running the tasks of this class to 'wp_enqueue_scripts', executed very late
		add_action( 'wp_enqueue_scripts', array( $this, 'run' ), 9999 );
	}

	/**
	 * The main function.
	 */
	function run() {

		// Get optimization data through filters.
		$this->move                 = apply_filters( 'wpsao_move',                  array() );
		$this->move_if_not_enqueued = apply_filters( 'wpsao_move_if_not_enqueued',  array() );
		$this->inline               = apply_filters( 'wpsao_inline',                array() );

		// Handle inlining.
		$this->prepare_inline_assets();
		add_action( 'wp_head', array( $this, 'inline_assets' ) );

		// Move scripts.
		$this->move_script_to_bottom( $this->move );

		// Move other scripts if no dependency script enqueued.
		foreach ( $this->move_if_not_enqueued as $move_if ) {
			if ( is_array( $move_if ) && count( $move_if ) > 0 ) {
				$this->move_script_to_bottom_if_another_not_enqueued( $move_if[0], $move_if[1] );
			}
		}
	}

	/**
	 * Move script(s) to bottom
	 *
	 * @param string|array $script_handles Script handles to be moved to bottom
	 * @param array        $args           Additional arguments. Defaults to array( 'move_deps' => false ). Set to true to also move scripts dependencies.
	 */
	protected function move_script_to_bottom( $script_handles, $args = array() ) {

		$defaults = array(
			'move_deps' => false,
		);

		$args = wp_parse_args( $args, $defaults );

		// Make it an array if not already.
		if ( ! is_array( $script_handles ) ) {
			$script_handles = array( $script_handles );
		}

		// Loop through scripts about to be moved.
		foreach ( $script_handles as $script_handle ) {
			// Check if the script is enqueued.
			if ( ! wp_script_is( $script_handle ) ) {
				continue;
			}

			if ( $args['move_deps'] ) {
				// Get script data.
				$script = $this->wp_scripts->registered[ $script_handle ];

				// Move script's dependencies to bottom.
				foreach ( $script->deps as $dep ) {
					$this->wp_scripts->add_data( $dep, 'group', 1 );
				}
			}

			// Move the main script to bottom.
			$this->wp_scripts->add_data( $script_handle, 'group', 1 );
		}
	}

	/**
	 * Move script(s) to bottom if none of scripts as the second parameter are enqueued.
	 *
	 * @param string|array $script_handles            Script handles to be moved to bottom
	 * @param string|array $dependency_script_handles Script handles that cannot be enqueued
	 */
	protected function move_script_to_bottom_if_another_not_enqueued( $script_handles, $dependency_script_handles ) {

		// Make it an array if not already
		if ( ! is_array( $dependency_script_handles ) ) {
			$dependency_script_handles = array( $dependency_script_handles );
		}

		// Don't do anything if any of the scripts are enqueued
		foreach ( $dependency_script_handles as $dependency_script_handle ) {
			if ( wp_script_is( $dependency_script_handle ) ) {
				return;
			}
		}

		$this->move_script_to_bottom( $script_handles, array( 'move_deps' => true ) );
	}

	/**
	 * Prepare for inlining script(s) adn style(s) by dequeueing them.
	 */
	protected function prepare_inline_assets() {

		// Make it an array if not already
		if ( ! is_array( $this->inline ) ) {
			$this->inline = array( $this->inline );
		}

		foreach ( $this->inline as $to_inline => $params ) {
			if ( is_numeric( $to_inline ) ) {
				$to_inline = $params;
			}
			if ( wp_script_is( $to_inline ) ) {
				wp_dequeue_script( $to_inline );
			} elseif ( wp_style_is( $to_inline ) ) {
				wp_dequeue_style( $to_inline );
			}
		}
	}

	/**
	 * Inline assets. Should run on wp_head.
	 */
	function inline_assets() {

		foreach ( $this->inline as $to_inline => $params ) {
			if ( is_numeric( $to_inline ) ) {
				$to_inline = $params;
			}

			if ( isset( $this->wp_scripts->registered[ $to_inline ] ) && $this->wp_scripts->registered[ $to_inline ] ) {
				$asset = $this->wp_scripts->registered[ $to_inline ];
				$element = 'script';
			} elseif ( isset( $this->wp_styles->registered[ $to_inline ] ) && $this->wp_styles->registered[ $to_inline ] ) {
				$asset = $this->wp_styles->registered[ $to_inline ];
				$element = 'style';
			} else {
				// Asset not found.
				continue;
			}

			$src = $asset->src;
			$dir = $this->src_to_dir( $src );

			if ( false !== $dir ) {
				$content = file_get_contents( $dir );

				if ( isset( $params['replace'] ) && isset( $params['with'] ) ) {
					$content = str_replace( $params['replace'], $params['with'], $content );
				}

				echo '<' . $element . '>' . $content . '</' . $element . '>';
			}
		}
	}

	/**
	 * Convert a url to a WordPress content resource to a file path
	 * @param string $src Url.
	 * @return string|bool File path of the resource or false if not.
	 */
	protected function src_to_dir( $src ) {

		$content_url = content_url();
		$template_dir = get_template_directory();

		// Assume content directory is two directories up from current template.
		$content_dir = dirname( dirname( $template_dir ) );

		// If asset is under content_url.
		if ( substr( $src, 0, strlen( $content_url ) ) == $content_url ) {
			// Get its sub path.
			$src_sub_path = substr( $src, strlen( $content_url ) );
			// Append it to $content_dir to get the directory.
			$asset_dir = $content_dir . $src_sub_path;

			if ( file_exists( $asset_dir ) ) {
				return $asset_dir;
			}

			// File does not exist, something went wrong.
			return false;
		}

		// Some other type of resource not controlled by WordPress.
		return false;
	}
}
