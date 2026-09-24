</main>

<footer class="site-footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-brand">
                <a class="brand" href="<?= e(url('home')) ?>">
                    <span class="brand-mark">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M5 9h14l-1 11H6L5 9Z"></path><path d="M8 9a4 4 0 0 1 8 0M9 14v3M12 13v4M15 14v3"></path>
                        </svg>
                    </span>
                    <span style="color:#fff"><?= e(APP_NAME) ?><small>Fresh finds · Local roots</small></span>
                </a>
                <p>A local-first marketplace that makes weekly farm stock visible, pickup simple, and community connections last longer.</p>
                <div class="trust-row" style="color:rgba(255,255,255,.65)">
                    <span><?= icon('shield') ?> Secure accounts</span>
                    <span><?= icon('leaf') ?> Local produce</span>
                </div>
            </div>
            <div>
                <h2 class="footer-title">Marketplace</h2>
                <div class="footer-links">
                    <a href="<?= e(url('explore')) ?>">Browse produce</a>
                    <a href="<?= e(url('markets')) ?>">Find a market</a>
                    <a href="<?= e(url('farmers')) ?>">Meet farmers</a>
                    <a href="<?= e(url('register', ['role' => 'farmer'])) ?>">Sell with us</a>
                </div>
            </div>
            <div>
                <h2 class="footer-title">Company</h2>
                <div class="footer-links">
                    <a href="<?= e(url('about')) ?>">About us</a>
                    <a href="<?= e(url('contact')) ?>">Contact</a>
                    <a href="<?= e(url('faq')) ?>">Help & FAQ</a>
                    <a href="<?= e(url('login')) ?>">Sign in</a>
                </div>
            </div>
            <div>
                <h2 class="footer-title">Pickup only</h2>
                <div class="footer-links">
                    <span>No online payment</span>
                    <span>No delivery fee</span>
                    <span>No food-safety claims</span>
                    <span>Pay safely at pickup</span>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <span>&copy; <?= date('Y') ?> <?= e(APP_NAME) ?>. Built for fresher neighborhoods.</span>
            <span>Pickup orders · Community supported</span>
        </div>
    </div>
</footer>
<script src="<?= e(asset('assets/js/app.js')) ?>" defer></script>
</body>
</html>
