<?php
if (! defined('ABSPATH')) {
	header('HTTP/1.0 403 Forbidden');
	die();
}

/**
 * Attendee Class - Manage Attendee Push to Infusionsoft
 *
 * @since      1.0.0
 * @package    multilit-woo
 * @subpackage multilit-woo/classes
 * @author     Curtis Krauter <curtis@businesstechninjas.com>
 */
class multilit_woo_attendee {
	
	/**
	 * Custom Order Action to Trigger Attendee Push to IS
	 *
	 * @param array $actions
	 * @return array
	 */
	static function woocommerce_order_actions_multilit_attendee($actions){
		
		global $theorder;
		$ticket_meta = self::get_ticket_meta($theorder);
		
		if ( ! $ticket_meta ) {
			return $actions;
		}
		
		if( $ticket_meta ){
			$actions['push_multilit_attendee'] = __( 'Add Attendee(s) To Infusionsoft', 'multilit-woo' );
		}
		
		return $actions;
	}
	
	/**
	 * Add Attendee To Infusionsoft when order action is clicked
	 *
	 * @param WC_Order $order
	 */
	static function push_multilit_attendee_listener( $order ) {
		$order_id  = $order->get_id();
		$attendees = self::get_order_attendees($order_id);
		if( $attendees ){
			self::push_order_attendees_to_crm( $attendees, $order_id );
		}
	}
	
	/**
	 * Add Attendee To Infusionsoft when order action is clicked
	 *
	 * @param int $order_id
	 * @param string $status
	 */
	static function order_edit_status( $order_id, $status ){
		self::maybe_push_attendees($order_id);
	}
	
	/**
	 * Order Status Processing || Pending Payment
	 *
	 * @param mixed (Order ID || Order Object) WooCommerce Order Reference
	 */
	static function maybe_push_attendees( $order ){
		
		if (is_int($order)) {
			$order_id = $order;
			$order    = wc_get_order( $order_id );
		}
		else{
			$order_id = $order->get_id();
		}
		
		$ticket_meta = self::get_ticket_meta($order);
		if( $ticket_meta ){
			$pushed_attendees = (int)$order->get_meta('_multilit/woo/pushed/attendees');
			$status           = $order->get_status();
			$allowed_statuses = ['processing', 'pending', 'completed'];
			if( ! $pushed_attendees && in_array($status, $allowed_statuses) ){
				$attendees = self::get_order_attendees($order_id, $ticket_meta);
				if($attendees){
					self::push_order_attendees_to_crm( $attendees, $order_id );
				}
			}
		}
	
	}
	
	/**
	 * Get Ticket Metadata
	 * 
	 * @param WC_Order $order
	 * @return mixed (array || false)
	 */
	static function get_ticket_meta($order){
		$ticket_meta = $order->get_meta('_tribe_tickets_meta');
		return ( is_array($ticket_meta) && !empty($ticket_meta) ) ? $ticket_meta : false;
	}
	
	/**
	 * Push Order Attendees To CRM
	 *
	 * @param array $attendees
	 * @param int $order_id
	 * @return void
	 */
	static function push_order_attendees_to_crm( $attendees, $order_id ){

		$order           = wc_get_order( $order_id );
		$purchaser_first = $order->get_billing_first_name();
		$purchaser_last  = $order->get_billing_last_name();
		$purchaser_name  = "{$purchaser_first} {$purchaser_last}";
		$purchaser_email = $order->get_billing_email();
		$school          = $order->get_billing_company();
		$settings        = multilit_woo()->get_settings();
		
		// Infusionsoft Field Mapping
		$contact_map = [
			'first-name'    => 'FirstName',
			'last-name'     => 'LastName',
			'email'         => 'Email',
			'job-title'     => !empty($settings['attendee_job'])    ? $settings['attendee_job']    : '',
			'mobile-number' => !empty($settings['attendee_mobile']) ? $settings['attendee_mobile'] : '',
			'ticket_name'   => !empty($settings['ticket_name'])     ? $settings['ticket_name']     : '',
			'ticket_date'   => !empty($settings['ticket_date'])     ? $settings['ticket_date']     : '',
		];
		
		$purchaser_name_field   = !empty($settings['purchaser_name'])   ? $settings['purchaser_name']   : '';
		$purchaser_email_field  = !empty($settings['purchaser_email'])  ? $settings['purchaser_email']  : '';
		$purchaser_school_field = !empty($settings['purchaser_school']) ? $settings['purchaser_school'] : '';
		$purchaser_tags         = [];
		$added                  = [];
		
		if( is_array($attendees) && ! empty($attendees) ){
			foreach ($attendees as $a => $attendee) {
				
				$contact = [];
				
				// Map Attendee Data
				foreach ($contact_map as $meta_name => $field) {
					if( $field > '' && array_key_exists($meta_name, $attendee) && $attendee[$meta_name] > '' ){
						$contact[$field] = $attendee[$meta_name];
					}
				}
				
				// Purchaser Data
				if( $purchaser_name_field > '' && $purchaser_name > '' ){
					$contact[$purchaser_name_field] = $purchaser_name;
				}
				if( $purchaser_email_field > '' && $purchaser_name > '' ){
					$contact[$purchaser_email_field] = $purchaser_email;
				}
				if( $purchaser_school_field > '' && $school > '' ){
					$contact[$purchaser_school_field] = $school;
				}
				
				$event_tags            = self::get_event_tags($attendee['ticket_id']);
				$attendee_ticket_tags  = !empty($event_tags['ticket_attendee_tag'])  ? $event_tags['ticket_attendee_tag']  : [];
				$purchaser_ticket_tags = !empty($event_tags['ticket_purchaser_tag']) ? $event_tags['ticket_purchaser_tag'] : [];
				$purchaser_tags        = wp_parse_args($purchaser_ticket_tags, $purchaser_tags);
				if( ! empty($contact) && array_key_exists('Email', $contact) ){
					$contact_id = multilit_woo()->memberium()->add_with_dup_check($contact, $settings['optin']);
					if( $contact_id ){
						$added[$contact_id] = $contact['Email'];
						// Manage Attendee Tags
						if( !empty($attendee_ticket_tags) ){
							multilit_woo()->memberium()->set_tags( $attendee_ticket_tags, $contact_id );
						}
					}
				}
			}
		}
		
		// Manage Purchaser Tags
		if( count($purchaser_tags) > 0 && $purchaser_email > ''){
			self::manage_purchaser_tags( $purchaser_tags, $purchaser_email );
		}
		// Add Order Note
		if( ! empty($added) ){
			$note = __( 'Attendee(s) added to Infusionsoft', 'multilit-woo' );
			foreach ($added as $id => $email) {
				$note .= '<br/>';
				$url = multilit_woo()->memberium()->contact_url($id);
				$note .= "<a href=\"{$url}\" target=\"_blank\">{$email}</a>";
			}
			$order->add_order_note( $note );
			$order->update_meta_data( '_multilit/woo/pushed/attendees', 1 );
		}
	
	}
	
	/**
	 * Get Event CRM Tags
	 *
	 * @param int $product_id
	 * @return array
	 */
	static function get_event_tags( $product_id ){
		$tags = [];
		if( (int)$product_id > 0 ){
			$meta = get_post_meta($product_id, '_multilit/crm', true);
			if( is_array($meta) ){
				foreach ($meta as $key => $value) {
					$tags[$key] = ( $value > '' ) ? explode(',', trim($value, ',') ) : [];
				}
			}
		}
		return $tags;
	}
	
	/**
	 * Manage Purchaser Tags
	 * 
	 * @param array $tags
	 * @param string $email
	 * @return void
	 */
	static function manage_purchaser_tags( $tags, $email ){
		$tags       = array_unique($tags);
		$contact_id = multilit_woo()->memberium()->contact_id_by_email($email);
		if( ! $contact_id ){
			return;
		}
		multilit_woo()->memberium()->set_tags( $tags, $contact_id );
		
		return;
	}
	
	/**
	 * Return all attendees for events in a WooCommerce order
	 * 
	 * @param int $order_id - WooCommerce order ID
	 * @param mixed ( array || false ) $ticket_meta - Ticket meta data
	 * @return mixed ( array || false )
	 */
	static function get_order_attendees( $order_id, $ticket_meta = false ) {
		
		$attendees   = false;
		$order       = wc_get_order( $order_id );
		$ticket_meta = ($ticket_meta) ? $ticket_meta : self::get_ticket_meta($order);
		
		if( $ticket_meta ){
			$attendees = [];
			foreach ($ticket_meta as $ticket_id => $tickets) {
				$ticket_name = get_the_title($ticket_id);
				$ticket_date = multilit_woo()->tickets()->get_event_start_date($ticket_id);
				if( is_array($tickets) && !empty($tickets) ){
					foreach ($tickets as $ticket) {
						$attendee                = $ticket;
						$attendee['ticket_id']   = $ticket_id;
						$attendee['ticket_name'] = $ticket_name;
						$attendee['ticket_date'] = $ticket_date;
						$attendees[]             = $attendee;
					}
				}
			}
		}
		
		return $attendees;
	}

}