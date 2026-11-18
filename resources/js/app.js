/*
 * Comportamientos declarativos de la interfaz. Sustituyen a los manejadores en línea (onclick, onsubmit…)
 * para que la política de seguridad de contenido (CSP) pueda prohibir el JavaScript en línea.
 *
 *   data-abrir-dialogo="id"   abre el <dialog> con ese id
 *   data-cerrar-dialogo       cierra el <dialog> que lo contiene
 *   data-confirmar="texto"    en un <form>: pide confirmación antes de enviarlo
 *   data-enviar-al-cambiar    en un campo: envía su formulario al cambiar
 *   data-imprimir             abre el diálogo de impresión
 *   data-activa-horas         casilla de un día: habilita o deshabilita sus horas
 */
document.addEventListener('click', (e) => {
    const abrir = e.target.closest('[data-abrir-dialogo]');
    if (abrir) { document.getElementById(abrir.dataset.abrirDialogo)?.showModal(); return; }
    const cerrar = e.target.closest('[data-cerrar-dialogo]');
    if (cerrar) { cerrar.closest('dialog')?.close(); return; }
    if (e.target.closest('[data-imprimir]')) window.print();
});

document.addEventListener('submit', (e) => {
    const texto = e.target.dataset?.confirmar;
    if (texto && !window.confirm(texto)) e.preventDefault();
});

document.addEventListener('change', (e) => {
    if (e.target.matches('[data-enviar-al-cambiar]')) e.target.form?.submit();
    if (e.target.matches('[data-activa-horas]')) {
        e.target.closest('div').querySelectorAll('input[type=time]').forEach((i) => { i.disabled = !e.target.checked; });
    }
});

// Carga bajo demanda de los módulos de asistencia (solo en las pantallas que los usan)
if (document.querySelector('[data-sesion-qr]')) {
    import('./sesion-qr').then((m) => m.iniciar(document.querySelector('[data-sesion-qr]')));
}
if (document.querySelector('[data-escaner]')) {
    import('./escaner').then((m) => m.iniciar(document.querySelector('[data-escaner]')));
}
