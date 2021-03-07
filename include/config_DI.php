<?php
/* Información General
*----------------------------------------------------------------------*
* Proyecto: Web Services Coninsa
* Tipo Objeto: Archivo de configuración
* Descripción: Establece los datos básicos para conexión a la BD, y la ruta de los directorios
* Autor Programa: Coninsa Ramón H
* Fecha Creación: Marzo de 2021
*---------------------------------------------------------------------- */

// Databases
define('SERVER', '190.248.37.223');
define('USER', 'soportesinco');
define('PASS', 'SoporteCRH05');
define('DB', 'DIConinsaRH');

// Error reporting
error_reporting(1);
ini_set("display_errors", 1);
ini_set('display_startup_errors', 1);

//Idioma, Hora y Moneda
defined('es_ES') OR setlocale(LC_TIME, 'es_ES.utf8"');
date_default_timezone_set ('America/Bogota');
setlocale(LC_MONETARY, "es_CO.UTF-8");

//Parametros
define('Token_Avanto', 'n9PXWiP8cl7edhzPbr1sVAmQecWX2qN6kWIjOU2ByPXptcCMdqDhQNUa87cxistIx');
