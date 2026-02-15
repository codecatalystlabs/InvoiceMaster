# Accounting System - Quick Setup Guide

## 📋 Prerequisites

- XAMPP/WAMP/LAMP installed and running
- MySQL database access
- Existing invoice system installed

---

## 🚀 Installation Steps

### Step 1: Import Database Schema

Open your MySQL command line or phpMyAdmin and run:

```bash
# Option 1: Using MySQL command line
mysql -u root -p invoice_system < database_accounting.sql

# Option 2: Using phpMyAdmin
# - Open phpMyAdmin
# - Select 'invoice_system' database
# - Go to 'Import' tab
# - Choose 'database_accounting.sql' file
# - Click 'Go'
```

This will create all the necessary tables:
- ✅ `chart_of_accounts`
- ✅ `cashbook`
- ✅ `expenses`
- ✅ `assets`
- ✅ `asset_valuations`
- ✅ `services`
- ✅ `service_payments`
- ✅ `ledger_entries`
- ✅ `budgets`

### Step 2: Verify File Structure

Ensure all new folders are in your project root:

```
D:\xampp\htdocs\invoice\
├── users/          ← User Management
├── accounts/       ← Chart of Accounts & Cashbook
├── expenses/       ← Expense Management
├── assets/         ← Asset Management
├── services/       ← Services & Subscriptions
└── reports/        ← Financial Reports
```

### Step 3: Check Permissions

Ensure the web server has write permissions for audit logs and file uploads (if applicable).

### Step 4: Login and Test

1. Open your browser: `http://localhost/invoice`
2. Login with admin credentials
3. Verify new menu items appear:
   - Sales dropdown (Quotations, Invoices, Clients)
   - Accounting dropdown (Chart of Accounts, Cashbook, Reports)
   - Expenses
   - Assets
   - Services
   - Admin dropdown (User Management, Audit Logs)

---

## 🔐 Default Access

### Admin Account
- Username: `admin`
- Password: `admin123`
- **⚠️ IMPORTANT:** Change this password immediately!

### User Roles

The system has 3 roles with different permissions:

**Admin** (Full Access):
- ✅ User Management
- ✅ All Accounting Features
- ✅ Expenses, Assets, Services
- ✅ All Reports
- ✅ Audit Logs

**Finance** (Financial Management):
- ✅ Accounting & Cashbook
- ✅ Expenses, Assets, Services
- ✅ Financial Reports
- ✅ Invoice Management
- ❌ User Management

**Sales** (Sales Only):
- ✅ Quotations
- ✅ Invoices
- ✅ Clients
- ❌ Accounting Features
- ❌ User Management

---

## ✅ Post-Installation Checklist

### 1. Update Admin Password
- Go to Profile
- Change default password
- Use a strong password (min 6 characters)

### 2. Create User Accounts
- Navigate to **Admin > User Management**
- Create accounts for your team members
- Assign appropriate roles (Admin/Finance/Sales)

### 3. Review Chart of Accounts
- Go to **Accounting > Chart of Accounts**
- Default accounts are already created
- Add custom accounts if needed

### 4. Enter Initial Data

#### Assets
1. Go to **Assets > Add New Asset**
2. Enter all company assets:
   - Equipment
   - Furniture
   - Vehicles
   - Buildings
   - Electronics
3. Set current values
4. Assign to users if needed

#### Services
1. Go to **Services > Add New Service**
2. Add all active subscriptions:
   - Software licenses
   - Hosting services
   - Cloud services
   - Marketing tools
   - Communication tools
3. Set correct billing dates

#### Expenses (Backlog)
1. Go to **Expenses > Add New Expense**
2. Enter recent expenses (last 3-6 months recommended):
   - Rent payments
   - Utilities
   - Salaries
   - Office supplies
   - Marketing costs
3. Mark payment status correctly
4. Set recurring for regular expenses (rent, utilities, etc.)

### 5. Test Features

#### Test Expense Creation
1. Add a test expense
2. Mark as "Paid"
3. Check cashbook for automatic entry
4. Verify in financial reports

#### Test Asset Management
1. Add a test asset
2. View asset details
3. Check valuation history

#### Test Services
1. Add a test service
2. Check "next billing" alert
3. Verify in dashboard

#### Test Reports
1. Go to **Accounting > Financial Reports**
2. Generate report for current month
3. Verify Income Statement shows data
4. Check Balance Sheet
5. Review Cash Flow Summary

#### Test User Management (Admin only)
1. Create a test user
2. Assign "Finance" role
3. Login as that user
4. Verify permissions are correct
5. Login back as admin

---

## 📊 Quick Start Workflow

### Week 1: Setup & Data Entry
**Day 1-2:**
- ✅ Import database
- ✅ Change admin password
- ✅ Create user accounts
- ✅ Review chart of accounts

**Day 3-4:**
- ✅ Enter all assets
- ✅ Add all active services
- ✅ Input current expenses

**Day 5:**
- ✅ Test all features
- ✅ Generate initial reports
- ✅ Train team members

### Week 2+: Daily Operations
**Daily:**
- Record new expenses
- Check service billing alerts
- Update invoice status

**Weekly:**
- Review expense reports
- Update asset valuations if needed
- Check cashbook balance

**Monthly:**
- Generate financial reports
- Reconcile accounts
- Review and plan budget

---

## 🔧 Configuration Options

### Currency Settings
Located in `includes/config.php`:
```php
define('CURRENCY_CODE', 'UGX');  // Change to your currency
define('CURRENCY_SYMBOL', 'UGX');
```

### Items Per Page
```php
define('ITEMS_PER_PAGE', 10);  // Adjust pagination
```

### Email Settings
Configure SMTP for email notifications:
```php
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password');
```

---

## 🆘 Troubleshooting

### Problem: New Menu Items Don't Appear
**Solution:**
- Clear browser cache
- Logout and login again
- Check your user role (must be Admin or Finance)

### Problem: Database Tables Not Created
**Solution:**
- Check MySQL errors
- Ensure you selected correct database
- Verify file `database_accounting.sql` is not corrupted
- Run SQL commands manually if needed

### Problem: Cannot See Accounting Features
**Solution:**
- Verify you're logged in as Admin or Finance role
- Check `includes/header.php` was updated correctly
- Check browser console for JavaScript errors

### Problem: Financial Reports Show Zero
**Solution:**
- Ensure you've entered expenses and invoices
- Check date range filter
- Verify transactions are in the correct date range

### Problem: Cashbook Entry Not Created
**Solution:**
- Cashbook entries only created when expense/invoice marked "Paid"
- Check payment status
- Manually verify in cashbook

---

## 📚 Additional Resources

- **User Guide:** See `ACCOUNTING_SYSTEM_GUIDE.md` for detailed usage instructions
- **Database Schema:** Review `database_accounting.sql` for table structures
- **Audit Logs:** Check Admin > Audit Logs for all system actions

---

## 🎉 You're All Set!

Your accounting system is now ready to use. Start by:

1. ✅ Entering your current assets
2. ✅ Adding active service subscriptions
3. ✅ Recording recent expenses
4. ✅ Generating your first financial report

For detailed usage instructions, refer to `ACCOUNTING_SYSTEM_GUIDE.md`.

---

## 📞 Need Help?

If you encounter issues:
1. Check the troubleshooting section above
2. Review audit logs for errors
3. Verify all files are uploaded correctly
4. Check MySQL error logs
5. Ensure PHP version is 7.4 or higher

**Remember:** Always backup your database before making major changes!

