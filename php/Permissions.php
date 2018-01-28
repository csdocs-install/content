<?php

/**
 * Description of Permissions
 *
 * @author daniel
 */

require_once 'DataBase.php';
require_once "Log.php";
require_once "XML.php";
require_once "Session.php";


$RoutFile = dirname(getcwd());        

class Permissions {
    private $db;
    public function __construct() {
        $this->db = new DataBase();
    }
    public function ajax()
    {
        if(filter_input(INPUT_POST, "opcion")!=NULL and filter_input(INPUT_POST, "opcion")!=FALSE){
            
            $idSession = Session::getIdSession();
        
            if($idSession == null)
                return XML::XMLReponse ("Error", 0, "Permissions::No existe una sesión activa, por favor vuelva a iniciar sesión");

            $userData = Session::getSessionParameters();
            
            switch (filter_input(INPUT_POST, "opcion"))
            {

                case 'GetUserPermissions':$this->GetUserPermissions($userData); break;
                case 'ApplyPermissionsSettingsOfGroup':$this->ApplyPermissionsSettingsOfGroup($userData); break;
                case 'GetRepositoryAccessList':$this->GetRepositoryAccessList($userData); break;
                case 'GetAccessPermissionsList':$this->GetAccessPermissionsList($userData); break;
                case 'getSystemPermissions':$this->getSystemPermissions($userData); break;      
                case 'getAllUserPermissions': $this->getAllUserPermissions($userData); break;
            }
        }
    }
    
    function getSystemPermissions($userData)
    {        
        $instanceName = $userData['dataBaseName'];
        
        /* La subconsulta selecciona al nodo herramientas como punto de partida para construir el árbol (Herramientas)    */

        $SelelectMenus = "SELECT *FROM SystemMenu ORDER BY IdParent";   
        
        $ResultSelect = $this->db->ConsultaSelect($instanceName, $SelelectMenus);
        
        if($ResultSelect['Estado']!=1)
            XML::XMLReponse("Error", 0, "<p><b>Error</b> al obtener el listado de permisos del sistema. ". $ResultSelect['Estado'] ."</p>");

        $ArrayMenus = $ResultSelect['ArrayDatos'];       
               
        $doc  = new DOMDocument('1.0','utf-8');
        $doc->formatOutput = true;
        $root = $doc->createElement("MenusTree");
        $doc->appendChild($root);   
        
        for($cont = 0; $cont < count($ArrayMenus); $cont++)
        {
            
            $Menu = $doc->createElement("Menu");
            
            foreach ($ArrayMenus[$cont] as $node => $value)
            {
                $child = $doc->createElement($node, $value);
                $Menu->appendChild($child);
            }
            
            $root->appendChild($Menu);
        }                
                
        header ("Content-Type:text/xml");
        echo $doc->saveXML();           
        
    }    
    
    private function GetUserPermissions($userData)
    {        
        $DataBaseName = $userData['dataBaseName'];
        $IdUsuario = $userData['idUser'];
        $NombreUsuario =  $userData['userName'];
        $IdGrupo = $userData['idGroup'];
        $NombreGrupo = $userData['groupName'];
        $IdRepositorio = filter_input(INPUT_POST, "IdRepositorio");
        $RoutFile = dirname(getcwd());        

        $ArrayAccessPermissions = array(); 
        $ArraySystemMenus = array(); 
        
        $IfComeplement_= '';
        
        if(!file_exists("$RoutFile/Estructuras/$DataBaseName"))
            return XML::XMLReponse ("limitedAccess", 1, "Acceso a Diseñador de Formas");
        
        if((int)$IdGrupo>0)
            $IfComeplement_.=" smc.IdGrupo = $IdGrupo AND";
        
        if((int)$IdRepositorio > 0)
            $IfComeplement_.="  smc.IdRepositorio = $IdRepositorio AND";
        
        if(strlen($IfComeplement_) > 0)
            $IfComeplement_ = " WHERE $IfComeplement_";
        
        $IfComeplement = trim($IfComeplement_, "AND");        
        
        if(strcasecmp($IdUsuario, 1)==0 and strcasecmp($NombreUsuario, "root")==0)
            $QueryListPermissions = "SELECT smc.IdMenu, smc.IdGrupo, smc.IdUsuario, smc.IdRepositorio, sm.Nombre "
                                . "FROM SystemMenuControl smc INNER JOIN SystemMenu sm ON smc.IdMenu = sm.IdMenu "
                                . "WHERE smc.IdGrupo = 1";  
        else
            $QueryListPermissions = "SELECT smc.IdMenu, smc.IdGrupo, smc.IdUsuario, smc.IdRepositorio, sm.Nombre "
                                . "FROM SystemMenuControl smc INNER JOIN SystemMenu sm ON smc.IdMenu = sm.IdMenu "
                                . " $IfComeplement";        

        $ResultQueryListPermissions = $this->db->ConsultaSelect($DataBaseName, $QueryListPermissions);
        
        if($ResultQueryListPermissions['Estado']!=1)
            XML::XMLReponse("Error", 0, "<p><b>Error</b> al obtener los permisos del usuario</p><br><br>Detalles:<br><br>".$ResultQueryListPermissions['Estado']);
         
        for($cont = 0; $cont < count($ResultQueryListPermissions['ArrayDatos']); $cont++){
            $ArrayAccessPermissions[$ResultQueryListPermissions['ArrayDatos'][$cont]['IdMenu']] = array("IdMenu"=>$ResultQueryListPermissions['ArrayDatos'][$cont]['IdMenu'], "Nombre"=>$ResultQueryListPermissions['ArrayDatos'][$cont]['Nombre']);
        }
        
        $GetSystemMenu = "SELECT *FROM SystemMenu";
        $ResultGetSystemMenu = $this->db->ConsultaSelect($DataBaseName, $GetSystemMenu);
        
        if($ResultGetSystemMenu['Estado']!=1)
            XML::XMLReponse("Error", 0, "<p><b>Error</b> al obtener los Menús del Sistema</p><br><br>Detalles:<br><br>".$ResultGetSystemMenu['Estado']);
        
        for($cont = 0; $cont < count($ResultGetSystemMenu['ArrayDatos']); $cont++){
            $ArraySystemMenus[$ResultGetSystemMenu['ArrayDatos'][$cont]['IdMenu']] = array("IdMenu"=>$ResultGetSystemMenu['ArrayDatos'][$cont]['IdMenu'], "Nombre"=>$ResultGetSystemMenu['ArrayDatos'][$cont]['Nombre']);
        }              
                
        $ArrayDeniedPermissions = array_diff_key($ArraySystemMenus, $ArrayAccessPermissions);
        $ArrayPermissionsFile = $this->GetPermissionsFile();
        
        if(!is_array($ArrayPermissionsFile))
            XML::XMLReponse("Error", 0, "<p><b>Error</b> al abrír el documento con la lista de menús del sistema");            
        
        $doc  = new DOMDocument('1.0','utf-8');
        $doc->formatOutput = true;
        $root = $doc->createElement('Permissions');
        $doc->appendChild($root); 
        
        if(count($ArrayDeniedPermissions)>0)
            foreach ($ArrayDeniedPermissions as $value){
                $DeniedPermissions = $doc->createElement("DeniedPermissions");
                $Denied = $doc->createElement("IdMenu", $value['IdMenu']);
                $DeniedPermissions ->appendChild($Denied);
                $Name = $doc->createElement("Nombre",$value['Nombre']);
                $DeniedPermissions->appendChild($Name);
                $root->appendChild($DeniedPermissions);
            }
        
        if(count($ArrayAccessPermissions)>0)
            foreach ($ArrayAccessPermissions as $value){
                $AccesPermissions = $doc->createElement("AccessPermissions");
                $Acces = $doc->createElement("IdMenu", $value['IdMenu']);
                $AccesPermissions->appendChild($Acces);
                $Name = $doc->createElement("Nombre",$value['Nombre']);
                $AccesPermissions->appendChild($Name);
                $root->appendChild($AccesPermissions);
            }
            
        if(count($ArrayPermissionsFile)>0)
            foreach ($ArrayPermissionsFile as $key =>$value){
                $HtmlPermissionName = $doc->createElement("HtmlPermissionsName");
                $html = $doc->createElement("HtmlPermissionName", $value);
                $HtmlPermissionName->appendChild($html);
                $Name=$doc->createElement("Nombre",$key);
                $HtmlPermissionName->appendChild($Name);
                $root->appendChild($HtmlPermissionName);
            }
                
        header ("Content-Type:text/xml");
        echo $doc->saveXML();
          
    }
    
    private function GetPermissionsFile()
    {
       $PermissionsFile = array();
        if(file_exists('../Configuracion/SystemMenu/SystemMenu.ini'))
            $PermissionsFile = parse_ini_file('../Configuracion/SystemMenu/SystemMenu.ini');
        else
            return "<p><b>Error</b> no existe el archivo de Menús del Sistema</p>";
        
        return $PermissionsFile;
    }
    
    
    private function ApplyPermissionsSettingsOfGroup($userData)
    {
        $DataBaseName = $userData['dataBaseName'];
        
        $idUser = $userData['idUser'];
        $IdRepositorio = filter_input(INPUT_POST, "IdRepositorio");
        $SettingsXml = filter_input(INPUT_POST, "SettingsXml");
        $IdGroup = filter_input(INPUT_POST, "IdGrupo");
        
        if(!(int) $idUser == 1)
            return XML::XMLReponse ("Error", 0, "Solo el usuario root puede realizar cambios en permisos de grupo");
        
        if(!($xml=  simplexml_load_string($SettingsXml)))
            return XML::XMLReponse("Error", 0, "<p>El servidor no recibió correctamente la información</p>");
        
        if($IdGroup == 1)
            return XML::XMLReponse("SystemError", 0, "<p>No pueden modificarse los permisos de este grupo ya que pertenece al sistema.</p>");
        
        /* --------------- Configuraciones sobre Menus ------------- */  
            
        /* Se obtiene la configuración existente sobre el repositorio y luego se toma la diferencia entre 
         * la nueva configuración y la existente, para evitar multiples consultas a la BD  */
        
        $OldPermissionsMenu = array();
        $NewPermissionsMenu = array();
        $OldPermissionsRepository = array();
        $NewPermissionsRepository = array();
        
        $QSelectPermissions = "SELECT *FROM SystemMenuControl WHERE IdGrupo = $IdGroup AND IdRepositorio = $IdRepositorio";
        $ResultSelectPermissions = $this->db->ConsultaSelect($DataBaseName, $QSelectPermissions);
        
        if($ResultSelectPermissions['Estado']!=1)
            return XML::XMLReponse("Error", 0, "<p><b>Error</b> al obtener el listado completo de permissos del repositorio seleccionado</p><br><br>Detalles:<br><br>".$ResultSelectPermissions['Estado']);
        
        for($cont = 0; $cont < count($ResultSelectPermissions['ArrayDatos']) ; $cont++){
            $OldPermissionsMenu[$ResultSelectPermissions['ArrayDatos'][$cont]['IdMenu']] = $ResultSelectPermissions['ArrayDatos'][$cont]['IdMenu'];
        }        
        
        foreach ($xml->AccessMenu as $value){
            $Indice = (int)$value->IdMenu;
            $NewPermissionsMenu[$Indice] =(int)$value->IdMenu;
        }
        
        $ArrayNewPermissions = array_diff($NewPermissionsMenu, $OldPermissionsMenu);                                
        
        $WithoutAccessMenu_ = " DELETE FROM SystemMenuControl WHERE ";                        
        
        foreach ($xml->WithoutAccessMenu as $value)
        {
            $WithoutAccessMenu_.= " (IdMenu = $value->IdMenu AND IdGrupo = $IdGroup AND IdRepositorio = $IdRepositorio) OR";
        }                
               
        $WithoutAccessMenu = trim($WithoutAccessMenu_, "OR");
                     
        if(count($xml->WithoutAccessMenu)>0)
            if(($ResultWithoutAccessMenu = $this->db->ConsultaQuery($DataBaseName, $WithoutAccessMenu))!=1)
                return XML::XMLReponse("Error", 0, "<p><b>Error</b> al denegar acceso a los Menus Seleccionados</p>.<br><br>Detalles:<br><br> $ResultWithoutAccessMenu");
                
        $InsertIntoSystemMenuControl_ = "INSERT INTO SystemMenuControl (IdMenu, IdGrupo, IdRepositorio) VALUES ";
        $ValuesInsertIntoSystemMenu = '';
        
        foreach ($ArrayNewPermissions as $value)
        {
                $ValuesInsertIntoSystemMenu.=" ($value ,$IdGroup, $IdRepositorio),";
        }
        
        $InsertIntoSystemMenuControl = $InsertIntoSystemMenuControl_. trim($ValuesInsertIntoSystemMenu, ",");
        
        if(strcasecmp($ValuesInsertIntoSystemMenu, '')!=0)
            if(($ResultInsertIntoSystemMenuControl = $this->db->ConsultaQuery($DataBaseName, $InsertIntoSystemMenuControl))!=1)
                XML::XMLReponse("Error", 0, "<p><b>Error</b> al aplicar la configuración en los menùs seleccionados.</p><br>Detalles:<br><br> $ResultInsertIntoSystemMenuControl</p>");
            
        /* ------------------- Permissos sobre Repositorio ------------------- */
        $SelectRepositories = "SELECT * FROM RepositoryControl WHERE IdGrupo = $IdGroup";
        $ResultSelectRepositories = $this->db->ConsultaSelect($DataBaseName, $SelectRepositories);
        
        if($ResultSelectRepositories['Estado']!=1)
            return XML::XMLReponse("Error", 0, "<p><b>Error</b> al obtener los permissos de repositorio sobre el grupo.</p><br><br>Detalles:<br><br>".$ResultSelectRepositories['Estado']);
        
        for($cont = 0; $cont < count($ResultSelectRepositories['ArrayDatos']); $cont++)
        {
            $NewPermissionsRepository[$ResultSelectRepositories['ArrayDatos'][$cont]['IdRepositorio']] = $ResultSelectRepositories['ArrayDatos'][$cont]['IdRepositorio'];
        }
        
        foreach ($xml->AccessToTheRepository as $value)
        {
            $indice = (int)$value->IdRepository;
            $OldPermissionsRepository[$indice] = $indice;
        }
        
        $ArrayNewPermissionsRepository = array_diff($OldPermissionsRepository, $NewPermissionsRepository);
                    
        $InsertIntoRepositoryControl_ = "INSERT INTO RepositoryControl (IdGrupo, IdUsuario, IdRepositorio) VALUES ";
        $ValuesInsertToRepositoryControl = '';
        foreach ($ArrayNewPermissionsRepository as $value)
        {
                $ValuesInsertToRepositoryControl.=" ($IdGroup, 0, $value),";
        }
        
        $InsertIntoRepositoryControl = $InsertIntoRepositoryControl_. trim($ValuesInsertToRepositoryControl, ",");
                       
        if(strcasecmp($ValuesInsertToRepositoryControl, '')!=0)
            if(($ResultInsertIntoRepositoryControl = $this->db->ConsultaQuery($DataBaseName, $InsertIntoRepositoryControl))!=1)
                XML::XMLReponse("Error", 0, "<p>Error al registrar el acceso a repositorios. $ResultInsertIntoRepositoryControl</p>");
            
        $WithoutAccessRepository_ = " DELETE FROM RepositoryControl WHERE (";          
        $DeleteAccessToMenus_ = "DELETE FROM SystemMenuControl WHERE (";
        
        foreach ($xml->WithoutAccessToTheRepository as $value)
        {
            $WithoutAccessRepository_.= " IdRepositorio = $value->IdRepository OR";
            $DeleteAccessToMenus_.= " IdRepositorio = $value->IdRepository OR";
        }
               
        $WithoutAccessRepository = trim($WithoutAccessRepository_, "OR");        
        $WithoutAccessRepository.=") AND IdGrupo = $IdGroup";
        
        $DeleteAccessToMenus = trim($DeleteAccessToMenus_, "OR");
        $DeleteAccessToMenus.=") AND IdGrupo = $IdGroup";
        
        if(count($xml->WithoutAccessToTheRepository)>0)
        {
            if(($ResultWithoutAccessRepository = $this->db->ConsultaQuery($DataBaseName, $WithoutAccessRepository))!=1)
                XML::XMLReponse("Error", 0, "<p>Error al denegar acceso a los repositorios seleccionados. $ResultWithoutAccessRepository</p>");

            if(($ResultWithoutAccessToMenus = $this->db->ConsultaQuery($DataBaseName, $DeleteAccessToMenus))!=1)
                XML::XMLReponse("Error", 0, "<p><b>Error</b> al eliminar la configuración anterior del repositorio.</p><br><br>Detalles:<br><br> $ResultWithoutAccessToMenus");
        }
                                            
        XML::XMLReponse("ApplySettings", 1, "<p>Configuración Aplicada con éxito</p>");
    }
    
    private function GetRepositoryAccessList($userData)
    {
        $DataBaseName = $userData['dataBaseName'];

        $IdGrupo = filter_input(INPUT_POST, "idUserGroup");
        $NombreGrupo = filter_input(INPUT_POST, "userGroupName");
        
        $GetRepositoryPermissions = "SELECT *FROM RepositoryControl WHERE IdGrupo = $IdGrupo";
        $ResultGetRepositoryPermissions = $this->db->ConsultaSelect($DataBaseName, $GetRepositoryPermissions);

        if($ResultGetRepositoryPermissions['Estado']!=1)
            XML::XMLReponse("Error", 0, "<p><b>Error</b> al obtener la configuración de permisos sobre repositorios en el grupo <b>$NombreGrupo</b>.</p><br>Detalles:<br><br>".$ResultGetRepositoryPermissions['Estado']);
        
        $RepositoryPermissions = $ResultGetRepositoryPermissions['ArrayDatos'];
        
        $doc  = new DOMDocument('1.0','utf-8');
        $doc->formatOutput = true;
        $root = $doc->createElement('Permissions');
        $doc->appendChild($root); 
        for($cont = 0; $cont < count($RepositoryPermissions); $cont++)
        {
            $RepositoryXml = $doc->createElement("Repository");
            $IdRepositoryXml = $doc->createElement("IdRepositorio", $RepositoryPermissions[$cont]['IdRepositorio']);
            $RepositoryXml->appendChild($IdRepositoryXml);
            $root->appendChild($RepositoryXml);
        }
        header ("Content-Type:text/xml");
        echo $doc->saveXML();
    }
    
    private function GetAccessPermissionsList($userData)
    {
        $DataBaseName = $userData['dataBaseName'];
        $IdGrupo = filter_input(INPUT_POST, "IdGrupo");
        $NombreGrupo = filter_input(INPUT_POST, "NombreGrupo");
        $IdRepositorio = filter_input(INPUT_POST, "IdRepositorio");
//        $NombreRepositorio = filter_input(INPUT_POST, "NombreRepositorio");
        
        $GetRepositoryPermissions='';
        
        if($IdGrupo==1){
            $GetRepositoryPermissions = "SELECT sm.IdMenu, m.Type FROM SystemMenuControl sm 
                INNER JOIN RepositoryControl rc ON sm.IdGrupo = rc.IdGrupo 
                LEFT JOIN SystemMenu m ON sm.IdMenu = m.IdMenu 
                WHERE sm.IdGrupo = $IdGrupo AND rc.IdRepositorio = $IdRepositorio";
        }
        else{
            $GetRepositoryPermissions = "SELECT sm.IdMenu, m.Type FROM SystemMenuControl sm 
                INNER JOIN RepositoryControl rc ON sm.IdGrupo = rc.IdGrupo 
                LEFT JOIN SystemMenu m ON sm.IdMenu = m.IdMenu 
                WHERE sm.IdGrupo = $IdGrupo AND rc.IdRepositorio = $IdRepositorio AND sm.IdRepositorio = $IdRepositorio";
        }                             
        
        $ResultGetPermissions = $this->db->ConsultaSelect($DataBaseName, $GetRepositoryPermissions);

        if($ResultGetPermissions['Estado']!=1)
            return XML::XMLReponse("Error", 0, "<p><b>Error</b> al obtener la configuración de permisos sobre el grupo <b>$NombreGrupo</b>.</p><br>Detalles:<br><br>".$ResultGetPermissions['Estado']);
        
        $Permissions = $ResultGetPermissions['ArrayDatos'];
        
        XML::XmlArrayResponse("permissionsMenu", "Menu", $Permissions);
        
    }
    
    private function getAllUserPermissions($userData){
        
    }
    
    /**
     * @description Devuelve el listado completo de permisos de un usuario.
     * @param type $userData
     */
    public function getAllUserPermissionsArray($userData){
        $instanceName = $userData['dataBaseName'];
        $idUser = $userData['idUser'];
        $idUserGroup = $userData['idGroup'];
        
        if(!(int) $idUser > 0)
            return XML::XMLReponse ("Error", 0, "<p><b>Error</b> el usuario no tiene un identificador válido.</p>");
        
        if(!(int) $idUserGroup > 0)
            return XML::XMLReponse ("Error", 0, "<p><b>Error</b> el usuario no pertence a ningún grupo</p>");
        
        $select = "
            SELECT sm.*, smc.IdMenuControl, rc.IdRepositorio, cr.NombreRepositorio 
            FROM SystemMenu sm LEFT JOIN SystemMenuControl smc ON sm.IdMenu = smc.IdMenu 
            LEFT JOIN RepositoryControl rc ON smc.IdGrupo = rc.IdGrupo 
            LEFT JOIN CSDocs_Repositorios cr ON rc.IdRepositorio = cr.IdRepositorio 
            WHERE smc.IdGrupo = $idUserGroup
                ";
        
        $selectResult = $this->db->ConsultaSelect($instanceName, $select);
        
        if($selectResult['Estado'] != 1)
            return XML::XMLReponse ("Error", 0, "<p></b>Error</b> al obtener los permisos del usuario</p> Detalles:<br>".$selectResult['Estado']);
        
        return $selectResult['ArrayDatos'];
    }
}

$Permissions = new Permissions();
$Permissions->ajax();