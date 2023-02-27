<?php
if (! defined('ABSPATH')) {
	header('HTTP/1.0 403 Forbidden');
	die();
}

/**
 * Main Admin Class For Multilit Woo
 * 
 * Integrates WooCommerce, Memberium, Events Tickets, and WooCommerce Discout Rules
 *
 * @since      1.0.0
 * @package    multilit-woo
 * @subpackage multilit-woo/classes
 * @author     Curtis Krauter <curtis@businesstechninjas.com>
 */
class multilit_woo_admin {
	
	/**
	 * Add Wordpress Hooks ( Actions & Filters )
	 */
	function add_wp_hooks() {
		
		add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
		// Tribe Events Admin Settings
		if ( class_exists( 'Tribe__Events__Main' ) ) {
			add_action('tribe_settings_do_tabs', [$this, 'add_tribe_settings_crm_tab']);
			add_action('admin_init', [$this, 'register_tribe_event_setting']);
		
			// Ticket Meta Box - Advanced Section
			add_action('tribe_events_tickets_metabox_edit_accordion_content', [$this, 'ticket_metabox_accordion'], 999, 2);
			add_action('event_tickets_after_save_ticket', [$this, 'event_ticket_save'], 10, 3);
			
			// Single Ticket Woocommerce Product
			add_filter('woocommerce_product_data_tabs', [$this, 'crm_woo_tab']);
			add_action('woocommerce_product_data_panels', [$this, 'crm_woo_panel']);
			add_action('woocommerce_process_product_meta', [$this, 'crm_woo_save']);
		}
		// Woo Order Custom Meta
		add_action('woocommerce_admin_order_data_after_billing_address', [$this, 'invoice_email_display']);
		
		// Add Accounts Payable Email Settings to User Profile
		add_action('show_user_profile', [$this, 'user_profile_accounts_payable_email'], 10, 1);
		add_action('edit_user_profile', [$this, 'user_profile_accounts_payable_email'], 10, 1);
		add_action('edit_user_profile_update', [$this, 'update_user_profile_accounts_payable_email']);
		add_action('personal_options_update', [$this, 'update_user_profile_accounts_payable_email']);
	}
	
	/**
	 * Tribe Events - Settings Page - Add CRM Tab
	 */
	function add_tribe_settings_crm_tab(){
		$crm_tab = $this->tribe_event_tab_config();
		$tab     = 'multilit-crm';
		add_action("tribe_settings_form_element_tab_{$tab}", [ $this, 'tribe_event_form_header' ] );
		add_action("tribe_settings_before_content_tab_{$tab}", [ $this, 'tribe_events_settings_fields' ] );
		new Tribe__Settings_Tab( $tab, esc_html__( 'CRM', 'multilit-woo' ), $crm_tab );
	}
	
	/**
	 * Tribe Events - Settings Page Register Settings and Validation Callback
	 */
	function register_tribe_event_setting(){
		register_setting( 'multilit/woo/crm', 'multilit/crm/options', [$this, 'sanitize_tribe_event_settings'] );
	}
	
	/**
	 * Tribe Event - Settings Page - Settings Fields
	 */
	function tribe_events_settings_fields() {
		settings_fields( 'multilit/woo/crm' );
	}
	
	/**
	 * Tribe Event - Sanitize Settings
	 * 
	 * @param array $settings - Posted Settings data
	 * @return array $output  - Sanitized Settings data
	 */
	function sanitize_tribe_event_settings( $settings ){
		$output = [];
		$crm_settings = $this->get_crm_settings();
		unset($crm_settings['optin']);
		$select2s = array_keys($crm_settings);
		foreach( $settings as $key => $value ) {
			if( !empty( $settings[$key] ) ) {
				// Select2
				if( in_array($key, $select2s) ){
					$output[$key] = $this->cleanse_select2s($value);
				}
				else {
					$output[$key] = sanitize_text_field($value);
				}
			}
		}
		return apply_filters('multilit/crm/options/validation', $output, $settings);
	}
	
	/**
	 * Tribe Event - Settings Page - Tab Configuration 
	 *
	 * @return array
	 */
	function tribe_event_tab_config() : array {
		return [
			'priority'      => 70,
			'show_save'     => false,
			'parent_option' => 'multilit/crm/options',
			'fields'        => [
				'info-start'         => [
					'type' => 'html',
					'html' => '<div id="modern-tribe-info">'
				],
				'title'              => [
					'type' => 'html',
					'html' => '<h2>' . esc_html__( 'CRM Integration Settings', 'multilit-woo' ) . '</h2>'
				],
				'blurb'              => [
					'type' => 'html',
					'html' => '<p>' . sprintf(
						wp_kses_post(
							__( 'Configure CRM contact fields for event tickets.', 'multilit-woo' )
						)
					) . '</p>'
				],
				'info-end'           => [
					'type' => 'html',
					'html' => '</div>'
				],
				'form-elements'      => [
					'type' => 'html',
					'html' => $this->tribe_form_elements()
				],
				'minicolors-console' => [
					'type' => 'html',
					'html' => '<div id="console"></div>'
				],
				'save-button'        => [
					'type' => 'html',
					'html' => '<p class="submit"><input type="submit" class="button-primary" value="' . esc_html__( 'Save Changes', 'multilit-woo' ) . '" /></p>'
				]
			]
		];
	}

	/**
	 * Tribe Event - Settings Page - Form Header
	 *
	 * @return string - HTML Form Header
	 */
	function tribe_event_form_header() {
		echo '<form method="post" action="options.php">';
	}
	
	/**
	 * Tribe Event - Setting Page - Form HTML
	 *
	 * @return string - HTML Form Fields
	 */
	function tribe_form_elements(){
		$option_name = 'multilit/crm/options';
		$options     = multilit_woo()->get_settings();
		ob_start();
		include_once MULTILIT_WOO_PARTIALS_DIR . 'admin/tribe-setttings-page.php';
		return ob_get_clean();
	}
	
	/**
	 * Tribe Event - Setting Page - CRM Mapping Fields
	 * @return array
	 */
	function get_crm_settings() : array {
		return [
			'purchaser_name'   => [
				'label' => __('Contact Name', 'multilit-woo'),
				'desc'  => __('The CRM Field to populate with the purchaser\'s full name.', 'multilit-woo')
			],
			'purchaser_email'  => [
				'label' => __('Contact Email', 'multilit-woo'),
				'desc'  => __('The CRM Field to populate with the purchaser\'s email address.', 'multilit-woo')
			],
			'purchaser_school' => [
				'label' => __('Contact School', 'multilit-woo'),
				'desc'  => __('The CRM Field to populate with the purchaser\'s school name.', 'multilit-woo')
			],
			'attendee_job'     => [
				'label' => __('Attendee Job Title', 'multilit-woo'),
				'desc'  => __('The CRM Field to populate with the attendee\'s job title.', 'multilit-woo')
			],
			'attendee_mobile'  => [
				'label' => __('Attendee Mobile', 'multilit-woo'),
				'desc'  => __('The CRM Field to populate with the attendee\'s mobile number.', 'multilit-woo')
			],
			'ticket_name'      => [
				'label' => __('Ticket Name', 'multilit-woo'),
				'desc'  => __('The CRM Field to populate with the ticket name.', 'multilit-woo')
			],
			'ticket_date'      => [
				'label' => __('Ticket Date', 'multilit-woo'),
				'desc'  => __('The CRM Field to populate with the ticket date.', 'multilit-woo')
			],
			'optin'            => [
				'label' => __('Opt-In Text', 'multilit-woo'),
				'desc'  => __('The text used for new Attendee\'s email opt-in description.', 'multilit-woo')
			]
		];
	}
	
	/**
	 * Tribe Event - Single Ticket - Acccordion Metabox for CRM Settings
	 *
	 * @param int $event_id  - Event ID
	 * @param int $ticket_id - Ticket ID
	 * @return string - HTML Accordion Metabox
	 */
	function ticket_metabox_accordion( $event_id = 0, $ticket_id = 0 ){
		$meta = $ticket_id > 0 ? get_post_meta($ticket_id, '_multilit/crm', true) : [];
		include_once MULTILIT_WOO_PARTIALS_DIR . 'admin/tribe-ticket-meta-crm.php';
	}
	
	/**
	 * WooCommerce - Single Ticket WooCommerce Product - Tab for CRM Settings
	 *
	 * @param array $tabs - WooCommerce Product Tabs
	 * @return array - WooCommerce Product Tabs
	 */
	function crm_woo_tab($tabs){
		
		global $post;
		if( is_object($post) && !empty($post->ID) ){
			$is_ticket = get_post_meta($post->ID, '_tribe_wooticket_for_event', true);
			if( $is_ticket ){
				$tabs['multilit_woo_crm'] = [
					'label'    => __('Ticket CRM', 'multilit-woo'),
					'icon'	   => 'dashicons-tickets-alt',
					'target'   => 'multilit_woo_crm_data',
					'class'    => [],
					'priority' => 77
				];
			}
		}

		return $tabs;
	}
	
	/**
	 * WooCommerce - Single Ticket WooCommerce Product - Panel for CRM Settings
	 *
	 * @return string - HTML Panel form fields
	 */
	function crm_woo_panel(){
		
		global $post;
		$post_id = ( is_object($post) && !empty($post->ID) && (int)$post->ID > 0 ) ? $post->ID : false;
		if( ! $post_id ){
			return;
		}
		$is_ticket = get_post_meta($post->ID, '_tribe_wooticket_for_event', true);
		if( ! $is_ticket ){
			return;
		}
		$ticket_id = $post->ID;
		$meta      = $ticket_id > 0 ? get_post_meta($ticket_id, '_multilit/crm', true) : [];
		$link      = get_edit_post_link($is_ticket);
		$title     = get_the_title($is_ticket);
		
		include_once MULTILIT_WOO_PARTIALS_DIR . 'admin/woocommerce-product-meta-panel-crm.php';
	
	}
	
	/**
	 * Event Tickets - Save Ticket Meta
	 *
	 * @param int $event_id - Event ID
	 * @param object $ticket - Ticket Object
	 * @param array $data - Posted Data
	 * @return void
	 */
	function event_ticket_save( $event_id, $ticket, $data ){
		$this->save_ticket_meta( $ticket->ID, $data );
		return;
	}
	
	/**
	 * Woocommerce - Save Ticket Meta
	 * 
	 * @param int $post_id - Post ID (Ticket ID)
	 * @return void
	 */
	function crm_woo_save( $post_id ){
		$this->save_ticket_meta( $post_id, $_POST );
		return;
	}
	
	/**
	 * Common - Save Ticket Meta
	 * @param int $ticket_id - Ticket ID
	 * @param array $data - Posted Data
	 * @return void
	 */
	function save_ticket_meta( $ticket_id, $data ){
		
		$meta = [];
		foreach ($this->get_ticket_config() as $key => $text) {
			if( array_key_exists($key, $data) ){
				$meta[$key] = $this->cleanse_select2s($data[$key]);
			}
		}
		
		if( empty($meta) ){
			return;
		}
		
		// Check if empty
		if( empty( array_filter($meta) ) ){
			// Delete stale meta
			if( get_post_meta($ticket_id, '_multilit/crm', true) ){
				delete_post_meta($ticket_id, '_multilit/crm');
			}
		}
		// Update Ticket Meta
		else{
			update_post_meta($ticket_id, '_multilit/crm', $meta);
		}
		
		return;
	}
	
	/**
	 * Sanatize Data from Select2 Before Saving
	*/
	function cleanse_select2s($value = ''){
		if( ! empty($value) ){
			$trimmed = trim($value,',');
			$value   = preg_replace('/\s+/', ' ', $trimmed);
		}
		return $value;
	}
	
	/**
	 * Enqueue JS & Styles & Localize Data for JS
	 * 
	 * @param string $hook - Current Page Hook
	 * @return void
	 */
	function enqueue_scripts( $hook ) {
		
		$load_tags = false;
		if( $hook === 'post.php' || $hook === 'post-new.php' ){
			$post_type = isset($_GET['post_type']) ? $_GET['post_type'] : false;
			if( ! $post_type ){
				$post_id = isset($_GET['post']) ? (int)$_GET['post'] : 0;
				$post_type = ( $post_id > 0 ) ? get_post_type($post_id) : false;
			}
			if( $post_type === 'tribe_events' || $post_type === 'product' ){
				$load_tags = true;
			}
		}
		
		// User Profile Screen
		$is_user_profile_hook = ( "user-edit.php" === $hook || "profile.php" === $hook );
		
		// Woo Discount Rules Admin
		$is_woo_discount_rules = ( "woocommerce_page_woo_discount_rules" === $hook );
		
		$e = "tribe_events_page_tribe";
		if ( "{$e}-events-calendar" !== $hook && "{$e}-common" !== $hook && ! $load_tags && ! $is_user_profile_hook && ! $is_woo_discount_rules ) {
			return false;
		}
		
		$v   = MULTILIT_WOO_VERSION;
		$url = MULTILIT_WOO_ASSESTS_URL;
		$dep = ['jquery'];
		
		if( ! $is_user_profile_hook || ! $is_woo_discount_rules ){
			// Select 2 @link https://github.com/woocommerce/selectWoo
			wp_enqueue_script('selectWoo',"{$url}js/selectWoo.full.min.js",$dep,'1.0.4');
			if( ! $load_tags ){
				wp_enqueue_style('selectWoo',"{$url}css/selectWoo.css",false,'5.7.2');
			}
			else {
				wp_enqueue_style('multilit-woo-metaboxes',"{$url}css/metaboxes.css",false,$v);
			}
		}
		
		// CSS for WDR Select2
		if( $is_woo_discount_rules ){
			wp_enqueue_style('multilit-woo-wdr',"{$url}css/wdr-admin.css",false,$v);
		}
		
		// Build Data for JS
		$data = [];
		// Contact Fields
		if( "{$e}-common" === $hook ){
			$data['contact_fields'] = multilit_woo()->memberium()->get_contact_fields_map();
		}
		
		// Events || Woo Ticket
		if( $load_tags ){
			$data['contact_tags']     = multilit_woo()->memberium()->get_tags_map();
			$data['woo_kit_products'] = $this->get_woo_kit_products();
		}
		
		// Woo Discount Rules Admin
		if( $is_woo_discount_rules ){
			$negative_sprintf         = __('Does Not Have %s (%s)', 'multilit-woo');
			$data['user_access_tags'] = multilit_woo()->memberium()->get_tags_map(true, $negative_sprintf);
		}
		
		if( $is_user_profile_hook ){
			$data['accounts_payable_email'] = [
				'toggle'  => 'invoice_default_email',
				'email'   => 'invoice_email',
				'default' => 'email'
			];
		}
		
		// Admin JS
		$handle = 'multilit_woo_admin';
		wp_enqueue_script($handle, "{$url}js/admin.js",$dep,$v,true);
		wp_localize_script($handle, "{$handle}_data", $data);
	}
	
	/**
	 * Get All Kits and Consumables Products - Taxonomy Query 
	 * 
	 * @return array - Product ID and Title Data for Select2
	 */
	function get_woo_kit_products(){
		$data = [];
		$kits = new WP_Query( [
			'post_type'             => 'product',
			'post_status'           => 'publish',
			'ignore_sticky_posts'   => 1,
			'fields'                => 'ids',
			'posts_per_page'        => -1,
			'tax_query'             => [
				[
					'taxonomy'      => 'product_cat',
					'field'         => 'term_id',
					'terms'         => 52,//Kits and Consumables
					'operator'      => 'IN'
				]
			]
		] );
		
		if( $kits->have_posts() ){
			$kit_ids = $kits->posts;
			foreach ($kit_ids as $kit_id) {
				$product = wc_get_product( $kit_id );
				if ( is_object( $product ) ) {
					$data[] = [
						'id' 	=> $kit_id,
						'text'	=> wp_kses_post( $product->get_formatted_name() )
					];
				}
			}
		}
		
		wp_reset_postdata();
		return $data;
	}
	
	/**
	 * WooCommerce Order - Get PO saved for an Order
	 *
	 * @param mixed ( object | ID ) $order 	The Order Object or Order ID
	 * @return string
	 */
	function get_order_po( $order ){
		$order          = is_object($order) ? $order : wc_get_order( $order );
		$order_id       = $order->get_id();
		$purchase_order = $order->get_meta("_purchase_order");
		$purchase_order = empty($purchase_order) ? __("None", 'multilit-woo') : $purchase_order;
		
		return sprintf( __("%s - PO: %s", 'multilit-woo'), $order_id, $purchase_order );
	}
	
	/**
	 * WooCommerce Order - Display Invoice Email on WooCommerce Order
	 * 
	 * @param object $order - WooCommerce Order Object
	 */
	function invoice_email_display( $order ){
		// Invoice Email
		$display_email = false;
		if( (int)$order->get_meta("_invoice_default_email") < 1 ){
			$order_invoice_email = $order->get_meta("_invoice_email");
			if( ! empty($order_invoice_email) ){
				$display_email = $order_invoice_email;
			}
		}
		if( $display_email ){
			$label = __('Accounts Payable Email', 'multilit-woo');
			echo "<p><strong>{$label}:</strong> {$display_email}</p>";
		}
		
		// Purchase Order
		$purchase_order = $this->get_order_po($order);
		$label = __('School PO', 'multilit-woo');
		echo "<p><strong>{$label}:</strong> {$purchase_order}</p>";
	}
	
	/**
	 * WP User Profile - Accounts Payable Email
	 *
	 * @param WP_User $profile_user - WooCommerce Order Fields
	 * @return string - HTML Settings Output
	 */
	function user_profile_accounts_payable_email( $profile_user ){
		
		$user_id    = $profile_user->ID;
		$user_email = $profile_user->data->user_email;
		
		// Checkbox
		$email_check_key = 'invoice_default_email';
		$default_checked = get_user_meta($user_id, $email_check_key, true);
		$default_checked = (int)$default_checked > 0;
		$checked_prop    = checked(1, (int)$default_checked, false);
		
		// Email
		$invoice_email_key = 'invoice_email';
		$invoice_email     = get_user_meta($user_id, $invoice_email_key, true);
		$invoice_email     = $default_checked || empty($invoice_email) ? $user_email : $invoice_email;
		$readonly          = $default_checked ? ' readonly' : '';
		
		// Needs CRM Sync Check ( If Infused Woo hasn't saved Contact ID in time )
		$needs_sync_key = 'multilit/sync/email';
		$needs_sync     = get_user_meta($user_id, $needs_sync_key, true);
		$needs_sync     = (int)$needs_sync > 0;
		
		if( $needs_sync > 0 ){
			$contact_id = multilit_woo()->memberium()->contact_id_by_user_id($user_id);
			multilit_woo()->memberium()->set_contact_field('EmailAddress3', $invoice_email, $contact_id);
			delete_user_meta($user_id, $needs_sync_key, 1);
		}
		
		include_once MULTILIT_WOO_PARTIALS_DIR . 'admin/user-profile-accounts-payable-email.php';
	}
	
	/**
	 * WP User Profile - Accounts Payable Email - Update
	 *
	 * @param int $user_id - User ID
	 */
	function update_user_profile_accounts_payable_email( $user_id ){
		
		// Checkbox
		$invoice_email_check_key        = 'invoice_default_email';
		$existing_default_email_checked = get_user_meta($user_id, $invoice_email_check_key, true);
		$existing_default_email_checked = (int)$existing_default_email_checked > 0;
		$posted_default_email_checked   = !empty($_POST[$invoice_email_check_key]) && (int)$_POST[$invoice_email_check_key] > 0;
		
		// Email
		$email_key              = 'invoice_email';
		$user_email             = get_userdata($user_id)->user_email;
		$existing_invoice_email = get_user_meta( $user_id, $email_key, true );
		$existing_invoice_email = ! $existing_invoice_email   ? $user_email        : $existing_invoice_email;
		$posted_invoice_email   = ! empty($_POST[$email_key]) ? $_POST[$email_key] : $user_email;
		
		// Set to Default Email
		if( $posted_default_email_checked ){
			update_user_meta($user_id, $invoice_email_check_key, 1);
			delete_user_meta($user_id, $email_key);
		}
		// Set to Custom Email
		else{
			update_user_meta($user_id, $email_key, $posted_invoice_email);
			delete_user_meta($user_id, $invoice_email_check_key);
		}
		
		// Update CRM
		if( $posted_invoice_email != $existing_invoice_email ){
			$contact_id = multilit_woo()->memberium()->contact_id_by_user_id($user_id);
			multilit_woo()->memberium()->set_contact_field('EmailAddress3', $posted_invoice_email, $contact_id);
		}
	
	}
	
	/**
	 * WooCommerce Discount Rules - Add CRM Conditional Settings
	 * 
	 * @param array $available_conditions - Available Conditions
	 * @return array $available_conditions - Available Conditions
	 */
	static function addConditional($available_conditions){
		
		$condition_object = new multilit_woo_wdr_user_crm_access();
		$rule_name = $condition_object->name();
		$available_conditions[$rule_name] = [
			'object'       => $condition_object,
			'label'        => $condition_object->label,
			'group'        => $condition_object->group,
			'template'     => $condition_object->template,
			'extra_params' => $condition_object->extra_params
		];
		
		return $available_conditions;
	}
	
	// Returns the instance.
	private function __construct(){}
	public static function get_instance() : self {
		static $instance = false;
		return $instance ? $instance : $instance = new self;
	}
}