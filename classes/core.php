<?php
if (! defined('ABSPATH')) {
	header('HTTP/1.0 403 Forbidden');
	die();
}

/**
 * Main Plugin Class
 *
 * @since      1.0.0
 * @package    multilit-woo
 * @subpackage multilit-woo/classes
 * @author     Curtis Krauter <curtis@businesstechninjas.com>
 */
class multilit_woo_core {
	
	/**
	 * Add Wordpress Hooks ( Actions / Filters )
	 * 
	 * @return void
	 */
	function add_hooks(){
		
		// Ensure WooCommerce and Memberium are Active
		if( ! class_exists( 'WooCommerce' ) && ! defined('MEMBERIUM_SKU') ) {
			return;
		}
		
		// Text Domain / Localization
		$this->load_text_domain();
		
		// Init Hooks
		add_action('init',[$this,'init_hooks']); 
		
		// WooCommerce Cart Hooks
		add_action('woocommerce_remove_cart_item', ['multilit_woo_frontend', 'remove_cart_item_check'], 10, 2);
		add_filter('woocommerce_add_cart_item_data', ['multilit_woo_frontend', 'add_required_kit_cart_item_data'], PHP_INT_MAX, 3);
		add_action('woocommerce_check_cart_items', ['multilit_woo_frontend', 'cart_item_requirement_check'] );
		
		// Woocommerce Attendee Creation Hooks
		add_action('woocommerce_thankyou', [ 'multilit_woo_attendee', 'maybe_push_attendees' ], 20, 1);
		add_action('woocommerce_order_status_changed', [ 'multilit_woo_attendee', 'order_edit_status' ], 20, 2);
		add_filter('woocommerce_order_actions', ['multilit_woo_attendee', 'woocommerce_order_actions_multilit_attendee']);
		add_action('woocommerce_order_action_push_multilit_attendee', ['multilit_woo_attendee', 'push_multilit_attendee_listener']);
		
		// Woocommerce Emails
		add_filter('woocommerce_locate_template', ['multilit_woo_admin','woocommerce_email_locate_template'], PHP_INT_MAX, 3);
		
		// Checkout Process
		add_action('woocommerce_checkout_process', ['multilit_woo_checkout', 'checkout_process'], PHP_INT_MAX, 1);
		add_filter('woocommerce_checkout_posted_data', ['multilit_woo_checkout', 'filter_checkout_posted_data'], PHP_INT_MAX, 1);
		add_action('woocommerce_checkout_update_order_meta', ['multilit_woo_checkout', 'update_order_meta'], PHP_INT_MAX, 2);
		add_filter('woocommerce_available_payment_gateways', ['multilit_woo_checkout', 'remove_checkout_payment_method']);
		add_action('woocommerce_checkout_update_order_meta', ['multilit_woo_checkout', 'check_payment_status_for_invoice'], 1, 1);
		add_filter('woocommerce_email_recipient_customer_completed_order', ['multilit_woo_checkout','email_recipient_filter_function'], 10, 2);

		// Xeroom Invoice Settings
		add_filter('xeroom_shipping_address', ['multilit_woo_checkout', 'xeroom_address'], 10, 3);
		add_filter('xeroom_new_invoice_data', ['multilit_woo_checkout', 'xeroom_new_invoice_data'], 10, 2);
		
		// Tribe Single Event Hooks
		add_action('tribe_events_single_event_after_the_meta',['multilit_woo_event_single','multilit_content_before_related_post']);
		add_action('tribe_events_single_meta_after',['multilit_woo_event_single','multilit_content_before_register_workshop']);
		
		// Woo Discount Rules
		add_action( 'advanced_woo_discount_rules_loaded', function() {
			add_filter('advanced_woo_discount_rules_conditions', ['multilit_woo_admin', 'addConditional']);
		});
		add_filter('advanced_woo_discount_rules_conditions', ['multilit_woo_admin', 'addConditional']);
	
	}
	
	/**
	 * Initialize Hooks only when required for Frontend and Admin
	 * 
	 * @return void
	 */
	function init_hooks(){
		
		$is_ajax_or_admin = ( wp_doing_ajax() || is_admin() );
		if( ! $is_ajax_or_admin ){
			$this->frontend()->add_wp_hooks();
		}
		else if( ! wp_doing_ajax() && is_admin() ){
			$this->admin()->add_wp_hooks();
		}
		else if( wp_doing_ajax() ){
			$action = ! empty($_POST['action']) ? $_POST['action'] : false;
			if( $action ){
				$tribe_actions = ['tribe-ticket-edit', 'tribe-ticket-add'];
				if( in_array($action, $tribe_actions) ){
					$this->admin()->add_wp_hooks();
				}
			}
		}
	}
	
	// Get Frontend Class Instance
	function frontend() : multilit_woo_frontend {
		static $frontend = false;
		return $frontend ? $frontend : $frontend = multilit_woo_frontend::get_instance();
	}
	
	// Get Admin Class Instance
	function admin() : multilit_woo_admin {
		static $admin = false;
		return $admin ? $admin : $admin = multilit_woo_admin::get_instance();
	}
	
	// Get Memberium Class Instance
	function memberium() : multilit_woo_memberium {
		static $memberium = false;
		return $memberium ? $memberium : $memberium = multilit_woo_memberium::get_instance();
	}
	
	// Get Tickets Class Instance
	function tickets() : multilit_woo_tribe_tickets {
		static $tickets = false;
		return $tickets ? $tickets : $tickets = multilit_woo_tribe_tickets::get_instance();
	}
	
	// Text Domain
	function load_text_domain(){
		load_plugin_textdomain('multilit-woo', false, MULTILIT_WOO_DIR . '/languages' );
	}
	
	// Get Plugin Settings
	function get_settings(){
		static $settings = false;
		if( ! $settings ){
			$defaults = [
				'purchaser_name'  => '',
				'purchaser_email' => '',
				'ticket_name'     => '',
				'ticket_date'     => '',
				'optin'           => ''
			];
			$options  = get_option( 'multilit/crm/options', $defaults );
			$settings = wp_parse_args($options, $defaults);
		}
		return $settings;
	}
	
	// Returns the instance.
	private function __construct(){}
	public static function get_instance() : self {
		static $instance = false;
		return $instance ? $instance : $instance = new self;
	}

}
