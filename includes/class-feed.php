<?php
/**
 * CitySpark RSS fetching and parsing.
 *
 * @package CitySparkEmailFeed
 */

declare(strict_types=1);

namespace CitySpark\EmailFeed;

use SimpleXMLElement;
use Throwable;

if (! defined('ABSPATH')) {
	exit;
}

/**
 * CitySpark RSS fetching and parsing.
 */
final class Feed {
	/**
	 * Settings service.
	 *
	 * @var Settings
	 */
	private Settings $settings;

	/**
	 * Constructor.
	 *
	 * @param Settings $settings Settings service.
	 */
	public function __construct(Settings $settings) {
		$this->settings = $settings;
	}

	/**
	 * Fetch parsed events from a CitySpark RSS feed.
	 *
	 * @param string $url   Feed URL.
	 * @param int    $limit Maximum event count. Zero returns all parsed events.
	 * @return array<int, array<string, string>>
	 */
	public function get_events(string $url, int $limit = 0): array {
		$url = esc_url_raw($url);

		if ('' === $url) {
			return array();
		}

		$transient_key = $this->get_transient_key($url);
		$cached        = get_transient($transient_key);

		if (is_array($cached)) {
			return $this->limit_events($cached, $limit);
		}

		$events = $this->fetch_and_parse($url);
		set_transient($transient_key, $events, $this->get_cache_expiration());

		return $this->limit_events($events, $limit);
	}

	/**
	 * Build the configured transient key for a feed URL.
	 *
	 * @param string $url Feed URL.
	 * @return string
	 */
	private function get_transient_key(string $url): string {
		return 'cityspark_feed_' . md5($url);
	}

	/**
	 * Get cache expiration in seconds.
	 *
	 * @return int
	 */
	private function get_cache_expiration(): int {
		$minutes = absint($this->settings->get('cache_time'));

		return max(1, $minutes) * MINUTE_IN_SECONDS;
	}

	/**
	 * Fetch and parse a feed URL.
	 *
	 * @param string $url Feed URL.
	 * @return array<int, array<string, string>>
	 */
	private function fetch_and_parse(string $url): array {
		$response = wp_safe_remote_get(
			$url,
			array(
				'timeout'     => 15,
				'redirection' => 3,
				'user-agent'  => 'CitySpark Email Feed/' . CITYSPARK_EMAIL_FEED_VERSION . '; ' . home_url('/'),
			)
		);

		if (is_wp_error($response)) {
			return array();
		}

		$status_code = (int) wp_remote_retrieve_response_code($response);

		if ($status_code < 200 || $status_code >= 300) {
			return array();
		}

		$body = wp_remote_retrieve_body($response);

		if (! is_string($body) || '' === trim($body)) {
			return array();
		}

		return $this->parse_xml($body);
	}

	/**
	 * Parse RSS XML into normalized event arrays.
	 *
	 * @param string $xml RSS XML.
	 * @return array<int, array<string, string>>
	 */
	private function parse_xml(string $xml): array {
		if (! class_exists(SimpleXMLElement::class)) {
			return array();
		}

		$previous = libxml_use_internal_errors(true);
		libxml_clear_errors();

		try {
			$feed = simplexml_load_string($xml, SimpleXMLElement::class, LIBXML_NOCDATA);
		} catch (Throwable $throwable) {
			$feed = false;
		}

		libxml_clear_errors();
		libxml_use_internal_errors($previous);

		if (! $feed instanceof SimpleXMLElement || ! isset($feed->channel->item)) {
			return array();
		}

		$events = array();

		foreach ($feed->channel->item as $item) {
			if (! $item instanceof SimpleXMLElement) {
				continue;
			}

			$events[] = $this->parse_item($item);
		}

		return $events;
	}

	/**
	 * Parse one RSS item.
	 *
	 * @param SimpleXMLElement $item RSS item.
	 * @return array<string, string>
	 */
	private function parse_item(SimpleXMLElement $item): array {
		$description = (string) $item->description;
		$details     = $this->parse_description($description);
		$media       = $item->children('media', true);
		$image       = '';

		if (isset($media->content)) {
			$attributes = $media->content->attributes();
			$image      = isset($attributes['url']) ? esc_url_raw((string) $attributes['url']) : '';
		}

		return array(
			'title'       => sanitize_text_field((string) $item->title),
			'link'        => esc_url_raw((string) $item->link),
			'description' => sanitize_text_field(wp_strip_all_tags($description)),
			'date_time'   => $details['date_time'],
			'venue'       => $details['venue'],
			'image'       => $image,
		);
	}

	/**
	 * Parse the CitySpark description into date/time and venue.
	 *
	 * @param string $description Raw RSS item description.
	 * @return array{date_time: string, venue: string}
	 */
	private function parse_description(string $description): array {
		$description = preg_replace('/<br\s*\/?>/i', "\n", $description);
		$description = is_string($description) ? $description : '';
		$description = html_entity_decode($description, ENT_QUOTES | ENT_HTML5, 'UTF-8');
		$text        = wp_strip_all_tags($description);
		$parts       = preg_split('/\r\n|\r|\n/', $text);

		if (! is_array($parts)) {
			$parts = array($text);
		}

		$parts = array_values(
			array_filter(
				array_map(
					static function (string $part): string {
						return trim(preg_replace('/\s+/', ' ', $part) ?? '');
					},
					$parts
				),
				static function (string $part): bool {
					return '' !== $part;
				}
			)
		);

		return array(
			'date_time' => sanitize_text_field($parts[0] ?? ''),
			'venue'     => sanitize_text_field($parts[1] ?? ''),
		);
	}

	/**
	 * Limit event array.
	 *
	 * @param array<int, mixed> $events Events.
	 * @param int              $limit  Limit.
	 * @return array<int, array<string, string>>
	 */
	private function limit_events(array $events, int $limit): array {
		$normalized = array();

		foreach ($events as $event) {
			if (is_array($event)) {
				$normalized[] = $this->normalize_cached_event($event);
			}
		}

		if ($limit > 0) {
			return array_slice($normalized, 0, $limit);
		}

		return $normalized;
	}

	/**
	 * Normalize cached event data.
	 *
	 * @param array<mixed> $event Cached event.
	 * @return array<string, string>
	 */
	private function normalize_cached_event(array $event): array {
		return array(
			'title'       => isset($event['title']) ? (string) $event['title'] : '',
			'link'        => isset($event['link']) ? (string) $event['link'] : '',
			'description' => isset($event['description']) ? (string) $event['description'] : '',
			'date_time'   => isset($event['date_time']) ? (string) $event['date_time'] : '',
			'venue'       => isset($event['venue']) ? (string) $event['venue'] : '',
			'image'       => isset($event['image']) ? (string) $event['image'] : '',
		);
	}
}
