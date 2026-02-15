# Email Management System - Setup & Usage Guide

## 📧 Overview

A complete email management system has been integrated into your invoice system that allows you to:
- **Track all sent emails** (quotations, invoices, general)
- **Receive and read email replies** via IMAP
- **View email threads/conversations**
- **Compose and reply to emails** from within the system
- **Filter and search emails**

---

## 🚀 Setup Instructions

### Step 1: Create Database Tables

Run the SQL script to create the required tables:

```bash
mysql -u root invoice_system < database_emails.sql
```

Or manually run the SQL from `database_emails.sql` in phpMyAdmin.

**Tables Created:**
- `emails` - Stores all email records
- `email_attachments` - Stores attachment metadata (for incoming emails)
- `email_settings` - System email settings (optional)

### Step 2: Enable IMAP (Optional but Recommended)

To receive email replies, enable IMAP in `includes/config.php`:

```php
// Email Configuration (IMAP - for receiving replies)
define('IMAP_ENABLED', true);  // Change from false to true
define('IMAP_HOST', 'mail.codecatalystug.com');
define('IMAP_PORT', 993);
define('IMAP_USERNAME', 'info@codecatalystug.com');
define('IMAP_PASSWORD', 'Cod3C@t@!ystUg');
define('IMAP_SSL', true);
define('IMAP_FOLDER', 'INBOX');
```

### Step 3: Install PHP IMAP Extension (If Not Installed)

**For XAMPP on Windows:**
1. Open `php.ini` (usually in `C:\xampp\php\php.ini`)
2. Find the line `;extension=imap`
3. Remove the semicolon: `extension=imap`
4. Restart Apache

**For Linux:**
```bash
sudo apt-get install php-imap
sudo systemctl restart apache2
```

### Step 4: Test Email Sending

1. Navigate to any Quotation or Invoice
2. Click "Send Email"
3. The email will now be logged in the database
4. Check the Emails section in the navigation menu

---

## 📝 Features

### 1. **Automatic Email Logging**

All emails sent through the system (quotations, invoices) are automatically logged with:
- Full email content (HTML and text)
- All recipients (To, CC, BCC)
- Attachment information
- Success/failure status
- Link to related quotation/invoice

### 2. **Email List with Filters**

Access via: **Navigation Menu → Emails**

Features:
- Filter by direction (Incoming/Outgoing)
- Filter by reference type (Quotation/Invoice/General)
- Filter by status (Sent/Received/Read/Failed)
- Search by subject or recipient
- Pagination
- Unread email badge in navigation

### 3. **Email Threading/Conversations**

View complete email threads including:
- Original email
- All replies
- Related quotation or invoice links
- Automatic reply detection

### 4. **Compose & Reply**

**Compose New Email:**
- Navigate Menu → Emails → Compose Email
- Support for To, CC, BCC
- HTML email support
- Basic formatting toolbar

**Reply to Email:**
- Open any incoming email
- Click "Reply" button
- Original message is quoted
- Automatic subject prefixing (Re:)
- Threading maintained via Message-ID

### 5. **IMAP Sync (Incoming Emails)**

When IMAP is enabled:
- Click "Check for New Emails" button
- System fetches new emails from last 7 days
- Automatically links replies to original sent emails
- Marks emails as unread until you open them

---

## 🔧 Usage Workflow

### Sending a Quotation/Invoice Email

1. Go to Quotations or Invoices
2. View a quotation/invoice
3. Click "Send Email" button
4. Fill in:
   - To: (required)
   - CC: (optional, comma-separated)
   - BCC: (optional, comma-separated)
5. Click "Send Email"
6. **Automatically:**
   - PDF is generated and attached
   - Email is sent
   - Email is logged in database
   - Quotation/invoice status updated

### Viewing Sent Emails

1. Navigate Menu → Emails
2. Filter: Direction = Outgoing
3. Click on any email to view full details
4. See related quotation/invoice link

### Checking for Replies

**If IMAP is enabled:**
1. Navigate Menu → Emails
2. Click "Check for New Emails"
3. System fetches new emails
4. Incoming emails appear in list
5. Unread count shows in navigation badge

**If IMAP is disabled:**
- Emails are only tracked when sent from the system
- No incoming email functionality

### Replying to an Email

1. Navigate Menu → Emails
2. Click on an incoming email
3. Click "Reply" button
4. Compose your reply
5. Click "Send Email"
6. Reply is linked to original thread

---

## 📊 Database Schema

### `emails` Table Fields

| Field | Type | Description |
|-------|------|-------------|
| id | int | Primary key |
| message_id | varchar | Email Message-ID header |
| in_reply_to | varchar | For threading replies |
| reference_type | enum | 'quotation', 'invoice', 'general' |
| reference_id | int | ID of related quote/invoice |
| direction | enum | 'outgoing', 'incoming' |
| from_email | varchar | Sender email |
| to_email | text | Recipients (comma-separated) |
| cc_email | text | CC recipients |
| bcc_email | text | BCC recipients |
| subject | varchar | Email subject |
| body_html | longtext | HTML email body |
| body_text | text | Plain text body |
| has_attachment | boolean | Attachment flag |
| status | enum | 'sent', 'received', 'read', 'failed' |
| sent_at | datetime | When sent/received |

---

## 🎯 Tips & Best Practices

### 1. **Regular IMAP Sync**
- Set up a cron job to run `emails/sync.php` every 5-15 minutes
- Or manually click "Check for New Emails" regularly

### 2. **Email Security**
- Keep SMTP/IMAP passwords secure
- Use environment variables for production
- Consider using App Passwords for Gmail

### 3. **Storage Management**
- Email bodies with HTML can be large
- Consider archiving old emails periodically
- Implement email retention policies

### 4. **Monitoring**
- Check failed emails regularly
- Review error messages in email details
- Monitor unread email count

---

## 🐛 Troubleshooting

### Emails Not Sending?

1. **Check SMTP Configuration**
   ```php
   // In config.php
   define('SMTP_HOST', 'your-host');
   define('SMTP_USERNAME', 'your-username');
   define('SMTP_PASSWORD', 'your-password');
   ```

2. **Test Email Configuration**
   - Use `test_email.php`
   - Check error logs

3. **Check Email Logs**
   - Navigate → Emails
   - Filter by Status = Failed
   - View error messages

### IMAP Not Working?

1. **Check IMAP Extension**
   ```php
   <?php
   if (function_exists('imap_open')) {
       echo "IMAP is installed";
   } else {
       echo "IMAP is NOT installed";
   }
   ?>
   ```

2. **Verify IMAP Settings**
   - Test with email client (Thunderbird, Outlook)
   - Check firewall/ports

3. **Check Error Logs**
   - Look in Apache/PHP error logs
   - Check for authentication errors

### Emails Not Logging to Database?

1. **Check Database Connection**
2. **Run database_emails.sql**
3. **Check PHP error logs**
4. **Verify `logEmailToDatabase()` function**

---

## 🔐 Security Considerations

### Production Deployment

1. **Use Environment Variables**
   ```php
   define('SMTP_PASSWORD', getenv('SMTP_PASSWORD'));
   define('IMAP_PASSWORD', getenv('IMAP_PASSWORD'));
   ```

2. **Restrict Access**
   - Only admins can sync emails
   - Role-based email viewing (if needed)

3. **Sanitize Email Content**
   - Already implemented: `htmlspecialchars()` on display
   - Consider additional XSS protection

4. **HTTPS Required**
   - Always use HTTPS in production
   - Protects email content in transit

---

## 📈 Future Enhancements

Potential features to add:
- Email templates
- Scheduled emails
- Email analytics/reports
- Attachment upload for compose
- Rich text editor (CKEditor, TinyMCE)
- Email archiving
- Spam filtering
- Email signatures
- Auto-responders

---

## 🆘 Support

For issues or questions:
1. Check error logs (`check_error_log.php`)
2. Review this guide
3. Test with `test_email.php`
4. Check email status in Emails list

---

## ✅ Quick Setup Checklist

- [ ] Run `database_emails.sql`
- [ ] Configure SMTP settings in `config.php`
- [ ] (Optional) Enable and configure IMAP
- [ ] (Optional) Install PHP IMAP extension
- [ ] Test sending an email from quotation/invoice
- [ ] Verify email appears in Emails list
- [ ] (If IMAP enabled) Test syncing emails
- [ ] (If IMAP enabled) Test replying to an email

---

**Congratulations!** 🎉 Your email management system is now fully integrated!

