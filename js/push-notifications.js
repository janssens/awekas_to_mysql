let pushSubscription = null;
let subscriptionId = null;

// Check if push notifications are supported
if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
    console.log('Push notifications not supported');
} else {
    const button = document.getElementById("notifications");
    button.addEventListener("click", () => {
        Notification.requestPermission().then((result) => {
            if (result === "granted") {
                console.log('Notification permission granted');
                initPushNotifications();
            } else {
                console.log('Notification permission denied');
            }
        });
    });

    // Add click handlers for subscription toggles
    document.querySelectorAll('.subscription-toggle').forEach(toggle => {
        toggle.addEventListener('click', async () => {
            if (!pushSubscription || !subscriptionId) {
                const permission = await Notification.requestPermission();
                if (permission === "granted") {
                    await initPushNotifications();
                } else {
                    console.log('Notification permission denied');
                    return;
                }
            }
            await toggleAlertSubscription(toggle);
        });
    });
}

async function toggleAlertSubscription(toggle) {
    try {
        const alertId = toggle.dataset.alertId;
        
        const response = await fetch('/toggle-alert-subscription.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                subscription_id: subscriptionId,
                alert_id: alertId
            })
        });

        if (!response.ok) {
            throw new Error('Failed to toggle subscription');
        }

        const result = await response.json();
        
        if (result.status === 'subscribed') {
            toggle.classList.remove('unsubscribed');
            toggle.classList.add('subscribed');
            toggle.title = 'Cliquez pour désactiver les notifications';
        } else {
            toggle.classList.remove('subscribed');
            toggle.classList.add('unsubscribed');
            toggle.title = 'Cliquez pour recevoir les notifications';
        }
    } catch (err) {
        console.error('Failed to toggle subscription:', err);
        alert('Erreur lors de la modification de la souscription');
    }
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
        
        // Check for existing subscription
        pushSubscription = await registration.pushManager.getSubscription();
        
        if (!pushSubscription) {
            // Convert VAPID key
            const applicationServerKey = urlBase64ToUint8Array(vapidPublicKey);
            
            try {
                // Subscribe to push notifications
                pushSubscription = await registration.pushManager.subscribe({
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
            endpoint: pushSubscription.endpoint,
            publicKey: btoa(String.fromCharCode.apply(null, new Uint8Array(pushSubscription.getKey('p256dh')))),
            authToken: btoa(String.fromCharCode.apply(null, new Uint8Array(pushSubscription.getKey('auth'))))
        };

        // Send subscription to server
        const response = await fetch('./save-subscription.php', {
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
        subscriptionId = result.subscription_id;
        console.log('Subscription saved successfully:', result);

        // Update UI
        const button = document.getElementById("notifications");
        button.classList.remove('btn-outline-primary');
        button.classList.add('btn-success');
        button.innerHTML = '<i class="bi bi-bell-fill"></i> Notifications activées';

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