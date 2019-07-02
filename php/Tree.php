<?php


/**
 *
 * @author daniel
 */
require_once 'DataBase.php';
require_once 'XML.php';
require_once 'Fifo.php';
require_once "Log.php";
require_once 'Session.php';

class Tree {
    public function __construct() {
        $this->ajax();
    }
    private function ajax()
    {
        
        if(filter_input(INPUT_POST, "opcion")!=NULL and filter_input(INPUT_POST, "opcion")!=FALSE){
            
            $idSession = Session::getIdSession();
        
            if($idSession == null)
                return XML::XMLReponse ("Error", 0, "Tree:No existe una sesión activa, por favor vuelva a iniciar sesión");
            
            $userData = Session::getSessionParameters();
            
            switch (filter_input(INPUT_POST, "opcion"))
            {
                case 'getTree': $this->get_tree($userData); break;
                case 'InsertDir': $this->InsertDir($userData); break;      
                case 'ModifyDir': $this->ModifyDir($userData); break;
                case 'DeleteDir': $this->DeleteDir(); break; 
                case 'GetListReposity': $this->GetListReposity($userData); break; 
            }
        }
    }
    /****************************************************************************
     *  Devuelve el listado de Empresas con sus respectivos repositorios
     * En un array para ser mostrados como un arbol
     * GetListReposity() Y ReturnXmlEmpresasRepository() se relacionan
     */
    function GetListReposity($userData)
    {
        $BD = new DataBase();
        $DataBaseName = $userData['dataBaseName'];
    
        $query = "SELECT re.IdRepositorio, re.NombreRepositorio, em.IdEmpresa, em.NombreEmpresa, em.ClaveEmpresa from CSDocs_Repositorios re INNER JOIN CSDocs_Empresas em ON em.ClaveEmpresa = re.ClaveEmpresa";
        $queryResult = $BD->ConsultaSelect($DataBaseName, $query);
        
        if($queryResult['Estado'] != 1)
            return XML::XMLReponse("Error", 0, "<p><b>Error</b> al obtener la estructura de empresas y repositorios</p> Detalles: <br> ".$queryResult['Estado']);
        
        $repositories = $queryResult['ArrayDatos'];
        
        (count($repositories)>0)?$this->ReturnXmlEmpresasRepository($repositories):XML::XMLReponse("Advertencia", 0, "No existen repositorios para mostrar");
            
    }
    
    function ReturnXmlEmpresasRepository($Estructura)
    {
        $doc  = new DOMDocument('1.0','utf-8');
        $doc->formatOutput = true;
        $root = $doc->createElement("Tree");
        $doc->appendChild($root); 
        for($cont=0;$cont<count($Estructura);$cont++)
        {
            $Empresa=$doc->createElement("Empresas");
            $Repositorios=$doc->createElement("Repositorios");                
            $IdRepositorio=$doc->createElement("IdRepositorio",$Estructura[$cont]['IdRepositorio']);                                
            $Repositorios->appendChild($IdRepositorio);
            $NombreRepositorio=$doc->createElement("NombreRepositorio",$Estructura[$cont]['NombreRepositorio']);
            $Repositorios->appendChild($NombreRepositorio);
            $EmpresaClaveEmpresa=$doc->createElement("EmpresaClaveEmpresa",$Estructura[$cont]['ClaveEmpresa']);
            $Repositorios->appendChild($EmpresaClaveEmpresa);
            $NombreEmpresa=$doc->createElement("NombreEmpresa",$Estructura[$cont]['NombreEmpresa']);
            $Empresa->appendChild($NombreEmpresa);
            $IdEmpresa=$doc->createElement("IdEmpresa",$Estructura[$cont]['IdEmpresa']);
            $Empresa->appendChild($IdEmpresa);
            $ClaveEmpresa=$doc->createElement("ClaveEmpresa",$Estructura[$cont]['ClaveEmpresa']);
            $Empresa->appendChild($ClaveEmpresa);
            $root->appendChild($Empresa);
            $root->appendChild($Repositorios);

        }
        header ("Content-Type:text/xml");
        echo $doc->saveXML();
    }
/***************************************************************************/
/***************************************************************************/
    /**
     * @param $userData
     */
    function get_tree($userData) 
    {
        header('Content-type: application/json');

        try {
            $DataBaseName = $userData['dataBaseName'];
            $NombreRepositorio = filter_input(INPUT_POST, "NombreRepositorio");
            $parentId = filter_input(INPUT_POST, "idDirectory");

            if(!is_numeric((int) $parentId) or !((int) $parentId > 0))
                $parentId = 0;

            $right = $this->getDirectoriesArray($DataBaseName, $NombreRepositorio, (int) $parentId);

            if(!is_array($right))
                return XML::XMLReponse ("Error", 0, "Error al intentar recuperar la estructura de directorios. $right");

            $tree = $this->buildTree($right, [
                'key'                   => 'key',
                'parent_id_column_name' => 'parent_id',
                'children_key_name' => 'children',
                'id_column_name' => 'IdDirectory'
            ], $parentId);

            echo json_encode($tree);
        } catch (Exception $e) {
            header('HTTP/1.1 500 Internal Server Error');
            $error = "Error al obtener directorios ".$e->getMessage();
            echo json_encode($tree);
        }

    }

    /**
     * @param array $elements
     * @param array $options
     * @param int $parentId
     * @return array
     */
    function buildTree(array $elements, $options = [
        'key'                   => 'key',
        'parent_id_column_name' => 'parent_id',
        'children_key_name' => 'children',
        'id_column_name' => 'IdDirectory'
    ], $parentId = 0)
    {
        $branch = array();
        foreach ($elements as $element) {
            if ((int) $element[$options['parent_id_column_name']] == (int) $parentId) {
                $element[$options['key']] = $element[$options['id_column_name']];
                $element['isFolder'] = true;
                $element['isLazy'] = true;
                $children = $this->buildTree($elements, $options, $element[$options['id_column_name']]);
                if ($children) {
                    $element[$options['children_key_name']] = $children;
                }
                $branch[] = $element;
            }
        }
        return $branch;
    }
    
    function getDirectoriesArray($dataBaseName, $repositoryName, $idDirectory = 1){
        $DB = new DataBase();

        $query = "select dir_$repositoryName.* from dir_$repositoryName , (select @pv := '$idDirectory') initialisation where status = 1 AND find_in_set(parent_id, @pv) and length(1) or dir_$repositoryName.IdDirectory=$idDirectory";

        $queryResult = $DB->ConsultaSelect($dataBaseName, $query);
        
        if($queryResult['Estado']!=1)
            return $queryResult['Estado'];
        
        return $queryResult['ArrayDatos'];
        
    }
    
    function InsertDir($userData)
    {
               
        $DataBaseName = $userData['dataBaseName'];
        $NombreRepositorio = filter_input(INPUT_POST, "NombreRepositorio");       
        $NameDirectory = filter_input(INPUT_POST, "NameDirectory");
        $NombreUsuario = $userData['userName'];
        $IdUsuario = $userData['idUser'];
        $Path = filter_input(INPUT_POST, "Path");   
        $RoutFile = dirname(getcwd());

        $PathFinal = dirname($Path)."/";
        $IdParentDirectory = basename($PathFinal);
            
        $ultimo_id = $this->addNewDirectory($DataBaseName, $NombreRepositorio, $NameDirectory, $IdParentDirectory, $PathFinal);    
           
        if(is_numeric($ultimo_id))
            $PathFinal.=$ultimo_id;
        else
            return XML::XMLReponse ("Error", 0, $ultimo_id);
        
        $RutaBase = "$RoutFile/Estructuras/$DataBaseName/$NombreRepositorio/$PathFinal";
        
        mkdir("$RutaBase",0777,true);
                                
        $doc  = new DOMDocument('1.0','utf-8');
        $doc->formatOutput = true;
        $root = $doc->createElement("Tree");
        $doc->appendChild($root); 
        $NuevoDir=$doc->createElement("NewDirectory");
        $IdNewDir=$doc->createElement("IdNewDir",$ultimo_id);
        $NuevoDir->appendChild($IdNewDir);
        $root->appendChild($NuevoDir);
        header ("Content-Type:text/xml");
        echo $doc->saveXML();
        
        Log::WriteEvent("18", $IdUsuario, $NombreUsuario, $NameDirectory, $DataBaseName);
        
    }
    
    function addNewDirectory($dataBaseMame, $repositoryName, $dirname, $idParent, $path){
        
        $DB = new DataBase();
        
        $Insert = "INSERT INTO dir_$repositoryName(parent_id,title, path) VALUES "
                . "($idParent,'$dirname','$path')";            
        
        if(!(($resultInsert = $DB->ConsultaInsertReturnId($dataBaseMame, $Insert))>0))
                return $resultInsert;
        
        return (int)$resultInsert;    
        
    }
    
   function returnTreeXML($ArrayTree)
   {
       /* Devuelve un XML con la estructura de directorios obtenida de la BD */
       $XML=new XML();
       
       $Error=0;
       
        $doc  = new DOMDocument('1.0','utf-8');
        libxml_use_internal_errors(true);
        $doc->formatOutput = true;
        $root = $doc->createElement("Tree");
        $doc->appendChild($root); 
        for($cont=0;$cont<count($ArrayTree);$cont++)
        {
            $Directorio=$doc->createElement("Directory");
            $titulo=$doc->createElement("Title",$ArrayTree[$cont]['title']);
            $Directorio->appendChild($titulo);
            $Id=$doc->createElement("IdDirectory",$ArrayTree[$cont]['IdDirectory']);
            $Directorio->appendChild($Id);
            $IdParent=$doc->createElement("IdParent",$ArrayTree[$cont]['parent_id']);
            $Directorio->appendChild($IdParent);             
            $errors=libxml_get_errors();
            
            // Aquí se manejan los errores} 
//            for ($aux=0;$aux<count($errors); $aux++) {
//                $Error.=$XML->display_xml_error($errors[$aux]);
//            }
            
            if(count($errors)>0){libxml_clear_errors();  /* Se limpia buffer de errores */continue;}
            else
                $root->appendChild($Directorio);                                              
        }       
        
        
        if($Error!==0)
        {
//            $XML->ResponseXML("Error", 0, "Ocurrió un error durante la construcción del árbol, es posible que no se hayan cargado todos los directorios debido a: $Error.");
            $Error_=$doc->createElement("Error");
            $Estado=$doc->createElement("Estado",0);
            $Error_->appendChild($Estado);
            $Mensaje=$doc->createElement("Mensaje",$Error);
            $Error_->appendChild($Mensaje);
            $root->appendChild($Error_);
                    
        }
        
        header ("Content-Type:text/xml");
        echo $doc->saveXML();
   }
   
   function DeleteDir()
   {
        $BD= new DataBase();
        $XML=new XML();
        $Fifo= new Fifo();
        $Log = new Log();
        $estado=TRUE;
        
        $IdRepositorio=filter_input(INPUT_POST, "IdRepositorio");
        $DataBaseName=filter_input(INPUT_POST, "DataBaseName");
        $NombreRepositorio=  filter_input(INPUT_POST, "NombreRepositorio");
//        $NameDirectory=filter_input(INPUT_POST, "NameDirectory");
//        $NombreUsuario=filter_input(INPUT_POST, "nombre_usuario");
        $IdEmpresa=filter_input(INPUT_POST, "IdEmpresa");
        $IdDirectory=filter_input(INPUT_POST, "IdDirectory");
//        $IdParent = filter_input(INPUT_POST, 'IdParent');
//        $Path=  filter_input(INPUT_POST, "Path");
        //$title=filter_input(INPUT_POST, "title");
        //$IdUsuario = filter_input(INPUT_POST, "IdUsuario");
        //$XMLResponse=filter_input(INPUT_POST, "XMLResponse");
        //$qdelete='';
        //$xml=  simplexml_load_string($XMLResponse);

        $QueryDeleteDirectory="UPDATE dir_$NombreRepositorio LEFT JOIN $NombreRepositorio ON dir_$NombreRepositorio.IdDirectory=$NombreRepositorio.IdDirectory
                        SET dir_$NombreRepositorio.status=0,$NombreRepositorio.status=0
                        WHERE dir_$NombreRepositorio.IdDirectory IN
                        (SELECT * FROM (SELECT IdDirectory FROM dir_$NombreRepositorio , (SELECT @pv := '$IdDirectory') initialisation WHERE find_in_set(parent_id, @pv) and length(@pv := concat(@pv, ',', IdDirectory)) or IdDirectory=$IdDirectory) dirs)
       ";
        $delete = $BD->ConsultaQuery($DataBaseName, $QueryDeleteDirectory);
        if($delete==1){
            $QueryDeleteFileGlobal="UPDATE RepositorioGlobal SET status=0
                                   WHERE IdEmpresa=$IdEmpresa AND IdRepositorio=$IdRepositorio AND IdDirectory
                                   IN (SELECT * FROM (SELECT IdDirectory FROM dir_$NombreRepositorio , (SELECT @pv := '$IdDirectory') initialisation WHERE find_in_set(parent_id, @pv) and length(@pv := concat(@pv, ',', IdDirectory)) OR IdDirectory=$IdDirectory) dirs)
                                    ";
            $deleteFileGlobal=$BD->ConsultaQuery($DataBaseName, $QueryDeleteFileGlobal);
            if ($deleteFileGlobal==1){
                return XML::XMLReponse("DeleteDir", 1, "Directorio eliminado con éxito");
                //Log::WriteEvent("19", $IdUsuario, $NombreUsuario, $NameDirectory);
            }
        }
        return XML::XMLReponse ("Error", 0, "$delete");

        /*
 UPDATE RepositorioGlobal SET status=0 WHERE IdEmpresa=1 AND IdRepositorio=1 AND IdDirectory IN (select * from (select IdDirectory from dir_RepoBring , (select @pv := '5') initialisation where find_in_set(parent_id, @pv) and length(@pv := concat(@pv, ',', IdDirectory)) or IdDirectory=5) dirs)




         if(count($xml->Directory)>0)
         {
             foreach ($xml->Directory as $delete)
             {
                 $qdelete.=" OR IdDirectory=$delete";
             }
         }

         /* Se registra el Id del directorio a eliminar
         if(!file_exists("../Configuracion/DeleteDirectory/$DataBaseName/$NombreUsuario"))
         {
             if(!($mkdir = mkdir("../Configuracion/DeleteDirectory/$DataBaseName/$NombreUsuario",0777,true)))
             {
                 $XML->ResponseXML("Error", 0, "No se pudo crear el directorio en <b>Configuracion/$DataBaseName/$NombreUsuario</b> <br><br>Detalles:$mkdir");
                 return;
             }
         }

         if(file_exists("../Configuracion/DeleteDirectory/$DataBaseName/$NombreUsuario/DeleteDirectory.ini"))
             unlink("../Configuracion/DeleteDirectory/$DataBaseName/$NombreUsuario/DeleteDirectory.ini");

         $archivo = "/volume1/web/Configuracion/DeleteDirectory/$DataBaseName/$NombreUsuario/DeleteDirectory.ini";
         $config = fopen($archivo, "a+");
         if(!($config))
         {
             $XML->ResponseXML("Error", 0, "Error al crear archivo de configuración.<br><br>Detalles:<br><br>$config");
             return;
         }  /* Error al abrir y crear el archivo de config

         fwrite($config, "; Archivo que contiene el directorio a eliminar y sus subdirectorios. ".PHP_EOL);
         fwrite($config, "[DeleteDirectory]".PHP_EOL);
         fwrite($config, "DataBaseName=$DataBaseName".PHP_EOL);
         fwrite($config, "NombreRepositorio=$NombreRepositorio".PHP_EOL);
         fwrite($config, "IdRepositorio=$IdRepositorio".PHP_EOL);
         fwrite($config, "NameDirectory=$NameDirectory".PHP_EOL);
         fwrite($config, "IdEmpresa=$IdEmpresa".PHP_EOL);
         fwrite($config, "IdDirectory=$IdDirectory".PHP_EOL);
         fwrite($config, "IdParent=$IdParent".PHP_EOL);
         fwrite($config, "IdUsuario=$IdUsuario".PHP_EOL);
         fwrite($config, "NombreUsuario=$NombreUsuario".PHP_EOL);
         fwrite($config, "title=$title".PHP_EOL);
         fwrite($config, "PathDirectory=$Path".PHP_EOL);
 //        fwrite($config, "QueryDelete[]=$query".PHP_EOL);
         fwrite($config, "; Direcoties=> IdDirectory, IdParent, title ".PHP_EOL);
         fwrite($config, "[Directories]".PHP_EOL);
         /* Registro de subdirectorios
         if(count($xml->Directory)>0)
         {
             foreach ($xml->Directory as $delete)
             {
                 fwrite($config, "Directory[]=".$delete->IdDirectory."###".$delete->IdParent."###".$delete->title."###".$delete->Path.PHP_EOL);
             }
         }

         fclose($config);

         $KeyProcess=$Fifo->AddToStack("DeleteDirectory", $NombreUsuario, $archivo);
         if($KeyProcess===0){$XML->ResponseXML("Error", 0, "Error al registrar el proceso."); return;}

         rename($archivo, dirname($archivo)."/$KeyProcess.ini");

         $StartProcess=$Fifo->StartProcess($KeyProcess);

         if($StartProcess==0){$XML->ResponseXML("Error", 0, "No pudo inicializarse el proceso de borrado del directorio <b>$title</b>"); return 0;}

         $Log->Write("20", $IdUsuario, $NombreUsuario, $NameDirectory, $DataBaseName);

         $doc  = new DOMDocument('1.0','utf-8');
         libxml_use_internal_errors(true);
         $doc->formatOutput = true;
         $root = $doc->createElement("DeleteDir");
         $doc->appendChild($root);
         $Estado=$doc->createElement("Estado",$estado);
         $root->appendChild($Estado);
         $Mensaje=$doc->createElement("Mensaje","<p>Iniciando Proceso de Borrado del directorio <b>$NameDirectory</b></p>");
         $root->appendChild($Mensaje);
         $PathAdvancing=$doc->createElement("PathAdvancing",dirname($archivo)."/Advancing_$KeyProcess.ini");
         $root->appendChild($PathAdvancing);
         $PathCancel=$doc->createElement("PathStatus",dirname($archivo)."/Status_$KeyProcess.ini");
         $root->appendChild($PathCancel);
         $XmlKeyProcess=$doc->createElement("KeyProcess",$KeyProcess);
         $root->appendChild($XmlKeyProcess);
         header ("Content-Type:text/xml");
         echo $doc->saveXML();

         */

    }

    function ModifyDir($userData)
    {
        $db = new DataBase();
        
        $DataBaseName = $userData['dataBaseName'];
        $NombreRepositorio=  filter_input(INPUT_POST, "NombreRepositorio");    
        $NameDirectory=filter_input(INPUT_POST, "NameDirectory");    
        $IdDirectory=filter_input(INPUT_POST, "IdDirectory");  
        $NombreUsuario = $userData['userName'];
        $IdUsuario = $userData['idUser'];
   
        $update = "UPDATE dir_$NombreRepositorio SET title='$NameDirectory' WHERE IdDirectory = $IdDirectory";
        
        if(($resultUpdate = $db->ConsultaQuery($DataBaseName, $update)) != 1)
            return XML::XMLReponse ("Error", 0, "$resultUpdate");
        
        XML::XMLReponse("ModifyDir", 1, "Modificado con éxito");
        Log::WriteEvent("19", $IdUsuario, $NombreUsuario, $NameDirectory);
    }

}

$tree=new Tree();
