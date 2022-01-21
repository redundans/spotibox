<?php
/**
 * This file includes code for the Spotibox REST API.
 *
 * @package Spotibox
 */

// Check that the class exists before trying to use it.
if ( ! class_exists( 'SpotiboxRest' ) ) {
	/**
	 * Spotibox REST API Class.
	 */
	class SpotiboxRest {
		/**
		 * The Spotify API Class.
		 *
		 * @var mixed[] $spotify
		 */
		public static $spotify;

		/**
		 * Class constructor.
		 */
		public function __construct() {
			add_action( 'rest_api_init', array( $this, 'setup_rest_endpoints' ) );

			// Create Spotify Session and API.
			$session       = new SpotiboxSession();
			$this->spotify = $session->get_api();
		}

		/**
		 * Set up rest api endpoints.
		 */
		public function setup_rest_endpoints() {
			register_rest_route(
				'spotibox/v1',
				'/playsong',
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'play_song' ),
					'permission_callback' => '__return_true',
				)
			);

			register_rest_route(
				'spotibox/v1',
				'/search',
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'search_track' ),
					'permission_callback' => '__return_true',
				)
			);

			register_rest_route(
				'spotibox/v1',
				'/nowplaying',
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'now_playing' ),
					'permission_callback' => '__return_true',
				)
			);

			register_rest_route(
				'spotibox/v1',
				'/playlists',
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'playlists' ),
					'permission_callback' => '__return_true',
				)
			);

			register_rest_route(
				'spotibox/v1',
				'/playlist',
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'playlist' ),
					'permission_callback' => '__return_true',
				)
			);

			register_rest_route(
				'spotibox/v1',
				'/addtoplaylist',
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'add_to_playlist' ),
					'permission_callback' => '__return_true',
				)
			);

			register_rest_route(
				'spotibox/v1',
				'/history',
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'history' ),
					'permission_callback' => '__return_true',
				)
			);
		}

		/**
		 * Get recent tracks.
		 */
		public function history() {
			// If cached data exist return it.
			if ( get_transient( 'spotibox_history' ) ) {
				return get_transient( 'spotibox_history' );
			}

			// Fetch recent Playback and store it in cache.
			$history = $this->spotify->getMyRecentTracks(
				array(
					'type'  => 'track',
					'limit' => 1,
				)
			);
			set_transient( 'spotibox_history', $history, 5 );

			return $history;
		}

		/**
		 * Get current playback info.
		 */
		public function now_playing() {
			$nowplaying = null;
			$options = get_option( 'spotibox_options' );

			// If cached data exist return it.
			if ( get_transient( 'spotibox_nowplaying' ) ) {
				$nowplaying = get_transient( 'spotibox_nowplaying' );
			}

			// Fetch Current Playback and store it in cache.
			$nowplaying = $this->spotify->getMyCurrentPlaybackInfo();

			if ( $nowplaying->context->uri !== $options['playlist'] ) {
				$nowplaying = null;
			}

			set_transient( 'spotibox_nowplaying', $nowplaying, 5 );

			// Return Current Playback.
			return $nowplaying;
		}
		/**
		 * Update playlist.
		 *
		 * @param mixed[] $playlist The playlist object.
		 */
		public function update_playlist( $playlist ) {
			$nowplaying = $this->now_playing();

			$index = false;
			foreach ( $playlist->tracks->items as $key => $item ) {
				if ( $item->track->id === $nowplaying->item->id ) {
					$index = $key;
				}
			}
			if ( false !== $index ) {
				$track_options = array(
					'positions' => range( 0, $index ),
				);
				$result        = $this->spotify->deletePlaylistTracks( $playlist->id, $track_options, $playlist->snapshot_id );
			}
		}

		/**
		 * Get the user playlists.
		 */
		public function playlists() {
			// If cached data exist return it.
			if ( get_transient( 'spotibox_playlists' ) ) {
				return get_transient( 'spotibox_playlists' );
			}

			// Fetch Playlist and store it in cache.
			$me        = $this->spotify->me();
			$playlists = $this->spotify->getUserPlaylists( $me->id, array( 'limit' => 5 ) );
			set_transient( 'spotibox_playlists', $playlists, 5 );

			return $playlists;
		}

		/**
		 * Get the chosen playlist.
		 */
		public function playlist() {
			$options = get_option( 'spotibox_options' );

			// If cached data exist return it.
			if ( get_transient( 'spotibox_playlist' ) ) {
				return get_transient( 'spotibox_playlist' );
			}

			// Fetch Playlist and store it in cache.
			if ( isset( $options['playlist'] ) ) {
				$playlist = $this->spotify->getPlaylist( $options['playlist'] );
				set_transient( 'spotibox_playlist', $playlist, 5 );

				// Update playlist.
				$this->update_playlist( $playlist );

				return $playlist;
			}

			// Return  null if Option Playlist is not set.
			return wp_json_encode( array() );
		}

		/**
		 * Add to chisen playlist.
		 *
		 * @param WP_REST_Request $request The request object.
		 */
		public function add_to_playlist( WP_REST_Request $request ) {
			$options = get_option( 'spotibox_options' );

			if ( $options['playlist'] ) {
				// Get sent data and set default value.
				$params = wp_parse_args(
					$request->get_params(),
					array(
						'cart' => '',
					)
				);

				$tracks = array_column( $params['cart'], 'id' );

				$this->spotify->addPlaylistTracks( $options['playlist'], $tracks );
			}
			// Return  null if Option Playlist is not set.
			return true;
		}

		/**
		 * Add to chisen playlist.
		 *
		 * @param WP_REST_Request $request The request object.
		 */
		public function play_song( WP_REST_Request $request ) {
			$devices = $this->spotify->getMyDevices();

			// Get sent data and set default value.
			$params = wp_parse_args(
				$request->get_params(),
				array(
					'song' => '',
				)
			);

			// Tell Spotify API to play.
			$this->spotify->play(
				$devices->devices[0]->id,
				array(
					'uris' => array( $params['song'] ),
				)
			);

			return wp_json_encode( array() );
		}

		/**
		 * Search for tracks.
		 *
		 * @param WP_REST_Request $request The request object.
		 */
		public function search_track( WP_REST_Request $request ) {

			// Get sent data and set default value.
			$params = wp_parse_args(
				$request->get_params(),
				array(
					'search' => '',
				)
			);

			// Return result from Spotify API.
			return $this->spotify->search( $params['search'], array( 'track' ) );
		}
	}

	// Initiate class.
	$spotibox_rest = new SpotiboxRest();
}
