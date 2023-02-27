<?php 
/**
 * WooCommerce Product ( Event Ticket ) Meta - CRM Conditional Settings
 * 
 * @since      1.0.0
 * @package    multilit-woo
 * @subpackage multilit-woo/partials/admin
 * @author     Curtis Krauter <curtis@businesstechninjas.com>
 */
?>
<div id="multilit_woo_crm_data" class="panel woocommerce_options_panel">
    <h4 class="accordion-label screen_reader_text">
        <?php _e('CRM Settings', 'multilit-woo'); ?>
    </h4>
<?php // Loop through the ticket config and output the fields
foreach ( $this->get_ticket_config() as $key => $text ) { 
    $requires_kit = ( $key === 'requires_kit' );
    $css_class    = ( $requires_kit ) ? 'woo-kit-products-select2' : 'contact-tags-select2';
    $value        = ! empty($meta[$key]) ? $this->cleanse_select2s($meta[$key]) : '';
?>
    <div class="options_group">';
        <p class="form-field">
            <label class="ticket_form_label" for="<?php echo $key; ?>">
                <?php echo $text['label']; ?>
            </label>
            <input multiple id="<?php echo $key; ?>" type="text" class="<?php echo $css_class; ?>" value="<?php echo $value; ?>" name="<?php echo $key; ?>" />
            <p>
                <?php echo $text['desc']; ?>
            </p>
        </p>
    </div>
<?php } // End foreach ?>

    <div class="options_group">
        <p class="form-field">
            <label>
                <?php _e('Ticket Event', 'multilit-woo'); ?>
            </label>
            <span class="ticket_event_link">
                <a href="<?php echo $link; ?>">
                    <?php echo $title; ?>
                </a>
            </span>
        </p>
    </div>

</div>