<?php
/**
 * GitHub Theme Updater for Franco Deportes Theme
 *
 * Checks GitHub repository releases/tags for theme updates
 * and integrates natively with WordPress Theme Update API.
 *
 * @package FrancoTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Franco_Theme_Updater {

	/**
	 * GitHub repository in 'username/repository' format.
	 *
	 * @var string
	 */
	private $repository = 'lucassebastianfiorio/franco-theme';

	/**
	 * Theme stylesheet slug.
	 *
	 * @var string
	 */
	private $theme_slug = 'franco-theme';

	/**
	 * Current theme version.
	 *
	 * @var string
	 */
	private $current_version = '';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$theme                 = wp_get_theme( $this->theme_slug );
		$this->current_version = $theme->exists() ? $theme->get( 'Version' ) : FRANCO_THEME_VERSION;

		// Inject GitHub updates into WordPress theme update transient
		add_filter( 'pre_set_site_transient_update_themes', array( $this, 'check_for_update' ) );

		// Provide theme details popup when clicking "View version details" in WP Admin
		add_filter( 'themes_api', array( $this, 'theme_popup_details' ), 20, 3 );

		// Rename unpacked GitHub archive folder (e.g., repo-tag-sha -> franco-theme)
		add_filter( 'upgrader_source_selection', array( $this, 'fix_unpacked_folder_name' ), 10, 4 );

		// Clear cached GitHub release data when WordPress updates cache
		add_action( 'delete_site_transient_update_themes', array( $this, 'clear_release_cache' ) );
	}

	/**
	 * Fetch latest release from GitHub API.
	 *
	 * @return object|false
	 */
	public function get_github_release() {
		// Force refresh when user clicks "Check Again" in wp-admin/update-core.php
		$force_check = ( isset( $_GET['force-check'] ) && '1' === $_GET['force-check'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$transient_key = 'franco_github_theme_release';

		if ( ! $force_check ) {
			$cached = get_site_transient( $transient_key );
			if ( false !== $cached ) {
				return $cached;
			}
		}

		$api_url = sprintf( 'https://api.github.com/repos/%s/releases/latest', $this->repository );

		$response = wp_remote_get(
			$api_url,
			array(
				'timeout' => 10,
				'headers' => array(
					'Accept'     => 'application/vnd.github.v3+json',
					'User-Agent' => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . home_url(),
				),
			)
		);

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			// Fallback: check tags if no formal release is published yet
			$tags_url = sprintf( 'https://api.github.com/repos/%s/tags', $this->repository );
			$tags_response = wp_remote_get(
				$tags_url,
				array(
					'timeout' => 10,
					'headers' => array(
						'Accept'     => 'application/vnd.github.v3+json',
						'User-Agent' => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . home_url(),
					),
				)
			);

			if ( ! is_wp_error( $tags_response ) && 200 === wp_remote_retrieve_response_code( $tags_response ) ) {
				$tags = json_decode( wp_remote_retrieve_body( $tags_response ) );
				if ( ! empty( $tags ) && is_array( $tags ) ) {
					$latest_tag = $tags[0];
					$pseudo_release = (object) array(
						'tag_name'     => $latest_tag->name,
						'name'         => $latest_tag->name,
						'body'         => __( 'Actualización del tema Franco Deportes disponible en GitHub.', 'franco-theme' ),
						'html_url'     => sprintf( 'https://github.com/%s/releases/tag/%s', $this->repository, $latest_tag->name ),
						'zipball_url'  => $latest_tag->zipball_url,
						'assets'       => array(),
						'published_at' => gmdate( 'Y-m-d H:i:s' ),
					);
					set_site_transient( $transient_key, $pseudo_release, 6 * HOUR_IN_SECONDS );
					return $pseudo_release;
				}
			}

			// Cache failure for 1 hour to avoid flooding GitHub API
			set_site_transient( $transient_key, null, 1 * HOUR_IN_SECONDS );
			return false;
		}

		$release = json_decode( wp_remote_retrieve_body( $response ) );
		if ( empty( $release ) || ! isset( $release->tag_name ) ) {
			set_site_transient( $transient_key, null, 1 * HOUR_IN_SECONDS );
			return false;
		}

		// Cache valid release for 6 hours
		set_site_transient( $transient_key, $release, 6 * HOUR_IN_SECONDS );
		return $release;
	}

	/**
	 * Check GitHub release against current theme version and inject update if available.
	 *
	 * @param object $transient Update themes site transient.
	 * @return object
	 */
	public function check_for_update( $transient ) {
		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		$release = $this->get_github_release();
		if ( ! $release || empty( $release->tag_name ) ) {
			return $transient;
		}

		$remote_version = ltrim( (string) $release->tag_name, 'vV' );
		$current_ver    = ltrim( (string) $this->current_version, 'vV' );

		// Determine package download URL (prefer attached .zip asset, fallback to zipball)
		$download_url = $release->zipball_url;
		if ( ! empty( $release->assets ) && is_array( $release->assets ) ) {
			foreach ( $release->assets as $asset ) {
				if ( isset( $asset->name ) && substr( $asset->name, -4 ) === '.zip' && ! empty( $asset->browser_download_url ) ) {
					$download_url = $asset->browser_download_url;
					break;
				}
			}
		}

		$update_data = array(
			'theme'        => $this->theme_slug,
			'new_version'  => $remote_version,
			'url'          => ! empty( $release->html_url ) ? $release->html_url : sprintf( 'https://github.com/%s', $this->repository ),
			'package'      => $download_url,
			'requires'     => '6.0',
			'requires_php' => '7.4',
		);

		if ( version_compare( $remote_version, $current_ver, '>' ) ) {
			$transient->response[ $this->theme_slug ] = $update_data;
		} else {
			$transient->no_update[ $this->theme_slug ] = $update_data;
		}

		return $transient;
	}

	/**
	 * Provide modal details when administrator clicks "View version details".
	 *
	 * @param false|object|array $result Result object or false.
	 * @param string             $action Action name.
	 * @param object             $args   Arguments.
	 * @return false|object
	 */
	public function theme_popup_details( $result, $action, $args ) {
		if ( 'theme_information' !== $action || ! isset( $args->slug ) || $args->slug !== $this->theme_slug ) {
			return $result;
		}

		$release = $this->get_github_release();
		if ( ! $release ) {
			return $result;
		}

		$remote_version = ltrim( (string) $release->tag_name, 'vV' );

		$res = (object) array(
			'name'          => 'Franco Deportes Theme',
			'slug'          => $this->theme_slug,
			'version'       => $remote_version,
			'author'        => '<a href="https://github.com/lucassebastianfiorio">Lucas Fiorio</a>',
			'homepage'      => sprintf( 'https://github.com/%s', $this->repository ),
			'requires'      => '6.0',
			'requires_php'  => '7.4',
			'download_link' => $release->zipball_url,
			'sections'      => array(
				'description' => esc_html__( 'Tema oficial de Franco Deportes con soporte nativo de WooCommerce.', 'franco-theme' ),
				'changelog'   => ! empty( $release->body ) ? nl2br( esc_html( $release->body ) ) : esc_html__( 'Mejoras y correcciones generales.', 'franco-theme' ),
			),
		);

		return $res;
	}

	/**
	 * Fix folder name after WordPress unzips GitHub repository archive.
	 * GitHub archives unpack to a directory named '{user}-{repo}-{commit_hash}'.
	 * We rename it to 'franco-theme' so WordPress replaces the existing theme cleanly.
	 *
	 * @param string      $source        Path to the unpacked source directory.
	 * @param string      $remote_source Remote source path.
	 * @param WP_Upgrader $upgrader      Upgrader instance.
	 * @param array|null  $hook_extra    Extra arguments.
	 * @return string|WP_Error
	 */
	public function fix_unpacked_folder_name( $source, $remote_source, $upgrader, $hook_extra = null ) {
		global $wp_filesystem;

		// Only apply to theme upgrades for this theme
		$is_this_theme = false;
		if ( is_array( $hook_extra ) && isset( $hook_extra['theme'] ) && $hook_extra['theme'] === $this->theme_slug ) {
			$is_this_theme = true;
		} elseif ( false !== strpos( $source, 'franco-theme' ) ) {
			$is_this_theme = true;
		}

		if ( ! $is_this_theme ) {
			return $source;
		}

		$correct_source = trailingslashit( $remote_source ) . $this->theme_slug;

		if ( trailingslashit( $source ) === trailingslashit( $correct_source ) ) {
			return $source;
		}

		if ( $wp_filesystem->move( $source, $correct_source, true ) ) {
			return trailingslashit( $correct_source );
		}

		return new WP_Error(
			'franco_theme_rename_failed',
			__( 'No se pudo renombrar la carpeta del tema descargada desde GitHub.', 'franco-theme' )
		);
	}

	/**
	 * Clear cached release transient.
	 */
	public function clear_release_cache() {
		delete_site_transient( 'franco_github_theme_release' );
	}
}

// Initialize updater
new Franco_Theme_Updater();
