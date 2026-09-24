# Elite Vision Security - Invoice & Payroll Management System

A complete enterprise-grade invoice generation and payroll management system with email capabilities, database persistence, payment tracking, and comprehensive reporting features.

## Features Overview

### 📊 Dashboard

- Real-time statistics and overview
- Recent invoice activity
- Quick access to all major functions
- Summary cards showing key metrics

### 📄 Invoice Management

- Create invoices for multiple operators
- Track worked days and hours
- Automatic calculation of totals and taxes
- Invoice status tracking (Draft, Sent, Paid, Partially Paid, Overdue)
- PDF generation for printing and archiving
- Invoice history and archival

### 👥 Operator Management

- Add and manage operator profiles
- Track hourly rates and working hours
- Email contact information
- Operator performance summary
- Invoice history per operator

### 📧 Email System

- Automatic invoice delivery to operators
- Customizable email templates
- Payment confirmation notifications
- Email delivery tracking
- Resend capability for failed emails
- Email logging and history

### 💳 Payment Tracking

- Record incoming payments
- Track payment status (Unpaid, Partially Paid, Paid)
- Payment history per invoice
- Multiple payment method support (Bank Transfer, Check, Cash, Card)
- Payment reconciliation
- Days overdue calculations

### 📈 Reports & Analytics

- Revenue by operator reports
- Payment collection rates
- Status breakdown charts
- CSV export functionality
- Date range filtering
- Operator-specific filtering

### 🔧 Advanced Features

- RESTful API endpoints for integration
- SQLite database with automatic initialization
- Comprehensive audit logging
- Error logging and debugging
- Responsive mobile-friendly interface
- Professional branding and UI

## System Architecture

```
evs-payroll-system/
├── index.php                 # Main dashboard
├── payments.php              # Payment tracking interface
├── reports.php               # Reports & analytics
├── config.php                # Configuration settings
├── database.php              # Database connection & initialization
├── schema.sql                # Database schema
├── classes/
│   ├── OperatorsManager.php   # Operator management
│   ├── InvoicesManager.php    # Invoice operations
│   ├── PaymentsManager.php    # Payment tracking
│   ├── EmailManager.php       # Email functionality
│   └── PDFGenerator.php       # PDF generation
├── api/
│   ├── invoices.php           # Invoice API endpoints
│   ├── operators.php          # Operator API endpoints
│   ├── payments.php           # Payment API endpoints
│   └── generate_pdf.php       # PDF generation endpoint
├── pdfs/                      # Generated PDF storage
├── logs/                      # Application logs
└── README.md                  # This file
```

## Database Schema

### Tables

- **settings** - Company configuration and invoice settings
- **operators** - Employee/operator information
- **invoices** - Invoice records with totals
- **invoice_items** - Daily line items for invoices
- **payments** - Payment records with tracking
- **email_logs** - Email delivery tracking
- **email_templates** - Customizable email templates
- **audit_logs** - System activity logging

## Setup Instructions

### Prerequisites

- PHP 7.4 or higher
- XAMPP or similar local server with SQLite support
- Modern web browser

### Installation Steps

1. **Copy files to web root**

   ```
   cp -r evs-payroll-system/ /path/to/xampp/htdocs/
   ```

2. **Configure settings** (Edit `config.php`)

   ```php
   // Update these with your actual settings:
   define('COMPANY_NAME', 'Your Company Name');
   define('COMPANY_EMAIL', 'your-email@company.com');
   define('COMPANY_PHONE', '(555) 000-0000');
   define('SMTP_SERVER', 'smtp.gmail.com');
   define('SMTP_USERNAME', 'your-email@gmail.com');
   define('SMTP_PASSWORD', 'your-app-password');
   ```

3. **Access the system**
   - Open in browser: `http://localhost/evs-payroll-system/`
   - Database will auto-initialize on first load
   - Start adding operators and creating invoices!

## API Endpoints

### Invoice Operations

```
GET  /api/invoices.php?action=list           # List all invoices
GET  /api/invoices.php?action=get&id=1       # Get specific invoice
POST /api/invoices.php?action=create         # Create new invoice
POST /api/invoices.php?action=update_status  # Update status
POST /api/invoices.php?action=send_email     # Email invoice
POST /api/invoices.php?action=delete         # Delete invoice
GET  /api/invoices.php?action=get_stats      # Get statistics
```

### Operator Operations

```
GET  /api/operators.php?action=list           # List operators
GET  /api/operators.php?action=get&id=1       # Get operator
GET  /api/operators.php?action=get_summary&id=1  # With stats
POST /api/operators.php?action=create         # Add operator
POST /api/operators.php?action=update         # Update operator
POST /api/operators.php?action=delete         # Delete operator
```

### Payment Operations

```
POST /api/payments.php?action=record                      # Record payment
GET  /api/payments.php?action=get_invoice_payments&invoice_id=1  # Invoice payments
GET  /api/payments.php?action=get_operator_payments&operator_id=1  # Operator payments
GET  /api/payments.php?action=get_summary                 # Payment summary
GET  /api/payments.php?action=get_invoice_payment_status&invoice_id=1  # Status
POST /api/payments.php?action=delete                      # Delete payment
```

## Usage Examples

### Creating an Invoice

1. Navigate to Dashboard
2. Click "✨ Create Invoice" tab
3. Select operator
4. Set period dates
5. Add worked days
6. Click "Create Invoice"

### Sending Invoices via Email

1. Go to "📄 Invoices" tab
2. Find invoice in list
3. Click "Email" button
4. System generates PDF and sends automatically

### Recording Payments

1. Navigate to "💳 Payments"
2. Click "Record Payment" tab
3. Select unpaid invoice
4. Enter payment details
5. Submit

### Viewing Reports

1. Navigate to "📈 Reports" section
2. Use filters (date range, operator)
3. View revenue analytics
4. Export to CSV if needed

## Email Configuration

### Gmail Setup

1. Enable 2-Factor Authentication
2. Generate App Password at https://myaccount.google.com/apppasswords
3. Update config.php with App Password:
   ```php
   define('SMTP_USERNAME', 'your-email@gmail.com');
   define('SMTP_PASSWORD', 'your-app-password');
   ```

### Custom SMTP

Update config.php with your SMTP server details:

```php
define('SMTP_SERVER', 'mail.yourserver.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'user@yourserver.com');
define('SMTP_PASSWORD', 'password');
```

## Features in Detail

### Invoice Status Workflow

- **Draft**: Initial creation, editable
- **Sent**: Emailed to operator, waiting for payment
- **Partially Paid**: Partial payment received
- **Paid**: Fully paid
- **Overdue**: Payment past due date

### Payment Methods

- Bank Transfer
- Check
- Cash
- Credit Card
- Other (custom)

### Operator Statistics

- Total invoices generated
- Total revenue per operator
- Paid vs. unpaid invoices
- Collection rate percentage
- Email delivery tracking

### Email Templates

System includes pre-configured templates:

- Invoice Notification
- Payment Confirmation

Custom templates can be added via database directly.

## Logging & Debugging

All system activity is logged to:

```
/evs-payroll-system/logs/YYYY-MM-DD.log
```

Enable detailed logging in config.php:

```php
define('LOG_ERRORS', true);
```

## Security Considerations

1. **File Permissions**
   - Set `pdfs/` folder to 755
   - Set `logs/` folder to 755
   - Database file readable/writable by web server

2. **Database Backups**
   - Regularly backup `invoices.db` file
   - Store backups in secure location

3. **Email Credentials**
   - Never commit credentials to version control
   - Use environment variables in production
   - Use app-specific passwords for services like Gmail

4. **Access Control**
   - Add authentication layer for production deployment
   - Implement user roles and permissions
   - Use HTTPS for all connections

## Troubleshooting

### Database Issues

- Delete `invoices.db` to reinitialize
- Check file permissions on database
- Verify `schema.sql` is in root directory

### Email Not Sending

- Verify SMTP configuration in config.php
- Check firewall/port settings (587 for SMTP)
- Review email logs in `/logs/` directory
- Test with Gmail App Password first

### PDF Not Generating

- Verify `pdfs/` folder exists and is writable
- Check disk space availability
- Review error logs

### Missing Classes

- Verify all files in `/classes/` directory exist
- Check require paths in PHP files
- Verify file permissions

## Future Enhancement Ideas

1. **User Authentication**
   - Login system with password hashing
   - Role-based access control
   - Activity logging per user

2. **Advanced Features**
   - Recurring invoice templates
   - Invoice scheduling
   - Expense tracking
   - Commission calculations
   - Multi-currency support

3. **Integrations**
   - QuickBooks integration
   - Stripe/PayPal payment gateway
   - Slack notifications
   - Google Drive backup

4. **Mobile App**
   - React Native mobile application
   - Offline invoice creation
   - Mobile payment recording

5. **Advanced Analytics**
   - Predictive payment analysis
   - Cash flow forecasting
   - Operator performance metrics
   - Seasonal revenue trends

## Support & Maintenance

For issues or questions:

1. Check the logs directory for error details
2. Review configuration settings
3. Verify database integrity
4. Check system requirements

## License

This system is proprietary to Elite Vision Security.

## Version History

**v1.0.0** - Initial Release

- Core invoice generation
- Email delivery system
- Payment tracking
- Comprehensive reporting
- RESTful API endpoints

---

**Created:** 2025
**Last Updated:** September 2025
**System:** Elite Vision Security Invoice & Payroll Management
