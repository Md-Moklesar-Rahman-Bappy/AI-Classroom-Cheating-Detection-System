@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-2xl font-bold mb-6">Security Center</h1>

    @if(session('status'))
        <div class="mb-4 p-4 bg-green-100 text-green-800 rounded">{{ session('status') }}</div>
    @endif

    <div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg mb-6">
        <div class="px-4 py-5 sm:px-6">
            <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-white">Login Security Events</h3>
            <p class="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-300">Audit log of blocked attempts. Email and IP partially masked.</p>
        </div>
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Email</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">IP</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Reason</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Created</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($events as $event)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ $event->id }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white font-mono">{{ Str::mask($event->email ?? '', '*', 3) }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white font-mono">{{ Str::mask($event->ip ?? '', '*', 3) }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ $event->reason }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">{{ $event->created_at }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <form method="POST" action="{{ route('security.unblock-pair', ['email' => $event->email ?? '', 'ip' => $event->ip ?? '']) }}" class="inline" onsubmit="return confirm('Unblock pair?');">
                            @csrf
                            <input type="hidden" name="reason" value="Admin manual unblock">
                            <button type="submit" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 text-xs font-medium">Unblock Pair</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-6 py-4 text-sm text-gray-500">No events recorded.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700 sm:px-6">
            {{ $events->links() }}
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg p-6">
        <h3 class="text-lg font-medium mb-4">Unblock by IP</h3>
        <form method="POST" action="{{ route('security.unblock-ip', ['ip' => old('ip', '')]) }}" class="space-y-4">
            @csrf
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email (optional)</label>
                <input type="text" name="email" id="email" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-900 dark:border-gray-700" placeholder="user@example.com" value="{{ old('email') }}">
                @error('email') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="ip" class="block text-sm font-medium text-gray-700 dark:text-gray-300">IP Address <span class="text-red-500">*</span></label>
                <input type="text" name="ip" id="ip" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-900 dark:border-gray-700" placeholder="192.168.1.1" value="{{ old('ip') }}">
                @error('ip') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="reason" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Reason <span class="text-red-500">*</span></label>
                <input type="text" name="reason" id="reason" required maxlength="200" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-900 dark:border-gray-700" value="{{ old('reason') }}">
                @error('reason') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
            <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">Unblock IP</button>
        </form>
    </div>
</div>
@endsection
