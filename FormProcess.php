<?php
// FormProcess.php
$recipient = 'sramir18@student.scf.edu';
$fromNameDefault = 'SamGlobalTech Contact Form';
$logCsv = false; // set true to append submissions to form_submissions.csv

function clean_text($s) {
    return trim(htmlspecialchars(strip_tags((string)$s), ENT_QUOTES, 'UTF-8'));
}

function is_header_injection($str) {
    return preg_match("/[\r\n]/", $str);
}

$allowed_states = [
    'AL','AK','AZ','AR','CA','CO','CT','DE','FL','GA','HI','ID','IL','IN','IA','KS','KY','LA',
    'ME','MD','MA','MI','MN','MS','MO','MT','NE','NV','NH','NJ','NM','NY','NC','ND','OH','OK',
    'OR','PA','RI','SC','SD','TN','TX','UT','VT','VA','WA','WV','WI','WY',''
];

$errors = [];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo "Method not allowed.";
    exit;
}

$title      = clean_text($_POST['title'] ?? '');
$firstName  = clean_text($_POST['first-name'] ?? '');
$lastName   = clean_text($_POST['last-name'] ?? '');
$city       = clean_text($_POST['city'] ?? '');
$state      = strtoupper(clean_text($_POST['state'] ?? ''));
$zip        = clean_text($_POST['zip'] ?? '');
$email      = clean_text($_POST['email'] ?? '');
$gender     = clean_text($_POST['gender'] ?? '');
$comments   = clean_text($_POST['comments'] ?? '');

$edu_raw = $_POST['edu'] ?? [];
if (!is_array($edu_raw)) $edu_raw = [$edu_raw];
$allowed_edu = ['AS','BS'];
$edu = [];
foreach ($edu_raw as $e) {
    $e = clean_text($e);
    if (in_array($e, $allowed_edu, true)) $edu[] = $e;
}
$edu_list = implode(', ', $edu);

if ($firstName === '') $errors[] = 'First name is required.';
if ($lastName === '') $errors[] = 'Last name is required.';
if ($email === '') {
    $errors[] = 'Email is required.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Email is not valid.';
} elseif (is_header_injection($email)) {
    $errors[] = 'Email contains invalid characters.';
}

if ($zip !== '' && !preg_match('/^\d{5}$/', $zip)) {
    $errors[] = 'Zip must be 5 digits.';
}

if ($state !== '' && !in_array($state, $allowed_states, true)) {
    $errors[] = 'State selection is invalid.';
}

if ($gender !== '' && !in_array($gender, ['M','F'], true)) {
    $errors[] = 'Gender selection is invalid.';
}

if (!empty($errors)) {
    http_response_code(422);
    ?>
    <!doctype html>
    <html lang="en">
    <head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Form error</title></head>
    <body>
      <h1>There were problems with your submission</h1>
      <ul>
        <?php foreach ($errors as $err): ?>
          <li><?php echo htmlspecialchars($err, ENT_QUOTES, 'UTF-8'); ?></li>
        <?php endforeach; ?>
      </ul>
      <p><a href="javascript:history.back()">Go back and fix</a></p>
    </body>
    </html>
    <?php
    exit;
}

$subject = "New contact from SamGlobalTech site: " . ($firstName . ' ' . $lastName);
$email_body = "You have a new contact submission:\n\n";
$email_body .= "Name: {$title} {$firstName} {$lastName}\n";
$email_body .= "Email: {$email}\n";
if ($city !== '') $email_body .= "City: {$city}\n";
if ($state !== '') $email_body .= "State: {$state}\n";
if ($zip !== '') $email_body .= "Zip: {$zip}\n";
if ($gender !== '') $email_body .= "Gender: {$gender}\n";
if ($edu_list !== '') $email_body .= "Education: {$edu_list}\n";
if ($comments !== '') $email_body .= "Comments:\n{$comments}\n";

$fromEmail = $email;
if (is_header_injection($fromEmail) || is_header_injection($fromNameDefault)) {
    $fromEmail = 'no-reply@' . ($_SERVER['SERVER_NAME'] ?? 'localhost');
}

$headers = "From: {$fromNameDefault} <{$fromEmail}>\r\n";
$headers .= "Reply-To: {$email}\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

$mail_sent = mail($recipient, $subject, $email_body, $headers);

if ($logCsv) {
    $csvFile = __DIR__ . '/form_submissions.csv';
    $row = [
        date('c'),
        $title,
        $firstName,
        $lastName,
        $email,
        $city,
        $state,
        $zip,
        $gender,
        $edu_list,
        str_replace(["\r\n", "\r", "\n"], [' ', ' ', ' '], $comments)
    ];
    $fp = fopen($csvFile, 'a');
    if ($fp) {
        fputcsv($fp, $row);
        fclose($fp);
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />