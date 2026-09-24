# Quick Start Guide - Elite Vision Security Invoice System

## 🚀 First-Time Setup (5 Minutes)

### Step 1: Initial Configuration

1. Open `config.php` in your text editor
2. Update these key settings:
   ```php
   define('COMPANY_NAME', 'Your Company Name');
   define('COMPANY_EMAIL', 'your@company.com');
   define('COMPANY_PHONE', '(555) 123-4567');
   ```

### Step 2: Access the Dashboard

1. Open your browser to: `http://localhost/evs-payroll-system/`
2. Database will auto-create on first load
3. You should see the main dashboard

### Step 3: Add Your First Operator

1. Click "**👥 Operators**" tab
2. Click "**+ Add New Operator**" button
3. Fill in operator details:
   - Name: John Smith
   - Email: john@example.com
   - Hourly Rate: $25.00
   - Hours/Day: 12
4. Click "Add Operator"

### Step 4: Create Your First Invoice

1. Click "**✨ Create Invoice**" tab
2. Select the operator you just created
3. Set date range (Period Start & End)
4. Click "**+ Add Day**" for each worked day
5. Click "**Create Invoice**"

### Step 5: Email the Invoice

1. Go to "**📄 Invoices**" tab
2. Find your invoice
3. Click "**Email**" button
4. Invoice generates as PDF and sends automatically

### Step 6: Record a Payment

1. Click "**💳 Payments**" tab
2. Select the invoice from dropdown
3. Enter payment amount and date
4. Choose payment method
5. Click "**Record Payment**"
6. Payment confirmation email sent automatically

### Step 7: View Reports

1. Click "**📈 Reports**" tab
2. See revenue analytics and statistics
3. Click "**📥 Export CSV**" to download data

---

## 📋 Common Tasks

### Create Multiple Invoices at Once

1. Add all operators first
2. Create invoice for each operator
3. Send them all at once from the Invoices tab

### Track Unpaid Invoices

1. Go to **Payments** → **Unpaid Invoices** tab
2. Shows all outstanding invoices
3. Shows "Days Overdue" count
4. Easy access to record payments

### Export Data to Excel

1. Go to **Reports**
2. Select date range (optional)
3. Click **📥 Export CSV**
4. Open CSV file in Excel

### Resend Failed Invoice

1. Go to **📄 Invoices** tab
2. Find the invoice
3. Click **Email** again to resend

### View Operator Performance

1. Go to **👥 Operators** tab
2. Click **Detail** on any operator
3. See total invoices and payment stats

---

## 🔧 Configuration Tips

### Enable Email Sending

Edit `config.php` and update:

**For Gmail:**

```php
define('SMTP_SERVER', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password');
```

**For Office 365:**

```php
define('SMTP_SERVER', 'smtp.office365.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@company.com');
define('SMTP_PASSWORD', 'your-password');
```

### Customize Company Info

All these appear on invoices:

```php
define('COMPANY_NAME', 'Elite Vision Security');
define('COMPANY_EMAIL', 'billing@company.com');
define('COMPANY_PHONE', '(555) 000-0000');
define('COMPANY_ADDRESS', '123 Main St, City, State 12345');
```

### Set Default Tax Rate

```php
define('DEFAULT_TAX_RATE', 8.5);  // 8.5% sales tax
```

---

## 💡 Pro Tips

1. **Bulk Operations**: Add all operators before creating invoices
2. **Invoice Numbering**: Each invoice gets auto-incremented number (2026-001, 2026-002, etc.)
3. **Payment Tracking**: Partially paid invoices show progress bar on Payments page
4. **Overdue Alerts**: Days overdue highlighted in red on Unpaid Invoices tab
5. **CSV Reports**: Export data for use in Excel, Google Sheets, or accounting software
6. **Email Templates**: Customize email messages by editing database directly
7. **Mobile Friendly**: System works on mobile browsers - great for on-the-go access

---

## ⚠️ Important Notes

- **Database**: `invoices.db` is created automatically - do NOT delete it
- **Backups**: Regularly backup the `invoices.db` file
- **Logs**: Check `/logs/` directory if something breaks
- **PDFs**: Generated invoices stored in `/pdfs/` folder
- **Credentials**: Update SMTP settings before sending emails

---

## 🆘 Troubleshooting

### "Database connection failed"

- Ensure `/logs/` folder exists and is writable
- Check file permissions on `invoices.db`
- Delete `invoices.db` to reinitialize

### "Emails not sending"

- Verify SMTP settings in `config.php`
- Check error logs in `/logs/` directory
- Ensure port 587 is open in firewall
- For Gmail, use App Password not regular password

### "PDF not generating"

- Ensure `/pdfs/` folder exists and is writable
- Check disk space availability
- Review error logs

### "Operators list is empty"

- This is normal - add operators first
- Use "+ Add New Operator" button
- Must have at least one operator to create invoices

---

## 📞 Need Help?

1. Check the **README.md** file for detailed documentation
2. Review error logs in `/logs/` directory
3. Check configuration in `config.php`
4. Verify database isn't corrupted - delete `invoices.db` and reload page

---

**Your invoice system is ready to go! Start by adding an operator and creating your first invoice.** ✨
