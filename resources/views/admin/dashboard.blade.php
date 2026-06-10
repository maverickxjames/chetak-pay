@extends('admin.layout')

@section('page_title', 'System Dashboard')

@section('content')
<!-- Today's Statistics Row -->
<div class="space-y-4">
    <h3 class="text-sm font-bold uppercase tracking-wider text-gray-400">Today's Statistics</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Today Investments -->
        <div class="glass-card rounded-2xl p-6 flex items-center">
            <div class="p-4 rounded-xl bg-magentaAccent/15 text-magentaAccent mr-5">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Investments Today</p>
                <p class="text-2xl font-bold text-white mt-1">₹{{ number_format($todayInvestments, 2) }}</p>
            </div>
        </div>

        <!-- Today Commissions -->
        <div class="glass-card rounded-2xl p-6 flex items-center">
            <div class="p-4 rounded-xl bg-[#9C27B0]/15 text-[#9C27B0] mr-5">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M12 16v1"/></svg>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Commissions Today</p>
                <p class="text-2xl font-bold text-green-400 mt-1">₹{{ number_format($todayCommissions, 2) }}</p>
            </div>
        </div>

        <!-- Today Withdrawn -->
        <div class="glass-card rounded-2xl p-6 flex items-center">
            <div class="p-4 rounded-xl bg-orangeAccent/15 text-orangeAccent mr-5">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Withdrawn Today</p>
                <p class="text-2xl font-bold text-white mt-1">₹{{ number_format($todayWithdrawn, 2) }}</p>
            </div>
        </div>

        <!-- Today New Users -->
        <div class="glass-card rounded-2xl p-6 flex items-center">
            <div class="p-4 rounded-xl bg-[#00BCD4]/15 text-[#00BCD4] mr-5">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">New Users Today</p>
                <p class="text-2xl font-bold text-[#00BCD4] mt-1">{{ number_format($todayUsers) }}</p>
            </div>
        </div>
    </div>
</div>

<!-- Total Statistics Row -->
<div class="space-y-4">
    <h3 class="text-sm font-bold uppercase tracking-wider text-gray-400">Total Statistics</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Total Investments -->
        <div class="glass-card rounded-2xl p-6 flex items-center">
            <div class="p-4 rounded-xl bg-magentaAccent/15 text-magentaAccent mr-5">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Total Investments</p>
                <p class="text-2xl font-bold text-white mt-1">₹{{ number_format($totalInvestments, 2) }}</p>
            </div>
        </div>

        <!-- Total Commissions -->
        <div class="glass-card rounded-2xl p-6 flex items-center">
            <div class="p-4 rounded-xl bg-[#9C27B0]/15 text-[#9C27B0] mr-5">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M12 16v1"/></svg>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Commissions Paid</p>
                <p class="text-2xl font-bold text-green-400 mt-1">₹{{ number_format($totalCommissions, 2) }}</p>
            </div>
        </div>

        <!-- Total Withdrawn -->
        <div class="glass-card rounded-2xl p-6 flex items-center">
            <div class="p-4 rounded-xl bg-orangeAccent/15 text-orangeAccent mr-5">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Total Withdrawn</p>
                <p class="text-2xl font-bold text-white mt-1">₹{{ number_format($totalWithdrawn, 2) }}</p>
            </div>
        </div>

        <!-- Total Users -->
        <div class="glass-card rounded-2xl p-6 flex items-center">
            <div class="p-4 rounded-xl bg-[#FFC107]/15 text-[#FFC107] mr-5">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Total Users</p>
                <p class="text-2xl font-bold text-[#FFC107] mt-1">{{ number_format($totalUsers) }}</p>
            </div>
        </div>
    </div>
</div>

<!-- Bottom Section: Transactions & Users -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 pt-6">
    <!-- Recent Transactions -->
    <div class="glass-card rounded-2xl p-6 lg:col-span-2 space-y-4">
        <h3 class="text-lg font-bold text-gray-200">Recent Transactions</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-white/10 text-xs text-gray-400 uppercase tracking-wider">
                        <th class="pb-3 font-semibold">User</th>
                        <th class="pb-3 font-semibold">Type</th>
                        <th class="pb-3 font-semibold">Amount</th>
                        <th class="pb-3 font-semibold">Description</th>
                        <th class="pb-3 font-semibold">Date</th>
                    </tr>
                </thead>
                <tbody class="text-sm divide-y divide-white/5">
                    @forelse($recentTransactions as $tx)
                        <tr>
                            <td class="py-3">
                                <span class="font-medium block text-white">{{ $tx->user->name ?? 'User' }}</span>
                                <span class="text-xs text-gray-400">{{ $tx->user->mobile }}</span>
                            </td>
                            <td class="py-3 capitalize">
                                <span class="px-2 py-1 text-xs rounded-full 
                                    @if($tx->type === 'investment') bg-magentaAccent/20 text-[#C2185B] 
                                    @elseif($tx->type === 'withdrawal') bg-orangeAccent/20 text-[#FF9800] 
                                    @else bg-green-500/20 text-green-400 @endif">
                                    {{ $tx->type }}
                                </span>
                            </td>
                            <td class="py-3 font-bold 
                                @if($tx->type === 'withdrawal' || $tx->type === 'investment') text-white @else text-green-400 @endif">
                                {{ $tx->type === 'withdrawal' || $tx->type === 'investment' ? '-' : '+' }}₹{{ number_format($tx->amount, 2) }}
                            </td>
                            <td class="py-3 text-gray-300 max-w-[200px] truncate" title="{{ $tx->description }}">{{ $tx->description }}</td>
                            <td class="py-3 text-gray-400 text-xs">{{ $tx->created_at->format('M d, H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-4 text-center text-gray-500">No transaction records found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Users -->
    <div class="glass-card rounded-2xl p-6 space-y-4">
        <h3 class="text-lg font-bold text-gray-200">Recent Users</h3>
        <div class="space-y-4 max-h-[400px] overflow-y-auto pr-1">
            @forelse($recentUsers as $user)
                <div class="flex items-center justify-between border-b border-white/5 pb-3 last:border-b-0 last:pb-0">
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-full bg-purpleGradStart border border-[#FFC107] flex items-center justify-center font-bold text-[#FFC107]">
                            {{ substr($user->name ?? 'U', 0, 1) }}
                        </div>
                        <div class="ml-3">
                            <h4 class="text-sm font-semibold text-white">{{ $user->name ?? 'User' }}</h4>
                            <p class="text-xs text-gray-400">{{ $user->mobile }}</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="text-xs text-[#FFC107] block">ID: {{ $user->referral_code ?? 'None' }}</span>
                        <span class="text-[10px] text-gray-500">{{ $user->created_at->diffForHumans() }}</span>
                    </div>
                </div>
            @empty
                <p class="text-center text-gray-500 py-8">No registered users yet.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
