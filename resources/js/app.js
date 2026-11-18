
// Carga bajo demanda de los módulos de asistencia (solo en las pantallas que los usan)
if (document.querySelector('[data-sesion-qr]')) {
    import('./sesion-qr').then((m) => m.iniciar(document.querySelector('[data-sesion-qr]')));
}
if (document.querySelector('[data-escaner]')) {
    import('./escaner').then((m) => m.iniciar(document.querySelector('[data-escaner]')));
}
