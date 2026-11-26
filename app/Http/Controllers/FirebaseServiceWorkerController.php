<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use JsonException;

/**
 * Menyajikan service worker FCM dengan konfigurasi web dari server.
 */
class FirebaseServiceWorkerController
{
    /**
     * Menghasilkan service worker untuk notifikasi background FCM.
     *
     * @throws JsonException
     */
    public function __invoke(): Response
    {
        $config = [
            'apiKey' => (string) config('services.firebase_web.api_key'),
            'authDomain' => (string) config('services.firebase_web.auth_domain'),
            'projectId' => (string) config('services.firebase_web.project_id'),
            'messagingSenderId' => (string) config('services.firebase_web.messaging_sender_id'),
            'appId' => (string) config('services.firebase_web.app_id'),
        ];

        $version = preg_replace('/[^0-9.]/', '', (string) config('services.firebase_web.sdk_version', '12.4.0')) ?: '12.4.0';
        $configJson = json_encode($config, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);

        $script = <<<JS
        importScripts('https://www.gstatic.com/firebasejs/{$version}/firebase-app-compat.js');
        importScripts('https://www.gstatic.com/firebasejs/{$version}/firebase-messaging-compat.js');

        const firebaseConfig = {$configJson};

        if (firebaseConfig.apiKey && firebaseConfig.projectId) {
            firebase.initializeApp(firebaseConfig);
            const messaging = firebase.messaging();

            messaging.onBackgroundMessage((payload) => {
                const notification = payload.notification || {};
                const data = payload.data || {};
                const title = notification.title || 'Bank BINI';
                const options = {
                    body: notification.body || '',
                    icon: notification.icon || '/favicon/favicon-96x96.png',
                    badge: notification.badge || '/favicon/favicon-96x96.png',
                    data: { url: data.url || '/' },
                };

                self.registration.showNotification(title, options);
            });

            self.addEventListener('notificationclick', (event) => {
                event.notification.close();

                const url = (event.notification.data && event.notification.data.url) || '/';
                event.waitUntil(
                    clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
                        for (const client of clientList) {
                            if (client.url === url && 'focus' in client) {
                                return client.focus();
                            }
                        }

                        return clients.openWindow(url);
                    })
                );
            });
        }
        JS;

        return response($script, 200)
            ->header('Content-Type', 'application/javascript; charset=UTF-8')
            ->header('Cache-Control', 'no-cache, must-revalidate');
    }
}
