<?php

/*
 * Clase que se ejecuta como servicio para la eliminación de Directorios
 * y todo sus subdirectorios incluyendo archivos.
 * 
 * La clase recibe como parámetros:
 *      
 *      - KeyProcess
 *      - Path del archivo con los directorios a eliminar
 * 
 *  El proceso de borrado se realiza comenzando con los subdirectorios del más bajo nivel.
 *  Esto le da la posibilidad al usuario de cancelar la operación y que no se 
 *  corrompa la estructura del árbol de directorios por haber borrado un directorio 
 *  padre.
 * 
 *  Únicamente el directorio el directorio padre es el que aparece en la papelera de reciclaje.
 *  
 * La el resultado de Salida de esta clase es guardada en DeleteDirectory/KeyProcess.text
 */



/**
 * Description of ServiceDeleteDirectory
 *
 * @author daniel
 */

$RoutFile = dirname(getcwd());        


require_once "$RoutFile/php/DataBase.php";
require_once "$RoutFile/php/XML.php";
require_once "$RoutFile/php/DesignerForms.php";
require_once "$RoutFile/php/Catalog.php";
require_once(__DIR__ . '/Fifo.php');

class ServiceDeleteDirectory {
    public function __construct() {
        $this->Ajax();
    }    
    
    private function Ajax()
    {
        switch (filter_input(INPUT_POST, "opcion"))
        {           
            case 'CheckAdvancing': $this->CheckAdvancing(); break;
            case 'CancelAdvancing': $this->CancelAdvancing(); break;
            default: $this->ReadConfig(); break;
        }
    }
    
    /*--------------------------------------------------------------------------
     * Se obtiene el avance del servicio de borrado de directorios.
     *-------------------------------------------------------------------------*/
    
    private function CheckAdvancing()
    {
        $XML=new XML();
        $PathAdvancing=  filter_input(INPUT_POST, "PathAdvancing");
        $KeyProcess=  filter_input(INPUT_POST, "KeyProcess");
        
        /* Se si el archivo Ok_KeyProcess.txt significa que el proceso ya termino de ejecutarse */
        if(file_exists(dirname($PathAdvancing)."/Ok_$KeyProcess.txt")){      
            unlink($PathAdvancing);
            
            $doc  = new DOMDocument('1.0','utf-8');
            libxml_use_internal_errors(true);
            $doc->formatOutput = true;
            $root = $doc->createElement("Ok");
            $doc->appendChild($root); 
            $Mensaje = $doc->createElement("Mensaje","Directorio eliminado");
            $root->appendChild($Mensaje);
            header ("Content-Type:text/xml");
            echo $doc->saveXML();                          
            
            return 1;
        }
        
        
        if(!file_exists($PathAdvancing)){
            $XML->ResponseXML("NotFound", 0, "<p>No se encontró el archivo de progreso</p>");
            return 0;
        }

        $Progress = parse_ini_file ($PathAdvancing,true);

        if(!is_array($Progress)){
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
        if(isset($Progress['Progress']['TitleFile']))
        {
            $TitleFile = $doc->createElement("TitleFile",$Progress['Progress']['TitleFile']);
            $root->appendChild($TitleFile);
        }
        header ("Content-Type:text/xml");
        echo $doc->saveXML();        
    }
    
    /* Cancela el proceso de eliminación de un directorio */
    function CancelAdvancing()
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
     *  Se lee el archivo que contiene los datos del directorio a eliminar, así 
     *  como el registro de sus subdirectorios.
     *-------------------------------------------------------------------------*/
    
    private function ReadConfig()
    {
        $XML=new XML();
        $Catalog = new Catalog();
        $designer=new DesignerForms();
        $Fifo= new Fifo();
        
        $Parametros=$_SERVER['argv'];      
        $KeyProcess=$Parametros[1];        
        $Path=$Parametros[2];         
        $RouteFileStatus=dirname($Path)."/Status_$KeyProcess.ini";
        $RouteFileAdvancing=dirname($Path)."/Advancing_$KeyProcess.ini";
        if(!file_exists($Path)){echo "No existe el archivo de registro de directorios a eliminar. ".PHP_EOL; return 0;}
        
        if(!file_exists($RouteFileAdvancing)){touch($RouteFileAdvancing);}
        
        /* Archivo de status del servicio (Permite deter el servicio ) */
        if(!($FileStatus=fopen($RouteFileStatus, "a+"))){   echo "Error al crear archivo de estado del proceso $KeyProcess. ".$FileStatus.PHP_EOL;  return 0;}
        fwrite($FileStatus, "status=1");
        fclose($FileStatus);
        
        if(!($Directories=parse_ini_file ($Path,true))){echo "No pudo abrirse el archivo con la clave de proceso $KeyProcess.ini".PHP_EOL; return 0;}
        
        /* Variables de Repositorio y BD */
        
        $ChildDirectories = $Directories['Directories']['Directory'];
        $NombreRepositorio = $Directories['DeleteDirectory']['NombreRepositorio'];
        $DataBaseName = $Directories['DeleteDirectory']['DataBaseName'];
        $IdUsuario = $Directories['DeleteDirectory']['IdUsuario'];
        $IdRepositorio = $Directories['DeleteDirectory']['IdRepositorio'];
        $PathDirectoryFather = $Directories['DeleteDirectory']['PathDirectory'];
        $NombreUsuario = $Directories['DeleteDirectory']['NombreUsuario'];
        
        
        /* Archivos de Configuración de Estructura de repositorio */
        if(!file_exists("../Configuracion/$DataBaseName.ini")){$XML->ResponseXML("Error", 0, "No existe el archivo de Configuración del $NombreRepositorio"); return 0;}
        $EstructuraConfig=parse_ini_file ("../Configuracion/$DataBaseName.ini",true);
 
        $ArrayStructureUser=$designer->ReturnStructure($NombreRepositorio,$EstructuraConfig[$NombreRepositorio]);
        $ArrayStructureDefault=$designer->ReturnStructureDefault($NombreRepositorio,$EstructuraConfig[$NombreRepositorio]);
        
        $ArrayCatalogos = $Catalog->getCatalogsArray($DataBaseName, $IdRepositorio);
        
        if(!is_array($ArrayCatalogos)){
            echo "Error al obtener los catálogos. Mysql: ".PHP_EOL; 
            return 0;
            
        }                        
        
        /* Archivo que contiene el avance del borrado de los directorios */
        if(!($FileAdvancing=  fopen($RouteFileAdvancing, "a+"))){  $FileAdvancing.PHP_EOL;  } 
        
        fwrite($FileAdvancing, "[Progress]".PHP_EOL);
        fwrite($FileAdvancing, "TotalDirectories=".(count($ChildDirectories)+1).PHP_EOL);    /* Se suman los subdirectorios y el directorio padre */
        fwrite($FileAdvancing, "NumberDirectory=1".PHP_EOL);
        fclose($FileAdvancing);

        $AuxProgress=0;
        
        /* Se recorren los subdirectorios de menor a mayor para evitar corromper el árbol */                        
        for($cont=(count($ChildDirectories)-1) ; $cont>=0 ; $cont--)
        {                                         
            $FileStatus=  parse_ini_file($RouteFileStatus);
            if($FileStatus['status']==0){echo "La operación de borrado del directorio ".$Directories['DeleteDirectory']['title']." detenido por el usuario."; return 0; }
                        
            $RowIni=$ChildDirectories[$cont];   /* Fila del Archivo de registro DeleteDirectory */
            $DataDirectory = explode('###', $RowIni);
            $IdDirectory = $DataDirectory[0];
            $IdParent = $DataDirectory[1];
            $title = $DataDirectory[2];
            $PathDirectory = $DataDirectory[3];
            
            echo "Comenzando eliminación del directorio $title".PHP_EOL;                        
            
            /* Se registra el avance para mostrar la barra de progreso */
            $AuxProgress++;
            if(!($FileAdvancing=  fopen($RouteFileAdvancing, "a+"))){  $FileAdvancing.PHP_EOL;  } else {
                fwrite($FileAdvancing, "NumberDirectory=".$AuxProgress .PHP_EOL);
                fwrite($FileAdvancing, "TitleDirectory=$title".PHP_EOL);
                fclose($FileAdvancing);
            }
            
                        
            /* Se comprueba si el directorio ya había sido borrado */
            if($this->CheckIfWasRemoved($Path, $IdDirectory)){continue;}
            
            /* Se mueven los documentos contenidos en el directorio a la papelera de reciclaje */
            if(!$this->GetFilesFromDirectory($ArrayStructureDefault,$ArrayStructureUser,$ArrayCatalogos, $DataBaseName, $IdRepositorio, $NombreRepositorio, $IdDirectory, $IdUsuario, $NombreUsuario, $RouteFileAdvancing)){die("Error al obtener los documentos de $title".PHP_EOL);}
                        
            $MoveToTrash=$this->MoveDirectoryToTrash($DataBaseName,$NombreRepositorio, $IdDirectory,$IdParent, $IdUsuario, $NombreUsuario, 0, $title,$PathDirectory);           

            echo "Resultado de mover el directorio $title a la papelera: $MoveToTrash".PHP_EOL;
            
            ($MoveToTrash)?$this->RegisterDirectoryAsDeleted($Path, $IdDirectory,$title) : false;                                                                  
        }
        
        
        /* Se elimina el directorio Padre  */
        $IdDirectory= $Directories['DeleteDirectory']['IdDirectory'];
        $IdParent = $Directories['DeleteDirectory']['IdParent'];
        $title=$Directories['DeleteDirectory']['title'];           
        
        /* Se registra el avance para mostrar la barra de progreso */
        $AuxProgress++;
        if(!($FileAdvancing=  fopen($RouteFileAdvancing, "a+"))){  $FileAdvancing.PHP_EOL;  } else {
        fwrite($FileAdvancing, "TitleDirectory=$title".PHP_EOL);
        fwrite($FileAdvancing, "NumberDirectory=".$AuxProgress .PHP_EOL);
        fclose($FileAdvancing);
        }
        
        
        $FileStatus=  parse_ini_file($RouteFileStatus);
        if($FileStatus['status']==0){echo "La operación de borrado del directorio ".$Directories['DeleteDirectory']['title']." detenido por el usuario."; return 0; }
        
        

        
        if($this->CheckIfWasRemoved($Path, $IdDirectory)){return 0;}
        
        if(!$this->GetFilesFromDirectory($ArrayStructureDefault,$ArrayStructureUser,$ArrayCatalogos, $DataBaseName, $IdRepositorio, $NombreRepositorio, $IdDirectory, $IdUsuario, $NombreUsuario, $RouteFileAdvancing)){die("Error al obtener los documentos de $title".PHP_EOL);}
        
        /* FlagFather identifica al directorio como el padre de todos los subdirectorios que se movieron a la papelera
         * 1 = Directorio Padre
         * 0 = Subdirectorio 
         */ 
        
        $ResultDelete=$this->MoveDirectoryToTrash($DataBaseName,$NombreRepositorio, $IdDirectory, $IdParent, $IdUsuario, $NombreUsuario,  1,  $title, $PathDirectoryFather);  
        
//        unlink($RouteFileAdvancing);
        unlink($RouteFileStatus);      
        if(file_exists(dirname($Path)."/Deleted_".  basename($Path))){unlink(dirname($Path)."/Deleted_".  basename($Path));}
        unlink($Path);
        rename(dirname($Path)."/$KeyProcess.txt", dirname($Path)."/Ok_$KeyProcess.txt");
        echo "Resultado de mover el directorio $title a la papelera: $ResultDelete".PHP_EOL;
        
        $Fifo->DeleteProcess($KeyProcess);
        
                
    }
    
    /*--------------------------------------------------------------------------
     * Obtiene el listado de archivos contenidos en un directorio 
     * para posteriormente ser movidos a la papelera de reciclaje.
     --------------------------------------------------------------------------*/
    
    private function GetFilesFromDirectory($ArrayStructureDefault,$ArrayStructureUser, $ArrayCatalogos, $DataBaseName, $IdRepositorio, $NombreRepositorio,$IdDirectory, $IdUsuario, $NombreUsuario, $RouteFileAdvancing)
    {
        $BD= new DataBase();
        $QueryGetFiles="SELECT *FROM $NombreRepositorio WHERE IdDirectory=$IdDirectory";
        $UpdateGlobal = "UPDATE RepositorioGlobal SET Full='' WHERE IdDirectory = $IdDirectory AND IdRepositorio = $IdRepositorio";
         $DeleteFilesFromTrash = "DELETE temp_rep_$NombreRepositorio WHERE IdDirectory = $IdDirectory";
         
        $Files=$BD->ConsultaSelect($DataBaseName, $QueryGetFiles);                
        
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
         $CadenaDeleteFiles="DELETE FROM $NombreRepositorio WHERE IdDirectory=$IdDirectory";
         $InsertToTrash= "INSERT INTO temp_rep_$NombreRepositorio (IdUsuario, NombreUsuario, ". trim($CadenaCampos_,',').") VALUES ";
         
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
             $CadenaValores.="($IdUsuario, '$NombreUsuario', $IdFile,$IdDirectory,$IdEmpresa,";
             for($cont=0; $cont<count($ArrayStructureDefault); $cont++)
            {             
                $CampoDefault = $ArrayStructureDefault[$cont]['name'];
                $type = $ArrayStructureDefault[$cont]['type'];
                $value=$Files['ArrayDatos'][$ContFile][$CampoDefault];
                
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
            
            sleep(1);
         }
         
         $CadenaValores=trim($CadenaValores,' ,');
         
         $InsertToTrash.=$CadenaValores;
         
//         echo "$UpdateGlobal".PHP_EOL.PHP_EOL;
         
         if(($ResultadoInsertToTrash=$BD->ConsultaInsert($DataBaseName, $InsertToTrash))!=1){echo "Error al trasladar documentos a la papelera los documentos del directorio $IdDirectory. MySQL: $ResultadoInsertToTrash. ".PHP_EOL.$InsertToTrash; return 0;}
         
         if(($ResultadoDeleteFiles=$BD->ConsultaQuery($DataBaseName, $CadenaDeleteFiles))!=1)
         {     
             echo "Error al eliminar los documentos de $NombreRepositorio. MySQL: $ResultadoDeleteFiles.".PHP_EOL.$CadenaDeleteFiles.PHP_EOL;             
             $BD->ConsultaQuery($DataBaseName, $DeleteFilesFromTrash);
             return 0;      
         }          
         
        if(($ResultUpdateGlobal = $BD->ConsultaQuery($DataBaseName, $UpdateGlobal))!=1)
        {
            echo "Error al hacer Update en Global. ".$ResultUpdateGlobal.PHP_EOL.$UpdateGlobal.PHP_EOL;
            $ReturnValuesToRepository= "INSERT INTO $NombreRepositorio (IdUsuario, NombreUsuario, ". trim($CadenaCampos_,',').") VALUES $CadenaValores";
            $BD->ConsultaInsert($DataBaseName, $ReturnValuesToRepository);
            $BD->ConsultaQuery($DataBaseName, $DeleteFilesFromTrash);
            return 0;
        }                      
                          
        return 1; 
    }   
    
    /*--------------------------------------------------------------------------
     *  Quita el directorio de la tabla de directorios y la mueve a la papelera     
     */
    private function MoveDirectoryToTrash($DataBaseName,$NombreRepositorio,$IdDirectory,$IdParent, $IdUsuario, $NombreUsuario, $FlagFather ,$title,$Path)
    {
        $BD= new DataBase();
               
        $InsertToTrash='INSERT INTO temp_dir_'.$NombreRepositorio." (IdDirectory, parent_id, FlagFather, title, path, IdUsuario, NombreUsuario) VALUES ($IdDirectory, $IdParent, $FlagFather , '$title', '$Path', $IdUsuario, '$NombreUsuario')";
        
        $ResultadoInsert=$BD->ConsultaInsert($DataBaseName, $InsertToTrash);
        
        if($ResultadoInsert!=1){echo "Error al insertar en el directorio temporal. Consulta completa $InsertToTrash. MySQL: $ResultadoInsert".PHP_EOL; return 0;}
        
        $Delete="DELETE FROM dir_$NombreRepositorio WHERE IdDirectory=$IdDirectory";
        
        $ResultadoDelete= $BD->ConsultaQuery($DataBaseName, $Delete);
        
        if($ResultadoDelete!=1)
        {
            echo "Ocurrió un error al eliminar el directorio. Consulta completa: $Delete. MySQL: $ResultadoDelete".PHP_EOL;
            
            /* Se elimina de la papelera */
            $DeleteFromTrash="DELETE FROM temp_dir_$NombreRepositorio WHERE IdDirectory = $IdDirectory";
            $ResulDeleteFromTrash = $BD->ConsultaQuery($DataBaseName, $DeleteFromTrash);
            
            if($ResulDeleteFromTrash!=1){echo "Error al eliminar de la papelera. Consulta completa $DeleteFromTrash. MySQL: $ResulDeleteFromTrash".PHP_EOL;}
            return 0;
        }
        
        sleep(1);
        
        return 1;
    }
    
    /*--------------------------------------------------------------------------------------------------------------------------------------
     * Descripción: 
     *          Registro de directorios eliminidos.
     *-------------------------------------------------------------------------------------------------------------------------------------*/
    
    private function RegisterDirectoryAsDeleted($Path,$IdDrectory,$title)
    {        
        $config=  fopen(dirname($Path)."/Deleted_".  basename($Path), "a+");
        /* Se reescribe la sección [DeleteDirectory]. Consultar la descripción de esta función. */
        fwrite($config, "$IdDrectory=$title".PHP_EOL);
        fclose($config);
    }
    
    /*--------------------------------------------------------------------------
     * Se comprueba si ya se había eliminado el documento
     *  return: 
     *              1 => Ya se elimino el directorio
     *              2 => No se ha eliminado el directorio
     *------------------------------------------------------------------------*/
    
    private function CheckIfWasRemoved($Path, $IdDirectory)
    {
        if(!file_exists(dirname($Path)."/Deleted_".  basename($Path))){return 0;}
        
        if(!($DirectoriesDeleted=parse_ini_file (dirname($Path)."/Deleted_".  basename($Path),false))){echo "No pudo abrirse el archivo de registro de directorios removidos Deleted_".  basename($Path).PHP_EOL; return 0;}
           
        $Registered=isset($DirectoriesDeleted[$IdDirectory]);
        if(!$Registered){return 0;}else {return 1;}
    }
    
}

$DeleteDirectory=new ServiceDeleteDirectory();