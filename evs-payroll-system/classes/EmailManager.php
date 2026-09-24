<?php
/**
 * Email Manager - Handle sending invoices and notifications
 * Uses PHPMailer or built-in mail() function
 */

require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../config.php';

class EmailManager
{
    private $db;
    private $from_email;
    private $from_name;
    private $last_error = '';

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->from_email = SMTP_FROM_EMAIL;
        $this->from_name = SMTP_FROM_NAME;
    }

    public function getLastError()
    {
        return $this->last_error;
    }

    /**
     * Send invoice to operator
     */
    public function sendInvoiceEmail($invoice_id, $pdf_path = null, $is_update = false)
    {
        try {
            $invoice = $this->getInvoiceData($invoice_id);

            if (!$invoice || !$invoice['operator_email']) {
                logMessage("Cannot send invoice {$invoice_id}: operator email not found", 'error');
                return false;
            }

            // Get email template
            $template = $this->getEmailTemplate('invoice_notification');

            // Replace placeholders
            $subject = $this->replacePlaceholders($template['subject'], $invoice);
            if ($is_update)
                $subject = 'Updated - ' . $subject;
            $body = $this->replacePlaceholders($template['body'], $invoice);
            if ($is_update)
                $body = '<p><strong>This invoice was updated. Please use the attached revised invoice.</strong></p>' . $body;

            // Send email
            $result = $this->sendEmail(
                $invoice['operator_email'],
                $subject,
                $body,
                $pdf_path
            );

            if ($result) {
                // Log email sent
                $this->logEmailSent($invoice_id, $invoice['operator_id'], $invoice['operator_email'], $subject);

                // Update invoice status
                $stmt = $this->db->prepare('
                    UPDATE invoices 
                    SET status = ?, email_sent_count = email_sent_count + 1, last_email_sent = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?
                ');
                $stmt->execute(['sent', $invoice_id]);

                logMessage("Invoice {$invoice_id} emailed to {$invoice['operator_email']}", 'info');
                return true;
            }

            return false;
        } catch (Exception $e) {
            logMessage("Error sending invoice email: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Send payment confirmation email
     */
    public function sendPaymentConfirmationEmail($invoice_id, $payment_amount, $payment_date, $payment_method)
    {
        try {
            $invoice = $this->getInvoiceData($invoice_id);

            if (!$invoice || !$invoice['operator_email']) {
                return false;
            }

            $template = $this->getEmailTemplate('payment_confirmation');

            $data = array_merge($invoice, [
                'PAYMENT_AMOUNT' => number_format($payment_amount, 2),
                'PAYMENT_DATE' => $payment_date,
                'PAYMENT_METHOD' => ucwords(str_replace('_', ' ', $payment_method))
            ]);

            $subject = $this->replacePlaceholders($template['subject'], $data);
            $body = $this->replacePlaceholders($template['body'], $data);

            $result = $this->sendEmail(
                $invoice['operator_email'],
                $subject,
                $body
            );

            if ($result) {
                $this->logEmailSent($invoice_id, $invoice['operator_id'], $invoice['operator_email'], $subject);
            }

            return $result;
        } catch (Exception $e) {
            logMessage("Error sending payment confirmation: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Core email sending function
     */
    private function sendEmail($recipient, $subject, $body, $attachment = null)
    {
        $connection = null;
        try {
            if (SMTP_USERNAME === 'your-email@gmail.com' || SMTP_PASSWORD === 'your-app-password') {
                throw new RuntimeException('SMTP is not configured. Update SMTP_USERNAME and SMTP_PASSWORD in config.php.');
            }

            $connection = stream_socket_client(
                'tcp://' . SMTP_SERVER . ':' . SMTP_PORT,
                $error_number,
                $error_message,
                20,
                STREAM_CLIENT_CONNECT
            );
            if (!$connection)
                throw new RuntimeException($error_message);
            stream_set_timeout($connection, 20);

            $this->expectSmtpResponse($connection, 220);
            $this->sendSmtpCommand($connection, 'EHLO localhost', 250);
            $this->sendSmtpCommand($connection, 'STARTTLS', 220);
            if (!stream_socket_enable_crypto($connection, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new RuntimeException('Could not establish SMTP TLS encryption.');
            }
            $this->sendSmtpCommand($connection, 'EHLO localhost', 250);
            $this->sendSmtpCommand($connection, 'AUTH LOGIN', 334);
            $this->sendSmtpCommand($connection, base64_encode(SMTP_USERNAME), 334);
            $this->sendSmtpCommand($connection, base64_encode(SMTP_PASSWORD), 235);
            $this->sendSmtpCommand($connection, 'MAIL FROM:<' . $this->from_email . '>', 250);
            $this->sendSmtpCommand($connection, 'RCPT TO:<' . $recipient . '>', 250);
            $this->sendSmtpCommand($connection, 'DATA', 354);

            $boundary = '=_EVS_' . bin2hex(random_bytes(12));
            $message = 'From: ' . $this->from_name . ' <' . $this->from_email . ">\r\n";
            $message .= 'To: <' . $recipient . ">\r\n";
            $message .= 'Subject: ' . $subject . "\r\n";
            $message .= "MIME-Version: 1.0\r\nContent-Type: multipart/mixed; boundary=\"{$boundary}\"\r\n\r\n";
            $message .= "--{$boundary}\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit\r\n\r\n{$body}\r\n";

            if ($attachment && is_file($attachment)) {
                $filename = basename($attachment);
                $encoded_file = chunk_split(base64_encode(file_get_contents($attachment)));
                $message .= "--{$boundary}\r\nContent-Type: application/pdf; name=\"{$filename}\"\r\nContent-Disposition: attachment; filename=\"{$filename}\"\r\nContent-Transfer-Encoding: base64\r\n\r\n{$encoded_file}\r\n";
            }

            $message .= "--{$boundary}--\r\n.\r\n";
            fwrite($connection, $message);
            $this->expectSmtpResponse($connection, 250);
            fwrite($connection, "QUIT\r\n");
            fclose($connection);
            logMessage("Email sent to {$recipient}", 'info');
            return true;
        } catch (Exception $e) {
            $this->last_error = $e->getMessage();
            if (is_resource($connection))
                fclose($connection);
            logMessage("Email sending error: " . $e->getMessage(), 'error');
            return false;
        }
    }

    private function sendSmtpCommand($connection, $command, $expected_code)
    {
        fwrite($connection, $command . "\r\n");
        $this->expectSmtpResponse($connection, $expected_code);
    }

    private function expectSmtpResponse($connection, $expected_code)
    {
        $response = '';
        do {
            $line = fgets($connection, 515);
            if ($line === false)
                throw new RuntimeException('No response from SMTP server.');
            $response .= $line;
        } while (isset($line[3]) && $line[3] === '-');

        $actual_code = (int) substr($response, 0, 3);
        if ($actual_code !== $expected_code) {
            throw new RuntimeException("SMTP error {$actual_code}: " . trim($response));
        }
    }

    /**
     * Get invoice data for email
     */
    private function getInvoiceData($invoice_id)
    {
        try {
            $stmt = $this->db->prepare('
                SELECT i.*, o.name as operator_name, o.email as operator_email
                FROM invoices i
                LEFT JOIN operators o ON i.operator_id = o.id
                WHERE i.id = ?
            ');
            $stmt->execute([$invoice_id]);
            $invoice = $stmt->fetch();

            if ($invoice) {
                // Get items to count total hours
                $items_stmt = $this->db->prepare('
                    SELECT SUM(hours) as total_hours FROM invoice_items WHERE invoice_id = ?
                ');
                $items_stmt->execute([$invoice_id]);
                $items = $items_stmt->fetch();

                $invoice['TOTAL_HOURS'] = $items['total_hours'] ?? 0;
            }

            return $invoice;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Get email template
     */
    private function getEmailTemplate($name)
    {
        try {
            $stmt = $this->db->prepare('SELECT * FROM email_templates WHERE name = ? LIMIT 1');
            $stmt->execute([$name]);
            return $stmt->fetch();
        } catch (Exception $e) {
            logMessage("Error getting email template: " . $e->getMessage(), 'error');
            return ['subject' => '', 'body' => ''];
        }
    }

    /**
     * Replace placeholders in email content
     */
    private function replacePlaceholders($content, $data)
    {
        $placeholders = [
            '{COMPANY_NAME}' => COMPANY_NAME,
            '{COMPANY_EMAIL}' => COMPANY_EMAIL,
            '{COMPANY_PHONE}' => COMPANY_PHONE,
            '{OPERATOR_NAME}' => $data['operator_name'] ?? '',
            '{INVOICE_NUMBER}' => $data['invoice_number'] ?? '',
            '{PERIOD_START}' => $this->formatDate($data['period_start'] ?? ''),
            '{PERIOD_END}' => $this->formatDate($data['period_end'] ?? ''),
            '{TOTAL_HOURS}' => $data['TOTAL_HOURS'] ?? $data['total_hours'] ?? 0,
            '{SUBTOTAL}' => number_format($data['subtotal'] ?? 0, 2),
            '{TAX_AMOUNT}' => number_format($data['tax_amount'] ?? 0, 2),
            '{TOTAL_AMOUNT}' => number_format($data['total_amount'] ?? 0, 2),
            '{PAYMENT_AMOUNT}' => $data['PAYMENT_AMOUNT'] ?? '',
            '{PAYMENT_DATE}' => $data['PAYMENT_DATE'] ?? date('Y-m-d'),
            '{PAYMENT_METHOD}' => $data['PAYMENT_METHOD'] ?? ''
        ];

        return str_replace(array_keys($placeholders), array_values($placeholders), $content);
    }

    /**
     * Format date for display
     */
    private function formatDate($date)
    {
        if (!$date)
            return '';
        return date('F j, Y', strtotime($date));
    }

    /**
     * Log email sent
     */
    private function logEmailSent($invoice_id, $operator_id, $recipient, $subject)
    {
        try {
            $stmt = $this->db->prepare('
                INSERT INTO email_logs (invoice_id, operator_id, recipient_email, subject, status)
                VALUES (?, ?, ?, ?, ?)
            ');
            $stmt->execute([$invoice_id, $operator_id, $recipient, $subject, 'sent']);
        } catch (Exception $e) {
            logMessage("Error logging email: " . $e->getMessage(), 'error');
        }
    }

    /**
     * Get email log for an invoice
     */
    public function getEmailLog($invoice_id)
    {
        try {
            $stmt = $this->db->prepare('
                SELECT * FROM email_logs
                WHERE invoice_id = ?
                ORDER BY sent_at DESC
            ');
            $stmt->execute([$invoice_id]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Resend invoice email
     */
    public function resendInvoiceEmail($invoice_id)
    {
        try {
            // Check if invoice exists
            $invoice = $this->getInvoiceData($invoice_id);
            if (!$invoice) {
                return false;
            }

            // Send email again
            return $this->sendInvoiceEmail($invoice_id);
        } catch (Exception $e) {
            logMessage("Error resending invoice: " . $e->getMessage(), 'error');
            return false;
        }
    }
}
?>