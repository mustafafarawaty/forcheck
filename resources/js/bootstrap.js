import axios from 'axios';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import AgoraRTC from 'agora-rtc-sdk-ng';

window.axios = axios;
window.Pusher = Pusher;
window.AgoraRTC = AgoraRTC;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

const normalizeRealtimeConfig = () => {
    const explicitScheme = String(import.meta.env.VITE_REVERB_SCHEME || '').trim().toLowerCase();
    const host = String(import.meta.env.VITE_REVERB_HOST || '').trim() || window.location.hostname;
    const secureTransport = ['https', 'wss'].includes(explicitScheme)
        || (!explicitScheme && window.location.protocol === 'https:');
    const transport = secureTransport ? 'wss' : 'ws';
    const port = Number(import.meta.env.VITE_REVERB_PORT || (secureTransport ? 443 : 80));

    if (window.location.protocol === 'https:' && !secureTransport) {
        console.warn(
            'Realtime websocket is configured as WS while the page is loaded over HTTPS. '
            + 'For local development, open the app over HTTP or serve Reverb through WSS.'
        );
    }

    return {
        broadcaster: 'reverb',
        key: import.meta.env.VITE_REVERB_APP_KEY,
        wsHost: host,
        wsPort: port,
        wssPort: port,
        forceTLS: secureTransport,
        enabledTransports: [transport],
    };
};

const reverbConfig = normalizeRealtimeConfig();

if (reverbConfig.key) {
    window.Echo = new Echo(reverbConfig);

    const connection = window.Echo?.connector?.pusher?.connection;

    if (connection?.bind) {
        connection.bind('connected', () => {
            if (document.body) {
                document.body.dataset.realtimeStatus = 'connected';
            }
        });

        connection.bind('connecting', () => {
            if (document.body) {
                document.body.dataset.realtimeStatus = 'connecting';
            }
        });

        connection.bind('disconnected', () => {
            if (document.body) {
                document.body.dataset.realtimeStatus = 'disconnected';
            }
        });

        connection.bind('error', error => {
            if (document.body) {
                document.body.dataset.realtimeStatus = 'error';
            }
            console.warn('Realtime connection error:', error);
        });
    }
}
