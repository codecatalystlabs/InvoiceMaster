-- Code Catalyst Labs - Computation / Data Integrity Fixes
-- Run this once against the invoice_system database (e.g. via phpMyAdmin).
--
-- WHY: The sales tables were created with DECIMAL(10,2), which can only store
-- values up to 99,999,999.99. For UGX (and other low-denomination currencies),
-- invoice/quotation totals routinely exceed this and MySQL silently clamps the
-- stored value to the maximum, producing "weird figures". The accounting tables
-- already use DECIMAL(15,2); this migration brings the sales tables in line.
--
-- Safe to re-run: MODIFY COLUMN simply re-applies the wider type.

USE invoice_system;

-- Invoices
ALTER TABLE invoices
    MODIFY COLUMN subtotal DECIMAL(15,2) DEFAULT 0.00,
    MODIFY COLUMN tax      DECIMAL(15,2) DEFAULT 0.00,
    MODIFY COLUMN discount DECIMAL(15,2) DEFAULT 0.00,
    MODIFY COLUMN total    DECIMAL(15,2) DEFAULT 0.00;

-- Invoice line items
ALTER TABLE invoice_items
    MODIFY COLUMN unit_price DECIMAL(15,2) NOT NULL,
    MODIFY COLUMN total      DECIMAL(15,2) NOT NULL;

-- Quotations
ALTER TABLE quotations
    MODIFY COLUMN subtotal DECIMAL(15,2) DEFAULT 0.00,
    MODIFY COLUMN tax      DECIMAL(15,2) DEFAULT 0.00,
    MODIFY COLUMN discount DECIMAL(15,2) DEFAULT 0.00,
    MODIFY COLUMN total    DECIMAL(15,2) DEFAULT 0.00;

-- Quotation line items
ALTER TABLE quotation_items
    MODIFY COLUMN unit_price DECIMAL(15,2) NOT NULL,
    MODIFY COLUMN total      DECIMAL(15,2) NOT NULL;
