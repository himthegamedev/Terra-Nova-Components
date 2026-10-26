


<?php



use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');


// ==================================================
// PHPMAILER
// ==================================================

// IMPORTANT:
// Use the PHPMailer paths that already work on IONOS.

require __DIR__ . '/phpmailer/src/Exception.php';
require __DIR__ . '/phpmailer/src/PHPMailer.php';
require __DIR__ . '/phpmailer/src/SMTP.php';

//require __DIR__ . '/phpmailer/PHPMailer-7.1.1/src/Exception.php';
//require __DIR__ . '/phpmailer/PHPMailer-7.1.1/src/PHPMailer.php';
//require __DIR__ . '/phpmailer/PHPMailer-7.1.1/src/SMTP.php';


// ==================================================
// MAIL CONFIG
// ==================================================

// Leave your existing production mail-config.php unchanged.
//
// If mail-config.php is beside contact.php:
$config = require __DIR__ . '/mail-config.php';

// If production has mail-config.php one level ABOVE the web root,
// use this instead:
//$config = require dirname(__DIR__) . '/mail-config.php';


// ==================================================
// HELPERS
// ==================================================

function jsonError($message, $status = 400)
{
    http_response_code($status);

    echo json_encode([
        'success' => false,
        'message' => $message
    ]);

    exit;
}


function cleanText($value, $maxLength = 5000)
{
    if (is_array($value)) {
        return $value;
    }

    $value = trim((string) $value);

    return mb_substr(
        $value,
        0,
        $maxLength
    );
}


function humanizeFieldName($key)
{
    $key = preg_replace(
        '/[\[\]_-]+/',
        ' ',
        $key
    );

    $key = preg_replace(
        '/(?<!^)([A-Z])/',
        ' $1',
        $key
    );

    return ucwords(
        trim($key)
    );
}


function formatFieldValue($value)
{
    if (is_array($value)) {

        $cleanValues = [];

        foreach ($value as $item) {

            if (is_array($item)) {
                continue;
            }

            $item = trim((string) $item);

            if ($item !== '') {
                $cleanValues[] = $item;
            }
        }

        return implode(', ', $cleanValues);
    }

    return trim((string) $value);
}


// ==================================================
// REQUEST METHOD
// ==================================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    jsonError(
        'Method not allowed.',
        405
    );
}


// ==================================================
// REQUIRED CONTACT INFORMATION
// ==================================================

// These keys should match the name="" attributes
// on your quote form.
// PHP converts spaces in submitted field names to underscores,
// so "Full name" and "Call time" arrive as Full_name and Call_time.
//
// The fallbacks make this tolerant of some naming differences.

$name =
    cleanText(
        $_POST['Full_name']
        ?? $_POST['name']
        ?? $_POST['contact-name']
        ?? ''
    );

$email =
    cleanText(
        $_POST['Email']
        ?? $_POST['email']
        ?? $_POST['contact-email']
        ?? ''
    );

$phone =
    cleanText(
        $_POST['Phone']
        ?? $_POST['phone']
        ?? $_POST['contact-number']
        ?? $_POST['contact-phone']
        ?? ''
    );

$company =
    cleanText(
        $_POST['Company']
        ?? $_POST['company-name']
        ?? $_POST['company']
        ?? ''
    );

$bestTime =
    cleanText(
        $_POST['Call_time']
        ?? $_POST['best-time']
        ?? $_POST['best-time-to-call']
        ?? ''
    );


// ==================================================
// VALIDATION
// ==================================================

$errors = [];

if ($name === '') {
    $errors[] =
        'Please enter your name.';
}

if ($phone === '') {
    $errors[] =
        'Please enter your contact number.';
}

if (
    $email === '' ||
    !filter_var(
        $email,
        FILTER_VALIDATE_EMAIL
    )
) {

    $errors[] =
        'Please enter a valid email address.';
}


if (!empty($errors)) {

    jsonError(
        implode(' ', $errors)
    );
}


// ==================================================
// FORMAT BEST TIME TO CALL
// ==================================================

$bestTimeFormatted =
    $bestTime;

if ($bestTime !== '') {

    $date =
        DateTime::createFromFormat(
            'Y-m-d\TH:i',
            $bestTime
        );

    if ($date !== false) {

        $bestTimeFormatted =
            $date->format(
                'F j, Y \a\t g:i A'
            );
    }
}


// ==================================================
// PROCESS DRAWING ATTACHMENTS
// ==================================================

$attachments = [];

$maxFiles = 5;

$maxFileSize =
    10 * 1024 * 1024;

$maxTotalSize =
    20 * 1024 * 1024;

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


// Support either files[] or drawings[]
$fileField = null;

if (isset($_FILES['files'])) {
    $fileField = $_FILES['files'];
}
elseif (isset($_FILES['drawings'])) {
    $fileField = $_FILES['drawings'];
}


if (
    $fileField &&
    isset($fileField['name'])
) {

    // Normalize a single upload into arrays.
    if (!is_array($fileField['name'])) {

        foreach (
            [
                'name',
                'type',
                'tmp_name',
                'error',
                'size'
            ]
            as $key
        ) {

            $fileField[$key] = [
                $fileField[$key]
            ];
        }
    }


    $actualFileCount = 0;

    foreach (
        $fileField['error']
        as $uploadError
    ) {

        if (
            $uploadError !==
            UPLOAD_ERR_NO_FILE
        ) {

            $actualFileCount++;
        }
    }


    if (
        $actualFileCount >
        $maxFiles
    ) {

        jsonError(
            'Please upload no more than 5 drawing files.'
        );
    }


    foreach (
        $fileField['name']
        as $index => $originalName
    ) {

        $error =
            $fileField['error'][$index];


        // Optional attachments.
        if (
            $error ===
            UPLOAD_ERR_NO_FILE
        ) {

            continue;
        }


        if (
            $error !==
            UPLOAD_ERR_OK
        ) {

            $uploadErrors = [

                UPLOAD_ERR_INI_SIZE =>
                    'The file exceeds the server upload limit.',

                UPLOAD_ERR_FORM_SIZE =>
                    'The file exceeds the form upload limit.',

                UPLOAD_ERR_PARTIAL =>
                    'The file was only partially uploaded.',

                UPLOAD_ERR_NO_TMP_DIR =>
                    'The server upload folder is unavailable.',

                UPLOAD_ERR_CANT_WRITE =>
                    'The server could not save the uploaded file.',

                UPLOAD_ERR_EXTENSION =>
                    'The server stopped the file upload.'
            ];


            $uploadMessage =
                $uploadErrors[$error]
                ?? 'Unknown upload error.';


            jsonError(
                'Upload failed: ' .
                $uploadMessage
            );
        }


        $tmpName =
            $fileField['tmp_name'][$index];

        $fileSize =
            (int)
            $fileField['size'][$index];


        if (
            !$tmpName ||
            !is_uploaded_file($tmpName)
        ) {

            jsonError(
                'An uploaded drawing could not be verified.'
            );
        }


        if (
            $fileSize >
            $maxFileSize
        ) {

            jsonError(
                'Each drawing must be 10 MB or smaller.'
            );
        }


        $totalSize +=
            $fileSize;


        if (
            $totalSize >
            $maxTotalSize
        ) {

            jsonError(
                'The combined size of all drawings must be 20 MB or less.'
            );
        }


        $originalName =
            basename(
                $originalName
            );


        $extension =
            strtolower(
                pathinfo(
                    $originalName,
                    PATHINFO_EXTENSION
                )
            );


        if (
            !in_array(
                $extension,
                $allowedExtensions,
                true
            )
        ) {

            jsonError(
                'Drawings must be PDF, JPG, PNG, DWG or DXF files.'
            );
        }


        $finfo =
            new finfo(
                FILEINFO_MIME_TYPE
            );


        $mimeType =
            $finfo->file(
                $tmpName
            );


        // Strict checking for PDFs/images.
        if (
            isset(
                $strictMimeTypes[
                    $extension
                ]
            )
        ) {

            if (
                !in_array(
                    $mimeType,
                    $strictMimeTypes[
                        $extension
                    ],
                    true
                )
            ) {

                jsonError(
                    "The uploaded file '{$originalName}' does not match its file type."
                );
            }
        }


        // Broader validation for CAD files.
        if (
            $extension === 'dwg' ||
            $extension === 'dxf'
        ) {

            if (
                !in_array(
                    $mimeType,
                    $allowedCadMimeTypes,
                    true
                )
            ) {

                jsonError(
                    "The CAD file '{$originalName}' could not be validated."
                );
            }
        }


        $safeFileName =
            preg_replace(
                '/[^A-Za-z0-9._-]/',
                '_',
                $originalName
            );


        if (!$safeFileName) {

            $safeFileName =
                'drawing.' .
                $extension;
        }


        $attachments[] = [

            'path' =>
                $tmpName,

            'name' =>
                $safeFileName
        ];
    }
}


// ==================================================
// BUILD PROJECT DETAIL COLLECTION
// ==================================================

// These contact fields will be displayed separately,
// so don't repeat them in the project-detail section.

$excludedFields = [

    'Full_name',
    'Email',
    'Phone',
    'Company',
    'Call_time',

    'name',
    'contact-name',

    'email',
    'contact-email',

    'phone',
    'contact-phone',
    'contact-number',

    'company',
    'company-name',

    'best-time',
    'best-time-to-call'
];


$projectFields = [];


foreach (
    $_POST
    as $key => $value
) {

    if (
        in_array(
            $key,
            $excludedFields,
            true
        )
    ) {

        continue;
    }


    $formattedValue =
        formatFieldValue(
            $value
        );


    // Don't clutter the email
    // with blank optional fields.
    if (
        $formattedValue === ''
    ) {

        continue;
    }


    $projectFields[] = [

        'label' =>
            humanizeFieldName(
                $key
            ),

        'value' =>
            $formattedValue
    ];
}


// ==================================================
// DRAWING LIST
// ==================================================

$drawingHtml =
    '<em>No drawings attached</em>';

$drawingText =
    'No drawings attached';


if (
    count($attachments) > 0
) {

    $drawingHtml =
        '<ul style="margin:0;padding-left:18px;">';

    $drawingNames =
        [];


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


    $drawingHtml .=
        '</ul>';


    $drawingText =
        implode(
            "\n",
            $drawingNames
        );
}


// ==================================================
// BUILD PROJECT DETAIL HTML
// ==================================================

$projectHtml = '';

$projectText = '';


foreach (
    $projectFields
    as $field
) {

    $safeLabel =
        htmlspecialchars(
            $field['label'],
            ENT_QUOTES,
            'UTF-8'
        );


    $safeValue =
        htmlspecialchars(
            $field['value'],
            ENT_QUOTES,
            'UTF-8'
        );


    $safeValue =
        nl2br(
            $safeValue
        );


    $projectHtml .= "

        <tr>

            <td
                style='
                    padding:7px 12px;
                    vertical-align:top;
                    font-weight:bold;
                '
            >
                {$safeLabel}
            </td>

            <td
                style='
                    padding:7px 12px;
                '
            >
                {$safeValue}
            </td>

        </tr>
    ";


    $projectText .=
        $field['label'] .
        ': ' .
        $field['value'] .
        "\n";
}


// ==================================================
// CREATE EMAIL
// ==================================================

$mail =
    new PHPMailer(true);


try {

    // ----------------------------------------------
    // IONOS SMTP
    // ----------------------------------------------

    $mail->isSMTP();

    $mail->Host =
        $config['smtp_host'];

    $mail->SMTPAuth =
        true;

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
    // Sender / recipient
    // ----------------------------------------------

    $mail->setFrom(
        $config['from_email'],
        $config['from_name']
    );


    $mail->addAddress(
        $config['recipient_email'],
        $config['recipient_name']
    );


    $mail->addReplyTo(
        $email,
        $name
    );


    // ----------------------------------------------
    // Subject
    // ----------------------------------------------

    $subjectName =
        $company !== ''
        ? $company
        : $name;


    $mail->Subject =
        'New Quote Request - ' .
        $subjectName;


    // ==================================================
    // SAFE CONTACT VALUES
    // ==================================================

    $safeName =
        htmlspecialchars(
            $name,
            ENT_QUOTES,
            'UTF-8'
        );

    $safeCompany =
        htmlspecialchars(
            $company,
            ENT_QUOTES,
            'UTF-8'
        );

    $safeEmail =
        htmlspecialchars(
            $email,
            ENT_QUOTES,
            'UTF-8'
        );

    $safePhone =
        htmlspecialchars(
            $phone,
            ENT_QUOTES,
            'UTF-8'
        );

    $safeBestTime =
        htmlspecialchars(
            $bestTimeFormatted,
            ENT_QUOTES,
            'UTF-8'
        );


    // ==================================================
    // EMAIL BODY
    // ==================================================

    $mail->isHTML(true);


    $mail->Body = "

        <div
            style='
                font-family:
                    Arial,
                    Helvetica,
                    sans-serif;
                color:#222;
                max-width:760px;
            '
        >

            <h2
                style='
                    margin-bottom:6px;
                '
            >
                New Website Quote Request
            </h2>


            <p
                style='
                    color:#666;
                    margin-top:0;
                '
            >
                Submitted from
                terranovacomponents.ca
            </p>


            <h3>
                Contact Details
            </h3>


            <table
                cellpadding='0'
                cellspacing='0'
                border='0'
                style='
                    width:100%;
                    border-collapse:collapse;
                '
            >

                <tr>
                    <td style='padding:7px 12px;font-weight:bold;'>
                        Name
                    </td>

                    <td style='padding:7px 12px;'>
                        {$safeName}
                    </td>
                </tr>


                <tr>
                    <td style='padding:7px 12px;font-weight:bold;'>
                        Company
                    </td>

                    <td style='padding:7px 12px;'>
                        {$safeCompany}
                    </td>
                </tr>


                <tr>
                    <td style='padding:7px 12px;font-weight:bold;'>
                        Email
                    </td>

                    <td style='padding:7px 12px;'>
                        <a href='mailto:{$safeEmail}'>
                            {$safeEmail}
                        </a>
                    </td>
                </tr>


                <tr>
                    <td style='padding:7px 12px;font-weight:bold;'>
                        Contact Number
                    </td>

                    <td style='padding:7px 12px;'>
                        {$safePhone}
                    </td>
                </tr>


                <tr>
                    <td style='padding:7px 12px;font-weight:bold;'>
                        Best Time to Call
                    </td>

                    <td style='padding:7px 12px;'>
                        {$safeBestTime}
                    </td>
                </tr>

            </table>


            <h3
                style='
                    margin-top:28px;
                '
            >
                Project & Quote Details
            </h3>


            <table
                cellpadding='0'
                cellspacing='0'
                border='0'
                style='
                    width:100%;
                    border-collapse:collapse;
                '
            >

                {$projectHtml}

            </table>


            <h3
                style='
                    margin-top:28px;
                '
            >
                Drawings
            </h3>


            {$drawingHtml}

        </div>
    ";


    // ==================================================
    // PLAIN-TEXT FALLBACK
    // ==================================================

    $mail->AltBody =

        "NEW WEBSITE QUOTE REQUEST\n\n" .

        "CONTACT DETAILS\n" .
        "------------------------------\n" .

        "Name: {$name}\n" .

        "Company: {$company}\n" .

        "Email: {$email}\n" .

        "Contact Number: {$phone}\n" .

        "Best Time to Call: {$bestTimeFormatted}\n\n" .


        "PROJECT & QUOTE DETAILS\n" .
        "------------------------------\n" .

        $projectText .

        "\nDRAWINGS\n" .
        "------------------------------\n" .

        $drawingText;


// ==================================================
// ATTACH DRAWINGS
// ==================================================

    foreach (
        $attachments
        as $attachment
    ) {

        $mail->addAttachment(
            $attachment['path'],
            $attachment['name']
        );
    }


// ==================================================
// SEND
// ==================================================

    $mail->send();


    echo json_encode([
        'success' => true,
        'message' =>
            'Your quote request has been submitted successfully.'
    ]);


} catch (Exception $e) {

    error_log(
        'Terra Nova quote form error: ' .
        $mail->ErrorInfo
    );


    jsonError(
        'We could not send your quote request. Please try again.',
        500
    );
}
