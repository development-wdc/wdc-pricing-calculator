<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Ensure PHPMailer is installed via Composer

// Only process POST requests
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data and sanitize inputs
    $category = strip_tags(trim($_POST["category"] ?? ''));
    $service = strip_tags(trim($_POST["service"] ?? ''));
    $fullname = strip_tags(trim($_POST["fullname"] ?? ''));
    $organization = strip_tags(trim($_POST["organization"] ?? ''));
    $email = filter_var(trim($_POST["email"] ?? ''), FILTER_SANITIZE_EMAIL);
    $role = strip_tags(trim($_POST["role"] ?? ''));
    $suggested = strip_tags(trim($_POST["suggested"] ?? ''));
    $explanation = strip_tags(trim($_POST["explanation"] ?? ''));
    $billing_mode = strip_tags(trim($_POST["billing_mode"] ?? ''));
    $phase = strip_tags(trim($_POST["phase"] ?? ''));
    $requestor = strip_tags(trim($_POST["requestor"] ?? ''));
    $country = strip_tags(trim($_POST["country"] ?? ''));
    $sector = strip_tags(trim($_POST["sector"] ?? ''));
    $size = strip_tags(trim($_POST["size"] ?? ''));
    $speed = strip_tags(trim($_POST["speed"] ?? ''));
    $quality = strip_tags(trim($_POST["quality"] ?? ''));
    $experience = strip_tags(trim($_POST["experience"] ?? ''));
    $physical_presence = isset($_POST["physical_presence"]) ? "Yes" : "No";
    $start_date = strip_tags(trim($_POST["start_date"] ?? ''));
    $calculated_price = strip_tags(trim($_POST["calculated_price"] ?? ''));
    $live_support = isset($_POST["live_support"]) ? "Yes" : "No";

    // Validate required fields
    if (empty($fullname) || empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL) || empty($category) || empty($service)) {
        http_response_code(400);
        echo "Please complete all required fields (name, email, category, service) and try again.";
        exit;
    }
    if (empty($organization)) {
        http_response_code(400);
        echo "Organization is required.";
        exit;
    }
    if (empty($role)) {
        http_response_code(400);
        echo "Role is required.";
        exit;
    }
    if (!is_numeric($suggested) || $suggested < 0) {
        http_response_code(400);
        echo "Proposed price must be a valid non-negative number.";
        exit;
    }
    if (empty($explanation)) {
        http_response_code(400);
        echo "Explanation is required.";
        exit;
    }

    // Build email content for both WDC and submitter
    $email_content = "WDC Pricing Proposal Submission\n\n";
    $email_content .= "Service Details:\n";
    $email_content .= "-----------------\n";
    $email_content .= "Category: $category\n";
    $email_content .= "Service: $service\n";
    $email_content .= "Billing Mode: $billing_mode\n";
    $email_content .= "Phase: $phase\n";
    $email_content .= "Start Date: $start_date\n";
    $email_content .= "Requestor Type: $requestor\n";
    $email_content .= "Country Type: $country\n";
    $email_content .= "Sector Type: $sector\n";
    $email_content .= "Organization Size: $size\n";
    $email_content .= "Deployment Speed: $speed\n";
    $email_content .= "Quality: $quality\n";
    $email_content .= "Experience Level: $experience\n";
    $email_content .= "Physical Presence: $physical_presence\n";
    $email_content .= "Live Support: $live_support\n";
    $email_content .= "Calculated Price: $calculated_price\n\n";
    $email_content .= "Client Information:\n";
    $email_content .= "-----------------\n";
    $email_content .= "Name: $fullname\n";
    $email_content .= "Organization: $organization\n";
    $email_content .= "Email: $email\n";
    $email_content .= "Role: $role\n\n";
    $email_content .= "Proposal Details:\n";
    $email_content .= "-----------------\n";
    $email_content .= "Proposed Price: $suggested\n";
    $email_content .= "Explanation: $explanation\n\n";
    $email_content .= "Submission Time: " . date('Y-m-d H:i:s') . "\n";

    // Confirmation email content for submitter
    $confirmation_content = "Thank You for Your WDC Pricing Proposal Submission\n\n";
    $confirmation_content .= "Dear $fullname,\n\n";
    $confirmation_content .= "Thank you for submitting your proposal to the World Disaster Center. Below is a recap of your submission:\n\n";
    $confirmation_content .= $email_content;
    $confirmation_content .= "\nOur team will review your proposal and contact you soon.\n\nBest regards,\nWorld Disaster Center Team";

    // Initialize PHPMailer
    $mail = new PHPMailer(true);
    try {
        // SMTP settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'services@worlddisastercenter.org';
        $mail->Password = 'vsii lmtr txur wmsd';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;

        // Email to WDC
        $mail->setFrom($email, $fullname);
        $mail->addAddress('services@worlddisastercenter.org');
        $mail->addReplyTo($email, $fullname);
        // Add CC recipients for proposal email
        $cc_emails = [
            'office@worlddisastercenter.org',
            'dngaka@worlddisastercenter.org',
            'finance@worlddisastercenter.org',
            'admin@worlddisastercenter.org'
        ];
        foreach ($cc_emails as $cc_email) {
            $mail->addCC($cc_email);
        }
        $mail->Subject = "New Pricing Proposal: $service from $organization";
        $mail->Body = $email_content;
        $mail->send();

        // Email to submitter
        $mail->clearAddresses();
        $mail->addAddress($email, $fullname);
        // Add CC recipients for confirmation email
        foreach ($cc_emails as $cc_email) {
            $mail->addCC($cc_email);
        }
        $mail->Subject = "WDC Proposal Submission Confirmation";
        $mail->Body = $confirmation_content;
        $mail->send();

        http_response_code(200);
        echo "Thank you! Your proposal has been submitted, and a confirmation email has been sent to $email.";
    } catch (Exception $e) {
        http_response_code(500);
        echo "Oops! Something went wrong and we couldn't send your proposal. Error: {$mail->ErrorInfo}";
    }
} else {
    http_response_code(403);
    echo "There was a problem with your submission, please try again.";
}
?>