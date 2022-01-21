<?php
/**
 * This file includes code for the Spotibox Settings and Authorizing.
 *
 * @package Spotibox
 */

// Check that the class exists before trying to use it.
if ( ! class_exists( 'SpotiboxAdmin' ) ) {
	/**
	 * Spotibox Admin Class.
	 */
	class SpotiboxAdmin {
		/**
		 * The Spotify API Class.
		 *
		 * @var mixed[] $spotify
		 */
		public $spotify;

		/**
		 * The Spotify API session.
		 *
		 * @var mixed[] $spotify
		 */
		public $session;

		/**
		 * Class constructor.
		 */
		public function __construct() {
			// Initiate Spotify Class.
			$session       = new SpotiboxSession();
			$this->session = $session->get_session();
			$this->spotify = $session->get_api();

			// Setup menus.
			add_action(
				'admin_menu',
				array( $this, 'setup_menus' )
			);

			// Setup settings.
			add_action(
				'admin_init',
				array( $this, 'setup_settings' )
			);
		}

		/**
		 * Custom Spotufy menus.
		 */
		public function setup_menus() {
			// Spotibux settings page.
			add_menu_page(
				'Spotibox Settings',
				'Spotibox',
				'manage_options',
				'spotibox',
				array( $this, 'spotibox_admin_page' )
			);

			// Hidden callback page.
			add_submenu_page(
				null,
				'Spotibox Callback Page',
				'Spotibox Callback Page',
				'manage_options',
				'spotibox-callback',
				array( $this, 'spotibox_callback' ),
			);
		}

		/**
		 * Spotibox Admin Page Content.
		 */
		public function spotibox_admin_page() {
			?>
			<div class="wrap">
				<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
					<form action="options.php" method="post">
					<?php
					settings_fields( 'spotibox_options' );
					do_settings_sections( __FILE__ );
					submit_button( 'Save Settings' );
					?>
					<?php $this->spotibox_authorize_button(); ?>
				</form>
			</div>
			<?php
		}

		/**
		 * Custom option and settings
		 */
		public function setup_settings() {
			register_setting(
				'spotibox_options',
				'spotibox_options'
			);
			add_settings_section(
				'main_section',
				'Main Settings',
				array( $this, 'spotibox_callback_url_text' ),
				__FILE__
			);
			add_settings_field(
				'client_id',
				'Client ID',
				array( $this, 'spotibox_string_field' ),
				__FILE__,
				'main_section',
				array( 'client_id' )
			);
			add_settings_field(
				'client_secret',
				'Client Secret',
				array( $this, 'spotibox_string_field' ),
				__FILE__,
				'main_section',
				array( 'client_secret' )
			);
			add_settings_field(
				'playlist',
				'Playlist',
				array( $this, 'spotibox_playlist_field' ),
				__FILE__,
				'main_section',
				array( 'playlist' )
			);
			add_settings_field(
				'device',
				'Device',
				array( $this, 'spotibox_device_field' ),
				__FILE__,
				'main_section',
				array( 'device' )
			);
			add_settings_section(
				'test_section',
				'Test Data',
				array( $this, 'spotibox_test_data' ),
				__FILE__
			);
		}

		/**
		 * Callback code for Spotibox.
		 */
		public function spotibox_callback() {
			$auth    = get_option( 'spotibox_auth' );
			$options = get_option( 'spotibox_options' );
			$session = new SpotifyWebAPI\Session(
				$options['client_id'],
				$options['client_secret'],
				get_admin_url( null, 'admin.php?page=spotibox-callback' )
			);

			// Request a access token using the code from Spotify.
			$session->requestAccessToken( $_GET['code'] );

			$access_token                 = $session->getAccessToken();
			$refresh_token                = $session->getRefreshToken();
			$auth['access_token']         = $access_token;
			$auth['refresh_access_token'] = $refresh_token;

			// Save options.
			update_option( 'spotibox_auth', $auth );
			?>
			<div class="wrap">
				<h1>Laddar...</h1>
			</div>
			<?php
		}

		/**
		 * Spotibox wellcome text.
		 */
		public function spotibox_callback_url_text() {
			?>
			<p><strong>Copy this callback uri to your Spotify App settings:</strong><p/>
			<code><?php echo esc_url( get_admin_url( null, 'admin.php?page=spotibox-callback' ) ); ?></code>
			<?php
		}

		/**
		 * Spotibox Text Input.
		 *
		 * @param mixed[] $args Arguments.
		 */
		public function spotibox_string_field( $args ) {
			$options      = get_option( 'spotibox_options' );
			$allowed_html = array(
				'input' => array(
					'id'    => array(),
					'name'  => array(),
					'size'  => array(),
					'type'  => array(),
					'value' => array(),
				),
			);
			echo wp_kses( "<input id='spotibox_{$args[0]}' name='spotibox_options[{$args[0]}]' size='40' type='text' value='{$options[$args[0]]}' />", $allowed_html );
		}

		/**
		 * Spotibox Playlist Select.
		 *
		 * @param mixed[] $args Arguments.
		 */
		public function spotibox_playlist_field( $args ) {
			$options      = get_option( 'spotibox_options' );
			$allowed_html = array(
				'select' => array(
					'id'   => array(),
					'name' => array(),
				),
				'option' => array(
					'value' => array(),
				),
			);

			if ( $this->spotify ) {
				try {
					$me        = $this->spotify->me();
					$playlists = $this->spotify->getUserPlaylists( $me->id, array( 'limit' => 5 ) );

					// Render Select input for all playlists.
					if ( $playlists->items ) {
						echo wp_kses( "<select id='spotibox_{$args[0]}' name='spotibox_options[{$args[0]}]'>", $allowed_html );
						foreach ( $playlists->items as $item ) {
							?>
							<option value="<?php echo esc_attr( $item->uri ); ?>" <?php echo ( $item->uri === $options['playlist'] ) ? 'selected' : ''; ?>><?php echo esc_html( $item->name ); ?></option>
							<?php
						}
						echo '</select>';
					}
				} catch ( Exception $ex ) {
					print_r( $ex, 1 );
				}
			}
		}

		/**
		 * Spotibox Playlist Select.
		 *
		 * @param mixed[] $args Arguments.
		 */
		public function spotibox_device_field( $args ) {
			$options      = get_option( 'spotibox_options' );
			$allowed_html = array(
				'select' => array(
					'id'   => array(),
					'name' => array(),
				),
				'option' => array(
					'value' => array(),
				),
			);

			if ( $this->spotify ) {
				try {
					$devices = $this->spotify->getMyDevices();

					// Render Select input for all playlists.
					if ( $devices ) {
						echo wp_kses( "<select id='spotibox_" . $args[0] . "' name='spotibox_options[" . $args[0] . "]'>", $allowed_html );
						foreach ( $devices->devices as $item ) {
							?>
							<option value="<?php echo esc_attr( $item->id ); ?>" <?php echo ( $item->id === $options['device'] ) ? 'selected' : ''; ?>><?php echo esc_html( $item->name ); ?></option>
							<?php
						}
						echo '</select>';
					}
				} catch ( Exception $ex ) {
					print_r( $ex, 1 );
				}
			}
		}

		/**
		 * Spotibox Static Text Input.
		 *
		 * @param mixed[] $args Arguments.
		 */
		public function spotibox_static_field( $args ) {
			$options      = get_option( 'spotibox_options' );
			$allowed_html = array(
				'input' => array(
					'id'    => array(),
					'name'  => array(),
					'size'  => array(),
					'type'  => array(),
					'value' => array(),
				),
			);

			echo wp_kses( "<input id='spotibox_" . $args[0] . "' name='spotibox_options[" . $args[0] . "]' size='40' type='text' value='{$options[$args[0]]}' disabled />", $allowed_html );
		}

		/**
		 * Spotibox Authorize Button.
		 */
		public function spotibox_authorize_button() {
			$options      = get_option( 'spotibox_options' );
			$allowed_html = array(
				'a' => array(
					'href'  => array(),
					'class' => array(),
				),
			);

			if ( $this->spotify ) {
				try {
					$options = array(
						'scope' => array(
							'user-read-playback-state',
							'user-modify-playback-state',
							'user-read-private',
							'user-read-recently-played',
							'playlist-modify-private',
						),
						'state' => $state,
					);
					$url     = $this->session->getAuthorizeUrl( $options );
					echo wp_kses( "<a href='{$url}' class='button'>Authorize</a>", $allowed_html );
				} catch ( Exception $ex ) {
					print_r( $ex, 1 );
				}
			}
		}

		/**
		 * Spotibox Test Data.
		 */
		public function spotibox_test_data() {
			$options = get_option( 'spotibox_options' );
			if ( $this->spotify ) {
				try {
					// It's now possible to request data about the currently authenticated user.
					$devices = $this->spotify->getMyDevices();

					echo '<code>';
					var_dump( $devices );
					echo '</code>';
				} catch ( Exception $ex ) {
					print_r( $ex, 1 );
				}
			}
		}
	}

	$spotibox_admin = new SpotiboxAdmin();
}
