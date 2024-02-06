<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://r3blcreative.com
 * @since             1.0.0
 * @package           R3bl_Wp_Reset
 *
 * @wordpress-plugin
 * Plugin Name:       R3BL WP Reset
 * Plugin URI:        https://r3blcreative.com
 * Description:       This plugin handles "resetting" WordPress. It disables things like the media library image sizes, JQuery, and other items that are basically useless for R3BL Creative development.
 * Version:           1.0.0
 * Author:            R3BL Creative - James Cook
 * Author URI:        https://r3blcreative.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       r3bl-wp-reset
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define('R3BL_WP_RESET_VERSION', '1.0.0');

if (!class_exists('R3BL_WP_RESET')) {
	class R3BL_WP_RESET {
		public function __construct() {
			$this->actions();
			$this->filters();
		}



		private function actions() {
			//
			add_action('after_setup_theme', [$this, 'supports']);
			add_action('wp_enqueue_scripts', [$this, 'dequeue'], 100);
			add_action('send_headers', [$this, 'send_headers']);
			// add_action('welcome_panel', [$this, 'welcome_panel']);
			// add_action('all_admin_notices', [$this, 'show_everyone_welcome']);

			//
			add_action('init', [$this, 'remove_all_image_sizes']);

			// Disable comments completely
			// add_action('wp_dashboard_setup', [$this, 'remove_dashboard_widgets']);
			add_action('admin_menu', [$this, 'remove_admin_menus']);
			add_action('init', [$this, 'remove_comment_support'], 100);
			add_action('wp_before_admin_bar_render', [$this, 'remove_admin_bar_items']);

			//
			remove_action('wp_head', 'print_emoji_detection_script', 7);
			remove_action('admin_print_scripts', 'print_emoji_detection_script');
			remove_action('wp_print_styles', 'print_emoji_styles');
			remove_action('admin_print_styles', 'print_emoji_styles');
			remove_action('wp_body_open', 'wp_global_styles_render_svg_filters');
			remove_action('wp_body_open', 'gutenberg_global_styles_render_svg_filters');
		}



		private function filters() {
			//
			add_filter('tiny_mce_plugins', [$this, 'disable_emojis_tinymce']);
			add_filter('show_admin_bar', '__return_false');
			add_filter('jpeg_quality', function ($arg) {
				return 100;
			});
			add_filter('intermediate_image_sizes_advanced', [$this, 'remove_image_sizes'], 10, 2);

			//
			remove_filter('the_content_feed', 'wp_staticize_emoji');
			remove_filter('comment_text_rss', 'wp_staticize_emoji');
			remove_filter('wp_mail', 'wp_staticize_emoji_for_email');

			// Admin Columns Pro
			// add_filter('acp/storage/file/directory/writable', '__return_true');
			// add_filter('acp/storage/file/directory', function () {
			// 	return get_template_directory() . '/acp-php';
			// });
		}



		public function supports() {
			//
			remove_theme_support('post-formats');
			remove_theme_support('admin-bar');
			remove_theme_support('widgets');

			//
			add_theme_support('automatic-feed-links');
			add_theme_support('html5');
			add_theme_support('title-tag');
			add_theme_support('post-thumbnails');
			add_theme_support('dark-editor-style');
			add_theme_support('responsive-embeds');

			//
			set_post_thumbnail_size(1600, 9999);

			//
			add_post_type_support('page', 'excerpt');
		}




		public function dequeue() {
			//
			wp_dequeue_style('tmm');
			wp_dequeue_style('wp-block-library');
			wp_dequeue_style('wp-block-library-theme');
		}



		public function send_headers() {
			// Force HTTPS and convert any HTTP to HTTPS
			header('Strict-Transport-Security: max-age=10886400');
		}



		public function disable_emojis_tinymce($plugins) {
			if (is_array($plugins)) {
				return array_diff($plugins, ['wpemoji']);
			} else {
				return [];
			}
		}



		public function remove_image_sizes($sizes, $metadata) {
			return [];
		}
		public function remove_all_image_sizes() {
			foreach (get_intermediate_image_sizes() as $size) {
				remove_image_size($size);
			}
		}



		public function remove_admin_menus() {
			//
			remove_menu_page('edit-comments.php');

			//
			remove_submenu_page('tools.php', 'site-health.php');
			remove_submenu_page('tools.php', 'export-personal-data.php');
			remove_submenu_page('tools.php', 'erase-personal-data.php');

			//
			// remove_submenu_page('options-general.php', 'options-permalink.php');
			remove_submenu_page('options-general.php', 'options-discussion.php');

			//
			$customizer_url = add_query_arg('return', urlencode(remove_query_arg(wp_removable_query_args(), wp_unslash($_SERVER['REQUEST_URI']))), 'customize.php');
			remove_submenu_page('themes.php', $customizer_url);
		}



		public function remove_comment_support() {
			remove_post_type_support('post', 'comments');
			remove_post_type_support('page', 'comments');
		}



		public function remove_admin_bar_items() {
			global $wp_admin_bar;
			$wp_admin_bar->remove_menu('comments');
		}



		public function remove_dashboard_widgets() {
			remove_action('welcome_panel', 'wp_welcome_panel');

			remove_meta_box('dashboard_site_health', 'dashboard', 'normal');
			remove_meta_box('dashboard_activity', 'dashboard', 'normal');
			remove_meta_box('dashboard_right_now', 'dashboard', 'normal');
			remove_meta_box('dashboard_primary', 'dashboard', 'side');
			remove_meta_box('dashboard_secondary', 'dashboard', 'side');
			remove_meta_box('dashboard_quick_press', 'dashboard', 'side');
			remove_meta_box('rg_forms_dashboard', 'dashboard', 'side');
		}



		public function welcome_panel() {
			wp_enqueue_style('css-global', get_template_directory_uri() . '/build/css/index.css', [], filemtime(get_template_directory() . '/build/css/index.css'), 'all');

			get_template_part('templates/welcome-panel');
		}



		public function show_everyone_welcome() {
			if (get_current_screen()->id === 'dashboard') {
				add_filter('get_user_metadata', [$this, 'always_show_welcome'], 20, 4);
				add_filter('user_has_cap', [$this, 'show_welcome_caps']);
			}
		}



		public function always_show_welcome($return, $objectId, $metaKey, $single) {
			// Instantly remove to avoid conflicts later on
			remove_filter(current_filter(), [$this, __FUNCTION__]);

			// Only for the current user
			if ($objectId !== wp_get_current_user()->id) {
				return $return;
			}

			// Show welcome panel always
			if ($metaKey === 'show_welcome_panel') {
				return TRUE;
			}

			return $return;
		}



		public function show_welcome_caps($capabilities) {
			$capabilities['edit_theme_options'] = 1;
			return $capabilities;
		}
	}

	$R3BL_WP_RESET = new R3BL_WP_RESET();
}
