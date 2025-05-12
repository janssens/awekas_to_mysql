// Check if push notifications are supported
if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
    console.log('Push notifications not supported');
} else {
    initPushNotifications();
}

async function initPushNotifications() {
    try {
        // Check if service worker is already registered
        let registration = await navigator.serviceWorker.getRegistration();
        
        if (!registration) {
            // Register service worker if not already registered
            registration = await navigator.serviceWorker.register('/service-worker.js', {
                scope: '/'
            });
            console.log('Service Worker registered successfully');
        }

        // Wait for the service worker to be ready
        await navigator.serviceWorker.ready;
        console.log('Service Worker is ready');

        // Check notification permission
        const permission = await Notification.requestPermission();
        if (permission !== 'granted') {
            console.log('Notification permission denied');
            return;
        }
        console.log('Notification permission granted');

        // Check for existing subscription
        let subscription = await registration.pushManager.getSubscription();
        
        if (!subscription) {
            // Convert VAPID key
            const applicationServerKey = urlBase64ToUint8Array(vapidPublicKey);
            
            try {
                // Subscribe to push notifications
                subscription = await registration.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: applicationServerKey
                });
                console.log('Push notification subscription created');
            } catch (err) {
                console.error('Failed to subscribe to push:', err);
                throw new Error('Push subscription failed: ' + err.message);
            }
        }

        // Prepare subscription data
        const subscriptionData = {
            endpoint: subscription.endpoint,
            publicKey: btoa(String.fromCharCode.apply(null, new Uint8Array(subscription.getKey('p256dh')))),
            authToken: btoa(String.fromCharCode.apply(null, new Uint8Array(subscription.getKey('auth'))))
        };

        // Send subscription to server
        const response = await fetch('/save-subscription.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(subscriptionData)
        });

        if (!response.ok) {
            throw new Error('Failed to save subscription to server');
        }

        const result = await response.json();
        console.log('Subscription saved successfully:', result);

    } catch (err) {
        console.error('Push notification setup failed:', err);
        throw new Error('Registration failed - ' + err.message);
    }
}

// Convert VAPID key to Uint8Array
function urlBase64ToUint8Array(base64String) {
    try {
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
    } catch (err) {
        console.error('Failed to convert VAPID key:', err);
        throw new Error('Invalid VAPID key format');
    }
} 