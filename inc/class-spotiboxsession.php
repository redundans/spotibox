<?php
/**
 * This file includes code for the Spotibox Web API Session.
 *
 * @package Spotibox
 */

// Check that the class exists before trying to use it.
if ( ! class_exists( 'SpotiboxSession' ) ) {

	/**
	 * Spotibox Session Class.
	 */
	class SpotiboxSession {
		/**
		 * The Spotify API Class.
		 *
		 * @var mixed[] $spotify
		 */
		public $api;

		/**
		 * The Spotify API Session.
		 *
		 * @var mixed[] $spotify
		 */
		public $session;

		/**
		 * Class constructor.
		 */
		public function __construct() {
			$auth          = get_option( 'spotibox_auth' );
			$options       = get_option( 'spotibox_options' );
			$this->session = new SpotifyWebAPI\Session(
				$options['client_id'],
				$options['client_secret'],
				get_admin_url( null, 'admin.php?page=spotibox-callback' )
			);
			try {
				$this->api = new SpotifyWebAPI\SpotifyWebAPI( array( 'auto_refresh' => true ), $this->session );
				if ( ! empty( $auth['refresh_access_token'] ) ) {
					$this->session->refreshAccessToken( $auth['refresh_access_token'] );

					$access_token  = $this->session->getAccessToken();
					$refresh_token = $this->session->getRefreshToken();

					$auth['refresh_access_token'] = $refresh_token;
					update_option( 'spotibox_auth', $auth );
				}
			} catch ( Exception $ex ) {
				print_r( $ex, 1 );
			}
		}

		/**
		 * Return the initiated session.
		 */
		public function get_session() {
			return $this->session;
		}

		/**
		 * Return the initiated api.
		 */
		public function get_api() {
			return $this->api;
		}
	}
}
