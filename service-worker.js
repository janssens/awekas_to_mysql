self.addEventListener('push', function(event) {
    if (!event.data) return;

    const data = event.data.json();
    
    event.waitUntil(
        self.registration.showNotification(data.title, {
            body: data.body,
            icon: data.icon,
            timestamp: data.timestamp,
            vibrate: [100, 50, 100],
            data: data
        })
    );
});

self.addEventListener('notificationclick', function(event) {
    event.notification.close();
    
    event.waitUntil(
        clients.openWindow('/')
    );
}); 