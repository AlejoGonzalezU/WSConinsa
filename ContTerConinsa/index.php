<?php
/***********************************************************************************************************************************/
// Tipo de archivo: Ejecutable
// Fecha de elaboración: Junio del 2019
// Fecha de modificación: Marzo del 2021
// Elaborado por: Coninsa Ramón H
// Funcionalidad: Servicio web que entrega información detallada sobre contactos 
// de terceros asociados a Coninsa Ramón H, registrados en Sinco.
/***********************************************************************************************************************************/

header('Content-Type: application/json; charset=iso-8859-1');
ini_set('memory_limit', '-1');

include("../include/config_VoIP.php");
include("../include/ConexionSqlSvr.php");
include("../include/tools.php");

// Se valida la inclusión del protocolo seguro (HTTPS)
if(!isSecure()) {
    echo '{"datosTercero": 0, "mensaje": "El consumo del servicio debe ser sobre protocolo seguro (HTTPS://)"}';
    exit();
}

// Se carga el listado de las IP que tienen permiso de acceso al servicio web, así como el Token de acceso
$ip_file = file_get_contents('permitidas.txt');
$permitidas = explode(", ", $ip_file);

// Se obtiene el Token de la URL utilizada
$array = explode("/", $_GET["token"]);

// Se verifica que los Token sean iguales y valida el tipo de consumo que se quiere hacer
if ($array[0] == "n9PXWiP8cl7*edhzPbr1sVAmQecWX2qN6kWIjOU2ByPXptcCMdqDhQNUa87c-xist*I" && $array[1] == "consultar") {

	// Se verifica si la IP está dentro de las permitidas
    if (in_array(getRealIP(), $permitidas)) {
		$nitEmpresa = '';
		$numFactura = '';

		try {
			if (strtoupper($array[2]) == "NIT"){
				//Consulta de contactos por NIT
				$nitEmpresa = $array[3];

				//Conexión con la DB
				$ConexionSqlSvr = new ConexionSqlSvr();

				// Llamado al procedimiento almacenado
				$variables = array('NIT');
				$param = array('s', $nitEmpresa);

				$ConexionSqlSvr->execSP('sp_ContactosTercerosMostrar', $variables, $param);

				// Almacenado del resultado en un array
				$query = $ConexionSqlSvr->resulArray();
				$cantidad = count($query);

				//Se recorre la consulta de la DB para imprimir la información obtenida
				if ($cantidad != 0) {
					//Se crea arreglo para almacenar un contacto por posición
					$contactos = array();

					for ($i = 0; $i < $cantidad; $i++){
						//Por cada contacto existente se genera un objeto que lo almacene y este se va agregando al arreglo
						//Es muy importante codificar SIEMPRE en utf8 los datos para que funcione el procedimiento
						$dContactos = new stdClass();
						$dContactos->Nombre = $query[$i]["SNombre"];
						$dContactos->NIT = $query[$i]["SNitTercero"];
						$dContactos->TipoContacto = $query[$i]["STipoContacto"];
						$dContactos->Cargo = $query[$i]["SCargo"];
						$dContactos->Correo = $query[$i]["SCorreo"];
						$dContactos->Celular = $query[$i]["SCelular"];
						$dContactos->Telefono = $query[$i]["STelefono"];
						$dContactos->Ciudad = $query[$i]["SCiudad"];
						
						//Se almacena el contacto encontrado en el arreglo
						$contactos[] = $dContactos;
					}

					$datosTercero = new stdClass();
					for ($i = 0; $i < $cantidad; $i++){
						//Se almacenan los datos del contacto principal
						if ($query[$i]["STipoContacto"] == 'Principal'){
							$datosTercero->NIT = $query[$i]["SNitTercero"];
							$datosTercero->RazonSocial = $query[$i]["SNombre"];
							break;
						}
					}

					$datosTercero->Contactos = $contactos;
					$datosTercero->NumDeContactos = $cantidad;

					if ($cantidad == 1)
						$datosTercero->Mensaje = 'Datos de ' . $cantidad . ' contacto, (desde direccion ip '.$ip.')';
					else
						$datosTercero->Mensaje = 'Datos de ' . $cantidad . ' contactos, (desde direccion ip '.$ip.')';

				} else {
					// Mensaje si la consulta con la DB no devolvió ningún resultado
					$datosTercero = new stdClass();
					$datosTercero->Mensaje = 'Datos de ' . $cantidad . ' empleados, (desde direccion ip '.getRealIP().')';
				}

			} else {
				// Mensaje si los parámetros de consumo son incorrectos
				$datosTercero = new stdClass();
				$datosTercero->Mensaje = 'Por favor revise los parámetros de consumo del servicio web, (desde direccion ip '.getRealIP().'). (Error 01).';
			}

		} catch(Exception $e) {
			// Mensaje si hubo un error en la ejecución de la consulta
			$datosTercero = new stdClass();
			$datosTercero->Mensaje = 'Ha ocurrido un error en la ejecución. Por favor, contacte al administrador del sistema.';
		}

	} else {
		// Mensaje si la IP no se encuentra dentro de las permitidas
		$datosTercero = new stdClass();
		$datosTercero->Mensaje = 'No tiene permiso para realizar esta petición, (desde direccion ip '.getRealIP().'). Póngase en contacto con el administrador del servicio web. (Error 02).';
	}

} else {
	// Mensaje si el token no corresponde o la opción de consumo es errada
	$datosTercero = new stdClass();
	$datosTercero->Mensaje = 'Por favor revise los parámetros de consumo del servicio web y que el token proporcionado sea el correcto, (desde direccion ip '.getRealIP().'). (Error 03)';
}

// Se codifica como JSON la respuesta para ser retornada al usuario
echo json_encode($datosTercero, true);

 ?>