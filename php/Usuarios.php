<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Usuarios
 *
 * @author daniel
 */
require_once 'DataBase.php';
require_once 'XML.php';
require_once 'DesignerForms.php';
require_once 'Log.php';
require_once __DIR__ . '/Encrypter.php';

$RoutFile = dirname(getcwd());

class Usuarios {

//    static $userName =  null;
//    static $idUser = 0;
//    static $idGroup = 0;
//    static $groupName = null;

    public function Ajax() {
        if (filter_input(INPUT_POST, "opcion") != NULL and filter_input(INPUT_POST, "opcion") != FALSE) {

            if (strcasecmp(filter_input(INPUT_POST, "opcion"), "ExistRoot") == 0) {
                $this->ExisteRoot();
                return;
            }

            $idSession = Session::getIdSession();

            if ($idSession == null)
                return XML::XMLReponse("Error", 0, "Usuarios::No existe una sesión activa, por favor vuelva a iniciar sesión");

            $userData = Session::getSessionParameters();

            switch (filter_input(INPUT_POST, "opcion")) {
                case 'AddUser': $this->AddUser($userData);
                    break;
                case 'AddXmlUser': $this->AddXmlUser();
                    break;
                case 'UsersList': $this->UsersList($userData);
                    break;
                case 'GetUsersDiffGroup': $this->GetUsersDiffGroup();
                    break;
                case 'AddUserToGroup': $this->AddUserToGroup();
                    break;
                case 'DeleteUserFromGroup': $this->DeleteUserFromGroup();
                    break;
                case 'GetInfoUser':$this->GetInfoUser($userData);
                    break;
                case 'ModifyUser':$this->ModifyUser();
                    break;
                case 'CM_RemoveUser':$this->CM_RemoveUser($userData);
                    break;
                case 'changeUserPassword': $this->changeUserPassword($userData);
                    break;
                case 'closeUserSession': $this->closeUserSession($userData);
                    break;
            }
        }
    }

    private function closeUserSession($user) {
        Session::destroySession();

        return XML::XMLReponse("userSessionClosed", 1, "Sesión finalizada");
    }

    private function AddUser($user) {
        $BD = new DataBase();

        $UserXml = filter_input(INPUT_POST, "UserXml");
        $IdUser = $user['idUser'];
        $UserName = $user['userName'];
        $DataBaseName = $user['dataBaseName'];
        $RoutFile = dirname(getcwd());
        $UserLogin = '';
        $Password = '';

        if (!($xml = simplexml_load_string($UserXml)))
            return XML::XMLReponse("Error", 0, "El xml recibido es incorrecto. Es posible que no se haya formado correctamente.<br><br>" . $xml);

        if (!file_exists("$RoutFile/version/config.ini"))
            return XML::XMLReponse("Error", 0, "<p><b>Error</b><br><br> El registro de configuración de CSDocs no existe. Reportelo directamente con CSDocs</p>");

        $EncryptedSetting = parse_ini_file("$RoutFile/version/config.ini", true);

        if ($EncryptedSetting === FALSE)
            return XML::XMLReponse("Error", 0, "<p><b>Error</b> en el registro de configuración de CSDocs $EncryptedSetting</p>");

        $UsersNumber_ = $this->CheckUsersNumber($DataBaseName);

        if ($UsersNumber_['Estado'] != 1)
            return XML::XMLReponse("Error", 0, "<p><b>Error</b> al obtener el numéro de usuarios en el sistema</p><br>Detalles:<br><br>" . $UsersNumber_['Estado']);

        $UsersNumber = $UsersNumber_['ArrayDatos']['COUNT(*)'];

        $UsersNumberEncrypted = $EncryptedSetting['UsersNumber'];

        $UserNumberDecrypted = Encrypter::decrypt($UsersNumberEncrypted);

        if ($UsersNumber >= $UserNumberDecrypted)
            return XML::XMLReponse("warning", 0, "<p>Advertencia</p> <br><br><p>Numéro de usuarios alcanzado para su versión de CSDocs</p>");

        $ValuesChain = '';
        $FieldsChain = '';

        foreach ($xml as $field) {
            $FieldValue = $field->FieldValue;
            $FiledType = $field->FieldType;
            $FieldName = $field->FieldName;

            if (strcasecmp($FieldName, "Password") == 0) {
                $Password = $FieldValue;
                if (strlen($Password) <= 4)
                    return XML::XMLReponse("warning", 0, "<p><b>Error</b> contraseña demasiado corta </p>");
                else
                    $FieldValue = md5($FieldValue);
            }

            if (strcasecmp($FieldName, "Login") == 0) {
                $UserLogin = $FieldValue;
                if (strcasecmp($UserLogin, 'root') == 0)
                    return XML::XMLReponse("warning", 0, "<p>No puede utilizarse este nombre de usuario ya que es parte del sistema</p>");
            }

            $FormattedField = DataBase::FieldFormat($FieldValue, $FiledType);

            if (strcasecmp($FormattedField, 0) != 0) {
                $ValuesChain.= $FormattedField . ", ";
                $FieldsChain.=$FieldName . ", ";
            }
        }

        $ValuesChain_ = trim($ValuesChain, ", ");
        $FieldsChain_ = trim($FieldsChain, ", ");

        $ExistUser = $this->CheckIfExistUser($DataBaseName, $UserLogin);

        if ($ExistUser === 1)
            return XML::XMLReponse('warning', 0, "El nombre de usuario ya está registrado");
        else if ($ExistUser !== 0 and $ExistUser !== 1)
            return XML::XMLReponse("Error", 0, "<p><b>Error</b> al comprobar existencia del usuario que desea ingresar</p><br>Detalles:<br><br>$ExistUser");

        $QInsertUser = "INSERT INTO CSDocs_Usuarios ($FieldsChain_) VALUES ($ValuesChain_)";

        if (!($IdNewUser = $BD->ConsultaInsertReturnId($DataBaseName, $QInsertUser)) > 0)
            return XML::ReturnError("<p><b>Error</b> al intentar registrar el nuevo usuario</p><br>Detalles:<br><br>$IdNewUser");

        Log::WriteEvent("15", $IdUser, $UserName, " '$UserLogin'", $DataBaseName);
        
        $doc  = new DOMDocument('1.0','utf-8');
        $doc->formatOutput = true;
        $root = $doc->createElement("userAdded");
        $doc->appendChild($root);   
        $IdGrupo = $doc->createElement("idUser",$IdNewUser);
        $root->appendChild($IdGrupo);
        $Mensaje = $doc->createElement("Mensaje","Usuario agregado con éxito");
        $root->appendChild($Mensaje);          
        header ("Content-Type:text/xml");
        echo $doc->saveXML();        
        
    }

    private function CheckUsersNumber($DataBaseName) {
        $DB = new DataBase();
        $estado = true;
        $conexion = $DB->Conexion();
        if (!$conexion) {
            $estado = mysqli_error($conexion). "<br>" . mysqli_errno($conexion);
            $error = array("Estado" => $estado, "ArrayDatos" => 0);
            return $error;
        }

        mysqli_select_db($conexion, $DataBaseName);
        $query = "";
        if (strcasecmp($DataBaseName, "cs-docs") == 0)
            $query = "SELECT COUNT(*) FROM Usuarios";
        else
            $query = "SELECT COUNT(*) FROM CSDocs_Usuarios";

        $select = mysqli_query($conexion, $query);

        if (!$select) {
            $estado = mysqli_error($conexion). "<br>" . mysqli_errno($conexion);
            $error = array("Estado" => $estado, "ArrayDatos" => 0);
            return $error;
        }

        $ResultadoConsulta = mysqli_fetch_assoc($select);
        mysqli_close($conexion);

        $Resultado = array("Estado" => $estado, "ArrayDatos" => $ResultadoConsulta);
        return $Resultado;
    }

    private function CheckIfExistUser($DataBaseName, $UserName) {
        $BD = new DataBase();
        $ExistUser = "";
        if (strcasecmp("cs-docs", $DataBaseName) == 0)
            $ExistUser = $BD->ConsultaSelect($DataBaseName, "SELECT *FROM Usuarios WHERE Login = '$UserName'");
        else
            $ExistUser = $BD->ConsultaSelect($DataBaseName, "SELECT *FROM CSDocs_Usuarios WHERE Login = '$UserName'");

        if ($ExistUser['Estado'] != 1) {
            echo "<p>Error al comprobar existencia del usuario en el sistema. " . $ExistUser['Estado'] . "</p>";
            return $ExistUser['Estado'];
        }
        if (count($ExistUser['ArrayDatos']) > 0)
            return 1;
        else
            return 0;
    }

    private function CM_RemoveUser($userData) {
        $BD = new DataBase();

        $DataBaseName = $userData['dataBaseName'];
        $IdUser = $userData['idUser'];
        $IdRemoveUser = filter_input(INPUT_POST, "IdRemoveUser");
        $nombre_usuario = $userData['userName'];
        $NameUserToRemove = filter_input(INPUT_POST, "NameUserToRemove");
        $Password = filter_input(INPUT_POST, "Password");

        if (strcasecmp($NameUserToRemove, 'root') == 0 or (int) $IdRemoveUser == 1)
            return XML::XMLReponse("Error", 0, "No se puede eliminar al usuario root");

        if ($IdRemoveUser == $IdUser and $nombre_usuario != 'root')
            return XML::XMLReponse("Error", 0, "No puede eliminarse así mismo.");

        $ConsultaDelete = "DELETE FROM CSDocs_Usuarios WHERE IdUsuario=$IdRemoveUser";
        $ResultadoConsulta = $BD->ConsultaQuery($DataBaseName, $ConsultaDelete);

        if ($ResultadoConsulta != 1)
            return XML::XMLReponse("Error", 0, "Error de al intentar eliminar el usuario. $ResultadoConsulta. ");

        $qDeleteFromControl = "DELETE FROM GruposControl WHERE IdUsuario = $IdRemoveUser";
        if (($resultDeleteFromControl = $BD->ConsultaQuery($DataBaseName, $qDeleteFromControl)) != 1) {
            
        }

        $DeleteFromCsdocs = "DELETE FROM Usuarios WHERE Login = '$NameUserToRemove' AND Password = '$Password'";
        if (($ResultDelete = $BD->ConsultaQuery("cs-docs", $DeleteFromCsdocs)) != 1)
            return XML::XMLReponse("Error", 0, "<p><b>Error</b> al eliminar el usuario del contenedor de usuarios de CSDocs. Esto puede alterar el control del límite de usuarios de su versión de CSDocs, pongase en contacto con soporte técnico</p>");

        XML::XMLReponse("RemoveUser", 1, "<p>Usuario $NameUserToRemove eliminado con éxito </p>");

        Log::WriteEvent("17", $IdUser, $nombre_usuario, " '$NameUserToRemove'", $DataBaseName);
    }

    private function ModifyUser() {
        $BD = new DataBase();
        $XmlModify = filter_input(INPUT_POST, "ModifyFileXml");
        $DataBaseName = filter_input(INPUT_POST, "DataBaseName");
        $IdUser = filter_input(INPUT_POST, "IdUser");
        $IdModifyUser = filter_input(INPUT_POST, "IdModifyUser");
        $UserNameModiffied = filter_input(INPUT_POST, "UserNameModiffied");
        $UserName = filter_input(INPUT_POST, "UserName");
        $xml = simplexml_load_string($XmlModify);

        $Update = "UPDATE CSDocs_Usuarios SET ";

        for ($cont = 0; $cont < count($xml->Campo); $cont++) {
            $value = $xml->Campo[$cont]->value;
            $name = $xml->Campo[$cont]->name;
            $type = $xml->Campo[$cont]->type;

            if (strcasecmp($name, "Password") == 0) {
                if (strlen($value) <= 4) {
                    XML::XMLReponse("warning", 0, "<p><b>Error</b> contraseña demasiado corta </p>");
                    return 0;
                } else
                    $value = md5($value);
            }

            $FormattedField = DataBase::FieldFormat($value, $type);


            if (strcasecmp($FormattedField, 0) != 0) {
                $Update.="$name = $FormattedField,";
            }
        }

        $Update = trim($Update, ',');
        $Update.=" WHERE IdUsuario=$IdModifyUser";

        $ResulUpdate = $BD->ConsultaQuery($DataBaseName, $Update);
        if ($ResulUpdate != 1) {
            XML::XMLReponse("Error", 0, "Error al actualizar los datos. $ResulUpdate");
            return 0;
        }

        Log::WriteEvent("16", $IdUser, $UserName, " $UserNameModiffied", $DataBaseName);
        XML::XMLReponse("Modify", 1, "Datos actualizados con éxito.");
    }

    /*     * *************************************************************************
     * Editar la informacion de un Usuario
     */

    private function GetInfoUser($userData) {
        $BD = new DataBase();
        $designer = new DesignerForms();
        $IdUser = filter_input(INPUT_POST, "IdUser");
        $DataBaseName = $userData['dataBaseName'];

        $QueryInfoUser = "SELECT *FROM CSDocs_Usuarios WHERE IdUsuario=$IdUser";
        $InfoUser = $BD->ConsultaSelect($DataBaseName, $QueryInfoUser);
        $EstructuraConfig = parse_ini_file("../Configuracion/$DataBaseName.ini", true);
        $ArrayStructuUser = $designer->ReturnStructure("Usuarios", $EstructuraConfig["Usuarios"]);
        $StructDefault = $designer->ReturnStructureDefault("Usuarios", $EstructuraConfig["Usuarios"]);
        /*  Una vez obtenida la estructura definida por el usuario se
         *  devuelven los datos en un XML hacia el cliente */
//        var_dump($StructDefault);
        $doc = new DOMDocument('1.0', 'utf-8');
        $doc->formatOutput = true;
        $root = $doc->createElement("Usuario");
        $doc->appendChild($root);
        for ($cont = 0; $cont < count($StructDefault); $cont++) {
            $NombreCampo = $StructDefault[$cont]['name'];
            $ValorCampo = $InfoUser['ArrayDatos'][0][$NombreCampo];
            $columna = $doc->createElement($NombreCampo, $ValorCampo);
            $root->appendChild($columna);
        }

        for ($cont = 0; $cont < count($ArrayStructuUser); $cont++) {
            $NombreCampo = $ArrayStructuUser[$cont]['name'];
            $ValorCampo = $InfoUser['ArrayDatos'][0][$NombreCampo];
            $columna = $doc->createElement($NombreCampo, $ValorCampo);
            $root->appendChild($columna);
        }

        header("Content-Type:text/xml");
        echo $doc->saveXML();
    }

    /*     * *************************************************************************
     * 
     * Retira a un usuario de un grupo y lo mueve a NoGroup
     * 
     */

    private function DeleteUserFromGroup() {
        $BD = new DataBase();
        $DataBaseName = filter_input(INPUT_POST, "DataBaseName");
        $IdUser = filter_input(INPUT_POST, "IdUser");

        $Consulta = "UPDATE CSDocs_Usuarios SET IdRol=1 WHERE IdUsuario=$IdUser";
        if (($Grupo = $BD->ConsultaQuery($DataBaseName, $Consulta)) != true) {
            XML::XMLReponse("Error", 0, "Error en la asignación de grupo. $Grupo");
        } else {
            XML::XMLReponse("DeleteUserFromGroup", "1", "Usuario movido a NoGroup");
        }
    }

    /*     * ****************************************************************************
     * 
     * Agrega un Usuario a un Grupo independientemente de en que grupo se encuentre
     * 
     */

    private function AddUserToGroup() {
        $BD = new DataBase();
        $DataBaseName = filter_input(INPUT_POST, "DataBaseName");
        $IdUser = filter_input(INPUT_POST, "IdUser");
        $IdGroupDestination = filter_input(INPUT_POST, "IdGroupDestination");

        $Consulta = "UPDATE CSDocs_Usuarios SET IdRol=$IdGroupDestination WHERE IdUsuario=$IdUser";
        if (($Grupo = $BD->ConsultaQuery($DataBaseName, $Consulta)) != true) {
            XML::XMLReponse("Error", 0, "Error en la asignación de grupo. $Grupo");
        } else {
            XML::XMLReponse("UserToGroup", "1", "Agregado con éxito");
        }
    }

    /*     * *************************************************************************
     * Obtiene los usuarios que pertenecen a otros grupos diferentes a uno seleccionado
     */

    private function GetUsersDiffGroup() {
        $XML = new XML();
        $BD = new DataBase();
        $DataBaseName = filter_input(INPUT_POST, "DataBaseName");
        $IdGroup = filter_input(INPUT_POST, "IdGroup");
        $Consulta = "select IdUsuario,Nombre,IdRol,Login from CSDocs_Usuarios  where IdRol!=$IdGroup and estatus=1";
        $Usuarios = $BD->ConsultaSelect($DataBaseName, $Consulta);
        if ($Usuarios['Estado'] != true) {
            XML::XMLReponse("Error", 0, "<p>Ocurrió un error al consultar los usuarios " . $Consulta['Estado'] . "</p>");
            return;
        }
        $XML->ResponseXmlFromArray("Usuarios", "Usuario", $Usuarios['ArrayDatos']);
    }

    /*     * **************************************************************************
     * Esta función regresa el listado de usuarios registrados en el sistema
     */

    private function UsersList($userData) {
        $BD = new DataBase();
        $DataBaseName = $userData['dataBaseName'];
        
        $Consulta = "
                SELECT usu.*, gc.IdGrupo FROM CSDocs_Usuarios usu 
                LEFT JOIN GruposControl gc ON usu.IdUsuario = gc.IdUsuario 
                LEFT JOIN GruposUsuario gu ON gu.IdGrupo = gc.IdGrupo 
                where usu.estatus=1";
        
        $Usuarios = $BD->ConsultaSelect($DataBaseName, $Consulta);

        if ($Usuarios['Estado'] != 1) 
            return XML::XMLReponse("Error", 0, "<p>Ocurrió un error al consultar los usuarios " . $Usuarios['Estado'] . "</p>");
        
            XML::XmlArrayResponse("ListUsers", "Usuario", $Usuarios['ArrayDatos']);
    }

    /*     * **************************************************************************
     *  Se agrega un usuario nuevo al sistema desde la consola de Usuarios.
     */

    function AddXmlUser() {
        $XML = new XML();
        $BD = new DataBase();
        $ValidacionEsquema = $XML->validacion_xml();
        if (strcasecmp($ValidacionEsquema, 0) == 0) {
            return;
        }
        $RutaXML = $ValidacionEsquema;
        $xml = simplexml_load_file($RutaXML);
        foreach ($xml->children() as $valor) {
            $array_nodos[] = $valor->getName();
        }

        /*         * ***************************** USUARIOS **************************** */

        if (array_search('EstructuraUsuarios', $array_nodos) !== false) {
            echo "<p>Encontrado CrearEstructuraUsuarios Peso = " . count($xml->EstructuraUsuarios->InsertUsuario) . "</p>";
            $StructUsuario = $xml->EstructuraUsuarios->InsertUsuario;
            $BD->insertar_usuario($StructUsuario);
        } else {
            $XML->ResponseXML("Error", 0, "<p>Introduzca un XML con la estructura para Usuarios</p>");
        }
    }

    /*     * ************************************************************************
     * function: @SearchRoot
     * Comprueba que exista el usuario root al momento de iniciar sesión
     * ************************************************************************ */

    private function ExisteRoot() {
        /* Se crea instancia por default y user root */
        $DataBase = new DataBase();
        $DataBase->CreateInstanciaCSDOCS();
        $root = $DataBase->ExistUserRoot();
        if (($root['Peso'] == 0)) {
            $DataBase->InsertUserRoot();
        }
    }

    function changeUserPassword($user) {
        $DB = new DataBase();

        $dataBaseName = $user['dataBaseName'];
        $idUser = $user['idUser'];
        $userName = $user['userName'];
        $newPassword = filter_input(INPUT_POST, "newPassword");
        $md5Password = md5($newPassword);

        $query = "";

        if (strcasecmp("root", $userName) == 0) {
            $query = "UPDATE Usuarios SET Password = '$md5Password' WHERE IdUsuario = 1";
            $dataBaseName = "cs-docs";
        } else {
            $query = "UPDATE CSDocs_Usuarios SET Password = '$md5Password' WHERE IdUsuario = $idUser";
        }

        if (($resultUpdate = $DB->ConsultaQuery($dataBaseName, $query)) != 1)
            return XML::XMLReponse("Error", 0, "No se fué posible actualizar la contraseña. $resultUpdate");

        return XML::XMLReponse("passwordChanged", 1, "Contraseña actualizada");
    }

}

$usuarios = new Usuarios();
$usuarios->Ajax();
