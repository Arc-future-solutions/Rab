@extends('layouts.public')

@section('content')
<div class="min-h-[70vh] flex items-center justify-center bg-gray-50 py-20 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8 bg-white p-10 rounded-3xl shadow-xl text-center border border-gray-100">
        <div>
            <div class="mx-auto flex items-center justify-center h-20 w-20 rounded-full bg-green-100 mb-6">
                <svg class="h-10 w-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <h2 class="text-4xl font-black text-gray-900 tracking-tight mb-2">Thank You!</h2>
            <p class="text-lg text-gray-500 font-medium">Your inquiry has been successfully received.</p>
        </div>
        
        <div class="py-6 border-t border-b border-gray-50 my-6">
            <p class="text-sm text-gray-400 italic">
                Our team has been notified and we are currently reviewing your requirements. 
                One of our senior consultants will reach out to you within the next 24 business hours.
            </p>
        </div>

        <div class="space-y-4">
            <a href="/" class="w-full flex items-center justify-center px-8 py-3 border border-transparent text-base font-bold rounded-xl text-white bg-blue-600 hover:bg-blue-700 md:py-4 md:text-lg md:px-10 transition-all shadow-lg shadow-blue-500/20">
                Back to Homepage
            </a>
            <p class="text-xs text-gray-400 uppercase tracking-widest font-black">
                Rapid Consulting Platform
            </p>
        </div>
    </div>
</div>
@endsection
