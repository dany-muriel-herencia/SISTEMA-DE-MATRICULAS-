# Sistema de Matrícula UNJBG

Plataforma académica modular y escalable para la gestión de matrículas de la **Universidad Nacional Jorge Basadre Grohmann (UNJBG)**.

---

## 🏛️ 1. Arquitectura Técnica

El sistema implementa una **Arquitectura en Capas Desacopladas (Clean Architecture)** orientada a módulos de dominio:

```text
Cliente / Frontend
       ↓
Controller (Express)
       ↓
Service / Use Case (Lógica de Negocio Pura)
       ↓
Repository (Consultas SQL Parametrizadas)
       ↓
Driver Nativo (mysql2/promise)
       ↓
Base de Datos MySQL Local
```

### 🚫 Restricción de Persistencia: NO ORM
El proyecto **NO utiliza ORM** (como Sequelize, TypeORM o Prisma). Todo el acceso a datos se efectúa mediante SQL puro y explícito dentro de la capa de Repositorios, empleando parámetros para prevenir vulnerabilidades de inyección SQL.

---

## 📂 2. Estructura del Proyecto

```text
SISTEMA-DE-MATRICULAS-/
├── backend/
│   ├── src/
│   │   ├── config/               # Configuración centralizada (.env)
│   │   ├── database/             # Pool de MySQL y QueryRunner
│   │   ├── shared/               # Errores, utilidades y middlewares comunes
│   │   ├── modules/              # Módulos del dominio
│   │   │   ├── health/           # Diagnóstico de salud y conexión DB
│   │   │   ├── auth/             # Autenticación y roles
│   │   │   ├── estudiantes/      # Gestión de estudiantes
│   │   │   ├── academico/        # Carreras, planes y cursos
│   │   │   ├── periodos/         # Periodos académicos
│   │   │   ├── oferta-academica/ # Secciones, aulas y horarios
│   │   │   └── matricula/        # Registro y detalle de matrícula
│   │   ├── routes.js             # Agregador de rutas (/api/v1/*)
│   │   ├── server.js             # Configuración de Express
│   │   └── index.js              # Punto de entrada y arranque
│   ├── .env.example              # Plantilla de variables de entorno
│   ├── .env                      # Variables locales
│   └── package.json
│
├── frontend/                     # Cliente Web
│   ├── src/
│   │   ├── services/api.js       # Cliente API REST
│   │   ├── styles/main.css       # Sistema de diseño y estilos
│   │   └── main.js
│   └── index.html
│
├── database/                     # Recursos SQL
│   ├── schema/                   # DDL de tablas relacionales
│   ├── migrations/               # Scripts de migración
│   ├── seeds/                    # Datos iniciales (roles, admin, etc.)
│   └── queries/                  # Consultas SQL explícitas de referencia
│
├── docs/                         # Documentación técnica y diagramas
│   ├── arquitectura.md
│   └── diagrama_er.md
│
└── README.md
```

---

## 🚀 3. Guía de Instalación y Ejecución

### Prerrequisitos
* **Node.js**: v18+ o v22 LTS (incluido en Laragon `C:\laragon\bin\nodejs\node-v22`).
* **MySQL**: 8.x / MariaDB (incluido en Laragon).

### Paso 1: Configurar la Base de Datos Local
1. Inicie MySQL desde el panel de Laragon (o su servicio local de MySQL).
2. Ejecute el script de creación del esquema en MySQL:
   ```bash
   mysql -u root -p < database/schema/01_initial_schema.sql
   ```
3. Opcionalmente, cargue los datos iniciales de prueba:
   ```bash
   mysql -u root -p < database/seeds/001_seed_initial_data.sql
   ```

### Paso 2: Configurar Variables de Entorno
En la carpeta `backend/`:
```bash
cp .env.example .env
```
Edite `.env` con las credenciales de su base de datos local:
```env
PORT=3000
NODE_ENV=development
API_PREFIX=/api/v1

DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=db_matricula_unjbg
DB_USER=root
DB_PASSWORD=
```

### Paso 3: Instalar Dependencias del Backend
Desde el directorio `backend/`:
```bash
npm install
```

### Paso 4: Probar la Conexión a la Base de Datos
```bash
npm run test:db
```

### Paso 5: Iniciar el Servidor Backend
Modo desarrollo (con recarga automática):
```bash
npm run dev
```
O modo estándar:
```bash
npm start
```

El servidor estará disponible en `http://localhost:3000`.

---

## 📡 4. Endpoints Iniciales Disponibles

| Método | Endpoint | Descripción |
|---|---|---|
| `GET` | `/api/v1/health` | Estado del servidor y diagnóstico de conexión a MySQL |
| `POST` | `/api/v1/auth/login` | Inicio de sesión |
| `GET` | `/api/v1/estudiantes` | Módulo de estudiantes |
| `GET` | `/api/v1/cursos` | Módulo académico (cursos/planes) |
| `GET` | `/api/v1/periodos` | Módulo de periodos académicos |
| `GET` | `/api/v1/secciones` | Módulo de oferta académica y horarios |
| `GET` | `/api/v1/matriculas` | Módulo de matrícula |

---

## 📋 5. Convenciones de Desarrollo
* **Commits**: `feat:`, `fix:`, `docs:`, `style:`, `refactor:`, `test:`, `chore:`.
* **Respuestas JSON Estándar**:
  ```json
  {
    "success": true,
    "statusCode": 200,
    "message": "Mensaje descriptivo",
    "data": { ... }
  }
  ```
* **Control de Errores**: Todo error operativo debe extender de `AppError` (`NotFoundError`, `ValidationError`, `DatabaseError`, `UnauthorizedError`).
