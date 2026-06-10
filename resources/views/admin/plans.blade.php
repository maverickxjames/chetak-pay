@extends('admin.layout')

@section('page_title', 'Investment Plans')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Add New Plan Form -->
    <div class="glass-card rounded-2xl p-6 h-fit space-y-4">
        <h3 class="text-lg font-bold text-white">Create New Plan</h3>
        <form action="{{ route('admin.plans.store') }}" method="POST" class="space-y-4">
            @csrf
            <!-- Plan Name -->
            <div>
                <label for="name" class="block text-xs font-semibold uppercase text-gray-400 mb-2">Plan Name</label>
                <input type="text" name="name" id="name" required placeholder="e.g. Starter Tier III"
                       class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-[#FFC107] focus:ring-1 focus:ring-[#FFC107] transition-all">
            </div>

            <!-- Amount -->
            <div>
                <label for="amount" class="block text-xs font-semibold uppercase text-gray-400 mb-2">Investment Amount (₹)</label>
                <input type="number" step="0.01" name="amount" id="amount" required placeholder="0.00"
                       class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-[#FFC107] focus:ring-1 focus:ring-[#FFC107] transition-all">
            </div>

            <!-- Category -->
            <div>
                <label for="category" class="block text-xs font-semibold uppercase text-gray-400 mb-2">Tier Category</label>
                <select name="category" id="category" required 
                        class="w-full bg-[#1C002C] border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-[#FFC107] focus:ring-1 focus:ring-[#FFC107] transition-all">
                    <option value="Starter">Starter</option>
                    <option value="Silver">Silver</option>
                    <option value="Gold">Gold</option>
                    <option value="VIP">VIP</option>
                    <option value="Top Picks">Top Picks</option>
                </select>
            </div>

            <button type="submit" class="w-full bg-[#C2185B] hover:bg-[#d31c62] text-white py-3.5 rounded-xl font-bold transition-all border border-[#FFC107]/10 shadow-lg">
                Create Investment Asset
            </button>
        </form>
    </div>

    <!-- Plans List -->
    <div class="glass-card rounded-2xl p-6 lg:col-span-2 space-y-4">
        <h3 class="text-lg font-bold text-white">Configured Investment Plans</h3>
        
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-white/10 text-xs text-gray-400 uppercase tracking-wider">
                        <th class="pb-3 font-semibold">Plan Name</th>
                        <th class="pb-3 font-semibold">Category</th>
                        <th class="pb-3 font-semibold">Amount</th>
                        <th class="pb-3 font-semibold text-center">Status</th>
                        <th class="pb-3 font-semibold text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="text-sm divide-y divide-white/5">
                    @forelse($plans as $p)
                        <tr>
                            <td class="py-3.5 font-semibold text-white">{{ $p->name }}</td>
                            <td class="py-3.5 text-gray-300">{{ $p->category }}</td>
                            <td class="py-3.5 font-bold text-[#FFC107]">₹{{ number_format($p->amount, 2) }}</td>
                            <td class="py-3.5 text-center">
                                <span class="px-2 py-0.5 text-xs font-semibold rounded-full 
                                    {{ $p->status === 'active' ? 'bg-green-500/20 text-green-400' : 'bg-red-500/20 text-red-400' }}">
                                    {{ $p->status }}
                                </span>
                            </td>
                            <td class="py-3.5 text-right">
                                <form action="{{ route('admin.plans.toggle', $p->id) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" 
                                            class="px-3 py-1 rounded text-xs font-semibold border transition-all
                                            {{ $p->status === 'active' ? 'border-red-500/30 text-red-400 hover:bg-red-500/10' : 'border-green-500/30 text-green-400 hover:bg-green-500/10' }}">
                                        {{ $p->status === 'active' ? 'Deactivate' : 'Activate' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-6 text-center text-gray-500">No investment plans configured.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
