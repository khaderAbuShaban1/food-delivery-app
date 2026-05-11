@php
    $firestoreProjectId = (string) config('services.firestore.project_id', '');
    $firestoreApiKey = (string) (config('services.firestore.web_api_key') ?: 'AIzaSyBgAqN4YO1qVnp6_YTMbjdPzk5-Hh6apg4');
    $firestoreAppId = (string) (config('services.firestore.web_app_id') ?: '');
    $firestoreSenderId = (string) (config('services.firestore.web_messaging_sender_id') ?: '885023315229');
    $firestoreBucket = (string) (config('services.firestore.web_storage_bucket') ?: ($firestoreProjectId ? "{$firestoreProjectId}.firebasestorage.app" : ''));
    $notificationMode = $mode ?? 'admin';
    $restaurantId = isset($restaurant) && $restaurant ? (int) $restaurant->id : null;
@endphp

<div class="web-notification-center" data-mode="{{ $notificationMode }}">
    <button type="button" class="web-notification-button" id="webNotificationButton" aria-label="Realtime notifications">
        <span class="web-notification-icon">!</span>
        <span class="web-notification-count" id="webNotificationCount" hidden>0</span>
    </button>
    <div class="web-notification-menu" id="webNotificationMenu" hidden>
        <div class="web-notification-menu-head">
            <strong>الإشعارات المباشرة</strong>
            <button type="button" id="webNotificationClear">تحديد الكل كمقروء</button>
        </div>
        <div class="web-notification-status" id="webNotificationStatus">جار الاتصال بالتحديثات المباشرة...</div>
        <div class="web-notification-list" id="webNotificationList">
            <div class="web-notification-empty">لا توجد إشعارات جديدة</div>
        </div>
    </div>
</div>

<script>
    const foodDeliveryFirestoreConfig = {
        apiKey: @json($firestoreApiKey),
        authDomain: @json($firestoreProjectId ? "{$firestoreProjectId}.firebaseapp.com" : ''),
        projectId: @json($firestoreProjectId),
        storageBucket: @json($firestoreBucket),
        messagingSenderId: @json($firestoreSenderId),
    };
    if (@json($firestoreAppId) !== '') {
        foodDeliveryFirestoreConfig.appId = @json($firestoreAppId);
    }

    window.foodDeliveryFirestore = {
        mode: @json($notificationMode),
        restaurantId: @json($restaurantId),
        config: foodDeliveryFirestoreConfig,
    };
</script>

<script type="module">
    import { initializeApp } from 'https://www.gstatic.com/firebasejs/10.14.1/firebase-app.js';
    import {
        getFirestore,
        collection,
        onSnapshot,
        query,
        where,
    } from 'https://www.gstatic.com/firebasejs/10.14.1/firebase-firestore.js';

    const settings = window.foodDeliveryFirestore || {};
    const config = settings.config || {};
    const mode = settings.mode || 'admin';
    const restaurantId = Number(settings.restaurantId || 0);
    const button = document.getElementById('webNotificationButton');
    const menu = document.getElementById('webNotificationMenu');
    const countBadge = document.getElementById('webNotificationCount');
    const clearButton = document.getElementById('webNotificationClear');
    const list = document.getElementById('webNotificationList');
    const status = document.getElementById('webNotificationStatus');
    const notifications = [];
    const knownOrders = new Map();
    const knownUsers = new Map();
    const knownRestaurants = new Map();
    const notifiedKeys = new Set();
    let unread = 0;
    let lastToneAt = 0;
    let audioContext = null;
    let audioUnlocked = false;

    const ADMIN_ACTION_REQUIRED_STATUS = 'pending_payment_verification';
    const RESTAURANT_INCOMING_STATUS = 'payment_verified';

    function emitRealtimeEvent(name, detail = {}) {
        document.dispatchEvent(new CustomEvent(name, { detail }));
    }

    function dispatchDashboardRefresh() {
        emitRealtimeEvent('food:realtime-data');
        emitRealtimeEvent('food:realtime-orders');
        if (mode === 'restaurant') {
            emitRealtimeEvent('food:restaurant-orders');
        }
        if (mode === 'admin') {
            emitRealtimeEvent('food:admin-orders');
        }
    }

    function dispatchAdminUsersRefresh() {
        emitRealtimeEvent('food:realtime-data');
        emitRealtimeEvent('food:admin-users');
    }

    function dispatchAdminRestaurantsRefresh() {
        emitRealtimeEvent('food:realtime-data');
        emitRealtimeEvent('food:admin-restaurants');
    }

    function setStatus(text, ok = true) {
        if (!status) return;
        status.textContent = text;
        status.classList.toggle('is-error', !ok);
    }

    function updateBadge() {
        if (!countBadge) return;
        countBadge.hidden = unread <= 0;
        countBadge.textContent = unread > 99 ? '99+' : String(unread);
    }

    function renderList() {
        if (!list) return;
        if (notifications.length === 0) {
            list.innerHTML = '<div class="web-notification-empty">لا توجد إشعارات جديدة</div>';
            return;
        }

        list.innerHTML = notifications.slice(0, 12).map((item) => `
            <div class="web-notification-item ${item.important ? 'important' : ''}">
                <div class="web-notification-item-title">${escapeHtml(item.title)}</div>
                <div class="web-notification-item-body">${escapeHtml(item.body)}</div>
                <div class="web-notification-item-time">${escapeHtml(item.time)}</div>
            </div>
        `).join('');
    }

    function addNotification(title, body, important = false) {
        notifications.unshift({
            title,
            body,
            important,
            time: new Date().toLocaleTimeString('ar', { hour: '2-digit', minute: '2-digit' }),
        });
        unread += 1;
        updateBadge();
        renderList();
        showToast(title, body, important);
        if (important) playTone();
    }

    function notifyOnce(key, title, body, important = false) {
        if (!key || notifiedKeys.has(key)) return;
        notifiedKeys.add(key);
        addNotification(title, body, important);
    }

    function showToast(title, body, important) {
        if (window.toastr) {
            const fn = important ? 'warning' : 'info';
            window.toastr[fn](`${title}<br>${body}`);
            return;
        }

        const toast = document.createElement('div');
        toast.className = `web-live-toast ${important ? 'important' : ''}`;
        toast.innerHTML = `<strong>${escapeHtml(title)}</strong><span>${escapeHtml(body)}</span>`;
        document.body.appendChild(toast);
        requestAnimationFrame(() => toast.classList.add('show'));
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, 4200);
    }

    function getAudioContext() {
        const AudioContext = window.AudioContext || window.webkitAudioContext;
        if (!AudioContext) return null;
        audioContext ??= new AudioContext();
        return audioContext;
    }

    function playOscillator(frequency, volume, durationMs) {
        const ctx = getAudioContext();
        if (!ctx || ctx.state !== 'running') return false;

        const oscillator = ctx.createOscillator();
        const gain = ctx.createGain();
        oscillator.type = 'sine';
        oscillator.frequency.value = frequency;
        gain.gain.setValueAtTime(volume, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + (durationMs / 1000));
        oscillator.connect(gain);
        gain.connect(ctx.destination);
        oscillator.start();
        oscillator.stop(ctx.currentTime + (durationMs / 1000));
        return true;
    }

    async function playTone() {
        try {
            console.debug('[WebNotifications] audio playback attempted', { mode, audioUnlocked });
            const now = Date.now();
            if (now - lastToneAt < 1500) return;
            lastToneAt = now;

            const ctx = getAudioContext();
            if (!ctx) return;
            if (ctx.state !== 'running') {
                await ctx.resume();
            }

            const first = playOscillator(880, 0.18, 180);
            setTimeout(() => {
                playOscillator(660, 0.14, 180);
            }, 160);
            console.debug('[WebNotifications] audio playback result', { mode, played: first, state: ctx.state });
        } catch (error) {
            setStatus('اضغط زر الإشعارات مرة واحدة لتفعيل صوت التنبيهات', false);
            console.warn('[WebNotifications] audio playback failed', error);
        }
    }

    async function unlockAudio() {
        if (audioUnlocked) return;
        try {
            const ctx = getAudioContext();
            if (!ctx) return;
            if (ctx.state !== 'running') {
                await ctx.resume();
            }
            playOscillator(440, 0.001, 60);
            audioUnlocked = ctx.state === 'running';
            if (audioUnlocked) {
                setStatus('متصل بالتحديثات المباشرة - صوت التنبيهات مفعّل');
            }
            console.debug('[WebNotifications] audio unlocked', { mode, state: ctx.state });
        } catch (error) {
            console.warn('[WebNotifications] audio unlock blocked', error);
        }
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function asMillis(value) {
        if (!value) return 0;
        if (typeof value.toMillis === 'function') return value.toMillis();
        if (typeof value.seconds === 'number') return value.seconds * 1000;
        const parsed = Date.parse(String(value));
        return Number.isFinite(parsed) ? parsed : 0;
    }

    function orderTitle(order) {
        return `طلب #${order.id || order.order_number || ''}`;
    }

    function handleAdminOrderChange(change) {
        const id = change.doc.id;
        const data = { id, ...change.doc.data() };
        const previous = knownOrders.get(id);
        knownOrders.set(id, data);

        // Admin only needs alerts for orders waiting for payment/admin approval.
        if (data.status !== ADMIN_ACTION_REQUIRED_STATUS) return;
        if (previous && previous.status === data.status) return;

        notifyOnce(
            `admin-order-approval:${id}`,
            'طلب يحتاج موافقة الإدارة',
            `${orderTitle(data)} بانتظار التحقق من الدفع`,
            true
        );
    }

    function handleRestaurantOrderChange(change) {
        const id = change.doc.id;
        const data = { id, ...change.doc.data() };
        const previous = knownOrders.get(id);
        knownOrders.set(id, data);

        // Restaurants only need the first actionable incoming-order alert.
        if (data.status !== RESTAURANT_INCOMING_STATUS) return;
        if (previous && previous.status === data.status) return;

        notifyOnce(
            `restaurant-incoming-order:${id}`,
            'طلب وارد جديد',
            `${orderTitle(data)} بانتظار قبول المطعم`,
            true
        );
    }

    function handleAdminUserChange(change) {
        const id = change.doc.id;
        const data = { id, ...change.doc.data() };
        const previous = knownUsers.get(id);
        knownUsers.set(id, data);

        if (data.role !== 'driver' || !['pending', 'draft'].includes(String(data.status || ''))) return;
        if (previous && previous.status === data.status) return;

        notifyOnce(
            `admin-driver-request:${id}`,
            'طلب سائق جديد',
            `${data.name || 'سائق جديد'} بانتظار المراجعة`,
            true
        );
    }

    function handleAdminRestaurantChange(change) {
        const id = change.doc.id;
        const data = { id, ...change.doc.data() };
        knownRestaurants.set(id, data);
    }
    function bindUi() {
        ['click', 'pointerdown', 'keydown'].forEach((eventName) => {
            window.addEventListener(eventName, unlockAudio, { once: true, passive: true });
        });
        button?.addEventListener('click', () => {
            unlockAudio();
            if (!menu) return;
            menu.hidden = !menu.hidden;
            if (!menu.hidden) {
                unread = 0;
                updateBadge();
            }
        });
        clearButton?.addEventListener('click', () => {
            unread = 0;
            notifications.length = 0;
            updateBadge();
            renderList();
        });
        document.addEventListener('click', (event) => {
            if (!menu || menu.hidden) return;
            if (event.target.closest('.web-notification-center')) return;
            menu.hidden = true;
        });
    }

    function listenToOrders(db) {
        const constraints = [];
        if (mode === 'restaurant' && restaurantId > 0) {
            constraints.push(where('restaurant_id', '==', restaurantId));
        }

        const q = constraints.length
            ? query(collection(db, 'orders'), ...constraints)
            : query(collection(db, 'orders'));

        let initialized = false;
        return onSnapshot(q, (snapshot) => {
            setStatus('متصل بالتحديثات المباشرة');
            dispatchDashboardRefresh();
            snapshot.docChanges().forEach((change) => {
                if (!initialized) {
                    knownOrders.set(change.doc.id, { id: change.doc.id, ...change.doc.data() });
                    return;
                }
                mode === 'restaurant' ? handleRestaurantOrderChange(change) : handleAdminOrderChange(change);
            });
            initialized = true;
        }, () => setStatus('تعذر الاتصال بتحديثات Firestore', false));
    }

    function listenToAdminCollections(db) {
        let usersInitialized = false;
        let restaurantsInitialized = false;

        onSnapshot(query(collection(db, 'users')), (snapshot) => {
            dispatchAdminUsersRefresh();
            snapshot.docChanges().forEach((change) => {
                if (!usersInitialized) {
                    knownUsers.set(change.doc.id, { id: change.doc.id, ...change.doc.data() });
                    return;
                }
                handleAdminUserChange(change);
            });
            usersInitialized = true;
        });

        onSnapshot(query(collection(db, 'restaurants')), (snapshot) => {
            dispatchAdminRestaurantsRefresh();
            snapshot.docChanges().forEach((change) => {
                if (!restaurantsInitialized) {
                    knownRestaurants.set(change.doc.id, { id: change.doc.id, ...change.doc.data() });
                    return;
                }
                handleAdminRestaurantChange(change);
            });
            restaurantsInitialized = true;
        });
    }

    bindUi();
    renderList();

    if (!config.apiKey || !config.projectId) {
        setStatus('إعدادات Firestore للويب غير مكتملة', false);
    } else {
        try {
            const app = initializeApp(config, `food-delivery-web-${mode}`);
            const db = getFirestore(app);
            listenToOrders(db);
            if (mode === 'admin') listenToAdminCollections(db);
        } catch (_) {
            setStatus('فشل تهيئة Firestore للويب', false);
        }
    }
</script>
