@extends('layouts.public')

@section('title', 'Contact Us — Book a Consultation | RAB Consulting')

@section('content')
<!-- Contact Hero -->
<section class="section bg-soft" style="padding-top: 100px; padding-bottom: 60px; border-bottom: 1px solid var(--slate-200);">
    <div class="container">
        <h4 class="mb-4">Contact Us</h4>
        <h1 class="mb-6">Book a consulting discussion</h1>
        <p class="hero-text" style="max-width: 800px;">
            Whether you are looking for an independent PIR/SIR review, programme leadership support, or wider consulting advice, we are ready to discuss your requirements.
        </p>
    </div>
</section>

<!-- Contact Form Section -->
<section class="section">
    <div class="container">
        <div style="display: grid; grid-template-columns: 1fr 1.5fr; gap: 80px;">
            <!-- Contact Info -->
            <div>
                <h3 class="mb-6">Get in touch</h3>
                <p class="mb-8">Speak directly with our senior consultants about your programme or service environment.</p>
                
                <div style="display: flex; flex-direction: column; gap: 32px;">
                    <div>
                        <h4 class="mb-2" style="font-size: 0.8rem;">General Inquiries</h4>
                        <p style="font-weight: 700; color: var(--primary); font-size: 1.1rem; margin-bottom: 0;">contact@rabconsulting.co.uk</p>
                    </div>
                    <div>
                        <h4 class="mb-2" style="font-size: 0.8rem;">UK Office</h4>
                        <p style="color: var(--slate-600); line-height: 1.5;">United Kingdom<br>Senior-led delivery support nationwide.</p>
                    </div>
                </div>
                
                <div class="mt-12 p-8" style="background: var(--bg-highlight); border-radius: var(--radius);">
                    <h4 class="mb-4" style="color: var(--primary);">Need a quick assessment?</h4>
                    <p style="font-size: 0.9rem;">Gain initial insight through our 15-minute diagnostic before booking a full consultation.</p>
                    <a href="/rapid-consulting" class="btn-secondary mt-4 w-full">Start Diagnostic</a>
                </div>
            </div>
            
            <!-- Form -->
            <div class="bg-white" style="padding: 48px; border-radius: var(--radius-lg); border: 1px solid var(--slate-200); box-shadow: var(--shadow-lg);">
                <form action="{{ route('contact.submit') }}" method="POST">
                    @csrf
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px;">
                        <div>
                            <label for="name">Full Name</label>
                            <input type="text" id="name" name="name" placeholder="E.g. John Smith" required style="width: 100%; padding: 12px; border: 1px solid var(--slate-200); border-radius: var(--radius);">
                        </div>
                        <div>
                            <label for="email">Work Email</label>
                            <input type="email" id="email" name="email" placeholder="john@company.com" required style="width: 100%; padding: 12px; border: 1px solid var(--slate-200); border-radius: var(--radius);">
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px;">
                        <div>
                            <label for="company">Company</label>
                            <input type="text" id="company" name="company" placeholder="Company Name" required style="width: 100%; padding: 12px; border: 1px solid var(--slate-200); border-radius: var(--radius);">
                        </div>
                        <div>
                            <label for="role">Job Role</label>
                            <input type="text" id="role" name="role" placeholder="E.g. Programme Director" required style="width: 100%; padding: 12px; border: 1px solid var(--slate-200); border-radius: var(--radius);">
                        </div>
                    </div>
                    
                    <div style="margin-bottom: 24px;">
                        <label for="phone">Phone Number (Optional)</label>
                        <input type="tel" id="phone" name="phone" placeholder="+44 000 000 000" style="width: 100%; padding: 12px; border: 1px solid var(--slate-200); border-radius: var(--radius);">
                    </div>
                    
                    <div style="margin-bottom: 24px;">
                        <label for="interest">Area of Interest</label>
                        <select id="interest" name="interest" style="width: 100%; padding: 12px; border: 1px solid var(--slate-200); border-radius: var(--radius); background: white;">
                            <option value="pir">Programme Intelligence Review (PIR)</option>
                            <option value="sir">Service Intelligence Review (SIR)</option>
                            <option value="leadership">Programme/Project Leadership</option>
                            <option value="recovery">Recovery & Remediation</option>
                            <option value="augmentation">Team Augmentation</option>
                            <option value="other">Other Consulting Support</option>
                        </select>
                    </div>
                    
                    <div style="margin-bottom: 32px;">
                        <label for="message">How can we help?</label>
                        <textarea id="message" name="message" rows="5" placeholder="Tell us about your requirements..." style="width: 100%; padding: 12px; border: 1px solid var(--slate-200); border-radius: var(--radius); resize: vertical;"></textarea>
                    </div>
                    
                    <button type="submit" class="btn-primary" style="width: 100%; padding: 16px;">Request Consultation</button>
                    
                    <p class="mt-6 text-center" style="font-size: 0.75rem; color: var(--slate-400);">
                        By submitting this form, you agree to our <a href="/privacy-policy" style="text-decoration: underline;">Privacy Policy</a> and <a href="/terms" style="text-decoration: underline;">Terms of Service</a>.
                    </p>
                </form>
            </div>
        </div>
    </div>
</section>
@endsection
