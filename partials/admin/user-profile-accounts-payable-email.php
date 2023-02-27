<?php 
/**
 * Wordpress User Profile - Accounts Payable Email
 *
 * @since      1.0.0
 * @package    multilit-woo
 * @subpackage multilit-woo/partials/admin
 * @author     Curtis Krauter <curtis@businesstechninjas.com>
 */
?>

<fieldset id="multilti-accounts-payable-email">
    <h3>
        <?php _e('Accounts Payable Email', 'mulitlit-woo'); ?>
    </h3>
    <table class="form-table" role="presentation">
        <tr class="<?php echo $invoice_email_key; ?>-wrap">
            <th>
                <label for="<?php echo $invoice_email_key; ?>">
                    <?php _e('Email', 'mulitlit-woo'); ?>
                </label>
            </th>
            <td>
                <input class="regular-text" id="<?php echo $invoice_email_key; ?>" name="<?php echo $invoice_email_key; ?>" type="text" value="<?php echo $invoice_email; ?>"<?php echo $readonly; ?>/>
            </td>
        </tr>
        <tr class="<?php echo $email_check_key; ?>-wrap">
            <th>
                <label for="<?php echo $email_check_key; ?>">
                    <?php echo _e('Use Default Account Email', 'mulitlit-woo'); ?>
                </label>
            </th>
            <td>
                <input id="<?php echo $email_check_key; ?>" name="<?php echo $email_check_key; ?>" type="checkbox" value="1" <?php echo $checked_prop; ?>/>
            </td>
        </tr>
    </table>
</fieldset>