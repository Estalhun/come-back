<?php

namespace ComeBack;

defined( 'ABSPATH' ) || exit;

/**
 * Come Back Settings.
 *
 * @since 1.0.0
 */
class Settings {

	/**
	 * Initialize.
	 *
	 * @since 1.0.0
	 */
	public function init() {

		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'save_settings' ) );
		add_filter( 'admin_footer_text', array( $this, 'get_admin_footer' ), 1, 2 );
		add_action( 'admin_print_scripts', array( $this, 'remove_notices' ) );
		add_action( 'come_back_process_smart_tags', [ $this, 'process_smart_tags'] );
	}

	/**
	 * Checks if current screen is Come back! or not.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function is_admin_screen() {

		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';

		return 'settings_page_come-back' === $screen_id;
	}

	/**
	 * Add Come Back submenu under Settings menu.
	 *
	 * @since 1.0.0
	 */
	public function add_settings_page() {

		add_options_page(
			esc_html__( 'Come Back!', 'come-back' ),
			esc_html__( 'Come Back!', 'come-back' ),
			'manage_options',
			'come-back',
			array( $this, 'display' )
		);
	}

	/**
	 * Add contents to the settings page.
	 *
	 * @since 1.0.0
	 */
	public function display() {

		// Return if not Come Back screen.
		if ( ! $this->is_admin_screen() ) {
			return;
		}
		
		$inactivity_period = get_option( 'come_back_inactivity_period', 90 );
		$email_subject     = get_option( 'come_back_email_subject', esc_html__( 'Come Back!', 'come-back' ) );

		$message           = 'We haven\'t seen you in a while. Things are a lot different since the last time you logged into {site_name}. I\'m {name}, CEO of {site_name}. I wanted to send you a note since you have been inactive for a while. You can come back and continue your awesome works at {site_name}.<br/><br/>Please come back!';
		$email_message     = get_option( 'come_back_email_message', $message );

		?>
		<h2><?php esc_html_e( 'General Settings', 'come-back' ); ?></h2><hr/>
		<form method="post">
			<table class="form-table">

				<tr valign="top" class="come-back-inactivity-period">
					<th scope="row"><?php echo esc_html__( 'Send email to user after inactive days:', 'wp-frontend-delete-account' ); ?></th>
						<td>
							<input style="width:auto" type="number" name="come_back_inactivity_period" value="<?php echo $inactivity_period;?>" />
						</td>
				</tr>

				<tr valign="top" class="come-back-email-subject">
					<th scope="row"><?php echo esc_html__( 'Email Subject:', 'come-back' ); ?></th>
						<td>
							<input style="width:auto" type="text" name="come_back_email_subject" value="<?php echo $email_subject;?>" />
						</td>
				</tr>

				<tr valign="top" class="come-back-email-message">
					<th scope="row"><?php echo esc_html__( 'Email Message:', 'wp-frontend-delete-account' ); ?></th>
						<td>
							<?php 

								$editor_id = 'come-back-email-editor';
								$args = array(
								        'media_buttons' => false
									);
								?> 

								<?php wp_editor( $email_message, $editor_id, $args );
							?>
						</td>
						<style>
							#wp-come-back-email-editor-wrap {
								width: 80%;
							}
						</style>
				</tr>

			</table>
			<?php wp_nonce_field( 'come_back_settings', 'come_back_settings_nonce' ); ?>
			<?php submit_button(); ?>
		</form>
		<?php
	}

	/**
	 * Save settings to the database.
	 *
	 * @since 1.0.0
	 */
	public function save_settings() {

		if ( ! isset( $_POST['submit'] ) ) {
			return;
		}

		if (
			! isset( $_POST['come_back_settings_nonce'] ) ||
			! wp_verify_nonce( $_POST['come_back_settings_nonce'], 'come_back_settings' )
		) {
			return;
		}

		$options = array( 'come_back_inactivity_period', 'come_back_email_subject', 'come_back_email_message' );

			foreach ( $options as $option ) {
				if ( isset( $_POST[ $option ] ) ) {
		
					$value = sanitize_text_field( $_POST[ $option ] );
		
					update_option( $option, $value );
				}
			}
	}

	/**
	 * Process smart tags.
	 * 
	 * @since 1.0.0
	 */
	public function process_smart_tags( $content ) {

		$content = str_replace( '{site_name}', get_bloginfo(), $content );

		return $content;
	}

	/**
	 * Display a text to ask users to review the plugin on WP.org.
	 *
	 * @since 1.0.0
	 *
	 * @param string $text Rating Text.
	 *
	 * @return string
	 */
	public function get_admin_footer( $text ) {

		if ( ! $this->is_admin_screen() ) {
			return $text;
		}

		$url = 'https://wordpress.org/support/plugin/come-back/reviews/?filter=5#new-post';

		return sprintf(
			wp_kses( /* translators: %1$s - WP.org link; %2$s - same WP.org link. */
				__( 'Please rate <strong>Come Back</strong> <a href="%1$s" target="_blank" rel="noopener noreferrer">&#9733;&#9733;&#9733;&#9733;&#9733;</a> on <a href="%2$s" target="_blank" rel="noopener noreferrer">WordPress.org</a> to help us spread the word. Thank you from the Come Back team!', 'come-back' ),
				array(
					'strong' => true,
					'a'      => array(
						'href'   => true,
						'target' => true,
						'rel'    => true,
					),
				)
			),
			$url,
			$url
		);
	}

	/**
	 * Removes the admin notices on Come Back settings page.
	 *
	 * @since 1.0.0
	 */
	public function remove_notices() {

		global $wp_filter;

		if ( ! isset( $_REQUEST['page'] ) || 'come-back' !== $_REQUEST['page'] ) {
			return;
		}

		foreach ( array( 'user_admin_notices', 'admin_notices', 'all_admin_notices' ) as $wp_notice ) {
			if ( ! empty( $wp_filter[ $wp_notice ]->callbacks ) && is_array( $wp_filter[ $wp_notice ]->callbacks ) ) {
				foreach ( $wp_filter[ $wp_notice ]->callbacks as $priority => $hooks ) {
					foreach ( $hooks as $name => $arr ) {
						unset( $wp_filter[ $wp_notice ]->callbacks[ $priority ][ $name ] );
					}
				}
			}
		}
	}
}