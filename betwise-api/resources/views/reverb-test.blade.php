<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Reverb Test</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-950 text-white min-h-screen flex items-center justify-center p-8">
    <div class="w-full max-w-xl space-y-6">
        <div>
            <h1 class="text-2xl font-bold">Reverb Test</h1>
            <p id="connection-status" class="text-sm mt-1 text-yellow-400">Connecting...</p>
        </div>

        <div class="space-y-3">
            <input
                id="message-input"
                type="text"
                value="Hello from Reverb!"
                class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-2 text-sm focus:outline-none focus:border-blue-500"
            />
            <button
                id="broadcast-btn"
                class="w-full bg-blue-600 hover:bg-blue-500 text-white font-medium py-2 px-4 rounded-lg transition-colors"
            >
                Broadcast Event
            </button>
        </div>

        <div>
            <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-wide mb-2">Received Events</h2>
            <ul id="events-list" class="space-y-2 text-sm">
                <li class="text-gray-500 italic">No events yet...</li>
            </ul>
        </div>
    </div>

    <script>
        window.addEventListener('load', function () {
        const statusEl = document.getElementById('connection-status');
        const eventsList = document.getElementById('events-list');

        // Monitor connection state
        window.Echo.connector.pusher.connection.bind('connected', () => {
            statusEl.textContent = 'Connected';
            statusEl.className = 'text-sm mt-1 text-green-400';
        });

        window.Echo.connector.pusher.connection.bind('disconnected', () => {
            statusEl.textContent = 'Disconnected';
            statusEl.className = 'text-sm mt-1 text-red-400';
        });

        window.Echo.connector.pusher.connection.bind('error', (err) => {
            statusEl.textContent = 'Connection error';
            statusEl.className = 'text-sm mt-1 text-red-400';
            console.error('Reverb error:', err);
        });

        // Listen for broadcast events
        window.Echo.channel('reverb-test')
            .listen('ReverbTestEvent', (e) => {
                const isEmpty = eventsList.querySelector('li.text-gray-500');
                if (isEmpty) { isEmpty.remove(); }

                const li = document.createElement('li');
                li.className = 'bg-green-900/40 border border-green-700 rounded-lg px-4 py-2';
                li.innerHTML = `<span class="text-green-300">${e.message}</span> <span class="text-gray-500 text-xs ml-2">${new Date().toLocaleTimeString()}</span>`;
                eventsList.prepend(li);
            });

        // Broadcast button
        document.getElementById('broadcast-btn').addEventListener('click', () => {
            const message = document.getElementById('message-input').value;

            axios.post('{{ route('reverb-test.broadcast') }}', { message })
                .then(res => console.log('Broadcast sent:', res.data))
                .catch(err => console.error('Broadcast failed:', err));
        });
        }); // end window.load
    </script>
</body>
</html>
