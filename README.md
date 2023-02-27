# client-plugin-integration-example
 Woocommerce / Woo Discounts / Event Tickets / Memberium / Xeroom Integrations

NOTE - Not a working plugin - not all classes and files are included from the original and all main plugins need to be active. Nothing from the frontend is included here.
This is just for example to give a taste of my coding standards

 The main point of the integration was to use Memberium CRM settings to :
 1. Limit visitors ability to purchase specific items throughout the Event Ticket forms and throughout the Checkout process.
 2. Ensure visitors attempting to purchase an event met permission requirements
 3. Push event details to CRM for the Ticket Purchaser and any additional attendee tickets purchased
 Other functionality 
 4. Custom Fields for the Order Form email to allow membership schools to use a different email for invoices. 
 5. Custom Invoice data was pushed to Xeroom to meet client requirements

 I added custom settings to the admin for : 
 1. Events Tickets settings page for mapping CRM fields in a custom Tab
 2. WooCommerce Single Ticket Product Page
 3. Single Events Page Loads All Tickets in via AJAX so the same settings were applied there as the  WooCommerce Single Ticket Product Page