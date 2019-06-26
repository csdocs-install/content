<?php

/**
 * Description of Trash
 *
 * @author daniel
 */
require_once(__DIR__ . '/Trash.php');
require_once(__DIR__ . '/XML.php');
require_once(__DIR__ . '/DataBase.php');
require_once(__DIR__ . '/DesignerForms.php');
require_once(__DIR__ . '/Fifo.php');
require_once(__DIR__ . '/Log.php');
require_once(__DIR__ . '/Catalog.php');


class Trash {
    //put your code here
    public function __construct() {
        $this->Ajax();
    }
    
    private function Ajax()
    {
        switch (filter_input(INPUT_POST, "opcion"))
        {
            case 'ListDirectories': $this->ListDirectories(); break;
            case 'ListFiles': $this->ListFiles(); break;
            
            case 'RestoreFiles':$this->RestoreFiles(); break;
            case 'CheckAdvancingRestoreFiles': $this->CheckAdvancingRestoreFiles(); break;
            case 'CancelRestoringOfFiles': $this->CancelRestoringOfFiles(); break;         
        
            case 'RestoreDirectories': $this->RestoreDirectories(); break;
            case 'CheckAdvancingRestoreDir': $this->CheckAdvancingRestoreDir(); break;
            case 'CancelRestoringOfDirectories': $this->CancelRestoringOfDirectories(); break;    
            
            case 'DeleteDirectories': $this->DeleteDirectories(); break;
            case 'DeleteFiles': $this->DeleteFiles(); break;
            
            case 'CheckProgress': $this->CheckProgress(); break;
            case 'CancelService': $this->CancelService(); break;
        
            default: $this->OptionService(); break;
        }
    }
    
    /* Opciones para que funcione esta clase en modo servicio */
    private function OptionService()
    {
        $Parametros=$_SERVER['argv'];    
        if(!isset($Parametros[3])) {return;}
        $Option=$Parametros[3];
        
        switch ($Option)
        {
            case "RestoreDir":$this->ServiceRestoreDirectories(); break;
            case "RestoreFiles":$this->ServiceRestoreFiles(); break;
            case "DeleteDirectories":$this->ServiceDeleteDirectories(); break;
            case "DeleteFiles":$this->ServiceDeleteFiles(); break;
            default: break;
        }
    }
    
    /*--------------------------------------------------------------------------
     *                         Empty Trash (permanently)                       *
     --------------------------------------------------------------------------*/
    
    private function DeleteDirectories()
    {
        $BD= new DataBase();
        $XmlRestore = filter_input(INPUT_POST, 'XmlEmpty');
        $XML=new XML();
        $Fifo = new Fifo();
        
        $DataBaseName=  filter_input(INPUT_POST, "DataBaseName");
        $NombreRepositorio=  filter_input(INPUT_POST, "NombreRepositorio");
        $IdRepositorio=  filter_input(INPUT_POST, "IdRepositorio");
        $IdUsuario=filter_input(INPUT_POST, "IdUsuario");
        $NombreUsuario=  filter_input(INPUT_POST, "nombre_usuario");                                        
        /* Se registra el proceso en Fifo y se crea el archivo con los elementos a borrar en 
         * RestoreTrash/DataBaseName/User/ */

        $xml = simplexml_load_string($XmlRestore);
        $Title=$xml->Directory->title;
        $IdParent=$xml->Directory->IdParent;
        $IdDirectory=$xml->Directory->IdDirectory;

        $QueryDeleteDirectoryGlobal="DELETE FROM RepositorioGlobal WHERE IdRepositorio=$IdRepositorio AND NombreRepositorio='$NombreRepositorio' AND IdDirectory IN (select * from (select IdDirectory from dir_$NombreRepositorio , (select @pv := '$IdDirectory') initialisation where find_in_set(parent_id, @pv) and length(@pv := concat(@pv, ',', IdDirectory)) or IdDirectory=$IdDirectory) dirs)";
        $deleteGlobal=$BD->ConsultaQuery($DataBaseName, $QueryDeleteDirectoryGlobal);
        if($deleteGlobal==1){
            $QueryDeleteDirectory="DELETE drb, rb FROM dir_$NombreRepositorio AS drb LEFT JOIN $NombreRepositorio AS rb USING(IdDirectory) WHERE drb.IdDirectory IN (select * from (select IdDirectory from dir_$NombreRepositorio , (select @pv := '$IdDirectory') initialisation where find_in_set(parent_id, @pv) and length(@pv := concat(@pv, ',', IdDirectory)) or IdDirectory=$IdDirectory) dirs)";
            $delete=$BD->ConsultaQuery($DataBaseName, $QueryDeleteDirectory);

            if($delete==1){
                return $XML->ResponseXML("Delete", 0, "Directorio eliminado con éxito");
            }else{
                return $XML->ResponseXML("Error", 0, $delete);
            }

        }else{
            return $XML->ResponseXML("Error", 0, $deleteGlobal);
        }


//         $XML->ResponseXML("Error", 0, "$Title - - $IdParent - - $IdDirectory -- $NombreRepositorio");
//        $XML->ResponseXML("Success", 1, "Exito");


/*
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
                return XML::XMLReponse("ModifyDir", 1, "Modificado con éxito");
                //Log::WriteEvent("19", $IdUsuario, $NombreUsuario, $NameDirectory);
            }
        }
        return XML::XMLReponse ("Error", 0, "$delete");
*/

        /*
        $RutaFilesTrash="/volume1/web/Configuracion/EmptyTrash/$DataBaseName/$NombreUsuario";       
        
        if(!file_exists($RutaFilesTrash)){if(!mkdir($RutaFilesTrash, 0777, true)){$XML->ResponseXML("Error", 0, "No se pudo crear el directorio <b>EmptyTrash</p>"); return 0;}}
        
        $archivo="$RutaFilesTrash/DeleteDirectories.ini";
        
        if(file_exists($archivo)){if(!unlink($archivo)){$XML->ResponseXML("Error", 0, "No se pudo eliminar el archivo de \"Eliminación Permanente\" anterior."); return 0;}}
        
        $xml=  simplexml_load_string($XmlRestore);      
        
        $config=  fopen($archivo, "a+");
        if(!($config)){$XML->ResponseXML("Error", 0, "Error al crear archivo de configuración."); return;}  // Error al abrir y crear el archivo de config
        
        fwrite($config, "; Listado de documentos a restaurar. ".PHP_EOL);
        fwrite($config, "[DeleteDirectories]".PHP_EOL);
        fwrite($config, "DataBaseName = $DataBaseName".PHP_EOL);
        fwrite($config, "NombreRepositorio = $NombreRepositorio".PHP_EOL);
        fwrite($config, "IdRepositorio = $IdRepositorio".PHP_EOL);    
        fwrite($config, "NombreUsuario = $NombreUsuario".PHP_EOL);    
        fwrite($config, "IdUsuario = $IdUsuario".PHP_EOL);    
        fwrite($config, "[Directories]".PHP_EOL);
        
        // Se registran los directorios padre del árbol a restaurar
        foreach ($xml->Directory as $nodo)
        {
            fwrite($config, "$nodo->title=$nodo->IdDirectory".PHP_EOL);
        }
        fclose($config);
        
        // Se registra el proceso
        $KeyProcess=$Fifo->AddToStack("DeleteDirectories", $NombreUsuario, $archivo);
        
        if(!$KeyProcess){$XML->ResponseXML("Error", 0, "No pudo ser registrado el proceso en Fifo"); return 0;}
        
        $StartProcess=$Fifo->StartProcess($KeyProcess);
        
        if($StartProcess==0){$XML->ResponseXML("Error", 0, "No pudo inicializarse el proceso de borrado del directorio"); return 0;}
        
        rename($archivo, dirname($archivo)."/$KeyProcess.ini");
        
        $RouteFileStatus=dirname($archivo)."/Status_$KeyProcess.ini";
        $RouteFileAdvancing=dirname($archivo)."/Advancing_$KeyProcess.ini";

        
        // Archivo de status del servicio (Permite deter el servicio )
        if(!($FileProgress=fopen($RouteFileAdvancing, "a+"))){   echo "Error al crear archivo de Progress_$KeyProcess. ".$FileProgress.PHP_EOL;  return 0;}
        fwrite($FileProgress, "[Progress]".PHP_EOL);
        fwrite($FileProgress, "TotalDirectories=Calculando...".PHP_EOL);
        fclose($FileProgress);
        
        
        // Archivo de Progreso de la restauración
        
        if(!($FileStatus=fopen($RouteFileStatus, "a+"))){   echo "Error al crear archivo de Status_$KeyProcess. ".$FileStatus.PHP_EOL;  return 0;}
        fwrite($FileStatus, "status=1");
        fclose($FileStatus);
        
//        $XML->ResponseXML("DeleteDir", 1, "<p>Iniciando Proceso de Borrado del directorio <b>$NameDirectory</b></p>");
        $doc  = new DOMDocument('1.0','utf-8');
        libxml_use_internal_errors(true);
        $doc->formatOutput = true;
        $root = $doc->createElement("DeleteDirectories");
        $doc->appendChild($root); 
        $Estado=$doc->createElement("Estado",1);
        $root->appendChild($Estado);
        $Mensaje=$doc->createElement("Mensaje","<p>Iniciando Proceso de Restauración.</p>");
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
    
    /* Elimina permanentemente de la NAS los directorios seleccionados por el usuario */
    
    private function ServiceDeleteDirectories()
    {
//        $XML=new XML();
        $BD= new DataBase();
//        $designer=new DesignerForms();
        $Fifo= new Fifo();
        
        $Parametros=$_SERVER['argv'];      
        $KeyProcess=$Parametros[1];        
        $Path=$Parametros[2];  
        
        $RouteFileStatus=dirname($Path)."/Status_$KeyProcess.ini";
        $RouteFileAdvancing=dirname($Path)."/Advancing_$KeyProcess.ini";
        
        if(!file_exists($Path)){echo "No existe el archivo de registro de directorios a eliminar. ".PHP_EOL; return 0;}
        
        if(!($Delete=parse_ini_file ($Path,true))){echo "No pudo abrirse el archivo con la clave de proceso $KeyProcess.ini".PHP_EOL; return 0;}
        
        /* Variables de Repositorio y BD */
        
        $Directories = $Delete['Directories'];
        $NombreRepositorio = $Delete['DeleteDirectories']['NombreRepositorio'];
        $DataBaseName = $Delete['DeleteDirectories']['DataBaseName'];        
        $PathEstructura="/volume1/web/Estructuras/".$DataBaseName."/$NombreRepositorio";
        $IdUsuario = $Delete['DeleteDirectories']['IdUsuario'];  
        $NombreUsuario = $Delete['DeleteDirectories']['NombreUsuario'];  
        $IdRepositorio=$Delete['DeleteDirectories']['IdRepositorio'];
        
        /* Se obtienen los directorios temporales para construir el arbol desde el directorio padre e ir eliminando desde 
         * El hijo que se encuentra a mayor profundidad */
        
        $QuerySelectDir = "SELECT * FROM temp_dir_$NombreRepositorio ORDER BY parent_id";
        $ResultTempDirectories = $BD->ConsultaSelect($DataBaseName, $QuerySelectDir);
        if($ResultTempDirectories['Estado']!=1){echo "Error al obtener los directorios temporales. ".$ResultTempDirectories['Estado'].PHP_EOL; return 0;}
        $TempDirectories_ = $ResultTempDirectories['ArrayDatos'];
        
        /* Se organiza en un array asociativo el array con los directorios que están dentro de la tabla temporal */
        $TempDirectories = array();       
        for ($cont = 0; $cont < count($TempDirectories_); $cont++) {
                $TempDirectories[$TempDirectories_[$cont]['IdDirectory']] = $TempDirectories_[$cont];
        }
        
        
        if(!($FileAdvancing=  fopen($RouteFileAdvancing, "a+"))){  $FileAdvancing.PHP_EOL;  } else {
            fwrite($FileAdvancing, "TotalDirectories=".count($Directories).PHP_EOL);
            fclose($FileAdvancing);
            }
            
        $AuxCont = 0;
        foreach ($Directories as $key => $value)
        {                      
            $AuxCont++;
            if(!$this->CheckStatus($RouteFileStatus))
                return 0;
            
            if($this->CheckIfDirectoryWasDeleted($Path, $value))
                    continue;
            
            if(!($FileAdvancing=  fopen($RouteFileAdvancing, "a+"))){  $FileAdvancing.PHP_EOL;  } else {
            fwrite($FileAdvancing, "TitleDirectory=$key".PHP_EOL);  /*Registra el directorio Padre que se esta procesando */
            fwrite($FileAdvancing, "NumberDirectory=".$AuxCont.PHP_EOL);
            fclose($FileAdvancing);
            }
            
            if(($TempDirectories = $this->RemoveDirectoriesFromTrashAndHDD($IdUsuario, $NombreUsuario, $KeyProcess, $Path, $RouteFileStatus , $PathEstructura, $DataBaseName, $IdRepositorio , $NombreRepositorio , $RouteFileAdvancing,$TempDirectories, $value, $key))!=0)
            {
                echo "\"$key \" eliminado permanentemente".PHP_EOL;
                if(!($FileAdvancing=  fopen(dirname($RouteFileAdvancing)."/Ok_$KeyProcess.ini", "a+"))){  $FileAdvancing.PHP_EOL;  } else {
                    fwrite($FileAdvancing, $value."=".$key.PHP_EOL);
                    fclose($FileAdvancing);
                }
            }                        
        }
        
        
        unlink($RouteFileStatus);      
        if(file_exists(dirname($Path)."/Deleted_".  basename($Path))){unlink(dirname($Path)."/Deleted_".  basename($Path));}
        unlink($Path);               
//        unlink($RouteFileAdvancing);     
        rename(dirname($Path)."/$KeyProcess.txt", dirname($Path)."/Ok_$KeyProcess.txt");
        $Fifo->DeleteProcess($KeyProcess);
        
    }
    
    
    private function RemoveDirectoriesFromTrashAndHDD($IdUsuario, $NombreUsuario, $KeyProcess, $Path, $RouteFileStatus, $PathEstructura, $DataBaseName, $IdRepositorio , $NombreRepositorio , $RouteFileAdvancing,$TempDirectories, $IdDirectory, $title)
    {
        
        $BD= new DataBase();
        $Log = new Log();
        /* Construcción de árbol de subdirectorios a restaurar en un array Asociativo*/
        $TempTree = array();
        
        if(!isset($TempDirectories[$IdDirectory])){echo "El directorio \"$title\" no se encontró en la Papelera de Reciclaje"; return 0; }        
        $TempTree[$IdDirectory] = $TempDirectories[$IdDirectory];
        unset($TempDirectories[$IdDirectory]);

        foreach ($TempDirectories as $Fila => $valor)
        {          
            $TempIdParent = $TempDirectories["$Fila"]['parent_id'];
            if(isset($TempTree["$TempIdParent"]))
            {
                $TempTree[$Fila] = $TempDirectories[$Fila];
                unset($TempDirectories[$Fila]);
            }
        }                                 
        /* El array Asociativo se convierte en un array Númerico para procesar primero los directorios
                     * que se encuentran a mayor profundidad dentro del árbol temporal  */
            
        $Tree = array();
        
        foreach ($TempTree as $key => $value){ $Tree[] = $TempTree[$key];  }    
        
        if(!($FileAdvancing=  fopen($RouteFileAdvancing, "a+"))){  $FileAdvancing.PHP_EOL;  } else {
            fwrite($FileAdvancing, "TotalSubdirectories=".(count($Tree)-1).PHP_EOL);  /* Se resta 1 ya que el directorio padre no cuenta como subdirectorio */
            fclose($FileAdvancing);
            }
        
        /* Se procesan los directorios que serán eliminados del disco duro */
        $AuxCont= 0 ;
        for($cont = (count($Tree) - 1); $cont >= 0 ; $cont--)
        {
            if(!$this->CheckStatus($RouteFileStatus))
                return 0;
            
            if($this->CheckIfDirectoryWasDeleted($Path, $Tree[$cont]['IdDirectory']))
                    continue;
            
            $AuxCont++;
            echo $Tree[$cont]['title']." id= ".$Tree[$cont]['IdDirectory'].PHP_EOL;
            $RutaDirectory = $PathEstructura.$Tree[$cont]['path'];
            if(file_exists($RutaDirectory))
            {                
                
                if(!($FileAdvancing=  fopen($RouteFileAdvancing, "a+"))){ echo  $FileAdvancing.PHP_EOL; } else {                    
                    fwrite($FileAdvancing, "NumberSubdirectory=".$AuxCont.PHP_EOL);
                    if($cont!=0)
                        fwrite($FileAdvancing, "TitleSubdirectory =".$Tree[$cont]['title'].PHP_EOL);
                    fclose($FileAdvancing);
                }
                
                if(($ResultDeleteDirectory = $this->deleteDirectory($RutaDirectory)))
                {
                    $DeleteFromGlobal = "DELETE FROM RepositorioGlobal WHERE IdRepositorio = $IdRepositorio AND IdDirectory = $IdDirectory";
                    
                    if($BD->ConsultaQuery ($DataBaseName, "DELETE FROM temp_rep_$NombreRepositorio WHERE IdDirectory = ".$Tree[$cont]['IdDirectory']))
                        if($BD->ConsultaQuery ($DataBaseName, "DELETE FROM temp_dir_$NombreRepositorio WHERE IdDirectory = ".$Tree[$cont]['IdDirectory']))
                        {
                            
                            if(($ResultDeleteFromGlobal = $BD->ConsultaQuery($DataBaseName, $DeleteFromGlobal))!=1)
                                echo "Error al eliminar de Global los documento del directorio id = $IdDirectory Repositorio id = $IdRepositorio ".$ResultDeleteFromGlobal.PHP_EOL.$DeleteFromGlobal.PHP_EOL;
                            
                            echo "Se eliminó a ".$Tree[$cont]['title']." con clave ".$Tree[$cont]['IdDirectory'].PHP_EOL;
                            $this->RegisterDirectoryAsDeleted($Path, $Tree[$cont]['IdDirectory'], $Tree[$cont]['title']);
                        }                            
                        else
                            echo "Error al Eliminar el directorio \"".$Tree[$cont]['title']."\" con clave ".$Tree[$cont]['IdDirectory'].PHP_EOL;
                    else
                        echo "Error al intentar eliminar de la papelera los documentos del directorio \"".$Tree[$cont]['title']."\" con clave ".$Tree[$cont]['IdDirectory'].PHP_EOL;
                }                        
                else
                {
                   echo var_dump($ResultDeleteDirectory)." Error".PHP_EOL;
                }                    
            }                    
            else
            {
                echo "No existe la rua ".$PathEstructura.$Tree[$cont]['path'].PHP_EOL;
                return $TempDirectories;
            }     
            
            sleep(1);
        }
        
        $Log ->Write("21", $IdUsuario, $NombreUsuario, $title, $DataBaseName);
        
        return $TempDirectories;        
    }
    
    /* Devuelve el estado de la operación que se está ejecutando en segundo plano */
    
    private function CheckProgress()
    {
        $XML=new XML();
        
        $PathAdvancing=  filter_input(INPUT_POST, "PathAdvancing");
        
        $KeyProcess = filter_input(INPUT_POST, "KeyProcess");
        
        $OperationName = filter_input(INPUT_POST, "OperationName");
        
        $DirectoriesRestored = array();
        
        /* Se si el archivo Ok_KeyProcess.txt significa que el proceso ya termino de ejecutarse */
        if(file_exists(dirname($PathAdvancing)."/Ok_$KeyProcess.txt")){       
            if(file_exists(dirname($PathAdvancing)."/Ok_$KeyProcess.ini"))
                $DirectoriesRestored = parse_ini_file (dirname($PathAdvancing)."/Ok_$KeyProcess.ini",true);
            
//            if(file_exists($PathAdvancing)){unlink($PathAdvancing);}
            
            $doc  = new DOMDocument('1.0','utf-8');
            libxml_use_internal_errors(true);
            $doc->formatOutput = true;
            $root = $doc->createElement("Ok");
            $doc->appendChild($root); 
            
            $DescriptionNotificacion = $this->GetDescriptionOfNotification($OperationName);
            
 
            if($DescriptionNotificacion!=0)
            {
                $TituloNotificacion = $doc->createElement("TituloNotificacion", $DescriptionNotificacion['TituloNotificacion']);
                $root->appendChild($TituloNotificacion);
                $MensajeNotificacion = $doc ->createElement("MensajeNotificacion", $DescriptionNotificacion['MensajeNotificacion']);
                $root->appendChild($MensajeNotificacion);
            }
            
            foreach ($DirectoriesRestored as $Key => $value)
            {
                $Directory = $doc->createElement("Directory",$value);
                $root->appendChild($Directory);
            }
            
            header ("Content-Type:text/xml");
            echo $doc->saveXML();  
            
            return 1;
        }
        
        if(!file_exists($PathAdvancing)) {
            $XML->ResponseXML("NotFound", 0, "<p>No se encontró el archivo de progreso</p>");
            return 0;
        }

        if(!($Progress = parse_ini_file ($PathAdvancing,true))){
            $XML->ResponseXML("Error", 0, "<p>No fué posible abrir el archivo de progreso. </p>");
            return 0;
        }
                        
        $doc  = new DOMDocument('1.0','utf-8');
        libxml_use_internal_errors(true);
        $doc->formatOutput = true;
        $root = $doc->createElement("Progress");
        $doc->appendChild($root); 
        if(isset($Progress['Progress']['TotalDirectories']))
        {
            $TotalDirectories = $doc->createElement("TotalDirectories",$Progress['Progress']['TotalDirectories']);
            $root->appendChild($TotalDirectories);
        }
        if(isset($Progress['Progress']['TitleDirectory']))
        {
            $TitleDirectory = $doc->createElement("TitleDirectory",$Progress['Progress']['TitleDirectory']);
            $root->appendChild($TitleDirectory);
        }     
        
        if(isset($Progress['Progress']['NumberDirectory']))
        {
            $NumberDirectory = $doc->createElement("NumberDirectory",$Progress['Progress']['NumberDirectory']);
            $root->appendChild($NumberDirectory);
        }
        
        if(isset($Progress['Progress']['TotalSubdirectories']))
        {
            $TitleDirectory = $doc->createElement("TotalSubdirectories",$Progress['Progress']['TotalSubdirectories']);
            $root->appendChild($TitleDirectory);
        }  
        
        if(isset($Progress['Progress']['NumberSubdirectory']))
        {
            $TitleDirectory = $doc->createElement("NumberSubdirectory",$Progress['Progress']['NumberSubdirectory']);
            $root->appendChild($TitleDirectory);
        } 
        
        if(isset($Progress['Progress']['TitleSubdirectory']))
        {
            $TitleDirectory = $doc->createElement("TitleSubdirectory",$Progress['Progress']['TitleSubdirectory']);
            $root->appendChild($TitleDirectory);
        } 
        
        if(isset($Progress['Progress']['TotalFiles']))
        {
            $TitleFile = $doc->createElement("TotalFiles",$Progress['Progress']['TotalFiles']);
            $root->appendChild($TitleFile);
        }
        
        if(isset($Progress['Progress']['NumberFile']))
        {
            $TitleFile = $doc->createElement("NumberFile",$Progress['Progress']['NumberFile']);
            $root->appendChild($TitleFile);
        }
        
        if(isset($Progress['Progress']['TitleFile']))
        {
            $TitleFile = $doc->createElement("TitleFile",$Progress['Progress']['TitleFile']);
            $root->appendChild($TitleFile);
        }
        header ("Content-Type:text/xml");
        echo $doc->saveXML();  
    }
    
    /* Dependiendo de la operación lanzada por el usuario al término de esta
     * Se muestra una notificación en cliente, esta función devuelve el título y el
     * mensaje de dicha notificación. */
    
    function GetDescriptionOfNotification($OperationName)
    {
        switch ($OperationName)
        {
            case "DeleteDirectories": return $Array =array("TituloNotificacion" => "Eliminando Directorios de la papelera", "MensajeNotificación" => "La restauración de directorios finalizó: ");
            
            case "DeleteFiles": return $Array =array("TituloNotificacion" => "Eliminando Documentos de la papelera", "MensajeNotificación" => "Eliminación de documentos de la papelera finalizó: ");
            
            case "RestoreDirectories": return $Array =array("TituloNotificacion" => "Restaurando Directorio(s)", "MensajeNotificación" => "La restauración de directorio(s) finalizó: ");
            
            case "RestoreFiles": return $Array =array("TituloNotificacion" => "Restaurando Documento(s)", "MensajeNotificación" => "La restauración de documento(s) finalizó: ");
            
            default: return 0;
        }
    }
    
    /*--------------------------------------------------------------------------
     *                          Cancela un Servicio                         
     -------------------------------------------------------------------------*/
    
    private function CancelService()
    {
        $XML=new XML();

        $PathStatus=  filter_input(INPUT_POST, "PathStatus");
        $PathAdvancing = filter_input(INPUT_POST, "PathAdvancing");                           
        
        
        $Status=  fopen($PathStatus, "w");
        if(!$Status){$XML->ResponseXML("Error", 0, "No fue posible cancelar el proceso. ". $Status); return 0;}        
        fwrite($Status, "status=0");
        fclose($Status);
        
        if(file_exists($PathAdvancing)){unlink($PathAdvancing);}
        
        $XML->ResponseXML("CancelProgress", 1 , "Proceso Cancelado");
    }
    
    
    private function deleteDirectory($dir) {
            if (!file_exists($dir)) {
            return true;
        }

        if (!is_dir($dir)) {
            return unlink($dir);
        }

        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }

            if (!$this->deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }
        }

        return rmdir($dir);
    }
    
    
    private function RegisterDirectoryAsDeleted($Path,$IdDrectory,$title)
    {        
        $config=  fopen(dirname($Path)."/Deleted_".  basename($Path), "a+");
        /* Se reescribe la sección [Restored]. Consultar la descripción de esta función. */
        fwrite($config, "$IdDrectory=$title".PHP_EOL);
        fclose($config);
    }
    
    private function CheckIfDirectoryWasDeleted($Path, $IdDirectory)
    {
        if(!file_exists(dirname($Path)."/Deleted_".  basename($Path))){return 0;}
        
        if(!($DirectoriesDeleted=parse_ini_file (dirname($Path)."/Deleted_".  basename($Path),false))){echo "No pudo abrirse el archivo de registro de directorios removidos Restored_".  basename($Path).PHP_EOL; return 0;}
           
        $Registered=isset($DirectoriesDeleted[$IdDirectory]);
        if(!$Registered){return 0;}else {return 1;}
    }
    
    /* Devuelve el estado del servicio 
     * 1 = > Activo 
     * 0 => Inactivo */
    
    private function CheckStatus($RouteStatus)
    {
        if(!file_exists($RouteStatus))
            return 0;
        
        $FileStatus=  parse_ini_file($RouteStatus);
            if($FileStatus['status']==0){echo "Operación cancelada por el usuario."; return 0; }
            else
                return 1;
    }
    
    
    
    private function DeleteFiles()
    {
        $BD= new DataBase();
        $XmlRestore = filter_input(INPUT_POST, 'XmlEmpty');
        $XML=new XML();
        $Fifo = new Fifo();
        
        $DataBaseName=  filter_input(INPUT_POST, "DataBaseName");
        $NombreRepositorio=  filter_input(INPUT_POST, "NombreRepositorio");
        $IdRepositorio=  filter_input(INPUT_POST, "IdRepositorio");
        $IdUsuario=filter_input(INPUT_POST, "IdUsuario");
        $NombreUsuario=  filter_input(INPUT_POST, "nombre_usuario");

        $xml = simplexml_load_string($XmlRestore);
        foreach ($xml->File as $nodo){
            $NombreArchivo=$nodo->NombreArchivo;
            $IdFile=$nodo->IdRepositorio;
            $IdDirectory=$nodo->IdDirectory;
            $IdEmpresa=$nodo->IdEmpresa;
        }

        $QueryDeleteFile="DELETE FROM $NombreRepositorio WHERE IdRepositorio=$IdFile AND status = 0";
        $delete=$BD->ConsultaQuery($DataBaseName, $QueryDeleteFile);

        if($delete==1){
            $QueryDeleteFileGlobal="DELETE FROM RepositorioGlobal WHERE IdFile=$IdFile AND IdEmpresa=$IdEmpresa AND IdRepositorio=$IdRepositorio AND IdDirectory=$IdDirectory";
            $deleteGlobal=$BD->ConsultaQuery($DataBaseName, $QueryDeleteFileGlobal);

            if($deleteGlobal==1){
                $XML->ResponseXML("Delete", 0, "Archivo eliminado con éxito");
            }else{
                return $XML->ResponseXML("Error", 0, "No fue posible eliminar el archivo".$QueryDeleteFileGlobal);
            }
        }else{
            return $XML->ResponseXML("Error", 0, "No fue posible eliminar el archivo".$QueryDeleteFile);
        }

    }

    /*--------------------------------------------------------------------------
     *  Devuelve el listado de directorios que se movieron a la papelera.
     *  Únicamente se muestran los directorios padre
     --------------------------------------------------------------------------*/
    private function ListDirectories()
    {        
        $DataBaseName=  filter_input(INPUT_POST, "DataBaseName");
        $NombreRepositorio=  filter_input(INPUT_POST, "NombreRepositorio");
        $IdRepositorio=  filter_input(INPUT_POST, "IdRepositorio");
        $IdUsuario=filter_input(INPUT_POST, "IdUsuario");
        $NombreUsuario=  filter_input(INPUT_POST, "nombre_usuario"); 
        
        $ResultadoConsulta = $this->getDirectoriesQuery($DataBaseName, $NombreRepositorio, $IdUsuario, $NombreUsuario);
 
        $doc  = new DOMDocument('1.0','utf-8');
        libxml_use_internal_errors(true);
        $doc->formatOutput = true;
        $root = $doc->createElement("Directories");
        $doc->appendChild($root); 
        for($cont = 0; $cont < count($ResultadoConsulta); $cont++){
            $Directory = $doc->createElement("Directory");            
            
            $IdParent = $doc->createElement("IdParent", $ResultadoConsulta[$cont][0]);
            $Directory->appendChild($IdParent);
            
            $IdDirectory = $doc->createElement("IdDirectory",$ResultadoConsulta[$cont][1]);
            $Directory->appendChild($IdDirectory);
            
            $title = $doc->createElement("title",$ResultadoConsulta[$cont][2]);
            $Directory->appendChild($title);
            
            $root->appendChild($Directory);                        
        }                
        header ("Content-Type:text/xml");
        echo $doc->saveXML();    
        
    }
    
    private function getDirectoriesQuery($DataBaseName, $NombreRepositorio, $IdUsuario, $NombreUsuario){
        $BD= new DataBase();
        $QueryDirectoriesTrashed="SELECT parent_id, IdDirectory, title FROM dir_$NombreRepositorio WHERE status=0 AND parent_id NOT IN(SELECT IdDirectory FROM dir_$NombreRepositorio WHERE status=0)";

        /* Los administradores pueden ver todos los elementos de la papelera */                
        if(strcasecmp($NombreUsuario, "root")==0)
        {
            $QueryDirectoriesTrashed="SELECT parent_id, IdDirectory, title FROM dir_$NombreRepositorio WHERE status=0 AND parent_id NOT IN(SELECT IdDirectory FROM dir_$NombreRepositorio WHERE status=0)";

        }         
        
        $conexion = $BD->Conexion();
        if (!$conexion) 
            return XML::XMLReponse("Error", 0, mysqli_errno ($conexion). "<br>" . mysqli_error($conexion));

        mysqli_select_db($conexion, $DataBaseName);
        $select = mysqli_query($conexion, $QueryDirectoriesTrashed);
        
        if(!$select)
            return XML::XMLReponse("Error", 0, mysqli_errno ($conexion). "<br>" . mysqli_error($conexion));
        
        while(($ResultadoConsulta[] = mysqli_fetch_row($select)) || array_pop($ResultadoConsulta));     
        
        mysqli_close($conexion);
        return $ResultadoConsulta;
    }
    /*--------------------------------------------------------------------------
     *  Devuelve el listado de documentos que se encuentran en la tabla temporal
     --------------------------------------------------------------------------*/
    
    private function ListFiles()
    {
        $XML=new XML();
        $BD= new DataBase();
        
        $DataBaseName=  filter_input(INPUT_POST, "DataBaseName");
        $NombreRepositorio=  filter_input(INPUT_POST, "NombreRepositorio");
        $IdRepositorio=  filter_input(INPUT_POST, "IdRepositorio");
        $IdUsuario=filter_input(INPUT_POST, "IdUsuario");
        $NombreUsuario=  filter_input(INPUT_POST, "nombre_usuario");

        $QueryGetFiles = "SELECT * FROM $NombreRepositorio INNER JOIN dir_$NombreRepositorio ON $NombreRepositorio.IdDirectory=dir_$NombreRepositorio.IdDirectory WHERE $NombreRepositorio.status=0";

        if(strcasecmp($NombreUsuario, "root")==0)
        {
            $QueryGetFiles = "SELECT * FROM $NombreRepositorio INNER JOIN dir_$NombreRepositorio ON $NombreRepositorio.IdDirectory=dir_$NombreRepositorio.IdDirectory WHERE $NombreRepositorio.status=0";


        }
        
        $ResultGetFiles = $BD->ConsultaSelect($DataBaseName, $QueryGetFiles);
        
        if($ResultGetFiles['Estado']!=1){$XML->ResponseXML("Error", 0, "Error al obtener los documentos de la papelera. ".$ResultGetFiles['Estado']); return 0;}
        
        $Files = $ResultGetFiles['ArrayDatos'];
        
        $XML->ResponseXmlFromArray("Files", "File", $Files);
        
    }
    
    private function RestoreFiles(){
        $db= new DataBase();
        $RoutFile = dirname(getcwd());    
        $XmlRestore = filter_input(INPUT_POST, 'XmlRestore');
        $Fifo = new Fifo();
        $XML=new XML();
        //$Log = new Log();

        $DataBaseName=  filter_input(INPUT_POST, "DataBaseName");
        $NombreRepositorio=  filter_input(INPUT_POST, "NombreRepositorio");
        $IdRepositorio=  filter_input(INPUT_POST, "IdRepositorio");
        $IdUsuario=filter_input(INPUT_POST, "IdUsuario");
        $NombreUsuario=  filter_input(INPUT_POST, "nombre_usuario");                                        
        /* Se registra el proceso en Fifo y se crea el archivo con los elementos a borrar en 
         * RestoreTrash/DataBaseName/User/ */

        $xml = simplexml_load_string($XmlRestore);
        foreach ($xml->File as $nodo){
            $NombreArchivo=$nodo->NombreArchivo;
            $IdFile=$nodo->IdRepositorio;
            $IdDirectory=$nodo->IdDirectory;
            $IdEmpresa=$nodo->IdEmpresa;
        }

        $QueryRestoreFile="UPDATE $NombreRepositorio SET status=1 WHERE idRepositorio=$IdFile";
        $restore = $db->ConsultaQuery($DataBaseName, $QueryRestoreFile);
        if($restore==1)
        {
            $QueryRestoreFileGlobal="UPDATE RepositorioGlobal SET status=1 WHERE IdEmpresa=$IdEmpresa AND IdRepositorio=$IdRepositorio AND IdDirectory=$IdDirectory AND idFile=$IdFile";
            $restoreFileGlobal=$db->ConsultaQuery($DataBaseName,$QueryRestoreFileGlobal);
            if($restoreFileGlobal==1)
            {
                //$Log->Write("27", $IdUsuario, $NombreUsuario, $Key, $DataBaseName);
                //Log::WriteEvent("25", $IdUsuario, $NombreUsuario, $File['ArrayDatos'][0]["NombreArchivo"], $DataBaseName);
                return $XML->ResponseXML("Restore", 0, "Archivo restaurado con éxito.");
            }else {
                return XML::XMLReponse("Error", 0, "No fue posible restaurar el archivo.".$restoreFileGlobal);
            }
        }else {
            return XML::XMLReponse("Error", 0, "No fue restaurar eliminar el archivo.".$restore);
        }



    }
    
    /* Función que se ejecuta como servicio para la restauración de Documentos */
    private function ServiceRestoreFiles()
    {
        $XML=new XML();
        $BD= new DataBase();
        $designer=new DesignerForms();
        $Fifo= new Fifo();
        $Log = new Log();
        $Catalog = new Catalog();
        $Parametros=$_SERVER['argv'];      
        $KeyProcess=$Parametros[1];        
        $Path=$Parametros[2];  
        
        $RouteFileStatus=dirname($Path)."/Status_$KeyProcess.ini";
        $RouteFileAdvancing=dirname($Path)."/Advancing_$KeyProcess.ini";
        if(!file_exists($Path)){echo "No existe el archivo de registro de directorios a eliminar. ".PHP_EOL; return 0;}
        
        if(!($Restore=parse_ini_file ($Path,true))){echo "No pudo abrirse el archivo con la clave de proceso $KeyProcess.ini".PHP_EOL; return 0;}
        
        /* Variables de Repositorio y BD */
        
        $Files=$Restore['Files'];
        $NombreRepositorio = $Restore['RestoreFiles']['NombreRepositorio'];
        $DataBaseName = $Restore['RestoreFiles']['DataBaseName'];
        $IdRepositorio = $Restore['RestoreFiles']['IdRepositorio'];
        $IdUsuario = $Restore['RestoreFiles']['IdUsuario'];
        $NombreUsuario = $Restore['RestoreFiles']['NombreUsuario'];
//        $IdFile = $Restore['RestoreFiles']['IdRepositorio'];
        
        /* Archivos de Configuración de Estructura de repositorio */
        if(!file_exists("../Configuracion/$DataBaseName.ini")){$XML->ResponseXML("Error", 0, "No existe el archivo de Configuración del $NombreRepositorio"); return 0;}
        $EstructuraConfig=parse_ini_file ("../Configuracion/$DataBaseName.ini",true);
 
        $ArrayStructureUser=$designer->ReturnStructure($NombreRepositorio,$EstructuraConfig[$NombreRepositorio]);
        $ArrayStructureDefault=$designer->ReturnStructureDefault($NombreRepositorio,$EstructuraConfig[$NombreRepositorio]);
        
        $ArrayCatalogos = $Catalog->getFilteredArrayCatalogsDetail($DataBaseName, $IdRepositorio);
        
        if(!is_array($ArrayCatalogos)){
            echo  "Error al consulta catálogos. $ArrayCatalogos".PHP_EOL;
            return 0;
        }
        
        
        /* Archivo de status del servicio (Permite deter el servicio ) */
        if(!($FileProgress=fopen($RouteFileAdvancing, "a+"))){   echo "Error al crear archivo de Progress_$KeyProcess. ".$FileProgress.PHP_EOL;  return 0;}
        fwrite($FileProgress, "TotalFiles=".count($Files).PHP_EOL);
        fwrite($FileProgress, "NumberFile=0".PHP_EOL);
        fclose($FileProgress);
        
        $cont = 0;
        foreach ($Files as $Key => $value)
        {
            $cont++;
            
            $FileStatus=  parse_ini_file($RouteFileStatus);
            if($FileStatus['status']==0){echo "Restauracion de directorios cancelada por el usuario."; return 0; }                                    
            
            if($this->CheckIfFileWasRestored($Path, $value))
            {
                echo "Ya se había restaurado el documento con clave = $value".PHP_EOL;
                continue;
            }
            
            if(!($FileProgress=fopen($RouteFileAdvancing, "a+"))){   echo "Error al registrar el Número de Documento que se estaba restaurando.".PHP_EOL;  return 0;}
            fwrite($FileProgress, "NumberFile=$cont".PHP_EOL);
            fclose($FileProgress);    
            
            echo "Restaurando el Documento $Key".PHP_EOL;
            
            if($this->MoveFileToRepository($ArrayStructureDefault, $ArrayStructureUser, $ArrayCatalogos, $DataBaseName, $IdRepositorio, $NombreRepositorio, $value, $RouteFileAdvancing,$KeyProcess))
            {
                 echo "Se restauró a $Key".PHP_EOL;    
                 $Log ->Write("27", $IdUsuario, $NombreUsuario, $Key, $DataBaseName);
            }                                        
            sleep(2);
        }                        
        
         unlink($RouteFileStatus);      
        if(file_exists(dirname($Path)."/Restored_".  basename($Path))){unlink(dirname($Path)."/Restored_".  basename($Path));}
        unlink($Path);               
//        unlink($RouteFileAdvancing);     
        rename(dirname($Path)."/$KeyProcess.txt", dirname($Path)."/Ok_$KeyProcess.txt");
        $Fifo->DeleteProcess($KeyProcess);
        
    }
    /*-------- Mueve los documentos de la papelera al repositorio que le corresponde ------ */
    private function MoveFileToRepository($ArrayStructureDefault,$ArrayStructureUser, $ArrayCatalogos, $DataBaseName, $IdRepositorio ,$NombreRepositorio,$IdFile,$RouteFileAdvancing,$KeyProcess)
    {
        $BD= new DataBase();
        $QueryGetFiles="SELECT *FROM temp_rep_$NombreRepositorio WHERE IdRepositorio=$IdFile";
        $Full = '';
        $Files=$BD->ConsultaSelect($DataBaseName, $QueryGetFiles);                

        if($Files['Estado']!=1){echo "Error al obtener los documentos del directorio $IdFile. MySQL: ".$Files['Estado'].PHP_EOL; return 0;}                        
        
        if(!(count($Files['ArrayDatos'])>0)){return 1;}
                
        
        /* Se construye la consulta para realizar el insert del documento en la papelera */
        $CadenaCampos_='IdRepositorio,IdDirectory, IdEmpresa,';
        /*----------------Cadena de  Campos del repositorio--------------------*/
        
         for($cont=0; $cont<count($ArrayStructureDefault); $cont++)
         {             
             $CadenaCampos_.= $ArrayStructureDefault[$cont]['name'].",";                              
         }  
         
         for($cont=0; $cont<count($ArrayStructureUser); $cont++)
         {             
             $CadenaCampos_.= $ArrayStructureUser[$cont]['name'].",";                              
         }  
         
         for($cont=0 ; $cont < count($ArrayCatalogos); $cont++)
         {
             $CadenaCampos_.=$ArrayCatalogos[$cont]['NombreCatalogo'].",";
         }                  
         
         $CadenaValores='';
         $InsertToTrash='';
         $CadenaDeleteFiles="DELETE FROM temp_rep_$NombreRepositorio WHERE IdRepositorio=$IdFile";
         $InsertToTrash= "INSERT INTO $NombreRepositorio (". trim($CadenaCampos_,',').") VALUES ";
         
         $NombreArchivo = $Files['ArrayDatos'][0]['NombreArchivo'];
             
         if(!($FileAdvancing=  fopen($RouteFileAdvancing, "a+"))){  $FileAdvancing.PHP_EOL;  } else {
            fwrite($FileAdvancing, "TitleFile=$NombreArchivo".PHP_EOL);
            fclose($FileAdvancing);
        }


//         $IdRepositorio = $Files['ArrayDatos'][0]['IdRepositorio'];
         $IdEmpresa = $Files['ArrayDatos'][0]['IdEmpresa'];
         $CadenaValores.="($IdFile,".$Files['ArrayDatos'][0]['IdDirectory'].",$IdEmpresa,";
         
         
         for($cont=0; $cont<count($ArrayStructureDefault); $cont++)
        {             
            $CampoDefault = $ArrayStructureDefault[$cont]['name'];
            $type = $ArrayStructureDefault[$cont]['type'];
            $value=$Files['ArrayDatos'][0][$CampoDefault];   
            
            if(strcasecmp($CampoDefault, "Full")==0)
                    $Full = $value;
   
            if(strcasecmp($type, "int")==0 or strcasecmp($type, "float")==0 or strcasecmp($type, "integer")==0)
            {
                if(!is_numeric($value))            
                   $CadenaValores.=" 0 ,";                   
                else
                
                   $CadenaValores.=" $value ,";
            }
            else
                  $CadenaValores.=" '$value' ,";
        }            

        /* Campos del repositorio */
        for($cont=0; $cont<count($ArrayStructureUser); $cont++)       
        {
            $CampoUsuario=$ArrayStructureUser[$cont]['name'];
            $type = $ArrayStructureUser[$cont]['type'];
            $value=$Files['ArrayDatos'][0][$CampoUsuario];

//                echo "$CampoUsuario = $value  tipo = $type".PHP_EOL;

            if(strcasecmp($CampoDefault, $CadenaValores))
            
            if(strcasecmp($type, "int")==0 or strcasecmp($type, "float")==0 or strcasecmp($type, "integer" )==0 or strcasecmp($type, "double")==0)
            {
                if(!is_numeric($value))              
                   $CadenaValores.=" 0 ,";                   
               else
                   $CadenaValores.=" $value ,";
            }
            else
                  $CadenaValores.=" '$value' ,";
        }  


        for($cont=0 ; $cont < count($ArrayCatalogos) ; $cont++)
        {
            $NombreCatalogo=$ArrayCatalogos[$cont]['NombreCatalogo'];
            $value=$Files['ArrayDatos'][0][$NombreCatalogo];
            $CadenaValores.=" $value ,";
        }


        $CadenaValores=trim($CadenaValores," ,");

        $CadenaValores.=') ,';        
        
        $CadenaValores=trim($CadenaValores,' ,');

        $InsertToTrash.=$CadenaValores;

//        echo "$InsertToTrash".PHP_EOL.PHP_EOL;
        
        $ResultadoInsertToTrash=$BD->ConsultaInsert($DataBaseName, $InsertToTrash);
        if($ResultadoInsertToTrash!=1)
        {
            echo "Error al restaurar el documento  \"".$Files['ArrayDatos'][0]['NombreArchivo']."\" en el repositorio $NombreRepositorio. MySQL: $ResultadoInsertToTrash".PHP_EOL.$InsertToTrash; 
            return 0;
            
        }
        else
        {
            $UpdateGlobal = "UPDATE RepositorioGlobal SET Full='$Full' WHERE IdFile=$IdFile AND IdRepositorio=$IdRepositorio";

            if(($ResultUpdateGlobal = $BD->ConsultaQuery($DataBaseName, $UpdateGlobal))!=1)
            {
                echo "Error al restaurar el elemento en Global. $ResultUpdateGlobal".PHP_EOL.$UpdateGlobal.PHP_EOL; 
                return 0;
            }
        }              
        
        if(($ResultadoDeleteFiles=$BD->ConsultaQuery($DataBaseName, $CadenaDeleteFiles))!=1)
        {
            echo "Error al eliminar el documento ".$Files['ArrayDatos'][0]['NombreArchivo']." de la papelera de reciclaje. MySQL: $ResultadoDeleteFiles.".PHP_EOL.$CadenaDeleteFiles.PHP_EOL;
            return 0;
            
        }
        else
            echo $CadenaDeleteFiles.PHP_EOL;
        
        $this->RegisterFileAsRestored(dirname($RouteFileAdvancing), $IdFile, $Files['ArrayDatos'][0]['NombreArchivo']);                
        
        if(!($FileAdvancing=  fopen(dirname($RouteFileAdvancing)."/Ok_$KeyProcess.ini", "a+"))){  $FileAdvancing.PHP_EOL;  } else {
            fwrite($FileAdvancing, $IdFile."=".$Files['ArrayDatos'][0]['NombreArchivo'].PHP_EOL);
            fclose($FileAdvancing);
        }
        
       return 1; 
    }
    
    /*--------------------------------------------------------------------------------------------------------------------------------------
     * Descripción: 
     *          Registro de Documentos restaurados.
     *-------------------------------------------------------------------------------------------------------------------------------------*/
    
    private function RegisterFileAsRestored($Path,$IdDrectory,$title)
    {        
        $config=  fopen(dirname($Path)."/Restored_".  basename($Path), "a+");
        /* Se reescribe la sección [Restored]. Consultar la descripción de esta función. */
        fwrite($config, "$IdDrectory=$title".PHP_EOL);
        fclose($config);
    }
    
    private function CheckAdvancingRestoreFiles()
    {
        $XML=new XML();
        $PathAdvancing=  filter_input(INPUT_POST, "PathAdvancing");
        
        $KeyProcess = filter_input(INPUT_POST, "KeyProcess");
        
        /* Se si el archivo Ok_KeyProcess.txt significa que el proceso ya termino de ejecutarse */
        if(file_exists(dirname($PathAdvancing)."/Ok_$KeyProcess.txt"))
        {
            
            if(file_exists(dirname($PathAdvancing)."/Ok_$KeyProcess.ini"))
                $FilesRestored = parse_ini_file (dirname($PathAdvancing)."/Ok_$KeyProcess.ini",true);
                                    
//            if(file_exists($PathAdvancing)){unlink($PathAdvancing);}
            
            $doc  = new DOMDocument('1.0','utf-8');
            libxml_use_internal_errors(true);
            $doc->formatOutput = true;
            $root = $doc->createElement("Ok");
            $doc->appendChild($root); 
            
            if(count($FilesRestored)>0)
            {
                foreach ($FilesRestored as $Key => $value)
                {
                    $Directory = $doc->createElement("File",$value);
                    $root->appendChild($Directory);
                }
            }            
            
            header ("Content-Type:text/xml");
            echo $doc->saveXML();  
            
            return 1;
        }
        
        if(!file_exists($PathAdvancing)){ $XML->ResponseXML("NotFound", 0, "<p>No se encontró el archivo de progreso</p>"); return 0; }
        
        if(!($Progress=parse_ini_file ($PathAdvancing,true))){$XML->ResponseXML("Error", 0, "<p>No fué posible abrir el archivo de progreso. </p>"); return 0;}
                        
        $doc  = new DOMDocument('1.0','utf-8');
        libxml_use_internal_errors(true);
        $doc->formatOutput = true;
        $root = $doc->createElement("Progress");
        $doc->appendChild($root); 
        if(isset($Progress['Progress']['TotalFiles']))
        {
            $TotalDirectories = $doc->createElement("TotalFiles",$Progress['Progress']['TotalFiles']);
            $root->appendChild($TotalDirectories);
        }
        if(isset($Progress['Progress']['TitleFile']))
        {
            $TitleDirectory = $doc->createElement("TitleFile",$Progress['Progress']['TitleFile']);
            $root->appendChild($TitleDirectory);
        }        
        if(isset($Progress['Progress']['NumberFile']))
        {
            $NumberDirectory = $doc->createElement("NumberFile",$Progress['Progress']['NumberFile']);
            $root->appendChild($NumberDirectory);
        }        

        header ("Content-Type:text/xml");
        echo $doc->saveXML();  
    }
    
    /* Cancela el proceso de eliminación de un directorio */
    private function CancelRestoringOfFiles()
    {
        $XML=new XML();

        $PathStatus=  filter_input(INPUT_POST, "PathStatus");
        $PathAdvancing = filter_input(INPUT_POST, "PathAdvancing");                           
        
        
        $Status=  fopen($PathStatus, "w");
        if(!$Status){$XML->ResponseXML("Error", 0, "Imposible cancelar el proceso debido al siguiente error. $Status"); return 0;}        
        fwrite($Status, "status=0");
        fclose($Status);
        
        if(file_exists($PathAdvancing)){unlink($PathAdvancing);}
        
        $XML->ResponseXML("CancelProgress", 1 , "Proceso Cancelado");
        
    }
    
    /*--------------------------------------------------------------------------
     * Se comprueba si ya se restauró el directorio
     *  return: 
     *              1 => Ya se elimino el directorio
     *              2 => No se ha eliminado el directorio
     *------------------------------------------------------------------------*/
    
    private function CheckIfFileWasRestored($Path, $IdDirectory)
    {
        if(!file_exists(dirname($Path)."/Restored_".  basename($Path))){return 0;}
        
        if(!($DirectoriesDeleted=parse_ini_file (dirname($Path)."/Restored_".  basename($Path),false))){echo "No pudo abrirse el archivo de registro de directorios removidos Restored_".  basename($Path).PHP_EOL; return 0;}
           
        $Registered=isset($DirectoriesDeleted[$IdDirectory]);
        if(!$Registered){return 0;}else {return 1;}
    }


    /* Función que prepara la configuración para la restauración de Directorios */
    private function RestoreDirectories()
    {
        $BD= new DataBase();
        $XmlRestore = filter_input(INPUT_POST, 'XmlRestore');
        $XML=new XML();
        $Fifo = new Fifo();
        
        $DataBaseName=  filter_input(INPUT_POST, "DataBaseName");
        $NombreRepositorio=  filter_input(INPUT_POST, "NombreRepositorio");
        $IdRepositorio=  filter_input(INPUT_POST, "IdRepositorio");
        $IdUsuario=filter_input(INPUT_POST, "IdUsuario");
        $NombreUsuario=  filter_input(INPUT_POST, "nombre_usuario");

        $xml = simplexml_load_string($XmlRestore);
        foreach ($xml->Directory as $nodo){
            $IdDirectory=$nodo->IdDirectory;
        }



        $QueryRestoreDirectory="UPDATE dir_$NombreRepositorio LEFT JOIN $NombreRepositorio ON dir_$NombreRepositorio.IdDirectory=$NombreRepositorio.IdDirectory
                        SET dir_$NombreRepositorio.status=1,$NombreRepositorio.status=1
                        WHERE dir_$NombreRepositorio.IdDirectory IN
                        (SELECT * FROM (SELECT IdDirectory FROM dir_$NombreRepositorio , (SELECT @pv := '$IdDirectory') initialisation WHERE find_in_set(parent_id, @pv) and length(@pv := concat(@pv, ',', IdDirectory)) or IdDirectory=$IdDirectory) dirs)
       ";
        $restore = $BD->ConsultaQuery($DataBaseName, $QueryRestoreDirectory);
        if($restore==1){
            $QueryRestoreFileGlobal="UPDATE RepositorioGlobal SET status=1
                                   WHERE IdRepositorio=$IdRepositorio AND NombreRepositorio='$NombreRepositorio' AND IdDirectory
                                   IN (SELECT * FROM (SELECT IdDirectory FROM dir_$NombreRepositorio , (SELECT @pv := '$IdDirectory') initialisation WHERE find_in_set(parent_id, @pv) and length(@pv := concat(@pv, ',', IdDirectory)) OR IdDirectory=$IdDirectory) dirs)
                                    ";
            $restoreFileGlobal=$BD->ConsultaQuery($DataBaseName, $QueryRestoreFileGlobal);
            if ($restoreFileGlobal==1){
                return XML::XMLReponse("Restore", 0, "Directorio restaurado con éxito");
                //Log::WriteEvent("19", $IdUsuario, $NombreUsuario, $NameDirectory);
            }else{
                return XML::XMLReponse("Error", 0, "No fue posible restaurar el directorio".$restoreFileGlobal);
            }
        }
        return XML::XMLReponse ("Error", 0, "No fue posible restaurar el directorio".$delete);
        
        /* Se registra el proceso en Fifo y se crea el archivo con los elementos a borrar en 
         * RestoreTrash/DataBaseName/User/ */

        /*
        $RutaDirTrash="/volume1/web/Configuracion/RestoreTrash/$DataBaseName/$NombreUsuario";       
        
        if(!file_exists($RutaDirTrash)){if(!mkdir($RutaDirTrash, 0777, true)){$XML->ResponseXML("Error", 0, "No se pudo crear el directorio <b>RestoreTrash</p>"); return 0;}}
        
        $archivo="$RutaDirTrash/RestoreDir.ini";
        
        if(file_exists($archivo)){if(!unlink($archivo)){$XML->ResponseXML("Error", 0, "No se pudo eliminar el archivo de restauración anterior."); return 0;}}
        
        $config=  fopen($archivo, "a+");
        if(!($config)){$XML->ResponseXML("Error", 0, "Error al crear archivo de configuración."); return;}  /* Error al abrir y crear el archivo de config
                
        
                        
        $xml=  simplexml_load_string($XmlRestore);      
        
        fwrite($config, "; Listado de directorios a restaurar. ".PHP_EOL);
        fwrite($config, "[RestoreDirectories]".PHP_EOL);
        fwrite($config, "DataBaseName=$DataBaseName".PHP_EOL);
        fwrite($config, "NombreRepositorio=$NombreRepositorio".PHP_EOL);
        fwrite($config, "IdRepositorio=$IdRepositorio".PHP_EOL); 
        fwrite($config, "NombreUsuario=$NombreUsuario".PHP_EOL); 
        fwrite($config, "IdUsuario=$IdUsuario".PHP_EOL); 
        fwrite($config, "[Directories]".PHP_EOL);
        // Se registran los directorios padre del árbol a restaurar
        foreach ($xml->Directory as $nodo)
        {
            fwrite($config, "$nodo->title[]=$nodo->IdDirectory".PHP_EOL);
            fwrite($config, "$nodo->title[]=$nodo->IdParent".PHP_EOL);            
        }
        fclose($config);
        
        // Se registra el proceso
        
        $KeyProcess=$Fifo->AddToStack("RestoreDirectories", $NombreUsuario, $archivo);
        
        if(!$KeyProcess){$XML->ResponseXML("Error", 0, "No pudo ser registrado el proceso en Fifo"); return 0;}
        
        $StartProcess=$Fifo->StartProcess($KeyProcess);
        
        if($StartProcess==0){$XML->ResponseXML("Error", 0, "No pudo inicializarse el proceso de borrado del directorio"); return 0;}
        
        rename($archivo, dirname($archivo)."/$KeyProcess.ini");
        
        $RouteFileStatus=dirname($archivo)."/Status_$KeyProcess.ini";
        $RouteFileAdvancing=dirname($archivo)."/Advancing_$KeyProcess.ini";

        
        // Archivo de status del servicio (Permite deter el servicio )
        if(!($FileProgress=fopen($RouteFileAdvancing, "a+"))){   echo "Error al crear archivo de Progress_$KeyProcess. ".$FileProgress.PHP_EOL;  return 0;}
        fwrite($FileProgress, "[Progress]".PHP_EOL);
        fwrite($FileProgress, "TotalDirectories=Calculando...".PHP_EOL);
        fclose($FileProgress);
        
        
        // Archivo de Progreso de la restauración
        
        if(!($FileStatus=fopen($RouteFileStatus, "a+"))){   echo "Error al crear archivo de Status_$KeyProcess. ".$FileStatus.PHP_EOL;  return 0;}
        fwrite($FileStatus, "status=1");
        fclose($FileStatus);
        
        $doc  = new DOMDocument('1.0','utf-8');
        libxml_use_internal_errors(true);
        $doc->formatOutput = true;
        $root = $doc->createElement("RestoreDirectories");
        $doc->appendChild($root); 
        $Estado=$doc->createElement("Estado",1);
        $root->appendChild($Estado);
        $Mensaje=$doc->createElement("Mensaje","<p>Iniciando Proceso de Restauración.</p>");
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
    
   private function CheckAdvancingRestoreDir()
    {
        $XML=new XML();
        
        $PathAdvancing=  filter_input(INPUT_POST, "PathAdvancing");
        
        $KeyProcess = filter_input(INPUT_POST, "KeyProcess");
        
        /* Se si el archivo Ok_KeyProcess.txt significa que el proceso ya termino de ejecutarse */
        if(file_exists(dirname($PathAdvancing)."/Ok_$KeyProcess.txt")){            
            $DirectoriesRestored = parse_ini_file (dirname($PathAdvancing)."/Ok_$KeyProcess.ini",true);
            
            if(file_exists($PathAdvancing)){unlink($PathAdvancing);}
            
            $doc  = new DOMDocument('1.0','utf-8');
            libxml_use_internal_errors(true);
            $doc->formatOutput = true;
            $root = $doc->createElement("Ok");
            $doc->appendChild($root); 
            
            foreach ($DirectoriesRestored as $Key => $value)
            {
                $Directory = $doc->createElement("Directory",$value);
                $root->appendChild($Directory);
            }
            
            header ("Content-Type:text/xml");
            echo $doc->saveXML();  
            
            return 1;
        }
        
        if(!file_exists($PathAdvancing)){ $XML->ResponseXML("NotFound", 0, "<p>No se encontró el archivo de progreso</p>"); return 0; }
        
        if(!($Progress=parse_ini_file ($PathAdvancing,true))){$XML->ResponseXML("Error", 0, "<p>No fué posible abrir el archivo de progreso. </p>"); return 0;}
                        
        $doc  = new DOMDocument('1.0','utf-8');
        libxml_use_internal_errors(true);
        $doc->formatOutput = true;
        $root = $doc->createElement("Progress");
        $doc->appendChild($root); 
        if(isset($Progress['Progress']['TotalDirectories']))
        {
            $TotalDirectories = $doc->createElement("TotalDirectories",$Progress['Progress']['TotalDirectories']);
            $root->appendChild($TotalDirectories);
        }
        if(isset($Progress['Progress']['TitleDirectory']))
        {
            $TitleDirectory = $doc->createElement("TitleDirectory",$Progress['Progress']['TitleDirectory']);
            $root->appendChild($TitleDirectory);
        }        
        if(isset($Progress['Progress']['NumberDirectory']))
        {
            $NumberDirectory = $doc->createElement("NumberDirectory",$Progress['Progress']['NumberDirectory']);
            $root->appendChild($NumberDirectory);
        }        
        if(isset($Progress['Progress']['TitleFile']))
        {
            $TitleFile = $doc->createElement("TitleFile",$Progress['Progress']['TitleFile']);
            $root->appendChild($TitleFile);
        }
        header ("Content-Type:text/xml");
        echo $doc->saveXML();  
    }
    
    /* Función que se ejecuta como servicio para la restauración de directorios  */
    
    private function ServiceRestoreDirectories()
    {
        $XML=new XML();
        $BD= new DataBase();
        $designer=new DesignerForms();
        $Fifo= new Fifo();
        $Catalog = new Catalog();
        
        
        $Parametros=$_SERVER['argv'];      
        $KeyProcess=$Parametros[1];        
        $Path=$Parametros[2];  
        
        $RouteFileStatus=dirname($Path)."/Status_$KeyProcess.ini";
        $RouteFileAdvancing=dirname($Path)."/Advancing_$KeyProcess.ini";
        if(!file_exists($Path)){echo "No existe el archivo de registro de directorios a eliminar. ".PHP_EOL; return 0;}
        
        if(!($Restore=parse_ini_file ($Path,true))){echo "No pudo abrirse el archivo con la clave de proceso $KeyProcess.ini".PHP_EOL; return 0;}
        
        /* Variables de Repositorio y BD */
        
        $Directories = $Restore['Directories'];
        $NombreRepositorio = $Restore['RestoreDirectories']['NombreRepositorio'];
        $DataBaseName = $Restore['RestoreDirectories']['DataBaseName'];
        $IdRepositorio = $Restore['RestoreDirectories']['IdRepositorio'];
        $IdUsuario = $Restore['RestoreDirectories']['IdUsuario'];
        $NombreUsuario = $Restore['RestoreDirectories']['NombreUsuario'];
        
        /* Archivos de Configuración de Estructura de repositorio */
        if(!file_exists("../Configuracion/$DataBaseName.ini")){$XML->ResponseXML("Error", 0, "No existe el archivo de Configuración del $NombreRepositorio"); return 0;}
        $EstructuraConfig=parse_ini_file ("../Configuracion/$DataBaseName.ini",true);
 
        $ArrayStructureUser=$designer->ReturnStructure($NombreRepositorio,$EstructuraConfig[$NombreRepositorio]);
        $ArrayStructureDefault=$designer->ReturnStructureDefault($NombreRepositorio,$EstructuraConfig[$NombreRepositorio]);
        
        $ArrayCatalogos = $Catalog->getFilteredArrayCatalogsDetail($DataBaseName, $IdRepositorio);
        
        if(!is_array($ArrayCatalogos)){echo "Error al obtener los catálogos. Mysql: ".$ArrayCatalogos.PHP_EOL; return 0;}                                
        
        /* Archivo que contiene el avance del borrado de los directorios */
        if(!($FileAdvancing=  fopen($RouteFileAdvancing, "a+"))){  $FileAdvancing.PHP_EOL;  }         
        fwrite($FileAdvancing, "NumberDirectory=1".PHP_EOL);
        fclose($FileAdvancing);   
        
        /* Se obtienen los directorios temporales y a partir de estos se restaura el directorio (padre) seleccionado por el susuario. */
        $QuerySelectDir = "SELECT * FROM temp_dir_$NombreRepositorio ORDER BY parent_id";
        $ResultTempDirectories = $BD->ConsultaSelect($DataBaseName, $QuerySelectDir);
        if($ResultTempDirectories['Estado']!=1){echo "Error al obtener los directorios temporales. ".$ResultTempDirectories['Estado'].PHP_EOL; return 0;}
        $TempDirectories_ = $ResultTempDirectories['ArrayDatos'];
        
        /* Se organiza en un array asociativo el array con los directorios que están dentro de la tabla temporal */
        $TempDirectories = array();       
        for($cont = 0; $cont < count($TempDirectories_) ; $cont++)
        {
            foreach ($TempDirectories_[$cont] as $Campo => $valor)
            {
                $TempDirectories[$TempDirectories_[$cont]['IdDirectory']] = $TempDirectories_[$cont];
            }
        }                
        
            
        /* Se recorren los directorios padre que serán restaurados junto a sus subdirectorios.
           Los directorios restaurados son eliminados del Array TempDirectories         */                        
        
        foreach($Directories as $campo => $valor)
        {                                                 
            $FileStatus=  parse_ini_file($RouteFileStatus);
            if($FileStatus['status']==0){echo "Restauracion de directorios cancelada por el usuario."; return 0; }

            $IdDirectory=$valor[0];
            $IdParent=$valor[1];
            $title=$campo;
            
            echo "Comenzando restauración del directorio $title id=$IdDirectory   id parent = $IdParent".PHP_EOL;                        
            
            if($this->CheckIfDirectoryWasRestored($Path, $IdDirectory)){echo "El directorio \"$campo\" ya había sido restaurado."; continue;}           
                                    
            $TempDirectories=$this->MoveDirectoryToItsRepository($IdUsuario, $NombreUsuario, $KeyProcess,$ArrayStructureDefault,$ArrayStructureUser,$ArrayCatalogos,$Path,$RouteFileAdvancing,$DataBaseName, $IdRepositorio ,$NombreRepositorio, $title, $IdDirectory,$IdParent,$TempDirectories);                                   
        }                        
        
        unlink($RouteFileStatus);      
        if(file_exists(dirname($Path)."/Restored_".  basename($Path))){unlink(dirname($Path)."/Restored_".  basename($Path));}
        unlink($Path);               
//        unlink($RouteFileAdvancing);     
        rename(dirname($Path)."/$KeyProcess.txt", dirname($Path)."/Ok_$KeyProcess.txt");
        $Fifo->DeleteProcess($KeyProcess);
    }            
    
    /*--------------------------------------------------------------------------
     * Obtiene el listado de archivos contenidos en un directorio 
     * para posteriormente ser movidos nuevamente a su repositorio
     --------------------------------------------------------------------------*/
    
    private function GetFilesFromDirectory($ArrayStructureDefault,$ArrayStructureUser, $ArrayCatalogos, $DataBaseName, $IdRepositorio ,$NombreRepositorio,$IdDirectory,$RouteFileAdvancing)
    {
        $BD= new DataBase();
        $QueryGetFiles="SELECT *FROM temp_rep_$NombreRepositorio WHERE IdDirectory=$IdDirectory";
        $Files=$BD->ConsultaSelect($DataBaseName, $QueryGetFiles);                
        $Full = '';
        if($Files['Estado']!=1){echo "Error al obtener los documentos del directorio $IdDirectory. MySQL: ".$Files['Estado'].PHP_EOL; return 0;}                        
        
        if(!(count($Files['ArrayDatos'])>0)){return 1;}
        
        /* Se construye la consulta para realizar el insert del documento en la papelera */
        $CadenaCampos_='IdRepositorio,IdDirectory, IdEmpresa,';
        /*----------------Cadena de  Campos del repositorio--------------------*/
        
         for($cont=0; $cont<count($ArrayStructureDefault); $cont++)
         {             
             $CadenaCampos_.= $ArrayStructureDefault[$cont]['name'].",";                              
         }  
         
         for($cont=0; $cont<count($ArrayStructureUser); $cont++)
         {             
             $CadenaCampos_.= $ArrayStructureUser[$cont]['name'].",";                              
         }  
         
         for($cont=0 ; $cont < count($ArrayCatalogos); $cont++)
         {
             $CadenaCampos_.=$ArrayCatalogos[$cont]['NombreCatalogo'].",";
         }                  
         
         $CadenaValores='';
         $InsertToTrash='';
         $CadenaDeleteFiles="DELETE FROM temp_rep_$NombreRepositorio WHERE IdDirectory=$IdDirectory";
         
         $InsertToTrash= "INSERT INTO $NombreRepositorio (". trim($CadenaCampos_,',').") VALUES ";
         
         /*----------------------------------------------------------------------------
          * Construcción de cadena de Valores.
          * Cada iteración del For es un documento perteneciente al mismo directorio
          * --------------------------------------------------------------------------*/
         
         for($ContFile=0; $ContFile < count($Files['ArrayDatos']); $ContFile++)
         {                          
             $NombreArchivo = $Files['ArrayDatos'][$ContFile]['NombreArchivo'];
             
             if(!($FileAdvancing=  fopen($RouteFileAdvancing, "a+"))){  $FileAdvancing.PHP_EOL;  } else {
            fwrite($FileAdvancing, "TitleFile=$NombreArchivo".PHP_EOL);
            fclose($FileAdvancing);
            }
             
             
             $IdFile = $Files['ArrayDatos'][$ContFile]['IdRepositorio'];
             $IdEmpresa = $Files['ArrayDatos'][$ContFile]['IdEmpresa'];
             $CadenaValores.="($IdFile,$IdDirectory,$IdEmpresa,";
             
             for($cont=0; $cont<count($ArrayStructureDefault); $cont++)
            {             
                $CampoDefault = $ArrayStructureDefault[$cont]['name'];
                $type = $ArrayStructureDefault[$cont]['type'];
                $value=$Files['ArrayDatos'][$ContFile][$CampoDefault];
                
                if(strcasecmp($CampoDefault, "Full")==0)
                        $Full = $value;
                
//                echo "$CampoDefault = $value tipo = $type".PHP_EOL;
                
                if(strcasecmp($type, "int")==0 or strcasecmp($type, "float")==0 or strcasecmp($type, "integer")==0)
                {
                    if(!is_numeric($value))
                   {                    
                       $CadenaValores.=" 0 ,";                   
                   }else
                   {
                       $CadenaValores.=" $value ,";
                   }
                }
                else
                {
                      $CadenaValores.=" '$value' ,";
                }

            }            
                           
            /* Campos del repositorio */
            for($cont=0; $cont<count($ArrayStructureUser); $cont++)       
            {
                $CampoUsuario=$ArrayStructureUser[$cont]['name'];
                $type = $ArrayStructureUser[$cont]['type'];
                $value=$Files['ArrayDatos'][$ContFile][$CampoUsuario];
                
//                echo "$CampoUsuario = $value  tipo = $type".PHP_EOL;
                
                if(strcasecmp($type, "int")==0 or strcasecmp($type, "float")==0 or strcasecmp($type, "integer")==0)
                {
                    if(!is_numeric($value))
                   {                    
                       $CadenaValores.=" 0 ,";                   
                   }else
                   {
                       $CadenaValores.=" $value ,";
                   }
                }
                else
                {
                      $CadenaValores.=" '$value' ,";
                }
            }  
            
            
            for($cont=0 ; $cont < count($ArrayCatalogos) ; $cont++)
            {
                $NombreCatalogo=$ArrayCatalogos[$cont]['NombreCatalogo'];
                $value=$Files['ArrayDatos'][$ContFile][$NombreCatalogo];
                $CadenaValores.=" $value ,";
            }
            
            
            $CadenaValores=trim($CadenaValores," ,");
            
            $CadenaValores.=') ,';  
            
            $UpdateGlobal = "UPDATE RepositorioGlobal SET Full='$Full' WHERE IdFile=$IdFile AND IdRepositorio=$IdRepositorio";
         
            echo $UpdateGlobal.PHP_EOL;
         
            if(($ResultUpdateGlobal = $BD->ConsultaQuery($DataBaseName, $UpdateGlobal))!=1)
            {
                echo "Error al restaurar el elemento en Global. $ResultUpdateGlobal".PHP_EOL.$UpdateGlobal.PHP_EOL; 
                return 0;
            }
            
            sleep(2);
         }
         
         $CadenaValores=trim($CadenaValores,' ,');
         
         $InsertToTrash.=$CadenaValores;
         
         echo "$InsertToTrash".PHP_EOL.PHP_EOL;
         $ResultadoInsertToTrash=$BD->ConsultaInsert($DataBaseName, $InsertToTrash);
         if($ResultadoInsertToTrash!=1){echo "Error al trasladar documentos a la papelera los documentos del directorio $IdDirectory. MySQL: $ResultadoInsertToTrash "; return 0;}
                                    
         $ResultadoDeleteFiles=$BD->ConsultaQuery($DataBaseName, $CadenaDeleteFiles);
         if($ResultadoDeleteFiles!=1){echo "Error al eliminar los documentos de $NombreRepositorio. MySQL: $ResultadoDeleteFiles.".PHP_EOL; return 0;}
                          
        return 1; 
    }   
    
    
    /*--------------------------------------------------------------------------
     *  Quita  un directorio y documentos de la papelera a su ruta contenedor original    
     */
    private function MoveDirectoryToItsRepository($IdUsuario, $NombreUsuario, $KeyProcess,$ArrayStructureDefault,$ArrayStructureUser,$ArrayCatalogos,$Path,$RouteFileAdvancing,$DataBaseName, $IdRepositorio ,$NombreRepositorio, $title ,$IdDirectory,$IdParent,$TempDirectories)
    {
        $BD= new DataBase();           
        $TempTree=array();
        $Log = new Log();
        
        /* Construcción de árbol de subdirectorios a restaurar */
        
        if(!isset($TempDirectories[$IdDirectory])){echo "El directorio \"$title\" no se encuentra registrado como directorio listo para restaurar."; return 0; }        
        $TempTree[$IdDirectory] = $TempDirectories[$IdDirectory];
        unset($TempDirectories[$IdDirectory]);

        foreach ($TempDirectories as $Fila => $valor)
        {          
            $TempIdParent = $TempDirectories["$Fila"]['parent_id'];
            if(isset($TempTree["$TempIdParent"]))
            {
                $TempTree[$Fila] = $TempDirectories[$Fila];
                unset($TempDirectories[$Fila]);
            }
        }     
        
        if(!($FileAdvancing=  fopen($RouteFileAdvancing, "a+"))){  $FileAdvancing.PHP_EOL;  } else {
            fwrite($FileAdvancing, "TotalDirectories=".count($TempTree).PHP_EOL);
            fclose($FileAdvancing);
            }
        
        if(!($FileAdvancing=  fopen(dirname($RouteFileAdvancing)."/Ok_$KeyProcess.ini", "a+"))){  $FileAdvancing.PHP_EOL;  } else {
            fwrite($FileAdvancing, $IdDirectory."=$title".PHP_EOL);
            fclose($FileAdvancing);
        }
            
        
        unset($TempTree[$IdDirectory]);
        
        /* Búsqueda de documentos por directorio y pegado de directorios a su ruta original */
        $CadenaConsulta = "SELECT *FROM temp_dir_$NombreRepositorio WHERE IdDirectory = $IdDirectory ";
        foreach ($TempTree as $Key => $value)
        {
            $CadenaConsulta.= " OR IdDirectory = ".$TempTree[$Key]['IdDirectory'];
        }
        
        echo "$CadenaConsulta".PHP_EOL;
        $ResultArrayDirectories = $BD->ConsultaSelect($DataBaseName, $CadenaConsulta);

        if($ResultArrayDirectories['Estado']!=1){"Error al intentar obtener los datos del árbol de directorios desde la papelera de reciclaje. ".$ResultArrayDirectories['Estado']; return 0;}
        
        $ArrayDirectories = $ResultArrayDirectories['ArrayDatos'];
        
        
        $AuxProgress=0;
        for($cont = 0; $cont < count($ArrayDirectories); $cont++)
        {
            echo dirname($RouteFileAdvancing).PHP_EOL;
            if($this->CheckIfDirectoryWasRestored($Path, $ArrayDirectories[$cont]["IdDirectory"])){echo "El directorio \"".$ArrayDirectories[$cont]["title"]."\" ya había sido restaurado.".PHP_EOL; continue;}
            
            /* Registro del Progreso de restauración */
            $AuxProgress++;
            if(!($FileAdvancing=  fopen($RouteFileAdvancing, "a+"))){  $FileAdvancing.PHP_EOL;  } else {
                fwrite($FileAdvancing, "NumberDirectory=".$AuxProgress .PHP_EOL);
                fwrite($FileAdvancing, "TitleDirectory=".$ArrayDirectories[$cont]["title"].PHP_EOL);
                fclose($FileAdvancing);
            }
            
            $QueryInsertToRepository = "INSERT INTO dir_$NombreRepositorio (IdDirectory, parent_id, title, path) VALUES (".$ArrayDirectories[$cont]["IdDirectory"].",".$ArrayDirectories[$cont]["parent_id"].",'".$ArrayDirectories[$cont]["title"]."' , '".$ArrayDirectories[$cont]["path"]."')" ;            
            $ResultInsert = $BD->ConsultaQuery($DataBaseName, $QueryInsertToRepository);
            if($ResultInsert!=1){echo "Ocurrió un error al recuperar el directorio \"".$ArrayDirectories[$cont]["title"]."\"  al repositorio $NombreRepositorio. $ResultInsert".PHP_EOL; return 0;}
            $this->GetFilesFromDirectory($ArrayStructureDefault, $ArrayStructureUser, $ArrayCatalogos, $DataBaseName, $IdRepositorio, $NombreRepositorio,  $ArrayDirectories[$cont]["IdDirectory"], $RouteFileAdvancing);
            $DeleteFromTemp = "DELETE FROM temp_dir_$NombreRepositorio WHERE IdDirectory = ".$ArrayDirectories[$cont]["IdDirectory"];
            $ResultDeleteFromTemp = $BD->ConsultaInsert($DataBaseName, $DeleteFromTemp);
            if($ResultDeleteFromTemp!=1){echo "Error al quitar el directorio de la papelera \"".$ArrayDirectories[$cont]["title"]."\"".$ResultDeleteFromTemp.PHP_EOL; return 0;}
                        
            $this->RegisterDirectoryAsRestored($Path, $ArrayDirectories[$cont]["IdDirectory"], $ArrayDirectories[$cont]["title"]);                                                
            sleep(1);
        }
                      
        $Log->Write("22", $IdUsuario, $NombreUsuario, $title, $DataBaseName);
        
        return $TempDirectories;
    }
    
    /*--------------------------------------------------------------------------------------------------------------------------------------
     * Descripción: 
     *          Registro de directorios restaurados.
     *-------------------------------------------------------------------------------------------------------------------------------------*/
    
    private function RegisterDirectoryAsRestored($Path,$IdDrectory,$title)
    {        
        $config=  fopen(dirname($Path)."/Restored_".  basename($Path), "a+");
        /* Se reescribe la sección [Restored]. Consultar la descripción de esta función. */
        fwrite($config, "$IdDrectory=$title".PHP_EOL);
        fclose($config);
    }
    
    /*--------------------------------------------------------------------------
     * Se comprueba si ya se restauró el directorio
     *  return: 
     *              1 => Ya se elimino el directorio
     *              2 => No se ha eliminado el directorio
     *------------------------------------------------------------------------*/
    
    private function CheckIfDirectoryWasRestored($Path, $IdDirectory)
    {
        if(!file_exists(dirname($Path)."/Restored_".  basename($Path))){return 0;}
        
        if(!($DirectoriesDeleted=parse_ini_file (dirname($Path)."/Restored_".  basename($Path),false))){echo "No pudo abrirse el archivo de registro de directorios removidos Restored_".  basename($Path).PHP_EOL; return 0;}
           
        $Registered=isset($DirectoriesDeleted[$IdDirectory]);
        if(!$Registered){return 0;}else {return 1;}
    }
    
    /* Cancela el proceso de eliminación de un directorio */
    private function CancelRestoringOfDirectories()
    {
        $XML=new XML();
        $PathStatus=  filter_input(INPUT_POST, "PathStatus");
        $PathAdvancing = filter_input(INPUT_POST, "PathAdvancing");                           
        
        
        $Status=  fopen($PathStatus, "w");
        if(!$Status){$XML->ResponseXML("Error", 0, "Imposible cancelar el proceso debido al siguiente error. $Status"); return 0;}        
        fwrite($Status, "status=0");
        fclose($Status);
        
        if(file_exists($PathAdvancing)){unlink($PathAdvancing);}
        
        $XML->ResponseXML("CancelProgress", 1 , "Proceso Cancelado");
        
    }
    
}

$Trash = new Trash();
