# Installation & Setup Guide

# Elite Vision Security - Invoice & Payroll Management System

## 📋 System Overview

Your new invoice system includes:

✅ **Core Features**

- Complete invoice generation system
- Operator/employee management
- Payment tracking and reconciliation
- Email delivery of invoices
- PDF generation for printing
- Professional reporting & analytics
- Database persistence (SQLite)

✅ **Additional Components**

- RESTful API endpoints for integrations
- Email logging and delivery tracking
- Customizable email templates
- Audit logging
- Error logging system
- Mobile-responsive interface

---

## 📁 File Structure Created

```
evs-payroll-system/
│
├── 📄 Core Pages
│   ├── index.php              → Main Dashboard
│   ├── payments.php           → Payment Tracking Interface
│   ├── reports.php            → Reports & Analytics
│   ├── README.md              → Full Documentation
│   ├── QUICKSTART.md          → Quick Start Guide
│   └── INSTALLATION.md        → This File
│
├── ⚙️ Configuration & Database
│   ├── config.php             → Configuration Settings
│   ├── database.php           → Database Connection
│   └── schema.sql             → Database Schema
│
├── 📦 PHP Classes
│   └── classes/
│       ├── OperatorsManager.php   → Operator CRUD
│       ├── InvoicesManager.php    → Invoice Operations
│       ├── PaymentsManager.php    → Payment Tracking
│       ├── EmailManager.php       → Email Sending
│       └── PDFGenerator.php       → PDF Creation
│
├── 🔌 API Endpoints
│   └── api/
│       ├── invoices.php       → Invoice API
│       ├── operators.php      → Operator API
│       ├── payments.php       → Payment API
│       └── generate_pdf.php   → PDF Generation
│
└── 📂 Runtime Folders (Created Automatically)
    ├── pdfs/                  → Generated PDF Files
    └── logs/                  → System Logs

```

---

## 🔧 Installation Steps

### Step 1: File Verification

Ensure all files are in place:

```
✓ index.php
✓ config.php
✓ database.php
✓ schema.sql
✓ classes/ folder with all PHP files
✓ api/ folder with endpoint files
✓ README.md
✓ QUICKSTART.md
```

### Step 2: Create Writable Directories

The system needs these folders (created automatically on first run):

```bash
# Using terminal (Linux/Mac):
mkdir -p pdfs
mkdir -p logs
chmod 755 pdfs logs

# On Windows, folders auto-create when needed
```

### Step 3: Configure the System

Edit `config.php` and update:

**Company Information:**

```php
define('COMPANY_NAME', 'Your Company Name');
define('COMPANY_EMAIL', 'your-email@company.com');
define('COMPANY_PHONE', '(555) 000-0000');
define('COMPANY_ADDRESS', '123 Your Street, City, State 12345');
```

**Email Settings (Optional - for invoice sending):**

```php
// Gmail Example
define('SMTP_SERVER', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-16-character-app-password');
define('SMTP_FROM_EMAIL', 'noreply@yourcompany.com');
```

**Tax Rate:**

```php
define('DEFAULT_TAX_RATE', 8.5);  // Use your local tax rate
```

### Step 4: Access the System

1. Open your web browser
2. Navigate to: `http://localhost/evs-payroll-system/`
3. The database will auto-initialize
4. You'll see the main dashboard

---

## ⚙️ Configuration Details

### Email Setup (Gmail)

**Option A: Gmail with App Password**

1. Go to https://myaccount.google.com/apppasswords
2. Select "Mail" and "Windows Computer"
3. Copy the generated 16-character password
4. Update config.php:
   ```php
   define('SMTP_USERNAME', 'your-email@gmail.com');
   define('SMTP_PASSWORD', 'xxxxxxxxxxxxxxxx');  // 16-char app password
   ```

**Option B: Custom SMTP Server**

```php
define('SMTP_SERVER', 'mail.yourserver.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'user@yourserver.com');
define('SMTP_PASSWORD', 'your-password');
define('SMTP_FROM_EMAIL', 'noreply@yourserver.com');
```

### Database Settings

The system uses SQLite (no additional setup needed):

```php
define('DB_PATH', __DIR__ . '/invoices.db');
```

The database file is created automatically on first access.

---

## 🚀 First Use Walkthrough

### 1. Add an Operator (Employee)

```
Dashboard → 👥 Operators → "+ Add New Operator"
- Name: John Smith
- Email: john@company.com
- Hourly Rate: $25.00
- Hours Per Day: 12
- Click "Add Operator"
```

### 2. Create an Invoice

```
Dashboard → ✨ Create Invoice
- Select operator: John Smith
- Period Start: 2025-09-01
- Period End: 2025-09-15
- Click "+ Add Day" for each worked day (9 days)
- Click "Create Invoice"
```

### 3. Email the Invoice

```
Dashboard → 📄 Invoices
- Find invoice in list
- Click "Email" button
- System generates PDF and sends automatically
```

### 4. Record a Payment

```
Dashboard → 💳 Payments
- Click "Record Payment" tab
- Select invoice from dropdown
- Enter amount: $2700.00
- Payment Date: 2025-09-20
- Payment Method: Bank Transfer
- Click "Record Payment"
- Payment confirmation emailed automatically
```

### 5. View Reports

```
Dashboard → 📈 Reports
- See revenue analytics
- Filter by date range or operator
- Export to CSV for accounting
```

---

## 🔑 Key Features Explained

### Invoice Status Workflow

```
Draft
  ↓ (Create invoice)
Sent
  ↓ (Email to operator)
Unpaid / Partially Paid
  ↓ (Receive payment)
Paid
```

### Payment Tracking

- **Unpaid**: No payments received
- **Partially Paid**: Partial payment received (shows progress %)
- **Paid**: Full payment received
- Days overdue calculated automatically

### Operator Dashboard

Each operator has:

- Total invoices generated
- Total revenue owed
- Paid vs. unpaid status
- Collection rate percentage
- Email delivery history

### Report Types

1. **Summary Statistics**: Total revenue, paid amounts, invoice counts
2. **Revenue by Operator**: Bar chart and detailed breakdown
3. **Invoice Status**: Breakdown by status and payment status
4. **Recent Invoices**: Last 10 invoices created
5. **CSV Export**: Download all data for Excel

---

## 📡 API Usage Examples

### Get All Invoices

```bash
GET /api/invoices.php?action=list
Response: JSON array of all invoices
```

### Create Operator

```bash
POST /api/operators.php?action=create
Body: {
  "name": "Jane Doe",
  "email": "jane@company.com",
  "hourly_rate": 30.00,
  "hours_per_day": 12
}
```

### Record Payment

```bash
POST /api/payments.php?action=record
Body: {
  "invoice_id": 1,
  "operator_id": 1,
  "amount": 2700.00,
  "payment_date": "2025-09-20",
  "payment_method": "transfer"
}
```

### Get Payment Summary

```bash
GET /api/payments.php?action=get_summary
Response: JSON with total payments, amounts, etc.
```

---

## 📊 Database Tables

### Operators Table

- ID, Name, Email, Phone
- Hourly Rate, Hours Per Day
- Status (active/inactive/archived)
- Created/Updated timestamps

### Invoices Table

- ID, Invoice Number, Operator
- Period Start/End
- Subtotal, Tax, Total Amount
- Status (draft/sent/paid/etc.)
- Payment tracking fields
- Email delivery count

### Invoice Items Table

- ID, Invoice ID, Work Date
- Hours, Hourly Rate, Line Total

### Payments Table

- ID, Invoice ID, Operator ID
- Payment Date, Amount
- Payment Method, Reference Number
- Notes, Created timestamp

### Email Logs Table

- ID, Invoice ID, Recipient
- Subject, Status
- Sent timestamp, Opened timestamp

---

## 🔒 Security Recommendations

### For Production Deployment:

1. **Add Authentication**

   ```php
   // Add login system to protect pages
   session_start();
   if (!isset($_SESSION['user_id'])) {
       header('Location: login.php');
       exit;
   }
   ```

2. **Use Environment Variables**

   ```php
   // Instead of hardcoding credentials
   define('SMTP_PASSWORD', getenv('SMTP_PASSWORD'));
   ```

3. **Enable HTTPS**
   - Get SSL certificate
   - Redirect all traffic to HTTPS
   - Set secure cookie flag

4. **Database Backups**
   - Backup `invoices.db` daily
   - Store backups off-server
   - Test restore procedure

5. **File Permissions**
   ```bash
   chmod 750 config.php      # Restrict access
   chmod 755 pdfs/
   chmod 755 logs/
   ```

---

## 🐛 Troubleshooting

### Database Issues

**Problem**: "Database connection failed"
**Solution**:

1. Check if `/logs/` folder exists
2. Verify write permissions on folder
3. Delete `invoices.db` and reload page

### Email Not Sending

**Problem**: Emails not being sent
**Solution**:

1. Verify SMTP settings in `config.php`
2. Check `/logs/` for error details
3. Test with Gmail first (use App Password)
4. Verify port 587 is open in firewall

### PDF Generation Failed

**Problem**: PDF files not being created
**Solution**:

1. Ensure `/pdfs/` folder exists
2. Verify folder is writable
3. Check disk space
4. Review logs for errors

### Missing Operators

**Problem**: No operators showing in list
**Solution**:

1. Add operators first via Dashboard
2. Verify database was initialized
3. Check email field is optional but other fields required

---

## 📈 Scaling Tips

### For Multiple Users

- Add authentication layer
- Implement role-based access
- Add audit logging per user

### For Large Datasets

- Archive old invoices
- Implement pagination
- Add database indexes
- Consider MySQL migration

### For High Volume

- Implement job queue for emails
- Use background processing
- Add caching layer
- Consider API rate limiting

---

## 📞 Support Resources

1. **Documentation**: See README.md for detailed docs
2. **Quick Start**: See QUICKSTART.md for common tasks
3. **Error Logs**: Check `/logs/` directory
4. **Configuration**: Review config.php comments
5. **Database**: Use SQLite browser to inspect data

---

## ✅ Verification Checklist

After installation, verify:

- [ ] Can access dashboard at http://localhost/evs-payroll-system/
- [ ] Can add an operator
- [ ] Can create an invoice
- [ ] Database file `invoices.db` was created
- [ ] `/logs/` folder was created
- [ ] `/pdfs/` folder was created
- [ ] Can access Reports page
- [ ] Can access Payments page
- [ ] Email configuration set (if using email)

---

## 🎉 You're Ready!

Your invoice system is now fully operational. Next steps:

1. **Customize** company settings in config.php
2. **Add** your operators/employees
3. **Create** your first invoices
4. **Send** invoices to operators via email
5. **Track** payments and view reports

Refer to QUICKSTART.md for common tasks.

**Happy invoicing!** 📊

---

_Last Updated: September 2025_
_Version: 1.0.0_
