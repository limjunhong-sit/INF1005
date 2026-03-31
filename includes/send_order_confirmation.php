<?php

require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendOrderConfirmationEmail($toEmail, $toName, $orderData)
{
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'in-v3.mailjet.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'eb56202a1b22e22598020fff7f5d1b73';
        $mail->Password = 'db3cc53eeb896c084ef1c601dbc5929d';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('fujitaayumi1@gmail.com', 'Uniclothes');
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