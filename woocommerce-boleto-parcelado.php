<?php
/**
 * Plugin Name: WooCommerce Boleto Parcelado
 * Plugin URI: https://github.com/matheusgimenez/woocommerce-boleto-parcelado
 * Description:
 * Author: matheusgimenez
 * Author URI: http://codeispoetry.info
 * Version: 0.1
 * License: GPLv2 or later
 * Text Domain: woocommerce-boleto-parcelado
 * Domain Path: /languages/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Boleto_Parcelado' ) ) :

/**
 * WooCommerce Boleto main class.
 */
class WC_Boleto_Parcelado {

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	const VERSION = '0.1';

	/**
	 * Instance of this class.
	 *
	 * @var object
	 */
	protected static $instance = null;

	/**
	 * Initialize the plugin actions.
	 */
	private function __construct() {
		// Load plugin text domain
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		// Checks with WooCommerce is installed.
		if ( class_exists( 'WC_Payment_Gateway' ) ) {
			// Public includes.
			$this->includes();

			// Admin includes.
			if ( is_admin() ) {
				$this->admin_includes();
			}

			add_filter( 'woocommerce_payment_gateways', array( $this, 'add_gateway' ) );
			add_action( 'init', array( $this, 'add_boleto_endpoint' ) );
			add_action( 'template_include', array( $this, 'boleto_template' ) );
			add_action( 'woocommerce_view_order', array( $this, 'pending_payment_message' ) );
			add_action( 'woocommerce_order_details_after_order_table', array( $this, 'pending_payment_message' ) );
			add_action( 'woocommerce_get_formatted_order_total', array( $this, 'show_price' ) );
		} else {
			add_action( 'admin_notices', array( $this, 'woocommerce_missing_notice' ) );
		}
	}

	/**
	 * Return an instance of this class.
	 *
	 * @return object A single instance of this class.
	 */
	public static function get_instance() {
		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @return void
	 */
	public function load_plugin_textdomain() {
		$locale = apply_filters( 'plugin_locale', get_locale(), 'woocommerce-boleto-parcelado' );

		load_textdomain( 'woocommerce-boleto-parcelado', trailingslashit( WP_LANG_DIR ) . 'woocommerce-boleto/woocommerce-boleto-' . $locale . '.mo' );
		load_plugin_textdomain( 'woocommerce-boleto-parcelado', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * Includes.
	 *
	 * @return void
	 */
	private function includes() {
		include_once 'includes/class-wc-boleto-gateway.php';
	}

	/**
	 * Includes.
	 *
	 * @return void
	 */
	private function admin_includes() {
		require_once 'includes/class-wc-boleto-admin.php';
	}

	/**
	 * Add the gateway to WooCommerce.
	 *
	 * @param  array $methods WooCommerce payment methods.
	 *
	 * @return array          Payment methods with Boleto.
	 */
	public function add_gateway( $methods ) {
		$methods[] = 'WC_Boleto_Parcelado_Gateway';

		return $methods;
	}

	/**
	 * Created the boleto endpoint.
	 *
	 * @return void
	 */
	public function add_boleto_endpoint() {
		add_rewrite_endpoint( 'boleto-parcelado', EP_PERMALINK | EP_ROOT );
	}

	/**
	 * Plugin activate method.
	 *
	 * @return void
	 */
	public static function activate() {
		// Add the boleto endpoint.
		add_rewrite_endpoint( 'boleto-parcelado', EP_PERMALINK | EP_ROOT );

		flush_rewrite_rules();
	}

	/**
	 * Plugin deactivate method.
	 *
	 * @return void
	 */
	public static function deactivate() {
		flush_rewrite_rules();
	}

	/**
	 * Add custom template page.
	 *
	 * @param   [varname] [description]
	 *
	 * @return string
	 */
	public function boleto_template( $template ) {
		global $wp_query;

		if ( isset( $wp_query->query_vars['boleto-parcelado'] ) ) {
			return plugin_dir_path( __FILE__ ) . 'templates/boleto.php';
		}

		return $template;
	}

	/**
	 * Gets the boleto URL.
	 *
	 * @param  string $code Boleto code.
	 *
	 * @return string       Boleto URL.
	 */
	public static function get_boleto_url( $code ) {
		$home = esc_url( home_url( '/' ) );

		if ( get_option( 'permalink_structure' ) ) {
			return trailingslashit( $home ) . 'boleto-parcelado/' . $code;
		} else {
			return add_query_arg( array( 'boleto-parcelado' => $code ), $home );
		}
	}

	/**
	 * Display pending payment message in order details.
	 *
	 * @param  int $order_id Order id.
	 *
	 * @return string        Message HTML.
	 */
	public function show_price( $subtotal ) {
		if(!$order_id = get_query_var( 'view-order'))
			return $subtotal;

		$order = new WC_Order($order_id);
		if ( 'boleto-parcelado' == $order->payment_method ) {
			$infos = get_post_meta( $order->id, 'wc_boleto_infos', true );
			if(!$infos || empty($infos))
				return $subtotal;

			return $subtotal . ' [ ' . sprintf(__('%sx of %s','woocommerce-boleto-parcelado'), $infos['plots'], wc_price($infos['value'])) . ' ] ';

		}
		return $subtotal . 'ahoy';
	}

	/**
	 * Show price with plugin
	 *
	 */
	public function pending_payment_message( $order_id ) {
		$order = new WC_Order( $order_id );

		if ( 'on-hold' === $order->status && 'boleto-parcelado' == $order->payment_method ) {
			$html = '<div class="woocommerce-info">';
			$html .= sprintf( '<a class="button" href="%s" target="_blank" style="display: block !important; visibility: visible !important;">%s</a>', self::get_boleto_url( $order->order_key ), __( 'Pay the Ticket &rarr;', 'woocommerce-boleto-parcelado' ) );

			$message = sprintf( __( '%sAttention!%s Not registered the payment the docket for this product yet.', 'woocommerce-boleto-parcelado' ), '<strong>', '</strong>' ) . '<br />';
			$message .= __( 'Please click the following button and pay the Ticket in your Internet Banking.', 'woocommerce-boleto-parcelado' ) . '<br />';
			$message .= __( 'If you prefer, print and pay at any bank branch or lottery retailer.', 'woocommerce-boleto-parcelado' ) . '<br />';
			$message .= __( 'Ignore this message if the payment has already been made​​.', 'woocommerce-boleto-parcelado' ) . '<br />';

			$html .= apply_filters( 'wcboleto_pending_payment_message', $message, $order );

			$html .= '</div>';

			echo $html;
		}
	}
	/**
	 * WooCommerce fallback notice.
	 *
	 * @return string
	 */
	public function woocommerce_missing_notice() {
		echo '<div class="error"><p>' . sprintf( __( 'WooCommerce Boleto Gateway depends on the last version of %s to work!', 'woocommerce-boleto-parcelado' ), '<a href="http://wordpress.org/extend/plugins/woocommerce/">' . __( 'WooCommerce', 'woocommerce-boleto-parcelado' ) . '</a>' ) . '</p></div>';
	}
}

/**
 * Plugin activation and deactivation methods.
 */
register_activation_hook( __FILE__, array( 'WC_Boleto_Parcelado', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'WC_Boleto_Parcelado', 'deactivate' ) );

/**
 * Initialize the plugin.
 */
add_action( 'plugins_loaded', array( 'WC_Boleto_Parcelado', 'get_instance' ), 0 );

endif;

/**
 * Assets URL.
 *
 * @return string
 */
function wcboleto_parcelado_assets_url() {
	return plugin_dir_url( __FILE__ ) . 'assets/';
}
