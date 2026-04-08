<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>@yield('title', 'RAB Assessment Platform — RAB CONSULTING')</title>
    <meta name="description" content="RAB Assessment Platform front-end package"/>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,300&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/styles.css"/>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <header class="topbar">
        <div class="nav px-8">
            <a class="brand" href="/">
                <img class="brand-logo" src="/assets/images/logo-rab.png" alt="RAB Consulting logo">
                <span class="brand-text"><strong>RAB CONSULTING</strong><span>ASSESSMENT PLATFORM</span></span>
            </a>
            <nav class="nav-links">
                <a data-nav href="/about">About</a>
                <a data-nav href="/services">Services</a>
                <a data-nav href="/pricing">Pricing</a>
                <a data-nav href="/rapid-consulting">Rapid Consulting</a>
                <a data-nav href="/contact">Contact</a>
            </nav>
            <div style="display:flex;gap:12px;align-items:center;">
                <button class="btn ghost menu-toggle" data-menu-toggle>Menu</button>
                <a class="nav-cta" href="/admin">Open portal preview</a>
            </div>
        </div>
    </header>

    @yield('content')

    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div>
                    <div class="brand" style="margin-bottom:16px;">
                        <img class="brand-logo" src="/assets/images/logo-rab.png" alt="RAB Consulting logo">
                        <span class="brand-text"><strong>RAB CONSULTING</strong><span>ASSESSMENT PLATFORM</span></span>
                    </div>
                    <p>Unified front-end for Programme Health Check, Service Health Check, full consultant-led assessments, reporting workflows and internal review operations.</p>
                </div>
                <div>
                    <h4>Public Pages</h4>
                    <div class="footer-links">
                        <a href="/programme-health-check">Programme Health Check</a>
                        <a href="/service-health-check">Service Health Check</a>
                        <a href="/pricing">Pricing</a>
                        <a href="/rapid-consulting">Rapid Consulting</a>
                    </div>
                </div>
                <div>
                    <h4>Portal</h4>
                    <div class="footer-links">
                        <a href="/admin">Dashboard</a>
                        <a href="/admin/leads">Leads</a>
                        <a href="/admin/assessments">Assessments</a>
                        <a href="/admin/reports">Reports</a>
                    </div>
                </div>
                <div>
                    <h4>Legal</h4>
                    <div class="footer-links">
                        <a href="/privacy-policy">Privacy Policy</a>
                        <a href="/terms">Terms</a>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <span>© 2026 RAB Consulting Services</span>
                <span>Front-end build aligned to PHI and ITSM-HI platform structure.</span>
            </div>
        </div>
    </footer>
    <script src="/assets/js/main.js"></script>
    @stack('scripts')
</body>
</html>
