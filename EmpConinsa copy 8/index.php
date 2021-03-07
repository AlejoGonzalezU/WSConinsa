<?php
/***********************************************************************************************************************************/
// Tipo de archivo: Ejecutable
// Fecha de elaboración: Julio del 2019
// Fecha de modificación: Marzo del 2019
// Elaborado por: Coninsa Ramón H
// Funcionalidad: Servicio web que entrega información detallada sobre empleados de Coninsa Ramón H.
/***********************************************************************************************************************************/

header('Content-Type: application/json; charset=iso-8859-1');
ini_set('memory_limit', '-1');

include("../include/config.php");
include("../include/ConexionSqlSvr.php");
include("../include/tools.php");

// Se valida la inclusión del protocolo seguro (HTTPS)
if(!isSecure()) {
    echo '{"empleados": 0, "mensaje": "El consumo del servicio debe ser sobre protocolo seguro (HTTPS://)"}';
    exit();
}

// Se carga el listado de las IP que tienen permiso de acceso al servicio web, así como el Token de acceso
$ip_file = file_get_contents('../permitidas.txt');
$permitidas = explode(", ", $ip_file);

// Se obtiene el Token de la URL utilizada
$array = explode("/", $_GET["token"]);

// Se verifica que los Token sean iguales y valida el tipo de consumo que se quiere hacer
if ($array[0] == "n9PXWiP8cl7*edhzPbr1sVAmQecWX2qN6kWIjOU2ByPXptcCMdqDhQNUa87c-xist*I" && $array[1] == "consultar") {
	
    // Se obtiene la IP del cliente
	if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } else if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }

	// Se verifica si está dentro de las permitidas
    if (in_array($ip, $permitidas)) {
		$sCorreo = '';
		$sUsuario = '';
		$sIdentificacion = '';
		$sCiudad = '';
		$iEstado = -1;
		$i = 0;

// ** ------------------------------------------------------------------------------------------------------------------ ** // 
				// Opción 1 = Consulta de información detallada, según el correo electrónico

		if (strtoupper($array[2]) == "CORREO"){
			
			$sCorreo = $array[3];
			
			// Conexión con la DB
			$ConexionSqlSvr = new ConexionSqlSvr();

			// Llamado al procedimiento almacenado
            $variables = array('Opcion', 'Correo', 'Usuario', 'ID', 'Ciudad');
            $param = array('issss', 1, $sCorreo, '', '', '');
    
            $ConexionSqlSvr->execSP('sp_EmpConinsa_Mostrar', $variables, $param);

			// Almacenado del resultado en un array
            $query = $ConexionSqlSvr->resulArray();
			$cantidad = count($query);

			// Se recorre la consulta de la DB para imprimir la información obtenida
			if ($cantidad != 0) {
				
				// Se va almacenando la información en objetos para darla la estructura de un JSON
				$dEmpleado = new stdClass();
				$dEmpleado->Codigo = $query[$i]["ICodigo"];
				$dEmpleado->Identificacion = $query[$i]["SIdentificacion"];
				$dEmpleado->Nombre = $query[$i]["SNombre"];
				$dEmpleado->Email = $query[$i]["SEmail"];
				$dEmpleado->Usuario = $query[$i]["SUsuario"];
				$dEmpleado->Ciudad = $query[$i]["SCiudad"];
				$dEmpleado->CentroCostos = $query[$i]["SCentroCostos"];
				$dEmpleado->Unidad = $query[$i]["SUnidad"];
				$dEmpleado->Dependencia = $query[$i]["SDependencia"];
				$dEmpleado->Cargo = $query[$i]["SCargo"];
				$dEmpleado->Estado = $query[$i]["XEstado"];
				$dEmpleado->Nivel = $query[$i]["INivel"];
				$dEmpleado->Foto = $query[$i]["SFoto"];
				
				$datosEmpleados = new stdClass();
				$datosEmpleados->DatosEmpleado = $dEmpleado;
				$datosEmpleados->NumeroEmpleados = $cantidad;
				
				if ($cantidad == 1)
					$datosEmpleados->Mensaje = 'Datos de ' . $cantidad . ' empleado, (desde direccion ip '.$ip.')';
				else
					$datosEmpleados->Mensaje = 'Datos de ' . $cantidad . ' empleados, (desde direccion ip '.$ip.')';
				
			} else {
				// Mensaje si la consulta con la DB no devolvió ningún resultado
				$datosEmpleados = new stdClass();
				$datosEmpleados->Mensaje = 'Datos de ' . $cantidad . ' empleados, (desde direccion ip '.$ip.')';
			}

// ** ------------------------------------------------------------------------------------------------------------------ ** // 
				// Opción 2 = Consulta de información detallada, según el usuario del LDAP

		} else if (strtoupper($array[2]) == "USUARIO"){
			
			$sUsuario = $array[3];
			
			// Conexión con la DB
			$ConexionSqlSvr = new ConexionSqlSvr();

			// Llamado al procedimiento almacenado
            $variables = array('Opcion', 'Correo', 'Usuario', 'ID', 'Ciudad');
            $param = array('issss', 2, '', $sUsuario, '', '');
    
            $ConexionSqlSvr->execSP('sp_EmpConinsa_Mostrar', $variables, $param);

			// Almacenado del resultado en un array
            $query = $ConexionSqlSvr->resulArray();
			$cantidad = count($query);

			// Se recorre la consulta de la DB para imprimir la información obtenida
			if ($cantidad != 0) {
				
				// Se va almacenando la información en objetos para darla la estructura de un JSON
				$dEmpleado = new stdClass();
				$dEmpleado->Codigo = $query[$i]["ICodigo"];
				$dEmpleado->Identificacion = $query[$i]["SIdentificacion"];
				$dEmpleado->Nombre = $query[$i]["SNombre"];
				$dEmpleado->Email = $query[$i]["SEmail"];
				$dEmpleado->Usuario = $query[$i]["SUsuario"];
				$dEmpleado->Ciudad = $query[$i]["SCiudad"];
				$dEmpleado->CentroCostos = $query[$i]["SCentroCostos"];
				$dEmpleado->Unidad = $query[$i]["SUnidad"];
				$dEmpleado->Dependencia = $query[$i]["SDependencia"];
				$dEmpleado->Cargo = $query[$i]["SCargo"];
				$dEmpleado->Estado = $query[$i]["XEstado"];
				$dEmpleado->Nivel = $query[$i]["INivel"];
				$dEmpleado->Foto = $query[$i]["SFoto"];
				
				$datosEmpleados = new stdClass();
				$datosEmpleados->DatosEmpleado = $dEmpleado;
				$datosEmpleados->NumeroEmpleados = $cantidad;
				
				if ($cantidad == 1)
					$datosEmpleados->Mensaje = 'Datos de ' . $cantidad . ' empleado, (desde direccion ip '.$ip.')';
				else
					$datosEmpleados->Mensaje = 'Datos de ' . $cantidad . ' empleados, (desde direccion ip '.$ip.')';
				
			} else {
				// Mensaje si la consulta con la DB no devolvió ningún resultado
				$datosEmpleados = new stdClass();
				$datosEmpleados->Mensaje = 'Datos de ' . $cantidad . ' empleados, (desde direccion ip '.$ip.')';
			}

		// ** ------------------------------------------------------------------------------------------------------------------ ** // 
					

		} else if (strtoupper($array[2]) == "LOGIN"){
			
			$sUsuario = $array[3];
			
			// Conexión con la DB
			$ConexionSqlSvr = new ConexionSqlSvr();

            // Llamado al procedimiento almacenado
            $variables = array('Opcion', 'Correo', 'Usuario', 'ID', 'Ciudad');
            $param = array('issss', 7, '', $sUsuario, '', '');
    
            $ConexionSqlSvr->execSP('sp_EmpConinsa_Mostrar', $variables, $param);

			// Almacenado del resultado en un array
            $query = $ConexionSqlSvr->resulArray();
			$cantidad = count($query);

			if($sUsuario == 'fabiosanchez' || $sUsuario == 'oagonzalez'){
				if ($cantidad != 0) {
					// Se va almacenando la información en objetos para darla la estructura de un JSON
					$dEmpleado = new stdClass();
			
					$dEmpleado->Idusuario = $query[$i]["IdUsuario"];
					$dEmpleado->Nombre = $query[$i]["Nombre"];
					$dEmpleado->Perfil = $query[$i]["Descripcion"];
					$dEmpleado->Cedula = $query[$i]["cedula"];
					$dEmpleado->Nivel = $query[$i]["NivelA"];
					$dEmpleado->Cargo = $query[$i]["Cargo"];
					$dEmpleado->Email = $query[$i]["email"];
	
					$datosEmpleados = new stdClass();
					$datosEmpleados->DatosEmpleado = $dEmpleado;
					$datosEmpleados->NumeroEmpleados = $cantidad;
					
					if ($cantidad == 1)
						$datosEmpleados->Mensaje = 'Datos de ' . $cantidad . ' empleado, (desde direccion ip '.$ip.')';
					else
						$datosEmpleados->Mensaje = 'Datos de ' . $cantidad . ' empleados, (desde direccion ip '.$ip.')';
					
				} else {
					// Mensaje si la consulta con la DB no devolvió ningún resultado
					$datosEmpleados = new stdClass();
					$datosEmpleados->Mensaje = 'Datos de ' . $cantidad . ' empleados, (desde direccion ip '.$ip.')';
				}
			}else{
					// Se recorre la consulta de la DB para imprimir la información obtenida
			if ($cantidad != 0) {
				// Se va almacenando la información en objetos para darla la estructura de un JSON
				$dEmpleado = new stdClass();
				$dEmpleado->Codigo = $query[$i]["ICodigo"];
				$dEmpleado->Identificacion = $query[$i]["SIdentificacion"];
				$dEmpleado->Nombre = $query[$i]["SNombre"];
				$dEmpleado->Email = $query[$i]["SEmail"];
				$dEmpleado->Usuario = $query[$i]["SUsuario"];
				$dEmpleado->Nivel1 = $query[$i]["IdNivelA"];
				$dEmpleado->Descripcion = $query[$i]["Descripcion"];
				$dEmpleado->Perfil = $query[$i]["Nombre"];
				$dEmpleado->Ciudad = $query[$i]["SCiudad"];
				$dEmpleado->CentroCostos = $query[$i]["SCentroCostos"];
				$dEmpleado->Unidad = $query[$i]["SUnidad"];
				$dEmpleado->Dependencia = $query[$i]["SDependencia"];
				$dEmpleado->Cargo = $query[$i]["SCargo"];
				$dEmpleado->Estado = $query[$i]["XEstado"];
				$dEmpleado->Nivel = $query[$i]["INivel"];
				$dEmpleado->Foto = $query[$i]["SFoto"];
				
				$datosEmpleados = new stdClass();
				$datosEmpleados->DatosEmpleado = $dEmpleado;
				$datosEmpleados->NumeroEmpleados = $cantidad;
				
				if ($cantidad == 1)
					$datosEmpleados->Mensaje = 'Datos de ' . $cantidad . ' empleado, (desde direccion ip '.$ip.')';
				else
					$datosEmpleados->Mensaje = 'Datos de ' . $cantidad . ' empleados, (desde direccion ip '.$ip.')';
				
			} else {
				// Mensaje si la consulta con la DB no devolvió ningún resultado
				$datosEmpleados = new stdClass();
				$datosEmpleados->Mensaje = 'Datos de ' . $cantidad . ' empleados, (desde direccion ip '.$ip.')';
			}
        }
		

// ** ------------------------------------------------------------------------------------------------------------------ ** // 
				// opción 3 = Consulta de información detallada, según el número de documento de identidad

		}else if (strtoupper($array[2]) == "IDENTIFICACION"){
			
			$sIdentificacion = $array[3];
			
			// Conexión con la DB
			$ConexionSqlSvr = new ConexionSqlSvr();

			// Llamado al procedimiento almacenado
            $variables = array('Opcion', 'Correo', 'Usuario', 'ID', 'Ciudad');
            $param = array('issss', 3, '', '', $sIdentificacion, '');
    
            $ConexionSqlSvr->execSP('sp_EmpConinsa_Mostrar', $variables, $param);

			// Almacenado del resultado en un array
            $query = $ConexionSqlSvr->resulArray();
			$cantidad = count($query);

			// Se recorre la consulta de la DB para imprimir la información obtenida
			if ($cantidad != 0) {
				
				// Se va almacenando la información en objetos para darla la estructura de un JSON
				$dEmpleado = new stdClass();
				$dEmpleado->Codigo = $query[$i]["ICodigo"];
				$dEmpleado->Identificacion = $query[$i]["SIdentificacion"];
				$dEmpleado->Nombre = $query[$i]["SNombre"];
				$dEmpleado->Email = $query[$i]["SEmail"];
				$dEmpleado->Usuario = $query[$i]["SUsuario"];
				$dEmpleado->Ciudad = $query[$i]["SCiudad"];
				$dEmpleado->CentroCostos = $query[$i]["SCentroCostos"];
				$dEmpleado->Unidad = $query[$i]["SUnidad"];
				$dEmpleado->Dependencia = $query[$i]["SDependencia"];
				$dEmpleado->Cargo = $query[$i]["SCargo"];
				$dEmpleado->Estado = $query[$i]["XEstado"];
				$dEmpleado->Nivel = $query[$i]["INivel"];
				$dEmpleado->Foto = $query[$i]["SFoto"];
				$dEmpleado->ExtTel = $query[$i]["SNumExt"];
				$dEmpleado->TelMovil = $query[$i]["SNumTelM"];
				$dEmpleado->Especialidad = $query[$i]["SEspecialidad"];
			
				$datosEmpleados = new stdClass();
				$datosEmpleados->DatosEmpleado = $dEmpleado;
				$datosEmpleados->NumeroEmpleados = $cantidad;
				
				if ($cantidad == 1)
					$datosEmpleados->Mensaje = 'Datos de ' . $cantidad . ' empleado, (desde direccion ip '.$ip.')';
				else
					$datosEmpleados->Mensaje = 'Datos de ' . $cantidad . ' empleados, (desde direccion ip '.$ip.')';
				
			} else {
				// Mensaje si la consulta con la DB no devolvió ningún resultado
				$datosEmpleados = new stdClass();
				$datosEmpleados->Mensaje = 'Datos de ' . $cantidad . ' empleados, (desde direccion ip '.$ip.')';
			}

// ** ------------------------------------------------------------------------------------------------------------------ ** // 
				// opción 4 = Listado de empleados por ciudad

		} else if (strtoupper($array[2]) == "CIUDAD"){
			
			// Consulta de información detallada, según el usuario del LDAP
			$sCiudad = $array[3];
			
			// Conexión con la DB
			$ConexionSqlSvr = new ConexionSqlSvr();

            // Llamado al procedimiento almacenado
            $variables = array('Opcion', 'Correo', 'Usuario', 'ID', 'Ciudad');
            $param = array('issss', 4, '', '', '', $sCiudad);
    
            $ConexionSqlSvr->execSP('sp_EmpConinsa_Mostrar', $variables, $param);
            
			// Almacenado del resultado en un array
            $query = $ConexionSqlSvr->resulArray();
			$cantidad = count($query);

			// Se recorre la consulta de la DB para imprimir la información obtenida
			if ($cantidad != 0) {
				
				//Se crea arreglo para almacenar un empleado por posición
				$empleados = array();
				
				for ($i = 0; $i < $cantidad; $i++){

					// Por cada empleado relacionado con la ciudad seleccionada se genera 
					// un objeto que lo almacene y este se va agregando al arreglo.
					// Es muy importante codificar SIEMPRE en utf8 los datos para que funcione el procedimiento
					$dEmpleado = new stdClass();
					$dEmpleado->Codigo = $query[$i]["ICodigo"];
					$dEmpleado->Identificacion = $query[$i]["SIdentificacion"];
					$dEmpleado->Nombre = $query[$i]["SNombre"];
					$dEmpleado->Email = $query[$i]["SEmail"];
					$dEmpleado->Usuario = $query[$i]["SUsuario"];
					$dEmpleado->Ciudad = $query[$i]["SCiudad"];
					$dEmpleado->CentroCostos = $query[$i]["SCentroCostos"];
					$dEmpleado->Unidad = $query[$i]["SUnidad"];
					$dEmpleado->Dependencia = $query[$i]["SDependencia"];
					$dEmpleado->Cargo = $query[$i]["SCargo"];
					$dEmpleado->Estado = $query[$i]["XEstado"];
					$dEmpleado->Nivel = $query[$i]["INivel"];
					$dEmpleado->Foto = $query[$i]["SFoto"];
					
					//Se guarda el empleado encontrado en el arreglo
					$empleados[] = $dEmpleado;
					
				}
				
				$datosEmpleados = new stdClass();
				$datosEmpleados->DatosEmpleado = $empleados;
				$datosEmpleados->NumeroEmpleados = $cantidad;
				
				if ($cantidad == 1)
					$datosEmpleados->Mensaje = 'Datos de ' . $cantidad . ' empleado, (desde direccion ip '.$ip.')';
				else
					$datosEmpleados->Mensaje = 'Datos de ' . $cantidad . ' empleados, (desde direccion ip '.$ip.')';
				
			} else {
				// Mensaje si la consulta con la DB no devolvió ningún resultado
				$datosEmpleados = new stdClass();
				$datosEmpleados->Mensaje = 'Datos de ' . $cantidad . ' empleados, (desde direccion ip '.$ip.')';
			}

// ** ------------------------------------------------------------------------------------------------------------------ ** // 
				// opción 5 = Listado de empleados en estado ACTIVO
		} else if (strtoupper($array[2]) == "ACTIVOS"){

			// Conexión con la DB
			$ConexionSqlSvr = new ConexionSqlSvr();

			// Llamado al procedimiento almacenado
            $variables = array('Opcion', 'Correo', 'Usuario', 'ID', 'Ciudad');
            $param = array('issss', 5, '', '', '', '');
    
            $ConexionSqlSvr->execSP('sp_EmpConinsa_Mostrar', $variables, $param);
            
			// Almacenado del resultado en un array
            $query = $ConexionSqlSvr->resulArray();
			$cantidad = count($query);

			// Se recorre la consulta de la DB para imprimir la información obtenida
			if ($cantidad != 0) {
				
				//Se crea arreglo para almacenar un empleado por posición
				$empleados = array();
				
				for ($i = 0; $i < $cantidad; $i++){

					// Por cada empleado relacionado con el estado seleccionado, se genera 
					// un objeto que lo almacene y este se va agregando al arreglo.
					// Es muy importante codificar SIEMPRE en utf8 los datos para que funcione el procedimiento
					$dEmpleado = new stdClass();
					$dEmpleado->Codigo = $query[$i]["ICodigo"];
					$dEmpleado->Identificacion = $query[$i]["SIdentificacion"];
					$dEmpleado->Nombre = $query[$i]["SNombre"];
					$dEmpleado->Email = $query[$i]["SEmail"];
					$dEmpleado->Usuario = $query[$i]["SUsuario"];
					$dEmpleado->Ciudad = $query[$i]["SCiudad"];
					$dEmpleado->CentroCostos = $query[$i]["SCentroCostos"];
					$dEmpleado->Unidad = $query[$i]["SUnidad"];
					$dEmpleado->Dependencia = $query[$i]["SDependencia"];
					$dEmpleado->Cargo = $query[$i]["SCargo"];
					$dEmpleado->Estado = $query[$i]["XEstado"];
					$dEmpleado->Nivel = $query[$i]["INivel"];
					$dEmpleado->Foto = $query[$i]["SFoto"];
					
					//Se guarda el empleado encontrado en el arreglo
					$empleados[] = $dEmpleado;
					
				}
				
				$datosEmpleados = new stdClass();
				$datosEmpleados->DatosEmpleado = $empleados;
				$datosEmpleados->NumeroEmpleados = $cantidad;
				
				if ($cantidad == 1)
					$datosEmpleados->Mensaje = 'Datos de ' . $cantidad . ' empleado, (desde direccion ip '.$ip.')';
				else
					$datosEmpleados->Mensaje = 'Datos de ' . $cantidad . ' empleados, (desde direccion ip '.$ip.')';
				
			} else {
				// Mensaje si la consulta con la DB no devolvió ningún resultado
				$datosEmpleados = new stdClass();
				$datosEmpleados->Mensaje = 'Datos de ' . $cantidad . ' empleados, (desde direccion ip '.$ip.')';
			}

// ** ------------------------------------------------------------------------------------------------------------------ ** // 
				// opción 5 = Listado de empleados en estado INACTIVO

		} else if (strtoupper($array[2]) == "INACTIVOS"){

			// Conexión con la DB
			$ConexionSqlSvr = new ConexionSqlSvr();

			// Llamado al procedimiento almacenado
            $variables = array('Opcion', 'Correo', 'Usuario', 'ID', 'Ciudad');
            $param = array('issss', 6, '', '', '', '');
    
            $ConexionSqlSvr->execSP('sp_EmpConinsa_Mostrar', $variables, $param);

			// Almacenado del resultado en un array
            $query = $ConexionSqlSvr->resulArray();
			$cantidad = count($query);

			// Se recorre la consulta de la DB para imprimir la información obtenida
			if ($cantidad != 0) {
				
				//Se crea arreglo para almacenar un empleado por posición
				$empleados = array();
				
				for ($i = 0; $i < $cantidad; $i++){

					// Por cada empleado relacionado con el estado seleccionado, se genera 
					// un objeto que lo almacene y este se va agregando al arreglo.
					// Es muy importante codificar SIEMPRE en utf8 los datos para que funcione el procedimiento
					$dEmpleado = new stdClass();
					$dEmpleado->Codigo = $query[$i]["ICodigo"];
					$dEmpleado->Identificacion = $query[$i]["SIdentificacion"];
					$dEmpleado->Nombre = $query[$i]["SNombre"];
					$dEmpleado->Email = $query[$i]["SEmail"];
					$dEmpleado->Usuario = $query[$i]["SUsuario"];
					$dEmpleado->Ciudad = $query[$i]["SCiudad"];
					$dEmpleado->CentroCostos = $query[$i]["SCentroCostos"];
					$dEmpleado->Unidad = $query[$i]["SUnidad"];
					$dEmpleado->Dependencia = $query[$i]["SDependencia"];
					$dEmpleado->Cargo = $query[$i]["SCargo"];
					$dEmpleado->Estado = $query[$i]["XEstado"];
					$dEmpleado->Nivel = $query[$i]["INivel"];
					$dEmpleado->Foto = $query[$i]["SFoto"];
					
					//Se guarda el empleado encontrado en el arreglo
					$empleados[] = $dEmpleado;
					
				}
				
				$datosEmpleados = new stdClass();
				$datosEmpleados->DatosEmpleado = $empleados;
				$datosEmpleados->NumeroEmpleados = $cantidad;
				
				if ($cantidad == 1)
					$datosEmpleados->Mensaje = 'Datos de ' . $cantidad . ' empleado, (desde direccion ip '.$ip.')';
				else
					$datosEmpleados->Mensaje = 'Datos de ' . $cantidad . ' empleados, (desde direccion ip '.$ip.')';
				
			} else {
				// Mensaje si la consulta con la DB no devolvió ningún resultado
				$datosEmpleados = new stdClass();
				$datosEmpleados->Mensaje = 'Datos de ' . $cantidad . ' empleados, (desde direccion ip '.$ip.')';
			}
			
		} else {
			// Mensaje si los parámetros de consumo son incorrectos
			$datosEmpleados = new stdClass();
			$datosEmpleados->Mensaje = 'Por favor revise los parámetros de consumo del servicio web, (desde direccion ip '.$ip.'). (Error 01).';
		}
    } else {
		// Mensaje si la IP no se encuentra dentro de las permitidas
        $datosEmpleados = new stdClass();
		$datosEmpleados->Mensaje = 'No tiene permiso para realizar esta petición, (desde direccion ip '.$ip.'). Póngase en contacto con el administrador del servicio web. (Error 02).';
    }
} else {
	// Mensaje si el token no corresponde o la opción de consumo es errada
	$datosEmpleados = new stdClass();
	$datosEmpleados->Mensaje = 'Por favor revise los parámetros de consumo del servicio web y que el token proporcionado sea el correcto, (desde direccion ip '.$ip.'). (Error 03)';
}

// Se codifica como JSON la respuesta para ser retornada al usuario
echo json_encode($datosEmpleados, true);

 ?>