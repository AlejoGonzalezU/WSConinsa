<?php
/* ----------------------------------------------------------------------*
 * Tipo Objeto: Clase
 * Descripción: Permite realizar la conexión con un base de datos en SQL Server
 * y gestionar las consultas estándares.
 * Autor Programa: Coninsa Ramón H
 * Año: 2020
 * ---------------------------------------------------------------------- */

include 'config.php';

class ConexionSqlSvr
{
    public static $sqlsvr;
    public static $resultado;
    public static $mensaje = "";
    protected static $affected_rows = "";
    public static $sql = '';
    public static $id = '';

    public function __construct()
    {
        $a = func_get_args();
        $i = func_num_args();
        if ($i == 0){
            try {
                self::$sqlsvr = new PDO("sqlsrv:server=" . SERVER . ";database=" . DB, USER, PASS);
                self::$sqlsvr->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $e){
                sprintf('No  hay conexión a la base de datos, hubo un error: %s', $e->getMessage());
                exit();
            }
            ini_set('mssql.charset', 'UTF-8');
        } else {
            if (method_exists($this, $f = '__construct' . $i)) {
                call_user_func_array(array($this, $f) , $a);
            }
        }
    }

    public function __construct1($db)
    {
        try {
            self::$sqlsvr = new PDO("sqlsrv:server=" . SERVER . ";database=" . $db, USER, PASS);
            self::$sqlsvr->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e){
            sprintf('No  hay conexión a la base de datos, hubo un error: %s', $e->getMessage());
            exit();
        }
        ini_set('mssql.charset', 'UTF-8');
    }

    public function __construct4($server, $db, $user, $pass)
    {
        try {
            self::$sqlsvr = new PDO("sqlsrv:server=" . $server . ";database=" . $db, $user, $pass);
            self::$sqlsvr->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e){
            sprintf('No  hay conexión a la base de datos, hubo un error: %s', $e->getMessage());
            exit();
        }
        ini_set('mssql.charset', 'UTF-8');
    }

    public function __destruct()
    {
        if (isset(self::$resultado)) {
        }
    }

    //Manejo de consultas tipo Select 
    public function select($campos = '*', $table = null, $where = '', $param = null)
    {
        self::$sql = "SELECT " . $campos . " FROM " . $table . " " . $where . " ;";
        if (!($sentencia = self::$sqlsvr->prepare(self::$sql))) {
            self::$mensaje = "Falló la preparación: (" . self::$sqlsvr->errorInfoCode() . ")\n" . self::$sqlsvr->errorInfo();
            return false;
        }

        if (!empty($where) && !is_null($where) && !is_null($param)) {
            $ref = new ReflectionClass('PDOStatement');
            if (!($metodo = $ref->getMethod("bindParam"))) {
                self::$mensaje = "Falló el metodo bind param: (" . self::$sqlsvr->errorInfoCode() . ")\n" . self::$sqlsvr->errorInfo();
                return false;
            }
            for ($i = 0; $i < count($param); $i++) {
                $parametros[] = $param[$i];
            }
            foreach (array_keys($parametros) as $i) {
                if ($i > 0) $parametros[$i] = &$parametros[$i];
            }

            for ($i = 0; $i < count($parametros); $i++) {
                $j = $i + 1;
                $sentencia->bindParam($j, $parametros[$i]);
            }
        }
        if (!$sentencia->execute()) {
            self::$mensaje = "Falló la ejecución:(" . $sentencia->errorInfoCode() . ") " . $sentencia->errorInfo();
            return false;
        }
        
        self::$resultado = $sentencia;

        return true;
    }

    //Manejo de consultas tipo Insert 
    public function Ingresar($campos = '', $table = null, $values = '', $param)
    {
        self::$sql = "INSERT INTO " . $table . " " . $campos . " VALUES " . $values . " ;";
        if (!($sentencia = self::$sqlsvr->prepare(self::$sql))) {
            self::$mensaje = "Falló la preparación: (" . self::$sqlsvr->errorInfoCode() . ")\n" . self::$sqlsvr->errorInfo();
            return false;
        }
        $ref = new ReflectionClass('PDOStatement');
        if (!($metodo = $ref->getMethod("bindParam"))) {
            self::$mensaje = "Falló el metodo bind param: (" . self::$sqlsvr->errorInfoCode() . ")\n" . self::$sqlsvr->errorInfo();
            return false;
        }
        for ($i = 0; $i < count($param); $i++) {
            $parametros[] = $param[$i];
        }
        foreach (array_keys($parametros) as $i) {
            if ($i > 0) $parametros[$i] = &$parametros[$i];
        }

        for ($i = 0; $i < count($parametros); $i++) {
            $j = $i + 1;
            $sentencia->bindParam($j, $parametros[$i]);
        }
        
        if (!$sentencia->execute()) {
            self::$mensaje = "Falló la ejecución:(" . $sentencia->errorInfoCode() . ") " . $sentencia->errorInfo();
            return false;
        }

        self::$id = self::$sqlsvr->lastInsertId();
        self::$affected_rows = $sentencia->rowCount();
        self::$sqlsvr = NULL;

        return true;
    }

    //Manejo de consultas tipo Update 
    public function Modificar($table = null, $set = '', $where, $param)
    {
        $sentencia;
        self::$sql = "UPDATE " . $table . " SET " . $set . " WHERE " . $where . " ;";
        if (!($sentencia = self::$sqlsvr->prepare(self::$sql))) {
            self::$mensaje = "Falló la preparación: (" . self::$sqlsvr->errorInfoCode() . ")\n" . self::$sqlsvr->errorInfo();
            return false;
        }
        $ref = new ReflectionClass('PDOStatement');
        if (!($metodo = $ref->getMethod("bindParam"))) {
            self::$mensaje = "Falló el metodo bind param: (" . self::$sqlsvr->errorInfoCode() . ")\n" . self::$sqlsvr->errorInfo();
            return false;
        }

        for ($i = 0; $i < count($param); $i++) {
            $parametros[] = $param[$i];
        }
        foreach (array_keys($parametros) as $i) {
            if ($i > 0) $parametros[$i] = &$parametros[$i];
        }
        for ($i = 0; $i < count($parametros); $i++) {
            $j = $i + 1;
            $sentencia->bindParam($j, $parametros[$i]);
        }
        if ($exec = !$sentencia->execute()) {
            self::$mensaje = "Falló la ejecución:(" . $sentencia->errorInfoCode() . ") " . $sentencia->errorInfo();
            return false;
        }

        self::$affected_rows = $sentencia->rowCount();
        self::$sqlsvr = NULL;

        if (self::$affected_rows >= 1) {
            return true;
        } else {
            self::$mensaje = "No se afectaron registros";
            return false;
        }
    }

    //Manejo de consultas tipo Delete 
    public function Eliminar($table = null, $where, $param)
    {
        $sentencia;
        self::$sql = "DELETE TOP(1) FROM " . $table . " WHERE " . $where . " ;";
        if (!($sentencia = self::$sqlsvr->prepare(self::$sql))) {
            self::$mensaje = "Falló la preparación: (" . self::$sqlsvr->errorInfoCode() . ")\n" . self::$sqlsvr->errorInfo();
            return false;
        }
        $ref = new ReflectionClass('PDOStatement');
        if (!($metodo = $ref->getMethod("bindParam"))) {
            self::$mensaje = "Falló el metodo bind param: (" . self::$sqlsvr->errorInfoCode() . ")\n" . self::$sqlsvr->errorInfo();
            return false;
        }

        for ($i = 0; $i < count($param); $i++) {
            $parametros[] = $param[$i];
        }
        foreach (array_keys($parametros) as $i) {
            if ($i > 0) $parametros[$i] = &$parametros[$i];
        }
        for ($i = 0; $i < count($parametros); $i++) {
            $j = $i + 1;
            $sentencia->bindParam($j, $parametros[$i]);
        }
        if ($exec = !$sentencia->execute()) {
            self::$mensaje = "Falló la ejecución:(" . $sentencia->errorInfoCode() . ") " . $sentencia->errorInfo();
            return false;
        }

        self::$affected_rows = $sentencia->rowCount();
        self::$sqlsvr = NULL;

        return true;
    }

    //Manejo de consultas tipo EXEC SP
    public function execSP($sp, $variables = '', $param = null)
    {
        $cadena = '';
        for ($i = 0; $i < count($variables); $i++) {
            $cadena .= '@' . $variables[$i] . ' = ?';
            if ($i + 1 < count($variables)) $cadena .= ', ';
        }
        self::$sql = "EXEC " . $sp . " " . $cadena;
        if (!($sentencia = self::$sqlsvr->prepare(self::$sql))) {
            self::$mensaje = "Falló la preparación: (" . self::$sqlsvr->errorInfoCode() . ")\n" . self::$sqlsvr->errorInfo();
            return false;
        }

        if ($sp != '' && !is_null($param)) {
            $ref = new ReflectionClass('PDOStatement');
            if (!($metodo = $ref->getMethod("bindParam"))) {
                self::$mensaje = "Falló el metodo bind param: (" . self::$sqlsvr->errorInfoCode() . ")\n" . self::$sqlsvr->errorInfo();
                return false;
            }
            for ($i = 0; $i < count($param); $i++) {
                $parametros[] = $param[$i];
            }
            foreach (array_keys($parametros) as $i) {
                if ($i > 0) $parametros[$i] = &$parametros[$i];
            }

            for ($i = 0; $i < count($parametros) - 1; $i++) {
                $j = $i + 1;
                $prueba = $sentencia->bindParam($j, $parametros[$j], $this->read_param($parametros[0][$i]));
            }
        }
        if (!$sentencia->execute()) {
            self::$mensaje = "Falló la ejecución:(" . $sentencia->errorInfoCode() . ") " . $sentencia->errorInfo();
            return false;
        }
        
        self::$resultado = $sentencia;

        return true;
    }

    //Retorna el tipo de parámetro dependiendo del tipo de parámetro enviado
    public function read_param($type)
    {
        switch ($type) {
            case 'b':
                return PDO::PARAM_BOOL;
            break;
            case 'n':
                return PDO::PARAM_NULL;
            break;
            case 'i':
                return PDO::PARAM_INT;
            break;
            case 's':
                return PDO::PARAM_STR;
            break;
            case 'i':
                return PDO::PARAM_STR;
            break;
            case 'o':
                return PDO::PARAM_INPUT_OUTPUT;
            break;
            default:
                return false;
            break;
        }
    }

    //Para obtener la información arrojada por la consulta ejecutada de tipo object
    public function resulObject()
    {
        $rows = array();
        while ($row = self::$resultado->fetch(PDO::FETCH_OBJ)) {
            $rows[] = $row;
        }
        self::$affected_rows = self::$resultado->rowCount();
        return $rows;
    }

    //Para obtener la información arrojada por la consulta ejecutada de tipo array
    public function resulArray()
    {
        $rows = array();
        while ($row = self::$resultado->fetch(PDO::FETCH_ASSOC)) {
            $rows[] = $row;
        }
        self::$affected_rows = self::$resultado->rowCount();
        return $rows;
    }

    //Para obtener la sentencia de consulta ejecutada
    public function Sentencia()
    {
        if (DEBUG)
            return self::$sql;
        else
            return "";
    }

    //Para obtener el errorInfo de consulta ejecutada
    public function errorInfo($boolForce = false)
    {

        if (DEBUG || $boolForce)
            return self::$mensaje;
        else
            return "";

    }

    //Para obtener la cantidad de filas afectadas de consulta ejecutada
    public function affected_rows()
    {
        return self::$affected_rows;
    }

    //Para obtener el id de consulta ejecutada
    public function Id()
    {
        return self::$id;
    }
}