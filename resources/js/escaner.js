import { Html5Qrcode } from 'html5-qrcode';

/** Lector integrado del alumno: lee la URL del QR y registra la asistencia con una petición POST. */
export function iniciar(raiz) {
    const estado = raiz.querySelector('[data-resultado]');
    const boton = raiz.querySelector('[data-iniciar]');
    const csrf = document.querySelector('meta[name=csrf-token]').content;
    let lector = null;
    let ocupado = false;

    const mostrar = (ok, texto) => {
        estado.hidden = false;
        estado.className = `mt-4 rounded-lg border px-4 py-3 text-sm ${ok ? 'border-green-200 bg-green-50 text-green-900' : 'border-red-200 bg-red-50 text-red-900'}`;
        estado.textContent = texto;
    };

    async function registrar(texto) {
        if (ocupado) return;
        let url;
        try { url = new URL(texto); } catch { mostrar(false, 'El código leído no pertenece al PAC.'); return; }
        const sesion = url.searchParams.get('s');
        const token = url.searchParams.get('t');
        if (!sesion || !token) { mostrar(false, 'El código leído no es un código de asistencia.'); return; }
        ocupado = true;
        try {
            const r = await fetch(raiz.dataset.endpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ sesion: Number(sesion), token }),
            });
            // Respuestas que no son del registro: sesión vencida, demasiados intentos o error del servidor.
            if (r.status === 401 || r.status === 419) { mostrar(false, 'Tu sesión expiró. Recarga la página, vuelve a iniciar sesión y escanea de nuevo.'); return; }
            if (r.status === 429) { mostrar(false, 'Demasiados intentos seguidos. Espera un minuto y vuelve a escanear.'); return; }
            if (r.status >= 500) { mostrar(false, 'El sistema tuvo un problema. Pide al responsable que registre tu asistencia en modo contingencia.'); return; }
            const d = await r.json();
            mostrar(d.ok, d.mensaje ?? 'No fue posible registrar la asistencia.');
            if (d.ok && lector) { await lector.stop(); boton.hidden = false; boton.textContent = 'Escanear otro código'; }
        } catch {
            mostrar(false, 'Sin conexión. Pide al responsable que registre tu asistencia en modo contingencia.');
        } finally {
            setTimeout(() => { ocupado = false; }, 2500);
        }
    }

    boton.addEventListener('click', async () => {
        boton.hidden = true;
        estado.hidden = true;
        lector ??= new Html5Qrcode('lector-qr');
        try {
            await lector.start({ facingMode: 'environment' }, { fps: 10, qrbox: { width: 240, height: 240 } }, registrar);
        } catch (e) {
            boton.hidden = false;
            mostrar(false, 'No se pudo acceder a la cámara. Revisa los permisos del navegador o escanea con la app de cámara de tu teléfono.');
        }
    });
}
