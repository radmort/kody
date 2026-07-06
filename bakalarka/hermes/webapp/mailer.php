<?php
/**
 * Modul: SMTP mailer
 *
 * Odosielanie e-mailov cez SMTP (SSL/STARTTLS, AUTH LOGIN) s voliteľnou
 * PDF prílohou. Podporuje per-user SMTP nastavenia z databázy.
 *
 * Hlavné metódy:
 *   forUserId()               – Mailer s SMTP nastaveniami daného používateľa
 *   sendInvoiceIssuedNotice() – e-mail o novej faktúre
 *   sendPaymentReminder()    – upomienka na nezaplatenie
 *   send()                   – nízkoúrovňové odoslanie s prílohou
 */

class Mailer
{
    private string $host;
    private int    $port;
    private string $user;
    private string $pass;
    private string $fromEmail;
    private string $fromName;
    private bool   $tls;
    /** Názov dodávateľa na konci textu (zhodný s PDF – users.seller_display_name). */
    private string $signOffName;

    /**
     * @param array|null $resolved Ak nie null, použije sa namiesto konštánt z config.php (globálne SMTP).
     */
    public function __construct(?array $resolved = null, string $signOffName = '')
    {
        $this->signOffName = $signOffName !== '' ? $signOffName : APP_NAME;
        if ($resolved !== null) {
            $this->host      = $resolved['host'];
            $this->port      = (int)$resolved['port'];
            $this->user      = $resolved['user'];
            $this->pass      = $resolved['password'];
            $this->fromEmail = $resolved['from_email'];
            $this->fromName  = $resolved['from_name'];
            $this->tls       = $resolved['tls'];
        } else {
            $this->host      = SMTP_HOST;
            $this->port      = SMTP_PORT;
            $this->user      = SMTP_USER;
            $this->pass      = SMTP_PASSWORD;
            $this->fromEmail = SMTP_FROM;
            $this->fromName  = SMTP_FROM_NAME;
            $this->tls       = SMTP_TLS;
        }
    }

    /** SMTP pre daného používateľa: vlastné polia v DB, inak rovnaké ako globálne z prostredia. */
    public static function forUserId(int $userId): self
    {
        $row = DB::one(
            'SELECT smtp_host, smtp_port, smtp_user, smtp_password, smtp_from_email, smtp_from_name, smtp_use_tls,
                    seller_display_name
             FROM users WHERE id = ?',
            [$userId]
        );
        if (!$row) {
            return new self(null, APP_NAME);
        }
        $signOff = trim((string)($row['seller_display_name'] ?? ''));
        if ($signOff === '') {
            $signOff = APP_NAME;
        }
        $resolved = self::resolveUserSmtp($row);
        return $resolved !== null ? new self($resolved, $signOff) : new self(null, $signOff);
    }

    /**
     * @return array<string,mixed>|null null = použiť globálne SMTP
     */
    private static function resolveUserSmtp(?array $row): ?array
    {
        if (!$row) {
            return null;
        }
        $host = trim((string)($row['smtp_host'] ?? ''));
        $user = trim((string)($row['smtp_user'] ?? ''));
        if ($host === '' || $user === '') {
            return null;
        }
        $port = (int)($row['smtp_port'] ?? 0);
        if ($port <= 0) {
            $port = 465;
        }
        $pass = (string)($row['smtp_password'] ?? '');
        if ($pass === '') {
            $pass = SMTP_PASSWORD;
        }
        $fromEmail = trim((string)($row['smtp_from_email'] ?? ''));
        if ($fromEmail === '') {
            $fromEmail = $user !== '' ? $user : SMTP_FROM;
        }
        $fromName = trim((string)($row['smtp_from_name'] ?? ''));
        if ($fromName === '') {
            $fromName = SMTP_FROM_NAME;
        }
        $tls = isset($row['smtp_use_tls']) ? (bool)(int)$row['smtp_use_tls'] : SMTP_TLS;

        return [
            'host'       => $host,
            'port'       => $port,
            'user'       => $user,
            'password'   => $pass,
            'from_email' => $fromEmail,
            'from_name'  => $fromName,
            'tls'        => $tls,
        ];
    }

    /**
     * Prvé odoslanie faktúry zákazníkovi (šablóna, mesačný job, tlačidlo z faktúry).
     */
    public function sendInvoiceIssuedNotice(
        string $toEmail,
        string $toName,
        string $invoiceNumber,
        string $dueDate,
        string $currency,
        int    $totalCents,
        ?string $pdfPath = null,
        ?string $pdfAttachmentFilename = null
    ): bool {
        $subject = "Faktúra {$invoiceNumber}";
        $body    = $this->buildBodyNewInvoice($toName, $invoiceNumber, $dueDate, $currency, $totalCents);
        return $this->send($toEmail, $toName, $subject, $body, $pdfPath, $pdfAttachmentFilename);
    }

    /**
     * Upomienka na nezaplatenie (po splatnosti) – rovnaký zámer ako C++ billing_reminder.
     */
    public function sendPaymentReminder(
        string $toEmail,
        string $toName,
        string $invoiceNumber,
        string $dueDate,
        string $currency,
        int    $totalCents,
        ?string $pdfPath = null,
        ?string $pdfAttachmentFilename = null
    ): bool {
        $subject = "Upomienka platby – faktúra {$invoiceNumber}";
        $body    = $this->buildBodyPaymentReminder($toName, $invoiceNumber, $dueDate, $currency, $totalCents);
        return $this->send($toEmail, $toName, $subject, $body, $pdfPath, $pdfAttachmentFilename);
    }

    /** Low-level send; with optional PDF as multipart/mixed attachment */
    public function send(
        string $toEmail,
        string $toName,
        string $subject,
        string $body,
        ?string $pdfPath = null,
        ?string $pdfAttachmentFilename = null
    ): bool {
        if (empty($this->host) || empty($this->user)) {
            error_log('Mailer: SMTP not configured');
            return false;
        }

        try {
            $socket = $this->connect();
            $this->auth($socket);
            $this->sendMail($socket, $toEmail, $toName, $subject, $body, $pdfPath, $pdfAttachmentFilename);
            $this->cmd($socket, "QUIT");
            fclose($socket);
            return true;
        } catch (Throwable $e) {
            error_log("Mailer error: " . $e->getMessage());
            return false;
        }
    }

    // ═══════════════════════════════════════════════════════════════
    //  SEKCIA: Interné SMTP pomocníky
    // ═══════════════════════════════════════════════════════════════

    private function connect() /* resource */
    {
        $prefix  = $this->tls ? 'ssl://' : '';
        $timeout = 15;
        $errno   = 0;
        $errstr  = '';

        $sock = fsockopen("{$prefix}{$this->host}", $this->port, $errno, $errstr, $timeout);
        if (!$sock) {
            throw new RuntimeException("Connect failed: {$errstr} ({$errno})");
        }
        stream_set_timeout($sock, $timeout);
        $this->expect($sock, '220');

        if (!$this->tls) {
            // STARTTLS
            $this->cmd($sock, "EHLO " . gethostname(), '250');
            $this->cmd($sock, "STARTTLS", '220');
            stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        }

        $this->cmd($sock, "EHLO " . gethostname(), '250');
        return $sock;
    }

    private function auth($sock): void
    {
        $this->cmd($sock, "AUTH LOGIN", '334');
        $this->cmd($sock, base64_encode($this->user), '334');
        $this->cmd($sock, base64_encode($this->pass), '235');
    }

    private function sendMail(
        $sock,
        string $toEmail,
        string $toName,
        string $subject,
        string $body,
        ?string $pdfPath = null,
        ?string $pdfAttachmentFilename = null
    ): void {
        $this->cmd($sock, "MAIL FROM:<{$this->fromEmail}>", '250');
        $this->cmd($sock, "RCPT TO:<{$toEmail}>", '250');
        $this->cmd($sock, "DATA", '354');

        $date    = date('r');
        $subjEnc = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $b64body = chunk_split(base64_encode($body));

        $attachPdf = $pdfPath !== null && $pdfPath !== '' && is_file($pdfPath) && is_readable($pdfPath);
        if ($attachPdf) {
            $pdfBytes = file_get_contents($pdfPath);
            if ($pdfBytes === false) {
                throw new RuntimeException('Cannot read PDF for attachment');
            }
            $safeName = $pdfAttachmentFilename ?? 'faktura.pdf';
            $safeName = preg_replace('/[^-a-zA-Z0-9_.]/', '_', $safeName) ?: 'faktura.pdf';
            $b64pdf   = chunk_split(base64_encode($pdfBytes));
            $boundary = 'hermes_mixed_' . bin2hex(random_bytes(12));

            $msg = "Date: {$date}\r\n"
                . "From: {$this->fromName} <{$this->fromEmail}>\r\n"
                . "To: {$toName} <{$toEmail}>\r\n"
                . "Subject: {$subjEnc}\r\n"
                . "MIME-Version: 1.0\r\n"
                . "Content-Type: multipart/mixed; boundary=\"{$boundary}\"\r\n"
                . "\r\n"
                . "--{$boundary}\r\n"
                . "Content-Type: text/plain; charset=UTF-8\r\n"
                . "Content-Transfer-Encoding: base64\r\n"
                . "\r\n"
                . $b64body
                . "\r\n"
                . "--{$boundary}\r\n"
                . "Content-Type: application/pdf\r\n"
                . "Content-Transfer-Encoding: base64\r\n"
                . "Content-Disposition: attachment; filename=\"{$safeName}\"\r\n"
                . "\r\n"
                . $b64pdf
                . "\r\n"
                . "--{$boundary}--\r\n"
                . ".\r\n";
        } else {
            $msg = "Date: {$date}\r\n"
                . "From: {$this->fromName} <{$this->fromEmail}>\r\n"
                . "To: {$toName} <{$toEmail}>\r\n"
                . "Subject: {$subjEnc}\r\n"
                . "MIME-Version: 1.0\r\n"
                . "Content-Type: text/plain; charset=UTF-8\r\n"
                . "Content-Transfer-Encoding: base64\r\n"
                . "\r\n"
                . $b64body
                . "\r\n.\r\n";
        }

        fwrite($sock, $msg);
        $this->expect($sock, '250');
    }

    private function cmd($sock, string $cmd, string $expected = null): string
    {
        fwrite($sock, $cmd . "\r\n");
        return $this->expect($sock, $expected);
    }

    private function expect($sock, ?string $code): string
    {
        $response = '';
        while ($line = fgets($sock, 512)) {
            $response .= $line;
            // Multi-line: "250-..." continues; "250 " ends
            if (isset($line[3]) && $line[3] === ' ') break;
        }
        if ($code !== null && strncmp($response, $code, strlen($code)) !== 0) {
            throw new RuntimeException("SMTP expected {$code}, got: {$response}");
        }
        return $response;
    }

    private function buildBodyNewInvoice(
        string $name,
        string $num,
        string $due,
        string $currency,
        int    $cents
    ): string {
        $amount = number_format($cents / 100, 2, ',', ' ') . ' ' . $currency;
        return <<<TEXT
        Vážený zákazník {$name},

        Vystavili sme vám nasledujúcu faktúru:

          Číslo faktúry    : {$num}
          Dátum splatnosti : {$due}
          Suma k úhrade    : {$amount}

        Prosíme o úhradu sumy na náš bankový účet.
        V prípade otázok nás kontaktujte.

        S pozdravom,
        {$this->signOffName}
        TEXT;
    }

    private function buildBodyPaymentReminder(
        string $name,
        string $num,
        string $due,
        string $currency,
        int    $cents
    ): string {
        $amount = number_format($cents / 100, 2, ',', ' ') . ' ' . $currency;
        return <<<TEXT
        Vážený zákazník {$name},

        Dovoľujeme si vás upozorniť na nezaplatenie nasledujúcej faktúry:

          Číslo faktúry    : {$num}
          Dátum splatnosti : {$due}
          Suma k úhrade    : {$amount}

        Prosíme o úhradu sumy na náš bankový účet.
        Ak ste platbu už odoslali, prosím, ignorujte túto správu.
        V prípade otázok nás kontaktujte.

        S pozdravom,
        {$this->signOffName}
        TEXT;
    }
}
