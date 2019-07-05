<?php
/*------------------------------------------------------------------------------
 *  Description of Catalog
 * 
 *   Clase que administra los catálogos del sistema, responde a las peticiones del
 *   usuario tales como: 
 *      Mostrar el contenido de un catálogo
 *      Modificar
 *      Agregar
 *      Editar
 *      Agregar una nueva estructura de catálogo
 * 
 * @author Daniel
 *-----------------------------------------------------------------------------*/
$RoutFile = dirname(getcwd());        

require_once 'DataBase.php';
require_once 'Log.php';
require_once 'XML.php';
require_once 'Session.php';
require_once 'DesignerForms.php';

if(!isset($_SESSION))
    session_start();

class Catalog {
    protected $db;
    public function __construct() {
        $this->db = new DataBase();

        $this->Ajax();
    }
    
    private function Ajax()
    {
        if(filter_input(INPUT_POST, "opcion")!=NULL and filter_input(INPUT_POST, "opcion")!=FALSE){
            
            $idSession = Session::getIdSession();
        
            if($idSession == null)
                return XML::XMLReponse ("Error", 0, "Catalog::No existe una sesión activa, por favor vuelva a iniciar sesión");
            
            $userData = Session::getSessionParameters();
            
            switch (filter_input(INPUT_POST, "opcion"))
            {
                case 'GetListCatalogos':$this->GetListCatalogos($userData); break;
                case "GetCatalogRecordsInXml": $this->GetCatalogRecordsInXml($userData); break;
                case 'AddCatalogoXML':$this->AddCatalogoXML(); break;
                case 'ModifyCatalogRecord':$this->ModifyCatalogRecord($userData); break;
                case 'AddNewRecord':$this->AddNewRecord($userData); break;
                case 'AddNewColumn': $this->AddNewColumn($userData); break;
                case 'buildNewCatalog': $this->buildNewCatalog($userData); break;
            }
        }
    }  
    
    /***************************************************************************
     * Construye un nuevo catálogo a través de la interfaz de usuario.         *
     ***************************************************************************/
    
    private function buildNewCatalog($user){
        try {
            $dataBaseName = $user['dataBaseName'];
            $userName = $user['userName'];
            $idUser = $user['idUser'];
            $idRepository = filter_input(INPUT_POST, "idRepository");

            $xmlString = filter_input(INPUT_POST, "xml");
            $errorMessage = 0;

            if(!($xml = simplexml_load_string($xmlString))){
                return XML::XMLReponse("XXML recibido invalido");
            }

            if ($this->checkIfCatalogExists($dataBaseName, $xml->CrearEstructuraCatalogo->NombreCatalogo))
                return XML::XMLReponse ("Error", 0, "El catálogo ya existe");

            if(($result = $this->InsertCatalogIntoRepository($xml->CrearEstructuraCatalogo, $dataBaseName)) !=1)
                return XML::XMLReponse ("Error", 0, "No pudo ser creado el repositorio");
            else
                XML::XMLReponse ("newCatalogBuilded", 1, "Catálogo construido correctamente");
        } catch (Exception $e) {
            return XML::XMLReponse ("newCatalogBuilded", 1, "Ocurrio un error al construir el catálogo. ".$e->getMessage());
        }
    }

    private function checkIfCatalogExists($databaseName,$catalogName) {
        $query = "SELECT IdCatalogo FROM CSDocs_Catalogos WHERE NombreCatalogo = '$catalogName'";

        $res = $this->db->ConsultaSelect($databaseName, $query);

        if ($res['Estado'] != 1)
            throw new Exception("<p><b>Error</b> al comprobar existencia de catálogo. ".$ResultExistGroup['Estado']."</p>");

        return (count($res['ArrayDatos']) > 0) ? 1 : 0;
    }
    
    /***************************************************************************
     *  Devuelve el listado de catalogos ordenado por empresa y repositorio
     */
    private function GetListCatalogos($userData)
    {
        $BD = new DataBase();
        
        $DataBaseName = $userData['dataBaseName'];
        
        $Consulta = "select re.IdRepositorio, re.NombreRepositorio, re.ClaveEmpresa, em.IdEmpresa, em.NombreEmpresa,
        em.ClaveEmpresa, ca.IdCatalogo, ca.NombreCatalogo from CSDocs_Repositorios re inner join CSDocs_Empresas em on em.ClaveEmpresa=re.ClaveEmpresa
        inner join CSDocs_Catalogos ca on ca.IdRepositorio=re.IdRepositorio";
        
        $Catalogos = $BD->ConsultaSelect($DataBaseName, $Consulta);
       
        if($Catalogos['Estado']!=1)
            return XML::XMLReponse("Error", 0, "<p>Ocurrió un error al consultar el listado de catálogos ".$Catalogos['Estado']."</p>"); 
        
        XML::XmlArrayResponse("Catalogos", "Empresas", $Catalogos['ArrayDatos']);
    }   
    
    private function AddNewColumn($userData)
    {
        $BD= new DataBase();
        $XML=new XML();
        $Log = new Log();
        $Designer = new DesignerForms();
        
        $RepositoryName = filter_input(INPUT_POST, "RepositoryName");
        $NewFieldName = filter_input(INPUT_POST, "FieldName");
        $Required = filter_input(INPUT_POST, "RequiredField");
        $FieldLength = filter_input(INPUT_POST, "FieldLength");
        $FieldType = filter_input(INPUT_POST, "FieldType");
        $CatalogName = filter_input(INPUT_POST, "CatalogName");
        $DataBaseName = $userData['dataBaseName'];
        $IdUser = $userData['idUser'];
        $UserName = $userData['userName'];
        
        if($FieldLength>0)
        {
            $PropertyFieldLength = "long $FieldLength###";   /* Archivo config */
            $FieldLength = "($FieldLength)";            
        }
        else
        {
            $PropertyFieldLength = "";
            $FieldLength = '';
        }
        
        if(strcasecmp($FieldType, "TEXT")!=0)
               $AddForeigKey =  ", ADD FOREIGN KEY ($NewFieldName) REFERENCES $RepositoryName"."_$CatalogName ($NewFieldName)";
        else
            $AddForeigKey = '';
        
        if(strcasecmp($Required, "true")==0)
            $Null = "NOT NULL";
        else
            $Null = "";      
        $AlterCatalog = "ALTER TABLE $RepositoryName"."_$CatalogName ADD COLUMN $NewFieldName $FieldType $FieldLength $Null";        
//        echo "<br><br>$AlterCatalog<br>";
        if(($ResultAlterCatalog = $BD->ConsultaQuery($DataBaseName, $AlterCatalog))!=1)
        {
            $XML->ResponseXML("Error", 0, "<p><b>Error</b> al insertar la nueva columna en <b>$CatalogName</b></p><br>Detalles:<br><br>$ResultAlterCatalog");
            return 0;
        }  
        
        $NewProperty = "Properties###name $NewFieldName###type $FieldType###".$PropertyFieldLength."required $Required###";
        $RegisterProperty = DesignerForms::AddPropertyIntoStructureConfig($DataBaseName, $RepositoryName."_$CatalogName", $NewProperty);       
        
        if(strcasecmp($RegisterProperty, 1)!=0)
        {
            echo $RegisterProperty;
            $RemoveNewColumn = "ALTER TABLE $CatalogName DROP COLUMN $NewFieldName";
            $BD->ConsultaQuery($DataBaseName, $RemoveNewColumn);
            return 0;
        }                              
        
        $XML->ResponseXML("AddNewColumn", 1, "Columna $NewFieldName agregada a $CatalogName");                
    }
    
    private function AddNewRecord($userData)
    {
        $BD = new DataBase();
        
        $repositoryName = filter_input(INPUT_POST, "repositoryName");
        $NombreCatalogo = filter_input(INPUT_POST, "CatalogName");
        $xmlResponse = filter_input(INPUT_POST, "XmlReponse");
        $DataBaseName = $userData['dataBaseName'];
        $RegistroCatalogo='';    /* Cadena que genera el registro de la fila insertada en ../Configuracion/NombreCatalogo.ini */
        
        /* Xml que contiene los campos a regresar al cliente para la inserción en la tabla que lista un catálogo */
        
        $doc  = new DOMDocument('1.0','utf-8');
        $doc->formatOutput = true;
        $root = $doc->createElement("AddNewRecord");
        $doc->appendChild($root);   
        
        /* Fin declaración XML */
        
        
        $xml=  simplexml_load_string($xmlResponse);
        $cadenaCampos='';
        $cadenaValores='';        
        
        foreach ($xml->MetaData as $metadata)
        {
            $RegistroCatalogo.=$metadata->value." , "; 
            $cadenaCampos.=$metadata->name.",";
            $type=$metadata->type;
            $value=$metadata->value;
            
            if(strcasecmp($type,"int")==0 or strcasecmp($type,"integer")==0 or strcasecmp($type,"float")==0 or strcasecmp($type,"double")==0) /* Si detecta un tipo numerico */
            {
                if(!(is_numeric("$value")))
                    $cadenaValores.="0,";
                else
                    $cadenaValores.=$metadata->value.",";
            }
            else
                $cadenaValores.="'".$metadata->value."'".",";
            
            /* Campos y valores devueltos al cliente */
            $Field = $doc->createElement("Field");
            $FieldName = $doc->createElement("FieldName", $metadata->name);
            $Field->appendChild($FieldName);
            $FieldType = $doc->createElement("FieldType", $type);
            $Field->appendChild($FieldType);
            $FieldValue = $doc->createElement("FieldValue", $value);
            $Field->appendChild($FieldValue);
            $root->appendChild($Field);
        }
        
        $RegistroCatalogo=  trim($RegistroCatalogo,' , ');
        
        $cadenaValores = trim($cadenaValores,',');  /* Quita la última Coma ( , ) */
        $cadenaCampos = trim($cadenaCampos,',');
        
        $query="INSERT INTO $repositoryName"."_$NombreCatalogo (".$cadenaCampos .") VALUES (".$cadenaValores.")";
         
        $IdNewRow = $BD->ConsultaInsertReturnId($DataBaseName, $query);
        
        if(!$IdNewRow>0)
            return XML::XMLReponse("Error", 0, "<p>Error al agregar los nuevos datos al catálogo $NombreCatalogo verifique sus datos. $IdNewRow</p>");
            
        $IdRow = $doc->createElement("IdCatalog", $IdNewRow);
        $root->appendChild($IdRow);
        $Mensaje = $doc->createElement("Mensaje", "Registro añadido");
        $root->appendChild($Mensaje);
        header ("Content-Type:text/xml");
        echo $doc->saveXML();  
            
    }
    
    private function ModifyCatalogRecord($user)
    {
        $BD = new DataBase();
        $Log = new Log();
        
        $DataBaseName = $user['dataBaseName'];
        $IdUser = $user['idUser'];
        $UserName = $user['userName'];
        $repositoryName = filter_input(INPUT_POST, "repositoryName");
        $IdCatalog = filter_input(INPUT_POST, "IdCatalog");
        $CatalogName = filter_input(INPUT_POST, "CatalogName");
        $FieldName = filter_input(INPUT_POST, "FieldName");                        
        $FieldType = filter_input(INPUT_POST, "FieldType");
        $NewValue = filter_input(INPUT_POST, "NewValue");
        
        if(strcasecmp($FieldType, "varchar")==0 or strcasecmp($FieldType, "int")==0 or strcasecmp($FieldType, "integer")==0 or strcasecmp($FieldType, "float")==0)
            $NewField = "$FieldName = $NewValue";
        else
            $NewField = "$FieldName = '$NewValue'";
        
        $UpdateCatalog = "UPDATE $repositoryName"."_"."$CatalogName SET $NewField WHERE Id$CatalogName = $IdCatalog";
               
        if(($ResultUpdate = $BD->ConsultaQuery($DataBaseName, $UpdateCatalog))!=1)
        {
            XML::XMLReponse("Error", 0, "<p><b>Error</b> al actualizar el catálogo</p><br>Detalles:<br><br>$ResultUpdate");
            return 0;
        }
        
        echo $NewValue;   /* Se imprime el valor ya que la salida se plasma sobre el campo que se está modificando */
        
        $Log->Write('10', $IdUser, $UserName, " '$CatalogName' que contiene la clave '$IdCatalog' su nuevo valor es '$NewValue'", $DataBaseName);
    }
    
    private function GetCatalogRecordsInXml($userData)
    {
        
        $DataBaseName = $userData['dataBaseName'];
        $IdUsuario = $userData['idUser'];
//        $IdRepositorio=  filter_input(INPUT_POST, "IdRepositorio");
        $repositoryName = filter_input(INPUT_POST, "repositoryName");
        $NombreCatalogo =  filter_input(INPUT_POST, "CatalogName");
        $TipoCatalogo =  filter_input(INPUT_POST, "CatalogType");
        $ListSearch='';
        
//        if($TipoCatalogo=='ListSearch'){$ListSearch=' LIMIT 25';}
        $catalogs = $this->getCatalogRecords($DataBaseName, $repositoryName, $NombreCatalogo);
        
        if(!is_array($catalogs))
            return XML::XMLReponse ("Error", 0, $catalogs);
        
        XML::XmlArrayResponse("Catalog", "CatalogRecord", $catalogs); 
    }
    
    public function getCatalogRecords($dataBaseName, $repositoryName, $catalogName, $idRow = null){
        $BD= new DataBase();
        
        $Consulta = "";
        if($idRow==null)
            $Consulta = "SELECT *FROM $repositoryName"."_"."$catalogName";
        else
            $Consulta = "SELECT *FROM $repositoryName"."_"."$catalogName WHERE Id$catalogName = $idRow";
        
        $Catalogos = $BD->ConsultaSelect($dataBaseName, $Consulta);    
        
        if($Catalogos['Estado']!=1)
            return $Catalogos['Estado'];
        
        return $Catalogos['ArrayDatos'];
    }
    
     /***************************************************************************
     * Crea un nuevo Catálogo
     */
    private function AddCatalogoXML()
    {
        $XML=new XML();
        
        $ValidacionEsquema=$XML->validacion_xml();
        if(strcasecmp($ValidacionEsquema, 0)==0)
            return;
        
        $RutaXML=$ValidacionEsquema;
        $xml=  simplexml_load_file($RutaXML);  
        
        foreach ($xml->children() as $valor)
        {
            $array_nodos[]=$valor->getName();
        }
        
       if(array_search('EstructuraRepositorio', $array_nodos)!==false)
        {
            $Repositorio=$xml->EstructuraRepositorio->CrearEstructuraCatalogo;                                              
            $InsertRepositorio=$this->InsertCatalogIntoRepository($Repositorio);
            if($InsertRepositorio === 1)
                echo "<p>Catálogo(s) insertado(s) correctamente</p>";
        }
        else
        {
            $XML->ResponseXML("Error", 0, "No se encontro una estructura para insertar");
        } 
    }
    
    function InsertCatalogIntoRepository($CatalogDetail, $DataBaseName)
    {
        $DB = new DataBase();
        
        $NombreCatalogo = $CatalogDetail->NombreCatalogo;
        $NombreRepositorio = $CatalogDetail->NombreCatalogo['nombreRepositorio'];
        $IdRepository = $CatalogDetail->NombreCatalogo['idRepositorio'];
                                 
        $list = $CatalogDetail->DefinitionUsersProperties;

        $RepositoryFields_ = $DB->GetTableFields($DataBaseName, $NombreRepositorio);
        if($RepositoryFields_['Estado']!=1)
        {
            echo "<p><b>Error</b> al obtener los campos del Repositorio</p><br>Detalles:<br><br>".$RepositoryFields_['Estado'];
            return 0;
        }

        $RepositoryFields = $RepositoryFields_['ArrayDatos'];

        for ($cont = 0; $cont < count($RepositoryFields); $cont++)
        {
            if(strcasecmp($RepositoryFields[$cont]['Field'], $NombreCatalogo)==0){
                echo "<p>El nombre del catálogo que quiere ingresar a este repositorio ya <b>existe</b></p>";
                return 0;
            }                
        }

        $List=$list->children();/* Properties de un List */
        $CatalogAttr = array("Tipo"=>"ListSearch","Struct"=>$list);

        $TablaCatalogo="CREATE TABLE IF NOT EXISTS $NombreRepositorio"."_$NombreCatalogo (Id$NombreCatalogo int(11) NOT NULL AUTO_INCREMENT, ";

        foreach ($List as $valor)
        {
            if($valor['long']>0)
                $TablaCatalogo.=$valor['name']." ". $valor['type']."(".$valor['long']."), ";
            else
                $TablaCatalogo.=$valor['name']." ". $valor['type'].", ";                                      
        }

        $configStructure = array("TipoEstructura"=>"Catalogo","DataBaseName"=>$DataBaseName,"Estructura"=>$CatalogAttr);

        $TablaCatalogo.="PRIMARY KEY (`Id$NombreCatalogo`)" /* Al modificar, modificar también en la llave foranea del query de repositorio */
            . ") ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8";

        if(($ResultCrearCatalogo = $DB->crear_tabla($DataBaseName,$TablaCatalogo))!=1)
            return "<p>Error al crear el catálogo <b>$NombreCatalogo</b></p>";

        /* Se registra el catálogo en la tabla catálogos */
        $InsertCatalog = "INSERT INTO CSDocs_Catalogos (IdRepositorio, NombreCatalogo) VALUES ($IdRepository, '$NombreCatalogo')";
        if(($ResultInsertCatalog = $DB->ConsultaQuery($DataBaseName, $InsertCatalog))!=1)
        {
            echo "<p><b>Error</b> al registrar el catálogo</p>Detalles:<br>".$ResultInsertCatalog;
            $DeleteCatalog = "DROP TABLE $NombreRepositorio"."_$NombreCatalogo";
            $DB->ConsultaQuery($DataBaseName, $DeleteCatalog);
            return 0;
        }

        $DB->WriteConfigCatalogo("$NombreRepositorio"."_$NombreCatalogo",$configStructure);

        $AlterTable = "ALTER TABLE $NombreRepositorio ADD COLUMN $NombreCatalogo INT NOT NULL, ADD INDEX $NombreCatalogo ($NombreCatalogo), ADD FOREIGN KEY ($NombreCatalogo) REFERENCES $NombreRepositorio"."_$NombreCatalogo (Id$NombreCatalogo)";

        if(($ResultAlterTable = $DB->ConsultaQuery($DataBaseName, $AlterTable))!=1){
            $DeleteCatalog = "DROP TABLE $NombreRepositorio"."_$NombreCatalogo";
            $DB->ConsultaQuery($DataBaseName, $DeleteCatalog);
            echo  "<b>Error</b> al crear las relaciones del catálogo <b>$NombreCatalogo</b><br><br>Detalles:<br><br> $ResultAlterTable";
            return 0;
        }
        
        return 1;
    }
    
    public function getCatalogsArray($dataBaseName ,$idRepository){
        
        $DB = new DataBase();
                
        $query = "SELECT IdCatalogo, NombreCatalogo FROM CSDocs_Catalogos WHERE IdRepositorio = $idRepository";
        
        $queryResult = $DB->ConsultaSelect($dataBaseName, $query);
        
        if($queryResult['Estado']!=1)
            return $queryResult['Estado'];
        
        return $queryResult['ArrayDatos'];
        
    }
    /*
     * Devuelve los catálogos filtrados por permisos de grupo o usuario
     */
    public function getFilteredArrayCatalogsDetail($dataBaseName, $idRepository, $idGroup = 0, $idUser = 0){
        
        $DB = new DataBase();
                
        $query = "select re.IdRepositorio, re.NombreRepositorio, re.ClaveEmpresa, em.IdEmpresa, em.NombreEmpresa,
        em.ClaveEmpresa, ca.IdCatalogo, ca.NombreCatalogo from CSDocs_Repositorios re inner join CSDocs_Empresas em on em.ClaveEmpresa=re.ClaveEmpresa
        inner join CSDocs_Catalogos ca on ca.IdRepositorio=re.IdRepositorio AND re.IdRepositorio=$idRepository";
        
        $queryResult = $DB->ConsultaSelect($dataBaseName, $query);
        
        if($queryResult['Estado']!=1)
            return $queryResult['Estado'];
        
        return $queryResult['ArrayDatos'];
        
        
    }

    public function getArrayCatalogsNames($dataBaseName, $idRepository, $repositoryName = null){
        $DB = new DataBase();
        
        $query = "";
        
        if($repositoryName==null)
            $query = "SELECT IdCatalogo, NombreCatalogo FROM CSDocs_Catalogos WHERE IdRepositorio = $idRepository";
        else
            $query = "SELECT ca.NombreCatalogo, re.IdRepositorio, em.IdEmpresa, em.ClaveEmpresa
                    FROM CSDocs_Catalogos ca 
                    RIGHT JOIN CSDocs_Repositorios re ON ca.IdRepositorio = re.IdRepositorio  
                    RIGHT JOIN CSDocs_Empresas em ON re.ClaveEmpresa = em.ClaveEmpresa 
                    WHERE re.NombreRepositorio = '$repositoryName'";
        
        $queryResult = $DB->ConsultaSelect($dataBaseName, $query);
        
        if($queryResult['Estado']!=1)
            return $queryResult['Estado'];
        
        return $queryResult['ArrayDatos'];
    }
    
    public function deleteCatalog($dataBaseName, $idRepository, $repositoryName, $catalogName){
        $DB = new DataBase();
        
        $query = "DROP TABLE $repositoryName"."_$catalogName";
        
        if(($queryResult = $DB->ConsultaQuery($dataBaseName, $query))!=1)
                return "Error al intentar eliminar el catálogo $catalogName. $queryResult";
        
        $removeFromRegister = "DELETE FROM CSDocs_Catalogos WHERE IdRepositorio = $idRepository";
        
        if(($resultRemoveFromRegister = $DB->ConsultaQuery($dataBaseName, $removeFromRegister))!=1)
                return "Error al intentar eliminar del registro el catálogo $catalogName. Detalles: $resultRemoveFromRegister";
        
        return 1;
    }
}

$Catalog = new Catalog();
