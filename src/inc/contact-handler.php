<?php
/**
 * Contact form and contact inbox handler.
 *
 * Public submissions are inserted into the SQLite contact_messages table.
 * Admin actions such as archive, delete, and IP blacklist require the contact
 * management permission before any stored message is modified.
 */

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

$action = pmString($_POST['action'] ?? 'send');
$publicContactPage = 'contactme';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !pmValidateCsrfToken($_POST['csrf_token'] ?? null)) {
    $invalidTarget = $action === 'send' ? $publicContactPage : 'manage-contact';
    pmRedirect('../../index.php?page=' . $invalidTarget . '&status=invalid');
}

if ($action === 'send') {
    pmStartSession();

    // Honeypot field: real visitors never see this input, so filled values are likely bot submissions.
    if (pmString($_POST['website'] ?? '') !== '') {
        pmRedirect('../../index.php?page=' . $publicContactPage . '&status=sent');
    }

    // Human-check validation prevents basic automated spam without needing external CAPTCHA services.
    $expectedAnswer = (string) ($_SESSION['contact_human_answer'] ?? '');
    $submittedAnswer = pmString($_POST['human_answer'] ?? '');
    unset($_SESSION['contact_human_answer']);

    if ($expectedAnswer !== '' && !hash_equals($expectedAnswer, $submittedAnswer)) {
        pmRedirect('../../index.php?page=' . $publicContactPage . '&status=human');
    }

    $name = pmString($_POST['name'] ?? $_POST['sender_name'] ?? '');
    $email = pmString($_POST['email'] ?? $_POST['sender_email'] ?? '');
    $subject = pmString($_POST['subject'] ?? '');
    $message = pmString($_POST['message'] ?? '');

    if ($name === '' || $email === '' || $message === '') {
        pmRedirect('../../index.php?page=' . $publicContactPage . '&status=missing');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        pmRedirect('../../index.php?page=' . $publicContactPage . '&status=email');
    }

    $ip = substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 64);
    $blacklisted = false;

    if ($ip !== '') {
        $stmt = pmDb()->prepare('SELECT COUNT(*) FROM contact_ip_blacklist WHERE ip_address = :ip');
        $stmt->execute([':ip' => $ip]);
        $blacklisted = (int) $stmt->fetchColumn() > 0;
    }

    if ($blacklisted) {
        pmRedirect('../../index.php?page=' . $publicContactPage . '&status=blocked');
    }

    $stmt = pmDb()->prepare(<<<'SQL'
        INSERT INTO contact_messages (sender_name, sender_email, subject, message, ip_address, user_agent)
        VALUES (:sender_name, :sender_email, :subject, :message, :ip_address, :user_agent)
    SQL);
    $stmt->execute([
        ':sender_name' => $name,
        ':sender_email' => $email,
        ':subject' => $subject,
        ':message' => $message,
        ':ip_address' => $ip,
        ':user_agent' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
    ]);

    pmRedirect('../../index.php?page=' . $publicContactPage . '&status=sent');
}

pmRequirePermission('can_manage_contact');
$messageId = (int) ($_POST['message_id'] ?? 0);

if ($action === 'read') {
    pmDb()->prepare('UPDATE contact_messages SET is_read = 1 WHERE id = :id')->execute([':id' => $messageId]);
} elseif ($action === 'archive') {
    pmDb()->prepare('UPDATE contact_messages SET is_archived = 1 WHERE id = :id')->execute([':id' => $messageId]);
} elseif ($action === 'delete') {
    pmDb()->prepare('DELETE FROM contact_messages WHERE id = :id')->execute([':id' => $messageId]);
    pmRedirect('../../index.php?page=manage-contact&status=deleted');
} elseif ($action === 'blacklist_ip') {
    $ipAddress = pmString($_POST['ip_address'] ?? '');

    if ($ipAddress !== '') {
        $stmt = pmDb()->prepare('INSERT OR IGNORE INTO contact_ip_blacklist (ip_address, reason) VALUES (:ip_address, :reason)');
        $stmt->execute([
            ':ip_address' => $ipAddress,
            ':reason' => 'Blacklisted from contact inbox message ID ' . $messageId,
        ]);
    }

    pmRedirect('../../index.php?page=manage-contact&id=' . $messageId . '&status=blacklisted');
}

if ($action === 'archive') {
    pmRedirect('../../index.php?page=manage-contact&status=archived');
}

pmRedirect('../../index.php?page=manage-contact&id=' . $messageId . '&status=updated');
