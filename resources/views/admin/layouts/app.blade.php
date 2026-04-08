<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | RAB Consulting Services</title>
    <!-- Tailwind via CDN for quick admin setup, or use Vite if preferred by user -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.3/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-gray-100 font-sans text-gray-900">
    <div class="flex h-screen overflow-hidden">
        
        <!-- Sidebar -->
        <aside class="w-64 bg-slate-900 text-white flex flex-col hidden md:flex ring-1 ring-white/10 shrink-0">
            <div class="p-6 text-xl font-black border-b border-slate-800 flex items-center gap-3">
                <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center text-sm font-black italic">R</div>
                <span class="tracking-tighter uppercase">RAB <span class="text-blue-500">Admin</span></span>
            </div>
            <nav class="flex-1 px-4 py-8 space-y-1 overflow-y-auto">
                <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest px-4 mb-4">Operations</p>
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 hover:bg-slate-800 {{ request()->routeIs('admin.dashboard') ? 'bg-blue-600/10 text-blue-400 ring-1 ring-blue-500/20 shadow-lg' : 'text-slate-400' }}">
                    <svg class="w-5 h-5 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                    <span class="font-bold text-sm">Dashboard</span>
                </a>
                <a href="{{ route('admin.leads.index') }}" class="flex items-center justify-between px-4 py-3 rounded-xl transition-all duration-200 hover:bg-slate-800 {{ request()->routeIs('admin.leads.*') ? 'bg-blue-600/10 text-blue-400 ring-1 ring-blue-500/20 shadow-lg' : 'text-slate-400' }}">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        <span class="font-bold text-sm">Leads</span>
                    </div>
                </a>
                <a href="{{ route('admin.clients.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 hover:bg-slate-800 {{ request()->routeIs('admin.clients.*') ? 'bg-blue-600/10 text-blue-400 ring-1 ring-blue-500/20 shadow-lg' : 'text-slate-400' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1v1H9V7zm5 0h1v1h-1V7zm-5 4h1v1H9v-1zm5 0h1v1h-1v-1zm-5 4h1v1H9v-1zm5 0h1v1h-1v-1z"></path></svg>
                    <span class="font-bold text-sm">Clients</span>
                </a>
                <a href="{{ route('admin.assessments.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 hover:bg-slate-800 {{ request()->routeIs('admin.assessments.*') ? 'bg-blue-600/10 text-blue-400 ring-1 ring-blue-500/20 shadow-lg' : 'text-slate-400' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    <span class="font-bold text-sm">Assessments</span>
                </a>
                <a href="{{ route('admin.bookings.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 hover:bg-slate-800 {{ request()->routeIs('admin.bookings.*') ? 'bg-blue-600/10 text-blue-400 ring-1 ring-blue-500/20 shadow-lg' : 'text-slate-400' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    <span class="font-bold text-sm">Bookings</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 flex flex-col h-screen overflow-hidden bg-[#F8FAFC]">
            <!-- Topbar -->
            <header class="bg-white/80 backdrop-blur-md border-b border-gray-200 h-20 flex items-center justify-between px-8 shrink-0 relative z-10">
                <div class="flex items-center gap-8 flex-1">
                    <div class="text-xl font-black text-slate-800 tracking-tight">
                        @yield('header')
                    </div>
                    
                    {{-- Shared Search Bar --}}
                    <div class="max-w-md w-full relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </div>
                        <input type="text" name="global_search" id="global_search" 
                               class="block w-full pl-10 pr-3 py-2.5 border-none bg-gray-100 rounded-xl text-sm placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:bg-white transition-all" 
                               placeholder="Search leads, clients or assessments...">
                    </div>
                </div>
                
                <div class="flex items-center gap-6">
                    <div class="flex flex-col items-end">
                        <span class="text-xs font-black text-slate-400 uppercase tracking-widest leading-none mb-1">Authenticated</span>
                        <span class="text-sm font-bold text-slate-800">Assessor Mode</span>
                    </div>
                    <div class="w-10 h-10 bg-slate-100 rounded-full border-2 border-white shadow-sm flex items-center justify-center font-black text-slate-400">RC</div>
                </div>
            </header>

            <div class="flex-1 overflow-y-auto p-8">
                @if(session('success'))
                    <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                        <span class="block sm:inline">{{ session('success') }}</span>
                    </div>
                @endif
                
                @if($errors->any())
                    <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                        <ul>
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </div>
        </main>
    </div>

    @stack('scripts')
</body>
</html>
