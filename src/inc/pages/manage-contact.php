<?php
/**
 * Contact inbox page.
 *
 * Uses the working two-column admin inbox layout and maps it onto Portfolio
 * Manager's generic contact_messages schema.
 */

declare(strict_types=1);

pmRequirePermission('can_manage_contact');

$selectedId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$messages = pmDb()->query('SELECT * FROM contact_messages WHERE is_archived = 0 ORDER BY created_at DESC')->fetchAll();
$selectedMessage = null;

if ($selectedId > 0) {
    $stmt = pmDb()->prepare('SELECT * FROM contact_messages WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $selectedId]);
    $selectedMessage = $stmt->fetch() ?: null;

    if (is_array($selectedMessage)) {
        pmDb()->prepare('UPDATE contact_messages SET is_read = 1 WHERE id = :id')->execute([':id' => $selectedId]);
        $selectedMessage['is_read'] = 1;
    }
}

$statusMessages = [
    'updated' => ['success', 'Contact message updated.'],
    'deleted' => ['success', 'Contact message deleted.'],
    'archived' => ['success', 'Contact message archived.'],
    'blacklisted' => ['success', 'IP address blacklisted.'],
    'invalid' => ['error', 'Your session expired. Please try again.'],
];
$status = pmString($_GET['status'] ?? '');
$flash = $statusMessages[$status] ?? null;

/**
 * Formats a contact-message date for the admin inbox.
 *
 * @param string $date Raw database date.
 * @return string Display date.
 */
function pmContactFormatDate(string $date): string
{
    $timestamp = strtotime($date);

    return $timestamp === false ? $date : date('M j, Y g:ia', $timestamp);
}

/**
 * Creates a short preview of a contact message body.
 *
 * @param string $message Full message body.
 * @return string Short excerpt.
 */
function pmContactExcerpt(string $message): string
{
    $clean = trim(preg_replace('/\s+/', ' ', $message) ?? $message);

    return strlen($clean) > 120 ? substr($clean, 0, 117) . '...' : $clean;
}

/**
 * Checks whether an IP address has already been blacklisted.
 *
 * @param string $ipAddress IP address to check.
 * @return bool True when the IP is already blacklisted.
 */
function pmContactIsIpBlacklisted(string $ipAddress): bool
{
    if ($ipAddress === '') {
        return false;
    }

    $stmt = pmDb()->prepare('SELECT COUNT(*) FROM contact_ip_blacklist WHERE ip_address = :ip');
    $stmt->execute([':ip' => $ipAddress]);

    return (int) $stmt->fetchColumn() > 0;
}
?>

<section class="manage-contact-page">
    <div class="manage-projects-header">
        <div>
            <p class="admin-kicker">Admin Portal</p>
            <h1 class="admin-page-title">Contact Inbox</h1>
            <p class="admin-page-subtitle">
                Review messages submitted through the Portfolio Manager contact form.
            </p>
        </div>
    </div>

    <?php if (is_array($flash)): ?>
        <p class="contact-flash contact-flash-<?= pmEscape($flash[0]) ?>" role="alert"><?= pmEscape($flash[1]) ?></p>
    <?php endif; ?>

    <div class="manage-contact-layout">
        <aside class="project-admin-sidebar contact-admin-sidebar">
            <div class="admin-panel-heading">
                <h2>Messages</h2>
                <p>Select a message to read it.</p>
            </div>

            <div class="project-admin-list">
                <?php if ($messages === []): ?>
                    <p class="blog-admin-empty">No contact messages yet.</p>
                <?php else: ?>
                    <ul>
                        <?php foreach ($messages as $messageItem): ?>
                            <?php
                            $isActive = ((int) $messageItem['id'] === $selectedId) ? 'active' : '';
                            $isUnread = ((int) $messageItem['is_read'] === 0) ? 'unread' : '';
                            $subject = (string) ($messageItem['subject'] ?: 'No subject');
                            ?>

                            <li>
                                <a class="contact-admin-message-link <?= $isActive ?> <?= $isUnread ?>" href="index.php?page=manage-contact&id=<?= (int) $messageItem['id'] ?>">
                                    <strong><?= pmEscape($subject) ?></strong>
                                    <span><?= pmEscape((string) $messageItem['sender_name']) ?></span>
                                    <small><?= pmEscape(pmContactFormatDate((string) $messageItem['created_at'])) ?></small>
                                    <p><?= pmEscape(pmContactExcerpt((string) $messageItem['message'])) ?></p>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </aside>

        <section class="project-admin-workspace">
            <?php if ($selectedMessage === null): ?>
                <article class="admin-card contact-message-card">
                    <div class="admin-card-header">
                        <div>
                            <p class="admin-kicker">Inbox</p>
                            <h2>No message selected</h2>
                        </div>
                    </div>

                    <p class="contact-message-body">Select a contact message from the inbox list.</p>
                </article>
            <?php else: ?>
                <?php
                $selectedSubject = (string) ($selectedMessage['subject'] ?: 'No subject');
                $selectedIpAddress = trim((string) $selectedMessage['ip_address']);
                $ipIsBlacklisted = pmContactIsIpBlacklisted($selectedIpAddress);
                ?>

                <article class="admin-card contact-message-card">
                    <div class="admin-card-header">
                        <div>
                            <p class="admin-kicker">Contact Message</p>
                            <h2><?= pmEscape($selectedSubject) ?></h2>
                        </div>
                    </div>

                    <div class="contact-message-meta">
                        <div>
                            <span>Name</span>
                            <strong><?= pmEscape((string) $selectedMessage['sender_name']) ?></strong>
                        </div>

                        <div>
                            <span>Email</span>
                            <strong>
                                <a href="mailto:<?= pmEscape((string) $selectedMessage['sender_email']) ?>">
                                    <?= pmEscape((string) $selectedMessage['sender_email']) ?>
                                </a>
                            </strong>
                        </div>

                        <div>
                            <span>Received</span>
                            <strong><?= pmEscape(pmContactFormatDate((string) $selectedMessage['created_at'])) ?></strong>
                        </div>

                        <div class="contact-ip-box">
                            <span>IP Address</span>
                            <strong><?= pmEscape($selectedIpAddress !== '' ? $selectedIpAddress : 'Not recorded') ?></strong>

                            <?php if ($selectedIpAddress !== '' && !$ipIsBlacklisted): ?>
                                <form class="contact-ip-blacklist-form" action="src/inc/contact-handler.php" method="post" onsubmit="return confirm('Blacklist this IP address? Future messages from this IP will be silently discarded.');">
                                    <input type="hidden" name="csrf_token" value="<?= pmEscape(pmCsrfToken()) ?>">
                                    <input type="hidden" name="action" value="blacklist_ip">
                                    <input type="hidden" name="message_id" value="<?= (int) $selectedMessage['id'] ?>">
                                    <input type="hidden" name="ip_address" value="<?= pmEscape($selectedIpAddress) ?>">
                                    <button type="submit" class="ip-blacklist-button">Blacklist IP</button>
                                </form>
                            <?php elseif ($ipIsBlacklisted): ?>
                                <p class="ip-blacklisted-label">IP blacklisted</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="contact-message-body">
                        <?= nl2br(pmEscape((string) $selectedMessage['message'])) ?>
                    </div>

                    <div class="admin-actions">
                        <a class="contact-reply-button" href="mailto:<?= pmEscape((string) $selectedMessage['sender_email']) ?>?subject=Re:%20<?= rawurlencode($selectedSubject) ?>">
                            Reply by Email
                        </a>

                        <form action="src/inc/contact-handler.php" method="post">
                            <input type="hidden" name="csrf_token" value="<?= pmEscape(pmCsrfToken()) ?>">
                            <input type="hidden" name="action" value="archive">
                            <input type="hidden" name="message_id" value="<?= (int) $selectedMessage['id'] ?>">
                            <button type="submit" class="secondary-button">Archive</button>
                        </form>

                        <form action="src/inc/contact-handler.php" method="post" onsubmit="return confirm('Delete this contact message?');">
                            <input type="hidden" name="csrf_token" value="<?= pmEscape(pmCsrfToken()) ?>">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="message_id" value="<?= (int) $selectedMessage['id'] ?>">
                            <button type="submit" class="danger-button">Delete Message</button>
                        </form>
                    </div>
                </article>
            <?php endif; ?>
        </section>
    </div>
</section>
