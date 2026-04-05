# Pro Invoice System

Pro Invoice System is a WordPress plugin for managing invoices from the admin dashboard. It lets you add, view, edit, delete, filter, and export invoices, and it also includes a frontend shortcode for administrator-only access.

## Developer

- Mohammed Khalifa
- Twitter: [https://x.com/mmd1790](https://x.com/mmd1790)

## Features

- Add invoices with invoice number, date, supplier, total, and category.
- Upload an invoice image and store its URL in the database.
- View all invoices in the WordPress admin area with category filtering.
- Open invoice details in a dedicated view.
- Edit or delete invoices from the admin area.
- Export invoices to an Excel-compatible XLS file.
- Use the frontend shortcode: `pis_frontend`.
- RTL-friendly admin UI with Cairo styling.

## Requirements

- WordPress.
- Administrator access for managing and filtering invoices.
- PHP and MySQL compatible with your WordPress installation.

## Installation

1. Copy `pro-invoice-system.php` into a WordPress plugin folder.
2. Place it inside its own plugin directory, for example `pro-invoice-system`.
3. Activate the plugin from the WordPress admin dashboard.

## Usage

After activation, a menu named **Invoices** appears in the WordPress admin area. From there you can:

- Add a new invoice.
- Browse all invoices.
- Filter invoices by category.
- Export all invoices or only the selected category.
- View, edit, or delete any invoice.

## Frontend Shortcode

The plugin provides one shortcode for showing the invoice form and table on the frontend:

```text
[pis_frontend]
```

Notes:

- The shortcode is available only to logged-in users.
- Access is restricted to administrator users.

## Database Table

When activated, the plugin creates a database table named:

```text
{prefix}pis_invoices
```

It includes the following fields:

- `id`
- `invoice_number`
- `invoice_date`
- `supplier`
- `total`
- `category`
- `image`

## Excel Export

The admin area supports export with:

- Export all invoices.
- Export invoices by category.

## Notes

- The frontend access is restricted to administrators.
- Images are uploaded through the built-in WordPress upload handler.
- The admin UI is designed for Arabic/RTL workflows.
