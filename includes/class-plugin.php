<?php
/**
 * Main plugin coordinator.
 *
 * @package CitySparkEmailFeed
 */

declare(strict_types=1);

namespace CitySpark\EmailFeed;

if (! defined('ABSPATH')) {
	exit;
}

require_once CITYSPARK_EMAIL_FEED_PATH . 'includes/class-settings.php';
require_once CITYSPARK_EMAIL_FEED_PATH . 'includes/class-feed.php';
require_once CITYSPARK_EMAIL_FEED_PATH . 'includes/class-renderer.php';
require_once CITYSPARK_EMAIL_FEED_PATH . 'includes/class-shortcode.php';
require_once CITYSPARK_EMAIL_FEED_PATH . 'includes/class-noptin.php';

/**
 * Main plugin coordinator.
 */
final class Plugin {
	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Settings service.
	 *
	 * @var Settings
	 */
	private Settings $settings;

	/**
	 * Feed service.
	 *
	 * @var Feed
	 */
	private Feed $feed;

	/**
	 * Renderer service.
	 *
	 * @var Renderer
	 */
	private Renderer $renderer;

	/**
	 * Get the singleton instance.
	 *
	 * @return self
	 */
	public static function instance(): self {
		if (null === self::$instance) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Validate minimum runtime requirements on activation.
	 *
	 * @return void
	 */
	public static function activate(): void {
		global $wp_version;

		if (version_compare(PHP_VERSION, '8.1', '<')) {
			wp_die(
				esc_html__('CitySpark Email Feed requires PHP 8.1 or higher.', 'cityspark-email-feed'),
				esc_html__('Plugin Activation Error', 'cityspark-email-feed'),
				array('back_link' => true)
			);
		}

		if (version_compare((string) $wp_version, '6.8', '<')) {
			wp_die(
				esc_html__('CitySpark Email Feed requires WordPress 6.8 or higher.', 'cityspark-email-feed'),
				esc_html__('Plugin Activation Error', 'cityspark-email-feed'),
				array('back_link' => true)
			);
		}
	}

	/**
	 * Initialize plugin hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		$this->settings = new Settings();
		$this->feed     = new Feed($this->settings);
		$this->renderer = new Renderer();

		$this->settings->register_hooks();

		$shortcode = new Shortcode($this->settings, $this->feed, $this->renderer);
		$shortcode->register_hooks();

		$noptin = new Noptin($this->settings, $this->feed, $this->renderer);
		$noptin->register_hooks();
	}

	/**
	 * Prevent direct construction.
	 */
	private function __construct() {}

	/**
	 * Prevent cloning.
	 *
	 * @return void
	 */
	private function __clone() {}
}
