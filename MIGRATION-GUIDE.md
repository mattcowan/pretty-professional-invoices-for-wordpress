# Migration Guide: Theme to Plugin

## Overview

This guide helps you migrate from the theme-based ACF invoicing system to the standalone Pretty Professional Invoices plugin.

## Pre-Migration Checklist

- [ ] Backup your database
- [ ] Note down all custom modifications to invoice templates
- [ ] Export any ACF field groups for reference
- [ ] List all active invoices and their statuses

## Migration Steps

### 1. Install the Plugin

1. The plugin is already installed at `/wp-content/plugins/ppi-invoicing-system/`
2. Go to WordPress Admin > Plugins
3. Activate "Pretty Professional Invoices"

### 2. Verify Post Types

After activation, you should see:
- **Invoices 2025** menu item in WordPress admin
- **Clients** menu item in WordPress admin

### 3. Data Migration Options

#### Option A: Keep Existing Data (Recommended)

The existing invoice data from `invoice_2025` post type will need to be migrated. You have two options:

**Manual Migration Script** (recommended for testing first):

```php
// Add this to functions.php temporarily or use in a custom script
function mnc_migrate_invoice_data() {
    $invoices = get_posts(array(
        'post_type' => 'invoice_2025',
        'posts_per_page' => -1,
        'post_status' => 'any'
    ));

    foreach ($invoices as $invoice) {
        // Migrate ACF fields to plugin meta
        $fields_map = array(
            'client' => '_mnc_client_id',
            'invoice_number' => '_mnc_invoice_number',
            'period_of_work' => '_mnc_period_of_work',
            'date_of_invoice' => '_mnc_date_of_invoice',
            'payment_due' => '_mnc_payment_due',
            'total' => '_mnc_total',
            'status' => '_mnc_status',
            'notes' => '_mnc_notes',
            'invoice_type' => '_mnc_invoice_type',
        );

        foreach ($fields_map as $acf_field => $meta_key) {
            $value = get_field($acf_field, $invoice->ID);
            if ($value !== false && $value !== '') {
                // Handle client field specially (convert post object to ID)
                if ($acf_field === 'client' && is_object($value)) {
                    $value = $value->ID;
                }
                update_post_meta($invoice->ID, $meta_key, $value);
            }
        }

        // Migrate projects (repeater field)
        if (have_rows('project', $invoice->ID)) {
            $projects = array();
            while (have_rows('project', $invoice->ID)) {
                the_row();
                $project = array(
                    'display_project_name' => get_sub_field('display_project_name'),
                    'project_name' => get_sub_field('project_name'),
                    'project_name_dropdown' => get_sub_field('project_name_dropdown'),
                    'project_rate' => get_sub_field('project_rate'),
                    'time_entries' => array()
                );

                if (have_rows('time_entries')) {
                    while (have_rows('time_entries')) {
                        the_row();
                        $project['time_entries'][] = array(
                            'standard_date_format' => get_sub_field('standard_date_format'),
                            'date' => get_sub_field('date'),
                            'freeform_date' => get_sub_field('freeform_date'),
                            'description_of_tasks' => get_sub_field('description_of_tasks'),
                            'hours' => get_sub_field('hours'),
                            'total' => get_sub_field('total')
                        );
                    }
                }

                $projects[] = $project;
            }
            update_post_meta($invoice->ID, '_mnc_projects', $projects);
        }

        // Migrate itemized (repeater field)
        if (have_rows('itemized', $invoice->ID)) {
            $itemized = array();
            while (have_rows('itemized', $invoice->ID)) {
                the_row();
                $itemized[] = array(
                    'dates' => get_sub_field('dates'),
                    'description' => get_sub_field('description'),
                    'hours' => get_sub_field('hours'),
                    'amount_override' => get_sub_field('amount_override')
                );
            }
            update_post_meta($invoice->ID, '_mnc_itemized', $itemized);
        }

        // Change post type to new plugin post type
        wp_update_post(array(
            'ID' => $invoice->ID,
            'post_type' => 'mnc_invoice_2025'
        ));
    }

    return count($invoices) . ' invoices migrated!';
}

// Run once: add_action('admin_init', 'mnc_migrate_invoice_data');
```

#### Option B: Fresh Start

If you want to start fresh:
1. Keep the old `invoice_2025` posts for reference
2. Create new invoices using the plugin's interface
3. Archive old invoices when migration is complete

### 4. Migrate Clients

If you have a separate client post type, migrate similarly:

```php
function mnc_migrate_client_data() {
    // Assuming you have a 'client' post type
    $clients = get_posts(array(
        'post_type' => 'client', // Or whatever your client post type is called
        'posts_per_page' => -1,
        'post_status' => 'any'
    ));

    foreach ($clients as $client) {
        $standard_rate = get_field('standard_rate', $client->ID);
        $address = get_field('address', $client->ID);

        if ($standard_rate) {
            update_post_meta($client->ID, '_mnc_standard_rate', $standard_rate);
        }
        if ($address) {
            update_post_meta($client->ID, '_mnc_address', $address);
        }

        // Change post type
        wp_update_post(array(
            'ID' => $client->ID,
            'post_type' => 'mnc_client'
        ));
    }

    return count($clients) . ' clients migrated!';
}
```

### 5. Update Theme

After confirming the plugin works:

1. **Remove old invoice code from `functions.php`:**
   - Lines 113-233 (old custom post type registrations)
   - Lines 282-321 (old AJAX handler)

2. **Optional: Remove old templates** (backup first!):
   - `single-invoice_*.php` files
   - `archive-invoice_*.php` files
   - `header-invoice.php`
   - `footer-invoice.php`
   - `loop-templates/content-invoice.php`

3. **Keep these if you made customizations:**
   - The plugin templates can be overridden by copying them to your theme

### 6. Template Customization

If you had custom modifications to your invoice templates:

1. Copy plugin templates to your theme:
   ```
   wp-content/themes/your-theme/mnc-invoicing/
   ├── archive-invoice.php
   ├── single-invoice.php
   ├── header-invoice.php
   └── footer-invoice.php
   ```

2. The plugin will automatically use theme templates if they exist

3. Update any template hooks or custom functions to work with the new helper functions:
   - `get_field()` → `mnc_get_field()`
   - `have_rows()` → `mnc_have_rows()`
   - `the_row()` → `mnc_the_row()`
   - `get_sub_field()` → `mnc_get_sub_field()`

### 7. Testing Checklist

- [ ] View an existing invoice in the admin
- [ ] Edit an invoice and save
- [ ] Create a new invoice
- [ ] View single invoice on frontend
- [ ] View invoice archive
- [ ] Test status updates on archive page
- [ ] Verify client breakdown calculations
- [ ] Test print styles (Ctrl/Cmd + P)
- [ ] Check mobile responsiveness

### 8. Post-Migration Cleanup

After successful migration and testing:

1. Deactivate Advanced Custom Fields (if not used elsewhere)
2. Remove old invoice functions from theme
3. Delete old ACF field groups for invoices
4. Update any documentation

## Troubleshooting

### Invoices Don't Display

- Check that templates are loading from plugin
- Verify post type is `mnc_invoice_2025`
- Check for PHP errors in debug log

### Missing Data

- Verify ACF fields were migrated to correct meta keys
- Check that repeater fields were properly converted to arrays
- Use WordPress database plugins to inspect post meta

### Styling Issues

- Check that plugin CSS is loading
- Verify Bootstrap classes are available from theme
- Check for CSS conflicts in browser developer tools

### AJAX Errors

- Verify nonce is being generated correctly
- Check JavaScript console for errors
- Ensure user is logged in for status updates

## Rollback Plan

If you need to rollback:

1. Deactivate the plugin
2. Re-enable old invoice templates in theme
3. Old data should still be intact (if you didn't delete)
4. Re-run SQL to change post types back if needed:
   ```sql
   UPDATE wp_posts SET post_type = 'invoice_2025' WHERE post_type = 'mnc_invoice_2025';
   ```

## Support

For issues during migration, contact matt@mnc4.com
