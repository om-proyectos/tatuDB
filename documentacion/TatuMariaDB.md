# TatuMariaDB

Clase de acceso a datos para **MariaDB** basada en PDO.

## Descripción

`tatuMariaDB` es la implementación del motor MariaDB para TatuDB. Provee métodos genéricos para ejecutar consultas SELECT, INSERT, UPDATE, DELETE y funciones almacenadas, utilizando un formato de entrada estandarizado heredado de `tatuGenerico`.

### Responsabilidades

- Gestionar la conexión PDO a MariaDB
- Construir SQLs a partir de estructuras de arrays bien definidas
- Estandarizar el formato de retorno con estado, código, resultado, totales y excepciones

### Requisitos

- Extensión PDO habilitada
- Driver `pdo_mysql` instalado (MariaDB usa el mismo driver que MySQL)

---

## Diferencias entre MySQL y MariaDB en TatuDB

Aunque ambas implementaciones son funcionalmente equivalentes en TatuDB, MariaDB ofrece características adicionales que pueden aprovecharse:

| Característica | MySQL | MariaDB |
|----------------|-------|---------|
| Driver PDO | `pdo_mysql` | `pdo_mysql` |
| Stored Procedures | ✅ | ✅ |
| Funciones almacenadas | ✅ | ✅ |
| Tablas temporales | ✅ | ✅ |
| **Tablas de sistema versionadas** | ❌ | ✅ |
| **Columnas calculadas persistentes** | Limitado | ✅ |
| **WITH (CTE)** | 8.0+ | 10.2+ |
| **Ventanas (Window Functions)** | 8.0+ | 10.2+ |

> **Nota**: `tatuMariaDB.php` es idéntico a `tatuMySQL.php` en funcionalidad básica. Las características específicas de MariaDB pueden utilizarse mediante `ejecutarConsulta()` con SQL personalizado.

---

## Inicialización

### Conexión a la Base de Datos

```php
require_once 'tatuDB/MariaDB/tatuMariaDB.php';

$db = new tatuMariaDB();
$db->constructor([
    'servidor' => '127.0.0.1',
    'puerto'   => 3306,
    'base'     => 'mi_base',
    'usuario'  => 'root',
    'clave'    => 'secret'
]);
```

### Parámetros de Conexión

| Parámetro | Tipo | Descripción |
|-----------|------|-------------|
| `servidor` | string | Host o IP del servidor MariaDB |
| `puerto` | int | Puerto TCP del servicio MariaDB (default: 3306) |
| `base` | string | Nombre de la base de datos |
| `usuario` | string | Usuario con permisos sobre la base de datos |
| `clave` | string | Clave del usuario de base de datos |

---

## Métodos Disponibles

### 1. `ejecutarConsulta(string $sql, array $arrayExecute = []): array`

Ejecuta una consulta SQL directa utilizando prepared statements.

#### Parámetros

| Parámetro | Tipo | Descripción |
|-----------|------|-------------|
| `$sql` | string | SQL a ejecutar con placeholders (`:nombre`) |
| `$arrayExecute` | array | Parámetros asociativos para el bind |

#### Ejemplo

```php
$resultado = $db->ejecutarConsulta(
    "SELECT * FROM usuarios WHERE id = :id",
    [':id' => 5]
);

if ($resultado['estado'] === 'OK') {
    foreach ($resultado['resultado'] as $fila) {
        echo $fila['nombre'];
    }
}
```

#### Respuesta

```php
[
    'estado'         => 'OK',
    'codRespuesta'   => '1000',
    'resultado'      => [['id' => 5, 'nombre' => 'Juan']],
    'totalRegistros' => 1,
    'excepcion'      => ''
]
```

---

### 2. `traerListadoCompleto(array $arraySQL): array`

Construye y ejecuta un SELECT con JOINs, WHERE, ORDER BY, GROUP BY y LIMIT.

#### Estructura de `$arraySQL`

```php
$arraySQL = [
    'tabla'       => 'usuarios',
    'campos'      => ['id', 'nombre', 'email'],
    'conjuntos'   => [
        ['relacion' => 'INNER JOIN', 'tabla' => 'pedidos', 'condicion' => 'usuarios.id = pedidos.usuario_id']
    ],
    'condiciones' => ['activo = :activo', 'fecha >= :fecha'],
    'orden'       => [['campo' => 'nombre', 'orden' => 'ASC']],
    'agrupar'     => ['pais'],
    'limite'      => ['inicio' => 0, 'registrosPorPagina' => 20],
    'datos'       => [':activo' => 1, ':fecha' => '2024-01-01'],
    'funcion'     => ''
];
```

#### Campos de `$arraySQL`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `tabla` | string | Nombre de la tabla principal |
| `campos` | array | Campos a seleccionar (acepta alias con `AS`) |
| `conjuntos` | array | JOINs: `relacion`, `tabla`, `condicion` |
| `condiciones` | array | Filtros WHERE (con operadores y placeholders) |
| `orden` | array | Ordenamiento: `campo`, `orden` (ASC/DESC) |
| `agrupar` | array | Campos para GROUP BY |
| `limite` | array | Paginación: `inicio`, `registrosPorPagina` |
| `datos` | array | Valores para los placeholders |
| `funcion` | string | Función almacenada a ejecutar (no usado aquí) |

#### Ejemplo Completo

```php
$resultado = $db->traerListadoCompleto([
    'tabla' => 'usuarios',
    'campos' => ['u.id', 'u.nombre', 'u.email', 'p.total'],
    'conjuntos' => [
        ['relacion' => 'INNER JOIN', 'tabla' => 'pedidos p', 'condicion' => 'u.id = p.usuario_id']
    ],
    'condiciones' => ['u.activo = :activo'],
    'orden' => [['campo' => 'u.nombre', 'orden' => 'ASC']],
    'limite' => ['inicio' => 0, 'registrosPorPagina' => 20],
    'datos' => [':activo' => 1]
]);

// Acceder a resultados
echo "Total registros: " . $resultado['totalRegistros'];
echo "Total final: " . $resultado['totalFinal'];
echo "Página actual: " . $resultado['pagina'];
echo "Total páginas: " . $resultado['totalPaginas'];

foreach ($resultado['resultado'] as $fila) {
    echo $fila['nombre'] . " - " . $fila['total'];
}
```

#### Respuesta con Paginación

```php
[
    'estado'         => 'OK',
    'codRespuesta'   => '1000',
    'resultado'      => [...],
    'totalRegistros' => 20,
    'totalFinal'     => 150,
    'pagina'         => 0,
    'totalPaginas'   => 8,
    'excepcion'      => ''
]
```

---

### 3. `realizarInsert(array $arraySQL = []): array`

Realiza un INSERT basado en el formato estándar.

#### Estructura de `$arraySQL`

```php
$arraySQL = [
    'tabla'  => 'usuarios',
    'campos' => ['nombre', 'email', 'activo'],
    'datos'  => ['nombre' => 'Juan', 'email' => 'juan@example.com', 'activo' => 1]
];
```

#### Ejemplo

```php
$resultado = $db->realizarInsert([
    'tabla' => 'usuarios',
    'campos' => ['nombre', 'email', 'fecha_registro'],
    'datos' => [
        'nombre' => 'María García',
        'email' => 'maria@example.com',
        'fecha_registro' => date('Y-m-d H:i:s')
    ]
]);

if ($resultado['estado'] === 'OK') {
    echo "Insert exitoso. ID: " . $db->conexion->lastInsertId();
}
```

#### SQL Generado

```sql
INSERT INTO usuarios (nombre, email, fecha_registro) 
VALUES (:nombre, :email, :fecha_registro)
```

---

### 4. `realizarUpdate(array $arraySQL): array`

Realiza un UPDATE basado en el formato estándar.

#### Estructura de `$arraySQL`

```php
$arraySQL = [
    'tabla'       => 'usuarios',
    'campos'      => ['nombre = :nombre', 'email = :email'],
    'condiciones' => ['id = :id'],
    'datos'       => [':nombre' => 'Pedro', ':email' => 'pedro@example.com', ':id' => 1]
];
```

#### Ejemplo

```php
$resultado = $db->realizarUpdate([
    'tabla' => 'usuarios',
    'campos' => ['nombre = :nombre', 'activo = :activo'],
    'condiciones' => ['id = :id'],
    'datos' => [
        ':nombre' => 'Carlos López',
        ':activo' => 0,
        ':id' => 5
    ]
]);

if ($resultado['estado'] === 'OK') {
    echo "Registros actualizados: " . $resultado['totalRegistros'];
}
```

#### SQL Generado

```sql
UPDATE usuarios 
SET nombre = :nombre, activo = :activo 
WHERE id = :id
```

---

### 5. `realizarDelete(array $arraySQL): array`

Realiza un DELETE validando que existan condiciones (seguridad).

#### Estructura de `$arraySQL`

```php
$arraySQL = [
    'tabla'       => 'usuarios',
    'condiciones' => ['id = :id'],
    'datos'       => [':id' => 1]
];
```

#### Ejemplo

```php
// DELETE seguro con condiciones
$resultado = $db->realizarDelete([
    'tabla' => 'usuarios',
    'condiciones' => ['id = :id'],
    'datos' => [':id' => 5]
]);

// Intento de DELETE sin condiciones (bloqueado)
$resultado = $db->realizarDelete([
    'tabla' => 'usuarios',
    'condiciones' => [],
    'datos' => []
]);
// Devuelve codRespuesta: 1002 con mensaje de advertencia
```

#### SQL Generado

```sql
DELETE FROM usuarios WHERE id = :id
```

#### Códigos de Respuesta Especiales

| Código | Descripción |
|--------|-------------|
| `1000` | DELETE exitoso |
| `1001` | Error en la consulta |
| `1002` | **Advertencia**: No se permiten DELETE sin condiciones |

---

### 6. `ejecutarFuncion(array $arraySQL = []): array`

Ejecuta una función almacenada de MariaDB y retorna su código de salida.

#### Estructura de `$arraySQL`

```php
$arraySQL = [
    'funcion' => 'miFuncion(:p1, :p2)',
    'datos'   => [':p1' => 'valor1', ':p2' => 'valor2']
];
```

#### Ejemplo

```php
$resultado = $db->ejecutarFuncion([
    'funcion' => 'validarUsuario(:usuario, :clave)',
    'datos'   => [
        ':usuario' => 'juan',
        ':clave'   => 'hash_password'
    ]
]);

if ($resultado['estado'] === 'OK') {
    echo "Código de función: " . $resultado['codRespuesta'];
}
```

#### SQL Generado

```sql
SELECT validarUsuario(:usuario, :clave) AS funcion_retorno
```

---

## Formato de Respuesta Estándar

Todos los métodos devuelven un array con esta estructura:

```php
[
    'estado'         => 'OK' | 'Error',
    'codRespuesta'   => '1000' | '1001' | '1002',
    'resultado'      => [],      // Array de resultados
    'totalRegistros' => 0,       // Cantidad de registros devueltos
    'totalFinal'     => 0,       // Total real (solo en listados paginados)
    'pagina'         => 0,       // Página actual (solo en listados paginados)
    'totalPaginas'   => 0,       // Total de páginas (solo en listados paginados)
    'excepcion'      => ''       // Mensaje de error si ocurrió
]
```

### Códigos de Respuesta

| Código | Significado |
|--------|-------------|
| `1000` | Operación exitosa |
| `1001` | Error en la consulta (PDOException) |
| `1002` | Advertencia (ej: DELETE sin condiciones) |

---

## Ejemplos Avanzados

### SELECT con Múltiples JOINs

```php
$resultado = $db->traerListadoCompleto([
    'tabla' => 'usuarios u',
    'campos' => ['u.id', 'u.nombre', 'p.numero', 'd.nombre AS departamento'],
    'conjuntos' => [
        ['relacion' => 'INNER JOIN', 'tabla' => 'pedidos p', 'condicion' => 'u.id = p.usuario_id'],
        ['relacion' => 'LEFT JOIN', 'tabla' => 'departamentos d', 'condicion' => 'p.departamento_id = d.id']
    ],
    'condiciones' => ['u.activo = :activo', 'p.fecha >= :fecha'],
    'orden' => [
        ['campo' => 'u.nombre', 'orden' => 'ASC'],
        ['campo' => 'p.fecha', 'orden' => 'DESC']
    ],
    'limite' => ['inicio' => 0, 'registrosPorPagina' => 50],
    'datos' => [':activo' => 1, ':fecha' => '2024-01-01']
]);
```

### SELECT con GROUP BY y Funciones de Agregación

```php
$resultado = $db->traerListadoCompleto([
    'tabla' => 'ventas',
    'campos' => ['producto_id', 'SUM(cantidad) AS total_vendido', 'COUNT(*) AS veces_vendido'],
    'condiciones' => ['fecha BETWEEN :inicio AND :fin'],
    'agrupar' => ['producto_id'],
    'orden' => [['campo' => 'total_vendido', 'orden' => 'DESC']],
    'limite' => ['inicio' => 0, 'registrosPorPagina' => 10],
    'datos' => [':inicio' => '2024-01-01', ':fin' => '2024-12-31']
]);
```

### Características Específicas de MariaDB

#### Tablas de Sistema Versionadas (System-Versioned Tables)

```php
// Consultar datos históricos (MariaDB 10.3+)
$resultado = $db->ejecutarConsulta(
    "SELECT * FROM usuarios FOR SYSTEM_TIME AS OF TIMESTAMP :timestamp",
    [':timestamp' => '2024-01-01 00:00:00']
);

// Consultar rango temporal
$resultado = $db->ejecutarConsulta(
    "SELECT * FROM usuarios 
     FOR SYSTEM_TIME BETWEEN :inicio AND :fin",
    [':inicio' => '2024-01-01 00:00:00', ':fin' => '2024-12-31 23:59:59']
);
```

#### Common Table Expressions (CTE) - MariaDB 10.2+

```php
$resultado = $db->ejecutarConsulta(
    "WITH RECURSIVE arbol AS (
        SELECT id, nombre, padre_id FROM categorias WHERE padre_id IS NULL
        UNION ALL
        SELECT c.id, c.nombre, c.padre_id 
        FROM categorias c 
        INNER JOIN arbol a ON c.padre_id = a.id
    )
    SELECT * FROM arbol ORDER BY nombre"
);
```

#### Window Functions - MariaDB 10.2+

```php
$resultado = $db->ejecutarConsulta(
    "SELECT 
        producto_id,
        fecha,
        cantidad,
        SUM(cantidad) OVER (PARTITION BY producto_id ORDER BY fecha) AS acumulado,
        ROW_NUMBER() OVER (PARTITION BY producto_id ORDER BY cantidad DESC) AS ranking
    FROM ventas
    WHERE fecha >= :fecha",
    [':fecha' => '2024-01-01']
);
```

### Transacción Manual (usando `ejecutarConsulta`)

```php
try {
    $db->conexion->beginTransaction();
    
    $db->ejecutarConsulta(
        "INSERT INTO pedidos (usuario_id, total) VALUES (:usuario_id, :total)",
        [':usuario_id' => 5, ':total' => 150.00]
    );
    
    $db->ejecutarConsulta(
        "UPDATE usuarios SET saldo = saldo - :total WHERE id = :usuario_id",
        [':total' => 150.00, ':usuario_id' => 5]
    );
    
    $db->conexion->commit();
    echo "Transacción exitosa";
} catch (Exception $e) {
    $db->conexion->rollBack();
    echo "Error: " . $e->getMessage();
}
```

### Debug: Imprimir SQL Generado

```php
$resultado = $db->traerListadoCompleto([
    'tabla' => 'usuarios',
    'campos' => ['id', 'nombre'],
    'condiciones' => ['activo = :activo'],
    'datos' => [':activo' => 1],
    'imprimirSQL' => true  // Imprime el SQL antes de ejecutar
]);
```

---

## Propiedades de la Clase

| Propiedad | Tipo | Descripción |
|-----------|------|-------------|
| `$servidor` | protected | Host o IP del servidor MariaDB |
| `$puerto` | protected | Puerto TCP del servicio MariaDB |
| `$base` | protected | Nombre de la base de datos |
| `$usuario` | protected | Usuario de la base de datos |
| `$clave` | protected | Clave del usuario |
| `$conexion` | protected | Objeto PDO de conexión |

---

## Herencia

```
tatuGenerico (clase base)
    └── tatuMariaDB (esta clase)
```

### Métodos Heredados de `tatuGenerico`

- `formatoArrayConsulta()`: Devuelve la estructura estándar para consultas
- `formatoArrayRetorno()`: Devuelve la estructura estándar para respuestas

---

## Mejores Prácticas

1. **Siempre usar placeholders** para prevenir inyección SQL
2. **Verificar el `estado`** antes de usar los resultados
3. **Usar transacciones** para operaciones múltiples
4. **Aprovechar la paginación** en listados grandes
5. **Aprovechar características de MariaDB** como tablas versionadas y CTEs
6. **Manejar excepciones** apropiadamente

---

## Véase También

- [TatuDB README](../REDME.md)
- [TatuMySQL](./TatuMySQL.md)
- [tatuGenerico](../tatuGenerico/tatuGenerico.php)
- [Documentación oficial de MariaDB](https://mariadb.com/docs/)
