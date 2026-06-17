<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Secure Checkout - {{ $settings['website_name'] }}</title>
    <!-- Modern Typography: Inter and Outfit -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Tailwind CSS (via CDN) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        outfit: ['Outfit', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <style>
        body {
            background: radial-gradient(circle at 50% 0%, #f9f5ff 0%, #f3ebff 50%, #eae0fa 100%);
            min-height: 100vh;
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.6);
            box-shadow: 0 10px 30px -5px rgba(124, 58, 237, 0.1), 0 4px 12px -2px rgba(124, 58, 237, 0.05);
        }
        .animate-success {
            animation: scaleIn 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
        }
        @keyframes scaleIn {
            0% { transform: scale(0.8); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }
    </style>
</head>
<body class="font-sans antialiased text-gray-800 flex items-center justify-center p-4">

    <div class="w-full max-w-md space-y-6 py-6">
        <!-- Logo & App Header -->
        <div class="text-center space-y-2">
            @if(!empty($settings['website_logo']))
                <img src="{{ $settings['website_logo'] }}" alt="Logo" class="h-12 mx-auto object-contain">
            @else
                <div class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-gradient-to-tr from-[#C2185B] to-[#4B006E] text-white font-outfit font-extrabold text-2xl shadow-md">
                    C
                </div>
            @endif
            <h1 class="text-xl font-bold font-outfit text-[#4B006E] tracking-tight">{{ $settings['website_name'] }} Secure Portal</h1>
            <p class="text-xs text-gray-500 font-medium">Official Payment Checkout Gateway</p>
        </div>

        <!-- Checkout Card -->
        <div class="glass-card rounded-3xl p-6 space-y-6 relative overflow-hidden transition-all duration-300">
            <!-- Top decorative gradient bar -->
            <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-pink-500 via-[#C2185B] to-[#4B006E]"></div>

            <!-- Amount Section -->
            <div class="text-center py-2 border-b border-gray-100 space-y-1">
                <span class="text-xs text-gray-400 font-semibold tracking-wider uppercase">Amount to Pay</span>
                <div class="text-4xl font-extrabold font-outfit text-[#4B006E]">₹{{ number_format($order->amount, 2, '.', '') }}</div>
                <div class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 text-[#4B006E] border border-indigo-100">
                    Plan: {{ $order->plan->name }}
                </div>
            </div>

            <!-- Pending checkout view -->
            <div id="payment-pending-view" class="space-y-6 {{ $order->status !== 'pending' ? 'hidden' : '' }}">
                
                @if($paymentData['payment_method'] === 'upi_qr')
                    <!-- QR Code & Scanner for UPI QR -->
                    <div class="space-y-4 text-center">
                        <div class="text-xs font-semibold text-gray-400">Scan QR Code using any UPI App to Pay</div>
                        <div class="relative inline-block p-3 bg-white rounded-2xl border border-indigo-50 shadow-inner group">
                            <img src="{{ $paymentData['qr'] }}" alt="UPI QR Code" class="w-52 h-52 mx-auto object-contain">
                            
                            <!-- Expiration overlay -->
                            <div id="expiration-overlay" class="absolute inset-0 bg-white/95 rounded-2xl flex flex-col items-center justify-center p-4 space-y-2 hidden">
                                <svg class="w-12 h-12 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                <span class="font-bold text-gray-700">QR Code Expired</span>
                                <span class="text-xs text-gray-400 text-center">Please request a new payment link from the app.</span>
                            </div>
                        </div>

                        <!-- Paytm Merchant UPI ID -->
                        <div class="mt-2 text-center">
                            <span class="text-[10px] text-gray-400 block uppercase tracking-wider font-bold">Paytm Merchant UPI ID</span>
                            <div class="inline-flex items-center space-x-1.5 bg-indigo-50 border border-indigo-100 rounded-xl px-3 py-1.5 mt-1 shadow-sm">
                                <span id="paytm-upi-text" class="text-xs font-bold text-[#4B006E] font-mono select-all">{{ $paymentData['upi_id'] }}</span>
                                <button type="button" onclick="copyPaytmUpi()" class="text-indigo-600 hover:text-indigo-800 font-extrabold text-xs ml-1 focus:outline-none">
                                    Copy
                                </button>
                            </div>
                        </div>

                        <!-- Timer Countdown -->
                        <div class="flex items-center justify-center space-x-2 text-sm text-gray-500 font-semibold">
                            <svg class="w-4 h-4 text-pink-500 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span>QR Valid For:</span>
                            <span id="countdown-timer" class="text-pink-600 font-bold font-mono">05:00</span>
                        </div>
                    </div>

                    <!-- Direct pay buttons for mobile (Paytm, GPay, PhonePe, Cred) -->
                    <div class="sm:hidden space-y-3">
                        <div class="text-[10px] font-bold uppercase tracking-wider text-gray-400 text-center">Pay directly using your preferred app</div>
                        <div class="grid grid-cols-2 gap-3">
                            <a href="{{ $paymentData['paytm'] }}" 
                               class="flex items-center justify-center py-2.5 px-4 rounded-xl border border-sky-100 bg-sky-50/50 hover:bg-sky-50 text-sky-600 font-bold transition-all text-xs shadow-sm hover:shadow">
                                <span class="font-outfit text-sm">Paytm</span>
                            </a>
                            <a href="{{ $paymentData['gpay'] }}" 
                               class="flex items-center justify-center py-2.5 px-4 rounded-xl border border-red-100 bg-red-50/50 hover:bg-red-50 text-red-600 font-bold transition-all text-xs shadow-sm hover:shadow">
                                <span class="font-outfit text-sm">Google Pay</span>
                            </a>
                            <a href="{{ $paymentData['phonepe'] }}" 
                               class="flex items-center justify-center py-2.5 px-4 rounded-xl border border-purple-100 bg-purple-50/50 hover:bg-purple-50 text-purple-700 font-bold transition-all text-xs shadow-sm hover:shadow">
                                <span class="font-outfit text-sm">PhonePe</span>
                            </a>
                            <a href="{{ $paymentData['cred'] }}" 
                               class="flex items-center justify-center py-2.5 px-4 rounded-xl border border-gray-200 bg-gray-50/50 hover:bg-gray-100 text-gray-800 font-bold transition-all text-xs shadow-sm hover:shadow">
                                <span class="font-outfit text-sm">Cred</span>
                            </a>
                        </div>
                        <a href="{{ $paymentData['upi'] }}" 
                           class="block w-full text-center bg-gradient-to-r from-[#C2185B] to-[#4B006E] hover:from-[#d31c62] hover:to-[#5c0287] text-[#FFC107] font-bold py-3 px-4 rounded-2xl shadow-sm hover:shadow-md transition-all text-xs font-semibold tracking-wide">
                            Pay with Other UPI App
                        </a>
                    </div>
                @else
                    <!-- Manual QR details -->
                    <div class="space-y-4">
                        <div class="text-xs font-semibold text-gray-400 text-center">Pay Manually using details below</div>
                        
                        <div class="bg-gray-50 rounded-2xl p-4 border border-gray-100 space-y-3 text-sm">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-400 font-medium text-xs">Merchant UPI Name:</span>
                                <span class="font-bold text-gray-800">{{ $paymentData['upi_name'] }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-400 font-medium text-xs">Merchant UPI ID:</span>
                                <div class="flex items-center space-x-1">
                                    <span id="merchant-upi-text" class="font-bold text-gray-800 font-mono">{{ $paymentData['upi_id'] }}</span>
                                    <button onclick="copyUpi()" class="text-indigo-600 hover:text-indigo-800 font-semibold text-xs ml-1">Copy</button>
                                </div>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-400 font-medium text-xs">Reference remark:</span>
                                <div class="flex items-center space-x-1">
                                    <span id="remark-text" class="font-bold text-red-600 font-mono">{{ $paymentData['remark'] }}</span>
                                    <button onclick="copyRemark()" class="text-indigo-600 hover:text-indigo-800 font-semibold text-xs ml-1">Copy</button>
                                </div>
                            </div>
                        </div>

                        <!-- Direct pay buttons for mobile (Paytm, GPay, PhonePe, Cred) -->
                        <div class="sm:hidden space-y-3">
                            <div class="text-[10px] font-bold uppercase tracking-wider text-gray-400 text-center">Pay directly using your preferred app</div>
                            <div class="grid grid-cols-2 gap-3">
                                <a href="{{ $paymentData['paytm'] }}" 
                                   class="flex items-center justify-center py-2.5 px-4 rounded-xl border border-sky-100 bg-sky-50/50 hover:bg-sky-50 text-sky-600 font-bold transition-all text-xs shadow-sm hover:shadow">
                                    <span class="font-outfit text-sm">Paytm</span>
                                </a>
                                <a href="{{ $paymentData['gpay'] }}" 
                                   class="flex items-center justify-center py-2.5 px-4 rounded-xl border border-red-100 bg-red-50/50 hover:bg-red-50 text-red-600 font-bold transition-all text-xs shadow-sm hover:shadow">
                                    <span class="font-outfit text-sm">Google Pay</span>
                                </a>
                                <a href="{{ $paymentData['phonepe'] }}" 
                                   class="flex items-center justify-center py-2.5 px-4 rounded-xl border border-purple-100 bg-purple-50/50 hover:bg-purple-50 text-purple-700 font-bold transition-all text-xs shadow-sm hover:shadow">
                                    <span class="font-outfit text-sm">PhonePe</span>
                                </a>
                                <a href="{{ $paymentData['cred'] }}" 
                                   class="flex items-center justify-center py-2.5 px-4 rounded-xl border border-gray-200 bg-gray-50/50 hover:bg-gray-100 text-gray-800 font-bold transition-all text-xs shadow-sm hover:shadow">
                                    <span class="font-outfit text-sm">Cred</span>
                                </a>
                            </div>
                            <a href="{{ $paymentData['upi'] }}" 
                               class="block w-full text-center bg-gradient-to-r from-[#C2185B] to-[#4B006E] hover:from-[#d31c62] hover:to-[#5c0287] text-[#FFC107] font-bold py-3 px-4 rounded-2xl shadow-sm hover:shadow-md transition-all text-xs font-semibold tracking-wide">
                                Pay with Other UPI App
                            </a>
                        </div>

                        <!-- UTR Input Section -->
                        <div class="space-y-2">
                            <label for="payment_txid" class="block text-xs font-bold text-gray-500 uppercase tracking-wide">Enter 12-Digit Transaction UTR</label>
                            <input type="text" id="payment_txid" maxlength="12" placeholder="e.g. 123456789012"
                                   class="w-full bg-white border border-indigo-100 rounded-xl px-4 py-3 text-gray-800 font-mono font-bold tracking-widest text-center focus:outline-none focus:border-[#4B006E] focus:ring-1 focus:ring-[#4B006E] transition-all">
                            <p class="text-[10px] text-gray-400 text-center">Verify the reference number before submitting.</p>
                        </div>
                    </div>
                @endif

                <!-- Action Button for verification -->
                <button type="button" id="verify-button" onclick="verifyTransaction()"
                        class="w-full bg-[#4B006E] hover:bg-[#5c0287] text-white font-bold py-3.5 px-4 rounded-2xl shadow-md hover:shadow-lg transition-all flex items-center justify-center space-x-2 text-sm font-semibold tracking-wide">
                    <span id="btn-text">Verify Payment Status</span>
                    <span id="btn-spinner" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin hidden"></span>
                </button>

                <!-- Error Box -->
                <div id="error-message-box" class="bg-red-50 border border-red-200 text-red-700 text-xs rounded-xl p-3 text-center hidden"></div>

            </div>

            <!-- Verified / Success checkout view -->
            <div id="payment-success-view" class="text-center space-y-6 py-4 animate-success {{ $order->status === 'pending' ? 'hidden' : '' }}">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-green-100 text-green-600 mb-2">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold font-outfit text-[#4B006E]">Payment Verified!</h3>
                
                @if($order->payment_method === 'manual_qr')
                    <p class="text-sm text-gray-500 px-4 leading-relaxed">
                        Your UTR code has been submitted successfully. Admin will review the transaction and activate your plan.
                    </p>
                @else
                    <p class="text-sm text-gray-500 px-4 leading-relaxed">
                        Your transaction check completed successfully. The active plan and commission rewards have been credited.
                    </p>
                @endif

                <!-- Deep Link Redirection button -->
                <div class="pt-2 px-2">
                    <a href="chetakpay://payment/status?order_id={{ $order->id }}" 
                       class="block w-full bg-gradient-to-r from-[#C2185B] to-[#4B006E] text-[#FFC107] font-extrabold py-3.5 px-4 rounded-2xl shadow-md hover:scale-[1.01] transition-all text-sm font-semibold tracking-wide">
                        Return to App
                    </a>
                    <p class="text-[10px] text-gray-400 mt-2">Click to open Chetak Pay application directly.</p>
                </div>
            </div>

        </div>

        <!-- Accepted Gateways / Trust Labels -->
        <div class="glass-card rounded-2xl p-4 flex justify-around items-center opacity-90 text-gray-400">
            <div class="flex flex-col items-center space-y-1">
                <span class="text-[9px] font-bold tracking-wider uppercase text-gray-400">UPI Secured</span>
                <span class="text-[10px] font-semibold text-gray-500">100% Secure</span>
            </div>
            <div class="w-px h-6 bg-gray-200"></div>
            <div class="flex flex-col items-center space-y-1">
                <span class="text-[9px] font-bold tracking-wider uppercase text-gray-400">Paytm Partner</span>
                <span class="text-[10px] font-semibold text-gray-500">Auto Verification</span>
            </div>
            <div class="w-px h-6 bg-gray-200"></div>
            <div class="flex flex-col items-center space-y-1">
                <span class="text-[9px] font-bold tracking-wider uppercase text-gray-400">Instant Access</span>
                <span class="text-[10px] font-semibold text-gray-500">App Redirect</span>
            </div>
        </div>

        <!-- Support footer info -->
        <div class="text-center text-[10px] text-gray-400 font-medium">
            Having trouble? Contact us: <a href="mailto:{{ $settings['support_contact'] }}" class="text-indigo-600 underline font-semibold">{{ $settings['support_contact'] }}</a>
        </div>
    </div>

    <script>
        // Set up CSRF token for headers
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        // Copy Helper functions
        function copyUpi() {
            const upi = document.getElementById('merchant-upi-text').innerText;
            navigator.clipboard.writeText(upi);
            alert('UPI ID copied: ' + upi);
        }

        function copyPaytmUpi() {
            const upi = document.getElementById('paytm-upi-text').innerText;
            navigator.clipboard.writeText(upi);
            alert('Paytm Merchant UPI ID copied: ' + upi);
        }

        function copyRemark() {
            const remark = document.getElementById('remark-text').innerText;
            navigator.clipboard.writeText(remark);
            alert('Reference remark copied: ' + remark);
        }

        // Countdown Timer
        @if($paymentData['payment_method'] === 'upi_qr')
            let timeRemaining = 300; // 5 minutes
            const timerDisplay = document.getElementById('countdown-timer');
            const overlay = document.getElementById('expiration-overlay');
            const verifyBtn = document.getElementById('verify-button');

            const countdownInterval = setInterval(() => {
                const minutes = Math.floor(timeRemaining / 60);
                const seconds = timeRemaining % 60;
                
                timerDisplay.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
                
                if (timeRemaining <= 0) {
                    clearInterval(countdownInterval);
                    overlay.classList.remove('hidden');
                    verifyBtn.disabled = true;
                    verifyBtn.classList.add('opacity-50', 'cursor-not-allowed');
                }
                timeRemaining--;
            }, 1000);
        @endif

        // Verify transaction via AJAX POST check
        function verifyTransaction() {
            const verifyBtn = document.getElementById('verify-button');
            const btnText = document.getElementById('btn-text');
            const btnSpinner = document.getElementById('btn-spinner');
            const errorBox = document.getElementById('error-message-box');

            // Reset states
            errorBox.classList.add('hidden');
            errorBox.innerText = '';
            
            // Collect validation fields
            let requestBody = {};
            @if($paymentData['payment_method'] === 'manual_qr')
                const utr = document.getElementById('payment_txid').value.trim();
                if (utr.length !== 12 || isNaN(utr)) {
                    errorBox.innerText = 'Please enter a valid 12-digit numeric UTR code.';
                    errorBox.classList.remove('hidden');
                    return;
                }
                requestBody.payment_txid = utr;
            @endif

            // Set Loading state
            verifyBtn.disabled = true;
            btnSpinner.classList.remove('hidden');
            btnText.innerText = 'Verifying Transaction...';

            fetch("{{ route('payment.share.verify', $order->id) }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(requestBody)
            })
            .then(async response => {
                const data = await response.json();
                if (!response.ok) {
                    throw new Error(data.message || 'Verification check failed.');
                }
                return data;
            })
            .then(data => {
                // Success redirect
                document.getElementById('payment-pending-view').classList.add('hidden');
                document.getElementById('payment-success-view').classList.remove('hidden');
            })
            .catch(error => {
                errorBox.innerText = error.message;
                errorBox.classList.remove('hidden');
            })
            .finally(() => {
                verifyBtn.disabled = false;
                btnSpinner.classList.add('hidden');
                btnText.innerText = 'Verify Payment Status';
            });
        }
    </script>

</body>
</html>
