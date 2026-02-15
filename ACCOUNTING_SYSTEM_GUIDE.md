# Accounting System Guide
## Code Catalyst Labs - Complete Financial Management System

### Overview
Your invoice system has been expanded into a comprehensive accounting and financial management system. You can now:

- **Track all expenses** (recurring and one-time)
- **Manage company assets** with valuation tracking
- **Monitor service subscriptions** and recurring payments
- **Maintain a digital cashbook** for all transactions
- **Generate financial reports** (Income Statement, Balance Sheet, Cash Flow)
- **Manage users** with role-based access control
- **Enter historical data** (backlog) for complete financial records

---

## 🚀 Setup Instructions

### 1. Database Setup

Run the new database schema to add all accounting tables:

```bash
# Import the new accounting tables
mysql -u root -p invoice_system < database_accounting.sql
```

This will create the following tables:
- `chart_of_accounts` - Account categories for financial tracking
- `cashbook` - All cash inflows and outflows
- `expenses` - Company expenses (recurring and one-time)
- `assets` - Company assets and valuations
- `services` - Service subscriptions
- `service_payments` - Payment history for services
- `ledger_entries` - Double-entry bookkeeping ledger
- `budgets` - Budget planning and tracking
- `asset_valuations` - Asset value history

### 2. Default Chart of Accounts

The system automatically creates a standard chart of accounts with:
- **Assets** (Cash, Bank, Equipment, etc.)
- **Liabilities** (Accounts Payable, Loans, etc.)
- **Equity** (Owner Capital, Retained Earnings)
- **Revenue** (Sales, Service Revenue)
- **Expenses** (Rent, Utilities, Salaries, etc.)

You can customize these in the **Accounting > Chart of Accounts** menu.

---

## 👥 User Management

### Access User Management
**Admin only** - Navigate to **Admin > User Management**

### Creating Users

1. Click **"Add New User"**
2. Fill in the required information:
   - Username (minimum 3 characters)
   - Email address
   - Password (minimum 6 characters)
   - Role (Admin/Finance/Sales)
   - Status (Active/Inactive)

### User Roles & Permissions

#### 🔴 Admin
- Full system access
- User management
- All financial operations
- System settings and audit logs

#### 🟢 Finance
- Accounting & cashbook
- Expense management
- Asset management
- Financial reports
- Invoice management
- Service subscriptions

#### 🔵 Sales
- Create quotations
- Create invoices
- View clients
- Limited reports

### Editing Users

1. Go to **Admin > User Management**
2. Click the **Edit** button next to the user
3. Modify details (you cannot change your own role or deactivate yourself)
4. Optionally change password by checking **"Change Password"**

---

## 💰 Expense Management

### Access Expenses
Navigate to **Expenses** in the main menu (Finance/Admin only)

### Creating Expenses

1. Click **"Add New Expense"**
2. Fill in the details:
   - **Expense Date**: When the expense occurred
   - **Expense Account**: Select from your chart of accounts
   - **Vendor/Supplier**: Who you paid
   - **Category**: Type of expense (Rent, Utilities, etc.)
   - **Amount**: Expense amount
   - **Payment Method**: Cash, Bank Transfer, Mobile Money, etc.
   - **Payment Status**: Pending, Paid, Partially Paid, Overdue
   - **Description**: Additional details

### Recurring Expenses

For expenses that repeat regularly (rent, subscriptions, salaries):

1. Check **"This is a recurring expense"**
2. Select **Recurrence Frequency**: Daily, Weekly, Monthly, Quarterly, Yearly
3. Set **Next Recurrence Date**: When the next payment is due

The system will track when recurring expenses are due.

### Expense Status

- **Pending**: Not yet paid
- **Paid**: Fully paid (automatically creates a cashbook entry)
- **Partially Paid**: Partial payment made
- **Overdue**: Payment is late

### Filtering Expenses

Use the filter options to view:
- Expenses by date range
- Specific categories
- Payment status
- Recurring vs one-time expenses

---

## 📦 Asset Management

### Access Assets
Navigate to **Assets** in the main menu (Finance/Admin only)

### Adding Assets

1. Click **"Add New Asset"**
2. Enter asset information:
   - **Asset Name**: Description of the asset
   - **Category**: Equipment, Furniture, Vehicles, Buildings, etc.
   - **Purchase Date**: When acquired
   - **Purchase Price**: Original cost
   - **Current Value**: Current market value
   - **Serial Number**: For tracking (optional)
   - **Condition**: Excellent, Good, Fair, Poor, Damaged
   - **Location**: Where it's kept
   - **Assigned To**: User responsible for the asset
   - **Warranty Expiry**: For warranty tracking

### Depreciation Tracking

Assets can be depreciated using:
- **Straight Line**: Equal depreciation each period
- **Declining Balance**: Faster depreciation initially
- **None**: No depreciation

Set the **Depreciation Rate** (percentage) for automatic calculations.

### Asset Valuations

The system tracks asset value changes over time:
- Initial valuation is created automatically at purchase price
- Update current value as needed
- View valuation history

---

## 🔄 Services & Subscriptions

### Access Services
Navigate to **Services** in the main menu (Finance/Admin only)

### Adding Services

1. Click **"Add New Service"**
2. Enter service details:
   - **Service Name**: What the service is
   - **Category**: Software, Hosting, Cloud Services, etc.
   - **Provider Name**: Company providing the service
   - **Provider Contact**: Email or phone
   - **Cost**: How much per billing cycle
   - **Billing Frequency**: Monthly, Quarterly, Yearly, etc.
   - **Start Date**: When subscription started
   - **Next Billing Date**: When next payment is due
   - **End Date**: When subscription ends (optional)
   - **Auto-renew**: Whether it renews automatically

### Service Status

- **Active**: Currently active
- **Suspended**: Temporarily paused
- **Cancelled**: Terminated
- **Expired**: End date has passed

### Billing Alerts

The system highlights services due for billing within 7 days with a warning indicator.

---

## 💵 Cashbook

### Access Cashbook
Navigate to **Accounting > Cashbook** (Finance/Admin only)

### Understanding the Cashbook

The cashbook tracks all cash movements:
- **Income**: Money received (from invoices, sales, etc.)
- **Expense**: Money paid out (expenses, purchases, etc.)
- **Transfer**: Moving money between accounts

### Automatic Entries

The system automatically creates cashbook entries when:
- An invoice is marked as **Paid**
- An expense is marked as **Paid**

### Manual Entries

You can manually add cashbook entries for:
- Cash sales
- Bank deposits
- Cash withdrawals
- Other transactions

### Cashbook Reports

View summaries showing:
- Total Income
- Total Expenses
- Net Cashflow

Filter by date range and transaction type.

---

## 📊 Financial Reports

### Access Reports
Navigate to **Accounting** dropdown and select:
- **Financial Reports** - Income Statement, Balance Sheet, Cash Flow
- **General Ledger** - Detailed transaction history

### Financial Reports Page

Generate comprehensive reports including:

#### Income Statement (Profit & Loss)
- Revenue from sales
- Expenses by category
- Net Profit/Loss

#### Balance Sheet
- **Assets**: Cash, Receivables, Fixed Assets
- **Liabilities**: Payables, Loans
- **Equity**: Owner's equity

#### Cash Flow Summary
- Cash inflows
- Cash outflows
- Net cash flow

### General Ledger

View detailed double-entry bookkeeping records:
- All debits and credits
- Organized by account
- Filter by date and account
- Verify balance (debits should equal credits)

### Printing Reports

Click the **Print Report** button to generate printer-friendly versions.

---

## 📈 Chart of Accounts

### Access Chart of Accounts
Navigate to **Accounting > Chart of Accounts** (Finance/Admin only)

### Account Structure

Accounts are organized by type:
- **Assets**: What you own
- **Liabilities**: What you owe
- **Equity**: Owner's stake
- **Revenue**: Income sources
- **Expenses**: Cost categories

### Account Codes

Each account has a unique code:
- **1000-1999**: Assets
- **2000-2999**: Liabilities
- **3000-3999**: Equity
- **4000-4999**: Revenue
- **5000-5999**: Expenses

### Adding Custom Accounts

1. Click **"Add New Account"**
2. Enter account details:
   - Account Code (unique number)
   - Account Name
   - Account Type
   - Description
   - Parent Account (for sub-accounts)

---

## 🔄 Entering Historical Data (Backlog)

### Why Enter Historical Data?

To have complete financial records, you should enter:
- Past expenses
- Asset purchases
- Service subscriptions
- Previous transactions

### Best Practices for Backlog Entry

1. **Start with Assets**
   - Enter all company assets with original purchase dates
   - Set current values

2. **Add Service Subscriptions**
   - Enter all active services
   - Set correct billing dates

3. **Enter Expenses**
   - Go through bank statements
   - Add all major expenses
   - Mark payment status correctly

4. **Update Cashbook**
   - Enter cash transactions not already recorded
   - Include bank deposits and withdrawals

5. **Reconcile**
   - Check financial reports
   - Verify totals match your records

### Tips for Efficiency

- **Use CSV imports** (if you have bulk data)
- **Set correct dates** to maintain accurate historical records
- **Use consistent categories** for easier reporting
- **Add descriptions** for future reference

---

## 🎯 Dashboard Overview

The dashboard now shows:

### For All Users:
- Total Quotations
- Total Invoices
- Total Clients
- Unpaid Invoices
- Total Revenue (Paid)
- Pending Amount

### For Admin & Finance:
- **Monthly Expenses**: Current month total
- **Total Assets**: Total value of all assets
- **Active Services**: Number of subscriptions
- **Net Cashflow**: Current month cashflow

---

## 🔐 Security & Best Practices

### Password Requirements
- Minimum 6 characters
- Change regularly
- Use strong passwords

### User Access Control
- Assign appropriate roles
- Deactivate users who leave
- Regular access reviews

### Data Backup
- Regular database backups
- Store backups securely
- Test restore procedures

### Audit Trail
- All actions are logged
- Admin can view audit logs
- Track who did what and when

---

## 📱 Common Workflows

### Monthly Expense Recording
1. Collect receipts and bills
2. Go to **Expenses > Add New Expense**
3. Enter each expense with correct date and category
4. Mark payment status
5. Review monthly expense report

### Asset Acquisition
1. Purchase asset
2. Go to **Assets > Add New Asset**
3. Enter purchase details
4. Assign to user if needed
5. Set up depreciation if applicable

### Service Subscription Management
1. Subscribe to new service
2. Go to **Services > Add New Service**
3. Enter subscription details
4. Set billing frequency
5. System will alert when payment is due

### Monthly Financial Close
1. Enter all expenses for the month
2. Mark all invoices paid/unpaid correctly
3. Update asset valuations if needed
4. Generate financial reports
5. Review and reconcile

---

## 🆘 Troubleshooting

### Cannot See Accounting Modules
- Check your user role (must be Admin or Finance)
- Ensure you're logged in
- Contact admin to update your role

### Expense Not Creating Cashbook Entry
- Cashbook entries only created when status is "Paid"
- Check payment status
- Manually verify in Cashbook

### Financial Reports Show Zero
- Ensure you've entered transactions for the date range
- Check date filters
- Verify transactions are in correct accounts

### Cannot Delete User
- Cannot delete yourself
- Check for dependencies (created records)
- Deactivate instead of delete

---

## 📞 Support

For questions or issues:
1. Check this guide first
2. Review audit logs for errors
3. Contact system administrator
4. Backup data before major changes

---

## 🎉 Congratulations!

You now have a complete accounting system that tracks:
- ✅ All income and expenses
- ✅ Company assets and their values
- ✅ Service subscriptions and payments
- ✅ Complete cashbook
- ✅ Financial reports
- ✅ User management with roles
- ✅ Historical data (backlog)

Start by entering your current data, then use the system daily to track all financial activities!

