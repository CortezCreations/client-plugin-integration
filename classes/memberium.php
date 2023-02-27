<?php
if (! defined('ABSPATH')) {
	header('HTTP/1.0 403 Forbidden');
	die();
}

/**
 * Memberium Plugin Integration Class
 *
 * @since      1.0.0
 * @package    multilit-woo
 * @subpackage multilit-woo/classes
 * @author     Curtis Krauter <curtis@businesstechninjas.com>
 * @NOTE : Tested up to Memberium Version 2.150.11
 */
class multilit_woo_memberium {
	
	/**
	 * @var object $app i2sdk
	 */
	private $app = null;
	
	/**
	 * @var array $contact_fields_map
	 */
	private $contact_fields_map = null;
	
	/**
	 * @var array $tag_map
	 */
	private $tag_map = null;
	
	/**
	 * Has Post Access - API Function to check if a user has access to a post
	 */
	function has_post_access( $post_id ){
		
		if( function_exists('memb_hasPostAccess') ){
			if( ! memb_hasPostAccess($post_id) ){
				$post_id = false;
			}
		}
		return $post_id;
	}
	
	/**
	 * Has Tag Access - API Function to check if user passes tag condition
	 * 
	 * @param mixed ( int, string, array ) $tags - IDs For CRM Tags
	 */
	function has_tag_access( $tags ){
		
		$has_access = false;
		if( function_exists('memb_hasAnyTags') ){
			// Check for all negative values and reverse
			$is_all_negatives = $this->all_negatives($tags);
			if( $is_all_negatives ){
				foreach ($is_all_negatives as $key => $tag) {
					if(memb_hasAnyTags($tag)){
						return false;
					}
				}
				return true;
			}
			else{
				$has_access = memb_hasAnyTags($tags);
			}
		}
		
		return $has_access;
	}
	
	/**
	 * Has All Tags - API Function to check if user passes ALL tag conditions
	 * 
	 * @param mixed ( int, string, array ) $tags - IDs For CRM Tags
	 */
	function has_all_tags( $tags ){
		
		$has_access = false;
		if( function_exists('memb_hasAllTags') ){
			// Check for all negative values and reverse
			$is_all_negatives = $this->all_negatives($tags);
			if( $is_all_negatives ){
				foreach ($is_all_negatives as $key => $tag) {
					if(memb_hasAllTags($tag)){
						return false;
					}
				}
				return true;
			}
			else{
				$has_access = memb_hasAllTags($tags);
			}
		}
		
		return $has_access;
	}
	
	/**
	 * Get CRM Contact field value for current user
	 * 
	 * @param string $fieldname - Name of the field to get
	 * @return string $field_value - Value of the field
	 */
	function get_contact_field( $fieldname ){
		$field_value = '';
		if( function_exists('memb_getContactField') ){
			$field_value = memb_getContactField($fieldname);
		}
		return $field_value;
	}
	
	/**
	 * Set CRM Contact field value
	 * 
	 * @param string $field_name - Name of the field to set
	 * @param string $value - Value of the field
	 * @param int $contact_id - ID of the contact
	 * @return void
	 */
	function set_contact_field( $field_name, $value, $contact_id = 0 ){
		if( empty($contact_id) ){
			$contact_id = memb_getContactId();
		}
		if( ! empty($contact_id) ){
			if( function_exists('memb_setContactField') ){
				memb_setContactField($field_name, $value, $contact_id);
			}
		}
	}
	
	/**
	 * Get CRM Contact ID by WP User ID
	 * 
	 * @return int $contact_id - ID of the contact
	 */
	function contact_id_by_user_id( $user_id ){
		$contact_id = '';
		if( function_exists('memb_getContactIdByUserId') ){
			$contact_id = memb_getContactIdByUserId($user_id);
		}
		return $contact_id;
	}
	
	/**
	 * Get Global i2sdk Object
	 *
	 * @return object - i2sdk
	 */
	function i2sdk(){
		
		if( is_null($this->app) ){
			
			if( array_key_exists('i2sdk', $GLOBALS) ){
				$this->app = $GLOBALS['i2sdk'];
			}
			else {
				$this->app = false;
			}
		}
		
		return $this->app;
	}
	
	/**
	 * i2sdk Add With DupCheck - Adds a New Contact to the CRM checking Email first for duplicate
	 * 
	 * @param array $contact	Contact Fields => Value array
	 * @param string $optin		Optin Details
	 * @return mixed (bool|int) $contact_id
	 */
	function add_with_dup_check( $contact, $optin = false ){
		$contact_id = false;
		$i2sdk      = $this->i2sdk();
		$contact    = ( is_array($contact) && ! empty($contact) ) ? $contact : [];
		$email      = ( isset($contact['Email']) && $contact['Email'] > '' ) ? $contact['Email'] : false;
		$optin      = ( $optin ) ? sanitize_text_field($optin) : false;
		
		if( $i2sdk && $email ){
			$contact_id = $i2sdk->isdk->addWithDupCheck( $contact, 'Email' );
			if( $contact_id && $optin ){
				$i2sdk->isdk->optIn($email, $optin);
			}
		}
		
		return $contact_id;
	}
	
	/**
	 * Set CRM Tags for Contact
	 * 
	 * @param mixed ( int, string, array ) $tags - IDs For CRM Tags
	 * @param int $contact_id - ID of the contact
	 * @return void
	 */
	function set_tags( $tags, $contact_id ){
		
		if( $tags > '' && $contact_id > '' ){
			if( function_exists('memb_setTags') ){
				memb_setTags($tags, $contact_id);
			}
			else{
				multilit_woo()->write_log('Error : Func: '.__FUNCTION__.' Memberium function memb_setTags no longer exists');
			}
		}
	
	}
	
	/**
	 * Get CRM Contact ID by Email
	 * 
	 * @param string $email - Email of the contact
	 * @return mixed (int|false) $contact_id - ID of the contact
	 */
	function contact_id_by_email( $email ){
		$contact_id = false;
		$i2sdk      = $this->i2sdk();
		if( ! empty($email) && $i2sdk ){
			$data = $i2sdk->isdk->findByEmail($email, ['Id']);
			if (is_array($data) ) {
				$contact_id = empty($data[0]['Id']) ? false : $data[0]['Id'];
			}
		}
		
		return $contact_id;
	}
	
	/**
	 * Get Tag Data Map - Generates ID, Text array for Select2
	 *
	 * @param bool $negatives - Include Negative ( Remove ) Tags in the dataset
	 * @param string $negative_sprintf - Alternative sprintf string
	 * @return array 
	 */
	function get_tags_map( $negatives = true, $negative_sprintf = '' ){
		
		$cache_key = 'multilit/woo/tags/all/' . md5(serialize(func_get_args()));
		if( isset($this->tag_map[$cache_key]) ){
			return $this->tag_map[$cache_key];
		}
		
		$tags    = false;
		$tag_map = [];
		
		if( function_exists('memb_getTagMap') ){
			$tags = memb_getTagMap(true,$negatives);
		}
		
		if( $tags ){
			if( $negatives ){
				if( ! empty($negative_sprintf) ){
					$negative_sprintf = __('Remove %s (- %s)');
				}
			}
			$tags = ( isset($tags['mc']) ) ? $tags['mc'] : [];
			foreach ( $tags as $id => $tag ) {
				$tag_map[] = [
					'id' 	=> $id,
					'text'	=> $tag . ' (' . $id . ')'
				];
				if( $negatives ){
					$tag_map[] = [
						'id' 	=> '-' . $id,
						'text'	=> sprintf($negative_sprintf, $tag, $id)
					];
				}
			}
			
			$this->tag_map[$cache_key] = $tag_map ? $tag_map : [];
		}
		
		return $this->tag_map[$cache_key];
	}
	
	/**
	 * Get Contact Field Data Map for Select2
	 * 
	 * @return array 
	 */
	function get_contact_fields_map(){
		
		if( is_null($this->contact_fields_map) ){
			$contact_fields = [];
			$valid_fields = [];
			if( function_exists('memb_getContactFieldsMap') ){
				$valid_fields = memb_getContactFieldsMap();
			}
			
			if( !empty($valid_fields) && is_array($valid_fields) ){
				foreach ($valid_fields as $key => $valid_field) {
					$contact_fields[] = [
						'id'   => $valid_field,
						'text' => $valid_field
					];
				}
			}
			
			$this->contact_fields_map = $contact_fields;
		}
		
		return $this->contact_fields_map;
	}
	
	/**
	 * Checks if all values of array are negative
	 * 
	 * @param mixed (array|string|int) $tags - Array of tags or comma separated string
	 * @return mixed (array|false) $return - Array of positive tags or false
	 */
	function all_negatives($tags){
		$return = false;
		if( ! is_array($tags) ){
			$tags = ( $tags > '' ) ? explode(',', trim($tags, ',') ) : [];
		}
		if( ! empty($tags) ){
			$negatives = 0;
			$positive_array = [];
			foreach ($tags as $t => $tag) {
				if( substr($tag, 0, 1) === '-' ){
					$negatives ++;
					$tag = abs($tag);
				}
				$positive_array[] = $tag;
			}
			if( $negatives === count($tags) ){
				$return = $positive_array;
			}
		}
		
		return $return;
	}
	
	/**
	 * Get CRM Contact URL
	 * 
	 * @param int $contact_id - ID of the contact or %s for sprintf placeholder
	 * @return string $url - URL of the contact
	 */
	function contact_url( $contact_id = '%s' ){
		$appName = memb_getAppName();

		return "https://{$appName}.infusionsoft.com/Contact/manageContact.jsp?view=edit&ID={$contact_id}";
	}
	
	// Returns the instance.
	private function __construct(){}
	public static function get_instance() {
		static $instance = false;
		return $instance ? $instance : $instance = new self();
	}

}