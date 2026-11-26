import './bootstrap';
import { initializeApp } from 'firebase/app';
import { getMessaging, getToken, isSupported, onMessage } from 'firebase/messaging';

const firebaseConfig = window.BINI_FIREBASE_CONFIG ?? null;
let messagingInstance = null;

async function ensureMessaging()
{
    if (messagingInstance !== null) {
        return messagingInstance;
    }

    if (!firebaseConfig?.apiKey || !firebaseConfig?.projectId) {
        return null;
    }

    if (! await isSupported()) {
        return null;
    }

    messagingInstance = getMessaging(initializeApp(firebaseConfig));

    return messagingInstance;
}

async function activateNotifications()
{
    const messaging = await ensureMessaging();
    if (messaging === null) {
        throw new Error('Browser ini belum mendukung notifikasi FCM.');
    }

    if (!('Notification' in window)) {
        throw new Error('Browser ini tidak mendukung notifikasi.');
    }

    const permission = await Notification.requestPermission();
    if (permission !== 'granted') {
        throw new Error('Izin notifikasi belum diberikan.');
    }

    if (!firebaseConfig?.vapidKey) {
        throw new Error('VAPID key Firebase belum diisi.');
    }

    let serviceWorkerRegistration = undefined;
    if ('serviceWorker' in navigator) {
        await navigator.serviceWorker.register('/firebase-messaging-sw.js');
        serviceWorkerRegistration = await navigator.serviceWorker.ready;
    }

    return await getToken(messaging, {
        vapidKey: firebaseConfig.vapidKey,
        serviceWorkerRegistration,
    });
}

async function listenForegroundMessages()
{
    const messaging = await ensureMessaging();
    if (messaging === null) {
        return;
    }

    onMessage(messaging, async (payload) => {
        const notification = payload.notification ?? {};
        const url = payload.data?.url ?? '/';
        const title = notification.title ?? 'Bank BINI';
        const options = {
            body: notification.body ?? '',
            icon: notification.icon ?? '/favicon/favicon-96x96.png',
            badge: notification.badge ?? '/favicon/favicon-96x96.png',
            tag: 'bankbini-transaksi',
            data: { url },
        };

        if (!('Notification' in window) || Notification.permission !== 'granted') {
            return;
        }

        if ('serviceWorker' in navigator) {
            const registration = await navigator.serviceWorker.getRegistration('/firebase-messaging-sw.js');
            if (registration) {
                await registration.showNotification(title, options);
                return;
            }
        }

        const shown = new Notification(title, options);

        shown.onclick = () => {
            shown.close();
            window.focus();
            window.location.href = url;
        };
    });
}

if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/firebase-messaging-sw.js').catch(() => {});
}

listenForegroundMessages().catch(() => {});

window.BiniNotifications = {
    activate: activateNotifications,
};
