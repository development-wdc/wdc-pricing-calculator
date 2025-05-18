<?php
// Disable error display to prevent stray output
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(0);

// Clear any existing output buffers
while (ob_get_level() > 0) {
    ob_end_clean();
}
ob_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../../vendor/autoload.php';

// Set response header to JSON
header('Content-Type: application/json; charset=UTF-8');
// Set Netlify Function timeout to 20 seconds (doubled from default 10 seconds)
header('X-NF-Request-Timeout: 20');

// Netlify Functions handler
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();

// Use project directory for logging
$debug_log = __DIR__ . '/../../debug.log';
$phpmailer_log = __DIR__ . '/../../phpmailer.log';

// Log script version for verification
file_put_contents($debug_log, "Script Version: 2025-05-18-0710\n", FILE_APPEND);

// Log environment variable for debugging
$smtp_password = getenv('SMTP_PASSWORD') ?: 'vsii lmtr txur wmsd';
file_put_contents($debug_log, "SMTP Password: " . (empty($smtp_password) ? 'Not set' : 'Set') . "\n", FILE_APPEND);

// Log timeout header for verification
file_put_contents($debug_log, "Timeout Set: 20 seconds\n", FILE_APPEND);

// Log raw input for debugging
$raw_input = file_get_contents('php://input');
file_put_contents($debug_log, "Raw Input: $raw_input\n", FILE_APPEND);

// Use $_POST directly
$input = $_POST;

// Log parsed input
file_put_contents($debug_log, "Parsed Input: " . print_r($input, true) . "\n", FILE_APPEND);

// Sanitize inputs
$category = strip_tags(trim($input["category"] ?? ''));
$service = strip_tags(trim($input["service"] ?? ''));
$fullname = strip_tags(trim($input["fullname"] ?? ''));
$organization = strip_tags(trim($input["organization"] ?? ''));
$email = filter_var(trim($input["email"] ?? ''), FILTER_SANITIZE_EMAIL);
$role = strip_tags(trim($input["role"] ?? ''));
$suggested = strip_tags(trim($input["suggested"] ?? ''));
$explanation = strip_tags(trim($input["explanation"] ?? ''));
$billing_mode = strip_tags(trim($input["billing_mode"] ?? ''));
$phase = strip_tags(trim($input["phase"] ?? ''));
$requestor = strip_tags(trim($input["requestor"] ?? ''));
$country = strip_tags(trim($input["country"] ?? ''));
$sector = strip_tags(trim($input["sector"] ?? ''));
$size = strip_tags(trim($input["size"] ?? ''));
$speed = strip_tags(trim($input["speed"] ?? ''));
$quality = strip_tags(trim($input["quality"] ?? ''));
$experience = strip_tags(trim($input["experience"] ?? ''));
$physical_presence = isset($input["physical_presence"]) ? "Yes" : "No";
$start_date = strip_tags(trim($input["start_date"] ?? ''));
$calculated_price = strip_tags(trim($input["calculated_price"] ?? ''));
$live_support = isset($input["live_support"]) ? "Yes" : "No";

// Log sanitized inputs
file_put_contents($debug_log, "Sanitized Inputs: " . print_r([
    'category' => $category,
    'service' => $service,
    'fullname' => $fullname,
    'email' => $email
], true) . "\n", FILE_APPEND);

// Validate required fields with specific messages
if (empty($fullname)) {
    file_put_contents($debug_log, "Validation Failed: Full name is empty\n", FILE_APPEND);
    ob_end_clean();
    echo json_encode(['status' => 400, 'message' => 'Full name is required.']);
    exit;
}
if (empty($email)) {
    file_put_contents($debug_log, "Validation Failed: Email is empty\n", FILE_APPEND);
    ob_end_clean();
    echo json_encode(['status' => 400, 'message' => 'Email address is required.']);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    file_put_contents($debug_log, "Validation Failed: Invalid email format ($email)\n", FILE_APPEND);
    ob_end_clean();
    echo json_encode(['status' => 400, 'message' => 'A valid email address is required.']);
    exit;
}
if (empty($category)) {
    file_put_contents($debug_log, "Validation Failed: Category is empty\n", FILE_APPEND);
    ob_end_clean();
    echo json_encode(['status' => 400, 'message' => 'Category is required.']);
    exit;
}
if (empty($service)) {
    file_put_contents($debug_log, "Validation Failed: Service is empty\n", FILE_APPEND);
    ob_end_clean();
    echo json_encode(['status' => 400, 'message' => 'Service is required.']);
    exit;
}
if (empty($organization)) {
    file_put_contents($debug_log, "Validation Failed: Organization is empty\n", FILE_APPEND);
    ob_end_clean();
    echo json_encode(['status' => 400, 'message' => 'Organization is required.']);
    exit;
}
if (empty($role)) {
    file_put_contents($debug_log, "Validation Failed: Role is empty\n", FILE_APPEND);
    ob_end_clean();
    echo json_encode(['status' => 400, 'message' => 'Role is required.']);
    exit;
}
if (!is_numeric($suggested) || $suggested < 0) {
    file_put_contents($debug_log, "Validation Failed: Invalid proposed price ($suggested)\n", FILE_APPEND);
    ob_end_clean();
    echo json_encode(['status' => 400, 'message' => 'Proposed price must be a valid non-negative number.']);
    exit;
}
if (trim($explanation) === '') {
    file_put_contents($debug_log, "Validation Failed: Explanation is empty\n", FILE_APPEND);
    ob_end_clean();
    echo json_encode(['status' => 400, 'message' => 'Explanation is required.']);
    exit;
}

// Build email content
$email_content = "WDC Pricing Proposal Submission\n\n";
$email_content .= "Service Details:\n-----------------\n";
$email_content .= "Category: $category\nService: $service\nBilling Mode: $billing_mode\nPhase: $phase\nStart Date: $start_date\n";
$email_content .= "Requestor Type: $requestor\nCountry Type: $country\nSector Type: $sector\nOrganization Size: $size\n";
$email_content .= "Deployment Speed: $speed\nQuality: $quality\nExperience Level: $experience\nPhysical Presence: $physical_presence\n";
$email_content .= "Live Support: $live_support\nCalculated Price: $calculated_price\n\n";
$email_content .= "Client Information:\n-----------------\n";
$email_content .= "Name: $fullname\nOrganization: $organization\nEmail: $email\nRole: $role\n\n";
$email_content .= "Proposal Details:\n-----------------\n";
$email_content .= "Proposed Price: $suggested\nExplanation: $explanation\n\n";
$email_content .= "Submission Time: " . date('Y-m-d H:i:s') . "\n";

$confirmation_content = "Thank You for Your WDC Pricing Proposal Submission\n\n";
$confirmation_content .= "Dear $fullname,\n\nThank you for submitting your proposal to the World Disaster Center. Below is a recap of your submission:\n\n";
$confirmation_content .= $email_content;
$confirmation_content .= "\nOur team will review your proposal and contact you soon.\n\nBest regards,\nWorld Disaster Center Team";

// Initialize PHPMailer
$mail = new PHPMailer(true);
try {
    $mail->SMTPDebug = 2;
    $mail->Debugoutput = function($str, $level) use ($phpmailer_log) {
        file_put_contents($phpmailer_log, "[$level] $str\n", FILE_APPEND);
    };
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'services@worlddisastercenter.org';
    $mail->Password = $smtp_password;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port = 465;

    // Email to WDC
    $mail->setFrom($email, $fullname);
    $mail->addAddress('services@worlddisastercenter.org');
    $mail->addReplyTo($email, $fullname);
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
    foreach ($cc_emails as $cc_email) {
        $mail->addCC($cc_email);
    }
    $mail->Subject = "WDC Proposal Submission Confirmation";
    $mail->Body = $confirmation_content;
    $mail->send();

    file_put_contents($debug_log, "Submission Successful: Emails sent\n", FILE_APPEND);
    ob_end_clean();
    echo json_encode(['status' => 200, 'message' => "Thank you! Your proposal has been submitted, and a confirmation email has been sent to $email."]);
    exit;
} catch (Exception $e) {
    file_put_contents($debug_log, "PHPMailer Error: {$e->getMessage()}\n", FILE_APPEND);
    ob_end_clean();
    echo json_encode(['status' => 500, 'message' => "Oops! Something went wrong and we couldn't send your proposal. Error: {$e->getMessage()}"]);
    exit;
}

// Clear any stray output
ob_end_clean();
?>