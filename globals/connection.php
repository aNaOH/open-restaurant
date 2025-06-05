<?php
// Clase para gestionar la conexión y operaciones con la base de datos
class Connection {
    // Constantes para tipos de acciones de base de datos
    const DBACTION_INSERT = "INSERT INTO"; // Acción de inserción
    const DBACTION_UPDATE = "UPDATE";      // Acción de actualización
    const DBACTION_DELETE = "DELETE";      // Acción de borrado
    const DBACTION_SELECT = "SELECT";      // Acción de selección

    // Constante para modificador de consulta "IN"
    const DBMODIFIER_IN = "IN";

    // Constantes para búsqueda con LIKE
    const DBSEARCH_START = "start"; // Buscar que empieza por
    const DBSEARCH_END = "end";     // Buscar que termina por
    const DBSEARCH_BOTH = "both";   // Buscar que contiene

    // Constantes para ordenación
    const DBORDER_ASC = "ASC";      // Orden ascendente
    const DBORDER_DESC = "DESC";    // Orden descendente

    /**
     * Crea una base de datos y devuelve la conexión a ella.
     * @param string $host Host de la base de datos.
     * @param string $dbname Nombre de la base de datos.
     * @param string $user Usuario de la base de datos.
     * @param string $password Contraseña del usuario.
     * @return PDO|bool Retorna la conexión a la base de datos o false en caso de error.
     */
    public static function createDB($host, $dbname, $user, $password): PDO|bool {
        try {
            $dsn = "mysql:host=$host";
            $dbConn = new PDO($dsn, $user, $password);
            $dbConn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $dbConn->exec("CREATE DATABASE IF NOT EXISTS `$dbname`");
            // Conecta a la base de datos recién creada
            return self::connectToDB($host, $dbname, $user, $password);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Conecta a la base de datos usando credenciales dadas.
     * @param string $host Host de la base de datos.
     * @param string $dbname Nombre de la base de datos.
     * @param string $user Usuario de la base de datos.
     * @param string $password Contraseña del usuario.
     * @return PDO|bool Retorna la conexión o false en caso de error.
     */
    public static function connectToDB($host, $dbname, $user, $password): PDO|bool {
        try {
            $dsn = "mysql:host=$host;dbname=$dbname";
            return new PDO($dsn, $user, $password);
        } catch (PDOException $e) {
            echo $e->getMessage();
            return false;
        }
    }

    /**
     * Inserta datos en una tabla.
     * @param PDO $dbConn Conexión activa.
     * @param string $table Nombre de la tabla.
     * @param array $data Datos a insertar.
     * @return int Número de filas afectadas.
     */
    public static function doInsert($dbConn, $table, $data): int {
        return self::executeSql($dbConn, self::DBACTION_INSERT, $table, $data);
    }

    /**
     * Actualiza datos en una tabla.
     * @param PDO $dbConn Conexión activa.
     * @param string $table Nombre de la tabla.
     * @param array $data Datos a actualizar.
     * @param array $conditions Condiciones para el WHERE.
     * @return int Número de filas afectadas.
     */
    public static function doUpdate($dbConn, $table, $data, $conditions = []): int {
        return self::executeSql($dbConn, self::DBACTION_UPDATE, $table, $data, $conditions);
    }

    /**
     * Elimina datos de una tabla.
     * @param PDO $dbConn Conexión activa.
     * @param string $table Nombre de la tabla.
     * @param array $conditions Condiciones para el WHERE.
     * @return int Número de filas afectadas.
     */
    public static function doDelete($dbConn, $table, $conditions = []): int {
        return self::executeSql($dbConn, self::DBACTION_DELETE, $table, [], $conditions);
    }

    /**
     * Selecciona datos de una tabla.
     * @param PDO $dbConn Conexión activa.
     * @param string $table Nombre de la tabla.
     * @param array $conditions Condiciones para el WHERE.
     * @return array Resultado de la consulta en forma de array asociativo.
     */
    public static function doSelect($dbConn, $table, $conditions = []): array {
        return self::executeSql($dbConn, self::DBACTION_SELECT, $table, [], $conditions)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Ejecuta una consulta SQL (INSERT, UPDATE, DELETE, SELECT).
     * @param PDO $dbConn Conexión activa.
     * @param string $action Acción SQL.
     * @param string $table Tabla objetivo.
     * @param array $data Datos a insertar/actualizar.
     * @param array $conditions Condiciones para el WHERE.
     * @return PDOStatement|int Retorna el PDOStatement o el número de filas afectadas.
     */
    private static function executeSql($dbConn, $action, $table, $data, $conditions = []): PDOStatement|int {
        try {
            $sql = self::generateSql($action, $table, $data, $conditions);
            $stmt = $dbConn->prepare($sql);

            // Enlaza los datos a los parámetros de la consulta
            foreach ($data as $key => $value) {
                $stmt->bindValue(":$key", $value);
            }

            // Enlaza las condiciones al WHERE
            foreach ($conditions as $key => $value) {
                if (is_array($value)) {
                    $stmt->bindValue(":" . $value['param'], $value['value']);
                } else {
                    $stmt->bindValue(":$key", $value);
                }
            }

            $stmt->execute();
            return ($action === self::DBACTION_SELECT) ? $stmt : $stmt->rowCount();
        } catch (PDOException $e) {
            die("Error: " . $e->getMessage());
        }
    }

    /**
     * Genera la sentencia SQL según la acción.
     * @param string $action Acción SQL.
     * @param string $table Tabla objetivo.
     * @param array $data Datos a insertar/actualizar.
     * @param array $conditions Condiciones para el WHERE.
     * @return string Sentencia SQL generada.
     */
    private static function generateSql($action, $table, $data, $conditions): string {
        switch ($action) {
            case self::DBACTION_INSERT:
                $columns = implode(", ", array_keys($data));
                $values = ":" . implode(", :", array_keys($data));
                return "$action `$table` ($columns) VALUES ($values)";
            case self::DBACTION_UPDATE:
                $setClause = self::generateSetClause($data);
                $conditionClause = self::generateConditionClause($conditions);
                return "$action `$table` SET $setClause $conditionClause";
            case self::DBACTION_DELETE:
                $conditionClause = self::generateConditionClause($conditions);
                return "$action FROM `$table` $conditionClause";
            case self::DBACTION_SELECT:
                $conditionClause = self::generateConditionClause($conditions);
                return "$action * FROM `$table` $conditionClause";
            default:
                throw new Exception("Acción no válida");
        }
    }

    /**
     * Genera el SET para las consultas de UPDATE.
     * @param array $data Datos a actualizar.
     * @return string Cláusula SET generada.
     */
    private static function generateSetClause($data): string {
        return implode(", ", array_map(fn($column) => "$column = :$column", array_keys($data)));
    }

    /**
     * Genera la cláusula WHERE para consultas SQL.
     * @param array $conditions Condiciones para el WHERE.
     * @return string Cláusula WHERE generada.
     */
    private static function generateConditionClause($conditions): string {
        if (empty($conditions)) {
            return "";
        }

        $conditionClauses = [];
        foreach ($conditions as $column => $value) {
            if (is_array($value) && isset($value['modifier']) && $value['modifier'] === self::DBMODIFIER_IN) {
                $inValues = implode(',', array_fill(0, count($value['value']), '?'));
                $conditionClauses[] = "$column IN ($inValues)";
            } else {
                $conditionClauses[] = "$column = :$column";
            }
        }

        return "WHERE " . implode(" AND ", $conditionClauses);
    }

    /**
     * Realiza una búsqueda en una tabla con soporte de paginación y ordenación.
     * @param PDO $dbConn Conexión activa.
     * @param string $table Nombre de la tabla.
     * @param string $query Patrón de búsqueda.
     * @param string $searchColumn Columna donde buscar.
     * @param string $option Tipo de búsqueda: DBSEARCH_START, DBSEARCH_END, DBSEARCH_BOTH.
     * @param array $conditions Condiciones adicionales para el WHERE.
     * @param int|null $perPage Resultados por página.
     * @param int|null $page Número de página.
     * @param array $orderBy Ordenamiento.
     * @return PDOStatement Resultado de la búsqueda.
     */
    public static function searchInTable($dbConn, $table, $query, $searchColumn, $option = self::DBSEARCH_END, $conditions = [], $perPage = null, $page = null, $orderBy = []): PDOStatement {
        $sql = "SELECT * FROM `$table`";

        // Agrega condiciones al WHERE si existen
        if (!empty($query)) {
            $sql .= " WHERE ";
            if (count($conditions) > 0) {
                $sql .= implode(' AND ', array_map(fn($field) => "$field = :$field", array_keys($conditions))) . ' AND ';
            }

            $sql .= "$searchColumn LIKE :query";
        }

        // Agrega ordenamiento si corresponde
        if (!empty($orderBy)) {
            $sql .= " ORDER BY " . implode(', ', array_map(fn($order) => "{$order['column']} {$order['method']}", $orderBy));
        }

        // Agrega paginación si corresponde
        if ($perPage !== null && $page !== null) {
            $offset = ($page - 1) * $perPage;
            $sql .= " LIMIT :per_page OFFSET :offset";
        }

        $stmt = $dbConn->prepare($sql);

        // Enlaza condiciones
        foreach ($conditions as $field => $value) {
            $stmt->bindParam(":$field", $value);
        }

        // Enlaza el query para la búsqueda con LIKE
        if (!empty($query)) {
            switch ($option) {
                case self::DBSEARCH_START:
                    $query = "%$query";
                    break;
                case self::DBSEARCH_END:
                    $query = "$query%";
                    break;
                case self::DBSEARCH_BOTH:
                    $query = "%$query%";
                    break;
                default:
                    throw new InvalidArgumentException('Opción de búsqueda no válida');
            }
            $stmt->bindParam(':query', $query);
        }

        // Enlaza parámetros de paginación
        if ($perPage !== null && $page !== null) {
            $stmt->bindParam(':per_page', $perPage, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        }

        $stmt->execute();
        return $stmt;
    }

    /**
     * Ejecuta una consulta SQL personalizada.
     * @param PDO $dbConn Conexión a la base de datos.
     * @param string $sql Consulta SQL con marcadores "?".
     * @param array $params Valores para enlazar a los marcadores.
     * @return PDOStatement Resultado de la consulta.
     */
    public static function customQuery(PDO $dbConn, string $sql, array $params = []): PDOStatement {
        try {
            $stmt = $dbConn->prepare($sql);
            // Enlaza valores a los marcadores de posición "?"
            foreach ($params as $key => $value) {
                $stmt->bindValue($key + 1, $value);
            }
            $stmt->execute();
            return $stmt;
        } catch (PDOException $e) {
            die("Error: " . $e->getMessage());
        }
    }

    /**
     * Ejecuta un script SQL para crear tablas y datos iniciales.
     * @param PDO $dbConn Conexión a la base de datos.
     * @param string $sqlScript Ruta al archivo SQL.
     * @return bool Retorna true si se ejecutó correctamente, false en caso contrario.
     */
    public static function executeSqlScript($dbConn, $sqlScript): bool {
        try {
            $sql = file_get_contents($sqlScript);
            $dbConn->exec($sql);
            return true;
        } catch (Exception $e) {
            echo "Error al ejecutar el script SQL: " . $e->getMessage();
            return false;
        }
    }
}

