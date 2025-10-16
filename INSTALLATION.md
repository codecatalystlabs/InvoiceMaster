# Installation Guide - Code Catalyst Labs Invoice Management System

## Prerequisites

- **PHP**: Version 7.4 or higher
- **MySQL**: Version 5.7 or higher
- **Apache/Nginx**: Web server with mod_rewrite enabled
- **Composer**: For dependency management

## Step-by-Step Installation

### 1. Download and Extract Files

Extract all files to your web server directory:
```
D:\xampp\htdocs\invoice\invoice\
```

### 2. Database Setup

1. Open phpMyAdmin or MySQL command line
2. Import the database schema:
   ```sql
   mysql -u root -p < database.sql
   ```
   Or manually import `database.sql` through phpMyAdmin

3. The script will create:
   - Database: `invoice_system`
   - Default admin user:
     - Username: `admin`
     - Password: `admin123`
     - Email: `admin@codecatalystlabs.com`
   - Sample clients

### 3. Configure Database Connection

Edit `includes/config.php` and update these settings:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');           // Your MySQL username
define('DB_PASS', '');               // Your MySQL password
define('DB_NAME', 'invoice_system');
```

### 4. Update Application URL

In `includes/config.php`, update the APP_URL:

```php
define('APP_URL', 'http://localhost/invoice');
```

Change this to match your actual URL.

### 5. Install Dependencies

Open terminal/command prompt in the project directory and run:

```bash
composer install
```

This will install:
- PHPMailer (for email functionality)
- mPDF (for PDF generation)

If you don't have Composer installed, download it from: https://getcomposer.org/

### 6. Configure Email Settings (Optional)

Edit `includes/config.php` to configure email settings:

```php
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password');
define('SMTP_FROM_EMAIL', 'noreply@codecatalystlabs.com');
define('SMTP_FROM_NAME', 'Code Catalyst Labs');
```

**For Gmail:**
1. Enable 2-factor authentication
2. Generate an "App Password"
3. Use the app password in the configuration

### 7. Set Permissions

Ensure the following directories have write permissions:

```bash
chmod 755 pdf/
chmod 755 pdf/temp/
```

Create the temp directory if it doesn't exist:
```bash
mkdir -p pdf/temp
```

### 8. Apache Configuration

Ensure `.htaccess` is enabled in Apache:

1. Edit `httpd.conf` (XAMPP: `C:\xampp\apache\conf\httpd.conf`)
2. Find and uncomment:
   ```
   LoadModule rewrite_module modules/mod_rewrite.so
   ```
3. Find `<Directory>` section and change `AllowOverride None` to `AllowOverride All`
4. Restart Apache

### 9. Access the Application

Open your browser and navigate to:
```
http://localhost/invoice/auth/login.php
```

**Default Login:**
- Username: `admin`
- Password: `admin123`

### 10. Security Recommendations

After installation:

1. **Change Default Password**
   - Login and go to Profile
   - Change the admin password immediately

2. **Update Configuration**
   - Review all settings in `includes/config.php`
   - Set appropriate tax rates
   - Update company information

3. **Enable Error Logging**
   - In production, ensure `display_errors` is OFF
   - Check error logs regularly

4. **Backup Database**
   - Set up regular database backups
   - Store backups securely

## Troubleshooting

### Error: "Connection failed"
- Check database credentials in `includes/config.php`
- Ensure MySQL service is running
- Verify database exists

### Error: "Composer dependencies not found"
- Run `composer install` in the project directory
- Check if Composer is installed: `composer --version`

### PDF Generation Not Working
- Ensure Composer dependencies are installed
- Check PHP extensions: mbstring, gd
- Verify write permissions on `pdf/` directory

### Email Not Sending
- Verify SMTP settings in `includes/config.php`
- Check if your email provider allows SMTP access
- For Gmail, use an App Password

### Page Not Found (404)
- Check `.htaccess` configuration
- Ensure mod_rewrite is enabled in Apache
- Verify APP_URL in `includes/config.php`

## Directory Structure

```
invoice/
├── assets/              # CSS, JS, images
├── auth/                # Authentication pages
├── includes/            # Configuration and functions
├── quotations/          # Quotation management
├── invoices/            # Invoice management
├── clients/             # Client management
├── audit/               # Audit logs
├── pdf/                 # PDF generation
│   └── temp/            # Temporary PDF storage
├── vendor/              # Composer dependencies
├── database.sql         # Database schema
├── composer.json        # Composer configuration
├── .htaccess            # Apache configuration
├── index.php            # Dashboard
└── README.md            # Documentation
```

## Next Steps

1. **Add Your Logo**
   - Replace `assets/logo.png` with your company logo
   - Recommended size: 200x200 pixels

2. **Customize Branding**
   - Update company information in `includes/config.php`
   - Modify colors in `assets/css/style.css` if needed

3. **Create Users**
   - Go to the user management section (if implemented)
   - Or manually add users via the database

4. **Add Clients**
   - Navigate to Clients → Add Client
   - Import clients if you have existing data

5. **Configure Tax Settings**
   - Update `DEFAULT_TAX_RATE` in `includes/config.php`

## Support

For issues or questions:
- Check the documentation in `README.md`
- Review troubleshooting section above
- Contact: support@codecatalystlabs.com

## License

Proprietary - Code Catalyst Labs © 2025

