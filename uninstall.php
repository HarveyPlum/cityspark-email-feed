<?php
/**
 * Uninstall cleanup.
 *
 * @package CitySparkEmailFeed
 */

declare(strict_types=1);

if (! defined('WP_UNINSTALL_PLUGIN')) {
	exit;
}

delete_option('cityspark_email_feed_settings');

global $wpdb;

$transient_like         = $wpdb->esc_like('_transient_cityspark_feed_') . '%';
$transient_timeout_like = $wpdb->esc_like('_transient_timeout_cityspark_feed_') . '%';

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
		$transient_like,
		$transient_timeout_like
	)
);
