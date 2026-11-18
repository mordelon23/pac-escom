import QRCode from 'qrcode';

/**
 * Pantalla del responsable: consulta cada 2 s el estado de la sesión y redibuja el QR cuando cambia el token (cada 30 s).
 */
export function iniciar(raiz) {
    const lienzo = raiz.querySelector('[data-qr]');
    const cuenta = raiz.querySelector('[data-cuenta]');
    const barra = raiz.querySelector('[data-barra]');
    const asistentes = raiz.querySelector('[data-asistentes]');
    const lista = raiz.querySelector('[data-ultimos]');
    const aviso = raiz.querySelector('[data-aviso]');
    let urlActual = null;
    let segundos = 30;

    async function consultar() {
        try {
            const r = await fetch(raiz.dataset.endpoint, { headers: { Accept: 'application/json' } });
            if (!r.ok) throw new Error(r.status);
            const d = await r.json();
            aviso.hidden = true;
            if (d.estado !== 'ABIERTA') { window.location.reload(); return; }
            if (d.url !== urlActual) {
                urlActual = d.url;
                await QRCode.toCanvas(lienzo, d.url, { width: Math.min(420, raiz.clientWidth - 32), margin: 1, errorCorrectionLevel: 'M',
                    color: { dark: '#3d0f27', light: '#ffffff' } });
            }
            segundos = d.segundos;
            asistentes.textContent = `${d.asistentes} / ${d.inscritos}`;
            lista.innerHTML = d.ultimos.map((u) =>
                `<li class="flex justify-between py-1.5"><span>${escapar(u.nombre)}</span><span class="text-gray-500">${u.hora}${u.metodo === 'MANUAL' ? ' · manual' : ''}</span></li>`).join('')
                || '<li class="py-1.5 text-gray-500">Aún no hay registros.</li>';
        } catch (e) {
            aviso.hidden = false;   // posible falla de red: sugerir contingencia
        }
    }

    function tic() {
        segundos = Math.max(0, segundos - 1);
        cuenta.textContent = segundos;
        barra.style.width = `${(segundos / 30) * 100}%`;
    }

    const escapar = (s) => s.replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

    consultar();
    setInterval(consultar, 2000);
    setInterval(tic, 1000);
}
