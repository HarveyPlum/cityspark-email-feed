<?php
/**
 * Noptin integration.
 *
 * @package CitySparkEmailFeed
 */

declare(strict_types=1);

namespace CitySpark\EmailFeed;

if (! defined('ABSPATH')) {
	exit;
}

/**
 * Noptin integration.
 */
final class Noptin {
	/**
	 * Shared shortcode renderer.
	 *
	 * @var Shortcode
	 */
	private Shortcode $shortcode;

	/**
	 * Constructor.
	 *
	 * @param Settings $settings Settings service.
	 * @param Feed     $feed     Feed service.
	 * @param Renderer $renderer Renderer service.
	 */
	public function __construct(Settings $settings, Feed $feed, Renderer $renderer) {
		$this->shortcode = new Shortcode($settings, $feed, $renderer);
	}

	/**
	 * Register Noptin hooks.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_filter('noptin_email_tags', array($this, 'register_merge_tag'));
		add_filter('noptin_merge_tags', array($this, 'register_merge_tag'));
		add_filter('noptin_newsletter_merge_tags', array($this, 'register_merge_tag'));
		add_filter('noptin_known_smart_tags', array($this, 'register_smart_tag'));
		add_filter('noptin_smart_tags', array($this, 'register_smart_tag'));
		add_filter('noptin_replace_smart_tags', array($this, 'replace_smart_tag'), 10, 3);
		add_filter('noptin_parse_smart_tags', array($this, 'replace_content_tags'), 10, 3);
		add_filter('noptin_email_body', array($this, 'replace_content_tags'), 10, 2);
		add_filter('noptin_email_content', array($this, 'replace_content_tags'), 10, 2);
		add_filter('noptin_pre_send_email_content', array($this, 'replace_content_tags'), 10, 2);
		add_filter('noptin_parse_merge_tags', array($this, 'replace_content_tags'), 10, 2);
	}

	/**
	 * Register a legacy Noptin merge tag.
	 *
	 * @param mixed $tags Existing tags.
	 * @return mixed
	 */
	public function register_merge_tag(mixed $tags): mixed {
		if (! is_array($tags)) {
			return $tags;
		}

		$tags['cityspark_events'] = array(
			'description' => __('Displays CitySpark RSS events.', 'cityspark-email-feed'),
			'example'     => '{{cityspark_events limit=5}}',
			'callback'    => array($this, 'render_merge_tag'),
		);

		return $tags;
	}

	/**
	 * Register a Noptin smart tag.
	 *
	 * @param mixed $tags Existing tags.
	 * @return mixed
	 */
	public function register_smart_tag(mixed $tags): mixed {
		if (! is_array($tags)) {
			return $tags;
		}

		$tags['cityspark_events'] = array(
			'label'       => __('CitySpark Events', 'cityspark-email-feed'),
			'description' => __('Displays CitySpark RSS events.', 'cityspark-email-feed'),
			'callback'    => array($this, 'render_merge_tag'),
		);

		return $tags;
	}

	/**
	 * Render a merge tag callback.
	 *
	 * @param mixed $attributes Attributes or raw tag.
	 * @return string
	 */
	public function render_merge_tag(mixed $attributes = array()): string {
		if (is_string($attributes)) {
			$attributes = $this->parse_attribute_string($attributes);
		}

		if (! is_array($attributes)) {
			$attributes = array();
		}

		return $this->shortcode->render_with_attributes($attributes);
	}

	/**
	 * Replace smart tag values for Noptin versions that pass tag data through a value filter.
	 *
	 * @param mixed $replacement Current replacement.
	 * @param mixed $tag         Tag name or tag object.
	 * @param mixed $context     Context.
	 * @return mixed
	 */
	public function replace_smart_tag(mixed $replacement, mixed $tag = '', mixed $context = null): mixed {
		unset($context);

		$tag_name = is_string($tag) ? $tag : '';

		if ('cityspark_events' !== $tag_name) {
			return $replacement;
		}

		return $this->render_merge_tag();
	}

	/**
	 * Replace literal CitySpark merge tags in Noptin content.
	 *
	 * @param mixed $content Content.
	 * @param mixed $context Context.
	 * @param mixed $extra   Extra context.
	 * @return mixed
	 */
	public function replace_content_tags(mixed $content, mixed $context = null, mixed $extra = null): mixed {
		unset($context, $extra);

		if (! is_string($content) || false === stripos($content, '{{cityspark_events')) {
			return $content;
		}

		return preg_replace_callback(
			'/{{\s*cityspark_events(?P<attributes>[^}]*)}}/i',
			function (array $matches): string {
				$attribute_string = isset($matches['attributes']) ? trim((string) $matches['attributes']) : '';

				return $this->shortcode->render_with_attributes($this->parse_attribute_string($attribute_string));
			},
			$content
		) ?? $content;
	}

	/**
	 * Parse Noptin merge tag attributes.
	 *
	 * @param string $attribute_string Attribute string.
	 * @return array<string, mixed>
	 */
	private function parse_attribute_string(string $attribute_string): array {
		$attribute_string = trim($attribute_string);

		if ('' === $attribute_string) {
			return array();
		}

		$attributes = shortcode_parse_atts($attribute_string);

		return is_array($attributes) ? $attributes : array();
	}
}
