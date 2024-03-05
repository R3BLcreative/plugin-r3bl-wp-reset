<?php

/**
 * The plugin class that handles one-click & automatic updates.
 *
 * This is used to check for plugin updates via the GitHub repo.
 *
 */
class WP_Reset_Plugin_Updater {
	/**
	 * The plugin name/slug
	 *
	 * @var string
	 */
	public $plugin_name;

	/**
	 * The current plugin version
	 *
	 * @var string
	 */
	public $version;

	/**
	 * The reference key for the plugin's cache
	 *
	 * @var string
	 */
	public $cache_key;

	/**
	 * Whether or not to enable the cache
	 *
	 * @var bool
	 */
	public $cache_allowed;

	/**
	 * Instantiator method
	 */
	public function __construct() {
		$this->version = R3BL_WP_RESET_VERSION;
		$this->plugin_name = 'r3bl-wp-reset';
		$this->cache_key = $this->plugin_name . '_updater';
		$this->cache_allowed = false;

		add_filter('plugins_api', [$this, 'info'], 20, 3);
		add_filter('site_transient_update_plugins', [$this, 'update']);
		add_action('upgrader_process_complete', [$this, 'purge'], 10, 2);
	}

	/**
	 * Get's the plugin update manifest file (info.json) from remote server
	 *
	 * @return object|array $remote
	 */
	public function request() {
		$remote = get_transient($this->cache_key); // Uninstall should handle transient removal

		if (false === $remote || !$this->cache_allowed) {
			$cache_buster = rand(100, 5000);
			$remote = wp_remote_get(
				'https://r3blcreative.com/r3bl-updates/plugins/r3bl-wp-reset/info.json?v=' . $cache_buster,
				[
					'timeout' => 10,
					'headers' => [
						'Accept' => 'application/json'
					]
				]
			);

			if (is_wp_error($remote) || 200 !== wp_remote_retrieve_response_code($remote) || empty(wp_remote_retrieve_body($remote))) {
				return false;
			}

			set_transient($this->cache_key, $remote, DAY_IN_SECONDS);
		}

		$remote = json_decode(wp_remote_retrieve_body($remote));

		return $remote;
	}

	/**
	 * Gets the plugin update manifest details for display in WordPress.
	 *
	 * @param  false|object|array $response
	 * @param  string $action
	 * @param  object|array $args
	 * 
	 * @return object|array $response
	 */
	function info($response, $action, $args) {
		// do nothing if you're not getting plugin information right now
		if ('plugin_information' !== $action) {
			return $response;
		}

		// do nothing if it is not our plugin
		if (empty($args->slug) || $this->plugin_name !== $args->slug) {
			return $response;
		}

		// get updates
		$remote = $this->request();

		if (!$remote) {
			return $response;
		}

		$response = new \stdClass();

		$response->name           = $remote->name;
		$response->slug           = $remote->slug;
		$response->version        = $remote->version;
		$response->tested         = $remote->tested;
		$response->requires       = $remote->requires;
		$response->author         = $remote->author;
		$response->author_profile = $remote->author_profile;
		$response->donate_link    = $remote->donate_link;
		$response->homepage       = $remote->homepage;
		$response->download_link  = $remote->download_url;
		$response->trunk          = $remote->download_url;
		$response->requires_php   = $remote->requires_php;
		$response->last_updated   = $remote->last_updated;

		$response->sections = [
			'description'  => $remote->sections->description,
			'installation' => $remote->sections->installation,
			'changelog'    => $remote->sections->changelog
		];

		if (!empty($remote->banners)) {
			$response->banners = [
				'low'  => $remote->banners->low,
				'high' => $remote->banners->high
			];
		}

		return $response;
	}

	/**
	 * Runs the plugin update and installs new plugin version
	 *
	 * @param  object|array $transient
	 * 
	 * @return object|array $transient
	 */
	public function update($transient) {
		if (empty($transient->checked)) {
			return $transient;
		}

		$remote = $this->request();

		if ($remote && version_compare($this->version, $remote->version, '<') && version_compare($remote->requires, get_bloginfo('version'), '<=') && version_compare($remote->requires_php, PHP_VERSION, '<')) {
			$response = new \stdClass();
			$response->slug					= $this->plugin_name;
			$response->plugin				= $this->plugin_name . '/' . $this->plugin_name . '.php';
			$response->new_version	= $remote->version;
			$response->tested				= $remote->tested;
			$response->package			= $remote->download_url;

			$transient->response[$response->plugin] = $response;
		}

		return $transient;
	}

	/**
	 * Cleans out the cache after a new plugin version install
	 *
	 * @param  WP_Upgrader $upgrader
	 * @param  array $options
	 * 
	 * @return void
	 */
	public function purge($upgrader, $options) {
		if ($this->cache_allowed && 'update' === $options['action'] && 'plugin' === $options['type']) {
			delete_transient($this->cache_key);
		}
	}
}

$WP_Reset_Plugin_Updater = new WP_Reset_Plugin_Updater();
