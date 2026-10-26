<?php
declare(strict_types=1);

// Replace these two email addresses with your real website email addresses.
$recipientEmail = 'jims&james@gmail.com';
$fromEmail      = 'no-reply@yourdomain.com';

function showResponse(string $title, string $message, bool $success): never
{
    $safeTitle   = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $safeMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    $color       = $success ? '#198754' : '#dc3545';
    $statusIcon  = $success ? '✓' : '!';

    http_response_code($success ? 200 : 400);

    echo <<<HTML
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{$safeTitle}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
            background: #f5f5f3;
            color: #1f1f1f;
            font-family: Arial, sans-serif;
        }
        .message-card {
            width: min(100%, 520px);
            padding: 40px 30px;
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 14px 45px rgba(0, 0, 0, 0.10);
            text-align: center;
        }
        .status {
            width: 64px;
            height: 64px;
            margin: 0 auto 20px;
            display: grid;
            place-items: center;
            border-radius: 50%;
            background: {$color};
            color: #fff;
            font-size: 32px;
            font-weight: 700;
        }
        h1 { margin: 0 0 12px; font-size: 28px; }
        p { margin: 0 0 24px; color: #666; line-height: 1.6; }
        a {
            display: inline-block;
            padding: 12px 22px;
            border-radius: 7px;
            background: #1f1f1f;
            color: #fff;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <main class="message-card">
        <div class="status">{$statusIcon}</div>
        <h1>{$safeTitle}</h1>
        <p>{$safeMessage}</p>
        <a href="javascript:history.back()">Go Back</a>
    </main>
</body>
</html>
HTML;
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    showResponse('Invalid Request', 'Please submit the enquiry form.', false);
}

// Read and clean the submitted values.
$name        = trim((string) ($_POST['name'] ?? ''));
$company     = trim((string) ($_POST['company'] ?? ''));
$phone       = trim((string) ($_POST['phone'] ?? ''));
$email       = trim((string) ($_POST['email'] ?? ''));
$requirement = trim((string) ($_POST['requirement'] ?? ''));

// All fields are required.
if ($name === '' || $company === '' || $phone === '' || $email === '' || $requirement === '') {
    showResponse('Missing Information', 'Please complete all required fields.', false);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    showResponse('Invalid Email', 'Please enter a valid email address.', false);
}

// Block CR/LF characters that could be used for email-header injection.
if (preg_match('/[\r\n]/', $name . $company . $phone . $email) === 1) {
    showResponse('Invalid Information', 'The submitted information contains invalid characters.', false);
}

// Apply reasonable length limits.
if (
    strlen($name) > 100 ||
    strlen($company) > 150 ||
    strlen($phone) > 30 ||
    strlen($email) > 190 ||
    strlen($requirement) > 5000
) {
    showResponse('Information Too Long', 'Please shorten your enquiry and submit it again.', false);
}

$subject = 'New Website Enquiry - ' . $company;

$message = "A new enquiry has been submitted through the website.\n\n";
$message .= "Full Name: {$name}\n";
$message .= "Company / Store Name: {$company}\n";
$message .= "Phone Number: {$phone}\n";
$message .= "Email Address: {$email}\n\n";
$message .= "Requirement:\n{$requirement}\n";

$headers = [
    'From: Website Enquiry <' . $fromEmail . '>',
    'Reply-To: ' . $email,
    'MIME-Version: 1.0',
    'Content-Type: text/plain; charset=UTF-8',
    'X-Mailer: PHP/' . phpversion(),
];

$sent = mail(
    $recipientEmail,
    $subject,
    $message,
    implode("\r\n", $headers)
);

if ($sent) {
    showResponse(
        'Enquiry Sent Successfully',
        'Thank you! We have received your enquiry and will contact you soon.',
        true
    );
}

showResponse(
    'Unable to Send Enquiry',
    'The message could not be sent right now. Please try again later.',
    false
);
