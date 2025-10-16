# Code Catalyst Labs - Quotation & Invoice Management System

A comprehensive web application for managing quotations and invoices built with PHP, MySQLi, and Bootstrap 5.

## Features

- **Authentication System**: Role-based access control (Admin, Sales, Finance)
- **Quotation Management**: Create, edit, delete, and send quotations
- **Invoice Management**: Create invoices from quotations or standalone
- **Client Management**: Manage client information
- **Audit Logging**: Track all system actions
- **PDF Generation**: Generate professional PDF documents
- **Email Integration**: Send quotations and invoices via email
- **Responsive Design**: Bootstrap 5 for mobile-friendly interface

## Installation

1. **Database Setup**
   ```bash
   Import database.sql into your MySQL server
   ```

2. **Configuration**
   - Edit `includes/config.php` with your database credentials
   - Configure email settings in `includes/mailer.php`

3. **Dependencies**
   - Install Composer: [https://getcomposer.org/](https://getcomposer.org/)
   - Run: `composer require phpmailer/phpmailer mpdf/mpdf`

4. **Permissions**
   - Ensure `pdf/` directory has write permissions

## Default Login

- **Username**: admin
- **Password**: admin123
- **Email**: admin@codecatalystlabs.com

## Directory Structure

```
/assets         - CSS, JS, and images
/includes       - Configuration and shared includes
/auth           - Authentication pages
/quotations     - Quotation management
/invoices       - Invoice management
/audit          - Audit logs
/pdf            - Generated PDF files
```

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- Composer (for dependencies)

## Security Features

- Password hashing with bcrypt
- Prepared statements (MySQLi)
- Session-based authentication
- Role-based access control
- CSRF protection

## Company Information

**Code Catalyst Labs**
- Email: info@codecatalystlabs.com
- Phone: +1 (555) 123-4567
- Address: 123 Innovation Drive, Tech City, TC 12345

## License

Proprietary - Code Catalyst Labs © 2025

