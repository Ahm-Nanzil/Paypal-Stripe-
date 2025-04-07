<x-layouts.app>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Payment Successful') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="flex flex-col items-center">
                        <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mb-4">
                            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 mb-2">Payment Successful!</h3>
                        <p class="text-gray-700 mb-6">Your PayPal payment has been processed successfully.</p>

                        <div class="w-full max-w-md bg-gray-50 rounded-lg p-4 mb-6">
                            <h4 class="text-md font-semibold text-gray-700 mb-2">Payment Details:</h4>
                            <ul class="space-y-2">
                                <li class="text-sm text-gray-600">
                                    <span class="font-medium">Transaction ID:</span> {{ $payment['id'] ?? 'N/A' }}
                                </li>
                                <li class="text-sm text-gray-600">
                                    <span class="font-medium">Status:</span> {{ $payment['status'] ?? 'N/A' }}
                                </li>
                                @if(isset($payment['purchase_units'][0]['payments']['captures'][0]['amount']))
                                    <li class="text-sm text-gray-600">
                                        <span class="font-medium">Amount:</span>
                                        {{ $payment['purchase_units'][0]['payments']['captures'][0]['amount']['value'] ?? 'N/A' }}
                                        {{ $payment['purchase_units'][0]['payments']['captures'][0]['amount']['currency_code'] ?? 'USD' }}
                                    </li>
                                @endif
                            </ul>
                        </div>

                        <div class="flex space-x-4">
                            <a href="{{ route('payment.index') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-800 focus:outline-none focus:border-blue-800 focus:ring focus:ring-blue-300 disabled:opacity-25 transition">
                                Make Another Payment
                            </a>
                            <a href="{{ route('dashboard') }}" class="inline-flex items-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300 active:bg-gray-400 focus:outline-none focus:border-gray-400 focus:ring focus:ring-gray-200 disabled:opacity-25 transition">
                                Back to Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
