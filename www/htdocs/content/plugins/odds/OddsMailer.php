<?php

/**
 * Name: OddsMailer.php
 * Author: Roger C. Guilherme
 * Created: 2026-07-01
 * Description: Mailer service for the ODDS plugin in the ABCD application.
 * 
 * * @package ABCD_Plugins_ODDS
 * @requires PHP 8.1+
 * 
 * changelog:
 * 20260701 rogercgui Initial creation of LanguageManager class.
 * 
 * */


// Include PHPMailer manually (since we are embedding it in the plugin without Composer)
require_once __DIR__ . '/libs/PHPMailer/Exception.php';
require_once __DIR__ . '/libs/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/libs/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class OddsMailer
{
    private PHPMailer $mailer;

    public function __construct(
        private string $host,
        private int $port,
        private string $username,
        private string $password,
        private string $fromEmail,
        private string $fromName,
        private string $encryption = ''
    ) {
        $this->mailer = new PHPMailer(true);
        $this->configureMailer();
    }

    /**
     * Configures the library to use PHP's native mail() function.
     * Ignores direct connections via SMTP sockets and delegates to the server's MTA.
     */
    private function configureMailer(): void
    {
        // Instrui o PHPMailer a usar a função mail() do PHP
        $this->mailer->isMail();

        // Define o charset para evitar problemas com acentuação
        $this->mailer->CharSet = 'UTF-8';

        // Configura o cabeçalho 'From:' visível para o destinatário
        $this->mailer->setFrom($this->fromEmail, $this->fromName);

        // CRÍTICO: Define o Envelope Sender (Return-Path).
        // Ao preencher esta propriedade, o PHPMailer injeta automaticamente 
        // o parâmetro "-fadmin@abcdhost.net" na função mail() do PHP.
        $this->mailer->Sender = $this->fromEmail;
    }

    /**
     * Reads the translation .tab file for email templates based on language
     */
    private function readTemplate(string $templateName, string $lang): array
    {
        $templatePath = __DIR__ . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . $lang . DIRECTORY_SEPARATOR . $templateName;

        if (!file_exists($templatePath)) {
            // Fallback to English
            $templatePath = __DIR__ . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . 'en' . DIRECTORY_SEPARATOR . $templateName;
        }

        if (!file_exists($templatePath)) {
            throw new Exception("Email template missing: {$templateName}");
        }

        $content = file_get_contents($templatePath);
        $lines = explode("\n", $content);

        $subject = trim(str_replace('subject =', '', $lines[0]));
        unset($lines[0]);
        $body = trim(implode("\n", $lines));

        return ['subject' => $subject, 'body' => $body];
    }

    /**
     * Prepares and sends the notification to the user
     */
    public function sendDeliveryNotification(
        string $toEmail,
        string $lang,
        array $requestData,
        array $uploadedFiles
    ): bool {
        $templateName = count($uploadedFiles) > 1
            ? 'odds_success_mail_multiple_file.tab'
            : 'odds_success_mail_single_file.tab';

        $template = $this->readTemplate($templateName, $lang);

        $baseUrl = "http://" . $_SERVER['HTTP_HOST'] . "/bases/odds/";

        $fileLinks = "";
        foreach ($uploadedFiles as $file) {
            $fileLinks .= "<a href='{$baseUrl}{$file}'>{$file}</a><br>";
        }

        $replacements = [
            '[title]' => $requestData['title'] ?? '',
            '[name]'  => $requestData['name'] ?? '',
            '[date]'  => $requestData['date'] ?? date('d/m/Y'),
            '[notes]' => $requestData['notes'] ?? '',
            '[url]'   => $baseUrl,
            '[link]'  => $fileLinks
        ];

        $htmlBody = str_replace(
            array_keys($replacements),
            array_values($replacements),
            $template['body']
        );

        $htmlBody = nl2br($htmlBody);

        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($toEmail);
            $this->mailer->isHTML(true);
            $this->mailer->Subject = $template['subject'];
            $this->mailer->Body    = $htmlBody;

            return $this->mailer->send();
        } catch (Exception $e) {
            error_log("ODDS Email Error: " . $this->mailer->ErrorInfo);
            return false;
        }
    }

    /**
     * Sends a custom HTML email without relying on legacy .tab templates.
     * Perfect for auto-responders and administrative notifications.
     */
    public function sendCustomEmail(string $toEmail, string $subject, string $htmlBody): bool
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($toEmail);
            $this->mailer->isHTML(true);
            $this->mailer->Subject = $subject;
            $this->mailer->Body    = $htmlBody;

            return $this->mailer->send();
        } catch (Exception $e) {
            error_log("ODDS Custom Email Error: " . $this->mailer->ErrorInfo);
            return false;
        }
    }
    /**
     * Set up the SMTP server configuration
     */
    private function configureSmtp(): void
    {
        $this->mailer->isSMTP();
        $this->mailer->Host       = $this->host;

        // Dynamic Authentication
        if (!empty($this->password)) {
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = $this->username;
            $this->mailer->Password = $this->password;
        } else {
            $this->mailer->SMTPAuth = false;
        }

        $this->mailer->SMTPSecure = $this->encryption;
        $this->mailer->Port       = $this->port;
        $this->mailer->CharSet    = 'UTF-8';
        $this->mailer->setFrom($this->fromEmail, $this->fromName);

        // --- Bypass SSL certificate verification for localhost/internal servers ---
        $this->mailer->SMTPOptions = [
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true
            ]
        ];
    }

}
