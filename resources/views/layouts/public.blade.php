<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>@yield('title', 'RAB Consulting — Programme & Service Intelligence (PIR / SIR) & Programme Leadership')</title>
    <meta name="description" content="RAB Consulting helps organisations understand the true state of critical delivery and service environments through independent review and senior-led leadership." />

    <!-- Typography: Inter, Manrope & DM Serif Display -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=Inter:wght@300;400;500;600;700&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="/assets/css/styles.css" />

    <!-- Modern Styling: Tailwind CSS for premium components -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#1E3A8A',
                        /* color-primary */
                        action: '#2563EB',
                        /* color-action */
                        ink: '#0F172A',
                        /* color-ink */
                        slate: '#475569',
                        /* color-slate */
                        cloud: '#F8FAFC',
                        /* color-cloud */
                        line: '#E2E8F0',
                        /* color-line */
                    },
                    fontFamily: {
                        serif: ['"DM Serif Display"', 'serif'],
                        sans: ['Inter', 'sans-serif'],
                        inter: ['Inter', 'sans-serif'],
                        manrope: ['Manrope', 'sans-serif'],
                    }
                }
            }
        }
    </script>

    <style>
        .btn-primary {
            @apply inline-flex items-center justify-center px-8 py-4 bg-primary text-white font-medium rounded-xl hover:bg-action transition-colors group/btn;
        }

        .btn-secondary {
            @apply inline-flex items-center justify-center px-8 py-4 bg-white text-ink font-medium rounded-xl border-2 border-line hover:border-primary hover:bg-cloud transition-all;
        }

        /* General Section & Header Styling */
        .section {
            @apply py-20 md:py-28;
        }

        .section-header {
            @apply text-center max-w-3xl mx-auto mb-16;
        }

        .section-header h4 {
            @apply inline-block px-4 py-1.5 rounded-full bg-primary/10 text-primary text-xs font-bold uppercase tracking-widest mb-4;
        }

        .section-header h2 {
            @apply font-manrope text-4xl md:text-5xl font-bold text-ink mb-6;
        }

        .hero-section {
            @apply grid grid-cols-1 lg:grid-cols-2 gap-12 items-center;
        }

        .hero-section h4 {
            @apply text-lg font-semibold text-primary mb-3;
        }

        .hero-section h1 {
            @apply font-manrope text-5xl md:text-6xl font-extrabold text-ink mb-6;
        }

        .hero-text {
            @apply text-lg leading-relaxed text-slate mb-8;
        }

        .hero-actions {
            @apply flex gap-4;
        }

        .microcopy {
            @apply block text-xs text-slate-500 mt-4;
        }

        .card-service {
            @apply bg-white rounded-xl border border-line p-7 transition-all duration-300 hover:shadow-authority hover:-translate-y-1 hover:border-action/30;
        }

        .card-service .badge {
            @apply inline-block px-4 py-1.5 rounded-full bg-primary/10 text-primary text-xs font-bold uppercase tracking-wider mb-6;
        }

        .card-service h3 {
            @apply font-manrope font-bold text-xl text-ink mb-3;
        }

        .card-service p {
            @apply text-slate text-sm leading-relaxed;
        }

        .grid-cards {
            @apply grid grid-cols-1 md:grid-cols-3 gap-6;
        }

        .footer-main {
            @apply bg-ink text-white py-16 md:py-20;
        }

        .footer-grid {
            @apply grid grid-cols-1 md:grid-cols-4 gap-8 mb-12;
        }

        .footer-brand-col .brand-logo {
            @apply h-10 mb-4;
        }

        .footer-desc {
            @apply text-slate-300 text-sm leading-relaxed;
        }

        .footer-title {
            @apply font-manrope font-bold text-lg text-white mb-6;
        }

        .footer-links li a {
            @apply text-slate-300 hover:text-white transition-colors block mb-3 text-sm;
        }

        .footer-bottom {
            @apply border-t border-slate-700 pt-8 text-sm text-slate-400 flex flex-col md:flex-row items-center justify-between gap-4;
        }

        .footer-legal {
            @apply flex items-center gap-4;
        }

        .legal-nav a {
            @apply hover:text-white transition-colors;
        }

        /* Mobile Navigation */
        .nav-main {
            @apply fixed inset-y-0 right-0 w-64 bg-ink transform translate-x-full md:relative md:translate-x-0 md:bg-transparent transition-transform duration-300 ease-in-out z-50 md:flex md:items-center md:space-x-8 lg:space-x-10 flex-col md:flex-row pt-20 md:pt-0;
        }

        .nav-main.active {
            @apply translate-x-0;
        }

        .nav-main a {
            @apply block text-white text-lg md:text-base font-medium py-3 px-6 md:px-0 md:py-0 hover:text-primary transition-colors;
        }

        .nav-main .active {
            @apply text-primary;
        }

        .nav-dropdown {
            @apply relative;
        }

        .nav-dropdown-trigger {
            @apply flex items-center justify-between w-full;
        }

        .dropdown-pane {
            @apply hidden md:absolute bg-white shadow-lg rounded-lg md:mt-3 py-2 w-48;
        }

        .dropdown-pane a {
            @apply block px-4 py-2 text-ink text-sm hover:bg-cloud;
        }

        .header-actions {
            @apply flex items-center gap-4;
        }

        .menu-mobile-toggle {
            @apply md:hidden text-slate-600 hover:text-primary;
        }

        .nav-backdrop {
            @apply fixed inset-0 bg-black opacity-0 pointer-events-none transition-opacity duration-300 ease-in-out z-40;
        }

        .nav-backdrop.active {
            @apply opacity-50 pointer-events-auto;
        }

        @media (max-width: 767px) {
            .hidden-desktop {
                display: block !important;
            }

            .hidden-mobile {
                display: none !important;
            }
        }
    </style>

    <!-- Keeping Alpine for interactions like dropdowns/modals -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    @stack('head')
</head>

<body class="bg-white text-slate-900 font-inter antialiased">
    <div class="nav-backdrop" data-menu-toggle></div>
    @if(!isset($hide_nav) || !$hide_nav)
    <header class="header-sticky">
        <div class="container container-nav">
            <a class="brand" href="/">
                <img class="brand-logo" src="/assets/images/logo-rab.png" alt="RAB Consulting logo">

            </a>

            <nav class="nav-main">
                <a href="/" class="{{ Request::is('/') ? 'active' : '' }}">Home</a>

                <div class="nav-dropdown" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false">
                    <a href="/services" class="nav-dropdown-trigger {{ Request::is('services*') ? 'active' : '' }}">
                        Services
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m6 9 6 6 6-6" />
                        </svg>
                    </a>
                    <div class="dropdown-pane" x-show="open" x-transition x-cloak>
                        <a href="/services#leadership">Programme & Project Leadership</a>
                        <a href="/services#reviews">Intelligence Reviews (PIR/SIR)</a>
                        <a href="/services#recovery">Recovery & Remediation</a>
                        <a href="/services#augmentation">Team Augmentation</a>
                        <a href="/services#consulting">Business Consulting</a>
                        <a href="/services#digital">AI, Automation & Development</a>
                    </div>
                </div>

                <a href="/pir" alt="Programme Intelligence Review" class="{{ Request::is('pir*') ? 'active' : '' }}">PIR</a>
                <a href="/sir" alt="Service Intelligence Review" class="{{ Request::is('sir*') ? 'active' : '' }}">SIR</a>
                <a href="/about" class="{{ Request::is('about*') ? 'active' : '' }}">About</a>
                <a href="/contact" class="{{ Request::is('contact*') ? 'active' : '' }}">Contact</a>

                @auth
                <a href="{{ auth()->user()->role === 'admin' ? route('admin.dashboard') : route('user.dashboard') }}" class="{{ Request::is('user/dashboard*') || Request::is('admin*') ? 'active' : '' }}">Dashboard</a>
                @endauth

                <div class="mt-8 hidden-desktop">
                    <a href="/booking" class="btn-primary w-full py-4 text-center block mb-4">Book a Consultation</a>
                    @auth
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn-secondary w-full py-4 text-center flex items-center justify-center gap-2 border-slate-200 text-slate-600">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                                <polyline points="16 17 21 12 16 7"></polyline>
                                <line x1="21" y1="12" x2="9" y2="12"></line>
                            </svg>
                            Logout
                        </button>
                    </form>
                    @endauth
                </div>
            </nav>

            <div class="header-actions">
                <a href="/rapid-consulting" class="btn-secondary hidden-mobile">Start Diagnostic</a>
                <a href="/booking" class="btn-primary hidden-mobile">Book a Consultation</a>

                @auth
                <form action="{{ route('logout') }}" method="POST" class="hidden-mobile">
                    @csrf
                    <button type="submit" class="ml-2 flex items-center gap-2 text-slate-500 hover:text-red-600 transition-colors font-medium p-2 rounded-lg hover:bg-red-50" title="Logout">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                            <polyline points="16 17 21 12 16 7"></polyline>
                            <line x1="21" y1="12" x2="9" y2="12"></line>
                        </svg>
                    </button>
                </form>
                @endauth

                <button class="menu-mobile-toggle" data-menu-toggle aria-label="Toggle menu">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="3" y1="12" x2="21" y2="12"></line>
                        <line x1="3" y1="6" x2="21" y2="6"></line>
                        <line x1="3" y1="18" x2="21" y2="18"></line>
                    </svg>
                </button>
            </div>
        </div>
    </header>
    @endif

    <main>
        @yield('content')
    </main>

    @if(!isset($hide_nav) || !$hide_nav)
    <footer class="footer-main">
        <style>
            @media (max-width: 768px) {
                .footer-grid {
                    grid-template-columns: 1fr 1fr;
                }

                .hidden-desktop {
                    display: block;
                }
            }
        </style>
        <div class="container">
            <div class="footer-grid">
                <div class="footer-brand-col">
                    <div class="brand mb-6">
                        <img class="brand-logo" src="/assets/images/logo-rab copy.png" alt="RAB Consulting logo">
                        <div class="brand-info">
                            <span class="brand-name">RAB CONSULTING</span>
                        </div>
                    </div>
                    <p class="footer-desc">
                        Providing independent insight, leadership, and delivery confidence for complex programmes and IT service environments.
                    </p>
                </div>

                <div class="footer-links-col">
                    <h4 class="footer-title">Services</h4>
                    <ul class="footer-links">
                        <li><a href="/services#leadership">Programme Project Leadership</a></li>
                        <li><a href="/services#reviews">Intelligence Reviews (PIR/SIR)</a></li>
                        <li><a href="/services#recovery">Recovery & Remediation</a></li>
                        <li><a href="/services#digital">AI & Automation</a></li>
                    </ul>
                </div>

                <div class="footer-links-col">
                    <h4 class="footer-title">Reviews</h4>
                    <ul class="footer-links">
                        <li><a href="/pir">Programme Intelligence Review (PIR)</a></li>
                        <li><a href="/sir">Service Intelligence Review (SIR)</a></li>
                        <li><a href="/rapid-consulting">15-Minute Diagnostic</a></li>
                    </ul>
                </div>

                <div class="footer-links-col">
                    <h4 class="footer-title">Company</h4>
                    <ul class="footer-links">
                        <li><a href="/about">About Us</a></li>
                        <li><a href="/contact">Contact</a></li>
                        <li><a href="/login" class="text-slate-400 opacity-50 hover:opacity-100">Portal Login</a></li>
                    </ul>
                </div>
            </div>

            <div class="footer-bottom">
                <div class="footer-legal">
                    <span class="copyright">© {{ date('Y') }} RAB Consulting Services Ltd</span>
                    <nav class="legal-nav">
                        <a href="/privacy">Privacy Policy</a>
                        <a href="/terms">Terms & Conditions</a>
                        <a href="/cookies">Cookie Policy</a>
                    </nav>
                </div>
                <div class="footer-tagline">
                    Senior-led Programme & Service Intelligence (PIR / SIR)
                </div>
            </div>
        </div>
    </footer>
    @endif

    <script src="/assets/js/main.js"></script>
    @stack('scripts')
</body>

</html>
