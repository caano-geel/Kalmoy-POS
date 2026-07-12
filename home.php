<?php
/**
 * Kalmoy POS — official platform homepage (Kalmoy Tech Solutions).
 * Client storefront module remains at ?p=storefront (not linked from this page).
 */
$platform_email = 'kalmoypos@gmail.com';
$platform_demo_url = 'mailto:' . $platform_email
    . '?subject=' . rawurlencode('Kalmoy POS Demo Request')
    . '&body=' . rawurlencode("Hello Kalmoy Tech Solutions,\n\nI would like to request a demo of Kalmoy POS for my retail business.\n\nBusiness name:\nLocation:\nPhone number:\n\nThank you.");
$platform_contact_url = 'mailto:' . $platform_email
    . '?subject=' . rawurlencode('Kalmoy POS Inquiry')
    . '&body=' . rawurlencode("Hello Kalmoy Tech Solutions,\n\nI have a question about Kalmoy POS.\n\nBusiness name:\nMy question:\nPhone number:\n\nThank you.");
?>
<script>document.body.classList.add('platform-site');</script>

<section class="platform-hero" id="top">
    <div class="container platform-hero-inner">
        <div class="row align-items-center platform-hero-row">
            <div class="col-lg-7 platform-hero-content platform-animate">
                <h1>Kalmoy POS — Professional retail management for modern businesses</h1>
                <p class="platform-hero-lead">
                    Sell faster, track stock accurately, and run daily operations with confidence. Kalmoy POS gives supermarkets, pharmacies, wholesalers, and specialty retailers one reliable system for sales, inventory, customers, and reports.
                </p>
                <div class="platform-hero-actions">
                    <a href="<?php echo htmlspecialchars($platform_demo_url) ?>" class="platform-btn platform-btn-primary">Request Demo</a>
                    <a href="#features" class="platform-btn platform-btn-outline-light">Learn More</a>
                </div>
            </div>
            <div class="col-lg-5 platform-hero-visual-col platform-animate platform-animate-delay">
                <div class="platform-hero-visual">
                    <img
                        src="<?php echo base_url ?>assets/img/process.png"
                        alt="Illustration of efficient retail operations with Kalmoy POS"
                        class="platform-hero-image"
                        width="512"
                        height="512"
                        decoding="async"
                        fetchpriority="high"
                    >
                </div>
            </div>
        </div>
        <div class="platform-hero-benefits platform-animate platform-animate-delay-2">
            <div class="platform-hero-benefit">
                <i class="fas fa-bolt" aria-hidden="true"></i>
                <div>
                    <strong>Faster checkout</strong>
                    <span>Serve customers quickly with less waiting at the till</span>
                </div>
            </div>
            <div class="platform-hero-benefit">
                <i class="fas fa-boxes" aria-hidden="true"></i>
                <div>
                    <strong>Accurate stock</strong>
                    <span>Know what you have before items run out</span>
                </div>
            </div>
            <div class="platform-hero-benefit">
                <i class="fas fa-shield-alt" aria-hidden="true"></i>
                <div>
                    <strong>Private business data</strong>
                    <span>Your records stay separate and secure for your shop only</span>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="platform-section platform-section-features" id="features">
    <div class="container platform-features-container">
        <header class="platform-features-head platform-animate">
            <span class="platform-label">What you get</span>
            <h2>Everything you need to run your shop</h2>
            <p>Practical tools for cashiers, managers, and owners — every day.</p>
            <span class="platform-features-accent" aria-hidden="true"></span>
        </header>
        <div class="platform-features-grid platform-animate" id="platformFeaturesGrid">
            <?php
            $features = array(
                array('fa-cash-register', 'Point of Sale', 'Faster checkout with barcode scanning, receipts, and M-Pesa.'),
                array('fa-boxes', 'Inventory Management', 'Real-time stock tracking with low-stock alerts.'),
                array('fa-shopping-bag', 'Products &amp; Catalog', 'Organize products, brands, categories, and prices.'),
                array('fa-truck', 'Purchasing', 'Record supplier purchases and inbound stock.'),
                array('fa-chart-bar', 'Sales &amp; Reports', 'Daily sales, payment breakdowns, and profit reports.'),
                array('fa-hand-holding-usd', 'Debt Management', 'Track customer credit, payments, and overdue balances.'),
                array('fa-users', 'Customer Records', 'Customer details and purchase history in one place.'),
                array('fa-user-shield', 'Staff Control', 'Permissions for owners, managers, and cashiers.'),
                array('fa-database', 'Secure Business Data', 'Backup and restore records when you need them.'),
            );
            $fi = 0;
            foreach ($features as $f):
                $fi++;
                $extra_class = ($fi > 6) ? ' platform-feature-block--extra' : '';
            ?>
            <article class="platform-feature-block<?php echo ($fi % 2 === 0) ? ' platform-feature-block--tint' : ''; ?><?php echo $extra_class; ?>">
                <span class="platform-feature-mark"><i class="fas <?php echo $f[0] ?>" aria-hidden="true"></i></span>
                <h3><?php echo $f[1] ?></h3>
                <p><?php echo $f[2] ?></p>
            </article>
            <?php endforeach; ?>
        </div>
        <div class="platform-features-toggle-wrap">
            <button type="button" class="platform-features-toggle" id="platformFeaturesToggle" aria-expanded="false" aria-controls="platformFeaturesGrid">
                <span class="platform-features-toggle-more">Show all features</span>
                <span class="platform-features-toggle-less">Show fewer features</span>
            </button>
        </div>
    </div>
</section>

<section class="platform-section platform-section-industries" id="industries">
    <div class="container">
        <div class="platform-section-header platform-section-header--compact platform-animate">
            <span class="platform-label">Built for your industry</span>
            <h2>Retail businesses we serve</h2>
            <p>Kalmoy POS adapts to supermarkets, pharmacies, wholesalers, and specialty retail.</p>
        </div>
        <div class="platform-industries-wrap platform-animate">
            <?php
            $industries = array(
                'fa-shopping-cart' => 'Supermarkets',
                'fa-apple-alt' => 'Grocery shops',
                'fa-store-alt' => 'Mini markets',
                'fa-pallet' => 'Wholesalers',
                'fa-pills' => 'Pharmacies',
                'fa-spa' => 'Cosmetics shops',
                'fa-tools' => 'Hardware stores',
                'fa-tshirt' => 'Fashion &amp; apparel',
                'fa-mobile-alt' => 'Electronics',
                'fa-coffee' => 'Cafés',
                'fa-leaf' => 'Wellness &amp; herbal',
            );
            foreach ($industries as $icon => $label):
            ?>
            <span class="platform-industry-pill"><i class="fas <?php echo $icon ?>" aria-hidden="true"></i><?php echo $label ?></span>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="platform-section platform-section-alt platform-section-why" id="why">
    <div class="container">
        <div class="row platform-why-row">
            <div class="col-lg-6 platform-animate">
                <span class="platform-label">Why Kalmoy POS</span>
                <h2 class="platform-why-heading">Built for reliable daily shop operations</h2>
                <p class="platform-why-intro">Everything your team needs to sell, track stock, and understand the business.</p>
                <div class="platform-benefit-row">
                    <i class="fas fa-mouse-pointer" aria-hidden="true"></i>
                    <div>
                        <h4>Easy to use</h4>
                        <p>A clear interface from the till to the back office.</p>
                    </div>
                </div>
                <div class="platform-benefit-row">
                    <i class="fas fa-bolt" aria-hidden="true"></i>
                    <div>
                        <h4>Fast checkout</h4>
                        <p>Barcode support and M-Pesa for quicker service.</p>
                    </div>
                </div>
                <div class="platform-benefit-row">
                    <i class="fas fa-boxes" aria-hidden="true"></i>
                    <div>
                        <h4>Accurate stock control</h4>
                        <p>Real-time inventory with fewer miscount losses.</p>
                    </div>
                </div>
                <div class="platform-benefit-row">
                    <i class="fas fa-chart-line" aria-hidden="true"></i>
                    <div>
                        <h4>Clear reports</h4>
                        <p>See sales, payments, and profit without guesswork.</p>
                    </div>
                </div>
                <div class="platform-benefit-row">
                    <i class="fas fa-headset" aria-hidden="true"></i>
                    <div>
                        <h4>Setup, training, and local support</h4>
                        <p>Kalmoy Tech Solutions helps you get started and grow.</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 platform-animate platform-animate-delay">
                <div class="platform-trust-block">
                    <button type="button" class="platform-trust-toggle" id="platformTrustToggle" aria-expanded="false" aria-controls="platformTrustDetails">
                        <span>Why businesses trust Kalmoy POS</span>
                        <i class="fas fa-chevron-down platform-trust-chevron" aria-hidden="true"></i>
                    </button>
                    <div class="platform-trust-details" id="platformTrustDetails">
                        <h3 class="platform-trust-heading">Why businesses trust Kalmoy POS</h3>
                        <p class="platform-trust-intro">A professional platform for real retail operations.</p>
                        <ul class="platform-trust-list">
                            <li><i class="fas fa-check" aria-hidden="true"></i> Debt management and customer credit tracking</li>
                            <li><i class="fas fa-check" aria-hidden="true"></i> Private business data — your records stay yours</li>
                            <li><i class="fas fa-check" aria-hidden="true"></i> Staff permissions for better accountability</li>
                            <li><i class="fas fa-check" aria-hidden="true"></i> Reliable daily sales and inventory workflows</li>
                            <li><i class="fas fa-check" aria-hidden="true"></i> Local setup, training, and ongoing support</li>
                            <li><i class="fas fa-check" aria-hidden="true"></i> One system for owners, managers, and cashiers</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="platform-section platform-section-about" id="about">
    <div class="container platform-about-container">
        <header class="platform-about-head platform-animate">
            <span class="platform-label">About Kalmoy POS</span>
            <h2>Retail management software by Kalmoy Tech Solutions</h2>
            <p class="platform-about-lead">Kalmoy POS is a professional point-of-sale and retail management platform built for businesses that need accurate daily operations — not a generic shop template.</p>
        </header>
        <div class="platform-about-grid platform-animate">
            <div class="platform-about-block">
                <h3>What Kalmoy POS is</h3>
                <p>Kalmoy POS helps retail businesses manage sales, stock, customers, purchasing, debt, and reporting from one reliable system. It is developed and supported by <strong>Kalmoy Tech Solutions</strong>.</p>
            </div>
            <div class="platform-about-block">
                <h3>Who it is for</h3>
                <p>Supermarkets, grocery shops, mini markets, pharmacies, wholesalers, hardware stores, cosmetics shops, fashion retailers, electronics shops, cafés, wellness stores, and other specialty retailers.</p>
            </div>
            <div class="platform-about-block">
                <h3>What it helps you run</h3>
                <p>Point-of-sale checkout, inventory control, product catalogs, supplier purchasing, customer records, staff permissions, debt tracking, sales reports, and the daily workflows owners and teams depend on.</p>
            </div>
            <div class="platform-about-block">
                <h3>Your business, your data</h3>
                <p>Each client business receives its own setup and separate data environment. Your sales, stock, and customer records stay dedicated to your business — not mixed with another shop.</p>
            </div>
            <div class="platform-about-block">
                <h3>Setup and support</h3>
                <p>Kalmoy Tech Solutions provides setup, training, and ongoing local support so your team can use the system with confidence as your business grows.</p>
            </div>
            <div class="platform-about-block">
                <h3>Our goal</h3>
                <p>Help retail businesses operate more accurately, serve customers faster, control stock better, and make clearer decisions with less manual work and less guesswork.</p>
            </div>
        </div>
    </div>
</section>

<section class="platform-section platform-section-cta" id="contact">
    <div class="container">
        <div class="platform-cta platform-animate">
            <h2>Ready to see Kalmoy POS in action?</h2>
            <p>Request a demo or ask about setup, training, and pricing.</p>
            <p class="platform-cta-email"><i class="fas fa-envelope" aria-hidden="true"></i> <a href="mailto:<?php echo htmlspecialchars($platform_email) ?>"><?php echo htmlspecialchars($platform_email) ?></a></p>
            <div class="platform-cta-actions">
                <a href="<?php echo htmlspecialchars($platform_demo_url) ?>" class="platform-btn platform-btn-light">Request Demo</a>
                <a href="<?php echo htmlspecialchars($platform_contact_url) ?>" class="platform-btn platform-btn-outline-light">Contact Us</a>
            </div>
        </div>
    </div>
</section>

<footer class="platform-footer">
    <div class="container">
        <div class="platform-footer-main">
            <div class="platform-footer-brand">
                <a href="./" class="platform-footer-logo-link">
                    <img src="<?php echo base_url ?>assets/img/kalmoy_logo.png" alt="Kalmoy POS logo — Kalmoy Tech Solutions point of sale software" class="platform-footer-logo" width="16" height="16" loading="lazy" decoding="async">
                    <span class="platform-footer-logo-text">Kalmoy POS</span>
                </a>
                <p class="platform-footer-tagline">Professional retail management software for modern businesses.</p>
            </div>
            <nav class="platform-footer-nav platform-footer-desktop" aria-label="Footer navigation">
                <ul class="platform-footer-links">
                    <li><a href="#features">Features</a></li>
                    <li><a href="#industries">Industries</a></li>
                    <li><a href="#why">Why Kalmoy</a></li>
                    <li><a href="<?php echo base_url ?>admin/login.php">Client Login</a></li>
                </ul>
            </nav>
            <div class="platform-footer-contact platform-footer-desktop">
                <ul class="platform-footer-links">
                    <li><a href="mailto:<?php echo htmlspecialchars($platform_email) ?>"><?php echo htmlspecialchars($platform_email) ?></a></li>
                    <li><a href="<?php echo htmlspecialchars($platform_demo_url) ?>">Request Demo</a></li>
                    <li><a href="#about">About</a></li>
                    <li><a href="./?p=platform-privacy">Privacy Policy</a></li>
                </ul>
            </div>
        </div>
        <div class="platform-footer-accordions" aria-label="Footer links">
            <details class="platform-footer-accordion">
                <summary>Platform</summary>
                <ul class="platform-footer-links">
                    <li><a href="#features">Features</a></li>
                    <li><a href="#industries">Industries</a></li>
                    <li><a href="#why">Why Kalmoy</a></li>
                    <li><a href="#about">About</a></li>
                    <li><a href="#contact">Get Started</a></li>
                </ul>
            </details>
            <details class="platform-footer-accordion">
                <summary>Contact &amp; Legal</summary>
                <ul class="platform-footer-links">
                    <li><a href="mailto:<?php echo htmlspecialchars($platform_email) ?>"><?php echo htmlspecialchars($platform_email) ?></a></li>
                    <li><a href="<?php echo htmlspecialchars($platform_demo_url) ?>">Request Demo</a></li>
                    <li><a href="<?php echo base_url ?>admin/login.php">Client Login</a></li>
                    <li><a href="./?p=platform-privacy">Privacy Policy</a></li>
                </ul>
            </details>
        </div>
        <div class="platform-footer-bottom">
            <span>&copy; <?php echo date('Y') ?> Kalmoy Tech Solutions. All rights reserved.</span>
        </div>
    </div>
</footer>

<script>
(function () {
    var featuresToggle = document.getElementById('platformFeaturesToggle');
    var featuresGrid = document.getElementById('platformFeaturesGrid');
    if (featuresToggle && featuresGrid) {
        featuresToggle.addEventListener('click', function () {
            var expanded = featuresGrid.classList.toggle('is-expanded');
            featuresToggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
            featuresToggle.classList.toggle('is-expanded', expanded);
        });
    }

    var trustToggle = document.getElementById('platformTrustToggle');
    var trustDetails = document.getElementById('platformTrustDetails');
    if (trustToggle && trustDetails) {
        trustToggle.addEventListener('click', function () {
            var open = trustDetails.classList.toggle('is-open');
            trustToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            trustToggle.classList.toggle('is-open', open);
        });
    }
})();
</script>
