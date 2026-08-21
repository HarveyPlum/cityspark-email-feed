<?php
/**
 * Email-safe event renderer.
 *
 * @package CitySparkEmailFeed
 */

declare(strict_types=1);

namespace CitySpark\EmailFeed;

if (! defined('ABSPATH')) {
	exit;
}

/**
 * Email-safe event renderer.
 */
final class Renderer {
	/**
	 * Render events as nested table HTML.
	 *
	 * @param array<int, array<string, string>> $events Parsed events.
	 * @param array<string, mixed>              $args   Render arguments.
	 * @return string
	 */
	public function render(array $events, array $args = array()): string {
		$args = wp_parse_args(
			$args,
			array(
				'show_images'     => true,
				'show_date'       => true,
				'show_venue'      => true,
				'show_button'     => true,
				'button_text'     => 'More Information',
				'button_color'    => '#1a73e8',
				'card_background' => '#ffffff',
				'border_radius'   => 4,
				'layout'          => 'default',
			)
		);

		if (empty($events)) {
			return '<p style="margin:0;color:#555555;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:22px;">' . esc_html__('No upcoming events.', 'cityspark-email-feed') . '</p>';
		}

		$output  = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;border-collapse:collapse;mso-table-lspace:0pt;mso-table-rspace:0pt;">';
		$output .= '<tbody>';

		foreach ($events as $event) {
			$output .= $this->render_event($event, $args);
		}

		$output .= '</tbody></table>';

		return $output;
	}

	/**
	 * Render a single event row.
	 *
	 * @param array<string, string> $event Event data.
	 * @param array<string, mixed>  $args  Render arguments.
	 * @return string
	 */
	private function render_event(array $event, array $args): string {
		$is_compact      = 'compact' === (string) $args['layout'];
		$outer_padding   = $is_compact ? '0 0 12px 0' : '0 0 18px 0';
		$content_padding = $is_compact ? '14px 16px 16px 16px' : '18px 20px 20px 20px';
		$title_size      = $is_compact ? '17px' : '20px';
		$radius          = $this->sanitize_radius($args['border_radius']);
		$background      = $this->sanitize_color((string) $args['card_background'], '#ffffff');
		$button_color    = $this->sanitize_color((string) $args['button_color'], '#1a73e8');
		$title           = $event['title'] ?? '';
		$link            = $event['link'] ?? '';

		$output  = '<tr>';
		$output .= '<td style="padding:' . esc_attr($outer_padding) . ';">';
		$output .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="' . esc_attr($background) . '" style="width:100%;border-collapse:separate;mso-table-lspace:0pt;mso-table-rspace:0pt;background-color:' . esc_attr($background) . ';border:1px solid #dddddd;border-radius:' . esc_attr($radius) . 'px;">';
		$output .= '<tbody>';
		$output .= $this->render_image_row($event, $args, $radius);
		$output .= '<tr><td style="padding:' . esc_attr($content_padding) . ';">';
		$output .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;border-collapse:collapse;mso-table-lspace:0pt;mso-table-rspace:0pt;"><tbody>';
		$output .= '<tr><td style="padding:0 0 8px 0;color:#111111;font-family:Arial,Helvetica,sans-serif;font-size:' . esc_attr($title_size) . ';font-weight:bold;line-height:1.35;">';

		if ('' !== $link) {
			$output .= '<a href="' . esc_url($link) . '" target="_blank" rel="noopener noreferrer" style="color:#111111;text-decoration:none;">' . esc_html($title) . '</a>';
		} else {
			$output .= esc_html($title);
		}

		$output .= '</td></tr>';
		$output .= $this->render_meta_rows($event, $args);
		$output .= $this->render_button_row($link, $args, $button_color);
		$output .= '</tbody></table>';
		$output .= '</td></tr>';
		$output .= '</tbody></table>';
		$output .= '</td></tr>';

		return $output;
	}

	/**
	 * Render optional image row.
	 *
	 * @param array<string, string> $event  Event data.
	 * @param array<string, mixed>  $args   Render arguments.
	 * @param int                   $radius Border radius.
	 * @return string
	 */
	private function render_image_row(array $event, array $args, int $radius): string {
		$image = $event['image'] ?? '';

		if (! $this->truthy($args['show_images']) || '' === $image) {
			return '';
		}

		$top_radius = $radius . 'px ' . $radius . 'px 0 0';

		return '<tr><td style="padding:0;"><img src="' . esc_url($image) . '" width="640" alt="" style="display:block;width:100%;max-width:640px;height:auto;margin:0;border:0;outline:none;text-decoration:none;border-radius:' . esc_attr($top_radius) . ';" /></td></tr>';
	}

	/**
	 * Render date and venue rows.
	 *
	 * @param array<string, string> $event Event data.
	 * @param array<string, mixed>  $args  Render arguments.
	 * @return string
	 */
	private function render_meta_rows(array $event, array $args): string {
		$output = '';
		$date   = $event['date_time'] ?? '';
		$venue  = $event['venue'] ?? '';

		if ($this->truthy($args['show_date']) && '' !== $date) {
			$output .= '<tr><td style="padding:0 0 4px 0;color:#333333;font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:20px;">' . esc_html($date) . '</td></tr>';
		}

		if ($this->truthy($args['show_venue']) && '' !== $venue) {
			$output .= '<tr><td style="padding:0 0 12px 0;color:#666666;font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:20px;">' . esc_html($venue) . '</td></tr>';
		}

		return $output;
	}

	/**
	 * Render button row.
	 *
	 * @param string               $link         Event URL.
	 * @param array<string, mixed> $args         Render arguments.
	 * @param string               $button_color Button color.
	 * @return string
	 */
	private function render_button_row(string $link, array $args, string $button_color): string {
		if ('' === $link || ! $this->truthy($args['show_button'])) {
			return '';
		}

		$button_text = sanitize_text_field((string) $args['button_text']);

		if ('' === $button_text) {
			$button_text = __('More Information', 'cityspark-email-feed');
		}

		$output  = '<tr><td style="padding:4px 0 0 0;">';
		$output .= '<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="border-collapse:separate;mso-table-lspace:0pt;mso-table-rspace:0pt;"><tbody><tr>';
		$output .= '<td bgcolor="' . esc_attr($button_color) . '" style="background-color:' . esc_attr($button_color) . ';border-radius:4px;text-align:center;">';
		$output .= '<a href="' . esc_url($link) . '" target="_blank" rel="noopener noreferrer" style="display:inline-block;padding:10px 16px;color:#ffffff;font-family:Arial,Helvetica,sans-serif;font-size:14px;font-weight:bold;line-height:18px;text-decoration:none;border-radius:4px;">' . esc_html($button_text) . '</a>';
		$output .= '</td></tr></tbody></table>';
		$output .= '</td></tr>';

		return $output;
	}

	/**
	 * Normalize truthy setting values.
	 *
	 * @param mixed $value Value.
	 * @return bool
	 */
	private function truthy(mixed $value): bool {
		if (is_bool($value)) {
			return $value;
		}

		return ! in_array(strtolower((string) $value), array('0', 'false', 'no', 'off'), true);
	}

	/**
	 * Sanitize a CSS hex color.
	 *
	 * @param string $color    Color.
	 * @param string $fallback Fallback.
	 * @return string
	 */
	private function sanitize_color(string $color, string $fallback): string {
		$sanitized = sanitize_hex_color($color);

		return null !== $sanitized ? $sanitized : $fallback;
	}

	/**
	 * Sanitize border radius.
	 *
	 * @param mixed $radius Radius.
	 * @return int
	 */
	private function sanitize_radius(mixed $radius): int {
		return min(40, max(0, absint($radius)));
	}
}
