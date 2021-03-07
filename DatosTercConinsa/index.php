<?php
/***********************************************************************************************************************************/
// Tipo de archivo: Ejecutable
// Fecha de elaboración: Mayo del 2020
// Fecha de modificación: Marzo del 2021
// Elaborado por: Coninsa Ramón H
// Funcionalidad: Servicio web que entrega información detallada sobre terceros asociados a Coninsa Ramón H.
/***********************************************************************************************************************************/

header('Content-Type: application/json; charset=iso-8859-1');
ini_set('memory_limit', '-1');

include("../include/config_VoIP.php");
include("../include/ConexionSqlSvr.php");
include("../include/tools.php");

// Se valida la inclusión del protocolo seguro (HTTPS)
if(!isSecure()) {
    echo '{"Terceros": 0, "mensaje": "El consumo del servicio debe ser sobre protocolo seguro (HTTPS://)"}';
    exit();
}

// Se carga el listado de las IP que tienen permiso de acceso al servicio web, así como el Token de acceso
$ip_file = file_get_contents('permitidas.txt');
$permitidas = explode(", ", $ip_file);

// Se configura la recepción de información en formato JSON y se almacena en una variable
$array = json_decode(file_get_contents('php://input'), true);

// Se verifica que los Token sean iguales y valida el tipo de consumo que se quiere hacer
if ($array["Token"] == "n9PXWiP8cl7*edhzPbr1sVAmQecWX2qN6kWIjOU2ByPXptcCMdqDhQNUa87c-xist*I" && (strtoupper($array["TipoWS"]) == "CONSULTAR")) {

	// Se verifica si la IP está dentro de las permitidas
    if (in_array(getRealIP(), $permitidas)) {
		try {
			//Se verifican los parámetros de consulta en el procedimiento almacenado
			$nitEmpresa = $array["NIT"];

			//Conexión con la DB
			$ConexionSqlSvr = new ConexionSqlSvr();

			// Llamado al procedimiento almacenado
			$variables = array('Opcion', 'TCIdGrupo', 'TEIdCateg', 'NIT', 'CiuID', 'TerEstado', 'TEID');
			$param = array('issssss', 7, '', '', $nitEmpresa, '', '', '', '');

			$ConexionSqlSvr->execSP('sp_DatosTerc_Mostrar', $variables, $param);

			// Almacenado del resultado en un array
			$query = $ConexionSqlSvr->resulArray();
			$cantidad = count($query);

			//Se recorre la consulta de la DB para imprimir la información obtenida
			if ($cantidad != 0) {
				
				// Se va almacenando la información en objetos para darla la estructura de un JSON
				$datos = new stdClass();
				$datos->TipoIdent = $query[0]["TipoIdent"];
				$datos->Nit = $query[0]["Nit"];
				$datos->NatJur = $query[0]["NatJur"];
				$datos->RazonSocial = $query[0]["RazonSocial"];
				$datos->NombrePrimero = $query[0]["NombrePrimero"];
				$datos->NombreSegundo = $query[0]["NombreSegundo"];
				$datos->ApellidoPrimero = $query[0]["ApellidoPrimero"];
				$datos->ApellidoSegundo = $query[0]["ApellidoSegundo"];
				$datos->TerTipo = $query[0]["TerTipo"];
				$datos->TerTipoProveedor = $query[0]["TerTipoProveedor"];
				$datos->TerEstado = $query[0]["TerEstado"];
				$datos->TerCodActvEconomica = $query[0]["TerCodActvEconomica"];
				$datos->TerActividadEconomica = $query[0]["TerActividadEconomica"];
				$datos->TerDireccion = $query[0]["TerDireccion"];
				$datos->TerTelefono = $query[0]["TerTelefono"];
				$datos->TerDepartamento = $query[0]["TerDepartamento"];
				$datos->TerCiudad = $query[0]["TerCiudad"];
				$datos->TerObservaciones = $query[0]["TerObservaciones"];

				$contacto = new stdClass();
				$contacto->Nombre = $query[0]["Nombre"];
				$contacto->Telefono = $query[0]["Telefono"];
				$contacto->Email = $query[0]["Email"];
				$contacto->Cargo = $query[0]["Cargo"];

				$tributaria = new stdClass();
				$tributaria->TerGranContrib = $query[0]["TerGranContrib"];
				$tributaria->TerRegIVA = $query[0]["TerRegIVA"];
				$tributaria->TerAutoretenedor = $query[0]["TerAutoretenedor"];
				$tributaria->TerDeclarante = $query[0]["TerDeclarante"];

				$compras = new stdClass();
				$compras->Especialidad = $query[0]["Especialidad"];

				$datosBancarios->Banco = $query[0]["ICod_banco"];
				$datosBancarios->TipoCuenta = $query[0]["ITipo_cuenta"];
				$datosBancarios->NroCuenta = $query[0]["SNumero_cuenta"];
				$datosBancarios->Email = $query[0]["SEmail"];

				$datosTerceros = new stdClass();
				$datosTerceros->DatosBasicos = $datos;
				$datosTerceros->ContactoPrincipal = $contacto;
				$datosTerceros->ConfiguracionTributaria = $tributaria;
				$datosTerceros->ComprasContratos = $compras;
				$datosTerceros->CuentaBancaria = $datosBancarios;

				$Tercero = new stdClass();
				$Tercero->DatosTercero = $datosTerceros;
				$Tercero->Terceros = $cantidad;

				if ($cantidad == 1)
					$Tercero->mensaje = 'Datos de ' . $cantidad . ' tercero, (desde direccion ip '.getRealIP().')';
				else
					$Tercero->mensaje = 'Datos de ' . $cantidad . ' terceros, (desde direccion ip '.getRealIP().')';

			} else {
				// Mensaje si la consulta con la DB no devolvió ningún resultado
				$Tercero = new stdClass();
				$Tercero->mensaje = 'Datos de ' . $cantidad . ' terceros, (desde direccion ip '.getRealIP().')';
			}

		} catch(Exception $e) {
			// Mensaje si hubo un error en la ejecución de la consulta
			$Tercero = new stdClass();
			$Tercero->Mensaje = 'Ha ocurrido un error en la ejecución. Por favor, contacte al administrador del sistema.';
		}

	} else {
		//Mensaje si los parámetros de consumo son incorrectos
		$Tercero = new stdClass();
		$Tercero->Mensaje = 'Por favor revise los parámetros de consumo del servicio web, (desde direccion ip '.getRealIP().'). (Error 01).';
	}
} else {
	// Mensaje si el token no corresponde o la opción de consumo es errada
	$Tercero = new stdClass();
	$Tercero->Mensaje = 'Por favor revise los parámetros de consumo del servicio web y que el token proporcionado sea el correcto, (desde direccion ip '.getRealIP().'). (Error 03)';
}

// Se codifica como JSON la respuesta para ser retornada al usuario
echo json_encode($Tercero, true);

 ?>