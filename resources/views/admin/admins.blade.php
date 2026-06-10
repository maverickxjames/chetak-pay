@extends('admin.layout')

@section('page_title', 'Administrators & Roles Management')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Admin List -->
    <div class="glass-card rounded-2xl p-6 lg:col-span-2 space-y-4">
        <h3 class="text-lg font-bold text-white mb-4">Admin Accounts</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-white/10 text-xs text-gray-400 uppercase tracking-wider">
                        <th class="pb-3 font-semibold">Administrator</th>
                        <th class="pb-3 font-semibold">Mobile</th>
                        <th class="pb-3 font-semibold">Role</th>
                        <th class="pb-3 font-semibold text-right">Created</th>
                    </tr>
                </thead>
                <tbody class="text-sm divide-y divide-white/5">
                    @foreach($admins as $adm)
                        <tr>
                            <td class="py-3 font-semibold text-white">{{ $adm->name }}</td>
                            <td class="py-3 text-gray-300">{{ $adm->mobile }}</td>
                            <td class="py-3">
                                <span class="px-2.5 py-1 text-xs rounded-full font-bold
                                    @if($adm->role === 'super_admin') bg-magentaAccent/20 text-magentaAccent
                                    @else bg-blue-500/20 text-blue-400 @endif">
                                    {{ $adm->role === 'super_admin' ? 'Super Admin' : 'Admin' }}
                                </span>
                            </td>
                            <td class="py-3 text-right text-xs text-gray-400">
                                {{ $adm->created_at->format('Y-m-d') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Create Admin Form -->
    <div class="glass-card rounded-2xl p-6 h-fit">
        <h3 class="text-lg font-bold text-white mb-6 font-semibold">Create Administrator</h3>
        
        <form action="{{ route('admin.admins.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label for="name" class="block text-xs font-semibold uppercase text-gray-400 mb-2">Full Name</label>
                <input type="text" name="name" id="name" required placeholder="John Doe"
                       class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:border-[#FFC107] focus:ring-1 focus:ring-[#FFC107] transition-all">
            </div>

            <div>
                <label for="mobile" class="block text-xs font-semibold uppercase text-gray-400 mb-2">Mobile Number (Login ID)</label>
                <input type="text" name="mobile" id="mobile" required placeholder="9876543210"
                       class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:border-[#FFC107] focus:ring-1 focus:ring-[#FFC107] transition-all">
            </div>

            <div>
                <label for="email" class="block text-xs font-semibold uppercase text-gray-400 mb-2">Email Address</label>
                <input type="email" name="email" id="email" required placeholder="name@domain.com"
                       class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:border-[#FFC107] focus:ring-1 focus:ring-[#FFC107] transition-all">
            </div>

            <div>
                <label for="role" class="block text-xs font-semibold uppercase text-gray-400 mb-2">Security Role</label>
                <select name="role" id="role" required
                        class="w-full bg-[#1C002C] border border-white/10 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:border-[#FFC107] focus:ring-1 focus:ring-[#FFC107] transition-all">
                    <option value="admin">Admin (Approval rights, cannot modify settings/admins)</option>
                    <option value="super_admin">Super Admin (Full system control)</option>
                </select>
            </div>

            <div>
                <label for="password" class="block text-xs font-semibold uppercase text-gray-400 mb-2">Initial Password</label>
                <input type="password" name="password" id="password" required placeholder="••••••••"
                       class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:border-[#FFC107] focus:ring-1 focus:ring-[#FFC107] transition-all">
            </div>

            <div class="pt-4">
                <button type="submit"
                        class="w-full bg-[#C2185B] hover:bg-[#d31c62] text-white font-bold py-3 px-4 rounded-xl border border-white/10 transition-all">
                    Create Administrator
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
