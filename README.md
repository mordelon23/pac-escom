# PAC — Portal de Actividades Culturales (ESCOM · IPN)

Sistema web para centralizar, difundir y controlar la asistencia de las actividades culturales y deportivas de la Escuela Superior de Cómputo.

- **Stack:** Laravel 13 · PHP 8.3+ · PostgreSQL 16 · Blade + Tailwind CSS 4 · Vite
- **Arquitectura:** monolito modular en capas (ver abajo)

---

## Puesta en marcha (local)

Requisitos: **PHP 8.3+** con las extensiones `pdo_pgsql`, `mbstring`, `intl`, `gd`, `zip` y `xml`, Composer 2, Node 20+ y PostgreSQL 14+.

### 1. Revisa qué tienes instalado

```bash
php -v && composer -V && node -v && psql --version && git --version
```

> Si `php -v` muestra 8.2 o menos y tienes XAMPP, es probable que se esté usando el PHP de XAMPP. Revísalo con `which php` (Linux) o `where php` (Windows).

### 2. Instala lo que falte

**Debian / Ubuntu**
```bash
sudo apt install php-cli php-pgsql php-mbstring php-xml php-curl php-zip php-intl php-gd php-bcmath postgresql nodejs npm composer
sudo -u postgres psql -c "ALTER USER postgres PASSWORD 'TuContraseña';"
sudo -u postgres createdb pac
```

**Windows:** instala PHP 8.3+ (php.new o Laravel Herd), Node.js LTS (nodejs.org) y PostgreSQL (postgresql.org, incluye pgAdmin). Crea la base `pac` desde pgAdmin.

### 3. Prepara el proyecto

```bash
composer install
npm install
cp .env.example .env          # Windows: copy .env.example .env
php artisan key:generate
# edita .env → DB_PASSWORD=TuContraseña
php artisan migrate --seed
php artisan storage:link
npm run build
php artisan pac:verificar     # revisa todo y dice cómo corregir cada falla
php artisan serve             # http://127.0.0.1:8000
```

`composer setup` hace todos los pasos anteriores de una vez (después de configurar el `.env`).

### Solución de problemas

| Mensaje | Solución |
|---|---|
| `could not find driver` | Falta `pdo_pgsql`: `sudo apt install php-pgsql` (Windows: habilita `extension=pdo_pgsql` en `php.ini`) |
| `password authentication failed` | Revisa `DB_PASSWORD` en `.env` |
| `database "pac" does not exist` | `sudo -u postgres createdb pac` |
| `Vite manifest not found` | `npm run build` |
| Imágenes que no cargan | `php artisan storage:link` |
| `Your requirements could not be resolved… php ^8.3` | Tu PHP es anterior a 8.3 |
| Datos de demostración desordenados | `php artisan migrate:fresh --seed` (borra todo y vuelve a sembrar) |

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
| `tests/Feature/Requisitos/CompletitudTest.php` | RF6 (lugar y fecha), RF7 (semana), RF8 (recordatorio sin duplicados), RF9 (PDF y lista de asistencia, RB17), RF12 (agenda), RNF8 (aviso y aceptación) y Mi cuenta |
| `tests/Feature/Calidad/*` | Características ISO 9126: seguridad, fiabilidad (correo caído, errores, D-11), interoperabilidad (.xlsx, .ics), eficiencia (consultas y tiempo por pantalla), mantenibilidad (reglas configurables) y portabilidad (`pac:verificar`, `pac:respaldo`) |

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
| `PAC_MAX_INSCRIPCIONES`, `PAC_AVISO_CUPO`, `PAC_HORAS_CANCELACION`, `PAC_SEGUNDOS_QR`, `PAC_INTENTOS_LOGIN`, `PAC_MINUTOS_BLOQUEO` | Valores de las reglas RB6, RB19, RB9, RB20 y RB24. Cambian el comportamiento, los mensajes y la ayuda sin tocar el código. |
| `PAC_RESPALDOS_CONSERVAR`, `PAC_PG_DUMP` | Número de respaldos que se conservan y ruta de `pg_dump`. |
| `PAC_CSP` | Política de seguridad de contenido (activa por omisión). |
| `PAC_PROXIES_CONFIABLES` | `*` solo si el hosting coloca la aplicación detrás de un proxy HTTPS. |
| `QUEUE_CONNECTION` | `sync` en la maqueta (sin proceso de colas). En producción: `database` + `php artisan queue:work`. |
| `MAIL_MAILER` | `log` escribe los correos en `storage/logs/laravel-AAAA-MM-DD.log`. En producción, configurar SMTP. Si el correo falla, la operación (inscripción, cancelación…) se guarda de todos modos y el error queda en la bitácora. |

---

## Despliegue en hosting gratuito

El sistema **requiere PostgreSQL**: usa `ILIKE`, índices únicos parciales, `jsonb` y restricciones con expresiones regulares. Muchos hostings gratuitos de PHP solo ofrecen MySQL; verifica que el que elijan tenga PHP 8.3+ y PostgreSQL, o que permita conectarse a una base PostgreSQL externa gratuita.

Pasos generales:
1. `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://tu-dominio`.
2. Variables `DB_*` del PostgreSQL del hosting.
3. `composer install --no-dev --optimize-autoloader` y `npm run build`.
4. `php artisan migrate --force --seed` (con `PAC_DATOS_DEMO=true` si quieren mostrar datos de prueba).
5. `php artisan storage:link` y `php artisan optimize`.
6. `SESSION_SECURE_COOKIE=true` y, si el hosting usa proxy, `PAC_PROXIES_CONFIABLES=*`.
7. `php artisan pac:verificar` revisa también los ajustes de producción (APP_DEBUG, HTTPS, cookie segura, contraseña del superadministrador).
8. Programa `php artisan schedule:run` cada minuto (cron): ejecuta el respaldo diario `pac:respaldo` y, cada hora, los recordatorios `pac:recordatorios` (RF8). En local puedes dejarlo corriendo con `php artisan schedule:work`.
9. Monitorea `https://tu-dominio/up`: responde 200 si la aplicación **y** la base de datos están disponibles.

---

## Funcionalidad por rol

| Rol | Pantallas |
|---|---|
| Alumno | Inicio con próximas sesiones y recomendaciones (según inscripciones y agenda) · catálogo con filtros por categoría, lugar, día y fecha · detalle e inscripción · mis actividades e historial · calendario **mensual y semanal** con exportación a Google Calendar/Outlook (.ics) · agenda cultural · muro social · escáner de QR · correo de confirmación y **recordatorio 24 h antes** |
| Responsable de grupo | Mis grupos y sesiones de hoy · sesión con QR dinámico (se renueva cada 30 s) · modo contingencia con bitácora · resumen de asistencia · **lista de asistencia** por actividad (alumno × sesión) en pantalla, Excel y PDF |
| Coordinador | Panel con indicadores · actividades (crear, editar, publicar, cancelar, eliminar borrador) con horarios semanales · inscritos · reportes (pantalla, **PDF generado en el servidor**, Excel .xlsx, CSV y versión imprimible) · lista de asistencia de cualquier actividad · moderación del muro · sesiones |
| Superadministrador | Todo lo del coordinador + usuarios y roles (alta de personal, cambio de rol, suspensión y reactivación) |
| Todos | **Mi cuenta** (datos registrados y cambio de contraseña) · **Ayuda** según el rol · **aviso de privacidad** público · páginas de error en español |

### Probar el pase de lista con QR
1. Entra como `chernandez@ipn.mx` → **Mis grupos** → «Abrir sesión de asistencia» (Ajedrez se imparte de lunes a viernes).
2. En otro navegador o en el teléfono, entra como `alumno1@alumno.ipn.mx` y escanea el código (con la cámara del teléfono o desde **Inicio → Escanear asistencia**).
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
| 6 | Evaluación de calidad ISO/IEC 9126 / 25010 y correcciones (ver abajo) | ✅ |
| 7 | Cierre de requisitos: recordatorio 24 h (RF8), PDF del servidor y lista de asistencia (RF9), vista semanal (RF7), filtros por lugar y fecha (RF6), recomendaciones con agenda (RF12), aviso de privacidad (RNF8), Mi cuenta | ✅ |
| Pendiente | Pruebas con usuarios (SUS), análisis OWASP ZAP y prueba de carga de 200 usuarios en el servidor de producción | ⏳ |

---

## Calidad (ISO/IEC 9126 / 25010)

| Característica | Qué incluye el sistema | Cómo verificarlo |
|---|---|---|
| Funcionalidad · seguridad | RBAC, contraseñas con hash, bloqueo por intentos (RB24), auditoría de inicios de sesión, CSRF, cabeceras de seguridad y CSP sin JavaScript en línea, exportaciones protegidas contra inyección de fórmulas | `tests/Feature/Calidad/SeguridadTest.php`, `curl -I http://127.0.0.1:8000/login` |
| Funcionalidad · interoperabilidad | Reporte en Excel nativo (.xlsx) y CSV; calendario .ics; QR legible por cualquier app de cámara | `CaracteristicasTest.php` |
| Fiabilidad | Las operaciones no fallan si cae el correo; páginas de error propias; aviso de contingencia si se pierde la red; `/up` revisa la base de datos; respaldo `php artisan pac:respaldo` (diario) | `FiabilidadTest.php` |
| Usabilidad | Pantalla de Ayuda por rol; 0 violaciones WCAG 2.1 AA (axe-core) en las 27 pantallas, en escritorio y teléfono | Lighthouse o axe DevTools |
| Eficiencia | Índices en llaves foráneas; ≤ 15 consultas por pantalla; modo estricto contra consultas N+1; la pantalla del QR deja de consultar cuando la pestaña está oculta | `EficienciaTest.php` |
| Mantenibilidad | Módulos por dominio, reglas de negocio configurables, estilo verificado con Pint, integración continua en GitHub Actions | `composer lint`, `.github/workflows/pruebas.yml` |
| Portabilidad | Configuración solo por `.env`, comando `pac:verificar`, instrucciones para Linux y Windows, proxy HTTPS | `php artisan pac:verificar` |

```bash
php artisan test        # 81 pruebas (reglas de negocio, requisitos y calidad)
composer lint           # estilo del código
php artisan pac:respaldo
```
