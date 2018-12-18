<?php
/**
 * Admin Page Functions
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
 * Add import/export page under Tools
 *
 * @since 0.1
 */
function rri_add_import_page() {

	// Add page.
	$page_hook = add_management_page(
		esc_html__( 'Rich Reviews Importer', 'rich-reviews-importer' ), // Page title.
		esc_html__( 'Rich Reviews Importer', 'rich-reviews-importer' ), // Menu title.
		'edit_theme_options', // Capability
		'rich-reviews-importer', // Menu Slug.
		'rri_import_page_content' // Callback for displaying page content.
	);

}

add_action( 'admin_menu', 'rri_add_import_page' );

/**
 * Import/export page content
 *
 * @since 0.1
 */
function rri_import_page_content() {

	?>
	<div class="wrap">

		<h2><?php esc_html_e( 'Rich Reviews Importer', 'rich-reviews-importer' ); ?></h2>

		<?php

		// Show import results if have them.
		if ( rri_have_import_results() ) {

			rri_show_import_results();

			// Don't show content below.
			return;

		}

		?>

		<h3 class="title"><?php echo esc_html_x( 'Import Rich Reviews', 'heading', 'rich-reviews-importer' ); ?></h3>

		<p>

			<?php

			echo wp_kses(
				__( 'Please select a <b>.csv</b> file created for this importer.', 'rich-reviews-importer' ),
				array(
					'b' => array(),
				)
			);

			?>

		</p>

		<form method="post" enctype="multipart/form-data">
			<?php wp_nonce_field( 'rri_import', 'rri_import_nonce' ); ?>
			<input type="file" name="rri_import_file" id="rri-import-file"/>
			<?php submit_button( esc_html_x( 'Import Rich Reviews', 'button', 'rich-reviews-importer' ) ); ?>
		</form>

		<?php if ( ! empty( $rri_import_results ) ) : ?>
			<p id="rri-import-results">
				<?php echo wp_kses_post( $rri_import_results ); ?>
			</p>
			<br/>
		<?php endif; ?>

	</div>

	<?php

}

/**
 * Have import results to show?
 *
 * @since 0.3
 * @global string $rri_import_results
 * @return bool True if have import results to show
 */
function rri_have_import_results() {

	global $rri_import_results;

	if ( ! empty( $rri_import_results ) ) {
		return true;
	}

	return false;

}

/**
 * Show import results
 *
 * This is shown in place of import/export page's regular content.
 *
 * @since 0.3
 * @global string $rri_import_results
 */
function rri_show_import_results() {

	global $rri_import_results;

	?>

	<h3 class="title"><?php echo esc_html_x( 'Import Results', 'heading', 'rich-reviews-importer' ); ?></h3>

	<p>
		<?php
		printf(
			wp_kses(
				/* translators: %1$s is URL for rich reviews screen, %2$s is URL to go back */
				__( 'You can manage your <a href="%1$s">Reviews</a> or <a href="%2$s">Go Back</a>.', 'rich-reviews-importer' ),
				array(
					'a' => array(
						'href' => array(),
					),
				)
			),
			esc_url( admin_url( 'admin.php?page=fp_admin_approved_reviews_page' ) ),
			esc_url( admin_url( basename( $_SERVER['PHP_SELF'] ) . '?page=' . $_GET['page'] ) )
		);
		?>
	</p>

	<table id="rri-import-results">

		<?php
		// Loop reviews.
		$results = $rri_import_results;
		foreach ( $results as $review ) :
		?>

			<tr class="rri-import-results-review">
				<td class="rri-import-results-review-name">
					<?php
					echo esc_html( $review['name'] );
					?>
				</td>
				<td class="rri-import-results-review-title">
					<?php
					echo esc_html( $review['title'] );
					?>
				</td>
				<td class="rri-import-results-review-message rri-import-results-message rri-import-results-message-<?php echo esc_attr( $review['message_type'] ); ?>">
					<?php
					echo esc_html( $review['message'] );
					?>
				</td>
				<td class="rri-import-results-review-insert">
					<?php
					echo esc_html( $review['insert'] );
					?>
				</td>
			</tr>

		<?php endforeach; ?>

	</table>
	<?php
}
