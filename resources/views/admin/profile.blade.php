@extends('admin.layout')

@section('page_title', 'My Profile Settings')

@section('content')
<div class="max-w-2xl mx-auto grid grid-cols-1 md:grid-cols-3 gap-6">
    <!-- Profile Card -->
    <div class="glass-card rounded-2xl p-6 h-fit text-center space-y-4">
        <div class="w-20 h-20 mx-auto rounded-full bg-[#C2185B] border-2 border-[#FFC107] flex items-center justify-center font-bold text-[#FFC107] text-3xl">
            {{ substr($admin->name ?? 'A', 0, 1) }}
        </div>
        <div>
            <h3 class="text-lg font-bold text-white">{{ $admin->name }}</h3>
            <p class="text-xs text-gray-400 capitalize">{{ $admin->role === 'super_admin' ? 'Super Administrator' : 'Administrator' }}</p>
        </div>
        <hr class="border-white/10">
        <div class="text-left space-y-2 text-sm">
            <p class="text-gray-400">Mobile: <span class="text-white float-right">{{ $admin->mobile }}</span></p>
            <p class="text-gray-400">Email: <span class="text-white float-right">{{ $admin->email }}</span></p>
        </div>
    </div>

    <!-- Password Change Form -->
    <div class="glass-card rounded-2xl p-6 md:col-span-2">
        <h3 class="text-lg font-bold text-white mb-6 font-semibold">Change Security Password</h3>
        
        <form action="{{ route('admin.profile.password') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label for="current_password" class="block text-xs font-semibold uppercase text-gray-400 mb-2">Current Password</label>
                <input type="password" name="current_password" id="current_password" required placeholder="••••••••"
                       class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:border-[#FFC107] focus:ring-1 focus:ring-[#FFC107] transition-all">
            </div>

            <div>
                <label for="new_password" class="block text-xs font-semibold uppercase text-gray-400 mb-2">New Password</label>
                <input type="password" name="new_password" id="new_password" required placeholder="Min 6 characters"
                       class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:border-[#FFC107] focus:ring-1 focus:ring-[#FFC107] transition-all">
            </div>

            <div>
                <label for="new_password_confirmation" class="block text-xs font-semibold uppercase text-gray-400 mb-2">Confirm New Password</label>
                <input type="password" name="new_password_confirmation" id="new_password_confirmation" required placeholder="••••••••"
                       class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:border-[#FFC107] focus:ring-1 focus:ring-[#FFC107] transition-all">
            </div>

            <div class="pt-4">
                <button type="submit"
                        class="w-full bg-[#C2185B] hover:bg-[#d31c62] text-white font-bold py-3 px-4 rounded-xl border border-white/10 transition-all">
                    Update Password
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
