<?php
/**
 * Settings UI
 *
 * @package convert-to-blocks
 */

namespace ConvertToBlocks;

/**
 * UI for configuring plugin settings.
 */
class Settings {

	/**
	 * User permissions to manage settings.
	 *
	 * @var string
	 */
	private $capability = 'manage_options';

	/**
	 * Settings page slug.
	 *
	 * @var string
	 */
	private $settings_page = '%s-settings-page';

	/**
	 * Settings section name.
	 *
	 * @var string
	 */
	private $settings_section = '%s-settings-section';

	/**
	 * Settings group name.
	 *
	 * @var string
	 */
	private $settings_group = '%s_settings';

	/**
	 * Post types.
	 *
	 * @var array
	 */
	private $post_types = [];

	/**
	 * Register hooks with WordPress
	 */
	public function register() {
		// Configure variables and get post types.
		$this->init();

		add_action( 'admin_menu', [ $this, 'add_menu' ] );
		add_action( 'admin_init', [ $this, 'register_section' ], 10 );
		add_action( 'admin_init', [ $this, 'register_fields' ], 20 );
	}

	/**
	 * Only registers on admin context.
	 */
	public function can_register() {
		return is_admin();
	}

	/**
	 * Configures variables and fetches post types.
	 *
	 * @return void
	 */
	public function init() {
		// Configure variables.
		$this->settings_page    = sprintf( $this->settings_page, CONVERT_TO_BLOCKS_SLUG );
		$this->settings_section = sprintf( $this->settings_section, CONVERT_TO_BLOCKS_SLUG );
		$this->settings_group   = sprintf( $this->settings_group, CONVERT_TO_BLOCKS_PREFIX );

		// Get post types.
		$this->post_types = $this->get_post_types();
	}

	/**
	 * Retrieves all public post types.
	 *
	 * @return array
	 */
	public function get_post_types() {
		$post_types = get_post_types(
			[ 'public' => true ]
		);

		if ( ! empty( $post_types['attachment'] ) ) {
			unset( $post_types['attachment'] );
		}

		return $post_types;
	}

	/**
	 * Adds a submenu item for the `Settings` menu.
	 *
	 * @return void
	 */
	public function add_menu() {
		add_options_page(
			esc_html__( 'Convert to Blocks', 'convert-to-blocks' ),
			esc_html__( 'Convert to Blocks', 'convert-to-blocks' ),
			$this->capability,
			CONVERT_TO_BLOCKS_SLUG,
			[ $this, 'settings_page' ]
		);
	}

	/**
	 * Registers the settings page.
	 *
	 * @return void
	 */
	public function settings_page() {
		?>
		<div class="wrap">
			<h1>
				<?php esc_html_e( 'Convert to Blocks', 'convert-to-blocks' ); ?>
			</h1>
			<hr>

			<p>
				<?php esc_html_e( 'Configure plugin by selecting the supported post types.', 'convert-to-blocks' ); ?>
			</p>

			<form method="post" action="options.php">
				<?php
				settings_fields( $this->settings_group );
				do_settings_sections( CONVERT_TO_BLOCKS_SLUG );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Registers section for the settings page.
	 *
	 * @return void
	 */
	public function register_section() {
		add_settings_section(
			$this->settings_section,
			'',
			'',
			CONVERT_TO_BLOCKS_SLUG
		);
	}

	/**
	 * Registers setting fields.
	 *
	 * @return void
	 */
	public function register_fields() {
		// Supported post types.
		add_settings_field(
			sprintf( '%s_post_types', CONVERT_TO_BLOCKS_PREFIX ),
			esc_html__( 'Supported Post Types', 'convert-to-blocks' ),
			[ $this, 'field_post_types' ],
			CONVERT_TO_BLOCKS_SLUG,
			$this->settings_section,
			[
				'label_for' => sprintf( '%s_post_types', CONVERT_TO_BLOCKS_PREFIX ),
			]
		);

		register_setting(
			$this->settings_group,
			sprintf( '%s_post_types', CONVERT_TO_BLOCKS_PREFIX ),
			[
				'sanitize_callback' => [ $this, 'sanitize_post_types' ],
			]
		);
	}

	/**
	 * Renders the post_types field.
	 *
	 * @return void
	 */
	public function field_post_types() {
		$post_types = get_option( sprintf( '%s_post_types', CONVERT_TO_BLOCKS_PREFIX ), [] );

		echo '<fieldset>';
		foreach ( $this->post_types as $post_type ) {
			printf(
				'<label for="%1$s"><input name="%1$s[]" type="checkbox" %2$s id="%1$s" value="%3$s"> %4$s</label> <br>',
				sprintf( '%s_post_types', esc_attr( CONVERT_TO_BLOCKS_PREFIX ) ),
				checked( in_array( $post_type, $post_types, true ), 1, false ),
				esc_attr( $post_type ),
				esc_attr( ucfirst( $post_type ) )
			);
		}
		echo '</fieldset>';
	}

	/**
	 * Sanitizes post_types values.
	 *
	 * @param array $input Array containing checked values.
	 *
	 * @return array Sanitized array.
	 */
	public function sanitize_post_types( $input ) {
		if ( ! is_array( $input ) ) {
			return [];
		}

		return array_map( 'sanitize_text_field', $input );
	}

}
