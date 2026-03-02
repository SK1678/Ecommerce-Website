<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Centrally handle sending emails using saved SMTP settings
 */
function sendCustomEmail($to_email, $to_name, $subject, $html_body, $alt_body = '')
{
    global $con, $web_settings;

    // 1. Fetch saved settings
    $res = $con->query("SELECT * FROM mail_settings LIMIT 1");
    $mail_settings = ($res && $res->num_rows > 0) ? $res->fetch_assoc() : null;

    if (!$mail_settings || empty($mail_settings['mail_enabled'])) {
        return ['success' => false, 'message' => 'Email system is disabled or not configured.'];
    }

    if (empty($mail_settings['mail_username']) || empty($mail_settings['mail_password'])) {
        return ['success' => false, 'message' => 'SMTP credentials (username/password) are missing.'];
    }

    // 2. Load PHPMailer
    $pm_path = realpath(__DIR__ . '/../vendor/phpmailer/phpmailer/src');
    if (!$pm_path || !file_exists($pm_path . '/PHPMailer.php')) {
        // Try fallback path if the above fails
        $pm_path = realpath(__DIR__ . '/../vendor/phpmailer/src');
        if (!$pm_path || !file_exists($pm_path . '/PHPMailer.php')) {
            return ['success' => false, 'message' => 'PHPMailer files not found in vendor directory. Please check installation.'];
        }
    }

    $pm_path .= DIRECTORY_SEPARATOR;
    require_once $pm_path . 'Exception.php';
    require_once $pm_path . 'PHPMailer.php';
    require_once $pm_path . 'SMTP.php';

    $mailer = new PHPMailer(true);

    try {
        // Server settings
        $mailer->isSMTP();
        $mailer->Host = $mail_settings['mail_host'];
        $mailer->SMTPAuth = true;
        $mailer->Username = $mail_settings['mail_username'];
        $mailer->Password = $mail_settings['mail_password'];
        $mailer->Port = (int)$mail_settings['mail_port'];
        $mailer->CharSet = 'UTF-8';
        $mailer->Timeout = 15;

        // Encryption
        if ($mail_settings['mail_encryption'] === 'tls') {
            $mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        }
        elseif ($mail_settings['mail_encryption'] === 'ssl') {
            $mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        }
        else {
            $mailer->SMTPSecure = '';
            $mailer->SMTPAutoTLS = false;
        }

        // Recipients
        $site_name = !empty($web_settings['site_title']) ? $web_settings['site_title'] : 'ByteBazaar';
        $from_addr = !empty($mail_settings['mail_from_address']) ? $mail_settings['mail_from_address'] : $mail_settings['mail_username'];
        $from_name = !empty($mail_settings['mail_from_name']) ? $mail_settings['mail_from_name'] : $site_name;

        $mailer->setFrom($from_addr, $from_name);
        $mailer->addAddress($to_email, $to_name);

        // Content
        $mailer->isHTML(true);
        $mailer->Subject = $subject;
        $mailer->Body = $html_body;
        $mailer->AltBody = $alt_body ?: strip_tags($html_body);

        $mailer->send();
        return ['success' => true];

    }
    catch (Exception $e) {
        return ['success' => false, 'message' => 'PHPMailer Error: ' . $mailer->ErrorInfo];
    }
    catch (\Exception $e) {
        return ['success' => false, 'message' => 'General Error: ' . $e->getMessage()];
    }
}

/**
 * Send a professional digital invoice email for a specific order
 */
function sendOrderInvoiceEmail($oid)
{
    global $con, $web_settings;

    $oid = (int)$oid;

    // 1. Fetch Order & Customer Details
    $order_query = "SELECT o.*, a.afname, a.alname, a.email, a.phone 
                    FROM `orders` o 
                    JOIN `accounts` a ON o.aid = a.aid 
                    WHERE o.oid = $oid LIMIT 1";
    $order_res = $con->query($order_query);

    if (!$order_res || $order_res->num_rows === 0) {
        return ['success' => false, 'message' => "Order #$oid not found."];
    }

    $order = $order_res->fetch_assoc();

    // 2. Fetch Order Items
    $items_query = "SELECT od.*, p.pname 
                    FROM `order-details` od 
                    JOIN `products` p ON od.pid = p.pid 
                    WHERE od.oid = $oid";
    $items_res = $con->query($items_query);

    if (!$items_res) {
        return ['success' => false, 'message' => "Could not fetch items for Order #$oid."];
    }

    $site_name = !empty($web_settings['site_title']) ? $web_settings['site_title'] : 'ByteBazaar';
    $currency = !empty($web_settings['currency']) ? $web_settings['currency'] : 'BDT';

    // 3. Compose Invoice HTML
    $items_html = "";
    while ($item = $items_res->fetch_assoc()) {
        $subtotal = $item['price'] * $item['qty'];
        $items_html .= "
        <tr>
            <td style='padding: 12px 0; border-bottom: 1px solid #edf2f7;'>
                <div style='font-weight: 600; color: #2d3748;'>{$item['pname']}</div>
                <div style='font-size: 12px; color: #718096;'>Qty: {$item['qty']} x " . number_format($item['price'], 2) . "</div>
            </td>
            <td style='padding: 12px 0; border-bottom: 1px solid #edf2f7; text-align: right; font-weight: 600; color: #2d3748;'>
                $currency " . number_format($subtotal, 2) . "
            </td>
        </tr>";
    }

    $payment_status_label = ($order['payment_status'] === 'paid') ? 
        "<span style='background: #c6f6d5; color: #22543d; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase;'>Paid</span>" :
        "<span style='background: #feebc8; color: #744210; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase;'>Pending</span>";

    $payment_method_label = strtoupper($order['payment_method']);

    $html_body = "
    <!DOCTYPE html><html><head><meta charset='UTF-8'>
    <style>
        body{margin:0;padding:0;background-color:#f7fafc;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;}
        .container{max-width:600px;margin:20px auto;background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 25px rgba(0,0,0,0.05);}
        .header{background:linear-gradient(135deg, #088178, #05635d);padding:40px;text-align:center;color:#ffffff;}
        .header h1{margin:0;font-size:28px;font-weight:800;letter-spacing:-0.5px;}
        .header p{margin:10px 0 0;opacity:0.9;font-size:16px;}
        .content{padding:40px;}
        .invoice-box{background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:24px;margin-bottom:30px;}
        .row{display:flex;justify-content:space-between;margin-bottom:12px;font-size:14px;}
        .label{color:#718096;font-weight:500;}
        .value{color:#2d3748;font-weight:600;}
        .table{width:100%;border-collapse:collapse;margin-bottom:20px;}
        .total-row{border-top:2px solid #e2e8f0;margin-top:20px;padding-top:20px;}
        .footer{background:#f8fafc;padding:30px;text-align:center;color:#a0aec0;font-size:12px;border-top:1px solid #edf2f7;}
        .address-box{font-size:14px;color:#4a5568;line-height:1.6;margin-top:20px;}
    </style></head><body>
    <div class='container'>
        <div class='header'>
            <h1>Order Confirmed!</h1>
            <p>Thank you for your purchase, {$order['afname']}!</p>
        </div>
        <div class='content'>
            <div style='margin-bottom: 25px; text-align: center;'>
                <div style='font-size: 14px; color: #718096;'>Order Number</div>
                <div style='font-size: 24px; font-weight: 800; color: #088178;'>#ORD-" . str_pad($order['oid'], 6, '0', STR_PAD_LEFT) . "</div>
            </div>

            <div class='invoice-box'>
                <div class='row'><span class='label'>Order Date:</span><span class='value'>" . date('M d, Y', strtotime($order['dateod'])) . "</span></div>
                <div class='row'><span class='label'>Payment Method:</span><span class='value'>$payment_method_label</span></div>
                <div class='row'><span class='label'>Status:</span><span class='value'>$payment_status_label</span></div>
            </div>

            <table class='table'>
                <thead>
                    <tr>
                        <th style='text-align: left; color: #718096; font-size: 12px; text-transform: uppercase; padding-bottom: 10px; border-bottom: 2px solid #edf2f7;'>Product</th>
                        <th style='text-align: right; color: #718096; font-size: 12px; text-transform: uppercase; padding-bottom: 10px; border-bottom: 2px solid #edf2f7;'>Total</th>
                    </tr>
                </thead>
                <tbody>
                    $items_html
                </tbody>
            </table>

            <div class='total-row'>
                <div style='display: flex; justify-content: space-between; align-items: center;'>
                    <span style='font-size: 18px; font-weight: 700; color: #2d3748;'>Grand Total</span>
                    <span style='font-size: 24px; font-weight: 800; color: #088178;'>$currency " . number_format($order['total'], 2) . "</span>
                </div>
            </div>

            <div class='address-box'>
                <div style='font-weight: 700; margin-bottom: 5px; color: #2d3748;'>Shipping Address:</div>
                {$order['afname']} {$order['alname']}<br>
                {$order['address']}<br>
                {$order['city']}, {$order['country']}<br>
                Phone: {$order['phone']}
            </div>
            
            <div style='margin-top: 40px; text-align: center;'>
                <p style='font-size: 14px; color: #718096;'>We will notify you once your order has been shipped!</p>
            </div>
        </div>
        <div class='footer'>
            &copy; " . date('Y') . " $site_name. All rights reserved.<br>
            If you have any questions, feel free to reply to this email.
        </div>
    </div></body></html>";

    $subject = "🛍️ Digital Invoice — Order #ORD-" . str_pad($order['oid'], 6, '0', STR_PAD_LEFT);
    $alt_body = "Thank you for your order! Your total is $currency " . number_format($order['total'], 2) . ". Order ID: #ORD-" . str_pad($order['oid'], 6, '0', STR_PAD_LEFT);

    return sendCustomEmail($order['email'], $order['afname'] . ' ' . $order['alname'], $subject, $html_body, $alt_body);
}

/**
 * Send an email notification to the customer when their order status changes
 */
function sendOrderStatusUpdateEmail($oid, $new_status)
{
    global $con, $web_settings;

    $oid = (int)$oid;

    // 1. Fetch Order & Customer Details
    $order_query = "SELECT o.*, a.afname, a.alname, a.email, a.phone 
                    FROM `orders` o 
                    JOIN `accounts` a ON o.aid = a.aid 
                    WHERE o.oid = $oid LIMIT 1";
    $order_res = $con->query($order_query);

    if (!$order_res || $order_res->num_rows === 0) {
        return ['success' => false, 'message' => "Order #$oid not found."];
    }

    $order = $order_res->fetch_assoc();

    // 2. Fetch Order Items
    $items_query = "SELECT od.*, p.pname 
                    FROM `order-details` od 
                    JOIN `products` p ON od.pid = p.pid 
                    WHERE od.oid = $oid";
    $items_res = $con->query($items_query);

    $items_html = "";
    if ($items_res) {
        while ($item = $items_res->fetch_assoc()) {
            $subtotal = $item['price'] * $item['qty'];
            $items_html .= "
            <tr>
                <td style='padding: 8px 0; border-bottom: 1px solid #edf2f7; font-size: 14px;'>
                    <div style='font-weight: 600; color: #2d3748;'>{$item['pname']}</div>
                    <div style='font-size: 12px; color: #718096;'>Qty: {$item['qty']}</div>
                </td>
                <td style='padding: 8px 0; border-bottom: 1px solid #edf2f7; text-align: right; font-weight: 600; color: #2d3748; font-size: 14px;'>
                    " . number_format($subtotal, 2) . "
                </td>
            </tr>";
        }
    }

    $site_name = !empty($web_settings['site_title']) ? $web_settings['site_title'] : 'ByteBazaar';
    $currency = !empty($web_settings['currency']) ? $web_settings['currency'] : 'BDT';
    $order_num = "#ORD-" . str_pad($order['oid'], 6, '0', STR_PAD_LEFT);

    // Status specific messaging
    $status_msg = "";
    $icon = "📦";
    $accent_color = "#088178";

    switch (strtolower($new_status)) {
        case 'processing':
            $status_msg = "We're currently preparing your items for shipment. We'll let you know once they are on the way!";
            $icon = "⚙️";
            $accent_color = "#3498db";
            break;
        case 'shipped':
            $status_msg = "Great news! Your order has been shipped and is on its way to you.";
            $icon = "🚚";
            $accent_color = "#f39c12";
            break;
        case 'delivered':
            $status_msg = "Your order has been successfully delivered! We hope you love your purchase.";
            $icon = "✅";
            $accent_color = "#27ae60";
            break;
        case 'cancelled':
            $status_msg = "Your order has been cancelled. If you have any questions, please contact our support team.";
            $icon = "❌";
            $accent_color = "#e74c3c";
            break;
        default:
            $status_msg = "The status of your order has been updated to <strong>" . htmlspecialchars($new_status) . "</strong>.";
            break;
    }

    $html_body = "
    <!DOCTYPE html><html><head><meta charset='UTF-8'>
    <style>
        body{margin:0;padding:0;background-color:#f7fafc;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;}
        .container{max-width:600px;margin:20px auto;background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 25px rgba(0,0,0,0.05);}
        .header{background: $accent_color; padding:40px;text-align:center;color:#ffffff;}
        .header h1{margin:0;font-size:28px;font-weight:800;letter-spacing:-0.5px;}
        .header p{margin:10px 0 0;opacity:0.9;font-size:16px;}
        .content{padding:40px;}
        .status-box{background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:24px;text-align:center;margin-bottom:30px;}
        .status-badge {
            display: inline-block;
            background: $accent_color;
            color: #ffffff;
            padding: 8px 20px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 20px;
        }
        .order-meta{display:flex;justify-content:space-between;border-top:1px solid #edf2f7;padding-top:20px;margin-top:20px;font-size:14px;}
        .label{color:#718096;font-weight:500;}
        .value{color:#2d3748;font-weight:600;}
        .footer{background:#f8fafc;padding:30px;text-align:center;color:#a0aec0;font-size:12px;border-top:1px solid #edf2f7;}
        .table{width:100%;border-collapse:collapse;margin:20px 0;}
    </style></head><body>
    <div class='container'>
        <div class='header'>
            <h1>Order Update</h1>
            <p>Order $order_num</p>
        </div>
        <div class='content'>
            <div class='status-box'>
                <div style='font-size: 48px; margin-bottom: 10px;'>$icon</div>
                <div class='status-badge'>$new_status</div>
                <p style='color: #4a5568; font-size: 16px; line-height: 1.6;'>Hello {$order['afname']},<br><br>$status_msg</p>
            </div>

            <div style='font-weight: 700; font-size: 16px; color: #2d3748; margin-bottom: 10px; border-bottom: 2px solid #edf2f7; padding-bottom: 5px;'>Order Summary</div>
            <table class='table'>
                <tbody>
                    $items_html
                </tbody>
            </table>

            <div class='order-meta'>
                <div><span class='label'>Order Date:</span><br><span class='value'>" . date('M d, Y', strtotime($order['dateod'])) . "</span></div>
                <div style='text-align: right;'><span class='label'>Total Amount:</span><br><span class='value'>$currency " . number_format($order['total'], 2) . "</span></div>
            </div>

            <div style='margin-top: 30px; border-top: 1px solid #edf2f7; padding-top: 20px;'>
                 <div style='font-weight: 700; margin-bottom: 5px; color: #2d3748;'>Shipping to:</div>
                <p style='font-size: 14px; color: #4a5568; line-height: 1.6; margin: 0;'>
                    {$order['afname']} {$order['alname']}<br>
                    {$order['address']}<br>
                    {$order['city']}, {$order['country']}
                </p>
            </div>
            
            <div style='text-align: center; margin-top: 30px;'>
                <p style='font-size: 14px; color: #718096;'>Thank you for choosing $site_name!</p>
            </div>
        </div>
        <div class='footer'>
            &copy; " . date('Y') . " $site_name. All rights reserved.<br>
            If you have any questions, feel free to contact us.
        </div>
    </div></body></html>";

    $subject = "$icon Order Update: $order_num is now $new_status";
    $alt_body = "Hello {$order['afname']}, the status of your order $order_num has been updated to $new_status. $status_msg";

    return sendCustomEmail($order['email'], $order['afname'] . ' ' . $order['alname'], $subject, $html_body, $alt_body);
}
