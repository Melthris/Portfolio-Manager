<?php
/**
 * Public Contact Me page.
 *
 * This page restores the full Portfolio Manager contact layout rather than the
 * simplified scaffold card. It keeps the same CSS class structure used by the
 * migrated admin-page layout, while all wording and handlers remain generic to
 * the public Portfolio Manager project.
 */

declare(strict_types=1);

pmStartSession();

$firstNumber = random_int(2, 9);
$secondNumber = random_int(2, 9);
$_SESSION['contact_human_answer'] = (string) ($firstNumber + $secondNumber);

$status = pmString($_GET['status'] ?? '');
$statusMessages = [
    'sent' => ['success', 'Your message has been sent.'],
    'blocked' => ['error', 'This message could not be accepted.'],
    'invalid' => ['error', 'The form session expired. Please try again.'],
    'missing' => ['error', 'Please complete your name, email, and message.'],
    'email' => ['error', 'Please enter a valid email address.'],
    'human' => ['error', 'The human check answer was incorrect. Please try again.'],
    'error' => ['error', 'Something went wrong while sending your message.'],
];
$flash = $statusMessages[$status] ?? null;
$contactHeading = pmContactHeading();
$contactBody = pmContactBody();
$contactCards = pmContactCards();
$contactSocialLinks = function_exists('pmContactSocialLinks') ? pmContactSocialLinks() : [];
?>

<section class="contact-page">
    <div class="contact-header">
        <p class="admin-kicker">Contact</p>
        <h1 class="contact-page-title">Contact Me</h1>
        <p class="contact-page-subtitle">
            Send a message to me about projects, collaboration, job opportunities, or professional enquiries.
        </p>
    </div>

    <div class="contact-layout">
        <aside class="contact-info-card">
            <h2><?= pmEscape($contactHeading) ?></h2>

            <?php foreach (preg_split('/\R{2,}/', $contactBody) ?: [] as $paragraph): ?>
                <?php $paragraph = trim($paragraph); ?>
                <?php if ($paragraph !== ''): ?>
                    <p><?= nl2br(pmEscape($paragraph)) ?></p>
                <?php endif; ?>
            <?php endforeach; ?>

            <?php if ($contactCards !== []): ?>
                <div class="contact-info-list">
                    <?php foreach ($contactCards as $card): ?>
                        <div>
                            <span><?= pmEscape($card['label']) ?></span>
                            <strong><?= pmEscape($card['text']) ?></strong>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($contactSocialLinks !== []): ?>
                <div class="contact-social-links-card">
                    <span>You can also find me on</span>
                    <div class="contact-social-links" aria-label="Contact page social media links">
                        <?= pmRenderSocialIconLinks($contactSocialLinks, 'contact-social-link') ?>
                    </div>
                </div>
            <?php endif; ?>

        </aside>

        <form class="contact-form-card" action="src/inc/contact-handler.php" method="post" novalidate>
            <div class="admin-card-header">
                <div>
                    <p class="admin-kicker">Message Form</p>
                    <h2>Send a Message</h2>
                </div>
            </div>

            <?php if (is_array($flash)): ?>
                <p class="contact-flash contact-flash-<?= pmEscape($flash[0]) ?>" role="alert">
                    <?= pmEscape($flash[1]) ?>
                </p>
            <?php endif; ?>

            <input type="hidden" name="csrf_token" value="<?= pmEscape(pmCsrfToken()) ?>">
            <input type="hidden" name="action" value="send">

            <label class="admin-field contact-honeypot" aria-hidden="true">
                <span>Website</span>
                <input class="textbox" type="text" name="website" tabindex="-1" autocomplete="off">
            </label>

            <div class="admin-form-grid">
                <label class="admin-field">
                    <span>Your Name</span>
                    <input class="textbox" type="text" name="name" maxlength="100" required>
                </label>

                <label class="admin-field">
                    <span>Your Email</span>
                    <input class="textbox" type="email" name="email" maxlength="180" required>
                </label>

                <label class="admin-field admin-field-wide">
                    <span>Subject</span>
                    <input class="textbox" type="text" name="subject" maxlength="150" required>
                </label>

                <label class="admin-field admin-field-wide">
                    <span>Message</span>
                    <textarea name="message" maxlength="5000" placeholder="Write your message here..." required></textarea>
                </label>

                <label class="admin-field admin-field-wide">
                    <span>Human Check: What is <?= (int) $firstNumber ?> + <?= (int) $secondNumber ?>?</span>
                    <input
                        class="textbox human-check-input"
                        type="number"
                        name="human_answer"
                        inputmode="numeric"
                        placeholder="Answer"
                        required
                    >
                </label>
            </div>

            <div class="admin-actions">
                <button type="submit">Send Message</button>
            </div>
        </form>
    </div>
</section>
