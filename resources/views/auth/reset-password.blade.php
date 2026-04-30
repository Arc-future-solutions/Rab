@extends('layouts.public')

@section('title', 'Reset Password — RAB CONSULTING')

@section('content')
<section class="hero" style="padding: 100px 0;">
    <div class="container" style="max-width: 500px;">
        <div class="form-card">
            <div class="text-center mb-8">
                <div class="badge">Security</div>
                <h2 class="mt-4">Create New Password</h2>
                <p class="subtle">Please enter your new password below.</p>
            </div>

            <form action="{{ route('password.update') }}" method="POST" class="content-stack">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <div>
                    <label for="email">Email Address</label>
                    <input type="email" name="email" id="email" required value="{{ $email ?? old('email') }}" class="@error('email') border-red-500 @enderror">
                    @error('email')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password">New Password</label>
                    <input type="password" name="password" id="password" required autofocus class="@error('password') border-red-500 @enderror">
                    @error('password')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password_confirmation">Confirm New Password</label>
                    <input type="password" name="password_confirmation" id="password_confirmation" required>
                </div>

                <div class="hero-actions">
                    <button type="submit" class="btn primary w-full">Update Password</button>
                </div>
            </form>
        </div>
    </div>
</section>
@endsection
