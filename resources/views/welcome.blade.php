<!DOCTYPE html>
<html lang="en" class="h-full bg-[#13001C]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $settings['website_name'] }} - Secure Payment & High-Speed Investment Portal</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Outfit', 'sans-serif'],
                    },
                    colors: {
                        darkPurple: '#13001C',
                        purpleGradStart: '#2B003F',
                        purpleGradEnd: '#4B006E',
                        magentaAccent: '#C2185B',
                        goldenYellow: '#FFC107',
                        orangeAccent: '#FF9800',
                    }
                }
            }
        }
    </script>
    
    <style>
        .glass-card {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 10px 30px 0 rgba(0, 0, 0, 0.3);
        }
        .phone-frame {
            box-shadow: 0 25px 60px -15px rgba(0,0,0,0.8), inset 0 0 15px rgba(255,255,255,0.1);
        }
        .text-glow {
            text-shadow: 0 0 20px rgba(194, 24, 91, 0.4);
        }
        .gold-glow {
            text-shadow: 0 0 15px rgba(255, 193, 7, 0.3);
        }
        /* Hide scrollbars */
        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }
        .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
    </style>
</head>
<body class="h-full text-white font-sans bg-gradient-to-br from-[#13001C] via-[#240035] to-[#36004E] flex flex-col justify-between overflow-x-hidden min-h-screen">
    <!-- Decorative Ambient Blobs -->
    <div class="absolute w-[400px] h-[400px] rounded-full bg-[#C2185B]/10 -top-20 -left-20 blur-[100px] pointer-events-none"></div>
    <div class="absolute w-[500px] h-[500px] rounded-full bg-[#4B006E]/15 top-1/2 -right-40 blur-[150px] pointer-events-none"></div>

    <!-- Header Navigation -->
    <header class="w-full max-w-6xl mx-auto px-6 py-6 flex items-center justify-between z-10 relative">
        <a href="/" class="flex items-center space-x-3">
            @if(!empty($settings['website_logo']))
                <img src="{{ $settings['website_logo'] }}" alt="Logo" class="h-10 w-10 object-contain rounded-lg">
            @endif
            <span class="text-2xl font-black tracking-widest text-[#FFC107] text-glow">{{ strtoupper($settings['website_name']) }}</span>
        </a>
        
        <div class="flex items-center space-x-4">
            <a href="mailto:{{ $settings['support_contact'] }}" class="hidden sm:inline-block text-sm font-semibold text-gray-300 hover:text-[#FFC107] transition-colors">
                Support
            </a>
            @auth
                <a href="{{ route('admin.dashboard') }}" class="px-5 py-2.5 rounded-xl text-sm font-bold bg-[#C2185B] text-white hover:bg-[#d31c62] shadow-lg border border-white/10 transition-all">
                    Admin Dashboard
                </a>
            @else
                <a href="{{ route('login') }}" class="px-5 py-2.5 rounded-xl text-sm font-bold bg-white/5 border border-white/10 hover:border-[#FFC107] hover:text-[#FFC107] text-white transition-all">
                    Admin Portal
                </a>
            @endauth
        </div>
    </header>

    <!-- Main Container -->
    <main class="w-full max-w-6xl mx-auto px-6 py-8 sm:py-16 grid grid-cols-1 lg:grid-cols-12 gap-12 items-center z-10 relative grow">
        <!-- Hero Copy Details -->
        <div class="lg:col-span-7 space-y-8 text-center lg:text-left">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-magentaAccent/20 border border-magentaAccent/40 text-magentaAccent text-xs font-semibold">
                <span class="w-2 h-2 rounded-full bg-green-400 animate-pulse"></span>
                Direct Investment Commission Model
            </div>
            
            <h1 class="text-4xl sm:text-6xl font-black tracking-tight leading-tight text-white">
                Secure Payouts. <br>
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-[#FFC107] to-[#FF9800] gold-glow">Instant Commission.</span>
            </h1>
            
            <p class="text-gray-300 text-base sm:text-lg max-w-xl mx-auto lg:mx-0 leading-relaxed">
                Welcome to {{ $settings['website_name'] }}. Deposit funds, purchase high-speed investment plans, and watch your yields transfer instantly to your wallet. Secured with premium dual-verification gateways.
            </p>
            
            <!-- App Download & Version Box -->
            <div class="flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-4 pt-4">
                <a href="{{ $settings['app_update_url'] }}" target="_blank"
                   class="inline-flex items-center gap-3 px-8 py-4 bg-gradient-to-r from-[#C2185B] to-[#4B006E] hover:from-[#d31c62] hover:to-[#5c0287] text-[#FFC107] border border-[#FFC107]/20 font-extrabold rounded-2xl shadow-xl hover:shadow-2xl hover:scale-[1.02] transition-all duration-300">
                    <!-- Android Icon -->
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17.523 15.3l1.816 3.146c.105.18.04.405-.14.51-.18.105-.405.04-.51-.14L16.82 15.6c-1.57.705-3.322 1.1-5.18 1.1s-3.61-.395-5.18-1.1l-1.87 3.216c-.1.18-.328.24-.508.14-.18-.1-.24-.328-.14-.508L6.76 15.3C3.931 13.565 2 10.51 2 7h19.8c-.1 3.51-2.031 6.565-4.277 8.3zM7 10c.552 0 1-.448 1-1s-.448-1-1-1-1 .448-1 1 .448 1 1 1zm9 0c.552 0 1-.448 1-1s-.448-1-1-1-1 .448-1 1 .448 1 1 1zM11.9.1c.328.01.558.269.548.598L12.3 2.1c1.81.1 3.53.64 5.03 1.52L18.8 2.2c.18-.18.473-.18.653 0 .18.18.18.473 0 .653l-1.4 1.4c2.51 2.22 4.148 5.42 4.148 9.047H2c0-3.627 1.638-6.827 4.148-9.047l-1.4-1.4c-.18-.18-.18-.473 0-.653.18-.18.473-.18.653 0L6.87 3.62C8.37 2.74 10.09 2.2 11.9 2.1l-.148-1.4c-.01-.329.22-.588.548-.598z"/>
                    </svg>
                    Download Android App
                </a>
                <div class="text-xs text-gray-400 font-semibold flex items-center gap-2">
                    <span class="px-2 py-1 rounded-lg bg-white/5 border border-white/10 text-white font-bold">v{{ $settings['app_version'] }}</span>
                    Android Required
                </div>
            </div>
        </div>

        <!-- Right Side: High Fidelity CSS Mobile Frame Mockup -->
        <div class="lg:col-span-5 flex justify-center items-center">
            <div class="w-[300px] h-[600px] bg-[#0A0010] border-[8px] border-[#2B003F] rounded-[48px] phone-frame relative flex flex-col justify-between overflow-hidden">
                <!-- Phone Top Camera Notch -->
                <div class="absolute top-3 left-1/2 transform -translate-x-1/2 w-28 h-5 bg-[#2B003F] rounded-full z-20 flex items-center justify-between px-3">
                    <span class="w-1.5 h-1.5 rounded-full bg-blue-600"></span>
                    <span class="w-10 h-1 bg-[#13001C] rounded-full"></span>
                </div>
                
                <!-- Phone Inner Dashboard View -->
                <div class="flex-1 flex flex-col justify-between p-4 pt-10 bg-gradient-to-b from-[#1C002C] to-[#0A0010]">
                    <!-- Header -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-full bg-[#C2185B] border border-[#FFC107] flex items-center justify-center font-black text-xs text-[#FFC107]">C</div>
                            <div>
                                <h4 class="text-[10px] text-gray-400">Welcome</h4>
                                <h3 class="text-xs font-bold text-white leading-tight">Chetak User</h3>
                            </div>
                        </div>
                        <span class="px-2 py-0.5 rounded-full bg-green-500/10 text-green-400 border border-green-500/30 text-[9px] font-bold">Active</span>
                    </div>

                    <!-- Balance Card -->
                    <div class="glass-card rounded-2xl p-4 mt-4 space-y-3">
                        <p class="text-[10px] uppercase font-bold tracking-wider text-gray-400">Wallet Account Balance</p>
                        <h2 class="text-2xl font-black text-white">₹12,450.00</h2>
                        <div class="flex justify-between items-center text-[10px] text-gray-400 border-t border-white/5 pt-2">
                            <span>Commissions Today:</span>
                            <span class="text-green-400 font-bold">+₹373.50</span>
                        </div>
                    </div>

                    <!-- Mock Asset Plan Cards -->
                    <div class="mt-4 space-y-2 grow flex flex-col justify-center">
                        <p class="text-[9px] uppercase font-bold tracking-wider text-gray-400">Available Investment Plans</p>
                        <!-- Plan 1 -->
                        <div class="p-3 bg-white/5 rounded-xl border border-white/10 flex justify-between items-center">
                            <div>
                                <h4 class="text-xs font-bold text-white">Starter Plan I</h4>
                                <p class="text-[9px] text-gray-400">Direct 3.0% Commission Return</p>
                            </div>
                            <span class="text-xs font-black text-[#FFC107]">₹1,000.00</span>
                        </div>
                        <!-- Plan 2 -->
                        <div class="p-3 bg-white/5 rounded-xl border border-white/10 flex justify-between items-center">
                            <div>
                                <h4 class="text-xs font-bold text-white">Silver Plan III</h4>
                                <p class="text-[9px] text-gray-400">Direct 3.0% Commission Return</p>
                            </div>
                            <span class="text-xs font-black text-[#FFC107]">₹5,000.00</span>
                        </div>
                        <!-- Plan 3 -->
                        <div class="p-3 bg-white/5 rounded-xl border border-[#C2185B]/30 bg-[#C2185B]/5 flex justify-between items-center">
                            <div>
                                <h4 class="text-xs font-bold text-white">VIP Gold Plan</h4>
                                <p class="text-[9px] text-gray-400">Direct 3.0% Commission Return</p>
                            </div>
                            <span class="text-xs font-black text-[#C2185B]">₹10,000.00</span>
                        </div>
                    </div>

                    <!-- Bottom Nav -->
                    <div class="border-t border-white/5 pt-2 flex justify-around items-center">
                        <div class="flex flex-col items-center text-[#FFC107]">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>
                            <span class="text-[8px]">Home</span>
                        </div>
                        <div class="flex flex-col items-center text-gray-500">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm-5 12H9v-2h6v2zm3-4H6V8h12v4z"/></svg>
                            <span class="text-[8px]">Plans</span>
                        </div>
                        <div class="flex flex-col items-center text-gray-500">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M21 18v1c0 1.1-.9 2-2 2H5c-1.11 0-2-.9-2-2V5c0-1.1.89-2 2-2h14c1.1 0 2 .9 2 2v1h-9c-1.11 0-2 .9-2 2v8c0 1.1.89 2 2 2h9zm-9-2h10V8H12v8zm4-2.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"/></svg>
                            <span class="text-[8px]">Wallet</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Platform Features Grid -->
    <section class="w-full max-w-6xl mx-auto px-6 py-12 border-t border-white/10 z-10 relative">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- 3% Return Card -->
            <div class="glass-card rounded-2xl p-6 space-y-4">
                <div class="w-12 h-12 rounded-xl bg-magentaAccent/15 text-magentaAccent flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M12 16v1"/></svg>
                </div>
                <h3 class="text-lg font-bold text-white">Flat 3.0% Commission Return</h3>
                <p class="text-sm text-gray-400">All purchased investment assets feature a flat 3.0% return structure. The commission amount is instantly calculated and added directly to your wallet balance for immediate withdrawal access.</p>
            </div>

            <!-- Referral Bonus Card -->
            @if($settings['feature_referrals'] === '1')
            <div class="glass-card rounded-2xl p-6 space-y-4">
                <div class="w-12 h-12 rounded-xl bg-[#9C27B0]/15 text-[#9C27B0] flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                </div>
                <h3 class="text-lg font-bold text-white">10% Peer-to-Peer Referrals</h3>
                <p class="text-sm text-gray-400">Build your network and earn massive incentives. You receive an instant 10% cash reward straight into your wallet whenever a referred member activates any investment plan.</p>
            </div>
            @endif

            <!-- Daily Checkin Card -->
            @if($settings['feature_rewards'] === '1')
            <div class="glass-card rounded-2xl p-6 space-y-4">
                <div class="w-12 h-12 rounded-xl bg-orangeAccent/15 text-orangeAccent flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                </div>
                <h3 class="text-lg font-bold text-white">Daily Rewards & Milestones</h3>
                <p class="text-sm text-gray-400">Access extra earnings through check-ins. Earn flat cash bonuses for logging into the Android app daily, and unlock progress milestone payments as your portfolio grows.</p>
            </div>
            @endif
        </div>
    </section>

    <!-- Footer -->
    <footer class="w-full border-t border-white/10 bg-[#0A0010]/60 backdrop-blur-md py-8 z-10 relative">
        <div class="w-full max-w-6xl mx-auto px-6 flex flex-col md:flex-row items-center justify-between gap-4">
            <!-- Footer Branding -->
            <div class="flex items-center space-x-2">
                <span class="text-lg font-extrabold tracking-widest text-[#FFC107]">{{ strtoupper($settings['website_name']) }}</span>
                <span class="text-xs text-gray-500">| Secure Payments</span>
            </div>

            <!-- Footer Links -->
            <div class="flex flex-wrap items-center justify-center gap-6 text-sm text-gray-400 font-semibold">
                <button onclick="openModal('about')" class="hover:text-white transition-colors">About Us</button>
                <button onclick="openModal('privacy')" class="hover:text-white transition-colors">Privacy Policy</button>
                <button onclick="openModal('terms')" class="hover:text-white transition-colors">Terms of Service</button>
                <a href="mailto:{{ $settings['support_contact'] }}" class="hover:text-white transition-colors">Contact Support</a>
            </div>

            <!-- Copyright -->
            <p class="text-xs text-gray-500">Copyright &copy; 2026. All rights reserved.</p>
        </div>
    </footer>

    <!-- Dynamic Overlay Modals -->
    <div id="legalModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 hidden backdrop-blur-sm px-4">
        <div class="glass-card rounded-3xl w-full max-w-2xl mx-4 p-6 sm:p-8 relative">
            <!-- Close button -->
            <button onclick="closeModal()" class="absolute top-6 right-6 text-gray-400 hover:text-white focus:outline-none">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
            
            <h2 id="modalTitle" class="text-2xl font-black text-white mb-6">Modal Title</h2>
            <div id="modalBody" class="text-sm text-gray-300 leading-relaxed overflow-y-auto max-h-[400px] pr-2 no-scrollbar space-y-4">
                Modal content body.
            </div>
            
            <div class="flex justify-end pt-6">
                <button onclick="closeModal()" class="px-6 py-2.5 rounded-xl bg-white/5 border border-white/10 hover:border-white text-white font-bold transition-all text-sm">
                    Close Document
                </button>
            </div>
        </div>
    </div>

    <!-- Modal Swapper Scripts -->
    <script>
        // Content variables mapped dynamically from database values
        const contentData = {
            about: {
                title: "About {{ $settings['website_name'] }}",
                body: `{!! nl2br(e($settings['about_us'])) !!}`
            },
            privacy: {
                title: "Privacy Policy",
                body: `{!! nl2br(e($settings['privacy_policy'])) !!}`
            },
            terms: {
                title: "Terms & Conditions",
                body: `{!! nl2br(e($settings['terms_conditions'])) !!}`
            }
        };

        function openModal(type) {
            const data = contentData[type];
            if (!data) return;
            
            document.getElementById('modalTitle').innerText = data.title;
            document.getElementById('modalBody').innerHTML = data.body;
            document.getElementById('legalModal').classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('legalModal').classList.add('hidden');
        }
    </script>
</body>
</html>
