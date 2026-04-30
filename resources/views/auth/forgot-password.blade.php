@extends('layouts.public')

@section('title', 'Forgot Password — RAB CONSULTING')

@section('content')
<section class="hero" style="padding: 100px 0;">
    <div class="container" style="max-width: 500px;">
        <div class="form-card">
            <div class="text-center mb-8">
                <div class="badge">Security</div>
                <h2 class="mt-4">Reset Password</h2>
                <p class="subtle">Enter your email address and we'll send you a link to reset your password.</p>
            </div>

            @if(session('success'))
                <div class="mb-6 p-4 rounded-xl bg-green-50 text-green-700 border border-green-200 text-sm font-medium">
                    {{ session('success') }}
                </div>
            @endif

            <form action="{{ route('password.email') }}" method="POST" class="content-stack">
                @csrf
                <div>
                    <label for="email">Email Address</label>
                    <input type="email" name="email" id="email" required placeholder="name@company.com" class="@error('email') border-red-500 @enderror" value="{{ old('email') }}">
                    @error('email')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="hero-actions">
                    <button type="submit" class="btn primary w-full">Send Reset Link</button>
                    <a href="{{ route('login') }}" class="btn ghost w-full">Back to Login</a>
                </div>
            </form>
        </div>
    </div>
</section>
@endsection
