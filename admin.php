<?php
/**
 * Admin Functions
 *
 * General admin area functions. Also see page.php.
 *
 * @package    Rich_Reviews_Importer
 * @subpackage Functions
 * @copyright  Copyright (c) 2018, joshuaevanjohnson.com
 * @link       https://joshuaevanjohnson.com/plugins/rich-reviews-importer/
 * @license    GPLv2 or later
 * @since      0.1
 */

// No direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueue admin styles.
 *
 * @since 1.5
 */
function rri_enqueue_styles() {

	// Get current screen.
	$screen = get_current_screen();

	// Only on RRI and Dashboard screens.
	if ( ! in_array( $screen->base, array( 'dashboard', 'tools_page_rich-reviews-importer' ), true ) ) {
		return;
	}

	// Enqueue styles
	wp_enqueue_style( 'rri-main', RRI_URL . '/' . RRI_CSS_DIR . '/style.css', false, RRI_VERSION ); // Bust cache on update.

}

add_action( 'admin_enqueue_scripts', 'rri_enqueue_styles' ); // admin-end only.

/**
 * Add plugin action link.
 *
 * Insert an "Import" link into the plugin's action links (Plugin page's list)
 *
 * @since 1.4
 * @param array $links Existing action links.
 * @return array Modified action links
 */
function rri_add_plugin_action_link( $links ) {

	// If has permission.
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return array();
	}

	// Have links array?
	if ( is_array( $links ) ) {

		// Append "Settings" link.
		$links[] = sprintf(
			'<a href="%1$s">%2$s</a>',
			esc_url( admin_url( 'tools.php?page=rich-reviews_importer' ) ),
			esc_html__( 'Import', 'rich-reviews-importer' )
		);

	}

	return $links;

}
