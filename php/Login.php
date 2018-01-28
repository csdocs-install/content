<?php

/* * *****************************************************************************
 *  Clase para buscar al usuario en la BD y acceso al sistema                  *
 *            
 *  @author daniel                                                                 *
 * ***************************************************************************** */
//require_once 'DataBase.php';
require_once "Log.php";
require_once "XML.php";
//require_once 'Usuarios.php';
require_once 'Session.php';

if (!isset($_SESSION))
    session_start();

$RoutFile = dirname(getcwd());

class Login {
    public function __construct() {
        
//        $this->ajax();
    }

    public function ajax() {
        switch (filter_input(INPUT_POST, "opcion")) {
            case 'Login': $this->login();
                break;
            case 'checkSessionExistance': $this->checkSessionExistance();
                break;
        }
    }

    public function checkSessionExistance() {

        $idSession = Session::getIdSession();

        if ($idSession == null)
            return XML::XMLReponse("widthOutSession", 0, "No existe sesión activa.");

        $userParameters = Session::getSessionParameters();

        $idInstance = $userParameters['idDataBase'];

        if (!((int) $idInstance > 0))
            $idInstance = 0;

        $sessionParameters = array('IdUsuario' => $userParameters['idUser'], 'Login' => $userParameters['userName'],
            'IdGrupo' => $userParameters['idGroup'], 'Nombre' => $userParameters['groupName'],
            'idSession' => $userParameters['idSession'], 'dataBaseName' => $userParameters['dataBaseName'],
            'idDataBase' => $idInstance);

        Session::$idSession = $userParameters['idSession'];
       
        require 'Permissions.php';

        $permissions = new Permissions();
        
        $permissionsArray = $permissions->getAllUserPermissionsArray($_SESSION);
        Session::setPermissions($permissionsArray);
        
        $this->loginResponse($sessionParameters, $permissionsArray);
    }

    private function login() {

        $RoutFile = dirname(getcwd());

        $user = trim(filter_input(INPUT_POST, "UserName"));
        $pass = trim(filter_input(INPUT_POST, "Password"));
        $DataBaseName = filter_input(INPUT_POST, "instanceName");
        $IdDataBase = filter_input(INPUT_POST, "IdDataBase");

//        $idSession = Session::getIdSession();
        $ResultSelect = null;

        if (strlen($user) > 0)
            $ResultSelect = $this->searchRegisterUser($DataBaseName, $user, $pass);

        if (!is_array($ResultSelect))
            return XML::XMLReponse("Error", 0, "<p><b>Error</b> de inicio de sesión.</p><br>Detalles:<br><p> " . $ResultSelect . "</p>");


        if (count($ResultSelect) === 0)
            $Resultado = array("Login" => 0, "IdUsuario" => -1, "IdGrupo" => 0, "Nombre" => 0, 'dataBaseName' => $DataBaseName);
        else {
            $Resultado = $ResultSelect;

            DataBase::$dataBaseName = $DataBaseName;
            DataBase::$idDataBaseName = $IdDataBase;

            if (!($IdDataBase > 0))
                $Resultado['dataBaseName'] = "NoDataBase";
            else
                $Resultado['dataBaseName'] = $DataBaseName;
        }


        $Resultado['idDataBase'] = $IdDataBase;

        Session::$idSession = Session::createSession($IdDataBase, $DataBaseName, $Resultado['IdUsuario'], $Resultado['Login'], $Resultado['IdGrupo'], $Resultado['Nombre']);
            
        require_once 'Permissions.php';

        $permissions = new Permissions();
        $permissionsArray = ((int)$IdDataBase > 0) ? $permissions->getAllUserPermissionsArray($_SESSION) : array();

        Session::setPermissions($permissionsArray);
        Session::$idSession = Session::getIdSession();

        $this->loginResponse($Resultado, $permissionsArray);
    }

    private function loginResponse($Resultado, $permissionsArray) {
        $doc = new DOMDocument('1.0', 'utf-8');
        $doc->formatOutput = true;
        $root = $doc->createElement('StartSession');
        $doc->appendChild($root);
        $idInstance = $doc->createElement("idInstance", $Resultado['idDataBase']);
        $root->appendChild($idInstance);
        $instance = $doc->createElement("instanceName", $Resultado['dataBaseName']);
        $root->appendChild($instance);
        $Estado = $doc->createElement("Estado", 1);
        $root->appendChild($Estado);
        $Login = $doc->createElement("Login", $Resultado['Login']);
        $root->appendChild($Login);
        $Id = $doc->createElement("IdUsuario", $Resultado['IdUsuario']);
        $root->appendChild($Id);
        $IdGrupo = $doc->createElement("IdGrupo", $Resultado['IdGrupo']);
        $root->appendChild($IdGrupo);
        $NombreGrupo = $doc->createElement("NombreGrupo", $Resultado['Nombre']);
        $root->appendChild($NombreGrupo);
        $xmlIdSession = $doc->createElement("idSession", Session::$idSession);
        $root->appendChild($xmlIdSession);
        $permissions = $doc->createElement("permissions");
        for ($cont = 0; $cont < count($permissionsArray); $cont++){
            $idMenu = md5($permissionsArray[$cont]['IdMenu']);
            $type = md5($permissionsArray[$cont]['Type']);
            $idRepository =  md5($permissionsArray[$cont]['IdRepositorio']);
            $permission = $doc->createElement("permission");
            $permissionKey = $doc->createElement("menu", $idMenu);
            $permission->appendChild($permissionKey);
            $permissionType = $doc->createElement("type", $type);
            $permission->appendChild($permissionType);
            $repository = $doc->createElement("repository", $idRepository);
            $permission->appendChild($repository);
            $permissions->appendChild($permission);          
        }
        $root->appendChild($permissions);
        header("Content-Type:text/xml");
        echo $doc->saveXML();

        Log::WriteEvent("1", $Resultado['IdUsuario'], $Resultado['IdUsuario'], " '" . $Resultado['Login'] . "'", $Resultado['dataBaseName']);
    }

    public function searchRegisterUser($instanceName, $userName, $password) {
        $RoutFile = dirname(getcwd());

        $bd = new DataBase();

        $ResultSelect = array();

        if (strcasecmp($userName, 'root') == 0) {
            $SelectUsuario = "SELECT *FROM Usuarios WHERE Login COLLATE utf8_bin ='root' and Password COLLATE utf8_bin ='" . md5(trim($password)) . "'";

            $ResultSelect = $bd->ConsultaSelect("cs-docs", $SelectUsuario);
        } else {
            $SelectUsuario = "SELECT usu.IdUsuario, usu.Login, gc.IdGrupo, gu.Nombre FROM CSDocs_Usuarios usu 
            INNER JOIN GruposControl gc ON gc.IdUsuario=usu.IdUsuario
            LEFT JOIN GruposUsuario gu ON gu.IdGrupo = gc.IdGrupo
            WHERE usu.Login  COLLATE utf8_bin ='$userName' AND usu.Password  COLLATE utf8_bin ='" . md5(trim($password)) . "'";

            if (!file_exists("$RoutFile/Estructuras/$instanceName")) {
                $ResultSelect['Estado'] = 1;
                $ResultSelect['ArrayDatos'] = array();
            } else
                $ResultSelect = $bd->ConsultaSelect($instanceName, $SelectUsuario);
        }

        if ($ResultSelect['Estado'] != 1)
            return $ResultSelect['Estado'];

        if (count($ResultSelect['ArrayDatos']) > 0) {
            if (strcasecmp("root", $userName) == 0) {
                $ResultSelect['ArrayDatos'][0]['IdGrupo'] = 1;
                $ResultSelect['ArrayDatos'][0]['Nombre'] = "Administradores";
            }

            return $ResultSelect['ArrayDatos'][0];
        } else
            return array();
    }
    
}

$LoginClass = new Login();
$LoginClass->ajax();
