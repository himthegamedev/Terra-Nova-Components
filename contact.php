<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;



// --------------------------------------------------
// Return JSON responses
// --------------------------------------------------

header('Content-Type: application/json');


// --------------------------------------------------
// Load PHPMailer manually
// --------------------------------------------------

require __DIR__ . '/phpmailer/src/Exception.php';
require __DIR__ . '/phpmailer/src/PHPMailer.php';
require __DIR__ . '/phpmailer/src/SMTP.php';


// --------------------------------------------------
// Load SMTP configuration
// --------------------------------------------------

// Use this if mail-config.php is ONE DIRECTORY ABOVE
// your website/public directory.

 $config = require __DIR__ . '/mail-config.php';


// --------------------------------------------------
// Only allow POST requests
// --------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    http_response_code(405);

    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed.'
    ]);

    exit;
}


// --------------------------------------------------
// Read submitted form values
// --------------------------------------------------

$name = trim($_POST['name'] ?? '');

$phone = trim($_POST['phone'] ?? '');

$email = trim($_POST['email'] ?? '');

$bestTime = trim($_POST['best-time'] ?? '');


// --------------------------------------------------
// Validate fields
// --------------------------------------------------

$errors = [];


if ($name === '') {
    $errors[] = 'Please enter your name.';
}


if ($phone === '') {
    $errors[] = 'Please enter your contact number.';
}


if (
    $email === '' ||
    !filter_var($email, FILTER_VALIDATE_EMAIL)
) {
    $errors[] = 'Please enter a valid email address.';
}


if ($bestTime === '') {
    $errors[] = 'Please select the best time to call.';
}


if (!empty($errors)) {

    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => implode(' ', $errors)
    ]);

    exit;
}


// --------------------------------------------------
// Validate uploaded attachments
// --------------------------------------------------

$attachments = [];
$totalAttachmentBytes = 0;
$maxAttachmentBytes = 20 * 1024 * 1024;
$maxTotalAttachmentBytes = 25 * 1024 * 1024;

if (isset($_FILES['files'])) {

    $uploadedFiles = $_FILES['files'];

    if (
        !is_array($uploadedFiles['name'] ?? null) ||
        !is_array($uploadedFiles['tmp_name'] ?? null) ||
        !is_array($uploadedFiles['error'] ?? null) ||
        !is_array($uploadedFiles['size'] ?? null)
    ) {
        http_response_code(400);

        echo json_encode([
            'success' => false,
            'message' => 'The uploaded files could not be processed.'
        ]);

        exit;
    }

    foreach ($uploadedFiles['error'] as $index => $uploadError) {

        if ($uploadError === UPLOAD_ERR_NO_FILE) {
            continue;
        }

        if ($uploadError !== UPLOAD_ERR_OK) {
            http_response_code(400);

            echo json_encode([
                'success' => false,
                'message' => 'A file could not be uploaded. Please try again.'
            ]);

            exit;
        }

        $temporaryPath = $uploadedFiles['tmp_name'][$index] ?? '';
        $fileSize = (int) ($uploadedFiles['size'][$index] ?? 0);

        if (
            $temporaryPath === '' ||
            !is_uploaded_file($temporaryPath) ||
            $fileSize <= 0
        ) {
            http_response_code(400);

            echo json_encode([
                'success' => false,
                'message' => 'An uploaded file is invalid.'
            ]);

            exit;
        }

        if ($fileSize > $maxAttachmentBytes) {
            http_response_code(400);

            echo json_encode([
                'success' => false,
                'message' => 'Each file must be 20 MB or smaller.'
            ]);

            exit;
        }

        $totalAttachmentBytes += $fileSize;

        if ($totalAttachmentBytes > $maxTotalAttachmentBytes) {
            http_response_code(400);

            echo json_encode([
                'success' => false,
                'message' => 'The combined file size must be 25 MB or smaller.'
            ]);

            exit;
        }

        $originalName = (string) ($uploadedFiles['name'][$index] ?? '');
        $safeFilename = basename(str_replace('\\', '/', $originalName));
        $safeFilename = preg_replace('/[\x00-\x1F\x7F]/', '', $safeFilename);

        if (!is_string($safeFilename) || $safeFilename === '') {
            $safeFilename = 'attachment';
        }

        $attachments[] = [
            'path' => $temporaryPath,
            'name' => $safeFilename
        ];
    }
}


// --------------------------------------------------
// Limit input lengths
// --------------------------------------------------

$name = mb_substr($name, 0, 100);

$phone = mb_substr($phone, 0, 50);

$email = mb_substr($email, 0, 150);

$bestTime = mb_substr($bestTime, 0, 50);


// --------------------------------------------------
// Format best call time
// --------------------------------------------------

$bestTimeFormatted = $bestTime;


// datetime-local typically arrives as:
// 2026-09-03T14:30

$date = DateTime::createFromFormat(
    'Y-m-d\TH:i',
    $bestTime
);


if ($date !== false) {

    $bestTimeFormatted =
        $date->format('F j, Y \a\t g:i A');
}


// --------------------------------------------------
// Create PHPMailer
// --------------------------------------------------

$mail = new PHPMailer(true);


try {

    // ----------------------------------------------
    // IONOS SMTP
    // ----------------------------------------------

    $mail->isSMTP();

    $mail->Host =
        $config['smtp_host'];

    $mail->SMTPAuth = true;

    $mail->Username =
        $config['smtp_username'];

    $mail->Password =
        $config['smtp_password'];

    $mail->SMTPSecure =
        PHPMailer::ENCRYPTION_SMTPS;

    $mail->Port =
        $config['smtp_port'];

    $mail->CharSet = 'UTF-8';


    // ----------------------------------------------
    // Sender
    // ----------------------------------------------

    $mail->setFrom(
        $config['from_email'],
        $config['from_name']
    );


    // ----------------------------------------------
    // Recipient
    // ----------------------------------------------

    $mail->addAddress(
        $config['recipient_email'],
        $config['recipient_name']
    );


    // ----------------------------------------------
    // Reply directly to form submitter
    // ----------------------------------------------

    $mail->addReplyTo(
        $email,
        $name
    );

    // ----------------------------------------------
    // Attach uploaded files
    // ----------------------------------------------

    foreach ($attachments as $attachment) {
        $mail->addAttachment(
            $attachment['path'],
            $attachment['name']
        );
    }


    // ----------------------------------------------
    // Email subject
    // ----------------------------------------------

    $mail->Subject =
        'New Website Callback Request';


    // ----------------------------------------------
    // Sanitize output for HTML email
    // ----------------------------------------------

    $safeName =
        htmlspecialchars(
            $name,
            ENT_QUOTES,
            'UTF-8'
        );

    $safePhone =
        htmlspecialchars(
            $phone,
            ENT_QUOTES,
            'UTF-8'
        );

    $safeEmail =
        htmlspecialchars(
            $email,
            ENT_QUOTES,
            'UTF-8'
        );

    $safeBestTime =
        htmlspecialchars(
            $bestTimeFormatted,
            ENT_QUOTES,
            'UTF-8'
        );


    // ----------------------------------------------
    // HTML email
    // ----------------------------------------------

    $mail->isHTML(true);


    $mail->Body = "

        <h2>New Website Callback Request - Terra Nova Components</h2>

        <table
            cellpadding='8'
            cellspacing='0'
            border='0'
        >

            <tr>
                <td>
                    <strong>Name:</strong>
                </td>

                <td>
                    {$safeName}
                </td>
            </tr>


            <tr>
                <td>
                    <strong>Contact Number:</strong>
                </td>

                <td>
                    {$safePhone}
                </td>
            </tr>


            <tr>
                <td>
                    <strong>Email:</strong>
                </td>

                <td>
                    {$safeEmail}
                </td>
            </tr>


            <tr>
                <td>
                    <strong>Best Time to Call:</strong>
                </td>

                <td>
                    {$safeBestTime}
                </td>
            </tr>

        </table>

    ";


    // ----------------------------------------------
    // Plain-text fallback
    // ----------------------------------------------

    $mail->AltBody =

        "NEW WEBSITE CALLBACK REQUEST\n\n" .

        "Name: {$name}\n" .

        "Contact Number: {$phone}\n" .

        "Email: {$email}\n" .

        "Best Time to Call: {$bestTimeFormatted}\n";


    // ----------------------------------------------
    // Send email
    // ----------------------------------------------

    $mail->send();


    echo json_encode([
        'success' => true,
        'message' =>
            'Thanks! Your callback request has been received.'
    ]);


} catch (Exception $e) {

    error_log(
        'Terra Nova contact form error: ' .
        $mail->ErrorInfo
    );


    http_response_code(500);


    echo json_encode([
        'success' => false,
        'message' =>
            'We could not send your request. Please try again.'
    ]);

}