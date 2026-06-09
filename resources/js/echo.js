import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

const explicitScheme = String(import.meta.env.VITE_REVERB_SCHEME || '').trim().toLowerCase();
const secureTransport = ['https', 'wss'].includes(explicitScheme)
    || (!explicitScheme && window.location.protocol === 'https:');
const transport = secureTransport ? 'wss' : 'ws';
const reverbPort = Number(import.meta.env.VITE_REVERB_PORT || (secureTransport ? 443 : 80));

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: String(import.meta.env.VITE_REVERB_HOST || '').trim() || window.location.hostname,
    wsPort: reverbPort,
    wssPort: reverbPort,
    forceTLS: secureTransport,
    enabledTransports: [transport],
});
