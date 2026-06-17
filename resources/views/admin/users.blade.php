@extends('admin.layout')

@section('page_title', 'Users Management')

@section('content')
<!-- Search & Filter Controls -->
<div class="flex flex-col md:flex-row justify-between items-center gap-4 bg-white/5 border border-white/10 rounded-2xl p-4">
    <form action="{{ route('admin.users') }}" method="GET" class="w-full md:w-96 flex items-center">
        <input type="text" name="search" placeholder="Search by name, mobile, or email..." value="{{ request('search') }}"
               class="w-full bg-white/5 border border-white/10 rounded-l-xl px-4 py-2.5 text-white placeholder-gray-500 focus:outline-none focus:border-[#FFC107] focus:ring-1 focus:ring-[#FFC107] transition-all">
        <button type="submit" class="bg-[#C2185B] text-white px-5 py-2.5 rounded-r-xl border border-l-0 border-[#C2185B] font-semibold hover:bg-[#d31c62] transition-colors">
            Search
        </button>
    </form>
    
    @if(request('search'))
        <a href="{{ route('admin.users') }}" class="text-sm text-gray-400 hover:text-white transition-colors">
            Clear Search Filter
        </a>
    @endif
</div>

<!-- Users List -->
<div class="glass-card rounded-2xl p-6">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b border-white/10 text-xs text-gray-400 uppercase tracking-wider">
                    <th class="pb-4 font-semibold">User Details</th>
                    <th class="pb-4 font-semibold text-center">Referral Code</th>
                    <th class="pb-4 font-semibold">Wallet Balance</th>
                    <th class="pb-4 font-semibold">Investment</th>
                    <th class="pb-4 font-semibold">Commission</th>
                    <th class="pb-4 font-semibold">Withdrawn</th>
                    <th class="pb-4 font-semibold text-center">Status</th>
                    <th class="pb-4 font-semibold text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="text-sm divide-y divide-white/5">
                @forelse($users as $u)
                    <tr>
                        <td class="py-4">
                            <span class="font-bold text-white block">{{ $u->name ?? 'User' }}</span>
                            <span class="text-xs text-gray-400 block">{{ $u->mobile }}</span>
                            <span class="text-xs text-gray-400 block">{{ $u->email }}</span>
                            @if($u->upi_id || $u->account_number)
                                <div class="mt-1.5 pt-1.5 border-t border-white/5 space-y-0.5">
                                    @if($u->upi_id)
                                        <span class="text-[10px] text-yellow-400 block font-mono">UPI: {{ $u->upi_id }}</span>
                                    @endif
                                    @if($u->account_number)
                                        <span class="text-[10px] text-blue-400 block font-mono">Bank: {{ $u->bank_name }} ({{ $u->account_holder_name }})</span>
                                        <span class="text-[10px] text-blue-400 block font-mono">A/C: {{ $u->account_number }} | IFSC: {{ $u->ifsc_code }}</span>
                                    @endif
                                </div>
                            @endif
                        </td>
                        <td class="py-4 text-center">
                            <span class="px-2.5 py-1 text-xs rounded bg-purpleGradStart border border-[#FFC107]/20 text-[#FFC107] font-bold">
                                {{ $u->referral_code ?? '—' }}
                            </span>
                        </td>
                        <td class="py-4 font-bold text-green-400">₹{{ number_format($u->wallet_balance, 2) }}</td>
                        <td class="py-4 text-white">₹{{ number_format($u->total_investment, 2) }}</td>
                        <td class="py-4 text-gray-300">₹{{ number_format($u->total_commission, 2) }}</td>
                        <td class="py-4 text-gray-300">₹{{ number_format($u->total_withdrawn, 2) }}</td>
                        <td class="py-4 text-center">
                            <span class="px-2 py-0.5 text-xs rounded font-semibold {{ $u->is_blocked ? 'bg-red-500/20 text-red-400' : 'bg-green-500/20 text-green-400' }}">
                                {{ $u->is_blocked ? 'Blocked' : 'Active' }}
                            </span>
                        </td>
                        <td class="py-4 text-right space-x-1 space-y-1">
                            <button onclick="openAdjustmentModal({{ $u->id }}, '{{ $u->name ?? $u->mobile }}', '{{ $u->wallet_balance }}')" 
                                    class="px-3 py-1.5 text-xs font-semibold bg-[#FFC107] text-black hover:bg-[#ffd54f] rounded-lg transition-colors">
                                Adjust
                            </button>
                            <button onclick="openWithdrawModal({{ $u->id }}, '{{ $u->name ?? $u->mobile }}', '{{ $u->wallet_balance }}', '{{ $u->upi_id }}', '{{ $u->bank_name }}', '{{ $u->account_number }}', '{{ $u->ifsc_code }}', '{{ $u->account_holder_name }}')" 
                                    class="px-3 py-1.5 text-xs font-semibold bg-orangeAccent text-white hover:bg-orange-600 rounded-lg transition-colors">
                                Withdraw
                            </button>
                            <form action="{{ route('admin.users.toggle', $u->id) }}" method="POST" class="inline-block">
                                @csrf
                                <button type="submit" class="px-3 py-1.5 text-xs font-semibold border {{ $u->is_blocked ? 'border-green-500/30 text-green-400 hover:bg-green-500/10' : 'border-red-500/30 text-red-400 hover:bg-red-500/10' }} rounded-lg transition-colors">
                                    {{ $u->is_blocked ? 'Unblock' : 'Block' }}
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="py-8 text-center text-gray-500">No users found matching search credentials.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="mt-6">
        {{ $users->appends(request()->input())->links() }}
    </div>
</div>

<!-- Adjust Modal Dialog -->
<div id="adjustModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 hidden backdrop-blur-sm">
    <div class="glass-card rounded-2xl w-full max-w-md mx-4 p-6 relative">
        <h3 class="text-lg font-bold text-white mb-4">Adjust User Balance</h3>
        <p class="text-xs text-gray-400 mb-6">User: <span id="modalUsername" class="text-white font-semibold"></span> | Current Balance: ₹<span id="modalBalance" class="text-green-400 font-bold"></span></p>
        
        <form id="adjustForm" method="POST" action="" class="space-y-4">
            @csrf
            <!-- Type Toggle -->
            <div>
                <label class="block text-xs font-semibold uppercase text-gray-400 mb-2">Adjustment Type</label>
                <div class="flex gap-4">
                    <label class="flex items-center text-sm font-medium cursor-pointer text-white">
                        <input type="radio" name="type" value="credit" checked class="mr-2 accent-[#C2185B]">
                        Credit (Add Funds)
                    </label>
                    <label class="flex items-center text-sm font-medium cursor-pointer text-white">
                        <input type="radio" name="type" value="debit" class="mr-2 accent-[#C2185B]">
                        Debit (Deduct Funds)
                    </label>
                </div>
            </div>

            <!-- Amount Input -->
            <div>
                <label class="block text-xs font-semibold uppercase text-gray-400 mb-2">Amount (₹)</label>
                <input type="number" step="0.01" min="0.01" name="amount" required placeholder="0.00"
                       class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-[#FFC107] focus:ring-1 focus:ring-[#FFC107] transition-all">
            </div>

            <!-- Description -->
            <div>
                <label class="block text-xs font-semibold uppercase text-gray-400 mb-2">Transaction Description</label>
                <input type="text" name="description" required placeholder="Reason (e.g. Welcome bonus adjustment)"
                       class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-[#FFC107] focus:ring-1 focus:ring-[#FFC107] transition-all">
            </div>

            <div class="flex justify-end gap-3 mt-6">
                <button type="button" onclick="closeAdjustmentModal()" class="px-5 py-2.5 rounded-xl border border-white/10 text-gray-300 hover:text-white transition-colors text-sm">
                    Cancel
                </button>
                <button type="submit" class="bg-[#C2185B] hover:bg-[#d31c62] text-white px-5 py-2.5 rounded-xl font-bold transition-colors text-sm">
                    Confirm Adjustment
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Custom Withdraw Modal Dialog -->
<div id="withdrawModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 hidden backdrop-blur-sm">
    <div class="glass-card rounded-2xl w-full max-w-md mx-4 p-6 relative">
        <h3 class="text-lg font-bold text-white mb-4">Custom Admin Withdrawal</h3>
        <p class="text-xs text-gray-400 mb-6">User: <span id="withdrawModalUsername" class="text-white font-semibold"></span> | Available Balance: ₹<span id="withdrawModalBalance" class="text-green-400 font-bold"></span></p>
        
        <form id="withdrawForm" method="POST" action="" class="space-y-4">
            @csrf
            <!-- Payout Details Display -->
            <div class="mb-4">
                <label class="block text-xs font-semibold uppercase text-gray-400 mb-2">User Payout Details</label>
                <div id="withdrawPayoutDetails"></div>
            </div>

            <!-- Amount Input -->
            <div>
                <label class="block text-xs font-semibold uppercase text-gray-400 mb-2">Withdrawal Amount (₹)</label>
                <input type="number" step="0.01" min="0.01" name="amount" id="withdrawAmount" required placeholder="0.00"
                       class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-[#FFC107] focus:ring-1 focus:ring-[#FFC107] transition-all">
            </div>

            <!-- Message Description -->
            <div>
                <label class="block text-xs font-semibold uppercase text-gray-400 mb-2">Withdrawal Message / Reason (UTR Reference)</label>
                <input type="text" name="message" required placeholder="Reason (e.g. UTR 987654321012 via Bank Transfer)"
                       class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-[#FFC107] focus:ring-1 focus:ring-[#FFC107] transition-all">
            </div>

            <div class="flex justify-end gap-3 mt-6">
                <button type="button" onclick="closeWithdrawModal()" class="px-5 py-2.5 rounded-xl border border-white/10 text-gray-300 hover:text-white transition-colors text-sm">
                    Cancel
                </button>
                <button type="submit" class="bg-orangeAccent hover:bg-orange-600 text-white px-5 py-2.5 rounded-xl font-bold transition-colors text-sm">
                    Process Payout
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openAdjustmentModal(userId, name, currentBalance) {
        document.getElementById('modalUsername').innerText = name;
        document.getElementById('modalBalance').innerText = parseFloat(currentBalance).toFixed(2);
        document.getElementById('adjustForm').action = "/admin/users/" + userId + "/adjust-balance";
        document.getElementById('adjustModal').classList.remove('hidden');
    }

    function closeAdjustmentModal() {
        document.getElementById('adjustModal').classList.add('hidden');
    }

    function openWithdrawModal(userId, name, currentBalance, upi, bankName, acct, ifsc, holder) {
        document.getElementById('withdrawModalUsername').innerText = name;
        document.getElementById('withdrawModalBalance').innerText = parseFloat(currentBalance).toFixed(2);
        document.getElementById('withdrawAmount').max = parseFloat(currentBalance);
        document.getElementById('withdrawForm').action = "/admin/users/" + userId + "/admin-withdraw";

        // Populate payout details in modal
        let detailsHtml = "";
        if (upi) {
            detailsHtml += `<div class="p-2.5 bg-yellow-500/10 border border-yellow-500/20 rounded-xl text-yellow-300 text-xs font-mono mb-2"><strong>UPI ID:</strong> ${upi}</div>`;
        }
        if (acct) {
            detailsHtml += `<div class="p-2.5 bg-blue-500/10 border border-blue-500/20 rounded-xl text-blue-300 text-xs font-mono">
                <strong>Bank Name:</strong> ${bankName}<br>
                <strong>Holder:</strong> ${holder}<br>
                <strong>A/C Number:</strong> ${acct}<br>
                <strong>IFSC Code:</strong> ${ifsc}
            </div>`;
        }
        if (!upi && !acct) {
            detailsHtml = `<div class="p-2.5 bg-red-500/10 border border-red-500/20 rounded-xl text-red-400 text-xs">No payout details added by user.</div>`;
        }
        document.getElementById('withdrawPayoutDetails').innerHTML = detailsHtml;

        document.getElementById('withdrawModal').classList.remove('hidden');
    }

    function closeWithdrawModal() {
        document.getElementById('withdrawModal').classList.add('hidden');
    }
</script>
@endsection
