<?php

require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function loadEnvFromProjectRoot(): void
{
    static $loaded = false;
    if ($loaded) {
        return;
    }

    $envPath = dirname(__DIR__) . '/.env';
    if (!is_file($envPath) || !is_readable($envPath)) {
        $loaded = true;
        return;
    }

    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        $loaded = true;
        return;
    }

    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed === '' || str_starts_with($trimmed, '#')) {
            continue;
        }

        $parts = explode('=', $trimmed, 2);
        if (count($parts) !== 2) {
            continue;
        }

        $key = trim($parts[0]);
        $value = trim($parts[1]);
        if ($key === '') {
            continue;
        }

        if (
            (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
            (str_starts_with($value, "'") && str_ends_with($value, "'"))
        ) {
            $value = substr($value, 1, -1);
        }

        if (getenv($key) === false) {
            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }

    $loaded = true;
}

function sendOrderConfirmationEmail($toEmail, $toName, $orderData)
{
    loadEnvFromProjectRoot();

    $smtpHost = getenv('MAIL_SMTP_HOST') ?: '';
    $smtpPort = (int)(getenv('MAIL_SMTP_PORT') ?: 587);
    $smtpUser = getenv('MAIL_SMTP_USER') ?: '';
    $smtpPass = getenv('MAIL_SMTP_PASS') ?: '';
    $smtpFromEmail = getenv('MAIL_FROM_EMAIL') ?: '';
    $smtpFromName = getenv('MAIL_FROM_NAME') ?: 'UniClothes';

    if ($smtpHost === '' || $smtpUser === '' || $smtpPass === '' || $smtpFromEmail === '') {
        error_log('Order confirmation email config missing. Check .env mail variables.');
        return false;
    }

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = $smtpHost;
        $mail->SMTPAuth = true;
        $mail->Username = $smtpUser;
        $mail->Password = $smtpPass;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $smtpPort;

        $mail->setFrom($smtpFromEmail, $smtpFromName);
        $mail->addAddress($toEmail, $toName);

        $mail->isHTML(true);
        $mail->Subject = 'Order Confirmation #' . $orderData['order_id'];

        $safeName = htmlspecialchars($toName, ENT_QUOTES, 'UTF-8');
        $safeOrderId = htmlspecialchars((string)$orderData['order_id'], ENT_QUOTES, 'UTF-8');
        $safeOrderDate = htmlspecialchars((string)$orderData['order_date'], ENT_QUOTES, 'UTF-8');
        $safeTotal = number_format((float)$orderData['total'], 2);

        $itemsHtml = '';
        foreach ($orderData['items'] as $item) {
            $itemName = htmlspecialchars((string)$item['name'], ENT_QUOTES, 'UTF-8');
            $itemQty = (int)$item['quantity'];
            $itemPrice = number_format((float)$item['price'], 2);

            $itemsHtml .= "
                <tr>
                    <td style='padding:8px;border:1px solid #ddd;'>{$itemName}</td>
                    <td style='padding:8px;border:1px solid #ddd;text-align:center;'>{$itemQty}</td>
                    <td style='padding:8px;border:1px solid #ddd;text-align:right;'>$ {$itemPrice}</td>
                </tr>
            ";
        }

        $mail->Body = "
            <h2>Order Confirmed</h2>
            <p>Hi {$safeName},</p>
            <p>Thank you for your order. Your order has been confirmed.</p>
            <p><strong>Order number:</strong> {$safeOrderId}</p>
            <p><strong>Order date:</strong> {$safeOrderDate}</p>

            <table style='border-collapse:collapse;width:100%;max-width:600px;'>
                <thead>
                    <tr>
                        <th style='padding:8px;border:1px solid #ddd;text-align:left;'>Item</th>
                        <th style='padding:8px;border:1px solid #ddd;text-align:center;'>Qty</th>
                        <th style='padding:8px;border:1px solid #ddd;text-align:right;'>Price</th>
                    </tr>
                </thead>
                <tbody>
                    {$itemsHtml}
                </tbody>
            </table>

            <p><strong>Total:</strong> $ {$safeTotal}</p>
            <p>We will update you again when your order is shipped.</p>
        ";

        $mail->AltBody =
            "Order Confirmed\n" .
            "Hi {$toName},\n\n" .
            "Thank you for your order. Your order has been confirmed.\n" .
            "Order number: {$orderData['order_id']}\n" .
            "Order date: {$orderData['order_date']}\n" .
            "Total: $ {$safeTotal}\n";

        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log('Order confirmation email failed: ' . $mail->ErrorInfo);
        return false;
    }
}