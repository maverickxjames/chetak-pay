@extends('admin.layout')

@section('page_title', 'Admin Audit & Activity Logs')

@section('content')
<div class="glass-card rounded-2xl p-6">
    <div class="flex justify-between items-center mb-6">
        <h3 class="text-lg font-bold text-white">Security & Audit Trails</h3>
        <span class="text-xs text-gray-400">Total Entries Logged</span>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b border-white/10 text-xs text-gray-400 uppercase tracking-wider">
                    <th class="pb-4 font-semibold">Administrator</th>
                    <th class="pb-4 font-semibold">Action Performed</th>
                    <th class="pb-4 font-semibold">Details</th>
                    <th class="pb-4 font-semibold">IP Address</th>
                    <th class="pb-4 font-semibold text-right">Timestamp</th>
                </tr>
            </thead>
            <tbody class="text-sm divide-y divide-white/5">
                @forelse($logs as $log)
                    <tr>
                        <td class="py-4">
                            <span class="font-bold text-white block">{{ $log->admin->name ?? 'Deleted Admin' }}</span>
                            <span class="text-xs text-gray-400 block">{{ $log->admin->mobile ?? '—' }}</span>
                            <span class="px-1.5 py-0.5 text-[9px] rounded font-semibold {{ ($log->admin->role ?? '') === 'super_admin' ? 'bg-[#C2185B]/20 text-[#C2185B]' : 'bg-white/10 text-gray-300' }}">
                                {{ ($log->admin->role ?? '') === 'super_admin' ? 'Super Admin' : 'Admin' }}
                            </span>
                        </td>
                        <td class="py-4">
                            <span class="px-2.5 py-1 text-xs rounded bg-purple-500/20 text-purple-300 font-semibold uppercase">
                                {{ $log->action }}
                            </span>
                        </td>
                        <td class="py-4 text-gray-300 max-w-sm break-words" title="{{ $log->details }}">
                            {{ $log->details }}
                        </td>
                        <td class="py-4 font-mono text-xs text-gray-400">
                            {{ $log->ip_address ?? '—' }}
                        </td>
                        <td class="py-4 text-right text-xs text-gray-400">
                            {{ $log->created_at->format('Y-m-d H:i:s') }}
                            <span class="block text-[10px] text-gray-500">{{ $log->created_at->diffForHumans() }}</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-8 text-center text-gray-500">No admin actions have been logged yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="mt-6">
        {{ $logs->links() }}
    </div>
</div>
@endsection
