<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ContentManagement
 *
 * @author daniel
 */
$RoutFile = dirname(getcwd());        

require_once 'DataBase.php';
require_once 'XML.php';
require_once 'DesignerForms.php';
require_once 'Tree.php';
require_once '../apis/pclzip/pclzip.lib.php';
require_once 'Log.php';
require_once 'Catalog.php';
require_once 'Notes.php';

class ContentManagement {
    private $db;
    public function __construct() {
        $this->db = new DataBase();
    }
    public function ajax()
    {   
        if(filter_input(INPUT_POST, "opcion")!=NULL and filter_input(INPUT_POST, "opcion")!=FALSE){
            
            $idSession = Session::getIdSession();
            
            if($idSession == null)
                return XML::XMLReponse ("Error", 0, "ContentManagement::No existe una sesión activa, por favor vuelva a iniciar sesión");

                $userData = Session::getSessionParameters();
                
                switch (filter_input(INPUT_GET, "opcion"))
                {
                    case 'DownloadZip':$this->DownloadZip(); break;
                }
                
                switch (filter_input(INPUT_POST, "opcion")) {

                case 'getListEmpresas': $this->getListEmpresas(); break;
                case 'getListRepositorios': $this->getListRepositorios(); break;
                case 'UploadFile': $this->UploadFile(); break;             
                case 'UploadMetadatas':$this->UploadMetadatas(); break;
                case 'getCatalogos':$this->getCatalogos();break;
                case 'EngineSearch':$this->EngineSearch($userData);break;
                case 'GetFiles':$this->GetFiles();break;
                case 'FileEdit':$this->FileEdit($userData);break;
                case 'GetDetalle':$this->GetDetalle($userData);break;
                case 'DetailModify':$this->DetailModify($userData);break;
                case 'CopyFile':$this->CopyFile($userData); break;
                case 'CutFile':$this->CutFile($userData); break;
                case 'DeleteFile':$this->DeleteFile($userData); break;                                                  
            }
        }
    }

    
    
    /****************************************************************************
     * Los archivos eliminados de un repositorio se envian a la tabla temp_nombreRepositorio
     */
    private function DeleteFile($userData)
    {        
        $designer = new DesignerForms();
        $Catalog = new Catalog();
        
        $DataBaseName = $userData['dataBaseName'];
        $NombreRepositorio=  filter_input(INPUT_POST, "NombreRepositorio");
        $IdRepositorio=  filter_input(INPUT_POST, "IdRepositorio");
        $IdUsuario = $userData['idUser'];
        $NombreUsuario = $userData['userName'];
        $IdFile=filter_input(INPUT_POST, "IdFile");
//        $NombreArchivo=  filter_input(INPUT_POST, "NombreArchivo");
//        $RutaArchivo=  filter_input(INPUT_POST, "RutaArchivo");   /* Archivo que será copiado */
        $IdDirectory=  filter_input(INPUT_POST, "IdDirectory");
        $IdEmpresa=  filter_input(INPUT_POST, "IdEmpresa");
        
        if (!isset($_SESSION['permissions'][md5($IdRepositorio)]['b6d767d2f8ed5d21a44b0e5886680cb9']))
            return XML::XMLReponse ("Error", 0, 'No tiene permiso de realizar esta acción.');
        
        if(!file_exists("../Configuracion/$DataBaseName.ini"))
            return XML::XMLReponse("Error", 0,"<p>No existe el archivo de configuración estructural.</p>");
           
        
        if(!($EstructuraConfig = parse_ini_file ("../Configuracion/$DataBaseName.ini",true)))
                return XML::XMLReponse ("Error", 0, "<b>Error</b> al abrir el archivo de configuración del repositorio <b>$NombreRepositorio</b><br><br>$EstructuraConfig");
        
        $ArrayStructureDefault=$designer->ReturnStructureDefault($NombreRepositorio,$EstructuraConfig[$NombreRepositorio]);        
        
        $Full='';
        
        $GetFile='SELECT *FROM '.$NombreRepositorio." WHERE IdRepositorio=$IdFile";
        
        $File = $this->db->ConsultaSelect($DataBaseName, $GetFile);
        
        $delete='INSERT INTO temp_rep_'.$NombreRepositorio." (IdRepositorio,";
        
        $cadena_campos='';
        $cadena_valores=$IdFile.",";
        
        /* Estructura del repositorio */
        $EstructuraRepositorio=$designer->ReturnStructure($NombreRepositorio,$EstructuraConfig[$NombreRepositorio]);
        
        $catalogos = $Catalog->getFilteredArrayCatalogsDetail($DataBaseName, $IdRepositorio);   
        
        if(!is_array($catalogos))
            return XML::XMLReponse ("Error", 0, "Error al consultar catálogos del repositorio <b>$NombreRepositorio</b>");
        
        /* Campos del repositorio */
         for($cont=0; $cont<count($EstructuraRepositorio); $cont++)       
         {
             $CampoRepositorio=$EstructuraRepositorio[$cont]['name'];
             $type=$EstructuraRepositorio[$cont]['type'];
             $cadena_campos.=" ".$CampoRepositorio.",";

            if(strcasecmp($type,"int")==0 or strcasecmp($type,"integer")==0 or strcasecmp($type,"float")==0 or strcasecmp($type,"double")==0) /* Si detecta un tipo numerico */
            {                 
                if(!(is_numeric($File['ArrayDatos'][0][$CampoRepositorio])))                
                    $cadena_valores.=" 0,";                   
                else
                    $cadena_valores.=$File['ArrayDatos'][0][$CampoRepositorio].",";
            }
            else    /* Demás tipos de datos llevan ' ' */
                $cadena_valores.="'".$File['ArrayDatos'][0][$CampoRepositorio]."'".",";
            
            $Full.=$File['ArrayDatos'][0][$CampoRepositorio]." ";            
         }   
         /* Campos de Default */

         for($cont=0; $cont<count($ArrayStructureDefault); $cont++)
         {
             
             $CampoDefault=$ArrayStructureDefault[$cont]['name'];
             $type=$ArrayStructureDefault[$cont]['type'];
             $value=$File['ArrayDatos'][0][$CampoDefault];

             if($CampoDefault=='Full'){$Full = $value; continue;}
             
//             $Full.=$value." , ";
             $cadena_campos.=$CampoDefault.",";
                          
            if(strcasecmp($type,"int")==0 or strcasecmp($type,"integer")==0 or strcasecmp($type,"float")==0 or strcasecmp($type,"double")==0) /* Si detecta un tipo numerico */
            {                
                if(!(is_numeric($value)))             
                    $cadena_valores.="0,";                   
                else
                    $cadena_valores.=$value.",";
            }
            else    /* Demás tipos de datos llevan ' ' */
                $cadena_valores.="'".$value."'".",";         
         }                              
         
         /*  Nombres de Cada catálogo */
        for($cont=0; $cont<count($catalogos);$cont++)
        {
            $NombreCatalogo=$catalogos[$cont]['NombreCatalogo'];
            $cadena_campos.=$NombreCatalogo.",";
            $cadena_valores.=$File['ArrayDatos'][0][$NombreCatalogo].",";            
//            $Full.=$File['ArrayDatos'][0][$NombreCatalogo]." ";
        }                     
            /* Se copia el archivo a la ruta especificada por el usuario */
            $cadena_campos=  trim($cadena_campos,',');
            $cadena_valores=  trim($cadena_valores,',');

            $delete=$delete.$cadena_campos.",IdDirectory, IdEmpresa,Full) VALUES ($cadena_valores,$IdDirectory,$IdEmpresa,'$Full')";            
            $DeleteFromRepository="DELETE FROM $NombreRepositorio WHERE IdRepositorio=$IdFile";
        
            
            if(($ResultTempDelete = $this->db->ConsultaInsert($DataBaseName, $delete))==1)/* Se copia delete en tabla temporal */
            {                 
                if(($ResultDelete = $this->db->ConsultaQuery($DataBaseName, $DeleteFromRepository))==1)/* Se elimina del repositorio */
                {    
                    $DeleteFromGlobal = "UPDATE RepositorioGlobal SET Full = '' WHERE IdFile = $IdFile AND IdRepositorio = $IdRepositorio";
                    if(($ResultDeleteFromGlobal = $this->db->ConsultaQuery($DataBaseName, $DeleteFromGlobal)==1))
                    {
                        Log::WriteEvent("25", $IdUsuario, $NombreUsuario, $File['ArrayDatos'][0]["NombreArchivo"], $DataBaseName);
                        XML::XMLReponse("DeleteFile", 1, "Archivo Eliminado con éxito.");

                    }                    
                }
                else
                {
                    $this->db->ConsultaQuery($DataBaseName, "DELETE FROM temp_rep_$NombreRepositorio WHERE IdRepositorio = $IdFile");
                    return XML::XMLReponse("Error", 0, "Error al eliminar el archivo. ".$ResultDelete);
                }
            }  
            else
                return XML::XMLReponse("Error", 0, "Error al eliminar el archivo. ".$ResultTempDelete);
    }
    
    private function CutFile($userData)
    {
                      
        $XML=new XML();
        $BD= new DataBase();
        $Log = new Log();
        $designer = new DesignerForms();
        $Catalog = new Catalog();
        
        $DataBaseName =  $userData['dataBaseName'];
        $NombreRepositorio =  filter_input(INPUT_POST, "NombreRepositorio");
        $IdRepositorio =  filter_input(INPUT_POST, "IdRepositorio");
        $IdUsuario = $userData['idUser'];
        $NombreUsuario = $userData['userName'];
        $IdFile = filter_input(INPUT_POST, "IdFile");
        $Path = filter_input(INPUT_POST, "Path");
        $NombreArchivo =  filter_input(INPUT_POST, "NombreArchivo");
        $RutaArchivo =  filter_input(INPUT_POST, "RutaArchivo");   /* Archivo que será copiado */
        $IdDirectory =  filter_input(INPUT_POST, "IdDirectory");
        $NombreDirectorio = filter_input(INPUT_POST, "NombreDirectorio");
        $IdDirectorioOrigen = filter_input(INPUT_POST, "IdDirectorioOrigen");
//        $IdEmpresa=  filter_input(INPUT_POST, "IdEmpresa");
        $PathPrincipal = "Estructuras/".$DataBaseName."/";
        $RutaDestino = '../'.$PathPrincipal.$NombreRepositorio.$Path."/";
        $Full = '';   /* Campo FullText */
        $InnerJoin = '';
        $FechaIngreso = 0;
        $TipoArchivo = 0;
         /********************* Estructura del repositorio********************** */
        
        if(!file_exists("../Configuracion/$DataBaseName.ini"))
            return XML::XMLReponseResponseXML("Error", 0,"<p>No existe el archivo de configuración estructural.</p>");
        
        $EstructuraConfig=parse_ini_file ("../Configuracion/$DataBaseName.ini",true);
        
        $ArrayStructureDefault=$designer->ReturnStructureDefault($NombreRepositorio,$EstructuraConfig[$NombreRepositorio]);
        
        $EstructuraRepositorio=$designer->ReturnStructure($NombreRepositorio,$EstructuraConfig[$NombreRepositorio]);
                        
        $query='SELECT ';
        
        
        /***********************************************************************/
        
        /* Si existe se renombra el archivo nuevo  */
            $t=1; 
            $renombrado=0;
        while(file_exists($RutaDestino.$NombreArchivo)){ 
             $archivo = $NombreArchivo; 
             $archivo=substr($archivo,0,strpos($archivo,"."))."_$t".strstr($archivo,".");  
             $NombreArchivo=$archivo;
             $t++; 
             $renombrado=1;
            } 
            
            
        /* Los datos que pueden cambiar de un archivo copiado son
         * IdDirectory
         * IdRepositorio (Ya que es consecutivo)
         * Path  
         
         * Se obtiene el archivo de configuración de cada sección:
         *      Campos Default Repositorio
         *      Campos de Usuario Repositorio
         *      Catálogos (Sí existen)
         * 
         * 
         */
             
        $catalogos = $Catalog->getArrayCatalogsNames($DataBaseName, $IdRepositorio);
        
        if(!is_array($catalogos))
            return XML::XMLReponse ("Error", 0, "<b>Error</b> al obtener el listado de catálogos del repositorio <b>$NombreRepositorio</b><br>Detalles:<br><br>$catalogos");
        
        /* Estructura de cada catálogo */       
        for($cont=0; $cont<count($catalogos);$cont++)
        {
            $EstructuraCatalogos[$catalogos[$cont]['NombreCatalogo']] = $designer->ReturnStructure($catalogos[$cont]['NombreCatalogo'], $EstructuraConfig[$NombreRepositorio."_".$catalogos[$cont]['NombreCatalogo']]);
        }
                
        /* Se genera el Query que realiza la consulta con el detalle del archivo a traves de las estructuras
         * del repositorio y de los catálogos */
                        
         /* Campos de default en repositorio */
         for($cont=0; $cont<count($ArrayStructureDefault); $cont++)
         {             
             $CampoDefault=$ArrayStructureDefault[$cont]['name'];
             $query.=$NombreRepositorio.".".$CampoDefault.",";
         }
         
         /* Campos del repositorio */
         for($cont=0; $cont<count($EstructuraRepositorio); $cont++)       
         {
             $query.= $NombreRepositorio.".".$EstructuraRepositorio[$cont]['name'].",";           
         }   
         

         /* Se concatenan los campos de los catálogos */
         for ($cont=0; $cont<count($catalogos); $cont++)
         {
             $catalogo=$catalogos[$cont]['NombreCatalogo'];
             $query.= $NombreRepositorio.".".$catalogo.",";
             for($aux=1; $aux<count($EstructuraCatalogos[$catalogo]); $aux++)  /* 1 es el tipo */
             {
                 $NombreCatalogo=$catalogos[$cont]['NombreCatalogo'];
                 $query.= $NombreCatalogo.".".$EstructuraCatalogos[$catalogo][$aux]['name'].",";
             }                   
             /* substr obtiene el prrefijo de cada tabla pe. Rep.ClaveEmpresa, Rep.NombreArchivo, etct */
             $InnerJoin.=" LEFT JOIN $NombreRepositorio"."_".$NombreCatalogo." ".$NombreCatalogo ." ON ".$NombreRepositorio.".".$NombreCatalogo."=".  $NombreCatalogo.".Id".$NombreCatalogo;
         }       
                           
         $query=trim($query,',');
         $InnerJoin=trim($InnerJoin,',');         
         $query.=' FROM '.$NombreRepositorio." ".$NombreRepositorio;         
         $query= $query.$InnerJoin." WHERE ".$NombreRepositorio.".IdRepositorio=$IdFile";
         
        $File = $this->doDetailQuery($DataBaseName, $query);
        
        if(!is_array($File))
            return XML::XMLReponse ("Error", 0, "<b>Error</b> al obtener metadatos del repositorio <b>$NombreRepositorio</b><br>Detalles:<br><br>$File");
                      
        $cadena_campos='';
        $cadena_valores='';
        
        /************** - Campos de Default del Repositorio - ******************/
        
        $AuxCont=0;
         for($cont=0; $cont<count($ArrayStructureDefault); $cont++)
         {             
             $CampoDefault=$ArrayStructureDefault[$cont]['name'];
             $type=$ArrayStructureDefault[$cont]['type'];
             $value=$File[0][$AuxCont];
             
//             echo "<p>$CampoDefault = ".$File[0][$AuxCont]." </p>";
             
            $AuxCont++;
            if(strcasecmp($CampoDefault,'Full')==0){continue;}
            if(strcasecmp($CampoDefault, 'TipoArchivo')==0){$TipoArchivo=$value;}            
//            if(strcasecmp($CampoDefault,'NombreArchivo')==0){$Full.=$NombreArchivo." , "; continue;}
            if(strcasecmp($CampoDefault,'FechaIngreso')==0){$FechaIngreso = $value;}
                         
            $cadena_campos.=$CampoDefault.",";
             
            if(strcasecmp($CampoDefault,'RutaArchivo')==0){$cadena_valores.="'$RutaDestino$NombreArchivo',"; continue;}
        
            if(strcasecmp($type,"int")==0 or strcasecmp($type,"integer")==0 or strcasecmp($type,"float")==0 or strcasecmp($type,"double")==0) /* Si detecta un tipo numerico */
            {     
                if(!(is_numeric($value)))             
                    $cadena_valores.="0,";                   
                else
                {
                    $cadena_valores.=$value.",";
                    
                    if(strcasecmp($value,0)!=0)  /* Quita campos por default que están en 0 (topografia, resumen extrac, autor, clasificacion, gestion, etc) */
                        $Full.=$value." , ";                        
                }

            }
            else    /* Demás tipos de datos llevan ' ' */
            {
                $cadena_valores.="'".$value."'".",";
                
                if(strcasecmp($value,0)!=0)  /* Quita campos por default que están en 0 (topografia, resumen extrac, autor, clasificacion, gestion, etc) */
                        $Full.=$value." , ";
            }            
         }
         
         $Full=  trim($Full,' , ');         
                
         
        /**********************Campos del repositorio****************************/
         
         for($cont=0; $cont<count($EstructuraRepositorio); $cont++)       
         {
             $CampoRepositorio=$EstructuraRepositorio[$cont]['name'];
             
//             echo "<p>$CampoRepositorio = ".$File[0][$AuxCont]." </p>";
             
             $type=$EstructuraRepositorio[$cont]['type'];
             $cadena_campos.=" ".$CampoRepositorio.",";

            if(strcasecmp($type,"int")==0 or strcasecmp($type,"integer")==0 or strcasecmp($type,"float")==0 or strcasecmp($type,"double")==0) /* Si detecta un tipo numerico */
            {                 
                if(!(is_numeric($File[0][$AuxCont])))
                    $cadena_valores.=" 0,";                   
                else
                    $cadena_valores.=$File[0][$AuxCont].",";

            }
            else    /* Demás tipos de datos llevan ' ' */
                $cadena_valores.="'".$File[0][$AuxCont]."'".",";
            
            $Full.=" , ".$File[0][$AuxCont]." ";      
            
            $AuxCont++;
         }   
         
         $Full=  trim($Full,' , ');
         
         /*  Match con Campos de Catálogo  */
        for($cont=0; $cont<count($catalogos);$cont++)
        {
//            $IdCatalogo=$File[0][$AuxCont];
                        
            $NombreCatalogo=$catalogos[$cont]['NombreCatalogo'];
            $cadena_campos.=$NombreCatalogo.",";
            $cadena_valores.=$File[0][$AuxCont].",";   
            $AuxCont++;
            
            for($aux=1; $aux<count($EstructuraCatalogos[$NombreCatalogo]); $aux++)  /* 1 es el tipo */
             {
                 $value=$File[0][$AuxCont];
                 $Full.=" ".$value." , ";
                 $AuxCont++;
             }     
             
        }
        
        $Full=  trim($Full,' , ');

        $pathinfo = pathinfo($RutaDestino.$NombreArchivo);
        $XmlDestino = $RutaDestino.$pathinfo['filename'].".xml";
        $CapitalXmlDestino = $RutaDestino.$pathinfo['filename'].".XML";
        $XmlOrigen = dirname($RutaArchivo)."/".$pathinfo['filename'].".xml";
        $XMLOrigen = dirname($RutaArchivo)."/".$pathinfo['filename'].".XML"; 
        
        
        $UpdateRuta='UPDATE '.$NombreRepositorio." SET RutaArchivo='$RutaDestino$NombreArchivo', IdDirectory=$IdDirectory, NombreArchivo='$NombreArchivo' WHERE IdRepositorio=$IdFile";
        if(file_exists($RutaArchivo))
        {
            if(rename($RutaArchivo, $RutaDestino.$NombreArchivo))
            {          
                if(file_exists($XmlOrigen))
                    rename($XmlOrigen, $XmlDestino);
                if(file_exists($XMLOrigen))
                    rename($XMLOrigen, $CapitalXmlDestino);
                
                
                $ResultUpdate=$BD->ConsultaQuery($DataBaseName, $UpdateRuta);
                if($ResultUpdate==1)
                {
                    $Mensaje='';
                    if($renombrado==1)
                       $Mensaje= "Archivo Cortado y Renombrado a $NombreArchivo";
                    else
                        $Mensaje= "Archivo $NombreArchivo Cortado Correctamente.";
                    
                    $UpdateRepositorioGlobal = "UPDATE RepositorioGlobal SET RutaArchivo='$RutaDestino$NombreArchivo', IdDirectory=$IdDirectory, NombreArchivo='$NombreArchivo' WHERE IdFile = $IdFile AND IdRepositorio = $IdRepositorio ";
                    
                    if(($ResultUpdateGlobal=$BD->ConsultaQuery($DataBaseName, $UpdateRepositorioGlobal))==1)
                    {                                               
                        /*  Devolución de repuesta en XML */
                        $doc  = new DOMDocument('1.0','utf-8');
                        $doc->formatOutput = true;
                        $root = $doc->createElement("Paste");
                        $doc->appendChild($root); 
                        $XmlNombreArchivo=$doc->createElement("NombreArchivo",$NombreArchivo);
                        $root->appendChild($XmlNombreArchivo);
                        $XmlFechaIngreso=$doc->createElement("FechaIngreso",$FechaIngreso);
                        $root->appendChild($XmlFechaIngreso);
                        $XmlTipoArchivo=$doc->createElement("TipoArchivo",$TipoArchivo);
                        $root->appendChild($XmlTipoArchivo);
                        $XmlRutaArchivo=$doc->createElement("RutaArchivo",$RutaDestino.$NombreArchivo);
                        $root->appendChild($XmlRutaArchivo);
                        $XmlFull=$doc->createElement("Full",$Full);
                        $root->appendChild($XmlFull);
                        $XmlIdRepositorio=$doc->createElement("IdRepositorio",$IdFile);
                        $root->appendChild($XmlIdRepositorio);
                        $XmlEstado=$doc->createElement("Estado",1);
                        $root->appendChild($XmlEstado);
                        $XmlMensaje=$doc->createElement("Mensaje",$Mensaje);
                        $root->appendChild($XmlMensaje);
                        header ("Content-Type:text/xml");
                        echo $doc->saveXML();

                        $Log ->Write("34", $IdUsuario, $NombreUsuario, " '$NombreArchivo' al directorio '$NombreDirectorio'", $DataBaseName);
                    }
                }
                else
                {
                    rename($RutaDestino.$NombreArchivo,$RutaArchivo);/* Se regresa el documento a la ruta original */
                    $CancelUpdate = "UPDATE $NombreRepositorio SET RutaArchivo='$RutaArchivo', IdDirectory=$IdDirectorioOrigen, NombreArchivo='".  basename($RutaArchivo) ."' WHERE IdRepositorio=$IdFile";
                    $BD->ConsultaQuery($DataBaseName, $CancelUpdate);
                    $XML->ResponseXML("Error", 0, "Error al registrar el movimiento. ".$ResultUpdateGlobal);
                }
            }
            else
                $XML->ResponseXML("Error", 0, "No fué posible mover el archivo a la ruta seleccionada.");
        }
        else
            $XML->ResponseXML("Error", 0, "No existe la ruta destino.");

    }
    private function CopyFile($userData)
    {
                     
        $XML=new XML();
        $BD= new DataBase();
        $designer=new DesignerForms();
        $Log = new Log();
        $Catalog = new Catalog();
        
        $DataBaseName = $userData['dataBaseName'];
        $NombreRepositorio = filter_input(INPUT_POST, "NombreRepositorio");
        $IdRepositorio=  filter_input(INPUT_POST, "IdRepositorio");
        $IdUsuario = $userData['idUser'];
        $NombreUsuario = $userData['userName'];
        $IdFile = filter_input(INPUT_POST, "IdFile");
        $Path = filter_input(INPUT_POST, "Path");
        $DirectorioOrigen = filter_input(INPUT_POST, "DirectorioOrigen");
        $DirectorioDestino = filter_input(INPUT_POST, "DirectorioDestino");
        $NombreEmpresa = filter_input(INPUT_POST,"NombreEmpresa");
        $RutaArchivo = filter_input(INPUT_POST, "RutaArchivo");   /* Archivo que será copiado */
        $IdDirectory = filter_input(INPUT_POST, "IdDirectory");
        $IdEmpresa = filter_input(INPUT_POST, "IdEmpresa");
        $PathPrincipal="Estructuras/".$DataBaseName."/";
                
        $EstructuraCatalogos=array();
        
        $InnerJoin='';
        $copy='';   /* Variable que almacena la consulta de insert  */
        
        /*----------------Campos que se devuelven al cliente-------------------*/
        $NombreArchivo=  filter_input(INPUT_POST, "NombreArchivo");
        $RutaDestino='../'.$PathPrincipal.$NombreRepositorio.$Path."/";
        $Full='';   /* Campo FullText */
        $FechaIngreso='';
        $TipoArchivo='';
        
        /********************* Estructura del repositorio********************** */
        
        if(!file_exists("../Configuracion/$DataBaseName.ini"))
            return XML::XMLReponse("Error", 0,"<p>No existe el archivo de configuración estructural.</p>");
        
        $EstructuraConfig = parse_ini_file ("../Configuracion/$DataBaseName.ini",true); 
        
        $ArrayStructureDefault=$designer->ReturnStructureDefault($NombreRepositorio,$EstructuraConfig[$NombreRepositorio]);
        
        $EstructuraRepositorio=$designer->ReturnStructure($NombreRepositorio,$EstructuraConfig[$NombreRepositorio]);
                        
        $query='SELECT ';
        
        
        /***********************************************************************/
        
        /* Si existe se renombra el archivo nuevo  */
            $t=1; 
            $renombrado=0;
        while(file_exists($RutaDestino.$NombreArchivo)){ 
             $archivo = $NombreArchivo; 
             $archivo=substr($archivo,0,strpos($archivo,"."))."_$t".strstr($archivo,".");  
             $NombreArchivo=$archivo;
             $t++; 
             $renombrado=1;
            } 
            
            
        /* Los datos que pueden cambiar de un archivo copiado son:
         *      IdDirectory
         *      IdRepositorio (Ya que es consecutivo)
         *      Path  
         
         * Se obtiene el archivo de configuración de cada sección:
         *      Campos Default Repositorio
         *      Campos de Usuario Repositorio
         *      Catálogos (Sí existen)
         */
             
         
        $catalogos = $Catalog->getArrayCatalogsNames($DataBaseName, $IdRepositorio);
        
        if(!is_array($catalogos))
            return XML::XMLReponse ("Error", 0, "Error al consultar los catálogos del repositorio <b>$NombreRepositorio</b><br>Detalles:<br><br>$catalogos");
           
       
        /* Estructura de cada catálogo */       
        for($cont=0; $cont<count($catalogos);$cont++)
        {
            $EstructuraCatalogos[$catalogos[$cont]['NombreCatalogo']]=$designer->ReturnStructure($catalogos[$cont]['NombreCatalogo'], $EstructuraConfig[$NombreRepositorio."_".$catalogos[$cont]['NombreCatalogo']]);
        }
                
        /* Se genera el Query que realiza la consulta con el detalle del archivo a traves de las estructuras
         * del repositorio y de los catálogos */
                        
         /* Campos de default en repositorio */
         for($cont=0; $cont<count($ArrayStructureDefault); $cont++)
         {             
             $CampoDefault=$ArrayStructureDefault[$cont]['name'];
             $query.=$NombreRepositorio.".".$CampoDefault.",";
         }
         
         /* Campos del repositorio */
         for($cont=0; $cont<count($EstructuraRepositorio); $cont++)       
         {
             $query.= $NombreRepositorio.".".$EstructuraRepositorio[$cont]['name'].",";           
         }   
         

         /* Se concatenan los campos de los catálogos */
         for ($cont=0; $cont<count($catalogos); $cont++)
         {
             $catalogo=$catalogos[$cont]['NombreCatalogo'];
             $query.= $NombreRepositorio.".".$catalogo.",";
             for($aux=1; $aux<count($EstructuraCatalogos[$catalogo]); $aux++)  /* 1 es el tipo */
             {
                 $NombreCatalogo=$catalogos[$cont]['NombreCatalogo'];
                 $query.= $NombreCatalogo.".".$EstructuraCatalogos[$catalogo][$aux]['name'].",";
             }                   
             /* substr obtiene el prrefijo de cada tabla pe. Rep.ClaveEmpresa, Rep.NombreArchivo, etct */
             $InnerJoin.=" LEFT JOIN $NombreRepositorio"."_".$NombreCatalogo." ".$NombreCatalogo ." ON ".$NombreRepositorio.".".$NombreCatalogo."=".  $NombreCatalogo.".Id".$NombreCatalogo;
         }       
                           
         $query=trim($query,',');
         $InnerJoin=trim($InnerJoin,',');         
         $query.=' FROM '.$NombreRepositorio." ".$NombreRepositorio;         
         $query= $query.$InnerJoin." WHERE ".$NombreRepositorio.".IdRepositorio=$IdFile";
         
        $File = $this->doDetailQuery($DataBaseName, $query);
        
        if(!is_array($File))
            return XML::XMLReponse ("Error", 0, "<b>Error</b> al consultar metadatos. <br><br>Detalles:<br><br>$File");

        $copy='INSERT INTO '.$NombreRepositorio." (";
        $cadena_campos='';
        $cadena_valores='';
        
        /************** - Campos de Default del Repositorio - ******************/
        
        $AuxCont=0;
         for($cont=0; $cont<count($ArrayStructureDefault); $cont++)
         {             
             $CampoDefault=$ArrayStructureDefault[$cont]['name'];
             $type=$ArrayStructureDefault[$cont]['type'];
             $value=$File[0][$AuxCont];
             
//             echo "<p>$CampoDefault = ".$File[0][$AuxCont]." </p>";
             
            $AuxCont++;
            if(strcasecmp($CampoDefault, 'TipoArchivo')==0){$TipoArchivo=$value;}
            if(strcasecmp($CampoDefault,'Full')==0){continue;}
            if(strcasecmp($CampoDefault,'NombreArchivo')==0){$Full.=$NombreArchivo." , "; continue;}
            if(strcasecmp($CampoDefault,'FechaIngreso')==0){$FechaIngreso = $value;}
                         
            $cadena_campos.=$CampoDefault.",";
             
            if(strcasecmp($CampoDefault,'RutaArchivo')==0){$cadena_valores.="'$RutaDestino$NombreArchivo',"; continue;}
        
            if(strcasecmp($type,"int")==0 or strcasecmp($type,"integer")==0 or strcasecmp($type,"float")==0 or strcasecmp($type,"double")==0) /* Si detecta un tipo numerico */
            {     
                if(!(is_numeric($value)))             
                    $cadena_valores.="0,";                   
                else
                {
                    $cadena_valores.=$value.",";
                    
                    if(strcasecmp($value,0)!=0)  /* Quita campos por default que están en 0 (topografia, resumen extrac, autor, clasificacion, gestion, etc) */
                        $Full.=$value." , ";                        
                }

            }
            else    /* Demás tipos de datos llevan ' ' */
            {
                $cadena_valores.="'".$value."'".",";
                
                if(strcasecmp($value,0)!=0)  /* Quita campos por default que están en 0 (topografia, resumen extrac, autor, clasificacion, gestion, etc) */
                        $Full.=$value." , ";
            }            
         }
         
         $Full=  trim($Full,' , ');
                         
        /**********************Campos del repositorio****************************/
         
         for($cont=0; $cont<count($EstructuraRepositorio); $cont++)       
         {
             $CampoRepositorio=$EstructuraRepositorio[$cont]['name'];
             
//             echo "<p>$CampoRepositorio = ".$File[0][$AuxCont]." </p>";
             
             $type=$EstructuraRepositorio[$cont]['type'];
             $cadena_campos.=" ".$CampoRepositorio.",";

            if(strcasecmp($type,"int")==0 or strcasecmp($type,"float")==0 || strcasecmp($type,"integer")==0) /* Si detecta un tipo numerico */
            {         
                
                if(!(is_numeric($File[0][$AuxCont])))
                    $cadena_valores.=" 0,";                   
                else
                    $cadena_valores.=$File[0][$AuxCont].",";

            }
            else    /* Demás tipos de datos llevan ' ' */
                $cadena_valores.="'".$File[0][$AuxCont]."'".",";
            
            $Full.=" , ".$File[0][$AuxCont]." ";      
            
            $AuxCont++;
         }   
         
         $Full=  trim($Full,' , ');
         
         /*  Match con Campos de Catálogo  */
        for($cont=0; $cont<count($catalogos);$cont++)
        {
            $IdCatalogo=$File[0][$AuxCont];
                        
            $NombreCatalogo=$catalogos[$cont]['NombreCatalogo'];
            $cadena_campos.=$NombreCatalogo.",";
            $cadena_valores.=$File[0][$AuxCont].",";   
            $AuxCont++;
            for($aux=1; $aux<count($EstructuraCatalogos[$NombreCatalogo]); $aux++)  /* 1 es el tipo */
             {
                 $value=$File[0][$AuxCont];
                 $Full.=$value.", ";
                 $AuxCont++;
             }     
        }
        
        $Full=  trim($Full,' , ');
                                    
        if(!file_exists($RutaArchivo))
        {
            $XML->ResponseXML("Error", 0, "No existe el archivo de origen");
            return 0;
        }
        
        $pathinfo = pathinfo($RutaDestino.$NombreArchivo);
        $XmlDestino = $RutaDestino.$pathinfo['filename'].".xml";
        $CapitalXmlDestino = $RutaDestino.$pathinfo['filename'].".XML";
        $XmlOrigen = dirname($RutaArchivo)."/".$pathinfo['filename'].".xml";
        $XMLOrigen = dirname($RutaArchivo)."/".$pathinfo['filename'].".XML"; 
        
//        echo "origen $XmlOrigen <br>destino=$XmlDestino <br> $XMLOrigen <br> $CapitalXmlDestino <br>";

        if(file_exists($RutaDestino))
        {                       
            /* Se copia el archivo a la ruta especificada por el usuario */
            $cadena_campos=  trim($cadena_campos,',');
            $cadena_valores=  trim($cadena_valores,',');
            $copy=  trim($copy,',');

            $copy=$copy.$cadena_campos.",NombreArchivo,IdDirectory,IdEmpresa,Full) VALUES ($cadena_valores,'$NombreArchivo',$IdDirectory,$IdEmpresa,'$Full')";
            
            $IdNewFile=$BD->ConsultaInsertReturnId($DataBaseName, $copy);
            
            if(!($IdNewFile>0)){$XML->ResponseXML("Error", 0, "Error de mysql. $IdNewFile"); return;}
            
                     
            if(copy($RutaArchivo, $RutaDestino.$NombreArchivo))
            {                
                if(file_exists($XmlOrigen))
                    copy($XmlOrigen, $XmlDestino);
                if(file_exists($XMLOrigen))
                    copy($XMLOrigen, $CapitalXmlDestino);
                
                $Mensaje='';
                if($renombrado==1)
                   $Mensaje= "Archivo Copiado y Renombrado a $NombreArchivo";
                else
                    $Mensaje= "Archivo Copiado Correctamente.";
                
                $InsertIntoGloabal = "INSERT INTO RepositorioGlobal (IdFile, IdEmpresa, IdRepositorio, IdDirectory, "
                . "NombreEmpresa, NombreRepositorio, NombreArchivo, TipoArchivo, RutaArchivo, UsuarioPublicador, "
                . "FechaIngreso, Full) VALUES "
                . "($IdNewFile, $IdEmpresa, $IdRepositorio, $IdDirectory, '$NombreEmpresa', '$NombreRepositorio',"
                . "'$NombreArchivo', '$TipoArchivo', '$RutaDestino$NombreArchivo', '$NombreUsuario', '$FechaIngreso', '$Full')";
                   
                if(($ResultInsertIntoGlobal = $BD->ConsultaInsertReturnId($DataBaseName, $InsertIntoGloabal))>0)
                {
                    /*  Devolución de repuesta en XML */
                    $doc  = new DOMDocument('1.0','utf-8');
                    $doc->formatOutput = true;
                    $root = $doc->createElement("Paste");
                    $doc->appendChild($root); 
                    $XmlNombreArchivo=$doc->createElement("NombreArchivo",$NombreArchivo);
                    $root->appendChild($XmlNombreArchivo);
                    $XmlFechaIngreso=$doc->createElement("FechaIngreso",$FechaIngreso);
                    $root->appendChild($XmlFechaIngreso);
                    $XmlTipoArchivo=$doc->createElement("TipoArchivo",$TipoArchivo);
                    $root->appendChild($XmlTipoArchivo);
                    $XmlRutaArchivo=$doc->createElement("RutaArchivo",$RutaDestino.$NombreArchivo);
                    $root->appendChild($XmlRutaArchivo);
                    $XmlFull=$doc->createElement("Full",$Full);
                    $root->appendChild($XmlFull);
                    $XmlIdRepositorio=$doc->createElement("IdRepositorio",$IdNewFile);
                    $root->appendChild($XmlIdRepositorio);
                    $XmlEstado=$doc->createElement("Estado",1);
                    $root->appendChild($XmlEstado);
                    $XmlMensaje=$doc->createElement("Mensaje",$Mensaje);
                    $root->appendChild($XmlMensaje);
                    header ("Content-Type:text/xml");
                    echo $doc->saveXML();

                    $Log ->Write("33", $IdUsuario, $NombreUsuario, " '$NombreArchivo' del directorio '$DirectorioOrigen' al '$DirectorioDestino'", $DataBaseName);
                    
                } 
                else
                {
                    $BD->ConsultaQuery($DataBaseName, "DELETE FROM $NombreRepositorio WHERE IdRepositorio = $IdNewFile");
                    if(file_exists($RutaDestino.$NombreArchivo))
                        unlink($RutaDestino.$NombreArchivo);
                    $XML->ResponseXML("Error", 0, "Error al insertar en Global. $ResultInsertIntoGlobal");
                }
            }
            else
            {                
                $BD->ConsultaQuery($DataBaseName, "DELETE FROM $NombreRepositorio WHERE IdRepositorio = $IdNewFile");
                $XML->ResponseXML("Error", 0, "No fué posible copiar el archivo a la ruta seleccionada.");
            }            
        }
        else
        {
            $XML->ResponseXML("Error", 0, "No existe la ruta destino");
            return;
        }                            
    }
    
    private function DetailModify($userData)
    {                       
        $Notes = new Notes();
        
        $DataBaseName = $userData['dataBaseName'];
        $NombreRepositorio = filter_input(INPUT_POST, "NombreRepositorio");
        $IdRepositorio = filter_input(INPUT_POST, "IdRepositorio");
        $NombreUsuario = $userData['userName'];
        $IdUsuario = $userData['idUser'];
        $IdFile = filter_input(INPUT_POST, "IdFile");
        $IdGlobal = filter_input(INPUT_POST, "IdGlobal");
        $XMLResponse = filter_input(INPUT_POST, "XMLResponse");   
        $NombreArchivo = filter_input(INPUT_POST,"NombreArchivo");
        
        if(!isset($_SESSION['permissions'][md5($IdRepositorio)]['3c59dc048e8850243be8079a5c74d079']))
            return XML::XMLReponse('Error', 0, 'Modificar Metadatos del Documento:: No tiene permiso de realizar esta acción');
        
        
        $xml =  simplexml_load_string($XMLResponse);  
        
        /* El IdGlobal cuando se modifica el detalle desde la búsqueda */
        
        $CadenaUpdate="UPDATE $NombreRepositorio SET ";
        $FullText=''; /* Campo devuelto a la vista para insertarlo en la Tabla (Repositorio) */
        $CampoFullText="";     /* Contiene el resumen de todos los campos del repositorio y de los catálogos (Para la búsqueda) */
        $campoFullReturn='';/* Campo FullText que se retorna a la vista de usuario */
        
        foreach ($xml->Detalle as $campo){
            $type=$campo->type;
            $value=$campo->value;
            
            if(strcasecmp($campo->name,"Full")==0)
                    continue;
            
            if(strcasecmp($campo->name,"RutaArchivo")==0)
                    continue;
            
            if(strcasecmp($type,"int")==0 or strcasecmp($type,"integer")==0 or strcasecmp($type,"float")==0 or strcasecmp($type,"double")==0) /* Si detecta un tipo numerico */{
                if(!(is_numeric("$value")))
                    $CadenaUpdate.=$campo->name."=0,";
                else
                    $CadenaUpdate.=$campo->name."=$campo->value,";

            }
            else    /* Demás tipos de datos llevan ' ' */
                $CadenaUpdate.=$campo->name."='".$campo->value."'".",";
            
            if($campo->value!='0'){    /* Se evitan los campos por default que contengan 0 */
                 $CampoFullText.=$value." , ";
                 $campoFullReturn.=$value." , ";
             }  
        }
        
        if(count($xml->Catalogo)>0)    /* Los campos catálogo son tipo INT */
            foreach ($xml->Catalogo as $campo){
                $CadenaUpdate.=$campo->name."=$campo->value,";
                $CampoFullText.=$campo->TextoSelect." , ";
                $campoFullReturn.=$campo->TextoSelect." , ";
            }
        
        $CadenaUpdate=trim($CadenaUpdate,',');
        
        $CampoFullText=  trim($CampoFullText,' , ');
      
        $campoFullReturn=  trim($campoFullReturn,' , ');
        
        
        $notes = $Notes->getNotesArray($DataBaseName, $IdRepositorio, $IdFile);
        
        if(!is_array($notes))
            return XML::XMLReponse ("Error", 0, $notes);
        
        $NewsNotesFields = '';
        for($cont = 0; $cont < count($notes); $cont++)
        {
            $NewsNotesFields.= "||Nota|| ".$notes[$cont]['IdNote'].", ".$notes[$cont]['Page'].", ".$notes[$cont]['Text']." ";
        }

        $CampoFullText = "'".$CampoFullText.$NewsNotesFields."'";  /* Se cierra la comilla simple */
        
        $CadenaUpdate.=", Full=$CampoFullText WHERE IdRepositorio=$IdFile";
        
//        echo $CadenaUpdate."<br><br>";
                
        if(($ResultUpdate = $this->db->ConsultaQuery($DataBaseName, $CadenaUpdate))==1){
            $UpdateRepositorioGlobal='';                        
            
            if($IdGlobal>0)
                $UpdateRepositorioGlobal = "UPDATE RepositorioGlobal SET Full = $CampoFullText WHERE IdGlobal = $IdGlobal";
            else
                $UpdateRepositorioGlobal = "UPDATE RepositorioGlobal SET Full = $CampoFullText WHERE IdFile = $IdFile AND IdRepositorio = $IdRepositorio";
            
            if(($ResultUpdateRepositorioGlobal = $this->db->ConsultaQuery($DataBaseName, $UpdateRepositorioGlobal))==1){                
                $doc  = new DOMDocument('1.0','utf-8');
                $doc->formatOutput = true;
                $root = $doc->createElement("DetailModify");
                $doc->appendChild($root);   
                $estado=$doc->createElement("Estado",$ResultUpdateRepositorioGlobal);
                $root->appendChild($estado);
                $FullText=$doc->createElement("Full",$campoFullReturn);
                $root->appendChild($FullText);
                header ("Content-Type:text/xml");
                echo $doc->saveXML();   
                
                Log::WriteEvent("39", $IdUsuario, $NombreUsuario, " \"$NombreArchivo\"", $DataBaseName);
                
//                echo $UpdateRepositorioGlobal;
                
            }
            else
                return XML::XMLReponse ("Error", 0, "Error al actualizar los datos. $ResultUpdateRepositorioGlobal. <p>$UpdateRepositorioGlobal</p>");            
        }
        else
            return XML::XMLReponse("Error", 0, "Error al Intentar actualar los Datos. ".$ResultUpdate);
    }
    /***************************************************************************
     *  Se devuelven los campos para ser mostrados en la vista de usuario, los campos
     * por default para esta versión tales como Topografia, Clasificacion, Gestion, Expediente, ResumenEctract,
     * No se devuelven a la vista.
     */
    private function GetDetalle($userData)
    {
        $designer=new DesignerForms();
        $Catalog = new Catalog();
                
        $DataBaseName = $userData['dataBaseName'];
        $NombreRepositorio = filter_input(INPUT_POST, "NombreRepositorio");
        $IdRepositorio = filter_input(INPUT_POST, "IdRepositorio");
        $IdArchivo = filter_input(INPUT_POST, "IdArchivo");
        $IdUsuario = $userData['idUser'];
        $NombreUsuario = $userData['userName'];
        $NombreArchivo = filter_input(INPUT_POST, "NombreArchivo");
        
        $catalogos = array();
        $EstructuraCatalogos = array();
        
        $RoutFile = dirname(getcwd());        

        $query='SELECT ';
        $InnerJoin='';
        
        /* Se obtiene la lista de catálogos pertenecientes al repositorio 
       Y se prepara la consulta que devuelve el detalle de la información          */
        
        if(!file_exists("$RoutFile/Configuracion/$DataBaseName.ini"))
            return XML::XMLReponse("Error", 0,"<p>No existe el archivo de configuración estructural.</p>");
        
        $EstructuraConfig = parse_ini_file ("../Configuracion/$DataBaseName.ini",true);
        
        /* Estructura del repositorio */
        $EstructuraRepositorio=$designer->ReturnStructure($NombreRepositorio,$EstructuraConfig[$NombreRepositorio]);
        $ArrayStructureDefault=$designer->ReturnStructureDefault($NombreRepositorio,$EstructuraConfig[$NombreRepositorio]);
        
        $ArrayCatalogos = $Catalog->getArrayCatalogsNames($DataBaseName, $IdRepositorio);
        
        if(!is_array($ArrayCatalogos))
            return XML::XMLReponse ("Error", 0, "No pudo obtenerse el listado de catálogos del repositorio <b>$NombreRepositorio<b>");

        /*  Listado de Catálogos */
        for($cont=0; $cont<count($ArrayCatalogos);$cont++)
        {
            if(count($ArrayCatalogos)>0)
            {
                foreach ($ArrayCatalogos as $campo=>$valor)
                {
                    $catalogos[$campo]=$valor;
                }
            }            
        }     
        
        /* Estructura de cada catálogo */       
        for($cont=0; $cont<count($catalogos);$cont++)
        {
            $EstructuraCatalogos[$catalogos[$cont]['NombreCatalogo']]=$designer->ReturnStructure($catalogos[$cont]['NombreCatalogo'], $EstructuraConfig[$NombreRepositorio."_".$catalogos[$cont]['NombreCatalogo']]);
        }
               
        /* Se genera el Query que realiza la consulta con el detalle del archivo a traves de las estructuras
         * del repositorio y de los catálogos */
                
         /* Campos de default en repositorio */
         for($cont=0; $cont<count($ArrayStructureDefault); $cont++)
         {             
             $CampoDefault=$ArrayStructureDefault[$cont]['name'];
             $query.=$NombreRepositorio.".".$CampoDefault.",";
         }
         
         /* Campos del repositorio */
         for($cont=0; $cont<count($EstructuraRepositorio); $cont++)       
         {
             $query.= $NombreRepositorio.".".$EstructuraRepositorio[$cont]['name'].",";           
         }   
         

         /* Se concatenan los campos de los catálogos */
         for ($cont=0; $cont<count($catalogos); $cont++)
         {
             $catalogo=$catalogos[$cont]['NombreCatalogo'];
             $query.= $NombreRepositorio.".".$catalogo.",";
             for($aux=1; $aux<count($EstructuraCatalogos[$catalogo]); $aux++)  /* 1 es el tipo */
             {
                 $NombreCatalogo=$catalogos[$cont]['NombreCatalogo'];
                 $query.= $NombreCatalogo.".".$EstructuraCatalogos[$catalogo][$aux]['name'].",";
             }                   
             /* substr obtiene el prrefijo de cada tabla pe. Rep.ClaveEmpresa, Rep.NombreArchivo, etct */
             $InnerJoin.=" LEFT JOIN $NombreRepositorio"."_".$NombreCatalogo." ".$NombreCatalogo ." ON ".$NombreRepositorio.".".$NombreCatalogo."=".  $NombreCatalogo.".Id".$NombreCatalogo;
         }       

         $query = trim($query,',');
         $InnerJoin = trim($InnerJoin,',');         
         $query.=' FROM '.$NombreRepositorio." ".$NombreRepositorio;         
         $query= $query.$InnerJoin." WHERE ".$NombreRepositorio.".IdRepositorio=$IdArchivo";
         
        $Resultado = $this->doDetailQuery($DataBaseName, $query);

        if(!is_array($Resultado))
            return XML::XMLReponse ("Error", 0, "<b>Error</b> al consultar metadatos.<br><br>Detalles:$Resultado");
        
         /* Se devuelve el valor obtenido */
      
         /*Se devuelve el xml dividido en la sección con los datos del repositorio y otra con los datos de catálogo*/
        $doc  = new DOMDocument('1.0','utf-8');
        $doc->formatOutput = true;
        $root = $doc->createElement("Detalle");
        $doc->appendChild($root);         
        $AuxCont=0;
        /* Campos del repositorio por default */        
        for($cont=0; $cont<count($ArrayStructureDefault); $cont++)        
        {       
            $CampoDefault=$ArrayStructureDefault[$cont]['name'];
            $CampoOculto=1;
            /* Campos que estan ocultos en la vista del usuario para la versión Lite */
            if($CampoDefault=='Autor' or $CampoDefault=='ResumenExtract' or $CampoDefault=='Topografia'
             or $CampoDefault=='Clasificacion' or $CampoDefault=='Gestion' or $CampoDefault=='Expediente'
             or $CampoDefault=='NombreArchivo' or $CampoDefault=='Full' or $CampoDefault=='RutaArchivo')
             {
                $CampoOculto=0;
             }      
             
             
             $Campo_Repositorio=$doc->createElement("CampoRepositorio");
             
//             $Required=0;
            if(isset($EstructuraRepositorio[$cont]['required']))
            {
//                if($EstructuraRepositorio[$cont]['required']){$Required=1;}
                $CampoRequired=$doc->createElement("required",$EstructuraRepositorio[$cont]['required']);
                $Campo_Repositorio->appendChild($CampoRequired);
            }
            
            $TipoCampo=$doc->createElement("TipoCampo","Default");
            $Campo_Repositorio->appendChild($TipoCampo);
            $CampoRepositorio=$ArrayStructureDefault[$cont]['name'];
            $campo=$doc->createElement("Campo",$CampoRepositorio); 
            $Campo_Repositorio->appendChild($campo);
            $type=$doc->createElement("type",$ArrayStructureDefault[$cont]['type']); 
            $Campo_Repositorio->appendChild($type);
            $valor=$doc->createElement("Valor",$Resultado[0][$AuxCont]);
            $Campo_Repositorio->appendChild($valor);
            $CampoVisible=$doc->createElement("CampoVisible",$CampoOculto);
            $Campo_Repositorio->appendChild($CampoVisible);

            $root->appendChild($Campo_Repositorio);
            $AuxCont++;
        }
        
        /* Campos del repositorio definidos por el usuario */    
        
        for($cont=0; $cont<count($EstructuraRepositorio); $cont++)        
        {                        
//            $Required=0;
            $Campo_Repositorio=$doc->createElement("CampoRepositorio");
            if(isset($EstructuraRepositorio[$cont]['required']))
            {
//                if($EstructuraRepositorio[$cont]['required']){$Required=1;}
                $CampoRequired=$doc->createElement("required",$EstructuraRepositorio[$cont]['required']);
                $Campo_Repositorio->appendChild($CampoRequired);
            }                      
            
            $CampoRepositorio=$EstructuraRepositorio[$cont]['name'];
            $campo=$doc->createElement("Campo",$CampoRepositorio); 
            $Campo_Repositorio->appendChild($campo);                        
            $type=$doc->createElement("type",$EstructuraRepositorio[$cont]['type']); 
            $Campo_Repositorio->appendChild($type);
            if(isset($EstructuraRepositorio[$cont]['long']))
            {
                $length=$doc->createElement("long",$EstructuraRepositorio[$cont]['long']); 
                $Campo_Repositorio->appendChild($length);
            }            
            $valor=$doc->createElement("Valor",$Resultado[0][$AuxCont]);
            $Campo_Repositorio->appendChild($valor);
            $CampoVisible=$doc->createElement("CampoVisible",1);
            $Campo_Repositorio->appendChild($CampoVisible);
            $root->appendChild($Campo_Repositorio);
            $AuxCont++;
        }
        
        /* Campos de catálogo */        
        for($cont=0 ; $cont<count($catalogos); $cont++)
        {
            $NombreCatalogo=$catalogos[$cont]['NombreCatalogo'];
            $IdCatalogo=$Resultado[0][$AuxCont];
//            echo "<p>Catalogo= $NombreCatalogo  Id = $IdCatalogo cont=$AuxCont</p>";
            $NodoCatalogo=$doc->createElement("Catalogo");
//            $Required=0;
            if(isset($EstructuraRepositorio[$cont]['required']))
            {
//                if($EstructuraRepositorio[$cont]['required']){$Required=1;}
                $CampoRequired=$doc->createElement("required",$EstructuraRepositorio[$cont]['required']);
                $Campo_Repositorio->appendChild($CampoRequired);
            }
            
            $TipoCatalogo=$EstructuraCatalogos[$NombreCatalogo][0]['Tipo'];/* Nodo 0 es El tipo de Catálogo */
            
            $Tipo_Catalogo=$doc->createElement("Tipo",$TipoCatalogo);  
            $NodoCatalogo->appendChild($Tipo_Catalogo);
            $NodoIdCatalogo=$doc->createElement("IdCatalogo",$IdCatalogo);            
            $NodoCatalogo->appendChild($NodoIdCatalogo);
            $NodoNombreCatalogo=$doc->createElement("NombreCatalogo",$NombreCatalogo);
            $NodoCatalogo->appendChild($NodoNombreCatalogo);
            
            $AuxCont++;
            for($aux=1; $aux<count($EstructuraCatalogos[$NombreCatalogo]); $aux++)
            {
                $campo_catalogo=$EstructuraCatalogos[$NombreCatalogo][$aux]['name'];
                $campo=$doc->createElement("Campo",$campo_catalogo);
                $NodoCatalogo->appendChild($campo);
                $valor=$doc->createElement("valor",$Resultado[0][$AuxCont]);
                $NodoCatalogo->appendChild($valor);
                $root->appendChild($NodoCatalogo);
                $AuxCont++;
            }  
            
        }
        header ("Content-Type:text/xml");
        echo $doc->saveXML();         
        
        Log::WriteEvent("40", $IdUsuario, $NombreUsuario, " \"$NombreArchivo\" ", $DataBaseName);
        
    }
    
    function doDetailQuery($dataBaseName, $query){
        $DB = new DataBase();
        $Resultado = array();
        
        $conexion=  $DB->Conexion();
        
        if (!$conexion) 
            return mysqli_error($conexion. "<br>".  mysqli_errno($conexion));
        
        mysqli_select_db($conexion, $dataBaseName);
        $select = mysqli_query($conexion, $query);
        
        if(!$select)
            return mysqli_error($conexion)."<br>".  mysqli_errno($conexion);
        else
            while(($Resultado[] = mysqli_fetch_array($select)) || array_pop($Resultado)); 
        
        mysqli_close($conexion);

        return $Resultado;
    }
    
    /*  Se modifica el nombre de un archivo  */
    private function FileEdit($userData)
    {
        $XML=new XML();
        $BD= new DataBase();
        $designer=new DesignerForms();
        $Log = new Log();
        $Catalog = new Catalog();
        $Notes = new Notes();
        
        $DataBaseName = $userData['dataBaseName'];
        $IdRepositorio = filter_input(INPUT_POST, "IdRepositorio");
        $IdUsuario = $userData['idUser'];
        $NombreUsuario = $userData['userName'];
        
        $ArchivoActual=  filter_input(INPUT_POST, "NombreArchivoActual");
        $NombreNuevo= filter_input(INPUT_POST, "NombreArchivoNuevo");
        $Ruta=filter_input(INPUT_POST, "Ruta");
        $NombreRepositorio=filter_input(INPUT_POST, "NombreRepositorio");                
        $IdFile=filter_input(INPUT_POST, "IdFile");
        $extension= explode(".",$ArchivoActual);
        $extension=  end($extension);        
        $query="SELECT ";
        $InnerJoin ='';
        $Full='';
        
        $EstructuraCatalogos = array();
        
        $NombreNuevo=$NombreNuevo.".".$extension;
                      
        if(file_exists(dirname($Ruta)."/".$NombreNuevo)){$XML->ResponseXML("Advertencia", 0, "El nuevo nombre <b>$NombreNuevo</b> ya existe en este directorio.");return;}
        
        if(!file_exists($Ruta)){$XML->ResponseXML("Error", 0, "No se encontró el archivo."); return;}
        
        /* Se obtienen los campos del archivo para construir el campo Full con el nuevo nombre */
        if(!file_exists("../Configuracion/$DataBaseName.ini")){$XML->ResponseXML("Error", 0,"<p>No existe el archivo de configuración estructural.</p>");return;}
        $EstructuraConfig = parse_ini_file ("../Configuracion/$DataBaseName.ini",true);
        
        /* Estructura del repositorio */
        $EstructuraRepositorio=$designer->ReturnStructure($NombreRepositorio,$EstructuraConfig[$NombreRepositorio]);
        $ArrayStructureDefault=$designer->ReturnStructureDefault($NombreRepositorio,$EstructuraConfig[$NombreRepositorio]);
        
        $catalogos = $Catalog->getFilteredArrayCatalogsDetail($DataBaseName, $IdRepositorio);
        
        if(!is_array($catalogos))
            return XML::XMLReponse ("Error", 0, "Error al intentar recuperar los catàlogos del repositorio <b>$NombreRepositorio</b>. Detalles:<br><br>$catalogos");
        
        /* Estructura de cada catálogo */       
        for($cont=0; $cont<count($catalogos);$cont++)
        {
            $EstructuraCatalogos[$catalogos[$cont]['NombreCatalogo']]=$designer->ReturnStructure($catalogos[$cont]['NombreCatalogo'], $EstructuraConfig[$NombreRepositorio."_".$catalogos[$cont]['NombreCatalogo']]);
        }
        
        /* Se genera el Query que realiza la consulta con el detalle del archivo a traves de las estructuras
         * del repositorio y de los catálogos */
                
         /* Campos de default en repositorio */
         for($cont=0; $cont<count($ArrayStructureDefault); $cont++)
         {             
             $CampoDefault=$ArrayStructureDefault[$cont]['name'];
             $query.=$NombreRepositorio.".".$CampoDefault.",";
         }
         
         /* Campos del repositorio */
         for($cont=0; $cont<count($EstructuraRepositorio); $cont++)       
         {
             $query.= $NombreRepositorio.".".$EstructuraRepositorio[$cont]['name'].",";           
         }   
         

         /* Se concatenan los campos de los catálogos */
         for ($cont=0; $cont<count($catalogos); $cont++)
         {
             $catalogo=$catalogos[$cont]['NombreCatalogo'];
             $query.= $NombreRepositorio.".".$catalogo.",";
             for($aux=1; $aux<count($EstructuraCatalogos[$catalogo]); $aux++)  /* 1 es el tipo */
             {
                 $NombreCatalogo=$catalogos[$cont]['NombreCatalogo'];
                 $query.= $NombreCatalogo.".".$EstructuraCatalogos[$catalogo][$aux]['name'].",";
             }                   
             /* substr obtiene el prrefijo de cada tabla pe. Rep.ClaveEmpresa, Rep.NombreArchivo, etct */
             $InnerJoin.=" LEFT JOIN $NombreRepositorio"."_".$NombreCatalogo." ".$NombreCatalogo ." ON ".$NombreRepositorio.".".$NombreCatalogo."=".  $NombreCatalogo.".Id".$NombreCatalogo;
         }       
                           
         $query=trim($query,',');
         $InnerJoin=trim($InnerJoin,',');        
         
         $query.=' FROM '.$NombreRepositorio." ".$NombreRepositorio;         
         $query= $query.$InnerJoin." WHERE ".$NombreRepositorio.".IdRepositorio=$IdFile";
                  
        $File=array();
        
        $conexion=  $BD->Conexion();
        
        if (!$conexion) 
            return XML::XMLReponse("Error", 0, "Error al obtener metadatos. ". mysqli_connect_errno()." ".  mysqli_connect_error());
         
        mysqli_select_db($conexion, $DataBaseName);
        $select = mysqli_query($conexion, $query);
        
        if(!$select)
            return XML::XMLReponse("Error", 0, "Error al obtener metadatos. ". mysqli_connect_errno()." ".  mysqli_connect_error());
        else
            while(($File[] = mysqli_fetch_array($select)) || array_pop($File)); 
        
        mysqli_close($conexion);
        
        $cadena_campos='';
        $cadena_valores='';
        
        /* Campos de Default del Repositorio*/
        $AuxCont=0;
         for($cont=0; $cont<count($ArrayStructureDefault); $cont++)
         {             
             $CampoDefault=$ArrayStructureDefault[$cont]['name'];
             $type=$ArrayStructureDefault[$cont]['type'];
             $value=$File[0][$AuxCont];
             
//             echo "<p>$CampoDefault = ".$File[0][$AuxCont]." </p>";
             
            $AuxCont++;
            if(strcasecmp($CampoDefault, 'TipoArchivo')==0){$TipoArchivo=$value;}
            if(strcasecmp($CampoDefault,'Full')==0){continue;}
            if(strcasecmp($CampoDefault,'NombreArchivo')==0){$Full.=$NombreNuevo." , "; continue;}

            if(!(strcasecmp($value,'0')==0) and !(strcasecmp($CampoDefault,'RutaArchivo')==0)){$Full.=$value." , ";}
            
            $cadena_campos.=$CampoDefault.",";
             
            if(strcasecmp($CampoDefault,'RutaArchivo')==0){$cadena_valores.=$Ruta."/".$NombreNuevo.","; continue;}
        
            if(strcasecmp($type,"int")==0 or strcasecmp($type,"integer")==0 or strcasecmp($type,"float")==0 || strcasecmp($type,"double")==0) /* Si detecta un tipo numerico */
            {                
                if(!(is_numeric($value)))
                    $cadena_valores.="0,";                   
                else
                    $cadena_valores.=$value.",";

            }
            else    /* Demás tipos de datos llevan ' ' */
                $cadena_valores.="'".$value."'".",";
         }
         
         $Full=  trim($Full,' , ');
                         
        /**********************Campos del repositorio****************************/
         
         for($cont=0; $cont<count($EstructuraRepositorio); $cont++)       
         {
             $CampoRepositorio=$EstructuraRepositorio[$cont]['name'];
             
//             echo "<p>$CampoRepositorio = ".$File[0][$AuxCont]." </p>";
             
             $type=$EstructuraRepositorio[$cont]['type'];
             $cadena_campos.=" ".$CampoRepositorio.",";

            if(strcasecmp($type,"int")==0 or strcasecmp($type,"float")==0 || strcasecmp($type,"integer")==0 || strcasecmp($type,"double")==0) /* Si detecta un tipo numerico */
            {                 
                if(!(is_numeric($File[0][$AuxCont])))
                {                    
                    $cadena_valores.=" 0,";                   
                }else
                {
                    $cadena_valores.=$File[0][$AuxCont].",";
                }

            }
            else    /* Demás tipos de datos llevan ' ' */
            {
                $cadena_valores.="'".$File[0][$AuxCont]."'".",";
            }  
            $Full.=" , ".$File[0][$AuxCont]." ";      
            
            $AuxCont++;
         }   
         
         $Full=  trim($Full,' , ');
         
         /*  Match con Campos de Catálogo  */
        for($cont=0; $cont<count($catalogos);$cont++)
        {                        
            $NombreCatalogo=$catalogos[$cont]['NombreCatalogo'];
            $cadena_campos.=$NombreCatalogo.",";
            $cadena_valores.=$File[0][$AuxCont].",";   
            $AuxCont++;
            for($aux=1; $aux<count($EstructuraCatalogos[$NombreCatalogo]); $aux++)  /* 1 es el tipo */
             {
                 $value=$File[0][$AuxCont];
                 $Full.=" , ".$value;
                 $AuxCont++;
             }     
        }
        
        $Full=  trim($Full,' , ');
        
        /* Se concatenan las notas  */
        $notes = $Notes->getNotesArray($DataBaseName, $IdRepositorio, $IdFile);
        
        if(!is_array($notes))
            return XML::XMLReponse ("Error", 0, $notes);
        
        $NewsNotesFields = '';
        for($cont = 0; $cont < count($notes); $cont++)
        {
            $NewsNotesFields.= "||Nota|| ".$notes[$cont]['IdNote'].", ".$notes[$cont]['Page'].", ".$notes[$cont]['Text']." ";
        }
                                      
        $Full = $Full." ".$NewsNotesFields;
        
        if(rename($Ruta,  dirname($Ruta)."/".$NombreNuevo))
        {            
            $query="UPDATE $NombreRepositorio SET RutaArchivo='".dirname($Ruta)."/$NombreNuevo',NombreArchivo='$NombreNuevo', Full='$Full' WHERE IdRepositorio=$IdFile";
            
            if(($ResultConsulta=$BD->ConsultaQuery($DataBaseName, $query))!=1)
            {
                $XML->ResponseXML("Error", 0, "Ocurrío un error al intentar actualizar el documento. ". $ResultConsulta);return;                    
            }
            else
            {                
                $UpdateGlobal = "UPDATE RepositorioGlobal SET RutaArchivo='".dirname($Ruta)."/$NombreNuevo',NombreArchivo='$NombreNuevo', Full='$Full' WHERE IdRepositorio = $IdRepositorio AND IdFile = $IdFile ";
                
                if(($ResultUpdateGlobal = $BD->ConsultaQuery($DataBaseName, $UpdateGlobal))==1)
                {
                    /*  Devolución de repuesta en XML */
                    $doc  = new DOMDocument('1.0','utf-8');
                    $doc->formatOutput = true;
                    $root = $doc->createElement("FileEdit");
                    $doc->appendChild($root); 
                    $XmlNombreArchivo=$doc->createElement("NombreArchivo",$NombreNuevo);
                    $root->appendChild($XmlNombreArchivo);
                    $XmlRutaArchivo=$doc->createElement("Ruta",dirname($Ruta)."/".$NombreNuevo);
                    $root->appendChild($XmlRutaArchivo);
                    $XmlTipoArchivo=$doc->createElement("TipoArchivo",$TipoArchivo);
                    $root->appendChild($XmlTipoArchivo);
                    $XmlFull=$doc->createElement("Full",$Full);
                    $root->appendChild($XmlFull);
                    $XmlEstado=$doc->createElement("Estado",1);
                    $root->appendChild($XmlEstado);
                    $XmlMensaje=$doc->createElement("Mensaje","Archivo Actualizado con éxito a <b>$NombreNuevo</b>");
                    $root->appendChild($XmlMensaje);
                    header ("Content-Type:text/xml");
                    echo $doc->saveXML();
                    
                    $Log ->Write("24", $IdUsuario, $NombreUsuario, " '$ArchivoActual' a '$NombreNuevo'", $DataBaseName);
                    
                }
                else
                    $XML ->ResponseXML ("Error", 0, "Error al actualizar el repositorio global. $ResultUpdateGlobal");                                
            }
        }
        else
            $XML->ResponseXML("Error", 0, "Error al renombrar el archivo");

    }
    
    /***************************************************************************
     * 
     *  Devuelve el listado de archivos que se encuentran dentro de un directorio
     */
    private function GetFiles()
    {
        $XML=new XML();
        $BD= new DataBase();
        $DataBaseName=  filter_input(INPUT_POST, "DataBaseName");
        $UserName = filter_input(INPUT_POST, "UserName");
        $IdUser = filter_input(INPUT_POST, "IdUser");
        $IdGroup = filter_input(INPUT_POST, "IdGroup");
        $NombreRepositorio=  filter_input(INPUT_POST, "RepositoryName");
        $IdRepository = filter_input(INPUT_POST, "IdRepository");
        $IdDirectory=  filter_input(INPUT_POST, "IdDirectory");    
        
        $CheckPermission = "SELECT *FROM RepositoryControl WHERE IdGrupo = $IdGroup";
        $CheckPermissionResult = $BD->ConsultaSelect($DataBaseName, $CheckPermission);

        if($CheckPermissionResult['Estado']!=1)
        {
            XML::XMLReponse("Error", 0, "<p><b>Error</b> al comprobar permisos de consulta sobre el repositorio</p><br>Detalles:<br><br>".$CheckPermissionResult['Estado']);
            return 0;
        }
        
        if(!(count($CheckPermissionResult['ArrayDatos'])>0))
        {
            $XML->ResponseXmlFromArray("Busqueda", "Resultado", 0); 
            return;
        }
        
        $ConsultaBusqueda='SELECT IdRepositorio, TipoArchivo, FechaIngreso, NombreArchivo, Full, RutaArchivo FROM '.$NombreRepositorio.' WHERE IdDIrectory = '.$IdDirectory;
        $Resultado=$BD->ConsultaSelect($DataBaseName, $ConsultaBusqueda);           
        $XML->ResponseXmlFromArray("Busqueda", "Resultado", $Resultado['ArrayDatos']);        
    }
    
    
    /***************************************************************************
     *  Motor de Búsqueda
     * 
     */
    private function EngineSearch($userData)
    {          
        $Search = filter_input(INPUT_POST, "Search");
        $DataBaseName = $userData['dataBaseName'];
        $NombreUsuario = $userData['userName'];
        $IdUsuario = $userData['idUser'];
        $IdGroup = $userData['idGroup'];
        
        $ConsultaBusqueda = 'SELECT * FROM RepositorioGlobal rg INNER JOIN RepositoryControl rc ON rg.IdRepositorio = rc.IdRepositorio WHERE MATCH (rg.Full) AGAINST (\''.$Search.'\' IN BOOLEAN MODE) AND rc.IdGrupo = '.$IdGroup;

        $Resultado = $this->db->ConsultaSelect($DataBaseName, $ConsultaBusqueda);        

        if($Resultado['Estado']!= 1)
            return XML::XMLReponse("Error", 0, $Resultado['Estado']); 

        Log::WriteEvent("28", $IdUsuario, $NombreUsuario , " $Search", $DataBaseName);
        
        XML::XmlArrayResponse("Busqueda", "Resultado", $Resultado['ArrayDatos']);                           
        
    }
    
 
    
    /***************************************************************************
     * Inserta en una carpeta seleccionada por el usuario (Publicador)
     * 
     */
    private function UploadFile()
    {
        $XML=new XML();
//        $BD=new DataBase();
        
        $tamanioPermitido = 200 * 1024;
        $name= $_FILES['archivo']['name'];
        $type= $_FILES['archivo']['type'];
        $size=$_FILES['archivo']['size'];
        $tmp_name=$_FILES['archivo']['tmp_name'];
        $extension = explode(".", $name);
        $extension=  end($extension);
        $estado=1;
        $mensaje="Archivo Almacenado Temporalmente";        
        if(!file_exists("Temp"))
        {
            if(mkdir("Temp",0777))
            {
                $XML->ResponseXML("UploadFile", 1, "Carpeta Temporal Creada");
            }
            else
            {
                $XML->ResponseXML("Error", 0, "Error al crear el directorio Temporal");
            }
        }
                
        //Validamos el tipo de archivo, el tamaño en bytes y que la extensión sea válida
        if ((($type == "image/gif")
              || ($type == "image/jpeg")
              || ($type == "image/png")
              || ($type == "image/pjpeg")
              || ($type == "application/pdf")
              || ($type == "text/xml"))
              && ($size < $tamanioPermitido)
             ){
                      //Si no hubo un error al subir el archivo temporalmente
                      if ($_FILES['archivo']["error"] > 0){
                             $XML->ResponseXML("Error", 0, "Return Code: " . $_FILES['archivo']['error']);
                      }
                      else{
                            //Si el archivo ya existe se muestra el mensaje de error
                            if (file_exists("Temp/" . $name)){
                                   $XML->ResponseXML("Error", 0, "Return Code: " . $name. " ya existe. ");
                            }
                            else{
                                   //Se mueve el archivo de su ruta temporal a una ruta establecida
                                   move_uploaded_file($tmp_name,"Temp/" . $name);
                                   $XML->ResponseXML("UploadFile", 1, "Archivo almacenado en: " . "Temp/" . $name);
                            }
                      }
        }
        else{
             $XML->ResponseXML("Error", 0,  "Archivo inválido");
        }
    }        

   /* Es llamada para la carga manual del content management */ 
    private function UploadMetadatas()
    {
        $BD= new DataBase();
        $XML=new XML();
        $nombre_usuario = filter_input(INPUT_POST, "nombre_usuario");
        $xmlResponse = filter_input(INPUT_POST, "XmlReponse");
        $DataBaseName = filter_input(INPUT_POST, "DataBaseName");
        $NombreRepositorio = filter_input(INPUT_POST, "NombreRepositorio");
        $IdRepositoio = filter_input(INPUT_POST, "IdRepositorio");
        $NombreArchivo = filter_input(INPUT_POST, "NombreArchivo");
        $IdEmpresa = filter_input(INPUT_POST, "IdEmpresa");
        $NombreEmpresa = filter_input(INPUT_POST, "NombreEmpresa");
        $IdDirectory = filter_input(INPUT_POST, "IdDirectory");
        $Path = filter_input(INPUT_POST, "Path");
        $PathPrincipal = "Estructuras/".$DataBaseName."/";
        $PathDestino = '../'.$PathPrincipal.$NombreRepositorio.$Path.'/'; 
        
        $xml=  simplexml_load_string($xmlResponse);
        $Catalogos='';
        /* Valores por Default */
        
        $FechaIngreso=  date("Y-m-d");
        /* Datos del Archivo */
        $name= $_FILES['archivo']['name'];
        $type= $_FILES['archivo']['type'];
        $tmp_name=$_FILES['archivo']['tmp_name'];
        $size=$_FILES['archivo']['size'];
        
        $TipoArchivo_=  explode(".",$name);$ResumenExtrac=0;$Autor=0;$Topografia=0;$Clasificacion=0;
        $Gestion=0;$Expediente=0;$Full='';
        $TipoArchivo=  end($TipoArchivo_);

        
                       
        $renombrado=0;
                
        /* Primero se Mueve el archivo a su destino luego se realiza el insert en la BD */ 
        if ($_FILES['archivo']["error"] > 0)
        {
                 $XML->ResponseXML("Error", 0, "Return Code: " . $_FILES['archivo']['error']);
                 return;
        }
            //Si el archivo ya existe se muestra el mensaje de error
            $t=1; 
            while(file_exists($PathDestino.$name)){ 
             $archivo = $name; 
             $archivo=substr($archivo,0,strpos($archivo,"."))."_$t".strstr($archivo,".");  
             $name=$archivo;
             $t++; 
             $renombrado=1;
             $NombreArchivo=$name;
            } 
            
            if(!file_exists($PathDestino))
                mkdir ($PathDestino,0777,true);
            
            //Se mueve el archivo de su ruta temporal a una ruta establecida
            if(!($mover=move_uploaded_file($tmp_name,$PathDestino.$name)))
            {
                $XML->ResponseXML("Error", "0", "El archivo no se pudo mover a su destino. $mover");
                return;
            }
            
            if($renombrado==1){$renombrado="El archivo ya existía y fué renombrado a ".$name;}else{$renombrado='';}
                                
        $cadenaCampos='';
        $cadenaValores='';
        
        if(count($xml->MetaData)>0)
        {
            foreach ($xml->MetaData as $metadata)
            {
                $Full.="  ".$metadata->value." , ";
                $cadenaCampos.=$metadata->name.",";
                $type=$metadata->type;
                $stripos=stripos($type,"int");
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
            }
        }
        
        if(count($xml->Catalogo)>0)
        {
            foreach ($xml->Catalogo as $campo)
            {
                $Full.="  ".$campo->TextoSelect."  ";
                $cadenaCampos.=$campo->name.",";
                $type=$campo->type;
                $stripos=stripos($type,"int");
                $value=$campo->value;
                if(strcasecmp($type,"int")==0 or strcasecmp($type,"integer")==0 or strcasecmp($type,"float")==0 or strcasecmp($type,"double")==0) /* Si detecta un tipo numerico */
                {
                    if(!(is_numeric("$value")))
                        $cadenaValores.="0,";
                    else
                        $cadenaValores.=$campo->value.",";
                }
                else
                    $cadenaValores.="'".$campo->value."'".",";
                
            }
        }
        $Full.=" $TipoArchivo";
                
        $CadenaCamposDefault=",IdEmpresa,TipoArchivo,RutaArchivo,ResumenExtract,Autor,Topografia,Clasificacion, Gestion, Expediente, FechaIngreso,NombreArchivo,Full";
        $CadenaValoresDefault=",$IdEmpresa,'$TipoArchivo','$PathDestino$name','$ResumenExtrac','$Autor',$Topografia,'$Clasificacion',$Gestion,$Expediente,'$FechaIngreso','$NombreArchivo','$Full'";
        
        $cadenaValores=trim($cadenaValores,',');  /* Quita la última Coma ( , ) */
        $cadenaCampos=trim($cadenaCampos,',');
        
        $query="INSERT INTO $NombreRepositorio (".$cadenaCampos . $CadenaCamposDefault.") VALUES (".$cadenaValores.$CadenaValoresDefault.")";
        $Metadata=$BD->ConsultaInsertReturnId($DataBaseName, $query);
        if($Metadata>0)
        {           
            $BodyQuery = "(IdFile, IdEmpresa, IdRepositorio, NombreEmpresa, "
            . "NombreRepositorio, IdDirectory, NombreArchivo, TipoArchivo, RutaArchivo,UsuarioPublicador, FechaIngreso, Full) "
            . "VALUES ($Metadata, $IdEmpresa, $IdRepositoio, '$NombreEmpresa', '$NombreRepositorio', $IdDirectory , '$NombreArchivo',"
            . " '$TipoArchivo', '$PathDestino$name' , '$nombre_usuario', '$FechaIngreso', '$Full')";
            
            $InsertIntoGlobal = "INSERT INTO RepositorioGlobal $BodyQuery";
                    
            $ResultInsertGlobal = $BD ->ConsultaInsertReturnId($DataBaseName, $InsertIntoGlobal);
                    
            if($ResultInsertGlobal>0)
            {
                /*  Devolución de repuesta en XML */
                $doc  = new DOMDocument('1.0','utf-8');
                $doc->formatOutput = true;
                $root = $doc->createElement("SetMetadatas");
                $doc->appendChild($root); 
                $XmlNombreArchivo=$doc->createElement("NombreArchivo",$NombreArchivo);
                $root->appendChild($XmlNombreArchivo);
                $XmlFechaIngreso=$doc->createElement("FechaIngreso",$FechaIngreso);
                $root->appendChild($XmlFechaIngreso);
                $XmlTipoArchivo=$doc->createElement("TipoArchivo",$TipoArchivo);
                $root->appendChild($XmlTipoArchivo);
                $XmlRutaArchivo=$doc->createElement("RutaArchivo",$PathDestino.$name);
                $root->appendChild($XmlRutaArchivo);
                $XmlFull=$doc->createElement("Full",$Full);
                $root->appendChild($XmlFull);
                $XmlIdRepositorio=$doc->createElement("IdRepositorio",$Metadata);
                $root->appendChild($XmlIdRepositorio);
                $XmlEstado=$doc->createElement("Estado",1);
                $root->appendChild($XmlEstado);
                $XmlMensaje=$doc->createElement("Mensaje","Archivo $NombreArchivo almacenado correctamente.");
                $root->appendChild($XmlMensaje);
                header ("Content-Type:text/xml");
                echo $doc->saveXML();
            }
            else
            {
                $BD->ConsultaQuery($DataBaseName, "DELETE FROM $NombreRepositorio WHERE IdRepositorio = $Metadata");
                if(file_exists($PathDestino.$name))
                    unlink($PathDestino.$name);
                
                $XML->ResponseXML("Error", 0, "<p>Error al almacenar metadatos en Global $ResultInsertGlobal. <br>$BodyQuery</p>");
            }                                                
        }
        else
            return $XML->ResponseXML("Error", 0, "<p>Error al almacenar metadatos en el repositorio $Metadata</p><br><br>$query");
    }        
    
    private function getListEmpresas()
    {
        $XML=new XML();
        $BD= new DataBase();
        $IdUsuario=filter_input(INPUT_POST, "IdUser");
        $DataBaseName = filter_input(INPUT_POST, "DataBaseName");
        
        $query="SELECT *FROM Empresas ";        
        /* Resultado de Query */        
        $ListEmpresas= $BD->ConsultaSelect($DataBaseName, $query);
        
        /* Comprobación del Estado de la Consulta */
        if($ListEmpresas['Estado']!=1)
            return $XML->ResponseXML("Error", 0,$ListEmpresas['Estado']);  

        /*  Se recorre el listado de empresas para ser mostrado en el Select del Content MNanagement */
        $Listado=$ListEmpresas['ArrayDatos'];
        
        /*  Devolución de repuesta en XML */
        $doc  = new DOMDocument('1.0','utf-8');
        $doc->formatOutput = true;
        $root = $doc->createElement("ListEmpresas");
        $doc->appendChild($root); 
        
        if((count($ListEmpresas['ArrayDatos'])==0) or $ListEmpresas['ArrayDatos']==0)
        {
            $Empresa=$doc->createElement("Empresa");
            $XMLNombreEmpresa=$doc->createElement("NombreEmpresa","No Existen Empresas");
            $Empresa->appendChild($XMLNombreEmpresa);
            $XMLIdEmpresa=$doc->createElement("IdEmpresa",0);
            $Empresa->appendChild($XMLIdEmpresa);
            $root->appendChild($Empresa);
        }
        for($cont=0;$cont<count($Listado);$cont++)
        {
            $NombreEmpresa=$Listado[$cont]['NombreEmpresa'];
            $IdEmpresa=$Listado[$cont]['IdEmpresa'];
            
            $Empresa=$doc->createElement("Empresa");
            $XMLNombreEmpresa=$doc->createElement("NombreEmpresa",$NombreEmpresa);
            $Empresa->appendChild($XMLNombreEmpresa);
            $XMLIdEmpresa=$doc->createElement("IdEmpresa",$IdEmpresa);
            $Empresa->appendChild($XMLIdEmpresa);
            $ClaveEmpresa=$doc->createElement("ClaveEmpresa",$Listado[$cont]['ClaveEmpresa']);
            $Empresa->appendChild($ClaveEmpresa);
            $root->appendChild($Empresa);
        }
        header ("Content-Type:text/xml");
        echo $doc->saveXML();
    }
    /*****************************************************************************
     * Obtiene el listado de Catálogos pertenecientes a un Repositorio
     */
    private function getCatalogos()
    {
        $XML = new XML();
        $BD = new DataBase();
        $DataBaseName = filter_input(INPUT_POST, "DataBaseName");
        $IdRepositorio = filter_input(INPUT_POST, "IdRepositorio");
        $Consulta ="SELECT IdRepositorio, NombreCatalogo FROM CSDocs_Catalogos WHERE IdRepositorio=$IdRepositorio";    
        
        $Catalogos = $BD->ConsultaSelect($DataBaseName, $Consulta);
        
        if($Catalogos['Estado']!=1)
            return $XML->ResponseXML("Error", 0, "<p>Ocurrió un error al consultar los usuarios ".$Catalogos['Estado']."</p>");
        
        $XML->ResponseXmlFromArray("Catalogos", "Empresa", $Catalogos['ArrayDatos']);
    }
    /***************************************************************************
     *  Devuelve el listado de Repositorios correspondientes a una Empresa
     * 
     */
    private function getListRepositorios()
    {
        $XML=new XML();
        $BD= new DataBase();
                
        $DataBaseName=filter_input(INPUT_POST, "DataBaseName");
        $IdGrupo = filter_input(INPUT_POST, "IdGrupo");
        $NombreGrupo = filter_input(INPUT_POST, "NombreGrupo");
        $IdEmpresa=filter_input(INPUT_POST, "IdEmpresa");
        $IdUsuario=filter_input(INPUT_POST, "IdUsuario");
        $EnterpriseKey = filter_input(INPUT_POST, "EnterpriseKey");
                    
//        $query="select em.IdEmpresa, re.IdRepositorio, re.NombreRepositorio from Repositorios re INNER JOIN Empresas em "
//                . "on re.ClaveEmpresa=em.ClaveEmpresa WHERE em.IdEmpresa=$IdEmpresa";     
        
        $query = "SELECT  em.IdEmpresa, re.IdRepositorio, re.NombreRepositorio FROM Repositorios re "
                . "INNER JOIN RepositoryControl rc ON rc.IdRepositorio = re.IdRepositorio "
                . "INNER JOIN Empresas em on re.ClaveEmpresa=em.ClaveEmpresa "
                . "WHERE rc.IdGrupo = $IdGrupo";
        
        /* Resultado de Query */        
        $ListRepositorios= $BD->ConsultaSelect($DataBaseName, $query);
        
        /* Comprobación del Estado de la Consulta */
        if($ListRepositorios['Estado']!=1){$XML->ResponseXML("Error", 0,$ListRepositorios['Estado']); return 0;}      
        
        /*  Se recorre el listado de empresas para ser mostrado en el Select del Content MNanagement */
        $Listado=$ListRepositorios['ArrayDatos'];        
        
        /*  Devolución de repuesta en XML */
        $doc  = new DOMDocument('1.0','utf-8');
        $doc->formatOutput = true;
        $root = $doc->createElement("ListRepositorios");
        $doc->appendChild($root); 
        
        if((count($ListRepositorios['ArrayDatos'])==0) or $ListRepositorios['ArrayDatos']==0)
        {
            $Repositorio=$doc->createElement("Repositorio");
            $XMLNombreRepositorio=$doc->createElement("NombreRepositorio","No Existen Repositorios");
            $Repositorio->appendChild($XMLNombreRepositorio);
            $XMLIdRepositorio=$doc->createElement("IdRepositorio",0);
            $Repositorio->appendChild($XMLIdRepositorio);
            $root->appendChild($Repositorio);
        }
        for($cont=0;$cont<count($Listado);$cont++)
        {
            $NombreRepositorio=$Listado[$cont]['NombreRepositorio'];
            $IdRepositorio=$Listado[$cont]['IdRepositorio'];
            
            $Repositorio=$doc->createElement("Repositorio");
            $XMLNombreRepositorio=$doc->createElement("NombreRepositorio",$NombreRepositorio);
            $Repositorio->appendChild($XMLNombreRepositorio);
            $XMLIdRepositorio=$doc->createElement("IdRepositorio",$IdRepositorio);
            $Repositorio->appendChild($XMLIdRepositorio);
            $root->appendChild($Repositorio);
        }
        header ("Content-Type:text/xml");
        echo $doc->saveXML();
    }
    
    public static function RenameFile($filePath)
    {
        $increment = 1; //start with no suffix
        $name = pathinfo($filePath, PATHINFO_FILENAME);
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        $path = dirname($filePath);
        while(file_exists($path."/".$name . $increment . '.' . $extension)) {
            $increment++;
        }

        $finalPath = $path."/".$name . $increment . '.' . $extension;
        
        return $finalPath;
    }
        
}

$content = new ContentManagement();
$content->ajax();