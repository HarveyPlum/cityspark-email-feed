<?php
/**
 * Settings page and option handling.
 *
 * @package CitySparkEmailFeed
 */

declare(strict_types=1);

namespace CitySpark\EmailFeed;

if (! defined('ABSPATH')) {
	exit;
}

/**
 * Settings page and option handling.
 */
final class Settings {
	public const OPTION_NAME = 'cityspark_email_feed_settings';

	/**
	 * Settings page hook suffix.
	 *
	 * @var string
	 */
	private string $page_hook = '';

	/**
	 * Default settings.
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults(): array {
		return array(
			'rss_feed_url'          => 'https://p.cityspark.com/RSS/Feed/10491',
			'default_number_events' => 5,
			'cache_time'            => 60,
			'show_images'           => 'yes',
			'show_venue'            => 'yes',
			'show_date'             => 'yes',
			'button_text'           => 'More Information',
			'button_color'          => '#1a73e8',
			'card_background'       => '#ffffff',
			'border_radius'         => 4,
		);
	}

	/**
	 * Register WordPress hooks.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action('admin_menu', array($this, 'add_settings_page'));
		add_action('admin_init', array($this, 'register_settings'));
		add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
	}

	/**
	 * Get all sanitized settings with defaults applied.
	 *
	 * @return array<string, mixed>
	 */
	public function all(): array {
		$stored = get_option(self::OPTION_NAME, array());

		if (! is_array($stored)) {
			$stored = array();
		}

		return wp_parse_args($stored, self::defaults());
	}

	/**
	 * Get a single setting.
	 *
	 * @param string $key Setting key.
	 * @return mixed
	 */
	public function get(string $key): mixed {
		$settings = $this->all();

		return $settings[$key] ?? self::defaults()[$key] ?? null;
	}

	/**
	 * Register the admin page.
	 *
	 * @return void
	 */
	public function add_settings_page(): void {
		$page_hook = add_options_page(
			__('CitySpark Email Feed', 'cityspark-email-feed'),
			__('CitySpark Email Feed', 'cityspark-email-feed'),
			'manage_options',
			'cityspark-email-feed',
			array($this, 'render_page')
		);

		if (is_string($page_hook)) {
			$this->page_hook = $page_hook;
		}
	}

	/**
	 * Register settings and fields.
	 *
	 * @return void
	 */
	public function register_settings(): void {
		register_setting(
			'cityspark_email_feed',
			self::OPTION_NAME,
			array(
				'type'              => 'array',
				'sanitize_callback' => array($this, 'sanitize'),
				'default'           => self::defaults(),
			)
		);

		add_settings_section(
			'cityspark_email_feed_main',
			__('Feed Display Settings', 'cityspark-email-feed'),
			static function (): void {
				echo '<p>' . esc_html__('Configure the CitySpark RSS feed and default email display options.', 'cityspark-email-feed') . '</p>';
			},
			'cityspark-email-feed'
		);

		$this->add_field('rss_feed_url', __('RSS Feed URL', 'cityspark-email-feed'), 'url');
		$this->add_field('default_number_events', __('Default Number of Events', 'cityspark-email-feed'), 'number');
		$this->add_field('cache_time', __('Cache Time (minutes)', 'cityspark-email-feed'), 'number');
		$this->add_field('show_images', __('Show Images', 'cityspark-email-feed'), 'yes_no');
		$this->add_field('show_venue', __('Show Venue', 'cityspark-email-feed'), 'yes_no');
		$this->add_field('show_date', __('Show Date', 'cityspark-email-feed'), 'yes_no');
		$this->add_field('button_text', __('Button Text', 'cityspark-email-feed'), 'text');
		$this->add_field('button_color', __('Button Color', 'cityspark-email-feed'), 'color');
		$this->add_field('card_background', __('Card Background', 'cityspark-email-feed'), 'color');
		$this->add_field('border_radius', __('Border Radius', 'cityspark-email-feed'), 'number');
	}

	/**
	 * Add a settings field.
	 *
	 * @param string $key   Setting key.
	 * @param string $label Field label.
	 * @param string $type  Field type.
	 * @return void
	 */
	private function add_field(string $key, string $label, string $type): void {
		add_settings_field(
			$key,
			$label,
			array($this, 'render_field'),
			'cityspark-email-feed',
			'cityspark_email_feed_main',
			array(
				'key'   => $key,
				'type'  => $type,
				'label' => $label,
			)
		);
	}

	/**
	 * Enqueue admin styles.
	 *
	 * @param string $hook_suffix Current admin page hook suffix.
	 * @return void
	 */
	public function enqueue_admin_assets(string $hook_suffix): void {
		if ($hook_suffix !== $this->page_hook) {
			return;
		}

		wp_enqueue_style(
			'cityspark-email-feed-admin',
			CITYSPARK_EMAIL_FEED_URL . 'assets/admin.css',
			array(),
			CITYSPARK_EMAIL_FEED_VERSION
		);
	}

	/**
	 * Sanitize settings before storage.
	 *
	 * @param mixed $input Raw input.
	 * @return array<string, mixed>
	 */
	public function sanitize(mixed $input): array {
		$defaults = self::defaults();

		if (! is_array($input)) {
			return $defaults;
		}

		$settings = array();

		$url = isset($input['rss_feed_url']) ? $this->sanitize_feed_url($input['rss_feed_url']) : '';
		$settings['rss_feed_url'] = '' !== $url ? $url : $defaults['rss_feed_url'];

		$settings['default_number_events'] = $this->sanitize_positive_integer(
			$input['default_number_events'] ?? $defaults['default_number_events'],
			(int) $defaults['default_number_events'],
			1,
			50
		);

		$settings['cache_time'] = $this->sanitize_positive_integer(
			$input['cache_time'] ?? $defaults['cache_time'],
			(int) $defaults['cache_time'],
			1,
			1440
		);

		$settings['show_images'] = $this->sanitize_yes_no($input['show_images'] ?? $defaults['show_images']);
		$settings['show_venue']  = $this->sanitize_yes_no($input['show_venue'] ?? $defaults['show_venue']);
		$settings['show_date']   = $this->sanitize_yes_no($input['show_date'] ?? $defaults['show_date']);

		$button_text = isset($input['button_text']) ? sanitize_text_field($this->unslash_scalar($input['button_text'])) : '';
		$settings['button_text'] = '' !== $button_text ? $button_text : $defaults['button_text'];

		$settings['button_color'] = $this->sanitize_hex_color_with_default(
			$input['button_color'] ?? $defaults['button_color'],
			(string) $defaults['button_color']
		);

		$settings['card_background'] = $this->sanitize_hex_color_with_default(
			$input['card_background'] ?? $defaults['card_background'],
			(string) $defaults['card_background']
		);

		$settings['border_radius'] = $this->sanitize_positive_integer(
			$input['border_radius'] ?? $defaults['border_radius'],
			(int) $defaults['border_radius'],
			0,
			40
		);

		return $settings;
	}

	/**
	 * Render the settings page.
	 *
	 * @return void
	 */
	public function render_page(): void {
		if (! current_user_can('manage_options')) {
			return;
		}
		?>
		<div class="wrap cityspark-email-feed-settings">
			<h1><?php echo esc_html(get_admin_page_title()); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields('cityspark_email_feed');
				do_settings_sections('cityspark-email-feed');
				submit_button();
				?>
			</form>

			<?php $this->render_usage_details(); ?>

			<p class="cityspark-support-footer">
				<?php
				echo wp_kses_post(
					sprintf(
						/* translators: %s: support email address. */
						__('Need help? Email %s', 'cityspark-email-feed'),
						'<a href="mailto:support@harveyplum.com">' . esc_html__('support@harveyplum.com', 'cityspark-email-feed') . '</a>'
					)
				);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Render shortcode, email, and RSS field reference details.
	 *
	 * @return void
	 */
	private function render_usage_details(): void {
		?>
		<div class="cityspark-usage-details" aria-label="<?php esc_attr_e('CitySpark Email Feed usage details', 'cityspark-email-feed'); ?>">
			<div class="cityspark-usage-card">
				<h2><?php esc_html_e('Shortcode', 'cityspark-email-feed'); ?></h2>
				<p><code>[cityspark_events]</code></p>
				<table class="widefat striped">
					<thead>
						<tr>
							<th scope="col"><?php esc_html_e('Attribute', 'cityspark-email-feed'); ?></th>
							<th scope="col"><?php esc_html_e('Example', 'cityspark-email-feed'); ?></th>
							<th scope="col"><?php esc_html_e('Description', 'cityspark-email-feed'); ?></th>
						</tr>
					</thead>
					<tbody>
						<tr><td><code>limit</code></td><td><code>[cityspark_events limit="5"]</code></td><td><?php esc_html_e('Maximum number of events to display.', 'cityspark-email-feed'); ?></td></tr>
						<tr><td><code>image</code></td><td><code>[cityspark_events image="no"]</code></td><td><?php esc_html_e('Show or hide event images. Use yes or no.', 'cityspark-email-feed'); ?></td></tr>
						<tr><td><code>date</code></td><td><code>[cityspark_events date="no"]</code></td><td><?php esc_html_e('Show or hide the parsed date and time.', 'cityspark-email-feed'); ?></td></tr>
						<tr><td><code>venue</code></td><td><code>[cityspark_events venue="no"]</code></td><td><?php esc_html_e('Show or hide the parsed venue.', 'cityspark-email-feed'); ?></td></tr>
						<tr><td><code>button</code></td><td><code>[cityspark_events button="Details"]</code></td><td><?php esc_html_e('Customize the button text, or use no to hide the button.', 'cityspark-email-feed'); ?></td></tr>
						<tr><td><code>layout</code></td><td><code>[cityspark_events layout="compact"]</code></td><td><?php esc_html_e('Use default or compact spacing.', 'cityspark-email-feed'); ?></td></tr>
					</tbody>
				</table>
			</div>

			<div class="cityspark-usage-card">
				<h2><?php esc_html_e('Noptin Email Merge Tag', 'cityspark-email-feed'); ?></h2>
				<p><code>{{cityspark_events}}</code></p>
				<table class="widefat striped">
					<thead>
						<tr>
							<th scope="col"><?php esc_html_e('Field', 'cityspark-email-feed'); ?></th>
							<th scope="col"><?php esc_html_e('Example', 'cityspark-email-feed'); ?></th>
							<th scope="col"><?php esc_html_e('Description', 'cityspark-email-feed'); ?></th>
						</tr>
					</thead>
					<tbody>
						<tr><td><code>cityspark_events</code></td><td><code>{{cityspark_events}}</code></td><td><?php esc_html_e('Displays events using the defaults above.', 'cityspark-email-feed'); ?></td></tr>
						<tr><td><code>limit</code></td><td><code>{{cityspark_events limit=5}}</code></td><td><?php esc_html_e('Limits event count inside Noptin emails.', 'cityspark-email-feed'); ?></td></tr>
						<tr><td><code>image</code></td><td><code>{{cityspark_events image=no}}</code></td><td><?php esc_html_e('Shows or hides event images in email output.', 'cityspark-email-feed'); ?></td></tr>
						<tr><td><code>date</code></td><td><code>{{cityspark_events date=no}}</code></td><td><?php esc_html_e('Shows or hides the parsed date and time in email output.', 'cityspark-email-feed'); ?></td></tr>
						<tr><td><code>venue</code></td><td><code>{{cityspark_events venue=no}}</code></td><td><?php esc_html_e('Shows or hides the parsed venue in email output.', 'cityspark-email-feed'); ?></td></tr>
					</tbody>
				</table>
			</div>

			<div class="cityspark-usage-card">
				<h2><?php esc_html_e('RSS Fields Rendered', 'cityspark-email-feed'); ?></h2>
				<table class="widefat striped">
					<tbody>
						<tr><th scope="row"><code>title</code></th><td><?php esc_html_e('Event title.', 'cityspark-email-feed'); ?></td></tr>
						<tr><th scope="row"><code>link</code></th><td><?php esc_html_e('Event link used for the title and button.', 'cityspark-email-feed'); ?></td></tr>
						<tr><th scope="row"><code>description</code></th><td><?php esc_html_e('Parsed into date/time and venue when separated by a line break.', 'cityspark-email-feed'); ?></td></tr>
						<tr><th scope="row"><code>media:content url</code></th><td><?php esc_html_e('Event image URL.', 'cityspark-email-feed'); ?></td></tr>
					</tbody>
				</table>
			</div>
		</div>
		<?php
	}

	/**
	 * Render a settings field.
	 *
	 * @param array<string, string> $args Field arguments.
	 * @return void
	 */
	public function render_field(array $args): void {
		$key      = $args['key'] ?? '';
		$type     = $args['type'] ?? 'text';
		$settings = $this->all();
		$value    = $this->scalar_to_string($settings[$key] ?? self::defaults()[$key] ?? '');
		$name     = self::OPTION_NAME . '[' . $key . ']';
		$id       = 'cityspark_email_feed_' . $key;

		if ('yes_no' === $type) {
			$this->render_yes_no_field($id, $name, $value);
			return;
		}

		if ('color' === $type) {
			printf(
				'<input type="text" class="regular-text cityspark-color-field" id="%1$s" name="%2$s" value="%3$s" pattern="^#[0-9a-fA-F]{6}$" />',
				esc_attr($id),
				esc_attr($name),
				esc_attr($value)
			);
			return;
		}

		if ('number' === $type) {
			$min = 'border_radius' === $key ? 0 : 1;
			$max = 'cache_time' === $key ? 1440 : ('default_number_events' === $key ? 50 : 40);

			printf(
				'<input type="number" class="small-text" id="%1$s" name="%2$s" value="%3$d" min="%4$d" max="%5$d" />',
				esc_attr($id),
				esc_attr($name),
				(int) $value,
				(int) $min,
				(int) $max
			);

			if ('border_radius' === $key) {
				echo ' <span class="description">' . esc_html__('Pixels', 'cityspark-email-feed') . '</span>';
			}

			return;
		}

		$input_type = 'url' === $type ? 'url' : 'text';

		printf(
			'<input type="%1$s" class="regular-text" id="%2$s" name="%3$s" value="%4$s" />',
			esc_attr($input_type),
			esc_attr($id),
			esc_attr($name),
			esc_attr($value)
		);
	}

	/**
	 * Render a yes/no select field.
	 *
	 * @param string $id    Field ID.
	 * @param string $name  Field name.
	 * @param string $value Current value.
	 * @return void
	 */
	private function render_yes_no_field(string $id, string $name, string $value): void {
		?>
		<select id="<?php echo esc_attr($id); ?>" name="<?php echo esc_attr($name); ?>">
			<option value="yes" <?php selected($value, 'yes'); ?>><?php esc_html_e('Yes', 'cityspark-email-feed'); ?></option>
			<option value="no" <?php selected($value, 'no'); ?>><?php esc_html_e('No', 'cityspark-email-feed'); ?></option>
		</select>
		<?php
	}

	/**
	 * Sanitize a yes/no setting.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	private function sanitize_yes_no(mixed $value): string {
		return 'no' === $this->scalar_to_string($value) ? 'no' : 'yes';
	}

	/**
	 * Sanitize an integer within bounds.
	 *
	 * @param mixed $value    Raw value.
	 * @param int   $fallback Fallback value.
	 * @param int   $min      Minimum value.
	 * @param int   $max      Maximum value.
	 * @return int
	 */
	private function sanitize_positive_integer(mixed $value, int $fallback, int $min, int $max): int {
		$integer = absint($value);

		if ($integer < $min) {
			return $fallback;
		}

		return min($integer, $max);
	}

	/**
	 * Sanitize a hex color with fallback.
	 *
	 * @param mixed  $value    Raw value.
	 * @param string $fallback Fallback color.
	 * @return string
	 */
	private function sanitize_hex_color_with_default(mixed $value, string $fallback): string {
		$color = sanitize_hex_color($this->scalar_to_string($value));

		return null !== $color ? $color : $fallback;
	}

	/**
	 * Accept only public HTTPS feed URLs.
	 *
	 * @param mixed $value Raw feed URL.
	 */
	private function sanitize_feed_url(mixed $value): string {
		$url = esc_url_raw($this->unslash_scalar($value), array('https'));

		if ('' === $url || ! wp_http_validate_url($url)) {
			return '';
		}

		return $url;
	}

	/**
	 * Safely unslash a scalar value.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	private function unslash_scalar(mixed $value): string {
		return $this->scalar_to_string(wp_unslash($value));
	}

	/**
	 * Convert scalar values to strings without warnings.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	private function scalar_to_string(mixed $value): string {
		if (is_scalar($value)) {
			return (string) $value;
		}

		return '';
	}
}
