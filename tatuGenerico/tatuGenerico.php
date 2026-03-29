<?php


/**
 *
 * @autor 					: Damian Delgado
 * @Fecha Creacion			: 27/07/2021
 * @Ultimo Cambio 			: Damian Delgado
 * @Fecha Ultimo Cambio		: 03/02/2026
 * @Fecha Ultima Revision	: 03/02/2026
 *
 */


 class tatuGenerico{


	// -------------------------------------------------------------------------------------------- \\
	// -------------------------------------------------------------------------------------------- \\
	// -------------------------------------------------------------------------------------------- \\


	/**
		@return mixed
	*/
	public function formatoArrayConsulta():array{

		$arraySQL = [
			// Nombre de la tabla a consultar
			// Ej: tabla1
			"tabla" 		=> "",
			// Campos que quiero devolver en la respuesta
			// EJ: [ 'id', 'nombreCompleto as Nombre', 'count(email) as totalEmail' ]
			"campos" 		=> [],
			// Son los joins que vamos a realizar
			// EJ: [ ['relacion' => 'INNER JOIN', 'tabla' => 'tabla2', 'condicion' => 'tabla1.id = tabla2.id'], .... ]
			"conjuntos" 	=> [],
			// Es como vamos a filtrar el sql
			// EJ: [ 'id = 5', 'nombre LIKE %damian%', .... ]
			"condiciones" 	=> [],
			// Es como vamos a ordenar el sql
			// EJ: [ 'campo' => 'id', 'orden' => 'desc']
			"orden" 		=> [],
			// Es como vamos a agrupar el sql
			// EJ: [ 'id', 'Nombre', .... ]
			"agrupar" 		=> [],
			// Es el limite en sql
			// EJ: pagina es desde donde empieza y registrosPorPagina es cuantos registros traer
			"limite" 		=> [
				"inicio" => 0,
				"registrosPorPagina" => 50
			],
			//Son los valores para pasar
			//EJ: [ ':id' => 5, ':nombre' => 'damian', .... ]
			"datos" 		=> [],
			// Es function a ejecutar
			// EJ: 'nombreFuncion(:campo1, :campo2, .... )'
			"funcion" 		=> "",
		];
		return $arraySQL;
	}

	// -------------------------------------------------------------------------------------------- \\
	// -------------------------------------------------------------------------------------------- \\
	// -------------------------------------------------------------------------------------------- \\

	/**
		@return mixed
	*/
	protected function formatoArrayRetorno():array{

		$arrayRetorno = [
			// Estado de la operacion OK para correcto ERROR para error
			"estado" 			=> "",
			// Codigo de respuesta 1000 para OK y el resto para errores
			"codRespuesta" 		=> "",
			// Es el resultado de la consulta
			"resultado" 		=> [],
			// Es el total de registros traidos
			"totalRegistros" 	=> 0,
			// Es el total final de registros que cumplen la condicion
			"totalFinal" 		=> 0,
			// Es el numero de pagina
			"pagina" 			=> 0,
			// Es total de paginas que tiene ese consulta
			"totalPaginas"		=> 0,
			// Es la excepcion del catch en caso de fallar la consulta
			"excepcion" 		=> ""
		];
		return $arrayRetorno;

	}

	// -------------------------------------------------------------------------------------------- \\
	// -------------------------------------------------------------------------------------------- \\
	// -------------------------------------------------------------------------------------------- \\


}

?>
