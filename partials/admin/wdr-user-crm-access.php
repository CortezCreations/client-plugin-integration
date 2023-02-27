<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * WooCommerce Discount Rules - CRM Conditional Settings
 *
 * @since      1.0.0
 * @package    multilit-woo
 * @subpackage multilit-woo/partials/admin
 * @author     Curtis Krauter <curtis@businesstechninjas.com>
 */

$operator = isset($options->operator) ? $options->operator : 'in_list';
$value    = !empty($options->value) ? implode(',',$options->value) : '';
echo ($render_saved_condition == true) ? '' : '<div class="user_crm_access">';
?>
<div class="wdr_user_crm_access_group wdr-condition-type-options">
    <div class="wdr-select-filed-hight multilit-woo-crm_access-container">
        <input multiple
            class="wdr_user_crm_access contact-tags-select2"
            type="text"
            data-multilit-woo-index="<?php echo (isset($i)) ? $i : '{i}' ?>"
            value="<?php echo $value; ?>"
            data-placeholder="<?php _e('Search Access Tags', 'multilit-woo');?>"
            name="conditions[<?php echo (isset($i)) ? $i : '{i}' ?>][options][value][]"
        />
        <span class="wdr_select2_desc_text"><?php _e('Select Access Tag', 'multilit-woo'); ?></span>
    </div>
</div>
<?php echo ($render_saved_condition == true) ? '' : '</div>'; ?>
