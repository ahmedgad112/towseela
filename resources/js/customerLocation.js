const STORAGE_KEY = 'towseela.location';
const PROMPT_KEY = 'towseela.gpsPrompted';

export function defaultMapCenter(page) {
    return page.props.customerLocation?.mapCenter || { lat: 30.0444, lng: 31.2357 };
}

export function readStoredLocation() {
    try {
        const raw = window.localStorage.getItem(STORAGE_KEY);

        return raw ? JSON.parse(raw) : null;
    } catch {
        return null;
    }
}

export function writeStoredLocation(location) {
    window.localStorage.setItem(STORAGE_KEY, JSON.stringify(location));
    markGpsPrompted();
}

export function hasPromptedGps() {
    return window.localStorage.getItem(PROMPT_KEY) === '1';
}

export function markGpsPrompted() {
    window.localStorage.setItem(PROMPT_KEY, '1');
}

export async function reverseGeocode(lat, lng) {
    const params = new URLSearchParams({ lat: String(lat), lng: String(lng) });
    const response = await fetch(`/location/reverse?${params}`, {
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    });
    const data = await response.json();

    if (!response.ok) {
        throw new Error(data.message || 'تعذر تحديد العنوان من هذا الموقع.');
    }

    return data;
}

export async function searchPlaces(query) {
    const params = new URLSearchParams({ q: query });
    const response = await fetch(`/location/search?${params}`, {
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    });
    const data = await response.json();

    if (!response.ok) {
        throw new Error(data.message || 'تعذر البحث عن العنوان.');
    }

    return data.results || [];
}

export function requestBrowserLocation() {
    return new Promise((resolve, reject) => {
        if (!navigator.geolocation) {
            reject(new Error('المتصفح لا يدعم تحديد الموقع.'));
            return;
        }

        navigator.geolocation.getCurrentPosition(
            (position) => resolve({
                lat: position.coords.latitude,
                lng: position.coords.longitude,
            }),
            (error) => {
                if (error.code === error.PERMISSION_DENIED) {
                    reject(new Error('تم رفض إذن الموقع. فعّل GPS من إعدادات المتصفح.'));
                    return;
                }

                reject(new Error('تعذر قراءة موقعك الحالي.'));
            },
            { enableHighAccuracy: true, timeout: 12000, maximumAge: 30000 },
        );
    });
}

export function loadScript(src) {
    return new Promise((resolve, reject) => {
        if (document.querySelector(`script[src="${src}"]`)) {
            resolve();
            return;
        }

        const script = document.createElement('script');
        script.src = src;
        script.async = true;
        script.onload = () => resolve();
        script.onerror = () => reject(new Error('تعذر تحميل الخريطة.'));
        document.head.appendChild(script);
    });
}

export function loadStylesheet(href) {
    return new Promise((resolve, reject) => {
        if (document.querySelector(`link[href="${href}"]`)) {
            resolve();
            return;
        }

        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = href;
        link.onload = () => resolve();
        link.onerror = () => reject(new Error('تعذر تحميل الخريطة.'));
        document.head.appendChild(link);
    });
}

export async function loadLeaflet() {
    if (window.L) {
        return window.L;
    }

    await Promise.all([
        loadStylesheet('https://unpkg.com/leaflet@1.9.4/dist/leaflet.css'),
        loadScript('https://unpkg.com/leaflet@1.9.4/dist/leaflet.js'),
    ]);

    return window.L;
}
