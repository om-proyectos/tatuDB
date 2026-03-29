# TatuDB

**TatuDB** es un **Database Manager y Query Builder** escrito en PHP que permite ejecutar consultas SQL de forma sencilla, ordenada y estandarizada.

## ¿Qué es TatuDB?

TatuDB no es un ORM tradicional, sino un **Database Manager** que abstrae las operaciones CRUD mediante un formato de entrada basado en arrays estandarizados. Construye consultas SQL dinámicas utilizando PDO para garantizar seguridad y prevención de inyección SQL.

### Características Principales

- ✅ **Multi-motor**: Soporte para MySQL y MariaDB (con arquitectura extensible a PostgreSQL, SQLite, Redis)
- ✅ **Query Builder**: Construcción de consultas mediante arrays en lugar de SQL crudo
- ✅ **Prepared Statements**: Todas las consultas utilizan PDO para prevenir inyección SQL
- ✅ **Respuestas Estandarizadas**: Formato de retorno consistente con estado, código, resultado, totales y excepciones
- ✅ **Paginación Integrada**: Soporte nativo para paginación con totales y cálculo de páginas
- ✅ **JOINs Dinámicos**: Soporte para INNER, LEFT, RIGHT JOIN y otros
- ✅ **Funciones Almacenadas**: Ejecución de stored procedures y funciones del motor de base de datos
- ✅ **Seguridad**: Bloqueo de DELETE sin condiciones para evitar borrados accidentales

## Estructura de la Librería

```
tatuDB/
├── tatuDB.php              # Clase factory para instanciar el motor
├── MySQL/
│   └── tatuMySQL.php       # Implementación para MySQL
├── MariaDB/
│   └── tatuMariaDB.php     # Implementación para MariaDB
└── tatuGenerico/
    └── tatuGenerico.php    # Clase base con formatos estándar
```

## Motores Soportados

| Motor | Estado | Archivo |
|-------|--------|---------|
| MySQL | ✅ Estable | `MySQL/tatuMySQL.php` |
| MariaDB | ✅ Estable | `MariaDB/tatuMariaDB.php` |
| SQLite | 🔄 Pendiente | - |
| PostgreSQL | 🔄 Pendiente | - |
| Redis | 🔄 Pendiente | - |

## Instalación

```php
// Incluir la clase principal
require_once 'tatuDB/tatuDB.php';

// Instanciar el motor deseado
$factory = new tatuDB();
$db = $factory->constructor('tatuMySQL', [
    'servidor' => '127.0.0.1',
    'puerto'   => 3306,
    'base'     => 'mi_base',
    'usuario'  => 'root',
    'clave'    => 'secret'
]);
```

## Ejemplo Rápido

```php
// SELECT con paginación y filtros
$resultado = $db->traerListadoCompleto([
    'tabla' => 'usuarios',
    'campos' => ['id', 'nombre', 'email'],
    'condiciones' => ['activo = :activo'],
    'orden' => [['campo' => 'nombre', 'orden' => 'ASC']],
    'limite' => ['inicio' => 0, 'registrosPorPagina' => 20],
    'datos' => [':activo' => 1]
]);

// INSERT
$db->realizarInsert([
    'tabla' => 'usuarios',
    'campos' => ['nombre', 'email'],
    'datos' => ['nombre' => 'Juan', 'email' => 'juan@example.com']
]);

// UPDATE
$db->realizarUpdate([
    'tabla' => 'usuarios',
    'campos' => ['nombre = :nombre'],
    'condiciones' => ['id = :id'],
    'datos' => [':nombre' => 'Pedro', ':id' => 1]
]);

// DELETE
$db->realizarDelete([
    'tabla' => 'usuarios',
    'condiciones' => ['id = :id'],
    'datos' => [':id' => 1]
]);
```

## Formato de Respuesta Estándar

Todas las operaciones devuelven un array con la siguiente estructura:

```php
[
    'estado'         => 'OK' | 'Error',
    'codRespuesta'   => '1000' (éxito) | '1001' (error) | '1002' (advertencia),
    'resultado'      => [],      // Datos de la consulta
    'totalRegistros' => 0,       // Registros devueltos
    'totalFinal'     => 0,       // Total real (para paginación)
    'pagina'         => 0,       // Página actual
    'totalPaginas'   => 0,       // Total de páginas
    'excepcion'      => ''       // Mensaje de error si ocurrió
]
```

## Documentación Detallada

- [**TatuMySQL**](documentacion/TatuMySQL.md) - Guía completa para MySQL
- [**TatuMariaDB**](documentacion/TatuMariaDB.md) - Guía completa para MariaDB

## Requisitos

- PHP 7.4 o superior
- Extensión PDO habilitada
- Driver `pdo_mysql` para MySQL/MariaDB

## Licencia

Este proyecto está bajo la Licencia **MPL 2.0** (Mozilla Public License 2.0).  
Para más detalles, consulta el archivo [LICENSE](LICENSE).

---

**Autor**: Damián Delgado  
**Fecha de creación**: 27/07/2021  
**Última actualización**: 28/03/2026
