<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Block_Woo_Orders
 * @subpackage Block_Woo_Orders/admin
 * @author     Jenny Chan <jenny@jennychan.dev>
 */
class Block_Woo_Orders_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $plugin_name The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $version The current version of this plugin.
	 */
	private $version;

	/**
	 * The class for the listing table to display emails and app user ids
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Block_Woo_Orders_Listing_Table $listing_table
	 */
	private $listing_table;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $plugin_name The name of this plugin.
	 * @param string $version The version of this plugin.
	 *
	 * @since    1.0.0
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/block-woo-orders-admin.css', array(), $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/block-woo-orders-admin.js', array( 'jquery' ), $this->version, false );
	}

	public function add_admin_menu() {
		add_menu_page(
			__( 'Block Woo Orders', 'block-woo-orders' ),
			'Block Woo Orders',
			'manage_options',
			'block-woo-orders',
			array( $this, 'admin_page_display' ),
			'dashicons-shield',
			60
		);

		add_submenu_page(
			'block-woo-orders',
			'Block Woo Orders Settings',
			'Settings',
			'manage_options',
			'block-woo-orders',
		);

		add_submenu_page(
			'block-woo-orders',
			'Add New Entry',
			'Add New Entry',
			'manage_options',
			'add-block-woo-orders-entry',
			array( $this, 'add_entry_page_display' ),
		);

		add_submenu_page(
			'block-woo-orders',
			'Emails',
			'All Emails',
			'manage_options',
			'bwo_email',
			array( $this, 'entry_listing_display' )
		);

		add_submenu_page(
			'block-woo-orders',
			'App User IDs',
			'All App User IDs',
			'manage_options',
			'bwo_app_user_id',
			array( $this, 'entry_listing_display' )
		);
	}

	public function admin_init() {
		// add setting sections
		$this->add_settings_sections();

		// add setting fields
		$this->add_settings_fields();

		// save settings
		$this->save_settings();
	}

	public function add_settings_sections() {
		add_settings_section(
			'bwo-general-section',
			'General Settings',
			function () {
				echo '<p>These are general settings.</p>';
			},
			'bwo-settings-page',
		);
	}

	public function add_settings_fields() {
		add_settings_field(
			'bwo_scan_enabled',
			'Enable Scan?',
			array( $this, 'checkbox_cb' ),
			'bwo-settings-page',
			'bwo-general-section',
			array(
				'name'        => 'bwo_scan_enabled',
				'value'       => '1',
				'saved_value' => get_option( 'bwo_scan_enabled' ),
			)
		);
	}

	public function checkbox_cb( $args ) {
		if ( ! is_array( $args ) || empty( $args ) ) {
			return;
		}

		$name  = $args['name'] ?? '';
		$label = $args['label'] ?? '';
		$value = $args['value'] ?? '';

		?>
        <input type="checkbox" name="<?php echo $name ?>"
               value="<?php echo $value ?>" <?php checked( 1, get_option( $name ), true ); ?> />
        <label for="<?php echo $name ?>"><?php echo $label ?></label>
		<?php
	}

	public function save_settings() {
		register_setting(
			'bwo-settings-page-options-group',
			'bwo_scan_enabled',
			'',
		);
	}


	public function add_entry_page_display() {
		include 'partials/block-woo-orders-add-entry-display.php';
	}

	public function admin_page_display() {
		include 'partials/block-woo-orders-admin-display.php';
	}

	public function entry_listing_display() {
		$this->listing_table = new Block_Woo_Orders_Listing_Table();
		$this->listing_table->prepare_items();
		include 'partials/block-woo-orders-listing-table-display.php';
	}

	public function add_entry() {
		try {
			check_admin_referer( 'bwo_add_or_update_entry' );
			$type = $_POST['type'];
			$id   = intval( $_POST['id'] );

			if ( $type === "email" ) {
				$entry = new Block_Woo_Orders_Email( $id );
				$name  = sanitize_email( $_POST['name'] );
                if (empty($name)) {
                    throw new Exception($_POST['name'] . " is not a valid email.");
                }
			} else if ( $type === "app_user_id" ) {
				$entry = new Block_Woo_Orders_App_User_Id( $id );
				$name  = sanitize_text_field( $_POST['name'] );
			}

			$notes = sanitize_textarea_field( $_POST['notes'] );
			$flag  = sanitize_text_field( $_POST['flag'] );

			$entry->set_name( $name );
			$entry->set_flag( $flag );
			$entry->set_notes( $notes );

			$result = $entry->save();

            if (empty($result)) {
                global $wpdb;
                throw new Exception($wpdb->last_error);
            }

            $args = array( 'added' => 1 );

			$redirect = add_query_arg( $args, admin_url( 'admin.php?page=add-block-woo-orders-entry' ) );
			wp_safe_redirect( $redirect );
			exit();
		} catch ( Exception $e ) {
			$args = array(
				'added'     => 0,
				'error_msg' => $e->getMessage(),
			);

			$redirect = add_query_arg( $args, admin_url( 'admin.php?page=add-block-woo-orders-entry' ) );
			wp_safe_redirect( $redirect );
			exit();
		}
	}
}
