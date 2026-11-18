# PAC — Portal de Actividades Culturales (ESCOM · IPN)

Sistema web para centralizar, difundir y controlar la asistencia de las actividades culturales y deportivas de la Escuela Superior de Cómputo.

- **Stack:** Laravel 13 · PHP 8.3+ · PostgreSQL 16 · Blade + Tailwind CSS 4 · Vite
- **Arquitectura:** monolito modular en capas (ver abajo)

---

## Puesta en marcha (local)

Requisitos: PHP 8.3+ con las extensiones `pdo_pgsql`, `mbstring`, `intl` y `gd`, Composer 2, Node 20+ y PostgreSQL 14+.

```bash
git clone <url-del-repositorio> pac && cd pac
composer install
npm install
cp .env.example .env
php artisan key:generate

# Crear la base de datos (ajusta usuario/contraseña en .env)
createdb pac          # o desde pgAdmin
php artisan migrate --seed
php artisan storage:link

npm run build         # o «npm run dev» mientras desarrollas
php artisan serve     # http://127.0.0.1:8000
```

### Cuentas de demostración

En `APP_ENV=local` el seeder crea datos de prueba. La contraseña de todas es **`Pac2026demo`**.

| Rol | Correo |
|---|---|
| Alumno | `alumno0@alumno.ipn.mx` … `alumno5@alumno.ipn.mx` |
| Responsable de grupo | `chernandez@ipn.mx`, `atorres@ipn.mx` |
| Coordinadora | `coordinacion@ipn.mx` |
| Superadministrador | `superadmin@ipn.mx` / contraseña: `PAC_SUPERADMIN_PASSWORD` del `.env` |

> **Cambia `PAC_SUPERADMIN_PASSWORD` antes de desplegar.**

---

## Pruebas

```bash
createdb pac_test
php artisan test
```

Las pruebas usan la base `pac_test` (ver `phpunit.xml`). Cubren las reglas de negocio con su número del plan de proyecto:

| Archivo | Qué verifica |
|---|---|
| `tests/Unit/ValidacionIdentidadTest.php` | RB1 (dominio `@alumno.ipn.mx`) y RB3 (boleta de 10 dígitos) |
| `tests/Unit/ServicioTokenQRTest.php` | RB20: token QR de 30 s, ventana anterior, expiración y token de otra sesión |
| `tests/Unit/HorariosTest.php` | RB26: horarios traslapados |
| `tests/Feature/Reglas/InscripcionesTest.php` | RB4, RB5, RB6, RB9 y RB19 |
| `tests/Feature/Reglas/AsistenciaTest.php` | RB20, RB21, RB22 y RB23 |
| `tests/Feature/Http/AutenticacionTest.php` | CU1, CU2, RB12, RB24, cuenta suspendida y control de acceso por rol |
| `tests/Feature/Reglas/ActividadesTest.php` | CU11: borrador con horarios, publicación (RB10), cupo (RB5), horarios con sesiones (RB26), cancelación (RB8) |
| `tests/Feature/Reglas/ComunidadUsuariosTest.php` | RB16 (muro y moderación), CU15 y RB25 (usuarios) |
| `tests/Feature/Http/PantallasTest.php` | Todas las pantallas por rol, flujo QR por HTTP, formulario de actividad y CSV |

---

## Arquitectura

Monolito modular en capas sobre el patrón MVC de Laravel. Cada módulo agrupa sus enumeraciones, modelos, acciones (casos de uso) y controladores:

```
app/Modulos/
├── Identidad/       usuarios, roles y permisos (RBAC), login, registro, contraseña
├── Actividades/     catálogo, actividades, horarios semanales, publicación y cancelación
├── Inscripciones/   inscribirse, cancelar, inicio del alumno
├── Asistencia/      sesiones, token QR (HMAC-SHA256), contingencia y bitácora
├── Comunidad/       muro social y moderación
└── Compartido/      excepción de reglas de negocio y auditoría
```

| Capa | Dónde | Responsabilidad |
|---|---|---|
| Presentación | `Http/Controllers`, `Http/Requests`, `resources/views` | Recibir la petición, validar el formato y mostrar la respuesta. Los controladores no contienen reglas. |
| Aplicación | `Acciones/` | Un caso de uso por clase; maneja la transacción (p. ej. `InscribirAlumno`). |
| Dominio | `Modelos/`, `Enums/` | Entidades con sus reglas (`Actividad::tieneCupo()`), estados como enumeraciones. |
| Infraestructura | PostgreSQL, colas, correo, `Servicios/` | Persistencia, envío de correos y servicio de tokens. |

**Defensa en tres niveles:** las reglas críticas se validan en el navegador, en el servidor (acciones y Form Requests) y en la base de datos (restricciones `CHECK`, `UNIQUE` e índice único parcial). Por ejemplo, la sobreinscripción se impide con `SELECT … FOR UPDATE` en `InscribirAlumno` **y** con `CHECK (cupo_ocupado <= cupo_maximo)`.

---

## Variables de entorno propias del PAC

| Variable | Uso |
|---|---|
| `PAC_VERIFICAR_CORREO` | `true` envía correo de verificación al registrarse. En la maqueta: `false`. |
| `PAC_DATOS_DEMO` | `true` siembra datos de demostración aunque el entorno no sea `local`. |
| `PAC_SUPERADMIN_CORREO` / `PAC_SUPERADMIN_PASSWORD` | Cuenta inicial del superadministrador. |
| `QUEUE_CONNECTION` | `sync` en la maqueta (sin proceso de colas). En producción: `database` + `php artisan queue:work`. |
| `MAIL_MAILER` | `log` escribe los correos en `storage/logs/laravel.log`. En producción, configurar SMTP. |

---

## Despliegue en hosting gratuito

El sistema **requiere PostgreSQL**: usa `ILIKE`, índices únicos parciales, `jsonb` y restricciones con expresiones regulares. Muchos hostings gratuitos de PHP solo ofrecen MySQL; verifica que el que elijan tenga PHP 8.3+ y PostgreSQL, o que permita conectarse a una base PostgreSQL externa gratuita.

Pasos generales:
1. `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://tu-dominio`.
2. Variables `DB_*` del PostgreSQL del hosting.
3. `composer install --no-dev --optimize-autoloader` y `npm run build`.
4. `php artisan migrate --force --seed` (con `PAC_DATOS_DEMO=true` si quieren mostrar datos de prueba).
5. `php artisan storage:link` y `php artisan optimize`.

---

## Funcionalidad por rol

| Rol | Pantallas |
|---|---|
| Alumno | Inicio con próximas sesiones y recomendaciones · catálogo con filtros · detalle e inscripción · mis actividades e historial · calendario mensual · agenda cultural · muro social · escáner de QR |
| Responsable de grupo | Mis grupos y sesiones de hoy · sesión con QR dinámico (se renueva cada 30 s) · modo contingencia con bitácora · resumen de asistencia |
| Coordinador | Panel con indicadores · actividades (crear, editar, publicar, cancelar, eliminar borrador) con horarios semanales · inscritos · reportes (pantalla, CSV para Excel, versión imprimible/PDF) · moderación del muro · sesiones |
| Superadministrador | Todo lo del coordinador + usuarios y roles (alta de personal, cambio de rol, suspensión y reactivación) |

### Probar el pase de lista con QR
1. Entra como `chernandez@ipn.mx` → **Mis grupos** → «Abrir sesión de asistencia» (Ajedrez se imparte de lunes a viernes).
2. En otro navegador o en el teléfono, entra como `alumno1@alumno.ipn.mx` y escanea el código (con la cámara del teléfono o desde **Inicio → Escanear código QR**).
3. Para escanear desde el teléfono en tu red local: `php artisan serve --host=0.0.0.0` y abre `http://IP-de-tu-PC:8000`. La cámara del navegador requiere HTTPS salvo en `localhost`; con la app de cámara del teléfono funciona sin HTTPS porque solo abre el enlace.

## Avance por Sprint

| Sprint | Alcance | Estado |
|---|---|---|
| 0 | Proyecto, base de datos completa, modelos, reglas de dominio y pruebas | ✅ |
| 1 | Login (RB12, RB24), registro (RB1–RB3), recuperación de contraseña (RB13), roles | ✅ |
| 2 | Panel de coordinación: actividades con horarios, publicar, cancelar, inscritos | ✅ |
| 3 | Catálogo, inscripción y cancelación (RB4–RB6, RB9), mis actividades, inicio | ✅ |
| 4 | Sesión con QR dinámico, escáner del alumno, contingencia y resumen | ✅ |
| 5 | Agenda, calendario, muro y moderación, reportes, usuarios y roles | ✅ |
| Pendiente | Exportación .xlsx nativa (hoy CSV compatible con Excel), PDF generado en servidor (hoy versión imprimible), pruebas de carga y análisis OWASP ZAP | ⏳ |
