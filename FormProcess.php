<?php
// (continuation: after mail() and optional CSV logging)
$redirect = $_POST['redirect'] ?? 'contactsent.html';

if ($mail_sent) {
    // Prefer redirect for browser-based form submissions
    if (!headers_sent()) {
        header('Location: ' . $redirect);
        exit;
    }
    // If headers already sent, fall back to an HTML success message
    $status_message = 'Thank you — your message has been sent.';
} else {
    http_response_code(500);
    $status_message = 'There was a problem sending your message. Please try again later.';
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>SamGlobalTech Contact Status</title>
  <link rel="stylesheet" href="styles.css" />
</head>
<body>
  <main>
    <section>
      <h1><?php echo htmlspecialchars($status_message, ENT_QUOTES, 'UTF-8'); ?></h1>
      <?php if (!$mail_sent): ?>
        <p>Please go back and try again or contact site admin directly.</p>
      <?php else: ?>
        <p>If you are not redirected automatically, <a href="<?php echo htmlspecialchars($redirect, ENT_QUOTES, 'UTF-8'); ?>">click here</a> to continue.</p>
      <?php endif; ?>
    </section>
  </main>
</body>
</html>