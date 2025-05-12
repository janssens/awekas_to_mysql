self.addEventListener('install', (event) => {
    console.log('Service Worker installing...');
});

self.addEventListener('activate', (event) => {
    console.log('Service Worker activating...');
});

self.addEventListener('push', function(event) {
    console.log('Push message received:', event);

    if (!event.data) {
        console.log('No data in push message');
        return;
    }

    try {
        const data = event.data.json();
        
        event.waitUntil(
            self.registration.showNotification(data.title || 'Alerte Météo', {
                body: data.body,
                icon: '/icon.png',
                badge: '/badge.png',
                timestamp: data.timestamp || Date.now(),
                vibrate: [100, 50, 100],
                data: data,
                requireInteraction: true,
                tag: 'weather-alert'
            })
        );
    } catch (err) {
        console.error('Error showing notification:', err);
    }
});

self.addEventListener('notificationclick', function(event) {
    console.log('Notification clicked:', event);
    
    event.notification.close();
    
    event.waitUntil(
        clients.openWindow('/')
    );
}); 