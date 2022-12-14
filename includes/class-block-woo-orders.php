<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://jennychan.dev
 * @since      1.0.0
 *
 * @package    Block_Woo_Orders
 * @subpackage Block_Woo_Orders/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Block_Woo_Orders
 * @subpackage Block_Woo_Orders/includes
 * @author     Jenny Chan <jenny@jennychan.dev>
 */
class Block_Woo_Orders {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Block_Woo_Orders_Loader $loader Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string $plugin_name The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string $version The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'BLOCK_WOO_ORDERS_VERSION' ) ) {
			$this->version = BLOCK_WOO_ORDERS_VERSION;
		} else {
			$this->version = '1.0.0';
		}

		if ( defined( 'BLOCK_WOO_ORDERS_PLUGIN_NAME' ) ) {
			$this->plugin_name = BLOCK_WOO_ORDERS_PLUGIN_NAME;
		} else {
			$this->plugin_name = 'block-woo-orders';
		}

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		// not using public css or js for now
//		$this->define_public_hooks();
		$this->set_custom_order_statuses();
		$this->define_woocommerce_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Block_Woo_Orders_Loader. Orchestrates the hooks of the plugin.
	 * - Block_Woo_Orders_i18n. Defines internationalization functionality.
	 * - Block_Woo_Orders_Admin. Defines all hooks for the admin area.
	 * - Block_Woo_Orders_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-block-woo-orders-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-block-woo-orders-i18n.php';

		/**
		 * The class to load the email entries
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-block-woo-orders-email.php';

		/**
		 * The class to load the app_user_id entries
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-block-woo-orders-app-user-id.php';

		/**
		 * The class responsible for defining Woocommerce order custom statuses
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-block-woo-orders-custom-statuses.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-block-woo-orders-admin.php';

		/**
		 * The class responsible for defining all actions for Woocommerce.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-block-woo-orders-woocommerce-hooks.php';

		/**
		 * The class responsible for displaying the entries
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-block-woo-orders-listing-table.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-block-woo-orders-public.php';

		$this->loader = new Block_Woo_Orders_Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Block_Woo_Orders_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Block_Woo_Orders_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Block_Woo_Orders_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
//		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'add_admin_menu' );
		$this->loader->add_action( 'admin_init', $plugin_admin, 'admin_init' );
		$this->loader->add_action( 'admin_post_bwo_add_entry', $plugin_admin, 'add_entry' );
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Block_Woo_Orders_Public( $this->get_plugin_name(), $this->get_version() );

//		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
//		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

	}

	/**
	 * Register all of the hooks related to Woocommerce
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_woocommerce_hooks() {

		$woocommerce_hooks = new Block_Woo_Orders_Woocommerce_Hooks();
		$this->loader->add_action( 'woocommerce_checkout_order_processed', $woocommerce_hooks, 'scan_orders_for_fraud', 10, 3 );
		$this->loader->add_action( 'woocommerce_before_checkout_billing_form', $woocommerce_hooks, 'add_app_user_id_field', 10, 3 );
		$this->loader->add_action( 'woocommerce_checkout_process', $woocommerce_hooks, 'verify_app_user_id_field', 10, 3 );
		$this->loader->add_action( 'woocommerce_checkout_update_order_meta', $woocommerce_hooks, 'update_order_meta_app_user_id', 10, 3 );
		$this->loader->add_action( 'woocommerce_admin_order_data_after_billing_address', $woocommerce_hooks, 'display_admin_order_meta_app_user_id', 10, 3 );

	}

	private function set_custom_order_statuses() {

		$custom_order_statuses = new Block_Woo_Orders_Custom_Statuses();
		$this->loader->add_action( 'init', $custom_order_statuses, 'register_custom_statuses', 9 );
		$this->loader->add_filter( 'wc_order_statuses', $custom_order_statuses, 'add_custom_statuses_to_order' );
		$this->loader->add_filter( 'woocommerce_valid_order_statuses_for_payment', $custom_order_statuses, 'add_new_order_statuses', 10, 2 );

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @return    string    The name of the plugin.
	 * @since     1.0.0
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @return    Block_Woo_Orders_Loader    Orchestrates the hooks of the plugin.
	 * @since     1.0.0
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @return    string    The version number of the plugin.
	 * @since     1.0.0
	 */
	public function get_version() {
		return $this->version;
	}

}
