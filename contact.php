new message from John Kent  Show  Ignore

Skip to content
Using Gmail with screen readers
1 of 1,460
contact from for tnc
Inbox
Summarise this email

John Kent
Attachments
20:53 (18 minutes ago)
to me

The main changes are:

Add a file input to the form.
Make the form use multipart/form-data.
Validate the upload in contact.php.
Attach the file with PHPMailer.
Keep a conservative upload limit.


IONOS supports fairly large PHP uploads, and its documented defaults/configurable values include upload_max_filesize and post_max_size around 64 MB, but downstream email providers may impose much smaller limits. IONOS itself notes that recipient providers may reject large attachments even when IONOS accepts them. IONOS

For this form, I’d cap uploads at 10 MB. That is much less likely to create delivery problems.


See attachment for code changes


 One attachment
  •  Scanned by Gmail

jack kent
21:00 (12 minutes ago)
index.html:1 Access to fetch at 'file:///D:/ninty/Terra-Nova-Components/contact.php' from origin 'null' has been blocked by CORS policy: Cross origin requests a

John Kent
Attachments
21:11 (1 minute ago)
to me

  Tweak HTML below then replace the contact.php with the attached

<label for="files">
    Upload Drawings:
</label>

<input
    type="file"
    id="files"
    name="files[]"
    accept=".pdf,.jpg,.jpeg,.png,.dwg,.dxf"
    multiple
>
 One attachment
  •  Scanned by Gmail



<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');


// --------------------------------------------------
// Load PHPMailer
// --------------------------------------------------

require __DIR__ . '/phpmailer/src/Exception.php';
require __DIR__ . '/phpmailer/src/PHPMailer.php';
require __DIR__ . '/phpmailer/src/SMTP.php';


// --------------------------------------------------
// Load configuration
// --------------------------------------------------

// If mail-config.php is one directory above this website:
$config = require dirname(__DIR__) . '/mail-config.php';

// If mail-config.php is beside contact.php instead, use:
// $config = require __DIR__ . '/mail-config.php';


// --------------------------------------------------
// Only allow POST
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
// Read form values
// --------------------------------------------------

$companyName = trim($_POST['company-name'] ?? '');
$name = trim($_POST['name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$email = trim($_POST['email'] ?? '');
$bestTime = trim($_POST['best-time'] ?? '');


// --------------------------------------------------
// Validate form fields
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
// Limit text lengths
// --------------------------------------------------

$companyName = mb_substr($companyName, 0, 150);
$name = mb_substr($name, 0, 100);
$phone = mb_substr($phone, 0, 50);
$email = mb_substr($email, 0, 150);
$bestTime = mb_substr($bestTime, 0, 50);


// --------------------------------------------------
// Format callback time
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
// Process drawing attachments
// --------------------------------------------------

$attachments = [];

$maxFiles = 5;

$maxFileSize =
    10 * 1024 * 1024; // 10 MB each

$maxTotalSize =
    20 * 1024 * 1024; // 20 MB total

$totalSize = 0;


$allowedExtensions = [
    'pdf',
    'jpg',
    'jpeg',
    'png',
    'dwg',
    'dxf'
];


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


$allowedCadMimeTypes = [

    'application/octet-stream',

    'application/acad',
    'application/x-acad',

    'application/autocad',
    'application/x-autocad',

    'application/dwg',
    'application/x-dwg',

    'image/vnd.dwg',
    'image/x-dwg',

    'application/dxf',
    'application/x-dxf',

    'image/vnd.dxf',
    'image/x-dxf',

    'text/plain'
];


// --------------------------------------------------
// Check whether files were submitted
// --------------------------------------------------

if (
    isset($_FILES['files']) &&
    isset($_FILES['files']['name']) &&
    is_array($_FILES['files']['name'])
) {

    $uploadedFiles = $_FILES['files'];

    $actualFileCount = 0;

    foreach ($uploadedFiles['error'] as $error) {

        if ($error !== UPLOAD_ERR_NO_FILE) {
            $actualFileCount++;
        }
    }


    // ----------------------------------------------
    // Limit number of files
    // ----------------------------------------------

    if ($actualFileCount > $maxFiles) {

        http_response_code(400);

        echo json_encode([
            'success' => false,
            'message' =>
                'Please upload no more than 5 drawing files.'
        ]);

        exit;
    }


    // ----------------------------------------------
    // Process each file
    // ----------------------------------------------

    foreach (
        $uploadedFiles['name']
        as $index => $originalName
    ) {

        $error =
            $uploadedFiles['error'][$index];


        // No file in this slot
        if ($error === UPLOAD_ERR_NO_FILE) {
            continue;
        }


        // PHP upload error
        if ($error !== UPLOAD_ERR_OK) {

            http_response_code(400);

            echo json_encode([
                'success' => false,
                'message' =>
                    'There was a problem uploading one of your drawings.'
            ]);

            exit;
        }


        $tmpName =
            $uploadedFiles['tmp_name'][$index];

        $fileSize =
            (int) $uploadedFiles['size'][$index];


        // ------------------------------------------
        // Individual size limit
        // ------------------------------------------

        if ($fileSize > $maxFileSize) {

            http_response_code(400);

            echo json_encode([
                'success' => false,
                'message' =>
                    'Each drawing must be 10 MB or smaller.'
            ]);

            exit;
        }


        // ------------------------------------------
        // Total size limit
        // ------------------------------------------

        $totalSize += $fileSize;

        if ($totalSize > $maxTotalSize) {

            http_response_code(400);

            echo json_encode([
                'success' => false,
                'message' =>
                    'The combined size of all drawings must be 20 MB or less.'
            ]);

            exit;
        }


        // ------------------------------------------
        // File extension
        // ------------------------------------------

        $originalName =
            basename($originalName);

        $extension = strtolower(
            pathinfo(
                $originalName,
                PATHINFO_EXTENSION
            )
        );


        if (!in_array(
            $extension,
            $allowedExtensions,
            true
        )) {

            http_response_code(400);

            echo json_encode([
                'success' => false,
                'message' =>
                    'Drawings must be PDF, JPG, PNG, DWG or DXF files.'
            ]);

            exit;
        }


        // ------------------------------------------
        // Detect MIME type
        // ------------------------------------------

        $finfo =
            new finfo(FILEINFO_MIME_TYPE);

        $mimeType =
            $finfo->file($tmpName);


        // ------------------------------------------
        // Strict checks for PDF/images
        // ------------------------------------------

        if (
            isset(
                $strictMimeTypes[$extension]
            )
        ) {

            if (!in_array(
                $mimeType,
                $strictMimeTypes[$extension],
                true
            )) {

                http_response_code(400);

                echo json_encode([
                    'success' => false,
                    'message' =>
                        'One of the uploaded files does not match its file type.'
                ]);

                exit;
            }
        }


        // ------------------------------------------
        // CAD validation
        // ------------------------------------------

        if (
            $extension === 'dwg' ||
            $extension === 'dxf'
        ) {

            if (!in_array(
                $mimeType,
                $allowedCadMimeTypes,
                true
            )) {

                http_response_code(400);

                echo json_encode([
                    'success' => false,
                    'message' =>
                        'One of the uploaded CAD files could not be validated.'
                ]);

                exit;
            }
        }


        // ------------------------------------------
        // Sanitize filename
        // ------------------------------------------

        $safeFileName =
            preg_replace(
                '/[^A-Za-z0-9._-]/',
                '_',
                $originalName
            );


        $attachments[] = [

            'path' => $tmpName,

            'name' => $safeFileName,

            'mime' => $mimeType

        ];
    }
}


// --------------------------------------------------
// Send email
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

    $mail->CharSet =
        'UTF-8';


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
    // Reply to customer
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
    // Sanitize email output
    // ----------------------------------------------

    $safeCompanyName =
        htmlspecialchars(
            $companyName,
            ENT_QUOTES,
            'UTF-8'
        );

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
    // Drawing list
    // ----------------------------------------------

    $drawingHtml =
        '<em>No drawings attached</em>';

    $drawingText =
        'No drawings attached';


    if (count($attachments) > 0) {

        $drawingHtml = '<ul>';

        $drawingNames = [];


        foreach (
            $attachments
            as $attachment
        ) {

            $safeAttachmentName =
                htmlspecialchars(
                    $attachment['name'],
                    ENT_QUOTES,
                    'UTF-8'
                );

            $drawingHtml .=
                "<li>{$safeAttachmentName}</li>";

            $drawingNames[] =
                $attachment['name'];
        }


        $drawingHtml .= '</ul>';

        $drawingText =
            implode(
                "\n",
                $drawingNames
            );
    }


    // ----------------------------------------------
    // HTML email
    // ----------------------------------------------

    $mail->isHTML(true);


    $mail->Body = "

        <h2>
            New Website Callback Request
        </h2>

        <table
            cellpadding='8'
            cellspacing='0'
            border='0'
        >

            <tr>
                <td>
                    <strong>Company:</strong>
                </td>

                <td>
                    {$safeCompanyName}
                </td>
            </tr>

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

            <tr>
                <td valign='top'>
                    <strong>Drawings:</strong>
                </td>

                <td>
                    {$drawingHtml}
                </td>
            </tr>

        </table>

        <p
            style='
                margin-top:24px;
                color:#777;
                font-size:12px;
            '
        >
            Submitted from terranovacomponents.ca
        </p>

    ";


    // ----------------------------------------------
    // Plain-text version
    // ----------------------------------------------

    $mail->AltBody =

        "NEW WEBSITE CALLBACK REQUEST\n\n" .

        "Company: {$companyName}\n" .

        "Name: {$name}\n" .

        "Contact Number: {$phone}\n" .

        "Email: {$email}\n" .

        "Best Time to Call: {$bestTimeFormatted}\n\n" .

        "Drawings:\n{$drawingText}\n";


    // ----------------------------------------------
    // Attach all uploaded drawings
    // ----------------------------------------------

    foreach (
        $attachments
        as $attachment
    ) {

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
