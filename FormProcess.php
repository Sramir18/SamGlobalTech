<?php
// formprocess.php
// Simple, secure-ish form processor for the contact form.
// $to set to the email provided by the user.

$to = 'sramir18@student.scf.edu';
$subject = 'SamGlobalTech Contact Form Submission';

// Helper: sanitize a single text input
function clean_text($key) {
    if (!isset($_POST[$key])) return '';
    $s = trim($_POST[$key]);
    $s = strip_tags($s);
    $s = str_replace(["\r","\n"], [' ',' '], $s);
    return $s;
}

// Helper: safe output for HTML display
function h($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

// Collect & sanitize
$title     = clean_text('title');
$firstName = clean_text('FirstName');
$lastName  = clean_text('LastName');
$city      = clean_text('City');
$state     = clean_text('State');
$zip       = clean_text('Zip');
$email     = filter_var(trim($_POST['Email'] ?? ''), FILTER_SANITIZE_EMAIL);
$gender    = isset($_POST['Gender']) ? clean_text('Gender') : '';
$comments  = isset($_POST['Comments']) ? trim($_POST['Comments']) : '';
$comments  = strip_tags($comments);

// Education[] might be an array
$educationArr = [];
if (!empty($_POST['Education']) && is_array($_POST['Education'])) {
    foreach ($_POST['Education'] as $e) {
        $educationArr[] = preg_replace('/[^a-zA-Z0-9\-\_ ]/', '', $e);
    }
}
$education = implode(', ', $educationArr);

// Server-side validation for required fields
$errors = [];
if ($firstName === '') $errors[] = 'First Name is required.';
if ($lastName === '')  $errors[] = 'Last Name is required.';
if ($comments === '')  $errors[] = 'Comments are required.';

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'A valid Email is required.';
}

// Optional: validate zip (5 digits) if provided
if ($zip !== '' && !preg_match('/^\d{5}$/', $zip)) {
    $errors[] = 'Zip code must be 5 digits.';
}

// If validation failed, show errors and stop
if (!empty($errors)) {
    echo '<!doctype html><html><head><meta charset="utf-8"><title>Form Error</title></head><body>';
    echo '<h2>There were problems with your submission</h2><ul>';
    foreach ($errors as $err) {
        echo '<li>' . h($err) . '</li>';
    }
    echo '</ul>';
    echo '<p><a href="javascript:history.back()">Go back and fix the form</a></p>';
    echo '</body></html>';
    exit;
}

// Build email body
$bodyLines = [];
$bodyLines[] = "Contact form submission from SamGlobalTech site";
$bodyLines[] = "Date: " . date('Y-m-d H:i:s');
$bodyLines[] = "";
$bodyLines[] = "Name: {$title} {$firstName} {$lastName}";
$bodyLines[] = "Email: {$email}";
$bodyLines[] = "City: {$city}";
$bodyLines[] = "State: {$state}";
$bodyLines[] = "Zip: {$zip}";
$bodyLines[] = "Gender: {$gender}";
$bodyLines[] = "Education: {$education}";
$bodyLines[] = "";
$bodyLines[] = "Comments:";
$bodyLines[] = $comments;

$body = implode("\n", $bodyLines);

// Headers
$headers = [];
// Use a safe From header; if the user's email is from an external domain some hosts may reject — consider using a site domain address if needed
$headers[] = 'From: ' . $firstName . ' ' . $lastName . " <{$email}>";
$headers[] = 'Reply-To: ' . $email;
$headers[] = 'MIME-Version: 1.0';
$headers[] = 'Content-Type: text/plain; charset=UTF-8';
$headers_str = implode("\r\n", $headers);

// Send email
$mailSent = @mail($to, $subject, $body, $headers_str);

// Show confirmation or fallback
if ($mailSent) {
    echo '<!doctype html><html><head><meta charset="utf-8"><title>Thank You</title>';
    echo '<meta name="viewport" content="width=device-width,initial-scale=1" />';
    echo '</head><body>';
    echo '<h2>Thank you</h2>';
    echo '<p>Your message has been sent. We will get back to you shortly.</p>';
    echo '<h3>Submission Summary</h3><ul>';
    echo '<li><strong>Name:</strong> ' . h($title . ' ' . $firstName . ' ' . $lastName) . '</li>';
    echo '<li><strong>Email:</strong> ' . h($email) . '</li>';
    echo '<li><strong>City:</strong> ' . h($city) . '</li>';
    echo '<li><strong>State:</strong> ' . h($state) . '</li>';
    echo '<li><strong>Zip:</strong> ' . h($zip) . '</li>';
    echo '<li><strong>Gender:</strong> ' . h($gender) . '</li>';
    echo '<li><strong>Education:</strong> ' . h($education) . '</li>';
    echo '<li><strong>Comments:</strong><br>' . nl2br(h($comments)) . '</li>';
    echo '</ul>';
    echo '<p><a href="index.html">Return to site</a></p>';
    echo '</body></html>';
    exit;
} else {
    echo '<!doctype html><html><head><meta charset="utf-8"><title>Send Error</title></head><body>';
    echo '<h2>Unable to send message</h2>';
    echo '<p>There was an error sending your message from the server. Please try again later or contact us directly at: ' . h($to) . '</p>';
    echo '<p><a href="javascript:history.back()">Go back and try again</a></p>';
    echo '</body></html>';
    exit;
}
?>