<?php

/**
 * @autor 					: Damian Delgado
 * @Fecha Creacion			: 27/07/2021
 * @Ultimo Cambio 			: Damian Delgado
 * @Fecha Ultimo Cambio		: 27/07/2021
 * @Fecha Ultima Revision	: 27/07/2021
 *
 */


class tatuDB {

	public $version = '1.1.1';

	public $base;

	public $oTatuDB;

	public function constructor($motor, $parametros):object{

		switch($motor ){
			case "tatuMySQL":
				$path = __DIR__."/MySQL/tatuMySQL.php";
				if(file_exists($path)){
					$ruta = "tatuDB/MySQL/tatuMySQL.php";
				}
				$base = "tatuMysql";
				break;
			case "tatuMariaDB":
				$path = __DIR__."/MySQL/MariaDB.php";
				if(file_exists($path)){
					$ruta = "tatuDB/MySQL/MariaDB.php";
				}
				$base = "MariaDB";
				break;	
			case "tatuSQLite":
	
			case "tatuPostgreSQL":
			
			case "tatuRedisNoSQL":
			
			case "default":

				break;
		}

		include_once($ruta);
		$oTatuDB = new $base();
		$oTatuDB->constructor($parametros);

		return $oTatuDB;

	}//constructor

}





?>
