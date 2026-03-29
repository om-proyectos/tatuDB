
<?php

/**
 * @autor 					: Damian Delgado
 * @Fecha Creacion			: 03/02/2026
 * @Ultimo Cambio 			: Damian Delgado
 * @Fecha Ultimo Cambio		: 03/02/2026
 * @Fecha Ultima Revision	: 03/02/2026
 *
 */

 /**
 * Clase de acceso a datos para MySQL basada en PDO.
 *
 * Provee métodos genéricos para ejecutar consultas SELECT, INSERT, UPDATE,
 * DELETE y funciones, utilizando un formato de entrada estandarizado heredado de
 *
 * Responsabilidades:
 * - Gestionar la conexión PDO a MySQL.
 * - Construir SQLs a partir de estructuras de arrays bien definidas.
 * - Estandarizar el formato de retorno con estado, código, resultado, totales y excepción.
 *
 * Requisitos: extensión PDO habilitada y driver pdo_mysql.
 *
 * @package tatuDB\MySQL
 */

require_once(__DIR__."/../tatuGenerico/tatuGenerico.php");

class tatuMySQL extends tatuGenerico{

	/** Host o IP del servidor MySQL. */
	protected $servidor;

	/** Puerto TCP del servicio MySQL. */
	protected $puerto;

	/** Nombre de la base de datos a utilizar. */
	protected $base;

	/** Usuario con permisos sobre la base de datos. */
	protected $usuario;

	/** Clave del usuario de base de datos. */
	protected $clave;

	/**
	 * Conexión PDO a MySQL.
	 * @var PDO
	 */
	protected $conexion;
	// -------------------------------------------------------------------------------------------- \\
	// -------------------------------------------------------------------------------------------- \\
	// -------------------------------------------------------------------------------------------- \\

	/**
	* 
	* Inicializa los parámetros de conexión y crea la conexión PDO.
	*
	* Ejemplo de $parametros:
	* [
	*   'servidor' => '127.0.0.1',
	*   'puerto'   => 3306,
	*   'base'     => 'mi_base',
	*   'usuario'  => 'root',
	*   'clave'    => 'secret'
	* ]
	*
	* @param array $parametros Parámetros de conexión.
	* @return void
	*
	*/
	public function constructor(array $parametros):void{

		$this->servidor = $parametros['servidor'];
		$this->puerto	= $parametros['puerto'];
		$this->base		= $parametros['base'];
		$this->usuario	= $parametros['usuario'];
		$this->clave	= $parametros['clave'];

		try {
			$this->conexion = new PDO(
				"mysql:host={$this->servidor};port={$this->puerto};dbname={$this->base};", 
				$this->usuario,
				$this->clave
			);
			$this->conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		} catch (PDOException $e) {
			die("Error de conexión: " . $e->getMessage());
		}

	}//constructor

	// -------------------------------------------------------------------------------------------- \\
	// -------------------------------------------------------------------------------------------- \\
	// -------------------------------------------------------------------------------------------- \\

	/**
	* 
	* Ejecuta una consulta SQL directa (prepared statement) y retorna el resultado.
	*
	* @param string $sql          SQL a ejecutar con placeholders.
	* @param array  $arrayExecute Parámetros asociativos para bind de la consulta.
	* @return array Estructura estándar: estado, codRespuesta, resultado, totalRegistros, Exception.
	*
	*/
	public function ejecutarConsulta(string $sql, array $arrayExecute = []):array{

		$retorno = $this->formatoArrayRetorno();

		try {
			
			$mysqlPdo = $this->conexion->prepare($sql);
			$respuesta = $mysqlPdo->execute($arrayExecute);

			$retorno['estado'] 			= "OK";
			$retorno['codRespuesta']	= "1000";
			$retorno['resultado']		= $mysqlPdo->fetchAll(PDO::FETCH_ASSOC);
			$retorno['totalRegistros']	= $mysqlPdo->rowCount();

		}catch (Exception $e){

			$retorno['estado'] 			= "Error";
			$retorno['codRespuesta']	= "1001";
			$retorno['Exception']		= $e->getMessage();

		}

		return $retorno;

	}//ejecutarConsulta

	// -------------------------------------------------------------------------------------------- \\
	// -------------------------------------------------------------------------------------------- \\
	// -------------------------------------------------------------------------------------------- \\

	/**
	* 
	* Construye y ejecuta un SELECT con JOINs, WHERE, ORDER BY, GROUP BY y LIMIT a partir del formato estándar.
	*
	* Estructura esperada en $arraySQL (ver [tatuGenerico::formatoArrayListado](cci:1://file:///Users/ddelgado/Desktop/Damian/investigacion/programacion/PHP/orm_tatuDB/tatuDB/tatuGenerico/tatuGenerico.php:22:4-57:2)):
	* - tabla: string
	* - campos: string[]
	* - conjuntos: array<['relacion','tabla','condicion']>
	* - condiciones: string[]
	* - orden: array<['campo','orden']>
	* - agrupar: string[]
	* - limite: ['inicio'=>int,'registrosPorPagina'=>int]
	* - datos: array (valores para bind)
	* - funcion: string (opcional, no usado aquí)
	*
	* @param array $arraySQL Definición del SELECT a ejecutar.
	* @return array Estructura estándar con resultados paginados y totales.
	*
	*/
	public function traerListadoCompleto(array $arraySQL):array{
	
		$retorno = $this->formatoArrayRetorno();

		try {

			$sql = "SELECT ";
			$campoAux = '';
			foreach ($arraySQL['campos'] as $campo){
				$campoAux .= "{$campo},";
			}
			$sql .= substr($campoAux,  0, -1);
			$sql .= " FROM ".$arraySQL['tabla'];

			foreach ($arraySQL['conjuntos'] as $join){
				$sql .= " ".$join['relacion']." ".$join['tabla']." ON ".$join['condicion'];
			}
			$sqlWhere = "";
			foreach ($arraySQL['condiciones'] as $where){
				$sqlWhere .= ($sqlWhere == "")?" WHERE {$where}" : " AND {$where}";
			}
			$sql .= $sqlWhere;

			if(isset($arraySQL['imprimirSQL'])){
				print_r($sql);
				print_r("\n");
			}

			$totalSQL = "SELECT count(*) AS total FROM ({$sql}) AS tabla";
			$mysqlPdo = $this->conexion->prepare($totalSQL);
			$mysqlPdo->execute($arraySQL['datos']);
			$totalFinal= 0;
			foreach ($mysqlPdo->fetchAll() AS $total){
				$totalFinal = $total['total'];
			}

			if($arraySQL['orden'] != "" && count($arraySQL['orden']) > 0 ){
				$ordenAux = " ORDER BY";
				foreach ($arraySQL['orden'] as $orden){
					$ordenAux .= " ".$orden['campo']." ".$orden['orden'].",";
				}
				$sql .= substr($ordenAux,  0, -1);
			}
			if($arraySQL['agrupar'] != "" && count($arraySQL['agrupar']) > 0 ){
				$agruparAux = " GROUP BY";
				foreach ($arraySQL['agrupar'] as $agrupar){
					$agruparAux .= " {$agrupar},";
				}
			}

			if($arraySQL['limite'] != ""){
				$sql .= " LIMIT {$arraySQL['limite']['inicio']}, {$arraySQL['limite']['registrosPorPagina']}";
			}

			if(isset($arraySQL['imprimirSQL'])){
				print_r($sql);
				print_r("\n");
			}

			$mysqlPdo = $this->conexion->prepare($sql);
			$mysqlPdo->execute($arraySQL['datos']);

			$retorno['estado'] 			= "OK";
			$retorno['codRespuesta']	= "1000";
			$retorno['resultado']		= $mysqlPdo->fetchAll(PDO::FETCH_ASSOC);
			$retorno['totalRegistros']	= $mysqlPdo->rowCount();
			$retorno['totalFinal']		= $totalFinal;
			$retorno['pagina']			= $arraySQL['limite'];

		}catch (Exception $e){

			$retorno['estado'] 			= "Error";
			$retorno['codRespuesta']	= "1001";
			$retorno['Exception']		= $e->getMessage();

		}

		return $retorno;

	}//traerListado

	// -------------------------------------------------------------------------------------------- \\
	// -------------------------------------------------------------------------------------------- \\
	// -------------------------------------------------------------------------------------------- \\

	/**
	* 
	* Realiza un INSERT basado en el formato estándar.
	*
	* Requiere en $arraySQL:
	* - tabla: string
	* - campos: string[] (nombres de columnas)
	* - datos: array (clave=nombre campo sin dos puntos, valor=dato)
	*
	* @param array $arraySQL Definición del INSERT a ejecutar.
	* @return array Estructura estándar del resultado de la operación.
	*
	*/

	public function realizarInsert(array $arraySQL = []):array{

		$retorno = $this->formatoArrayRetorno();
		try {

			$sql = "INSERT INTO {$arraySQL['tabla']}";
			$campoAux = '(';
			foreach ($arraySQL['campos'] as $campo){
				$campoAux .= "{$campo},";
			}
			$sql .= substr($campoAux,  0, -1);
			$sql .= ")";
			
			$sqlValue = " VALUES (";
			foreach ($arraySQL['datos'] as $parametro => $valor){
				$sqlValue .= ":{$parametro},";
			}
			$sql .= substr($sqlValue,  0, -1);
			$sql .= ")";

			if(isset($arraySQL['imprimirSQL'])){
				print_r($sql);
				print_r("\n");
			}

			$mysqlPdo = $this->conexion->prepare($sql);
			$mysqlPdo->execute($arraySQL['datos']);

			$retorno['estado'] 			= "OK";
			$retorno['codRespuesta']	= "1000";
			$retorno['resultado']		= $mysqlPdo->fetchAll(PDO::FETCH_ASSOC);
			$retorno['totalRegistros']	= $mysqlPdo->rowCount();
			

		}catch (Exception $e){

			$retorno['estado'] 			= "Error";
			$retorno['codRespuesta']	= "1001";
			$retorno['Exception']		= $e->getMessage();

		}

		return $retorno;

	}//realizarInsert


	// -------------------------------------------------------------------------------------------- \\
	// -------------------------------------------------------------------------------------------- \\
	// -------------------------------------------------------------------------------------------- \\

	/**
	* Realiza un UPDATE basado en el formato estándar.
	*
	* Requiere en $arraySQL:
 	* - tabla: string
	* - campos: string[] (cada uno en formato "columna = :param")
	* - condiciones: string[] (WHERE concatenado con AND)
	* - datos: array (parámetros para bind)
	*
	* @param array $arraySQL Definición del UPDATE a ejecutar.
	* @return array Estructura estándar del resultado de la operación.
	*/
	public function realizarUpdate($arraySQL){

		$retorno = $this->formatoArrayRetorno();
		try {

			$sql = "UPDATE {$arraySQL['tabla']}";
			$campoAux = ' SET ';
			foreach ($arraySQL['campos'] as $campo){
				$campoAux .= "{$campo},";
			}
			$sql .= substr($campoAux,  0, -1);

			$sqlWhere = "";
			foreach ($arraySQL['condiciones'] as $where){
				$sqlWhere .= ($sqlWhere == "")?" WHERE {$where}" : " AND {$where}";
			}
			$sql .= $sqlWhere;

			if(isset($arraySQL['imprimirSQL'])){
				print_r($sql);
				print_r("\n");
			}

			$mysqlPdo = $this->conexion->prepare($sql);
			$mysqlPdo->execute($arraySQL['datos']);

			$retorno['estado'] 			= "OK";
			$retorno['codRespuesta']	= "1000";
			$retorno['resultado']		= $mysqlPdo->fetchAll(PDO::FETCH_ASSOC);
			$retorno['totalRegistros']	= $mysqlPdo->rowCount();

		}catch (Exception $e){

			$retorno['estado'] 			= "Error";
			$retorno['codRespuesta']	= "1001";
			$retorno['Exception']		= $e->getMessage();

		}

		return $retorno;

	}//realizarUpdate

	// -------------------------------------------------------------------------------------------- \\
	// -------------------------------------------------------------------------------------------- \\
	// -------------------------------------------------------------------------------------------- \\

	/**
	* 
	* Realiza un DELETE basado en el formato estándar, validando que existan condiciones.
	*
	* Requiere en $arraySQL:
	* - tabla: string
	* - condiciones: string[] (obligatorias; se bloquea si están vacías)
	* - datos: array
	*
	* @param array $arraySQL Definición del DELETE a ejecutar.
	* @return array Estructura estándar del resultado de la operación.
	*
	*/
	public function realizarDelete($arraySQL){

		$retorno = $this->formatoArrayRetorno();
		try {

			if(count($arraySQL['condiciones']) > 0 ){

				$sql = "DELETE FROM {$arraySQL['tabla']}";
			
				$sqlWhere = "";
				foreach ($arraySQL['condiciones'] as $where){
					$sqlWhere .= ($sqlWhere == "")?" WHERE {$where}" : " AND {$where}";
				}
				$sql .= $sqlWhere;
				
				if(isset($arraySQL['imprimirSQL'])){
					print_r($sql);
					print_r("\n");
				}

				$mysqlPdo = $this->conexion->prepare($sql);
				$mysqlPdo->execute($arraySQL['datos']);

				$retorno['estado'] 			= "OK";
				$retorno['codRespuesta']	= "1000";
				$retorno['resultado']		= $mysqlPdo->fetchAll(PDO::FETCH_ASSOC);
				$retorno['totalRegistros']	= $mysqlPdo->rowCount();

			}else{

				$retorno['estado'] 			= "OK";
				$retorno['codRespuesta']	= "1002";
				$retorno['Exception']		= "No se permiten eliminar todos los registros de una tabla.";
			}

		}catch (Exception $e){

			$retorno['estado'] 			= "Error";
			$retorno['codRespuesta']	= "1001";
			$retorno['Exception']		= $e->getMessage();

		}

		return $retorno;

	}//realizarDelete

	// -------------------------------------------------------------------------------------------- \\
	// -------------------------------------------------------------------------------------------- \\
	// -------------------------------------------------------------------------------------------- \\


	/**
	* 
	* Ejecuta una función MySQL y retorna su código de salida en 'codRespuesta'.
	*
	* Requiere en $arraySQL:
	* - funcion: string (por ejemplo 'miFuncion(:p1, :p2)')
	* - datos: array (parámetros para bind)
	*
	* @param array $arraySQL Definición de la función a ejecutar.
	* @return array Estructura estándar con 'estado' y 'codRespuesta'. 
	*
	*/
	public function ejecutarFuncion($arraySQL = array()):array{

		$retorno = $this->formatoArrayRetorno();
		try {

			$sqlFuncion = "SELECT {$arraySQL['funcion']} AS funcion_retorno";
			$mysqlPdo = $this->conexion->prepare($sqlFuncion);
			$mysqlPdo->execute($arraySQL['datos']);
			$respuesta = $mysqlPdo->fetchAll(PDO::FETCH_ASSOC);			
			$retorno['estado'] 			= "OK";
			if(isset($respuesta[0]['funcion_retorno'])){
				$codRespuesta = $respuesta[0]['funcion_retorno'];
			}
			$retorno['codRespuesta']	= $codRespuesta;
		

		}catch (Exception $e){

			$retorno['estado'] 		= "Error";
			$retorno['codRespuesta']= "1001";
			$retorno['Exception'] 	= $e->getMessage();

		}
		return $retorno;

	}//ejecutarFuncion

	// -------------------------------------------------------------------------------------------- \\
	// -------------------------------------------------------------------------------------------- \\
	// -------------------------------------------------------------------------------------------- \\


	
	

}



?>
