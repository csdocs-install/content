<?php
class DBConexion{

    static $params;

    public static function params(){
        return self::$params;
    }

    public static function load(){
        $RoutFile = dirname(getcwd());
        $Conexion = parse_ini_file($RoutFile . "/Configuracion/ConexionBD/BD.ini", true);
        $host=$Conexion['Conexion']['Host'];
        $user=$Conexion['Conexion']['User'];
        $password=$Conexion['Conexion']['Password'];
        $port=$Conexion['Conexion']['Port'];
        if(!isset($port)){
            $port=3306;
        }

        self::$params=[
            "host"=>$host,
            "user"=>$user,
            "password"=>$password,
            "port"=>$port

        ];
    }

}
