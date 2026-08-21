<?php
/**
 * Shortcode integration.
 *
 * @package CitySparkEmailFeed
 */

declare(strict_types=1);

namespace CitySpark\EmailFeed;

if (! defined('ABSPATH')) {
	exit;
}

/**
 * Shortcode integration.
 */
final class Shortcode {
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
	 * Constructor.
	 *
	 * @param Settings $settings Settings service.
	 * @param Feed     $feed     Feed service.
	 * @param Renderer $renderer Renderer service.
	 */
	public function __construct(Settings $settings, Feed $feed, Renderer $renderer) {
		$this->settings = $settings;
		$this->feed     = $feed;
		$this->renderer = $renderer;
	}

	/**
	 * Register WordPress hooks.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_shortcode('cityspark_events', array($this, 'render'));
	}

	/**
	 * Render the shortcode.
	 *
	 * @param mixed $atts Shortcode attributes.
	 * @return string
	 */
	public function render(mixed $atts = array()): string {
		$atts = shortcode_atts(
			array(
				'limit'  => '',
				'image'  => '',
				'date'   => '',
				'venue'  => '',
				'button' => '',
				'layout' => 'default',
			),
			is_array($atts) ? $atts : array(),
			'cityspark_events'
		);

		return $this->render_with_attributes($atts);
	}

	/**
	 * Render events using shortcode-like attributes.
	 *
	 * @param array<string, mixed> $atts Attributes.
	 * @return string
	 */
	public function render_with_attributes(array $atts): string {
		$settings = $this->settings->all();
		$limit    = $this->resolve_limit($atts['limit'] ?? '', (int) $settings['default_number_events']);
		$events   = $this->feed->get_events((string) $settings['rss_feed_url'], $limit);
		$args     = $this->build_render_args($atts, $settings);

		return $this->renderer->render($events, $args);
	}

	/**
	 * Build renderer arguments from attributes and settings.
	 *
	 * @param array<string, mixed> $atts     Shortcode attributes.
	 * @param array<string, mixed> $settings Plugin settings.
	 * @return array<string, mixed>
	 */
	private function build_render_args(array $atts, array $settings): array {
		$button_value = isset($atts['button']) ? trim((string) $atts['button']) : '';
		$show_button  = true;
		$button_text  = (string) $settings['button_text'];

		if ('' !== $button_value) {
			if ($this->is_no($button_value)) {
				$show_button = false;
			} elseif (! $this->is_yes($button_value)) {
				$button_text = sanitize_text_field($button_value);
			}
		}

		return array(
			'show_images'     => $this->resolve_yes_no($atts['image'] ?? '', (string) $settings['show_images']),
			'show_date'       => $this->resolve_yes_no($atts['date'] ?? '', (string) $settings['show_date']),
			'show_venue'      => $this->resolve_yes_no($atts['venue'] ?? '', (string) $settings['show_venue']),
			'show_button'     => $show_button,
			'button_text'     => $button_text,
			'button_color'    => (string) $settings['button_color'],
			'card_background' => (string) $settings['card_background'],
			'border_radius'   => (int) $settings['border_radius'],
			'layout'          => $this->sanitize_layout($atts['layout'] ?? 'default'),
		);
	}

	/**
	 * Resolve event limit.
	 *
	 * @param mixed $value    Attribute value.
	 * @param int   $fallback Fallback value.
	 * @return int
	 */
	private function resolve_limit(mixed $value, int $fallback): int {
		$limit = absint($value);

		if ($limit < 1) {
			return max(1, $fallback);
		}

		return min(50, $limit);
	}

	/**
	 * Resolve yes/no attribute values.
	 *
	 * @param mixed  $attribute Attribute value.
	 * @param string $fallback  Fallback setting.
	 * @return bool
	 */
	private function resolve_yes_no(mixed $attribute, string $fallback): bool {
		$value = trim((string) $attribute);

		if ('' === $value) {
			$value = $fallback;
		}

		return ! $this->is_no($value);
	}

	/**
	 * Check a no-like value.
	 *
	 * @param string $value Value.
	 * @return bool
	 */
	private function is_no(string $value): bool {
		return in_array(strtolower($value), array('0', 'false', 'no', 'off'), true);
	}

	/**
	 * Check a yes-like value.
	 *
	 * @param string $value Value.
	 * @return bool
	 */
	private function is_yes(string $value): bool {
		return in_array(strtolower($value), array('1', 'true', 'yes', 'on'), true);
	}

	/**
	 * Sanitize layout attribute.
	 *
	 * @param mixed $layout Layout.
	 * @return string
	 */
	private function sanitize_layout(mixed $layout): string {
		return 'compact' === strtolower((string) $layout) ? 'compact' : 'default';
	}
}
