<?php
/**
 * smtp_mail.php
 * Drop-in replacement for PHP's mail() on localhost/Windows.
 * Uses fsockopen + STARTTLS — no PHPMailer, no composer, no extensions.
 *
 * Usage:
 *   require_once 'smtp_mail.php';
 *   smtp_mail('to@example.com', 'Subject', 'Body text');
 */

// ─── ✏️  Edit these four lines only ──────────────────────────────────────────
define('SMTP_HOST', 'smtp.gmail.com');   // or smtp.outlook.com, etc.
define('SMTP_PORT', 587);               
define('SMTP_USER', 'your Gmail address');   // your Gmail address
define('SMTP_PASS', 'Gmail App Password');       // Gmail App Password (not your login password)
define('SMTP_FROM', 'your-address@gmail.com');   // shown as sender
define('SMTP_NAME', 'your sender name');             // shown as sender name
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Send an email via SMTP with STARTTLS.
 *
 * @param  string $to      Recipient email
 * @param  string $subject Email subject
 * @param  string $body    Plain-text body
 * @return bool            true on success, false on failure
 */
function smtp_mail(string $to, string $subject, string $body): bool {
    $host = SMTP_HOST;
    $port = SMTP_PORT;

    // Open TCP connection
    $errno = 0; $errstr = '';
    $sock = @fsockopen($host, $port, $errno, $errstr, 10);
    if (!$sock) {
        error_log("smtp_mail: Could not connect to {$host}:{$port} — {$errstr}");
        return false;
    }

    // Helper: send a command and read response
    $cmd = function(string $command = '') use ($sock): string {
        if ($command !== '') {
            fwrite($sock, $command . "\r\n");
        }
        $response = '';
        while (!feof($sock)) {
            $line = fgets($sock, 515);
            $response .= $line;
            // Last line of a multi-line response has a space after the code
            if (isset($line[3]) && $line[3] === ' ') break;
        }
        return $response;
    };

    // SMTP handshake
    $cmd();                                        // read server greeting
    $cmd('EHLO ' . gethostname());                 // EHLO

    // Upgrade to TLS
    $cmd('STARTTLS');
    stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
    $cmd('EHLO ' . gethostname());                 // re-EHLO after TLS

    // Authenticate
    $cmd('AUTH LOGIN');
    $cmd(base64_encode(SMTP_USER));
    $r = $cmd(base64_encode(SMTP_PASS));
    if (strpos($r, '235') === false) {
        error_log("smtp_mail: Authentication failed — " . trim($r));
        fwrite($sock, "QUIT\r\n");
        fclose($sock);
        return false;
    } 

    // Envelope
    $cmd('MAIL FROM:<' . SMTP_FROM . '>');
    $cmd('RCPT TO:<' . $to . '>');
    $cmd('DATA');

    // Build message headers + body
    $message =
        "From: " . SMTP_NAME . " <" . SMTP_FROM . ">\r\n"
      . "To: <{$to}>\r\n"
      . "Subject: " . mb_encode_mimeheader($subject, 'UTF-8') . "\r\n"
      . "MIME-Version: 1.0\r\n"
      . "Content-Type: text/plain; charset=UTF-8\r\n"
      . "Content-Transfer-Encoding: base64\r\n"
      . "\r\n"
      . chunk_split(base64_encode($body));

    // Escape leading dots (SMTP transparency)
    $message = preg_replace('/^\.$/m', '..', $message);
    $r = $cmd($message . "\r\n.");

    $cmd('QUIT');
    fclose($sock);

    if (strpos($r, '250') === false) {
        error_log("smtp_mail: Message rejected — " . trim($r));
        return false;
    }
    return true;
}
