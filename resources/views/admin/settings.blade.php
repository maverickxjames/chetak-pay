@extends('admin.layout')

@section('page_title', 'System Configurations')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Tab navigation -->
    <div class="flex border-b border-white/10 bg-white/5 p-1.5 rounded-xl gap-2 overflow-x-auto no-scrollbar scroll-smooth">
        <button type="button" onclick="switchTab('gateway')" id="tab-btn-gateway" class="flex-shrink-0 sm:flex-1 py-2.5 px-4 sm:px-0 text-sm font-semibold rounded-lg text-white bg-gradient-to-r from-[#C2185B] to-[#4B006E] transition-all whitespace-nowrap">
            Payment Gateways
        </button>
        <button type="button" onclick="switchTab('general')" id="tab-btn-general" class="flex-shrink-0 sm:flex-1 py-2.5 px-4 sm:px-0 text-sm font-semibold rounded-lg text-gray-400 hover:text-white transition-all whitespace-nowrap">
            Website & General
        </button>
        <button type="button" onclick="switchTab('legal')" id="tab-btn-legal" class="flex-shrink-0 sm:flex-1 py-2.5 px-4 sm:px-0 text-sm font-semibold rounded-lg text-gray-400 hover:text-white transition-all whitespace-nowrap">
            Legal Pages
        </button>
        <button type="button" onclick="switchTab('app')" id="tab-btn-app" class="flex-shrink-0 sm:flex-1 py-2.5 px-4 sm:px-0 text-sm font-semibold rounded-lg text-gray-400 hover:text-white transition-all whitespace-nowrap">
            App Release & Maintenance
        </button>
    </div>

    <!-- Configuration Form -->
    <form action="{{ route('admin.settings.save') }}" method="POST" class="space-y-6">
        @csrf
        
        <!-- Tab 1: Payment Gateways -->
        <div id="tab-content-gateway" class="glass-card rounded-2xl p-6 space-y-6">
            <h3 class="text-lg font-bold text-white mb-4">Payment Settings</h3>
            
            <div>
                <label for="active_gateway" class="block text-xs font-semibold uppercase tracking-wider text-gray-400 mb-2">Active Payment Gateway</label>
                <select name="active_gateway" id="active_gateway" required
                        class="w-full bg-[#1C002C] border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-[#FFC107] focus:ring-1 focus:ring-[#FFC107] transition-all">
                    <option value="manual_qr" {{ $settings['active_gateway'] === 'manual_qr' ? 'selected' : '' }}>Manual QR Code (UTR Verification)</option>
                    <option value="upi_qr" {{ $settings['active_gateway'] === 'upi_qr' ? 'selected' : '' }}>UPI QR Intent / Direct Payout</option>
                    <option value="razorpay" {{ $settings['active_gateway'] === 'razorpay' ? 'selected' : '' }}>Razorpay Gateway Integration</option>
                    <option value="cashfree" {{ $settings['active_gateway'] === 'cashfree' ? 'selected' : '' }}>Cashfree Gateway Integration</option>
                </select>
                <p class="text-xs text-gray-500 mt-2">Specify which checkout flow is exposed to the Android mobile app.</p>
            </div>

            <hr class="border-white/10">

            <!-- UPI ID & Name -->
            <div class="space-y-4">
                <h4 class="text-sm font-bold text-[#FFC107]">UPI / QR Payout Credentials</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="upi_id" class="block text-xs font-semibold uppercase tracking-wider text-gray-400 mb-2">Merchant UPI ID</label>
                        <input type="text" name="upi_id" id="upi_id" value="{{ $settings['upi_id'] }}" placeholder="merchant@okaxis"
                               class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-[#FFC107] focus:ring-1 focus:ring-[#FFC107] transition-all">
                    </div>
                    <div>
                        <label for="upi_name" class="block text-xs font-semibold uppercase tracking-wider text-gray-400 mb-2">Merchant Name</label>
                        <input type="text" name="upi_name" id="upi_name" value="{{ $settings['upi_name'] }}" placeholder="Chetak Pay Admin"
                               class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-[#FFC107] focus:ring-1 focus:ring-[#FFC107] transition-all">
                    </div>
                </div>
            </div>

            <hr class="border-white/10">

            <!-- Razorpay -->
            <div class="space-y-4">
                <h4 class="text-sm font-bold text-[#C2185B]">Razorpay Credentials</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="razorpay_key_id" class="block text-xs font-semibold uppercase tracking-wider text-gray-400 mb-2">Razorpay Key ID</label>
                        <input type="text" name="razorpay_key_id" id="razorpay_key_id" value="{{ $settings['razorpay_key_id'] }}" placeholder="rzp_test_..."
                               class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-[#FFC107] focus:ring-1 focus:ring-[#FFC107] transition-all">
                    </div>
                    <div>
                        <label for="razorpay_key_secret" class="block text-xs font-semibold uppercase tracking-wider text-gray-400 mb-2">Razorpay Key Secret</label>
                        <input type="password" name="razorpay_key_secret" id="razorpay_key_secret" value="{{ $settings['razorpay_key_secret'] }}" placeholder="••••••••"
                               class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-[#FFC107] focus:ring-1 focus:ring-[#FFC107] transition-all">
                    </div>
                </div>
            </div>

            <hr class="border-white/10">

            <!-- Cashfree -->
            <div class="space-y-4">
                <h4 class="text-sm font-bold text-blue-400">Cashfree Credentials</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="cashfree_app_id" class="block text-xs font-semibold uppercase tracking-wider text-gray-400 mb-2">Cashfree App ID</label>
                        <input type="text" name="cashfree_app_id" id="cashfree_app_id" value="{{ $settings['cashfree_app_id'] }}" placeholder="cf_app_..."
                               class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-[#FFC107] focus:ring-1 focus:ring-[#FFC107] transition-all">
                    </div>
                    <div>
                        <label for="cashfree_secret_key" class="block text-xs font-semibold uppercase tracking-wider text-gray-400 mb-2">Cashfree Secret Key</label>
                        <input type="password" name="cashfree_secret_key" id="cashfree_secret_key" value="{{ $settings['cashfree_secret_key'] }}" placeholder="••••••••"
                               class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-[#FFC107] focus:ring-1 focus:ring-[#FFC107] transition-all">
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab 2: Website & General Config -->
        <div id="tab-content-general" class="glass-card rounded-2xl p-6 space-y-6 hidden">
            <h3 class="text-lg font-bold text-white mb-4">Branding & Features</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="website_name" class="block text-xs font-semibold uppercase tracking-wider text-gray-400 mb-2">Website / App Name</label>
                    <input type="text" name="website_name" id="website_name" required value="{{ $settings['website_name'] }}"
                           class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-[#FFC107] focus:ring-1 focus:ring-[#FFC107] transition-all">
                </div>
                <div>
                    <label for="website_logo" class="block text-xs font-semibold uppercase tracking-wider text-gray-400 mb-2">Website Logo URL</label>
                    <input type="url" name="website_logo" id="website_logo" value="{{ $settings['website_logo'] }}" placeholder="https://domain.com/logo.png"
                           class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-[#FFC107] focus:ring-1 focus:ring-[#FFC107] transition-all">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label for="support_contact" class="block text-xs font-semibold uppercase tracking-wider text-gray-400 mb-2">Support Contact (Email/URL)</label>
                    <input type="text" name="support_contact" id="support_contact" required value="{{ $settings['support_contact'] }}"
                           class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-[#FFC107] focus:ring-1 focus:ring-[#FFC107] transition-all">
                </div>
                <div>
                    <label for="otp_api_key" class="block text-xs font-semibold uppercase tracking-wider text-gray-400 mb-2">OTP SMS Gateway API Key</label>
                    <input type="text" name="otp_api_key" id="otp_api_key" value="{{ $settings['otp_api_key'] }}" placeholder="SMS provider key"
                           class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-[#FFC107] focus:ring-1 focus:ring-[#FFC107] transition-all">
                </div>
                <div>
                    <label for="commission_percentage" class="block text-xs font-semibold uppercase tracking-wider text-gray-400 mb-2">Master Commission Rate (%)</label>
                    <input type="number" step="0.1" min="0" max="100" name="commission_percentage" id="commission_percentage" required value="{{ $settings['commission_percentage'] }}" placeholder="3.8"
                           class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-[#FFC107] focus:ring-1 focus:ring-[#FFC107] transition-all">
                </div>
            </div>

            <hr class="border-white/10">

            <div class="space-y-4">
                <h4 class="text-sm font-bold text-[#FFC107]">Toggle Modules & Features</h4>
                <div class="flex flex-col gap-3">
                    <label class="flex items-center text-sm font-medium cursor-pointer text-white">
                        <input type="checkbox" name="feature_referrals" value="1" {{ $settings['feature_referrals'] === '1' ? 'checked' : '' }} class="mr-3 h-4 w-4 rounded bg-white/5 border-white/10 accent-[#C2185B]">
                        Enable Referrals System (10% parent investment bonus)
                    </label>
                    <label class="flex items-center text-sm font-medium cursor-pointer text-white">
                        <input type="checkbox" name="feature_rewards" value="1" {{ $settings['feature_rewards'] === '1' ? 'checked' : '' }} class="mr-3 h-4 w-4 rounded bg-white/5 border-white/10 accent-[#C2185B]">
                        Enable Rewards Claiming Tab (Milestones & Daily Check-ins)
                    </label>
                </div>
            </div>
        </div>

        <!-- Tab 3: Legal Pages Content -->
        <div id="tab-content-legal" class="glass-card rounded-2xl p-6 space-y-6 hidden">
            <h3 class="text-lg font-bold text-white mb-4">Legal Documents</h3>
            
            <div class="space-y-4">
                <div>
                    <label for="about_us" class="block text-xs font-semibold uppercase tracking-wider text-gray-400 mb-2">About Us Content</label>
                    <textarea name="about_us" id="about_us" rows="4" placeholder="Brief description of the platform..."
                              class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-[#FFC107] focus:ring-1 focus:ring-[#FFC107] transition-all">{{ $settings['about_us'] }}</textarea>
                </div>

                <div>
                    <label for="privacy_policy" class="block text-xs font-semibold uppercase tracking-wider text-gray-400 mb-2">Privacy Policy</label>
                    <textarea name="privacy_policy" id="privacy_policy" rows="6" placeholder="Terms detailing how user details are handled..."
                              class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-[#FFC107] focus:ring-1 focus:ring-[#FFC107] transition-all">{{ $settings['privacy_policy'] }}</textarea>
                </div>

                <div>
                    <label for="terms_conditions" class="block text-xs font-semibold uppercase tracking-wider text-gray-400 mb-2">Terms & Conditions</label>
                    <textarea name="terms_conditions" id="terms_conditions" rows="6" placeholder="Platform investment agreement terms..."
                              class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-[#FFC107] focus:ring-1 focus:ring-[#FFC107] transition-all">{{ $settings['terms_conditions'] }}</textarea>
                </div>
            </div>
        </div>

        <!-- Tab 4: App Releases & Maintenance -->
        <div id="tab-content-app" class="glass-card rounded-2xl p-6 space-y-6 hidden">
            <h3 class="text-lg font-bold text-white mb-4">Maintenance & Release Controls</h3>
            
            <div class="space-y-4">
                <h4 class="text-sm font-bold text-[#FFC107]">System Maintenance Mode</h4>
                <div class="flex items-center gap-3">
                    <label class="flex items-center text-sm font-medium cursor-pointer text-white">
                        <input type="checkbox" name="maintenance_mode" value="1" {{ $settings['maintenance_mode'] === '1' ? 'checked' : '' }} class="mr-3 h-4 w-4 rounded bg-white/5 border-white/10 accent-[#C2185B]">
                        Lock App into Maintenance Mode
                    </label>
                </div>
                <div>
                    <label for="maintenance_message" class="block text-xs font-semibold uppercase tracking-wider text-gray-400 mb-2">Maintenance Blocker Screen Message</label>
                    <input type="text" name="maintenance_message" id="maintenance_message" value="{{ $settings['maintenance_message'] }}"
                           class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-[#FFC107] focus:ring-1 focus:ring-[#FFC107] transition-all">
                </div>
            </div>

            <hr class="border-white/10">

            <div class="space-y-4">
                <h4 class="text-sm font-bold text-blue-400">Android Application Pushes</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="app_version" class="block text-xs font-semibold uppercase tracking-wider text-gray-400 mb-2">Required App Version</label>
                        <input type="text" name="app_version" id="app_version" required value="{{ $settings['app_version'] }}" placeholder="1.0.0"
                               class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-[#FFC107] focus:ring-1 focus:ring-[#FFC107] transition-all">
                    </div>
                    <div>
                        <label for="app_update_url" class="block text-xs font-semibold uppercase tracking-wider text-gray-400 mb-2">APK/Google Play Update Link</label>
                        <input type="url" name="app_update_url" id="app_update_url" value="{{ $settings['app_update_url'] }}" placeholder="https://play.google.com/..."
                               class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-[#FFC107] focus:ring-1 focus:ring-[#FFC107] transition-all">
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <label class="flex items-center text-sm font-medium cursor-pointer text-white">
                        <input type="checkbox" name="app_force_update" value="1" {{ $settings['app_force_update'] === '1' ? 'checked' : '' }} class="mr-3 h-4 w-4 rounded bg-white/5 border-white/10 accent-[#C2185B]">
                        Force Update (Block access to older app versions)
                    </label>
                </div>
            </div>
        </div>

        <!-- Submit Panel -->
        <div class="pt-4">
            <button type="submit"
                    class="w-full bg-gradient-to-r from-[#C2185B] to-[#4B006E] hover:from-[#d31c62] hover:to-[#5c0287] text-[#FFC107] font-bold py-3.5 px-4 rounded-xl border border-[#FFC107]/20 shadow-lg hover:shadow-xl hover:scale-[1.01] transition-all">
                Save System Configuration Settings
            </button>
        </div>
    </form>
</div>

<script>
    function switchTab(tabName) {
        // Hide all tabs content
        document.getElementById('tab-content-gateway').classList.add('hidden');
        document.getElementById('tab-content-general').classList.add('hidden');
        document.getElementById('tab-content-legal').classList.add('hidden');
        document.getElementById('tab-content-app').classList.add('hidden');

        // Deactivate all buttons styling
        const buttons = ['gateway', 'general', 'legal', 'app'];
        buttons.forEach(btn => {
            const el = document.getElementById('tab-btn-' + btn);
            el.className = "flex-shrink-0 sm:flex-1 py-2.5 px-4 sm:px-0 text-sm font-semibold rounded-lg text-gray-400 hover:text-white transition-all whitespace-nowrap";
        });

        // Show selected tab content
        document.getElementById('tab-content-' + tabName).classList.remove('hidden');

        // Activate selected button styling
        const activeBtn = document.getElementById('tab-btn-' + tabName);
        activeBtn.className = "flex-shrink-0 sm:flex-1 py-2.5 px-4 sm:px-0 text-sm font-semibold rounded-lg text-white bg-gradient-to-r from-[#C2185B] to-[#4B006E] transition-all whitespace-nowrap";
    }
</script>
@endsection
