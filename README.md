# Pretty Professional Invoices

A self-contained WordPress plugin for managing invoices, clients, projects, and time tracking. Zero external dependencies required.

I built this for myself several years ago to manage my business, now working on turning it into a plugin I can share.

## Features

- **Custom Post Types**
  - Invoices (ppi_invoice)
  - Clients (ppi_client)

- **Invoice Types**
  - Project-based with time entries
  - Itemized invoices
  - External (custom content)

- **Client Management**
  - Standard hourly rates
  - Client addresses
  - Automatic rate assignment to projects

- **Project Tracking**
  - Multiple projects per invoice
  - Time entry tracking
  - Per-project rate overrides
  - Automatic total calculations

- **Status Management**
  - Customizable status workflows
  - Default statuses: In Progress, Issued, Paid, Abandoned
  - Bulk status updates from archive page

- **Archive Features**
  - Sortable invoice table
  - Advanced filtering (client, status, date range, amount, search)
  - Status breakdown analytics
  - Client breakdown with percentages
  - Revenue tracking
  - Export to CSV and Excel (filtered invoices)
  - Quick action buttons (view, print, download PDF) for each invoice

- **Professional Templates**
  - Clean invoice layout
  - PDF-ready print styles
  - Print and download buttons on single invoices
  - Auto-print functionality from archive links
  - Responsive design

## Installation

1. Upload the `ppi-invoicing-system` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to **Invoicing** in the WordPress admin menu
4. Configure settings under **Invoicing > Settings**
5. Upload your logo and set contact information
6. Create clients under **Invoicing > Clients**

## Usage

### Creating a Client

1. Go to **Clients > Add New**
2. Enter client name as the title
3. Set the standard hourly rate
4. Add the client's address
5. Publish

### Creating an Invoice

1. Go to **Invoicing > Add New**
2. Enter invoice title (client name or project reference)
3. Fill in Invoice Details:
   - Select client
   - Invoice number (auto-generated if left blank)
   - Period of work
   - Date of invoice
   - Payment due date
   - Status
4. Choose invoice type:
   - **Project-based**: Add projects with time entries
   - **Itemized**: Add line items directly
   - **External**: Use the main content editor
5. Add projects/items as needed
6. Publish

### Managing Status Updates

On the archive page (Invoicing), logged-in users can:
- Change invoice statuses using dropdowns
- Click "Save Status Changes" to bulk update
- View real-time totals and client breakdowns
- Sort by status, date, client, or amount

### Quick Actions from Archive Page

Each invoice row in the archive table includes action buttons:
- **View** (👁️) - Opens the full invoice page
- **Print** (🖨️) - Opens invoice and automatically triggers print dialog
- **Download PDF** (💾) - Opens invoice in new tab for saving as PDF

These buttons are hidden when printing the archive page.

### Filtering and Exporting Invoices

The archive page provides powerful filtering and export capabilities:

**Filtering Options:**
- Filter by client
- Filter by status (multi-select checkboxes)
- Date range (from/to)
- Amount range (min/max)
- Search by invoice number

**Export Features:**
- Export visible invoices to CSV or Excel format
- Exports respect all active filters
- Files include: Invoice Number, Date, Client, Status, Period of Work, Payment Due, Amount
- Automatic filename generation: `invoices-YYYY-MM-DD.csv` or `.xls`
- Includes summary row with total amount
- CSV format uses UTF-8 with BOM for Excel compatibility

To export:
1. Apply any filters you want (optional)
2. Click the "Export" button in the filter bar
3. Choose "Export as CSV" or "Export as Excel"
4. File downloads automatically

## Customization

### Settings Page

Access plugin settings at **Invoicing > Settings**:
- Upload your logo
- Set contact information (email, phone)
- Customize "From" address for invoices
- Configure custom invoice statuses
- Set footer text

### Template Overrides

Templates are located in `templates/`:
- `single-invoice.php` - Individual invoice display
- `archive-invoice.php` - Invoice list and analytics
- `header-invoice.php` - Invoice header

### ACF-Compatible Helper Functions

For template development, the plugin provides familiar helper functions:

```php
// Get field value
$client = ppi_get_field( 'client', $post_id );

// Check if repeater has rows
if ( ppi_have_rows( 'project' ) ) {
    while ( ppi_the_row() ) {
        $project_name = ppi_get_sub_field( 'project_name' );
    }
}
```

## Data Structure

### Meta Key Reference

| Field | Meta Key |
|-------|----------|
| Client | _ppi_client_id |
| Invoice Number | _ppi_invoice_number |
| Period of Work | _ppi_period_of_work |
| Date of Invoice | _ppi_date_of_invoice |
| Payment Due | _ppi_payment_due |
| Total | _ppi_total |
| Status | _ppi_status |
| Notes | _ppi_notes |
| Invoice Type | _ppi_invoice_type |
| Projects | _ppi_projects |
| Itemized | _ppi_itemized |
| Standard Rate | _ppi_standard_rate |
| Address | _ppi_address |

## File Structure

```
ppi-invoicing-system/
├── assets/
│   ├── css/
│   │   ├── admin-styles.css
│   │   └── invoice-styles.css
│   ├── js/
│   │   ├── invoice-admin.js
│   │   ├── invoice-archive.js
│   │   └── invoice-single.js
│   └── img/
├── includes/
│   ├── ajax-handlers.php
│   ├── meta-boxes.php
│   ├── post-types.php
│   ├── settings.php
│   ├── template-loader.php
│   └── migrate-acf.php (temporary)
├── templates/
│   ├── archive-invoice.php
│   ├── header-invoice.php
│   └── single-invoice.php
├── ppi-invoicing-system.php
├── README.md
├── LICENSE
├── CLAUDE.md
└── MIGRATION-GUIDE.md
```

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher

## Changelog

### 1.2.0
- Added print and download PDF buttons to single invoice pages
- Added quick action buttons (view, print, download) to archive table
- Auto-print functionality when accessing invoices via print links
- Action buttons hidden during print for clean output
- Enhanced user experience with icon-based controls

### 1.1.0
- Added CSV and Excel export functionality for filtered invoices
- Export button in archive page filter bar
- Exports include all invoice details and summary totals
- UTF-8 BOM support for Excel compatibility

### 1.0.0
- Initial release as "Pretty Professional Invoices"
- Self-contained plugin with native meta boxes
- Full invoice, client, and project management
- Customizable status workflows
- Archive page with analytics and filtering
- Zero external dependencies

## Support

For issues or questions, contact the plugin author.

## License

GNU General Public License v2.0 or later
See LICENSE file for details

## Credits

**Author:** Matthew Cowan
**Author URI:** https://mnc4.com
