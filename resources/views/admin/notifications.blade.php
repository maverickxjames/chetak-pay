@extends('admin.layout')

@section('page_title', 'Global Announcements')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Broadcast Form -->
    <div class="glass-card rounded-2xl p-6 h-fit space-y-4">
        <h3 class="text-lg font-bold text-white">Broadcast New Notice</h3>
        <form action="{{ route('admin.notifications.store') }}" method="POST" class="space-y-4">
            @csrf
            <!-- Title -->
            <div>
                <label for="title" class="block text-xs font-semibold uppercase text-gray-400 mb-2">Notification Title</label>
                <input type="text" name="title" id="title" required placeholder="e.g. Server Maintenance Notice"
                       class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-[#FFC107] focus:ring-1 focus:ring-[#FFC107] transition-all">
            </div>

            <!-- Audience -->
            <div>
                <label for="audience" class="block text-xs font-semibold uppercase text-gray-400 mb-2">Target Audience</label>
                <select name="audience" id="audience" onchange="toggleAudienceFields()"
                        class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-[#FFC107] focus:ring-1 focus:ring-[#FFC107] transition-all">
                    <option value="all" class="bg-[#2b003f] text-white">Broadcast to All (Public)</option>
                    <option value="specific" class="bg-[#2b003f] text-white">Send to Specific User</option>
                </select>
            </div>

            <!-- Mobile Field (hidden by default) -->
            <div id="mobile-field-group" class="hidden">
                <label for="mobile" class="block text-xs font-semibold uppercase text-gray-400 mb-2">User Mobile Number</label>
                <input type="text" name="mobile" id="mobile" placeholder="e.g. 9876543210"
                       class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-[#FFC107] focus:ring-1 focus:ring-[#FFC107] transition-all">
            </div>

            <!-- Content -->
            <div>
                <label for="content" class="block text-xs font-semibold uppercase text-gray-400 mb-2">Notice Message (Content)</label>
                <textarea name="content" id="content" required placeholder="Write the announcement message content..." rows="4"
                       class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-[#FFC107] focus:ring-1 focus:ring-[#FFC107] transition-all"></textarea>
            </div>

            <!-- Firebase FCM Toggle -->
            <div class="flex items-center gap-3">
                <label class="flex items-center text-sm font-medium cursor-pointer text-white">
                    <input type="checkbox" name="send_push" value="1" checked class="mr-3 h-4 w-4 rounded bg-white/5 border-white/10 accent-[#C2185B]">
                    Send Push Notification (via Firebase FCM)
                </label>
            </div>

            <button type="submit" class="w-full bg-[#C2185B] hover:bg-[#d31c62] text-white py-3.5 rounded-xl font-bold transition-all border border-[#FFC107]/10 shadow-lg">
                Send Notification
            </button>
        </form>
    </div>

    <!-- Announcement History -->
    <div class="glass-card rounded-2xl p-6 lg:col-span-2 space-y-4">
        <h3 class="text-lg font-bold text-white">Previous Broadcasts</h3>
        
        <div class="space-y-4 max-h-[500px] overflow-y-auto pr-1">
            @forelse($notifications as $notif)
                <div class="p-4 rounded-xl bg-white/5 border border-white/10 space-y-2">
                    <div class="flex justify-between items-start">
                        <h4 class="font-bold text-[#FFC107]">{{ $notif->title }}</h4>
                        <span class="text-[10px] text-gray-400">{{ $notif->created_at->format('M d Y, H:i') }}</span>
                    </div>
                    <p class="text-sm text-gray-300 leading-relaxed">{{ $notif->content }}</p>
                </div>
            @empty
                <p class="text-center text-gray-500 py-8">No announcements broadcasted yet.</p>
            @endforelse
        </div>
    </div>
</div>

<script>
    function toggleAudienceFields() {
        const aud = document.getElementById('audience').value;
        const mobileGroup = document.getElementById('mobile-field-group');
        const mobileInput = document.getElementById('mobile');
        if (aud === 'specific') {
            mobileGroup.classList.remove('hidden');
            mobileInput.required = true;
        } else {
            mobileGroup.classList.add('hidden');
            mobileInput.required = false;
            mobileInput.value = '';
        }
    }
</script>
@endsection
