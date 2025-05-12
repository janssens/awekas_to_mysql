// Check if push notifications are supported
if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
    console.log('Push notifications not supported');
} else {
    initPushNotifications();
}

async function initPushNotifications() {
    try {
        // Register service worker
        const registration = await navigator.serviceWorker.register('/service-worker.js');
        console.log('Service Worker registered');

        // Check notification permission
        const permission = await Notification.requestPermission();
        if (permission !== 'granted') {
            throw new Error('Notification permission not granted');
        }

        // Get push subscription
        const subscription = await registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: urlBase64ToUint8Array(vapidPublicKey)
        });

        // Send subscription to server
        await saveSubscription(subscription);
        console.log('Push notification subscription successful');

    } catch (err) {
        console.error('Error setting up push notifications:', err);
    }
}

// Send subscription to server
async function saveSubscription(subscription) {
    const response = await fetch('/save-subscription.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            endpoint: subscription.endpoint,
            publicKey: subscription.getKey('p256dh'),
            authToken: subscription.getKey('auth')
        }),
    });

    if (!response.ok) {
        throw new Error('Failed to save subscription');
    }
}

// Convert VAPID key to correct format
function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - base64String.length % 4) % 4);
    const base64 = (base64String + padding)
        .replace(/\-/g, '+')
        .replace(/_/g, '/');

    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);

    for (let i = 0; i < rawData.length; ++i) {
        outputArray[i] = rawData.charCodeAt(i);
    }
    return outputArray;
} 