<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * WooCommerce Discount Rules - Add and Check CRM Conditional Settings
 *
 * @since      1.0.0
 * @package    multilit-woo
 * @subpackage multilit-woo/classes
 * @author     Curtis Krauter <curtis@businesstechninjas.com>
 */

use Wdr\App\Conditions\Base;

class multilit_woo_wdr_user_crm_access extends Base{
    function __construct(){
        parent::__construct();
        $this->name     = 'user_crm_access';
        $this->label    = __('User CRM Access', 'multilit-woo');
        $this->group    = __('Customer', 'multilit-woo');
        $this->template = MULTILIT_WOO_PARTIALS_DIR .'admin/wdr-user-crm-access.php';
    }

    public function check($cart, $options) {
        
        if( isset($options->value) && is_array($options->value) && !empty($options->value[0]) ){
            return multilit_woo()->memberium()->has_all_tags(trim($options->value[0],','));
        }

        return false;
    }
}