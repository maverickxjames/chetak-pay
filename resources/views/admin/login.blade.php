<!DOCTYPE html>
<html lang="en" class="h-full bg-[#13001C]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chetak Pay - Admin Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
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
        .glass-panel {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
        }
    </style>
</head>
<body class="h-full flex items-center justify-center px-4 relative overflow-hidden">
    <!-- Decorative background blobs -->
    <div class="absolute w-[500px] h-[500px] rounded-full bg-[#C2185B]/10 -top-20 -left-20 blur-[120px] pointer-events-none"></div>
    <div class="absolute w-[600px] h-[600px] rounded-full bg-[#4B006E]/15 -bottom-40 -right-40 blur-[150px] pointer-events-none"></div>

    <div class="w-full max-w-md">
        <!-- Brand Header -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-extrabold tracking-widest text-[#FFC107]">CHETAK<span class="text-[#C2185B]">PAY</span></h1>
            <p class="text-gray-400 mt-2 text-sm">Web Administration Portal</p>
        </div>

        <!-- Glass Login Panel -->
        <div class="glass-panel rounded-2xl p-8">
            <h2 class="text-xl font-bold mb-6 text-white text-center">Administrator Sign In</h2>

            <!-- Errors -->
            @if($errors->any())
                <div class="mb-5 p-3 rounded-lg bg-red-500/10 border border-red-500/30 text-red-400 text-xs space-y-1">
                    @foreach($errors->all() as $error)
                        <div class="flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            {{ $error }}
                        </div>
                    @endforeach
                </div>
            @endif

            <form action="{{ route('admin.login') }}" method="POST" class="space-y-5">
                @csrf
                <!-- Mobile Field -->
                <div>
                    <label for="mobile" class="block text-xs font-semibold uppercase tracking-wider text-gray-400 mb-2">Admin Mobile Number</label>
                    <input type="text" name="mobile" id="mobile" required placeholder="Enter admin mobile number" 
                           class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-gray-500 focus:outline-none focus:border-[#FFC107] focus:ring-1 focus:ring-[#FFC107] transition-all">
                </div>

                <!-- Password Field -->
                <div>
                    <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-gray-400 mb-2">Secure Password</label>
                    <input type="password" name="password" id="password" required placeholder="••••••••" 
                           class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-gray-500 focus:outline-none focus:border-[#FFC107] focus:ring-1 focus:ring-[#FFC107] transition-all">
                </div>

                <!-- Submit Button -->
                <button type="submit" 
                        class="w-full bg-gradient-to-r from-[#C2185B] to-[#4B006E] hover:from-[#d31c62] hover:to-[#5c0287] text-[#FFC107] font-bold py-3.5 px-4 rounded-xl border border-[#FFC107]/20 shadow-lg hover:shadow-xl hover:scale-[1.01] transition-all">
                    Sign In to Dashboard
                </button>
            </form>
        </div>

        <!-- Legal/Footer -->
        <p class="text-center text-xs text-gray-500 mt-8">Secure connection encrypted. Copyright &copy; 2026 Chetak Pay.</p>
    </div>
</body>
</html>
