<?php 
/**
 * Tribe Settings Page - CRM Field Mapping Settings
 *
 * @since      1.0.0
 * @package    multilit-woo
 * @subpackage multilit-woo/partials/admin
 * @author     Curtis Krauter <curtis@businesstechninjas.com>
 */
?>

<div id="multilit_woo_options" class="tribe-settings-form-wrap">
    <h3>
        <?php _e('Attendee CRM Field Mapping', 'multilit-woo'); ?>
    </h3>
    
    <?php // Loop Through CRM Settings Config
    foreach ($this->get_crm_settings() as $key => $setting) {
        $css_class = $key === 'optin' ? 'contact-optin' : 'contact-fields-select2';	
    ?>
    <?php if( $key === 'optin' ){ ?>
    <h3>
        <?php _e('Email Compliance', 'multilit-woo'); ?>
    </h3>
    <?php } //END if optin ?>
    
    <fieldset id="tribe-field-<?php echo $key; ?>" class="tribe-field">
        <legend class="tribe-field-label">
            <?php echo $setting['label']; ?>
        </legend>
        <div class="tribe-field-wrap">
            <input class="<?php echo $css_class; ?>" type="text" name="multilit/crm/options[<?php echo $key; ?>]" value="<?php echo $options[$key];?>">
            <label class="screen-reader-text">
                <?php echo $setting['desc']; ?>
            </label>
            <p class="tooltip description">
                <?php echo $setting['desc']; ?>
            </p>
        </div>
    </fieldset>

    <?php } // End foreach ?>
</div>