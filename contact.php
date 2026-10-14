<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');


// --------------------------------------------------
// Load PHPMailer manually
// --------------------------------------------------

require __DIR__ . '/phpmailer/src/Exception.php';
require __DIR__ . '/phpmailer/src/PHPMailer.php';
require __DIR__ . '/phpmailer/src/SMTP.php';


// --------------------------------------------------
// Load configuration
// --------------------------------------------------

// If mail-config.php is ONE DIRECTORY ABOVE the website root:
$config = require dirname(__DIR__) . '/mail-config.php';

// If mail-config.php is in the SAME directory as contact.php,
// use this instead:
// $config = require __DIR__ . '/mail-config.php';


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
// Validate standard fields
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
// Limit field lengths
// --------------------------------------------------

$name = mb_substr($name, 0, 100);
$phone = mb_substr($phone, 0, 50);
$email = mb_substr($email, 0, 150);
$bestTime = mb_substr($bestTime, 0, 50);


// --------------------------------------------------
// Format best call time
// --------------------------------------------------

$bestTimeFormatted = $bestTime;

$date = DateTime::createFromFormat(
    'Y-m-d\TH:i',
    $bestTime
);

if ($date !== false) {

    $bestTimeFormatted =
        $date->format('F j, Y \a\t g:i A');
}


// --------------------------------------------------
// Validate optional drawing attachment
// --------------------------------------------------

$attachment = null;

if (
    isset($_FILES['blueprint']) &&
    $_FILES['blueprint']['error'] !== UPLOAD_ERR_NO_FILE
) {

    $file = $_FILES['blueprint'];


    // ----------------------------------------------
    // Check PHP upload error
    // ----------------------------------------------

    if ($file['error'] !== UPLOAD_ERR_OK) {

        http_response_code(400);

        echo json_encode([
            'success' => false,
            'message' => 'There was a problem uploading your drawing.'
        ]);

        exit;
    }


    // ----------------------------------------------
    // Maximum file size: 10 MB
    // ----------------------------------------------

    $maxFileSize = 10 * 1024 * 1024;

    if ($file['size'] > $maxFileSize) {

        http_response_code(400);

        echo json_encode([
            'success' => false,
            'message' => 'The drawing must be 10 MB or smaller.'
        ]);

        exit;
    }


    // ----------------------------------------------
    // Extension validation
    // ----------------------------------------------

    $originalName = basename($file['name']);

    $extension = strtolower(
        pathinfo(
            $originalName,
            PATHINFO_EXTENSION
        )
    );

    $allowedExtensions = [
        'pdf',
        'jpg',
        'jpeg',
        'png',
        'dwg',
        'dxf'
    ];

    if (!in_array(
        $extension,
        $allowedExtensions,
        true
    )) {

        http_response_code(400);

        echo json_encode([
            'success' => false,
            'message' =>
                'Please upload a PDF, JPG, PNG, DWG or DXF file.'
        ]);

        exit;
    }


    // ----------------------------------------------
    // Detect MIME type
    // ----------------------------------------------

    $finfo = new finfo(FILEINFO_MIME_TYPE);

    $mimeType = $finfo->file(
        $file['tmp_name']
    );


    // ----------------------------------------------
    // Strict MIME checks for common formats
    // ----------------------------------------------

    $strictMimeTypes = [

        'pdf' => [
            'application/pdf'
        ],

        'jpg' => [
            'image/jpeg'
        ],

        'jpeg' => [
            'image/jpeg'
        ],

        'png' => [
            'image/png'
        ]

    ];


    if (isset($strictMimeTypes[$extension])) {

        if (!in_array(
            $mimeType,
            $strictMimeTypes[$extension],
            true
        )) {

            http_response_code(400);

            echo json_encode([
                'success' => false,
                'message' =>
                    'The uploaded file does not match its file type.'
            ]);

            exit;
        }
    }


    // ----------------------------------------------
    // CAD MIME validation
    // ----------------------------------------------

    $cadExtensions = [
        'dwg',
        'dxf'
    ];


    if (in_array(
        $extension,
        $cadExtensions,
        true
    )) {

        $allowedCadMimeTypes = [

            'application/octet-stream',

            'application/acad',
            'application/x-acad',

            'application/autocad',
            'application/x-autocad',

            'image/vnd.dwg',
            'image/x-dwg',

            'application/dwg',
            'application/x-dwg',

            'application/dxf',
            'application/x-dxf',

            'image/vnd.dxf',
            'image/x-dxf',

            'text/plain'
        ];


        if (!in_array(
            $mimeType,
            $allowedCadMimeTypes,
            true
        )) {

            http_response_code(400);

            echo json_encode([
                'success' => false,
                'message' =>
                    'The uploaded CAD file could not be validated.'
            ]);

            exit;
        }
    }


    // ----------------------------------------------
    // Sanitize attachment filename
    // ----------------------------------------------

    $safeFileName = preg_replace(
        '/[^A-Za-z0-9._-]/',
        '_',
        $originalName
    );


    $attachment = [
        'path' => $file['tmp_name'],
        'name' => $safeFileName,
        'mime' => $mimeType
    ];
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
    // Reply-To
    // ----------------------------------------------

    $mail->addReplyTo(
        $email,
        $name
    );


    // ----------------------------------------------
    // Subject
    // ----------------------------------------------

    $mail->Subject =
        'New Callback Request - Terra Nova Components';


    // ----------------------------------------------
    // Sanitize values for HTML email
    // ----------------------------------------------

    $safeName = htmlspecialchars(
        $name,
        ENT_QUOTES,
        'UTF-8'
    );

    $safePhone = htmlspecialchars(
        $phone,
        ENT_QUOTES,
        'UTF-8'
    );

    $safeEmail = htmlspecialchars(
        $email,
        ENT_QUOTES,
        'UTF-8'
    );

    $safeBestTime = htmlspecialchars(
        $bestTimeFormatted,
        ENT_QUOTES,
        'UTF-8'
    );


    // ----------------------------------------------
    // Build attachment display text
    // ----------------------------------------------

    $attachmentHtml = '';

    $attachmentText = 'No drawing attached';

    if ($attachment !== null) {

        $safeAttachmentName = htmlspecialchars(
            $attachment['name'],
            ENT_QUOTES,
            'UTF-8'
        );

        $attachmentHtml = "
            <tr>
                <td>
                    <strong>Drawing:</strong>
                </td>

                <td>
                    {$safeAttachmentName}
                </td>
            </tr>
        ";

        $attachmentText =
            $attachment['name'];
    }


    // ----------------------------------------------
    // HTML email
    // ----------------------------------------------

    $mail->isHTML(true);


    $mail->Body = "

        <h2>New Website Callback Request</h2>

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
                    <a href='tel:{$safePhone}'>
                        {$safePhone}
                    </a>
                </td>
            </tr>

            <tr>
                <td>
                    <strong>Email:</strong>
                </td>

                <td>
                    <a href='mailto:{$safeEmail}'>
                        {$safeEmail}
                    </a>
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

            {$attachmentHtml}

        </table>

        <p style='margin-top:24px; color:#777; font-size:12px;'>
            Submitted from terranovacomponents.ca
        </p>

    ";


    // ----------------------------------------------
    // Plain-text fallback
    // ----------------------------------------------

    $mail->AltBody =

        "NEW WEBSITE CALLBACK REQUEST\n\n" .

        "Name: {$name}\n" .

        "Contact Number: {$phone}\n" .

        "Email: {$email}\n" .

        "Best Time to Call: {$bestTimeFormatted}\n" .

        "Drawing: {$attachmentText}\n";


    // ----------------------------------------------
    // Attach drawing if supplied
    // ----------------------------------------------

    if ($attachment !== null) {

        $mail->addAttachment(
            $attachment['path'],
            $attachment['name']
        );
    }


    // ----------------------------------------------
    // Send
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
