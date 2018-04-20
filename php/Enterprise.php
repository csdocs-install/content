<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Enterprise
 *
 * @author Daniel
 */
$RoutFile = dirname(getcwd());        

require_once 'XML.php';
require_once 'DataBase.php';
require_once 'Repository.php';
require_once 'Session.php';

if(!isset($_SESSION))
    session_start();

class Enterprise {

    public function __construct() {
        $this->Ajax();
    }

    private function Ajax() {
        if(filter_input(INPUT_POST, "option")!=NULL and filter_input(INPUT_POST, "option")!=FALSE){
            
            $idSession = Session::getIdSession();
            
            if($idSession == null)
                return XML::XMLReponse ("Error", 0, "Enterprise::No existe una sesión activa, por favor vuelva a iniciar sesión");

            $userData = Session::getSessionParameters();
            
            switch (filter_input(INPUT_POST, "option")) {
                case 'GetEnterprises': $this->GetEnterprises($userData);
                    break;
                case 'NewField': $this->NewField();
                    break;
                case 'DeleteField':$this->DeleteField($userData);
                    break;
                case 'AddNewRegister': $this->AddNewRegister($userData);
                    break;
                case 'DeleteEnterprise':$this->DeleteEnterprise($userData);
                    break;
                case 'ModifyEnterprise':$this->ModifyEnterprise($userData);
                    break;
                default : break;
            }
        }
    }
    
    private function ModifyEnterprise($userData)
    {
        $DB = new DataBase();
        
        $DataBaseName = $userData['dataBaseName'];
        $IdEnterprise = filter_input(INPUT_POST, "IdEnterprise");
        $Xml = filter_input(INPUT_POST, "Xml");
        $ValuesChain = '';
        $UpdateEnterprise_ = "UPDATE CSDocs_Empresas SET ";

        
        if(!($xml =  simplexml_load_string($Xml)))
        {
            $Error='';
          
            $errors=libxml_get_errors();
            
            for ($aux=0;$aux<count($errors); $aux++) {
                $Error.= $this->display_xml_error($errors[$aux]);
            }
            
            libxml_clear_errors();   /* Se limpia buffer de errores */          
            
            return XML::XMLReponse ("Error", 0, "<p><b>Error</b> al cargar el xml generado con los datos de la nueva empresa, es posible que no se haya formado correctamente $Error</p>");
        }
//        $varchar = "varchar";
//        if(strcasecmp("varchar", $varchar)==0)
//                echo "varchar encontrado";
//        return;
        foreach ($xml->Field as $Value)
        {
            $FieldName = $Value->FieldName;
            $FieldValue =$Value->FieldValue;
            $RequiredField = $Value->RequiredField;
            $FieldType = $Value->FieldType;
                        
            $FieldValue_ = trim($FieldValue, "");
            $FieldType_  = trim($FieldType);
            
            $Formated = DataBase::FieldFormat($FieldValue_, $FieldType_);
//            echo "<p>Analizando $FieldType $FieldValue =  $Formated</p>";
            $UpdateEnterprise_.= " $FieldName = $Formated,";
            
            $ValuesChain.="$Formated, ";
        }
        
        $OldEnterpriseKey = trim($xml->OldEnterpriseKey, "");
        $NewEnterpriseKey = trim($xml->NewEnterpriseKey, "");
                
        $UpdateEnterprise = trim($UpdateEnterprise_, ",");
        
        $UpdateEnterprise.=" WHERE IdEmpresa = $IdEnterprise";
//        echo $UpdateEnterprise; return;
        if(($UpdateResult = $DB->ConsultaQuery($DataBaseName, $UpdateEnterprise))!=1)
                return XML::XMLReponse ("Error", 0, "<p><b>Error</b> al intentar actualizar la información</p><br>Detalles:<br><br>$UpdateResult");
        
        if(strcasecmp($OldEnterpriseKey, $NewEnterpriseKey)!=0)
        {
            $UpdateRepositories = "UPDATE CSDocs_Repositorios SET ClaveEmpresa = '$NewEnterpriseKey' WHERE ClaveEmpresa = '$OldEnterpriseKey'";
            
            if(($ResultUpdateRepositories = $DB->ConsultaQuery($DataBaseName, $UpdateRepositories))!=1)
                return XML::XMLReponse("Error", 0, "<p><b>Error</b> al intentar actualizar la clave de empresa en Repositorios</p><br>Detalles:<br><br>$ResultUpdateRepositories");
        }
        
       
          
        XML::XMLReponse("ModifiedEnterprise", 1, "Datos actualizados de la empresa ");
    }
    
    private function AddNewRegister($userData)
    {        
        $DB = new DataBase();
        
        $DataBaseName = $userData['dataBaseName'];
        $EnterpriseKey = '';
        $ValuesChain = '';
        $FieldsChain = '';
        
        $xml = filter_input(INPUT_POST, "xml");
        
        libxml_use_internal_errors(true);

        if(!($RegisterXml =  simplexml_load_string($xml)))
        {
            $Error='';
          
            $errors=libxml_get_errors();
            
            for ($aux=0;$aux<count($errors); $aux++) {
                $Error.= $this->display_xml_error($errors[$aux]);
            }
            
            libxml_clear_errors();   /* Se limpia buffer de errores */          
            
            return XML::XMLReponse ("Error", 0, "<p><b>Error</b> al cargar el xml generado con los datos de la nueva empresa, es posible que no se haya formado correctamente $Error</p>");
        }
        
        /* Se generá dinámicamente la consulta para insertar la nueva empresa*/
        foreach ($RegisterXml->Campo as $field)
        {       
            $FieldValue = $field->value;
            $FieldType = $field->type;
            $FieldName = $field->name;
            
            if(strcasecmp("ClaveEmpresa", $FieldName)==0)
                    $EnterpriseKey = $FieldValue;
            
            $FormattedField = DataBase::FieldFormat($FieldValue, $FieldType);
            if(strcasecmp($FormattedField, 0)!=0)
            {
                $ValuesChain.= $FormattedField.", ";
                $FieldsChain.=$FieldName.", ";
            }                
        }
        
        $ValuesChain_ = trim($ValuesChain, ", ");
        $FieldsChain_ = trim($FieldsChain, ", ");
        
        /* Se comprueba que no exista la empresa */
        $CheckIfExist = "SELECT *FROM CSDocs_Empresas WHERE ClaveEmpresa COLLATE utf8_bin = '$EnterpriseKey' ";
        $CheckResult = $DB->ConsultaSelect($DataBaseName, $CheckIfExist);
        if($CheckResult['Estado']!=1)
            return XML::XMLReponse ("Error", 0, "<p><b>Error</b> al intentar comprobar disponibilidad de la nueva clave de empresa <b>$EnterpriseKey</b></p><br>Detalles:<br><br>".$CheckResult['Estado']);
        
        if(count($CheckResult['ArrayDatos'])>0)
            return XML::XMLReponse ("Error", 0, "<p><b>Notificación.</b> La clave de empresa seleccionada ya existe. Por favor seleccione una nueva</p>");
        
        $InsertEnterprise = "Insert Into CSDocs_Empresas ($FieldsChain_) VALUES ($ValuesChain_)";
        
        $NewIdEnterprise = $DB->ConsultaInsertReturnId($DataBaseName, $InsertEnterprise);
        
        if(!($NewIdEnterprise>0))
            return XML::XMLReponse("Error", 0, "<p><b>Error</b> al insertar la nueva empresa.</p><br>Detalles:<br><br>$NewIdEnterprise");
        
        $doc  = new DOMDocument('1.0','utf-8');
        $doc->formatOutput = true;
        $root = $doc->createElement("AddedNewRecord");
        $doc->appendChild($root);   
        
        $Message = $doc->createElement("Mensaje", "Empresa insertada con éxito");
        $root->appendChild($Message);
        $XmlNewIdEnterprise = $doc->createElement("NewIdEnterprise", $NewIdEnterprise);
        $root->appendChild($XmlNewIdEnterprise);
        
        
        header ("Content-Type:text/xml");
        echo $doc->saveXML();           
    }
    
    private function DeleteField($userData)
    {
        $DB = new DataBase();
        
        $DataBaseName = $userData['dataBaseName'];
        $FieldName = filter_input(INPUT_POST, "FieldName");
        
        $DeletedResult = DesignerForms::DeleteField($DataBaseName , "Empresa", $FieldName);
        
        if($DeletedResult!=1)
            return;
        
        $AlterTable = "ALTER TABLE CSDocs_Empresas DROP COLUMN $FieldName";
        if(($AlterTableResult = $DB->ConsultaQuery($DataBaseName, $AlterTable))!=1)
                return XML::XMLReponse ("Error", 0, "<p><b>Error</b> al eliminar la columna <b>$FieldName</b></p><br>Detalles:<br><br>$AlterTableResult");
        
        XML::XMLReponse("DeletedField", 0, "Campo \"$FieldName\" eliminado");
        
    }
    
    private function DeleteEnterprise($userData)
    {
        $DB = new DataBase();
        $Repository = new Repository();
        
        $DataBaseName = $userData['dataBaseName'];
        $IdEnterprise = filter_input(INPUT_POST, "IdEnterprise");
        $EnterpriseKey = filter_input(INPUT_POST, "EnterpriseKey");
        
        $QueryGetRepositories = "SELECT IdRepositorio, ClaveEmpresa, NombreRepositorio FROM CSDocs_Repositorios WHERE ClaveEmpresa = '$EnterpriseKey'";
        $ResultQueryGet = $DB->ConsultaSelect($DataBaseName, $QueryGetRepositories);
        
        if($ResultQueryGet['Estado']!=1)
            return XML::XMLReponse ("Error", 0, "<p><b>Error</b> al obtener las empresas relacionadas a la clave de empresa <b>$EnterpriseKey</b></p><br>Detalles:<br><br>".$ResultQueryGet['Estado']);
        
        $Repositories = $ResultQueryGet['ArrayDatos'];
        
        /* Borrado de repositorios desde el RepositoryControl */
        
        $QueryForDeletion = "DELETE FROM RepositoryControl WHERE ";    
        
        for($cont = 0; $cont < count($Repositories); $cont++)
        {
            if($cont>0)
                $QueryForDeletion.=" OR  IdRepositorio = ".$Repositories[$cont]['IdRepositorio'];
            else
                $QueryForDeletion.=" IdRepositorio = ".$Repositories[$cont]['IdRepositorio'];
        }
        
        if(count($Repositories)  > 0)
            if(($ResultQueryForDeletion = $DB->ConsultaQuery($DataBaseName, $QueryForDeletion))!=1)
                    return XML::XMLReponse ("Error", 0, "<p></b>Error</b/> al intentar eliminar las empresas relacionadas a la clave <b>$EnterpriseKey</b> del Control de Repositorios</p><br>Detalles:<br><br>$ResultQueryForDeletion");
        
        /* Eliminando las tablas de cada repositorio ligado a la empresa    */
        for($cont = 0; $cont < count($Repositories); $cont++)
        {
            $DeletingRepository = $Repository->DeleteRepository($DataBaseName, $IdEnterprise,  $Repositories[$cont]['IdRepositorio'], $Repositories[$cont]['NombreRepositorio'], 1);            
        }
                
        $DeletingOfGlobal = "DELETE FROM RepositorioGlobal WHERE  IdEmpresa = $IdEnterprise";
        
        if(($ResultDeletingOfGlobal = $DB->ConsultaQuery($DataBaseName, $DeletingOfGlobal))!=1)
                return XML::XMLReponse ("Error", 0, "<p><b>Error</b> al eliminar los registros desde Global</p><br>Detalles:<br><br>$ResultDeletingOfGlobal");
        
        $DeletingOfRepository = "DELETE FROM CSDocs_Repositorios WHERE ClaveEmpresa = '$EnterpriseKey'";
        
        if(($ResultDeletingOfRepository = $DB->ConsultaQuery($DataBaseName, $DeletingOfRepository))!=1)
            return XML::XMLReponse ("Error", 1, "<p><b>Error</b> al intentar eliminar los repositorios ligados a la empresa <b>$EnterpriseKey</b> del registro de Repositorios</p><br>Detalles:<br><br>$ResultDeletingOfRepository");
        
        /* Eliminado de la tabla Empresas */
        
        $QForDeletetingEnterprise = "DELETE FROM  CSDocs_Empresas WHERE ClaveEmpresa = '$EnterpriseKey' ";
        if(($ResultDeletingEnterprise = $DB->ConsultaQuery($DataBaseName, $QForDeletetingEnterprise))!=1)
                return XML::XMLReponse ("Error", 0, "<p></b>Error</b/> al intentar eliminar la empresa con clave <b>$EnterpriseKey</b></p><br>Detalles:<br><br>$ResultDeletingEnterprise");
        
        
        XML::XMLReponse("DeletedEnterprise", 1, "Empresa con clave $EnterpriseKey eliminada con éxito");
    }

    private function NewField() {
        $DB = new DataBase();

        $DataBaseName = filter_input(INPUT_POST, "DataBaseName");
//        $IdUser = filter_input(INPUT_POST, "IdUser");
//        $UserName = filter_input(INPUT_POST, "UserName");
//        $IdRepository = filter_input(INPUT_POST, "IdRepository");

        $FieldName = filter_input(INPUT_POST, "FieldName");
        $FieldLength = filter_input(INPUT_POST, "FieldLength");
        $FieldType = filter_input(INPUT_POST, "FieldType");
        $RequiredField = filter_input(INPUT_POST, "RequiredField");

        $FieldDetail = DesignerForms::CreateProperty($FieldName, $FieldType, $FieldLength, $RequiredField);

        $AlterTable = "ALTER TABLE CSDocs_Empresas ADD COLUMN $FieldName ".$FieldDetail['FieldMySql'];

        if (($AlterRes = $DB->ConsultaQuery($DataBaseName, $AlterTable)) != 1)
            return XML::XMLReponse("Error", 0, "<p><b>Error</b> al agregar el nuevo campo <b>$FieldName</b></p><br>Detalles:<br><br>$AlterRes");


        if (($AddField = DesignerForms::AddPropertyIntoStructureConfig($DataBaseName, "Empresa", $FieldDetail['FieldDetail'])) != 1) {
            $DropColumn = "ALTER TABLE CSDocs_Empresas DROP COLUMN $FieldName";
            if (($DropResult = $DB->ConsultaQuery($DataBaseName, $DropColumn)) != 1)
                return XML::XMLReponse("Error", 0, "<p><b>Error</b> al agregar al registro de estructura el nuevo campo '$FieldName'. No fué posible eliminar la columna posteriormente, debe reportarlo a CSDocs</p><br>Detalles:<br><br>$DropResult");
            else
                return XML::XMLReponse("Error", 0, "<p><b>Error</b> al agregar al registro de estructura el nuevo campo '$FieldName'</p><br>Detalles:<br><br>$AddField");
        }

        XML::XMLReponse("AddedField", 1, "Campo $FieldName agregado correctamente");
    }

    private function GetEnterprises($userData) {
       
        $DataBaseName = $userData['dataBaseName'];
        $IdUser = $userData['idUser'];
        $idGroup = $userData['idGroup'];
        $UserName = $userData['userName'];
        

        $enterprises = $this->getEnterprisesArray($DataBaseName);
        
        if(is_array($enterprises))
            return XML::XmlArrayResponse("Enterprises", "Enterprise", $enterprises);
        else
            return XML::XMLReponse("Error", 0, "<b>Error</b> al obtener el listado de empresas.</p><br>Detalles:<br><br>" . $enterprises);

    }
    
    function getEnterprisesArray($dataBaseName)
    {
        $BD = new DataBase();

        $query = "SELECT *FROM CSDocs_Empresas";

        $ListEmpresas = $BD->ConsultaSelect($dataBaseName, $query);

        if ($ListEmpresas['Estado'] != 1)
            return $ListEmpresas['Estado'];
        
         return $ListEmpresas['ArrayDatos'];
        
    }
    
    private function display_xml_error($error)
    {
        $return  = $error->line . "\n";
        $return .= str_repeat('-', $error->column) . "^\n";

        switch ($error->level) {
            case LIBXML_ERR_WARNING:
                $return .= "Warning $error->code: ";
                break;
             case LIBXML_ERR_ERROR:
                $return .= "Error $error->code: ";
                break;
            case LIBXML_ERR_FATAL:
                $return .= "Fatal Error $error->code: ";
                break;
        }

        $return .= trim($error->message) .
                   "\n  Line: $error->line" .
                   "\n  Column: $error->column";

        if ($error->file) {
            $return .= "\n  File: $error->file";
        }

        return "$return\n\n--------------------------------------------\n\n";
    }

}

$Enterprise = new Enterprise();
