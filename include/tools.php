<?php
/***********************************************************************************************************************************/
// Tipo de archivo: Validaciones
// Fecha de elaboración: Marzo del 2021
// Elaborado por: Coninsa Ramón H
// Funcionalidad: Utilitarios para los servicios web.
/***********************************************************************************************************************************/

function isSecure() {
    return
        !((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || $_SERVER['SERVER_PORT'] == 443);
}

function getRealIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP']))
        return $_SERVER['HTTP_CLIENT_IP'];
       
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
   
    return $_SERVER['REMOTE_ADDR'];
}

function getFirstBytesIP($ip) {
    return substr($ip, 0, strpos($ip, ".", strpos($ip, ".") + 1));
}

// Se obtienen los dos primeros bytes de la IP, para que funcione con cualquier subneteo que se haga
//$ip2 = substr($ip, 0, strpos($ip, ".", strpos($ip, ".")+1));

function RandomString($characters) {
	$length = 65;
    return substr(str_shuffle(str_repeat($x = $characters, ceil($length/strlen($x)) )), 1, $length);
}

function eliminar_tildes($cadena){

    // Codificamos la cadena en formato utf8 en caso de que nos de errores
    $cadena = utf8_encode($cadena);

    // Ahora reemplazamos las letras
    $cadena = str_replace(
        array('Á', 'À', 'Â', 'Ä', 'á', 'à', 'ä', 'â', 'ª'),
        array('A', 'A', 'A', 'A', 'a', 'a', 'a', 'a', 'a'),
        $cadena
    );
    
    $cadena = str_replace(
        array('É', 'È', 'Ê', 'Ë', 'é', 'è', 'ë', 'ê'),
        array('E', 'E', 'E', 'E', 'e', 'e', 'e', 'e'),
        $cadena
    );
    
    $cadena = str_replace(
        array('Í', 'Ì', 'Ï', 'Î', 'í', 'ì', 'ï', 'î'),
        array('I', 'I', 'I', 'I', 'i', 'i', 'i', 'i'),
        $cadena
    );
    
    $cadena = str_replace(
        array('Ó', 'Ò', 'Ö', 'Ô', 'ó', 'ò', 'ö', 'ô'),
        array('O', 'O', 'O', 'O', 'o', 'o', 'o', 'o'),
        $cadena
    );
    
    $cadena = str_replace(
        array('Ú', 'Ù', 'Û', 'Ü', 'ú', 'ù', 'ü', 'û'),
        array('U', 'U', 'U', 'U', 'u', 'u', 'u', 'u'),
        $cadena
    );
    
    $cadena = str_replace(
        array('Ñ', 'ñ', 'Ç', 'ç'),
        array('N', 'n', 'C', 'c'),
        $cadena
    );

    return $cadena;
}
