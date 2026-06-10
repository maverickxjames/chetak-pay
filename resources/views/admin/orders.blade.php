@extends('admin.layout')

@section('page_title', 'Orders Verification')

@section('content')
<!-- Filter Status Tabs -->
<div class="flex gap-2 border-b border-white/10 pb-px overflow-x-auto no-scrollbar scroll-smooth">
    @foreach(['all' => 'All Orders', 'pending' => 'Pending Verification', 'active' => 'Active Plans', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $key => $label)
        <a href="{{ route('admin.orders', ['status' => $key]) }}" 
           class="flex-shrink-0 px-5 py-3 text-sm font-semibold border-b-2 hover:text-[#FFC107] transition-all whitespace-nowrap
           {{ $status === $key ? 'border-[#C2185B] text-[#FFC107]' : 'border-transparent text-gray-400' }}">
            {{ $label }}
        </a>
    @endforeach
</div>

<!-- Orders Table -->
<div class="glass-card rounded-2xl p-6">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b border-white/10 text-xs text-gray-400 uppercase tracking-wider">
                    <th class="pb-4 font-semibold">Order ID</th>
                    <th class="pb-4 font-semibold">User Details</th>
                    <th class="pb-4 font-semibold">Investment Plan</th>
                    <th class="pb-4 font-semibold">Method</th>
                    <th class="pb-4 font-semibold">UTR / Tx ID</th>
                    <th class="pb-4 font-semibold">Status</th>
                    <th class="pb-4 font-semibold">Date</th>
                    <th class="pb-4 font-semibold text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="text-sm divide-y divide-white/5">
                @forelse($orders as $o)
                    <tr>
                        <td class="py-4 font-semibold text-white">{{ $o->id }}</td>
                        <td class="py-4">
                            <span class="text-white font-medium block">{{ $o->user->name ?? 'User' }}</span>
                            <span class="text-xs text-gray-400 block">{{ $o->user->mobile }}</span>
                        </td>
                        <td class="py-4">
                            <span class="text-white block font-medium">{{ $o->plan->name }}</span>
                            <span class="text-xs text-[#FFC107] block">₹{{ number_format($o->amount, 2) }}</span>
                        </td>
                        <td class="py-4 capitalize text-gray-300">
                            {{ str_replace('_', ' ', $o->payment_method) }}
                        </td>
                        <td class="py-4">
                            <span class="px-2 py-1 bg-white/5 border border-white/10 rounded font-mono text-xs text-[#FFC107]" title="UTR Verification code">
                                {{ $o->payment_txid ?? 'Not Submitted' }}
                            </span>
                        </td>
                        <td class="py-4 capitalize">
                            <span class="px-2.5 py-1 text-xs font-semibold rounded-full
                                @if($o->status === 'pending') bg-yellow-500/20 text-yellow-400
                                @elseif($o->status === 'active') bg-green-500/20 text-green-400
                                @elseif($o->status === 'completed') bg-blue-500/20 text-blue-400
                                @else bg-red-500/20 text-red-400 @endif">
                                {{ $o->status }}
                            </span>
                        </td>
                        <td class="py-4 text-gray-400 text-xs">
                            {{ $o->created_at->format('M d Y, H:i') }}
                        </td>
                        <td class="py-4 text-right">
                            @if($o->status === 'pending')
                                <div class="flex justify-end gap-2">
                                    <!-- Approve Form -->
                                    <form action="{{ route('admin.orders.approve', $o->id) }}" method="POST" onsubmit="return confirm('Verify deposit and approve this plan activation?');">
                                        @csrf
                                        <button type="submit" class="px-3 py-1.5 text-xs font-bold bg-green-500 text-black hover:bg-green-400 rounded-lg transition-all">
                                            Approve
                                        </button>
                                    </form>
                                    
                                    <!-- Cancel Form -->
                                    <form action="{{ route('admin.orders.cancel', $o->id) }}" method="POST" onsubmit="return confirm('Cancel this order and reject UTR?');">
                                        @csrf
                                        <button type="submit" class="px-3 py-1.5 text-xs font-semibold bg-red-500/20 text-red-400 border border-red-500/30 hover:bg-red-500/30 rounded-lg transition-all">
                                            Cancel
                                        </button>
                                    </form>
                                </div>
                            @else
                                <span class="text-xs text-gray-500">Processed</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="py-8 text-center text-gray-500">No orders found in this status category.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="mt-6">
        {{ $orders->appends(request()->input())->links() }}
    </div>
</div>
@endsection
