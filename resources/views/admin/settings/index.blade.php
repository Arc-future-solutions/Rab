@extends('admin.layouts.app')

@section('header', 'Account Settings')

@section('content')
<div class="max-w-4xl mx-auto space-y-8">
    
    <!-- Profile Section -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-8 border-b border-slate-100 bg-slate-50/50">
            <h3 class="text-xl font-bold text-slate-900">Administrator Profile</h3>
            <p class="text-sm text-slate-600 mt-1">Manage your administrator account credentials and security.</p>
        </div>
        
        <div class="p-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                    <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-2">Display Name</label>
                    <div class="px-4 py-3 rounded-xl bg-slate-100 text-slate-900 font-bold border border-slate-200">
                        {{ auth()->user()->name }}
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-2">Email Address</label>
                    <div class="px-4 py-3 rounded-xl bg-slate-100 text-slate-900 font-bold border border-slate-200">
                        {{ auth()->user()->email }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Security Section -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-8 border-b border-slate-100 bg-slate-50/50">
            <h3 class="text-xl font-bold text-slate-900">Password & Security</h3>
            <p class="text-sm text-slate-600 mt-1">Ensure your account is using a long, random password to stay secure.</p>
        </div>
        
        <div class="p-8">
            <form action="{{ route('admin.settings.password') }}" method="POST" class="space-y-6 max-w-lg">
                @csrf
                
                <div>
                    <label for="current_password" class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-2">Current Password</label>
                    <input type="password" name="current_password" id="current_password" required 
                           class="w-full px-4 py-3 rounded-xl border border-slate-300 bg-white text-slate-900 focus:ring-4 focus:ring-blue-500/10 focus:border-blue-600 transition-all outline-none placeholder-slate-400 shadow-sm">
                    @error('current_password')
                        <p class="text-red-500 text-xs mt-2 font-bold">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-2">New Password</label>
                    <input type="password" name="password" id="password" required 
                           class="w-full px-4 py-3 rounded-xl border border-slate-300 bg-white text-slate-900 focus:ring-4 focus:ring-blue-500/10 focus:border-blue-600 transition-all outline-none placeholder-slate-400 shadow-sm">
                    @error('password')
                        <p class="text-red-500 text-xs mt-2 font-bold">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-2">Confirm New Password</label>
                    <input type="password" name="password_confirmation" id="password_confirmation" required 
                           class="w-full px-4 py-3 rounded-xl border border-slate-300 bg-white text-slate-900 focus:ring-4 focus:ring-blue-500/10 focus:border-blue-600 transition-all outline-none placeholder-slate-400 shadow-sm">
                </div>

                <div class="pt-4">
                    <button type="submit" class="px-8 py-3 bg-blue-600 text-white rounded-xl font-bold shadow-lg shadow-blue-500/20 hover:bg-blue-700 hover:-translate-y-0.5 transition-all">
                        Update Password
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
