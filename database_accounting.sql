-- Code Catalyst Labs - Accounting Module Database Schema
-- Extension to invoice_system database

USE invoice_system;

-- Chart of Accounts - Define all account categories
CREATE TABLE IF NOT EXISTS chart_of_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    account_code VARCHAR(20) NOT NULL UNIQUE,
    account_name VARCHAR(100) NOT NULL,
    account_type ENUM('Asset', 'Liability', 'Equity', 'Revenue', 'Expense') NOT NULL,
    parent_id INT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES chart_of_accounts(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Cashbook - Record all cash transactions
CREATE TABLE IF NOT EXISTS cashbook (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_date DATE NOT NULL,
    reference_number VARCHAR(50) NOT NULL UNIQUE,
    account_id INT NOT NULL,
    transaction_type ENUM('Income', 'Expense', 'Transfer') NOT NULL,
    payment_method ENUM('Cash', 'Bank Transfer', 'Mobile Money', 'Cheque', 'Card') NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    description TEXT,
    category VARCHAR(100),
    invoice_id INT NULL,
    expense_id INT NULL,
    service_id INT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (account_id) REFERENCES chart_of_accounts(id),
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_transaction_date (transaction_date),
    INDEX idx_transaction_type (transaction_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Expenses - Track all company expenses
CREATE TABLE IF NOT EXISTS expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    expense_number VARCHAR(50) NOT NULL UNIQUE,
    expense_date DATE NOT NULL,
    account_id INT NOT NULL,
    vendor_name VARCHAR(100) NOT NULL,
    category VARCHAR(100) NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    payment_method ENUM('Cash', 'Bank Transfer', 'Mobile Money', 'Cheque', 'Card') NOT NULL,
    payment_status ENUM('Pending', 'Paid', 'Partially Paid', 'Overdue') DEFAULT 'Pending',
    is_recurring BOOLEAN DEFAULT FALSE,
    recurrence_frequency ENUM('Daily', 'Weekly', 'Monthly', 'Quarterly', 'Yearly') NULL,
    next_recurrence_date DATE NULL,
    description TEXT,
    receipt_file VARCHAR(255),
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (account_id) REFERENCES chart_of_accounts(id),
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_expense_date (expense_date),
    INDEX idx_category (category),
    INDEX idx_recurring (is_recurring)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Assets - Track company assets
CREATE TABLE IF NOT EXISTS assets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    asset_number VARCHAR(50) NOT NULL UNIQUE,
    asset_name VARCHAR(100) NOT NULL,
    category VARCHAR(100) NOT NULL,
    purchase_date DATE NOT NULL,
    purchase_price DECIMAL(15,2) NOT NULL,
    current_value DECIMAL(15,2) NOT NULL,
    depreciation_rate DECIMAL(5,2) DEFAULT 0,
    depreciation_method ENUM('Straight Line', 'Declining Balance', 'None') DEFAULT 'None',
    location VARCHAR(255),
    condition_status ENUM('Excellent', 'Good', 'Fair', 'Poor', 'Damaged') DEFAULT 'Good',
    description TEXT,
    serial_number VARCHAR(100),
    warranty_expiry DATE,
    assigned_to INT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_category (category),
    INDEX idx_purchase_date (purchase_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Asset Valuations - Track asset value changes over time
CREATE TABLE IF NOT EXISTS asset_valuations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    asset_id INT NOT NULL,
    valuation_date DATE NOT NULL,
    valuation_amount DECIMAL(15,2) NOT NULL,
    valuation_reason TEXT,
    valued_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (asset_id) REFERENCES assets(id) ON DELETE CASCADE,
    FOREIGN KEY (valued_by) REFERENCES users(id),
    INDEX idx_asset_date (asset_id, valuation_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Services - Track subscriptions and recurring services
CREATE TABLE IF NOT EXISTS services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_number VARCHAR(50) NOT NULL UNIQUE,
    service_name VARCHAR(100) NOT NULL,
    provider_name VARCHAR(100) NOT NULL,
    provider_contact VARCHAR(100),
    category VARCHAR(100),
    cost DECIMAL(15,2) NOT NULL,
    billing_frequency ENUM('Daily', 'Weekly', 'Monthly', 'Quarterly', 'Yearly') NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NULL,
    next_billing_date DATE NOT NULL,
    auto_renew BOOLEAN DEFAULT TRUE,
    status ENUM('Active', 'Suspended', 'Cancelled', 'Expired') DEFAULT 'Active',
    description TEXT,
    contract_file VARCHAR(255),
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_status (status),
    INDEX idx_next_billing (next_billing_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Service Payments - Track payments for services
CREATE TABLE IF NOT EXISTS service_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_id INT NOT NULL,
    payment_date DATE NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    payment_method ENUM('Cash', 'Bank Transfer', 'Mobile Money', 'Cheque', 'Card') NOT NULL,
    reference_number VARCHAR(100),
    notes TEXT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_payment_date (payment_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Ledger Entries (Double Entry Bookkeeping)
CREATE TABLE IF NOT EXISTS ledger_entries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entry_date DATE NOT NULL,
    reference_number VARCHAR(50) NOT NULL,
    account_id INT NOT NULL,
    entry_type ENUM('Debit', 'Credit') NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    description TEXT,
    source_type ENUM('Invoice', 'Expense', 'Cashbook', 'Asset', 'Service', 'Manual') NOT NULL,
    source_id INT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (account_id) REFERENCES chart_of_accounts(id),
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_entry_date (entry_date),
    INDEX idx_account (account_id),
    INDEX idx_reference (reference_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Budget Planning
CREATE TABLE IF NOT EXISTS budgets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    budget_name VARCHAR(100) NOT NULL,
    account_id INT NOT NULL,
    budget_period ENUM('Monthly', 'Quarterly', 'Yearly') NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    allocated_amount DECIMAL(15,2) NOT NULL,
    spent_amount DECIMAL(15,2) DEFAULT 0,
    status ENUM('Active', 'Completed', 'Exceeded') DEFAULT 'Active',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (account_id) REFERENCES chart_of_accounts(id),
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_period (start_date, end_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert Default Chart of Accounts
INSERT INTO chart_of_accounts (account_code, account_name, account_type, description) VALUES
-- Assets
('1000', 'Assets', 'Asset', 'Main asset account'),
('1100', 'Current Assets', 'Asset', 'Short-term assets'),
('1110', 'Cash', 'Asset', 'Cash on hand'),
('1120', 'Bank Account', 'Asset', 'Money in bank accounts'),
('1130', 'Accounts Receivable', 'Asset', 'Money owed by customers'),
('1200', 'Fixed Assets', 'Asset', 'Long-term assets'),
('1210', 'Equipment', 'Asset', 'Office and business equipment'),
('1220', 'Furniture', 'Asset', 'Office furniture'),
('1230', 'Vehicles', 'Asset', 'Company vehicles'),
('1240', 'Buildings', 'Asset', 'Real estate properties'),

-- Liabilities
('2000', 'Liabilities', 'Liability', 'Main liability account'),
('2100', 'Current Liabilities', 'Liability', 'Short-term liabilities'),
('2110', 'Accounts Payable', 'Liability', 'Money owed to suppliers'),
('2120', 'Short-term Loans', 'Liability', 'Loans due within one year'),
('2200', 'Long-term Liabilities', 'Liability', 'Long-term debt'),
('2210', 'Long-term Loans', 'Liability', 'Loans due after one year'),

-- Equity
('3000', 'Equity', 'Equity', 'Owner equity'),
('3100', 'Owner Capital', 'Equity', 'Capital invested by owners'),
('3200', 'Retained Earnings', 'Equity', 'Accumulated profits'),

-- Revenue
('4000', 'Revenue', 'Revenue', 'Main revenue account'),
('4100', 'Sales Revenue', 'Revenue', 'Income from sales'),
('4200', 'Service Revenue', 'Revenue', 'Income from services'),
('4300', 'Other Income', 'Revenue', 'Other sources of income'),

-- Expenses
('5000', 'Expenses', 'Expense', 'Main expense account'),
('5100', 'Operating Expenses', 'Expense', 'Day-to-day business expenses'),
('5110', 'Rent', 'Expense', 'Office and property rent'),
('5120', 'Utilities', 'Expense', 'Electricity, water, internet'),
('5130', 'Salaries', 'Expense', 'Employee salaries and wages'),
('5140', 'Office Supplies', 'Expense', 'Stationery and supplies'),
('5150', 'Marketing', 'Expense', 'Advertising and promotion'),
('5160', 'Insurance', 'Expense', 'Business insurance'),
('5170', 'Maintenance', 'Expense', 'Repairs and maintenance'),
('5180', 'Travel', 'Expense', 'Business travel expenses'),
('5190', 'Professional Fees', 'Expense', 'Legal, accounting, consulting'),
('5200', 'Software & Subscriptions', 'Expense', 'Software licenses and subscriptions');

