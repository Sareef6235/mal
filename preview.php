<?php
require __DIR__ . '/lib.php';
$user = currentUser();
renderHead('Live Preview', 'Interactive premium preview page for sending sample messages in a WhatsApp-style support layout.');
?>
<body>
<div class="page-shell marketing-page">
    <div class="ambient ambient-a"></div>
    <div class="ambient ambient-b"></div>
    <header class="topbar glass" aria-label="Preview header">
        <div class="brand">
            <div class="brand-mark">PS</div>
            <div>
                <strong>Premium Support Desk</strong>
                <p>Interactive live preview of the premium messaging layout</p>
            </div>
        </div>
        <?php renderPrimaryMenu($user, 'preview'); ?>
    </header>

    <main>
        <section class="page-hero card">
            <div class="section-intro">
                <span class="eyebrow">Live Preview</span>
                <h1>Send a message inside the premium preview card.</h1>
                <p class="lead">This demo page lets you type and send sample messages directly inside the styled preview so you can present the experience like a real product demo.</p>
            </div>
        </section>

        <section class="preview-grid">
            <aside class="hero-card card premium-card" aria-label="Interactive preview card">
                <div class="mini-topbar"><span></span><span></span><span></span></div>
                <div class="support-widget demo-widget">
                    <div class="ticket-pill">Ticket #TKT-DEMO91 · Urgent</div>
                    <div class="widget-toolbar">
                        <span class="soft-chip">Admin online</span>
                        <span class="soft-chip">Avg reply 05 min</span>
                    </div>
                    <div class="demo-chat" id="demoChat" aria-live="polite">
                        <div class="chat-bubble customer">Order #ORD-2026-1102 updated. Please confirm delivery schedule.</div>
                        <div class="chat-bubble agent">Hi Arjun 👋 Delivery is confirmed. Invoice and ticket update have been queued for email and WhatsApp.</div>
                        <div class="chat-bubble customer">Great, നന്ദി.</div>
                    </div>
                    <form class="demo-composer" id="demoComposer">
                        <textarea id="demoMessage" placeholder="Type your preview message..." rows="3" required></textarea>
                        <div class="composer-actions">
                            <button class="primary-btn" type="submit">Send Message</button>
                            <button class="secondary-btn" type="button" id="demoReply">Auto Reply</button>
                        </div>
                    </form>
                    <div class="widget-footer">
                        <span>Email ready</span>
                        <span>WhatsApp queue</span>
                        <span>Admin notes</span>
                    </div>
                </div>
            </aside>

            <section class="card preview-info">
                <span class="eyebrow">Preview details</span>
                <h2>Interactive premium message demo</h2>
                <p class="lead">Use this page during presentation or development to showcase how messages appear, how the premium colors behave, and how the layout responds across screen sizes.</p>
                <ul class="feature-list">
                    <li>Send sample customer messages instantly</li>
                    <li>Trigger a styled admin auto-reply</li>
                    <li>Responsive layout with premium hover states</li>
                    <li>Consistent design with the rest of the application</li>
                </ul>
            </section>
        </section>
    </main>

    <?php renderFooter(); ?>
</div>
<script>
const demoChat = document.getElementById('demoChat');
const demoComposer = document.getElementById('demoComposer');
const demoMessage = document.getElementById('demoMessage');
const demoReply = document.getElementById('demoReply');

function appendBubble(text, role) {
    const bubble = document.createElement('div');
    bubble.className = 'chat-bubble ' + role;
    bubble.textContent = text;
    demoChat.appendChild(bubble);
    demoChat.scrollTop = demoChat.scrollHeight;
}

demoComposer.addEventListener('submit', function (event) {
    event.preventDefault();
    const value = demoMessage.value.trim();
    if (!value) {
        return;
    }
    appendBubble(value, 'customer');
    demoMessage.value = '';
});

demoReply.addEventListener('click', function () {
    appendBubble('Thanks for the update. Our support team is reviewing this request now.', 'agent');
});
</script>
</body>
</html>
