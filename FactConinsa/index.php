<?php
/***********************************************************************************************************************************/
// Tipo de archivo: Ejecutable
// Fecha de elaboración: Agosto del 2019
// Fecha de modificación: Marzo del 2019
// Elaborado por: Coninsa Ramón H
// Funcionalidad: Servicio web que entrega información detallada sobre facturas de terceros asociados a Coninsa Ramón H.
/***********************************************************************************************************************************/

header('Content-Type: application/json; charset=iso-8859-1');
ini_set('memory_limit', '-1');

include("../include/config_VoIP.php");
include("../include/ConexionSqlSvr.php");
include("../include/tools.php");

// Se valida la inclusión del protocolo seguro (HTTPS)
if(!isSecure()) {
    echo '{"facturas": 0, "mensaje": "El consumo del servicio debe ser sobre protocolo seguro (HTTPS://)"}';
    exit();
}

// Se carga el listado de las IP que tienen permiso de acceso al servicio web, así como el Token de acceso
$ip_file = file_get_contents('permitidas.txt');
$permitidas = explode(", ", $ip_file);

// Se configura la recepción de información en formato JSON y se almacena en una variable
$array = json_decode(file_get_contents('php://input'), true);

// Se verifica que los Token sean iguales y valida el tipo de consumo que se quiere hacer
if ($array["Token"] == "n9PXWiP8cl7*edhzPbr1sVAmQecWX2qN6kWIjOU2ByPXptcCMdqDhQNUa87c-xist*I" && 
	($array["TipoWS"] == "pendientes" || $array["TipoWS"] == "pagadas" || $array["TipoWS"] == "medios" || $array["TipoWS"] == "terceros")
) {

	// Se verifica si la IP está dentro de las permitidas
	if (in_array(getRealIP(), $permitidas)) {
		try {
			if (strtoupper($array["TipoWS"]) == "PENDIENTES"){
				//Consulta de facturas pendientes
				if (isset($array["NIT"]) && isset($array["Facturas"])) {
					$facturas = $array["Facturas"];
					$nitEmpresa = $array["NIT"];

					//Conexión con la DB
					$ConexionSqlSvr = new ConexionSqlSvr();

					//Llamado al procedimiento almacenado
					if (strpos($facturas, "|")) {
						$variables = array('Opcion', 'NIT', 'FechaInicial', 'FechaFinal', 'Factura');
						$param = array('issss', 1, $nitEmpresa, '', '', $facturas);
					} else {
						$variables = array('Opcion', 'NIT', 'FechaInicial', 'FechaFinal', 'Factura');
						$param = array('issss', 2, $nitEmpresa, '', '', $facturas);
					}

					$ConexionSqlSvr->execSP('sp_DatosTerc_Mostrar', $variables, $param);

					// Almacenado del resultado en un array
					$query = $ConexionSqlSvr->resulArray();
					$cantidad = count($query);

					//Se recorre la consulta de la DB para imprimir la información obtenida
					if ($cantidad != 0 && strpos($facturas, "|")) {
						//Se crea arreglo para almacenar una factura por posición
						$facturas = array();
						
						for ($i = 0; $i < $cantidad; $i++){
							if ($query[$i]["SFactura"] !== $query[$j]["SFactura"]){
								//Por cada factura existente se genera un objeto que lo almacene y este se va agregando al arreglo
								//Es muy importante codificar SIEMPRE en utf8 los datos para que funcione el procedimiento

								$dFacturas = new stdClass();

								$dFacturas->SFactura = $query[$i]["SFactura"];
								$dFacturas->STipoDocumento = $query[$i]["SDoc_Tipo"];
								$dFacturas->SIdDocumento = $query[$i]["SDocID"];
								$dFacturas->SNumDocumento = $query[$i]["SDocNo"];
								$dFacturas->SFechaDocumento = $query[$i]["DocFecha"];
								$dFacturas->SEstado = $query[$i]["SEstado"];
								$dFacturas->SFechaPago = $query[$i]["SFechaPago"];

								//Se guarda la factura encontrada en el arreglo
								$facturas[] = $dFacturas;
							} 
							
						}
						$cantidad = count($facturas);
						$datosFacturas = new stdClass();
						$datosFacturas->Facturas = $facturas;
						$datosFacturas->NumDeFacturas = $cantidad;
						
						if ($cantidad == 1)
							$datosFacturas->Mensaje = 'Datos de ' . $cantidad . ' factura, (desde direccion ip '.$ip.')';
						else
							$datosFacturas->Mensaje = 'Datos de ' . $cantidad . ' facturas, (desde direccion ip '.$ip.')';
						
					} else if ($cantidad != 0) {
						//Se crea arreglo para almacenar una factura por posición
						$facturas = array();
						$cuentas = array();
						
						for ($i = 0; $i < $cantidad; $i++){
							//Se crea la variable $j para poder comparar con el siguiente valor y evitar repetidos
							$j = $i + 1;
							
							//Por cada factura existente se genera un objeto que lo almacene y este se va agregando al arreglo
							//Es muy importante codificar SIEMPRE en utf8 los datos para que funcione el procedimiento
							
							$datosCuenta = new stdClass();
							
							if ($query[$i]["SCuentaNo"] === $query[$j]["SCuentaNo"])
								continue;

								$datosCuenta->NumeroCuenta = $query[$i]["SCuentaNo"];
								$datosCuenta->Valor = $query[$i]["SValor"];
								$datosCuenta->Base = $query[$i]["SBase"];
								$datosCuenta->Debito = $query[$i]["SDebito"];
								$datosCuenta->Credito = $query[$i]["SCredito"];
								$datosCuenta->NombreCuenta = preg_replace('/\s+/', ' ', $query[$i]["SNombre_Cuenta"]));
							
								$cuentas[] = $datosCuenta;
							
							if ($query[$i]["SFactura"] !== $query[$j]["SFactura"]){
								$dFacturas = new stdClass();

								$dFacturas->TipoDocumento = $query[$i]["SDoc_Tipo"];
								$dFacturas->IDDocumento = $query[$i]["SDocID"];
								$dFacturas->NumeroDocumento = $query[$i]["SDocNo"];
								$dFacturas->FechaDocumento = $query[$i]["SFechaCP"];
								$dFacturas->EstadoDocumento = $query[$i]["DocEstado"];
								$dFacturas->FacturaProveedor = $query[$i]["SFactura"];
								$dFacturas->Cuentas = $cuentas;
								$dFacturas->Contrato = $query[$i]["SContrato"];
								$dFacturas->NombreTercero = $query[$i]["SNombre_Tercero"];
								$dFacturas->Sucursal = $query[$i]["SSucursal"];
								$dFacturas->ValorMedio = $query[$i]["MovVrTotal"];
								
								//Se guarda la factura encontrada en el arreglo
								$facturas[] = $dFacturas;
								
								$cuentas = array();
							} 
							
						}
						$cantidad = count($facturas);
						$datosFacturas = new stdClass();
						$datosFacturas->Facturas = $facturas;
						$datosFacturas->NumDeFacturas = $cantidad;
						
						if ($cantidad == 1)
							$datosFacturas->Mensaje = 'Datos de ' . $cantidad . ' factura, (desde direccion ip '.$ip.')';
						else
							$datosFacturas->Mensaje = 'Datos de ' . $cantidad . ' facturas, (desde direccion ip '.$ip.')';
						
					} else {
						//Mensaje si la consulta con la DB no devolvió ningún resultado
						$datosFacturas = new stdClass();
						$datosFacturas->Mensaje = 'Datos de ' . $cantidad . ' facturas, (desde direccion ip '.$ip.')';
					}

				} else {
					//Mensaje si los parámetros de consumo son incorrectos
					$datosFacturas = new stdClass();
					$datosFacturas->Mensaje = 'Por favor revise los parámetros de consumo del servicio web, (desde direccion ip '.$ip.'). (Error 01).';
				}
			} else if (strtoupper($array["TipoWS"]) == "PAGADAS"){
				//Consulta de facturas pagadas
				if (isset($array["NIT"]) && isset($array["FechaInicial"]) && isset($array["FechaFinal"])) {
					$facturas = $array["Facturas"];
					$nitEmpresa = $array["NIT"];
					$fechaInicial = $array["FechaInicial"];
					$fechaFinal = $array["FechaFinal"];

					//Conexión con la DB
					$ConexionSqlSvr = new ConexionSqlSvr();

					//Llamado al procedimiento almacenado
					if (strpos($facturas, "|")) {
						$variables = array('Opcion', 'NIT', 'FechaInicial', 'FechaFinal', 'Factura');
						$param = array('issss', 3, $nitEmpresa, $fechaInicial, $fechaInicial, '');
					} else {
						$variables = array('Opcion', 'NIT', 'FechaInicial', 'FechaFinal', 'Factura');
						$param = array('issss', 4, $nitEmpresa, '', '', $facturas);
					}

					$ConexionSqlSvr->execSP('sp_DatosTerc_Mostrar', $variables, $param);

					// Almacenado del resultado en un array
					$query = $ConexionSqlSvr->resulArray();
					$cantidad = count($query);

					//Se recorre la consulta de la DB para imprimir la información obtenida
					if ($cantidad != 0 && $facturas != "") {
						//Se crea arreglo para almacenar una factura por posición
						$facturas = array();
						$cuentas = array();
						
						for ($i = 0; $i < $cantidad; $i++){
							//Se crea la variable $j para poder comparar con el siguiente valor y evitar repetidos
							$j = $i + 1;
							
							//Por cada factura existente se genera un objeto que lo almacene y este se va agregando al arreglo
							//Es muy importante codificar SIEMPRE en utf8 los datos para que funcione el procedimiento
							
							$datosCuenta = new stdClass();

								$datosCuenta->NumeroCuenta = $query[$i]["SCuentaNo"];
								$datosCuenta->Valor = $query[$i]["SValor"];
								$datosCuenta->Base = $query[$i]["SBase"];
								$datosCuenta->Debito = $query[$i]["SDebito"];
								$datosCuenta->Credito = $query[$i]["SCredito"];
								$datosCuenta->NombreCuenta = preg_replace('/\s+/', ' ', $query[$i]["SNombre_Cuenta"]));
							
								$cuentas[] = $datosCuenta;
							
							if ($query[$i]["SFactura"] !== $query[$j]["SFactura"] && $query[$j]["SDoc_Tipo"] != 'DM'){
								$dFacturas = new stdClass();

								$dFacturas->TipoDocumento = $query[$i]["SDoc_Tipo"];
								$dFacturas->IDDocumento = $query[$i]["SDocID"];
								$dFacturas->NumeroDocumento = $query[$i]["SDocNo"];
								$dFacturas->FechaDocumento = $query[$i]["SFechaCP"];
								$dFacturas->EstadoDocumento = $query[$i]["DocEstado"];
								$dFacturas->FacturaProveedor = $query[$i]["SFactura"];
								$dFacturas->Cuentas = $cuentas;
								$dFacturas->Contrato = $query[$i]["SContrato"];
								$dFacturas->NombreTercero = $query[$i]["SNombre_Tercero"];
								$dFacturas->Sucursal = $query[$i]["SSucursal"];
								$dFacturas->ValorMedio = $query[$i]["MovVrTotal"];
								
								//Se guarda la factura encontrada en el arreglo
								$facturas[] = $dFacturas;
								
								$cuentas = array();
							} 
							
						}
						$cantidad = count($facturas);
						$datosFacturas = new stdClass();
						$datosFacturas->Facturas = $facturas;
						$datosFacturas->NumDeFacturas = $cantidad;
						
						if ($cantidad == 1)
							$datosFacturas->Mensaje = 'Datos de ' . $cantidad . ' factura, (desde direccion ip '.$ip.')';
						else
							$datosFacturas->Mensaje = 'Datos de ' . $cantidad . ' facturas, (desde direccion ip '.$ip.')';
						
					} else if ($cantidad != 0) {
						//Se crea arreglo para almacenar una factura por posición
						$facturas = array();
						
						for ($i = 0; $i < $cantidad; $i++){
							//Por cada factura existente se genera un objeto que lo almacene y este se va agregando al arreglo
							//Es muy importante codificar SIEMPRE en utf8 los datos para que funcione el procedimiento

							$dFacturas = new stdClass();

							$dFacturas->FacturaProv = $query[$i]["FacturaProv"];
							$dFacturas->Proyecto = $query[$i]["Proyecto"];
							$dFacturas->Pagado = $query[$i]["Pagado"];
							$dFacturas->SubTotal = $query[$i]["SubTotal"];
							$dFacturas->FechaPago = $query[$i]["FechaPago"];
							$dFacturas->Medio = $query[$i]["Medio"];

							//Se guarda la factura encontrada en el arreglo
							$facturas[] = $dFacturas;
							
						}
						$cantidad = count($facturas);
						$datosFacturas = new stdClass();
						$datosFacturas->Facturas = $facturas;
						$datosFacturas->NumDeFacturas = $cantidad;
						
						if ($cantidad == 1)
							$datosFacturas->Mensaje = 'Datos de ' . $cantidad . ' factura, (desde direccion ip '.$ip.')';
						else
							$datosFacturas->Mensaje = 'Datos de ' . $cantidad . ' facturas, (desde direccion ip '.$ip.')';
						
					} else {
						//Mensaje si la consulta con la DB no devolvió ningún resultado
						$datosFacturas = new stdClass();
						$datosFacturas->Mensaje = 'Datos de ' . $cantidad . ' facturas, (desde direccion ip '.$ip.')';
					}

				} else {
					//Mensaje si los parámetros de consumo son incorrectos
					$datosFacturas = new stdClass();
					$datosFacturas->Mensaje = 'Por favor revise los parámetros de consumo del servicio web, (desde direccion ip '.$ip.'). (Error 01).';
				}
			} else if (strtoupper($array["TipoWS"]) == "MEDIOS"){
				//Consulta de medios magnéticos para facturas ya pagadas
				if (isset($array["NIT"]) && isset($array["Medio"])) {
					$nitEmpresa = $array["NIT"];
					$medio = $array["Medio"];

					//Conexión con la DB
					$ConexionSqlSvr = new ConexionSqlSvr();

					//Llamado al procedimiento almacenado
					$variables = array('Opcion', 'NIT', 'FechaInicial', 'FechaFinal', 'Factura');
					$param = array('issss', 5, $nitEmpresa, '', '', $medio);

					$ConexionSqlSvr->execSP('sp_DatosTerc_Mostrar', $variables, $param);

					// Almacenado del resultado en un array
					$query = $ConexionSqlSvr->resulArray();
					$cantidad = count($query);

					//Se recorre la consulta de la DB para imprimir la información obtenida
					if ($cantidad != 0) {
						//Se crea arreglo para almacenar una factura por posición
						$facturas = array();
						
						for ($i = 0; $i < $cantidad; $i++){
							//Por cada factura existente se genera un objeto que lo almacene y este se va agregando al arreglo
							//Es muy importante codificar SIEMPRE en utf8 los datos para que funcione el procedimiento

							$dFacturas = new stdClass();

							$dFacturas->ComprobanteEgreso = $query[$i]["NumeroCE"];
							$dFacturas->FacturaProveedor = $query[$i]["FacturaProv"];
							$dFacturas->NitTercero = $query[$i]["Nit"];
							$dFacturas->RazonSocial = $query[$i]["RazonSocial"];
							$dFacturas->ChequeNo = $query[$i]["ChequeNo"];
							$dFacturas->FormaGiro = $query[$i]["FormaGiro"];
							$dFacturas->Banco = $query[$i]["Banco"];
							$dFacturas->TipoCuentaBancaria = $query[$i]["TipoCuentaBancaria"];
							$dFacturas->CuentaBancariaNo = $query[$i]["CuentaBancariaNo"];
							$dFacturas->FechaPago = $query[$i]["FechaPago"];
							$dFacturas->SubTotal = $query[$i]["SubTotal"];
							$dFacturas->Iva = $query[$i]["Iva"];
							$dFacturas->ReteIca = $query[$i]["ReteIca"];
							$dFacturas->ReteIva = $query[$i]["ReteIva"];
							$dFacturas->ReteFuente = $query[$i]["ReteFuente"];
							$dFacturas->ReteGarantia = $query[$i]["ReteGarantia"];
							$dFacturas->Amortizacion = $query[$i]["Amortizacion"];
							$dFacturas->ReteCree = $query[$i]["ReteCree"];
							$dFacturas->Neto = $query[$i]["Neto"];
							$dFacturas->DescuentosF = $query[$i]["DescuentosF"];
							$dFacturas->Pagado = $query[$i]["Pagado"];
							$dFacturas->Saldo = $query[$i]["Saldo"];
							$dFacturas->NotasCredito = $query[$i]["NotasCredito"];
							$dFacturas->Proyecto = $query[$i]["Proyecto"];
							
							//Se guarda la factura encontrada en el arreglo
							$facturas[] = $dFacturas;
							
						}
						$cantidad = count($facturas);
						$datosFacturas = new stdClass();
						$datosFacturas->Facturas = $facturas;
						$datosFacturas->NumDeFacturas = $cantidad;
						
						if ($cantidad == 1)
							$datosFacturas->Mensaje = 'Datos de ' . $cantidad . ' factura, (desde direccion ip '.$ip.')';
						else
							$datosFacturas->Mensaje = 'Datos de ' . $cantidad . ' facturas, (desde direccion ip '.$ip.')';
						
					} else {
						//Mensaje si la consulta con la DB no devolvió ningún resultado
						$datosFacturas = new stdClass();
						$datosFacturas->Mensaje = 'Datos de ' . $cantidad . ' facturas, (desde direccion ip '.$ip.')';
					}

				} else {
					//Mensaje si los parámetros de consumo son incorrectos
					$datosFacturas = new stdClass();
					$datosFacturas->Mensaje = 'Por favor revise los parámetros de consumo del servicio web, (desde direccion ip '.$ip.'). (Error 01).';
				}
			} else if (strtoupper($array["TipoWS"]) == "TERCEROS"){
				$sNombre = $array["InfoTercero"];

				//Conexión con la DB
				$ConexionSqlSvr = new ConexionSqlSvr();

				//Llamado al procedimiento almacenado
				$variables = array('Opcion', 'NIT', 'FechaInicial', 'FechaFinal', 'Factura');
				$param = array('issss', 6, '', '', '', $sNombre);

				$ConexionSqlSvr->execSP('sp_DatosTerc_Mostrar', $variables, $param);

				// Almacenado del resultado en un array
				$query = $ConexionSqlSvr->resulArray();
				$cantidad = count($query);

				//Se recorre la consulta de la DB para imprimir la información obtenida
				if ($cantidad != 0) {

					//Se crea arreglo para almacenar un tercero por posición
					$terceros = array();
					for ($i = 0; $i < $cantidad; $i++){
						//Por cada factura existente se genera un objeto que lo almacene y este se va agregando al arreglo
						//Es muy importante codificar SIEMPRE en utf8 los datos para que funcione el procedimiento

						$dTerceros = new stdClass();

						$dTerceros->Nit = $query[$i]["TerNit"];
						$dTerceros->Cadena = $query[$i]["Cadena"];

						$terceros[] = $dTerceros;
					}
					$cantidad = count($terceros);
					$datosFacturas = new stdClass();
					$datosFacturas->Terceros = $terceros;
					$datosFacturas->NumDeTerceros = $cantidad;

					if ($cantidad == 1)
						$datosFacturas->Mensaje = 'Datos de ' . $cantidad . ' tercero, (desde direccion ip '.$ip.')';
					else
						$datosFacturas->Mensaje = 'Datos de ' . $cantidad . ' terceros, (desde direccion ip '.$ip.')';

				} else {
					//Mensaje si la consulta con la DB no devolvió ningún resultado
					$datosFacturas = new stdClass();
					$datosFacturas->Mensaje = 'Datos de ' . $cantidad . ' terceros, (desde direccion ip '.$ip.')';
					$datosFacturas->Facturas = 0;
				}

			} else {
				//Mensaje si los parámetros de consumo son incorrectos
				$datosFacturas = new stdClass();
				$datosFacturas->Mensaje = 'Por favor revise los parámetros de consumo del servicio web, (desde direccion ip '.$ip.'). (Error 01).';
			}

		} catch(Exception $e) {
			// Mensaje si hubo un error en la ejecución de la consulta
			$datosFacturas = new stdClass();
			$datosFacturas->Mensaje = 'Ha ocurrido un error en la ejecución. Por favor, contacte al administrador del sistema.';
		}

    } else {
		//Mensaje si la IP no se encuentra dentro de las permitidas
        $datosFacturas = new stdClass();
		$datosFacturas->Mensaje = 'No tiene permiso para realizar esta petición, (desde direccion ip '.$ip.'). Póngase en contacto con el administrador del servicio web. (Error 02).';
    }
} else {
	// Mensaje si el token no corresponde o la opción de consumo es errada
	$datosFacturas = new stdClass();
	$datosFacturas->Mensaje = 'Por favor revise los parámetros de consumo del servicio web y que el token proporcionado sea el correcto, (desde direccion ip '.$ip.'). (Error 03)';
}

// Se codifica como JSON la respuesta para ser retornada al usuario
echo json_encode($datosFacturas, true);

 ?>