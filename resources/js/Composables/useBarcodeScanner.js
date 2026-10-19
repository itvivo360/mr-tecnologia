import { onBeforeUnmount, onMounted, ref } from 'vue';

/**
 * Detecta lecturas de un lector de códigos USB/Bluetooth en modo teclado (HID).
 *
 * Los lectores "tipean" el código muy rápido (≈ 5–30 ms entre teclas) y terminan
 * con Enter (o Tab). Una persona escribe a > 80 ms por tecla, así que la velocidad
 * permite distinguir un escaneo de la escritura normal sin necesidad de que haya
 * un campo con foco.
 *
 * - Con el foco en un input normal no se interfiere: el lector escribe en él.
 * - Con el foco en un elemento con `data-barcode-capture`, el lector escribe en
 *   el campo y además se emite el escaneo, evitando que el Enter envíe el formulario.
 * - Sin foco en ningún campo, el escaneo se captura de forma global.
 *
 * @param {(code: string) => void} onScan
 * @param {{ minLength?: number, maxInterval?: number, enabled?: import('vue').Ref<boolean> }} options
 */
export function useBarcodeScanner(onScan, options = {}) {
    const minLength = options.minLength ?? 4;
    const maxInterval = options.maxInterval ?? 50;
    const enabled = options.enabled ?? ref(true);

    const lastCode = ref(null);
    let buffer = '';
    let lastTime = 0;
    let fastStreak = 0;

    const reset = () => {
        buffer = '';
        fastStreak = 0;
    };

    const isTypingField = (el) =>
        el && (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA' || el.isContentEditable);

    const handler = (event) => {
        if (!enabled.value || event.ctrlKey || event.altKey || event.metaKey) return;

        const target = event.target;
        const capture = target?.closest?.('[data-barcode-capture]');
        // En campos normales (sin captura) no interferimos con la escritura.
        if (isTypingField(target) && !capture) return;

        const now = performance.now();
        const delta = now - lastTime;
        lastTime = now;

        if (event.key === 'Enter' || event.key === 'Tab') {
            if (buffer.length >= minLength && fastStreak >= buffer.length - 1) {
                event.preventDefault();
                event.stopPropagation();
                const code = buffer;
                reset();
                lastCode.value = code;
                onScan(code);
            } else {
                reset();
            }
            return;
        }

        if (event.key.length !== 1) return; // Shift, flechas, etc.

        if (buffer.length > 0 && delta > maxInterval) {
            // Pausa larga: empezamos una secuencia nueva.
            reset();
        } else if (buffer.length > 0) {
            fastStreak++;
        }

        buffer += event.key;

        // Sin campo con foco, evitamos que las teclas disparen otras acciones de la página.
        if (!isTypingField(target)) {
            event.preventDefault();
        }
    };

    onMounted(() => window.addEventListener('keydown', handler, true));
    onBeforeUnmount(() => window.removeEventListener('keydown', handler, true));

    return { lastCode, enabled };
}
