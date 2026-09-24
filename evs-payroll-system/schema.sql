-- Elite Vision Security Payroll & Invoice System Database Schema

-- Company Settings Table
CREATE TABLE IF NOT EXISTS settings (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  company_name TEXT DEFAULT 'Elite Vision Security',
  company_email TEXT,
  company_phone TEXT,
  address TEXT,
  tax_rate REAL DEFAULT 0.0,
  invoice_prefix TEXT DEFAULT '2026-',
  next_invoice_number INTEGER DEFAULT 1,
  smtp_server TEXT,
  smtp_port INTEGER DEFAULT 587,
  smtp_username TEXT,
  smtp_password TEXT,
  smtp_from_email TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Operators/Employees Table
CREATE TABLE IF NOT EXISTS operators (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL,
  email TEXT,
  phone TEXT,
  hourly_rate REAL DEFAULT 0.0,
  hours_per_day REAL DEFAULT 12,
  status TEXT DEFAULT 'active', -- active, inactive, archived
  notes TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Invoices Table
CREATE TABLE IF NOT EXISTS invoices (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  invoice_number TEXT UNIQUE NOT NULL,
  operator_id INTEGER NOT NULL,
  period_start DATE,
  period_end DATE,
  subtotal REAL DEFAULT 0.0,
  tax_amount REAL DEFAULT 0.0,
  total_amount REAL DEFAULT 0.0,
  status TEXT DEFAULT 'draft', -- draft, sent, paid, partially_paid, overdue
  payment_status TEXT DEFAULT 'unpaid', -- unpaid, partial, paid
  payment_date DATE,
  payment_amount REAL DEFAULT 0.0,
  notes TEXT,
  email_sent_count INTEGER DEFAULT 0,
  last_email_sent DATETIME,
  pdf_path TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (operator_id) REFERENCES operators(id)
);

-- Invoice Line Items (Days worked)
CREATE TABLE IF NOT EXISTS invoice_items (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  invoice_id INTEGER NOT NULL,
  work_date DATE NOT NULL,
  hours REAL DEFAULT 0.0,
  hourly_rate REAL DEFAULT 0.0,
  line_total REAL DEFAULT 0.0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE
);

-- Payment Records Table
CREATE TABLE IF NOT EXISTS payments (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  invoice_id INTEGER NOT NULL,
  operator_id INTEGER NOT NULL,
  payment_date DATE NOT NULL,
  amount REAL NOT NULL,
  payment_method TEXT, -- cash, check, transfer, card, other
  reference_number TEXT,
  notes TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (invoice_id) REFERENCES invoices(id),
  FOREIGN KEY (operator_id) REFERENCES operators(id)
);

-- Email Log Table
CREATE TABLE IF NOT EXISTS email_logs (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  invoice_id INTEGER,
  operator_id INTEGER,
  recipient_email TEXT NOT NULL,
  subject TEXT,
  status TEXT DEFAULT 'sent', -- sent, failed, bounced, opened
  error_message TEXT,
  sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  opened_at DATETIME,
  FOREIGN KEY (invoice_id) REFERENCES invoices(id),
  FOREIGN KEY (operator_id) REFERENCES operators(id)
);

-- Email Templates Table
CREATE TABLE IF NOT EXISTS email_templates (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT UNIQUE NOT NULL,
  subject TEXT,
  body TEXT,
  is_default BOOLEAN DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Audit Log Table
CREATE TABLE IF NOT EXISTS audit_logs (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  action TEXT NOT NULL, -- created, updated, deleted, emailed, paid
  entity_type TEXT NOT NULL, -- invoice, operator, payment, settings
  entity_id INTEGER,
  changes TEXT, -- JSON format
  user_info TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Create Indexes for Performance
CREATE INDEX IF NOT EXISTS idx_invoices_operator ON invoices(operator_id);
CREATE INDEX IF NOT EXISTS idx_invoices_status ON invoices(status);
CREATE INDEX IF NOT EXISTS idx_invoices_payment_status ON invoices(payment_status);
CREATE INDEX IF NOT EXISTS idx_invoices_created ON invoices(created_at);
CREATE INDEX IF NOT EXISTS idx_invoice_items_invoice ON invoice_items(invoice_id);
CREATE INDEX IF NOT EXISTS idx_payments_invoice ON payments(invoice_id);
CREATE INDEX IF NOT EXISTS idx_payments_operator ON payments(operator_id);
CREATE INDEX IF NOT EXISTS idx_email_logs_invoice ON email_logs(invoice_id);
CREATE INDEX IF NOT EXISTS idx_operators_status ON operators(status);

-- Insert Default Email Templates
INSERT OR IGNORE INTO email_templates (name, subject, body, is_default) VALUES (
  'invoice_notification',
  'Your Invoice #{INVOICE_NUMBER} from Elite Vision Security',
  '<html>
<body style="font-family: Arial, sans-serif; color: #1a2418;">
  <h2>Hello {OPERATOR_NAME},</h2>
  <p>Please find your invoice for the billing period <strong>{PERIOD_START} to {PERIOD_END}</strong> attached to this email.</p>
  
  <h3>Invoice Details:</h3>
  <ul>
    <li><strong>Invoice Number:</strong> {INVOICE_NUMBER}</li>
    <li><strong>Total Hours:</strong> {TOTAL_HOURS}</li>
    <li><strong>Subtotal:</strong> ${SUBTOTAL}</li>
    <li><strong>Tax:</strong> ${TAX_AMOUNT}</li>
    <li><strong>Total Amount Due:</strong> <span style="font-size: 18px; color: #1f3d1a;"><strong>${TOTAL_AMOUNT}</strong></span></li>
  </ul>
  
  <p>If you have any questions about this invoice, please contact us at {COMPANY_PHONE} or reply to this email.</p>
  
  <p>Thank you for your service!</p>
  <p><strong>{COMPANY_NAME}</strong></p>
</body>
</html>',
  1
);

INSERT OR IGNORE INTO email_templates (name, subject, body, is_default) VALUES (
  'payment_confirmation',
  'Payment Confirmation - Invoice #{INVOICE_NUMBER}',
  '<html>
<body style="font-family: Arial, sans-serif; color: #1a2418;">
  <h2>Hello {OPERATOR_NAME},</h2>
  <p>We have received your payment for Invoice {INVOICE_NUMBER}.</p>
  
  <h3>Payment Details:</h3>
  <ul>
    <li><strong>Invoice Number:</strong> {INVOICE_NUMBER}</li>
    <li><strong>Amount Paid:</strong> ${PAYMENT_AMOUNT}</li>
    <li><strong>Payment Date:</strong> {PAYMENT_DATE}</li>
    <li><strong>Payment Method:</strong> {PAYMENT_METHOD}</li>
  </ul>
  
  <p>Thank you!</p>
  <p><strong>{COMPANY_NAME}</strong></p>
</body>
</html>',
  0
);
