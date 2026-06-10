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

            <!-- Content -->
            <div>
                <label for="content" class="block text-xs font-semibold uppercase text-gray-400 mb-2">Announcement Message</label>
                <textarea name="content" id="content" rows="6" required placeholder="Write details here..."
                          class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-[#FFC107] focus:ring-1 focus:ring-[#FFC107] transition-all"></textarea>
            </div>

            <button type="submit" class="w-full bg-[#C2185B] hover:bg-[#d31c62] text-white py-3.5 rounded-xl font-bold transition-all border border-[#FFC107]/10 shadow-lg">
                Broadcast Announcement
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
@endsection
