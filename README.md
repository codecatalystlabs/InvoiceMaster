# Invoice Master (Laravel)

Merged Laravel 12 app combining:

- **This repo’s original invoice system** — quotations, invoices, clients, expenses, assets, services, chart of accounts, ledger, financial reports, analytics, CSV export, audit logs, roles (Admin / Finance / Sales)
- **InvoiceMaster / CodeCash** — company workspaces, receipts, running cash book (debit/credit + running balance, folio, discount), proforma invoices, company profile + logo, team invites, PDF + DOCX export

The previous PHP apps are kept under `legacy/` for reference.

## Run locally (XAMPP)

1. MySQL database `invoice_master` is created automatically if you ran migrate.
2. Open **http://localhost/invoice/public** (or http://localhost/invoice — root `.htaccess` rewrites to `public/`).
3. Default login after `php artisan db:seed`:
   - Email: `admin@codecatalystug.com`
   - Password: `admin123`

Or register a new company at `/register`.

## Commands

```bash
composer install
copy .env.example .env   # then set DB_* and APP_URL
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
```

## Main modules

| Area | Routes |
|------|--------|
| Sales | Invoices, Quotations (convert to invoice), Receipts, Clients |
| Accounting | Cash book, Expenses, Assets, Services, Chart of accounts, Ledger |
| Insights | Dashboard, Analytics (Chart.js), Financial report, CSV export |
| Admin | Users, Company settings + invites, Audit log |

Documents: invoice/quotation/receipt **PDF** (DomPDF) and invoice **DOCX** (PHPWord).
