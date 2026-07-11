<?php

/**
 * Name: send_email.php
 * Author: Roger C. Guilherme
 * Created: 2026-07-01
 * Description: This file is part of the ODDS plugin for the ABCD application. It handles sending email notifications based on user actions within the plugin. The script reads SMTP configuration from a secure file, processes incoming requests, and sends emails accordingly. It ensures that only authenticated users can trigger email sending and provides feedback on the success or failure of the operation.
 * 
 * * @package ABCD_Plugins_ODDS
 * @requires PHP 8.1+
 * 
 * changelog:
 * 20260701 rogercgui Initial creation of LanguageManager class.
 * 
 * */


if (!class_exists('PluginBridge')) {
    header("HTTP/1.1 403 Forbidden");
    die("Direct access forbidden.");
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Verifica se é um bibliotecário/admin logado no ABCD
if (!isset($_SESSION['permiso'])) {
    header("HTTP/1.1 403 Forbidden");
    die("<b><font color='red'>Access Denied: Authentication required.</font></b>");
}

require_once __DIR__ . '/OddsMailer.php';

$bridge = PluginBridge::getInstance();
$lang   = $bridge->get('lang', 'en');

// 1. Carregamos o seu arquivo seguro de configurações SMTP
$smtpConfigFile = __DIR__ . '/config/smtp.php';
if (!file_exists($smtpConfigFile)) {
    die("<b><font color='red'>Error: SMTP configuration file not found.</font></b>");
}

$smtpConfig = require $smtpConfigFile;

try {
    // 2. Recebendo os dados da requisição (Vindos do clique do Bibliotecário)
    $status = trim($_REQUEST['status'] ?? '');
    $email  = trim($_REQUEST['email'] ?? '');

    if (empty($email)) {
        throw new Exception("E-mail address missing");
    }

    if ($status === '2') {

        // 3. Montagem correta dos arrays de dados que estavam faltando
        $requestData = [
            'title' => trim(str_replace("/", " ", $_REQUEST['title'] ?? '')),
            'name'  => $_REQUEST['name'] ?? '',
            'date'  => $_REQUEST['fecha'] ?? '',
            'notes' => $_REQUEST['notes'] ?? ''
        ];

        // Formato legado usava pipe '|' para separar arquivos múltiplos
        $rawFiles = $_REQUEST['uploadFiles'] ?? '';
        $uploadedFiles = empty($rawFiles) ? [] : explode('|', $rawFiles);

        // 4. Instanciamos o Mailer com as credenciais do seu smtp.php
        $mailer = new OddsMailer(
            $smtpConfig['host'],
            $smtpConfig['port'],
            $smtpConfig['username'],
            $smtpConfig['password'],
            $smtpConfig['from_email'],
            $smtpConfig['from_name']
        );

        // 5. Dispara a notificação
        $result = $mailer->sendDeliveryNotification($email, $lang, $requestData, $uploadedFiles);

        if ($result) {
            echo "<b><font face='Verdana' size='2' color='#3669c6'>Email successfully sent to: {$email}</font></b>";
        } else {
            echo "<b><font face='Verdana' size='2' color='red'>Failed to send email. Check logs.</font></b>";
        }
    } else {
        echo "<b><font face='Verdana' size='2'>Status not configured for automatic email.</font></b>";
    }
} catch (Exception $e) {
    echo "<b><font face='Verdana' size='2' color='red'>Error: " . htmlspecialchars($e->getMessage()) . "</font></b>";
}
