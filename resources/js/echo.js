import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

const BROADCAST_DRIVER = import.meta.env.VITE_BROADCAST_DRIVER || 'reverb';

const echoConfig = {
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY || 'default',
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER || 'mt1',
    wsHost: BROADCAST_DRIVER === 'reverb'
        ? import.meta.env.VITE_REVERB_HOST
        : import.meta.env.VITE_PUSHER_HOST,
    wsPort: BROADCAST_DRIVER === 'reverb'
        ? import.meta.env.VITE_REVERB_PORT
        : import.meta.env.VITE_PUSHER_PORT,
    wssPort: BROADCAST_DRIVER === 'reverb'
        ? import.meta.env.VITE_REVERB_PORT
        : import.meta.env.VITE_PUSHER_PORT,
    forceTLS: BROADCAST_DRIVER === 'reverb' ? false : true,
    enabledTransports: BROADCAST_DRIVER === 'reverb' ? ['ws', 'wss'] : ['ws', 'wss'],
};

window.Echo = new Echo(echoConfig);
