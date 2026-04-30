# RAB Consulting Website Analysis

## Technology Stack
- **Backend:** Laravel 13 (PHP 8.3)
- **Frontend:** 
    - HTML/Blade Templates
    - Tailwind CSS 4.0 (via CDN and Vite)
    - Alpine.js (for interactivity/dropdowns)
    - Chart.js (for reporting/dashboards)
- **Fonts:** Inter (Sans), Manrope (Headers)
- **Build Tool:** Vite

## Page Map & Components

### Public Pages
- **Home (`/`)**: Main landing page. Features: Capabilities section, Intelligence Reviews (PIR/SIR) intro, "Your Path to Clarity" journey.
- **Services (`/services`)**: Detailed service offerings.
    - Programme & Project Leadership
    - Intelligence Reviews (PIR/SIR)
    - Recovery & Remediation
    - Team Augmentation
    - Business Consulting
    - AI, Automation & Development
- **PIR (`/pir`)**: Programme Intelligence Review specialized page.
- **SIR (`/sir`)**: Service Intelligence Review specialized page.
- **About (`/about`)**: Company information.
- **Contact (`/contact`)**: Contact form/details.
- **Rapid Consulting (`/rapid-consulting`)**: 15-Minute Diagnostic tool.
    - Includes: Select Type, Personal Form, Assessment, Results.
- **Booking (`/booking`)**: Consultation booking page.
- **Legal**: Privacy Policy, Terms & Conditions, Cookie Policy.

### User/Client Portal
- **User Dashboard (`/user/dashboard`)**: Client-side overview.
- **User Results (`/user/results`)**: View diagnostic/assessment results.
- **Login/Auth**: Login, Forgot Password, Reset Password.

### Admin Portal
- **Admin Dashboard (`/admin/dashboard`)**: Management overview.
- **Assessments Management**: Create, Index, Show, and Scoring (ITSM/PHI) for assessments.
- **Client Management**: Client index and detailed views.
- **Lead Management**: Tracking prospective leads.
- **Frameworks Management**: Managing assessment frameworks.
- **Settings**: Site/system configuration.
- **Reports**: Administrative reporting.

## Brand Kit & Visual Identity

### Color Palette
- **Primary:** `#1E3A8A` (Deep Blue) - Used for primary branding and key accents.
- **Action:** `#2563EB` (Bright Blue) - Used for buttons and calls to action.
- **Ink:** `#0F172A` (Dark Navy/Slate) - Main text color for high contrast.
- **Slate:** `#475569` (Cool Grey) - Secondary text and muted elements.
- **Cloud:** `#F8FAFC` (Off-White/Blue-Grey) - Backgrounds and light sections.
- **Line:** `#E2E8F0` (Light Grey) - Borders and separators.

### Typography
- **Primary Sans:** Inter (Used for body text, UI elements)
- **Headings:** Manrope (Used for bold, professional headers)

### Design Language
- **Style:** Modern, Professional, Enterprise-grade.
- **UI Patterns:** 
    - Rounded corners (`border-radius`)
    - Subtle shadows for "Authority Blocks"
    - Clean, white-space heavy layouts
    - High-contrast "Badges" for focus areas (Programme vs Service)
- **Imagery:** Professional logos (`logo-rab.png`), clean iconography (SVG).
