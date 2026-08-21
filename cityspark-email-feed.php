<?php
/**
 * Plugin Name: CitySpark Email Feed
 * Plugin URI: https://harveyplum.com
 * Description: Embed CitySpark RSS events in Noptin emails and WordPress content using email-safe HTML.
 * Version: 1.0.3
 * Requires at least: 6.8
 * Requires PHP: 8.1
 * Author: Harvey Plum
 * Author URI: https://harveyplum.com
 * GitHub Plugin URI: https://github.com/HarveyPlum/cityspark-email-feed
 * Primary Branch: main
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: cityspark-email-feed
 *
 * @package CitySparkEmailFeed
 */

declare(strict_types=1);

namespace CitySpark\EmailFeed;

if (! defined('ABSPATH')) {
	exit;
}

define('CITYSPARK_EMAIL_FEED_VERSION', '1.0.3');
define('CITYSPARK_EMAIL_FEED_FILE', __FILE__);
define('CITYSPARK_EMAIL_FEED_PATH', plugin_dir_path(__FILE__));
define('CITYSPARK_EMAIL_FEED_URL', plugin_dir_url(__FILE__));

require_once CITYSPARK_EMAIL_FEED_PATH . 'includes/class-plugin.php';

register_activation_hook(
	__FILE__,
	static function (): void {
		Plugin::activate();
	}
);

add_action(
	'plugins_loaded',
	static function (): void {
		Plugin::instance()->init();
	}
);
