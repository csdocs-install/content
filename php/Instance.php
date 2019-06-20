<?php
/**
 * Description of Instance
 *
 * @author Daniel
 */

require_once 'DataBase.php';
require_once 'XML.php';
require_once 'Session.php';
require_once 'Encrypter.php';

if (!isset($_SESSION))
    session_start();

class Instance {
    public function __construct() {
        $option = filter_input(INPUT_POST, "option");
        switch ($option)
        {
            case 'getInstances': $this->getInstances(); break;
            case "DeleteInstance": $this->DeleteInstance(); break;
            case 'buildNewInstance': $this->buildNewInstance(); break;
        }
    }

    /**
     * creates a new instance
     * @return string|void
     */
    private function buildNewInstance(){
        $idInstance = 0;
        $instanceName = filter_input(INPUT_POST, "instanceName");
        $userName = filter_input(INPUT_POST, "userName");

        try {
            $DB = new DataBase();

            $RoutFile = dirname(getcwd());


            if(($checkTotalInstances = $this->checkTotalInstances()) != 1)
                return XML::XMLReponse ("Error", 0, $checkTotalInstances);

            $createSchema = "CREATE DATABASE $instanceName 
                        DEFAULT CHARACTER SET utf8
                        DEFAULT COLLATE utf8_general_ci";

            if(!file_exists("$RoutFile/Estructuras")) {
                if(! mkdir("$RoutFile/Estructuras",  0777, false))
                    throw new Exception("No se pudo crear directorio Estructuras");
            }

            if(file_exists("$RoutFile/Estructuras/$instanceName"))
                return XML::XMLReponse("Error", 0, "La instancia ingresada ya existe.");

            if(file_exists("$RoutFile/Configuracion/$instanceName.ini"))    /* Elimina el archivo de configuración anterior sí existe */
                unlink ("$RoutFile/Configuracion/$instanceName.ini");

            /* Archivo que almacena la configuración de la estrucutra de usuarios, empresas, repositorios, etc */
            touch("$RoutFile/Configuracion/$instanceName.ini");

            if(($result = $DB->ConsultaQuery("", $createSchema))!=1)
                return XML::XMLReponse ("Error", 0, "<b>Error</b> al intentar crear la instancia $instanceName.<br>Detalles:<br>$result" );

            $InsertInstance = "INSERT INTO instancias (NombreInstancia, fechaCreacion, usuarioCreador) VALUES ('$instanceName', '".date('Y-m-d H:i:s')."', '$userName')";

            if(!(($idInstance = $DB->ConsultaInsertReturnId("cs-docs", $InsertInstance))>0)){
                $DB->ConsultaQuery("", "DROP DATABASE IF EXISTS $instanceName");
                return XML::XMLReponse("Error", 0, "<b>Error</b> al intentar registrar la instancia. <br>Detalles:<br>$idInstance");
            }

            if(($enterpriseStructure = $DB->createEnterpriseDefaultConfiguration($instanceName))!=1){
                $this->removeInstanceFromCSDocs($idInstance, $instanceName, 0, $userName, 1);
                return XML::XMLReponse("Error", 0, $enterpriseStructure);
            }

            if(($userStructure = $DB->createUsersDefaultConfiguration($instanceName))!=1){
                $this->removeInstanceFromCSDocs($idInstance, $instanceName, 0, $userName, 1);
                return XML::XMLReponse("Error", 0, $userStructure);
            }

            if(($result = $DB->createUsersControl($instanceName))!=1){
                $this->removeInstanceFromCSDocs($idInstance, $instanceName, 0, $userName, 1);
                return "Error al intentar crear el control de <b>Usuarios</b> en la instancia <b>$instanceName</b><br><br>".$result;
            }
            if(($resultBuildInstanceControl = $DB->CreateCSDocsControl($instanceName))!=1){
                $this->removeInstanceFromCSDocs($idInstance, $instanceName, 0, $userName, 1);
                return XML::XMLReponse ("Error", 0, "<b>Error</b> al crear la estructura de control de la instancia <b>$instanceName</b>. $resultBuildInstanceControl");
            }
            if($resultBuildInstanceControl == 0){
                $this->removeInstanceFromCSDocs($idInstance, $instanceName, 0, $userName, 1);
            }

            mkdir("$RoutFile/Estructuras/$instanceName", 0777);

            return $this->returnInstanceCreatedResponse($idInstance, $instanceName);
        } catch (Exception $e) {
            if($idInstance > 0)
                $this->removeInstanceFromCSDocs($idInstance, $instanceName, 0, $userName, 1);

            $this->rollbackInstance($instanceName);
            return "Error al crear la instancia ".$e->getMessage();
        }
    }

    private function rollbackInstance($instanceName) {
        try {
            $DB = new DataBase();

            $RoutFile = dirname(getcwd());

            exec("rm -rf $RoutFile/Estructuras/$instanceName");

            $QueryDrop = "DROP DATABASE IF EXISTS $instanceName";

            if(($Result = $DB->ConsultaQuery("cs-docs", $QueryDrop))!=1) {
                XML::XMLReponse("Error", 0, "<p><b>Error</b> al eliminar la instancia $instanceName</p>".$QueryDrop);
                return 0;
            }

            unlink("$RoutFile/Configuracion/$instanceName.ini");

        } catch (Exception $e) {
            return "Error in rollback instance $instanceName ".$e->getMessage();
        }

    }
    
    private function returnInstanceCreatedResponse($idInstance, $instanceName){
        
        $doc  = new DOMDocument('1.0','utf-8');
        libxml_use_internal_errors(true);
        $doc->formatOutput = true;
        $root = $doc->createElement("newInstanceBuilded");
        $doc->appendChild($root); 
        $Estado=$doc->createElement("Estado",1);
        $root->appendChild($Estado);
        $Mensaje=$doc->createElement("Mensaje","Instancia '$instanceName' creada con éxito");
        $root->appendChild($Mensaje);
        $idInstanceElement = $doc->createElement("idInstance", $idInstance);
        $root->appendChild($idInstanceElement);
        header ("Content-Type:text/xml");
        echo $doc->saveXML();
        
    }
    
    private function checkTotalInstances(){

        $RoutFile = dirname(getcwd());
        
        if(!file_exists("$RoutFile/version/config.ini"))
            return "<p><b>Error</b><br><br> El registro de configuración de CSDocs no existe. Reportelo directamente con CSDocs</p>";
        
        $EncryptedSetting = parse_ini_file("$RoutFile/version/config.ini", true);
        
        if($EncryptedSetting === FALSE)
            return "<p><b>Error</b> en el registro de configuración de CSDocs $EncryptedSetting</p>";
        
        if(!isset($EncryptedSetting['InstancesNumber']))
            return "No existe registro del total de instancias permitidas.";
        
        $getTotalInstances = $this->getTotalInstances();
        
        if($getTotalInstances['Estado']!=1)
            return $getTotalInstances['Estado'];
      
        $totalInstances = $getTotalInstances['ArrayDatos'][0]['COUNT(*)']; 
        
        $autorizeEncriptTotalInstance = Encrypter::checkControlOf("InstancesNumber");

        if((int)$totalInstances >= (int) $autorizeEncriptTotalInstance)
                return "Límite alcanzado de instancias permitidas para su versión";
        
        return 1;
        
    }
    
    private function getTotalInstances(){
        $DB = new DataBase();
        $query = "SELECT COUNT(*) FROM instancias";
        $result = $DB->ConsultaSelect("cs-docs", $query);
        
        return $result;
    }
    
    private function DeleteInstance()
    {
        $IdUser = filter_input(INPUT_POST, "IdUser");
        $UserName = filter_input(INPUT_POST, "UserName");
        $IdInstance = filter_input(INPUT_POST, "IdInstance");
        $InstanceName = filter_input(INPUT_POST, "InstanceName");
        
        $this->removeInstanceFromCSDocs($IdInstance, $InstanceName, $IdUser, $UserName);
    }
    
    private function removeInstanceFromCSDocs($IdInstance, $InstanceName, $idUser, $userName, $duringExecution = 0){
        $DB = new DataBase();
        
        $userData = null;
        if(!$duringExecution){
            $idSession = Session::getIdSession();

            if($idSession == null)
                return XML::XMLReponse ("Error", 0, "removeInstanceFromCSDocs:No existe una sesión activa, por favor vuelva a iniciar sesión");
            
            $userData = Session::getSessionParameters();

        }
                
        /* Sí el usuario inicio sesión en la misma instancia que va a eliminar, se destruye la sesión */
        if(is_array($userData)){
            if(isset($userData['dataBaseName']))
                if(strcasecmp($userData['dataBaseName'], $InstanceName)==0)
                    Session::destroySession();
        }
        
        $RoutFile = dirname(getcwd());
        
        $QueryDrop = "DROP DATABASE IF EXISTS $InstanceName";
        if(($Result = $DB->ConsultaQuery("cs-docs", $QueryDrop))!=1)
        {
            XML::XMLReponse("Error", 0, "<p><b>Error</b> al eliminar la instancia $InstanceName</p>".$QueryDrop);
            return 0;
        }
        
        $DeleteRegister = "DELETE FROM instancias WHERE IdInstancia = $IdInstance";
        if(($ResultDelete = $DB->ConsultaQuery("cs-docs", $DeleteRegister))!=1)
        {
            XML::XMLReponse("Error", 0, "<p><b>Error</b> al eliminar el registro de la instancia en cs-docs</p>");
            return 0;
        }
                
        if(file_exists("$RoutFile/Configuracion/$InstanceName.ini"))
            unlink("$RoutFile/Configuracion/$InstanceName.ini");
        
        if(file_exists("$RoutFile/Configuracion/$InstanceName"))
            exec("rm -R $RoutFile/$InstanceName");
        
        if(file_exists("$RoutFile/Configuracion/Catalogos/$InstanceName"))
            exec("rm -R $RoutFile/Configuracion/Catalogos/$InstanceName");
        
        if(file_exists("$RoutFile/Estructuras/$InstanceName"))
            exec("rm -R $RoutFile/Estructuras/$InstanceName");    
        
        if(file_exists("$RoutFile/Configuracion/MassiveUpload/$InstanceName"))
            exec("rm -R $RoutFile/Configuracion/MassiveUpload/$InstanceName"); 
        
        if(file_exists("$RoutFile/Configuracion/Catalogs/$InstanceName"))
            exec("rm -R $RoutFile/Configuracion/Catalogs/$InstanceName"); 
        
        if(file_exists("$RoutFile/Configuracion/EmptyTrash/$InstanceName"))
            exec("rm -R $RoutFile/Configuracion/EmptyTrash/$InstanceName"); 
        
        if(file_exists("$RoutFile/Configuracion/RestoreTrash/$InstanceName"))
            exec("rm -R $RoutFile/Configuracion/RestoreTrash/$InstanceName"); 
        
        if(file_exists("$RoutFile/Configuracion/DeleteDirectory/$InstanceName"))
            exec("rm -R $RoutFile/Configuracion/DeleteDirectory/$InstanceName");  
        
        if(file_exists("/volume1/Publisher/$InstanceName"))
            exec("rm -R /volume1/Publisher/$InstanceName");  
        
        if(file_exists("$RoutFile/Log/$InstanceName"))
            exec ("rm -R $RoutFile/Log/$InstanceName");
                
        XML::XMLReponse("DeleteInstance", 1, "Instancia $InstanceName eliminada");
    }
    
    private function getInstances()
    {        
        $instances = $this->getInstancesArray();
        
        if(is_array($instances))
            XML::XmlArrayResponse("Instances", "Instance", $instances);
        else
            XML::XMLReponse("Error", 0, "<p><b>Error</b> al obtener el listado de instancias del sistema</p><br><br>Detalles:<br><br>".$instances);

    }
    
    function getInstancesArray()
    {
        $DB = new DataBase();
        
        $QueryInstances = "SELECT *FROM instancias ORDER BY NombreInstancia";
        $ResultQuery = $DB->ConsultaSelect("cs-docs", $QueryInstances);
        
        if($ResultQuery['Estado']!=1)
            return $ResultQuery['Estado'];
        else
            return $ResultQuery['ArrayDatos'];
    }
    
}

$instances = new Instance();
