import { onMounted, onUnmounted, ref } from 'vue';
import { router } from '@inertiajs/vue3';

const POLL_MS = 4000;

function csrfToken() {
    const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/);
    return match ? decodeURIComponent(match[1]) : '';
}

function playOrderChime() {
    try {
        const AudioCtx = window.AudioContext || window.webkitAudioContext;
        if (!AudioCtx) {
            return;
        }

        const ctx = new AudioCtx();
        const oscillator = ctx.createOscillator();
        const gain = ctx.createGain();

        oscillator.type = 'sine';
        oscillator.frequency.setValueAtTime(880, ctx.currentTime);
        oscillator.frequency.exponentialRampToValueAtTime(1175, ctx.currentTime + 0.12);
        gain.gain.setValueAtTime(0.0001, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.09, ctx.currentTime + 0.02);
        gain.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + 0.45);

        oscillator.connect(gain);
        gain.connect(ctx.destination);
        oscillator.start();
        oscillator.stop(ctx.currentTime + 0.5);
        oscillator.onended = () => ctx.close();
    } catch {
        // Browsers may block audio until a user gesture; live reload still works.
    }
}

/**
 * Lightweight live refresh for ops/driver/customer order screens.
 * Polls a cache-backed pulse endpoint (no WebSocket process required).
 *
 * @param {{ channel: 'ops' | 'driver' | 'customer', only: string[], soundOn?: string[] }} options
 */
export function useOrderLive(options) {
    const { channel, only, soundOn = ['created', 'assigned'] } = options;
    const live = ref(false);
    const lastVersion = ref(null);

    let timer = null;
    let inFlight = false;

    const stop = () => {
        if (timer) {
            clearInterval(timer);
            timer = null;
        }
    };

    const tick = async () => {
        if (document.hidden || inFlight) {
            return;
        }

        inFlight = true;

        try {
            const response = await fetch('/realtime/orders/pulse', {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-XSRF-TOKEN': csrfToken(),
                },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                live.value = false;
                return;
            }

            const payload = await response.json();
            const pulse = payload?.[channel] ?? null;

            if (!pulse?.v) {
                live.value = false;
                return;
            }

            live.value = true;

            if (lastVersion.value === null) {
                lastVersion.value = pulse.v;
                return;
            }

            if (lastVersion.value === pulse.v) {
                return;
            }

            lastVersion.value = pulse.v;

            if (soundOn.includes(pulse.reason)) {
                playOrderChime();
            }

            router.reload({
                only,
                preserveScroll: true,
                preserveState: true,
            });
        } catch {
            live.value = false;
        } finally {
            inFlight = false;
        }
    };

    const onVisibility = () => {
        if (!document.hidden) {
            tick();
        }
    };

    onMounted(() => {
        tick();
        timer = setInterval(tick, POLL_MS);
        document.addEventListener('visibilitychange', onVisibility);
    });

    onUnmounted(() => {
        stop();
        document.removeEventListener('visibilitychange', onVisibility);
    });

    return { live };
}
