<?php 
/**
 * Tribe Ticket Meta Accordion - CRM Settings
 *
 * @since      1.0.0
 * @package    multilit-woo
 * @subpackage multilit-woo/partials/admin
 * @author     Curtis Krauter <curtis@businesstechninjas.com>
 */
?>

<button class="accordion-header tribe_crm_meta">
    <?php _e('CRM Settings', 'multilit-woo'); ?>
</button>

<section id="ticket_form_crm" class="crm accordion-content">
    <h4 class="accordion-label screen_reader_text">
        <?php _e('CRM Settings', 'multilit-woo'); ?>
    </h4>
    
<?php // Loop Through Ticket Settings Config
foreach ($this->get_ticket_config() as $key => $text) {
    $requires_kit = ( $key === 'requires_kit' );
    $value        = !empty($meta[$key]) ? $this->cleanse_select2s($meta[$key]) : '';
    $css_class    = ( $requires_kit ) ? 'woo-kit-products-select2' : 'contact-tags-select2';
?>

    <div class="input_block">
        <label class="ticket_form_label ticket_form_left mlw-ticket_label" for="<?php echo $key; ?>">
            <?php echo $text['label']; ?>
        </label>
        <input multiple id="<?php echo $key; ?>" type="text" class="<?php echo $css_class; ?>" value="<?php echo $value; ?>" name="<?php echo $key; ?>" />
        <p>
            <?php echo $text['desc']; ?>
        </p>
    </div>
    <?php // Check if Loaded Via AJAX and Initialize Select2
    if( wp_doing_ajax() ){
        $data_type = ( $requires_kit ) ? 'woo_kit_products' : 'contact_tags';
        echo "<script>window.multilitSelect(\"#{$key}\", \"{$data_type}\");</script>";
    }
} // End foreach ?>

</section>