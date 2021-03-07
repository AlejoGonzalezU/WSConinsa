<?php
/***********************************************************************************************************************************/
// Tipo de archivo: Ejecutable
// Fecha de elaboración: Diciembre de 2020
// Fecha de modificación: Marzo del 2021
// Elaborado por: T&P Coninsa RH
// Funcionalidad: Servicio web que entrega información en la estructura solicitada por Avanto.
/***********************************************************************************************************************************/

header('Content-Type: application/json; charset=iso-8859-1');
ini_set('memory_limit', '-1');

include("../include/config_DI.php");
include("../include/ConexionSqlSvr.php");
include("../include/tools.php");

$token_random = RandomString(Token_Avanto);

// Se valida la inclusión del protocolo seguro (HTTPS)
if(!isSecure()) {
    echo '{"clientes": 0, "mensaje": "El consumo del servicio debe ser sobre protocolo seguro (HTTPS://)"}';
    exit();
}

// Se carga el listado de las IP que tienen permiso de acceso al servicio web, así como los usuarios con permiso y sus claves
$ip_file = file_get_contents('direccionesip.txt');
$ips_allowed = explode(", ", $ip_file);

$users_file = file_get_contents('usuarios.txt');
$users_allowed = explode(", ", $users_file);

$keys_file = file_get_contents('claves.txt');
$keys_allowed = explode(", ", $keys_file);

// Se obtienen los dos primeros bytes de la IP, para que funcione con cualquier subneteo que se haga
$ip_cliente = getFirstBytesIP(getRealIP());

// Se obtienen los dos primeros bytes de las IP almacenadas en el archivo de permitidas
for ($i = 0; $i < count($ips_allowed); $i++){
	$ips_allowed[$i] = getFirstBytesIP($ips_allowed[$i]);
}

// Se obtiene la cadena de conexión recibida
$array = explode("/", $_SERVER['QUERY_STRING']);

// Se valida el tipo de consumo que se quiere hacer y que se haya enviado un usuario
if ($array[1] == "acceso" && $array[2]== "usuario") {

	// Se verifica el Token recibido y se consulta si la IP está dentro de las permitidas y el usuario corresponde con uno autorizado
	if (array_search($ip_cliente, $ips_allowed) && array_search($array[3], $users_allowed) && $array[0] == Token_Avanto) {

		// Se valida que haya un usuario enviado en el consumo del WS
		if (!isset($_SERVER['PHP_AUTH_USER']) && !$_SERVER['PHP_AUTH_USER']) {
			echo $_SERVER['PHP_AUTH_USER'].'<br>';

			// Si no se envió usuario, arroja error y finaliza la ejecución
			header('WWW-Authenticate: Basic realm="LOGIN REQUIRED"');
			header('HTTP/1.0 401 Unauthorized');

			// Configuración de JSON de salida
			$respuesta = new stdClass();
			$respuesta->Error = 2;
			$respuesta->Message = 'Debe especificar usuario y contraseña.';

			echo json_encode($respuesta);
			exit();
		}

		$accessOk = false;

		// Se validan los datos de autenticación del usuario, que deben estar incluidos en los arrays almacenados en el servidor
		if (array_search($_SERVER['PHP_AUTH_USER'], $users_allowed) && array_search($_SERVER['PHP_AUTH_PW'], $keys_allowed))
			$accessOk = true;

		// Se valida que haya un usuario enviado en el consumo del WS
		if (!$accessOk) {

			// Si no se validó la autenticación, arroja error y finaliza la ejecución
			header('WWW-Authenticate: Basic realm="WRONG PASSWORD"');
			header('HTTP/1.0 401 Unauthorized');

			// Se va almacenando la información en objetos para darla la estructura de un JSON
			$respuesta = new stdClass();
			$respuesta->Error = 2;
			$respuesta->Message = 'Error en usuario o clave para generar Token.';

			echo json_encode($respuesta);
			exit();
		} else {

			// Conexión con la DB
			$ConexionSqlSvr = new ConexionSqlSvr();

			// Llamado al procedimiento almacenado
			$variables = array('Opcion', 'WS', 'DirIP', 'Usuario', 'Random');
			$param = array('issss', 1, 'AVANTO', getRealIP(), $array[3], $token_random);

			$ConexionSqlSvr->execSP('sp_ABR_Avanto', $variables, $param);

			// Almacenado del resultado en un array
			$query = $ConexionSqlSvr->resulArray();
			$cantidad = count($query);

			if ($cantidad != 0) {
				// Se va almacenando la información en objetos para darla la estructura de un JSON
				$token = new stdClass();
				$token->Acceso = 1;
				$token->codigo = $query[0]["SToken"];
			}

            $token->mensaje = 'Token generado desde direccion ip '.getRealIP();

			// Se codifica como JSON la respuesta para ser retornada al usuario
			echo json_encode($token);

			// Se sale del Servicio Web para obligar a un segundo consumo con el Token ya generado
			exit();
		}
	} else {
			// Mensaje si la IP no se encuentra dentro de las permitidas
		$datosCliente = new stdClass();
		$datosCliente->mensaje = 'No tiene permiso para realizar esta petición, (desde direccion ip '.getRealIP().'). Póngase en contacto con el administrador del servicio web. (Error 02).';
		echo json_encode($datosCliente);
		exit();
	}
} elseif ($array[1] == "consultar") {
	if (array_search($ip_cliente, $ips_allowed)){

		// Conexión con la DB
		$ConexionSqlSvr = new ConexionSqlSvr();

		// Llamado al procedimiento almacenado
		$variables = array('Opcion', 'WS', 'DirIP', 'Usuario', 'Random');
		$param = array('issss', 2, 'AVANTO', getRealIP(), '', $array[0]);

		$ConexionSqlSvr->execSP('sp_ABR_Avanto', $variables, $param);

		// Almacenado del resultado en un array
		$query = $ConexionSqlSvr->resulArray();
		$cantidad = count($query);

		if ($cantidad != 0 && !is_null($query[0]["SToken"])) {
			if (strtoupper($array[2]) == "CONTRATO") {

				$sContrato = $array[3];

				// Llamado al procedimiento almacenado
				$variables = array('Opcion', 'WS', 'DirIP', 'Usuario', 'Random');
				$param = array('issss', 3, $sContrato, '', '', '');

				$ConexionSqlSvr->execSP('sp_ABR_Avanto', $variables, $param);

				// Almacenado del resultado en un array
				$query = $ConexionSqlSvr->resulArray();
				$cantidad = count($query);

				// Se recorre la consulta de la DB para imprimir la información obtenida
				if ($cantidad != 0) {
					
					// Se va almacenando la información en objetos para darla la estructura de un JSON
					$dCliente = new stdClass();
                    if (isset($query[0]["id_propietario"])) {
					    $dCliente->id_propietario = $query[0]["id_propietario"];
					    $dCliente->id_inmueble = $query[0]["id_inmueble"];
					    $dCliente->tipo_solicitante = $query[0]["tipo_solicitante"];

					    $dCliente->datos_propietario[0]->correo = $query[0]["correo"];
					    $dCliente->datos_propietario[0]->nombre = $query[0]["nombre"];
					    $dCliente->datos_propietario[0]->telefono = $query[0]["telefono"];
					    $dCliente->datos_propietario[0]->cuenta_bancaria = $query[0]["cuenta_bancaria"];
					    $dCliente->datos_propietario[0]->tipo_de_cuenta = $query[0]["tipo_de_cuenta"];
					    $dCliente->datos_propietario[0]->entidad_bancaria = $query[0]["entidad_bancaria"];
					    $dCliente->datos_propietario[0]->cedula_beneficiario = $query[0]["cedula_beneficiario"];
					    $dCliente->datos_propietario[0]->nombre_beneficiario = $query[0]["nombre_beneficiario"];
					    $dCliente->datos_propietario[0]->porcentaje_canon_propietario = $query[0]["porcentaje_canon_propietario"];

					    $dCliente->estado_centrales_riesgo = $query[0]["estado_centrales_riesgo"];
					    $dCliente->numero_propietarios = $query[0]["numero_propietarios"];
					    $dCliente->vigencia_contrato = $query[0]["vigencia_contrato"];

					    $dCliente->inmueble[0]->tipo_inmueble = $query[0]["tipo_inmueble"];
					    $dCliente->inmueble[0]->estrato = $query[0]["estrato"];
					    $dCliente->inmueble[0]->antiguedad_inmueble = $query[0]["antiguedad_inmueble"];
					    $dCliente->inmueble[0]->estado_contrato = $query[0]["EstadoContrato"];
					    $dCliente->inmueble[0]->direccion_inmueble = $query[0]["direccion_inmueble"];
					    $dCliente->inmueble[0]->ubicacion_inmueble = $query[0]["ubicacion_inmueble"];

					    $dCliente->valor_canon = $query[0]["valor_canon"];
					    $dCliente->valor_iva_canon = $query[0]["valor_iva_canon"];
					    $dCliente->renovaciones = $query[0]["renovaciones"];
					    $dCliente->fecha_inico_contrato = $query[0]["fecha_inico_contrato"];
					    $dCliente->fecha_final_contrato = $query[0]["fecha_final_contrato"];
					    $dCliente->tipo_contrato = $query[0]["tipo_contrato"];
					    $dCliente->numero_contrato = $query[0]["numero_contrato"];
					    $dCliente->tasa_comision = $query[0]["tasa_comision"];
					    $dCliente->aseguradora = $query[0]["aseguradora"];
					    $dCliente->url_respuesta = $query[0]["url_respuesta"];
					    $dCliente->url_redireccion = $query[0]["url_redireccion"];
					    $dCliente->valor_administracion = $query[0]["valor_administracion"];
					    $dCliente->administracion_paga_inmobiliaria = $query[0]["paga_inmobiliaria"];
					    $dCliente->ultima_fecha_pago = $query[0]["ultima_fecha_pago"];
					    $dCliente->dia_mes_pago_canon = $query[0]["dia_mes_pago_canon"];
					    $dCliente->numero_siniestros = $query[0]["numero_siniestros"];
					    $dCliente->numero_moras = $query[0]["numero_moras"];
					    $dCliente->altura_mora_max = $query[0]["altura_mora_max"];
					    $dCliente->numero_reparaciones = $query[0]["numero_reparaciones"];
					    $dCliente->zona_inmueble = $query[0]["zona_inmueble"];
					    $dCliente->credito_inmueble = $query[0]["credito_inmueble"];
					    $dCliente->llamados_de_atencion = $query[0]["llamados_de_atencion"];
					} else {
						$cantidad=0;
					}
                    $datosCliente = new stdClass();
					$datosCliente->datos_cliente = $dCliente;
					$datosCliente->numero_registros = $cantidad;
					
					if ($cantidad == 1)
						$datosCliente->mensaje = 'Datos de ' . $cantidad . ' cliente, (desde direccion ip '.getRealIP().')';
					else
						$datosCliente->mensaje = $query[0]["SRespuesta"] . ', (desde direccion ip '.getRealIP().')';
					
				} else {
					// Mensaje si la consulta con la DB no devolvió ningún resultado
					$datosCliente = new stdClass();
					$datosCliente->mensaje = 'Datos de ' . $cantidad . ' clientes, (desde direccion ip '.getRealIP().')';
				}

			}
		} else {
			// Mensaje si el token no corresponde o la opción de consumo es errada
			$datosCliente = new stdClass();
			$datosCliente->mensaje = 'Por favor revise que el token proporcionado sea el correcto, (desde direccion ip '.getRealIP().'). (Error 03).';
		}
	} else {
			// Mensaje si la IP no se encuentra dentro de las permitidas
		$datosCliente = new stdClass();
		$datosCliente->mensaje = 'No tiene permiso para realizar esta petición, (desde direccion ip '.getRealIP().'). Póngase en contacto con el administrador del servicio web. (Error 02).';
	}
} elseif ($array[1] == "consultar-v2") {
	if (array_search($ip_cliente, $ips_allowed)){

		// Conexión con la DB
		$ConexionSqlSvr = new ConexionSqlSvr();

		// Llamado al procedimiento almacenado
		$variables = array('Opcion', 'WS', 'DirIP', 'Usuario', 'Random');
		$param = array('issss', 2, 'AVANTO', getRealIP(), '', $array[0]);

		$ConexionSqlSvr->execSP('sp_ABR_Avanto', $variables, $param);

		// Almacenado del resultado en un array
		$query = $ConexionSqlSvr->resulArray();
		$cantidad = count($query);

		if ($cantidad != 0 && !is_null($query[0]["SToken"])) {
			if (strtoupper($array[2]) == "CONTRATO" && strtoupper($array[4]) == "NIT") {

				$sContrato = $array[3];
				$sNit = $array[5];

				// Llamado al procedimiento almacenado
				$variables = array('Opcion', 'WS', 'NIT');
				$param = array('iss', 3, $sContrato, $sNit);

				$ConexionSqlSvr->execSP('sp_ABR_Avanto2', $variables, $param);

				// Almacenado del resultado en un array
				$query = $ConexionSqlSvr->resulArray();
				$cantidad = count($query);

				// Se recorre la consulta de la DB para imprimir la información obtenida
				if ($cantidad != 0) {
					
					// Se va almacenando la información en objetos para darla la estructura de un JSON
					$dCliente = new stdClass();
                    if (isset($query[0]["id_propietario"])) {
					    $dCliente->id_propietario = $query[0]["id_propietario"];
					    $dCliente->user_avanto = $query[0]["user_avanto"];
					    $dCliente->id_inmobiliaria = $query[0]["id_inmobiliaria"];
					    $dCliente->id_inmueble = $query[0]["id_inmueble"];
					    $dCliente->tipo_solicitante = $query[0]["tipo_solicitante"];

					    $dCliente->datos_propietario[0]->tipo = $query[0]["tipo"];
					    $dCliente->datos_propietario[0]->correo = $query[0]["correo"];
					    $dCliente->datos_propietario[0]->nombre = $query[0]["nombre"];
					    $dCliente->datos_propietario[0]->direccion_propietario = $query[0]["direccion_propietario"];
					    $dCliente->datos_propietario[0]->ciudad_propietario = $query[0]["ciudad_propietario"];
					    $dCliente->datos_propietario[0]->telefono = $query[0]["telefono"];
					    $dCliente->datos_propietario[0]->cuenta_bancaria = $query[0]["cuenta_bancaria"];
					    $dCliente->datos_propietario[0]->tipo_de_cuenta = $query[0]["tipo_de_cuenta"];
					    $dCliente->datos_propietario[0]->entidad_bancaria = $query[0]["entidad_bancaria"];
					    $dCliente->datos_propietario[0]->cedula_beneficiario = $query[0]["cedula_beneficiario"];
					    $dCliente->datos_propietario[0]->nombre_beneficiario = $query[0]["nombre_beneficiario"];
					    $dCliente->datos_propietario[0]->titular_cuenta_beneficiario = $query[0]["titular_cuenta_beneficiario"];
					    $dCliente->datos_propietario[0]->porcentaje_canon_propietario = $query[0]["porcentaje_canon_propietario"];
					    $dCliente->datos_propietario[0]->juridico_nombre_representante = utf8_encode($query[0]["nombre_representante"]);
					    $dCliente->datos_propietario[0]->juridico_email_representante = $query[0]["email_representante"];
						$dCliente->datos_propietario[0]->juridico_documento_representante = $query[0]["documento_representante"];
					    $dCliente->datos_propietario[0]->juridico_telefono_representante = $query[0]["telefono_representante"];

						$dCliente->estado_centrales_riesgo = $query[0]["estado_centrales_riesgo"];
					    $dCliente->numero_propietarios = $query[0]["numero_propietarios"];
					    $dCliente->vigencia_contrato = $query[0]["vigencia_contrato"];

					    $dCliente->inmueble[0]->tipo_inmueble = $query[0]["tipo_inmueble"];
					    $dCliente->inmueble[0]->estrato = $query[0]["estrato"];
					    $dCliente->inmueble[0]->antiguedad_inmueble = $query[0]["antiguedad_inmueble"];
					    $dCliente->inmueble[0]->estado_contrato = $query[0]["estado_contrato"];
					    $dCliente->inmueble[0]->direccion_inmueble = $query[0]["direccion_inmueble"];
					    $dCliente->inmueble[0]->ubicacion_inmueble = $query[0]["ubicacion_inmueble"];

					    $dCliente->valor_canon = $query[0]["valor_canon"];
					    $dCliente->valor_iva_canon = $query[0]["valor_iva_canon"];
					    $dCliente->renovaciones = $query[0]["renovaciones"];
					    $dCliente->fecha_inico_contrato = $query[0]["fecha_inico_contrato"];
					    $dCliente->fecha_final_contrato = $query[0]["fecha_final_contrato"];
					    $dCliente->tipo_contrato = $query[0]["tipo_contrato"];
					    $dCliente->numero_contrato = $query[0]["numero_contrato"];
					    $dCliente->tasa_comision = $query[0]["tasa_comision"];
					    $dCliente->aseguradora = $query[0]["aseguradora"];
					    $dCliente->url_respuesta = $query[0]["url_respuesta"];
					    $dCliente->url_redireccion = $query[0]["url_redireccion"];
					    $dCliente->valor_administracion = $query[0]["valor_administracion"];
					    $dCliente->administracion_paga_inmobiliaria = $query[0]["paga_inmobiliaria"];
					    $dCliente->ultima_fecha_pago = $query[0]["ultima_fecha_pago"];
					    $dCliente->dia_mes_pago_canon = $query[0]["dia_mes_pago_canon"];
					    $dCliente->numero_siniestros = $query[0]["numero_siniestros"];
					    $dCliente->numero_moras = $query[0]["numero_moras"];
					    $dCliente->altura_mora_max = $query[0]["altura_mora_max"];
					    $dCliente->numero_reparaciones = $query[0]["numero_reparaciones"];
					    $dCliente->zona_inmueble = $query[0]["zona_inmueble"];
					    $dCliente->credito_inmueble = $query[0]["credito_inmueble"];
					    $dCliente->llamados_de_atencion = $query[0]["llamados_de_atencion"];
					} else {
						$cantidad=0;
					}
                    $datosCliente = new stdClass();
					$datosCliente->datos_cliente = $dCliente;
					$datosCliente->numero_registros = $cantidad;
					
					if ($cantidad == 1)
						$datosCliente->mensaje = 'Datos de ' . $cantidad . ' cliente, (desde direccion ip '.getRealIP().')';
					else
						$datosCliente->mensaje = $query[0]["SRespuesta"] . ', (desde direccion ip '.getRealIP().')';
					
				} else {
					// Mensaje si la consulta con la DB no devolvió ningún resultado
					$datosCliente = new stdClass();
					$datosCliente->mensaje = 'Datos de ' . $cantidad . ' clientes, (desde direccion ip '.getRealIP().')';
				}

			}
		} else {
			// Mensaje si el token no corresponde o la opción de consumo es errada
			$datosCliente = new stdClass();
			$datosCliente->mensaje = 'Por favor revise que el token proporcionado sea el correcto, (desde direccion ip '.getRealIP().'). (Error 03).';
		}
	} else {
			// Mensaje si la IP no se encuentra dentro de las permitidas
		$datosCliente = new stdClass();
		$datosCliente->mensaje = 'No tiene permiso para realizar esta petición, (desde direccion ip '.getRealIP().'). Póngase en contacto con el administrador del servicio web. (Error 02).';
	}
} else {
	// Mensaje si los parámetros de consumo son incorrectos
	$datosCliente = new stdClass();
	$datosCliente->mensaje = 'Por favor revise los parámetros de consumo del servicio web, (desde direccion ip '.getRealIP().'). (Error 05).';
}

// Se codifica como JSON la respuesta para ser retornada al usuario
echo json_encode($datosCliente);

 ?>