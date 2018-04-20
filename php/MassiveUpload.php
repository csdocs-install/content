<?php

/**
 * Description of MassiveUpload
 *
 * @author daniel
 */
require_once 'DataBase.php';
require_once 'XML.php';
require_once 'DesignerForms.php';
require_once 'Log.php';
require_once 'UploadSources.php';
require_once 'Catalog.php';

class MassiveUpload {
    public function __construct() {
        $this->Ajax();
    }
    
    private function Ajax()
    {
        switch (filter_input(INPUT_POST, "opcion"))
        {
            case 'CheckMassiveUploadIncomplete':$this->CheckMassiveUploadIncomplete(); break;
            case 'NewMassiveUpload':$this->NewMassiveUpload(); break;
            case 'ResumeMassiveUpload':$this->ResumeMassiveUpload(); break;
            default: exit;
        }
    }
    
    private function CheckMassiveUploadIncomplete()
    {
//        $IdUser = filter_input(INPUT_POST, "IdUser");
        $UserName = filter_input(INPUT_POST, "UserName");
        $RoutFile = filter_input(INPUT_SERVER, "DOCUMENT_ROOT"); /* /var/services/web */
        $DataBaseName = filter_input(INPUT_POST, "DataBaseName");
        
        if(!file_exists("/volume1/Publisher/$DataBaseName/$UserName"))
        {
            if(!($mkdir =mkdir("/volume1/Publisher/$DataBaseName/$UserName", 0777, true)))
            {
                XML::XMLReponse("Error", 0, "<p><b>Error</b> al crear el directorio de carga del usuario</p><br>Detalles:<br><br>$mkdir");
                return 0;
            }
        }
        
        $PathDirectorySettings = "$RoutFile/Configuracion/MassiveUpload/$DataBaseName/$UserName/";
        if(!file_exists($PathDirectorySettings))
        {
            XML::XMLReponse ("NotExist", 0, "<p>No existe carga masiva pendiente</p>");     
            return 0;
        }
        
        $contenido=  scandir($PathDirectorySettings);
        if(count($contenido) > 2)
        {
            XML::ResponseXML("Exist", 1, "Existe archivo de sesión de carga masiva ");
            return;            
        }     
        else
            XML::XMLReponse ("NotExist", 0, "<p>No existe carga masiva pendiente</p>");     
    }
    
    private function DeleteSettingsMassiveUpload($DataBaseName,$UserName)
    {
        $RoutFile = filter_input(INPUT_SERVER, "DOCUMENT_ROOT"); /* /var/services/web */
        
        $PathDirectorySettings = "$RoutFile/Configuracion/MassiveUpload/$DataBaseName/$UserName";
        if(file_exists($PathDirectorySettings))
        {
            $this->deleteDirectory($PathDirectorySettings);
        }
        else
            echo "<p>No existe el </p>";
    }
    
    private function CreateSettingsMassiveUpload()
    {
        $RoutFile = filter_input(INPUT_SERVER, "DOCUMENT_ROOT");
        
        $DataBaseName = filter_input(INPUT_POST, "DataBaseName");
        $NombreRepositorio =  filter_input(INPUT_POST, "RepositoryName");
        $IdRepositorio = filter_input(INPUT_POST, "IdRepository");
        $IdUser = filter_input(INPUT_POST, "IdUser");
        $IdEmpresa = filter_input(INPUT_POST, "IdEnterprise");
        $NombreEmpresa = filter_input(INPUT_POST, "EnterpriseName");
        $UserName =  filter_input(INPUT_POST, "UserName");
        $IdDirectory =  filter_input(INPUT_POST, "IdDirectory");
        $Path =  filter_input(INPUT_POST, "Path");
        $Random = filter_input(INPUT_POST, "Random");
        $SourceMassiveUpload = filter_input(INPUT_POST, "SourceMassiveUpload");
        $fecha = date("ymdhis");                            
        $UserPathMassiveUpload = "$RoutFile/Configuracion/MassiveUpload/$DataBaseName/$UserName";
        /* Se crea un archivo .ini para guardar los datos en la carpeta de Configuracion */        
        if(!file_exists("$RoutFile/Configuracion")){XML::ResponseXML("Error", 0, "No existe el directorio de configuración"); return;}
        
        if(file_exists($UserPathMassiveUpload))
            $this->deleteDirectory ($UserPathMassiveUpload);
            
        if(!mkdir($UserPathMassiveUpload,0777,true))
        {
            XML::ResponseXML("Error", 0, "Imposible crear directorio de Configuración del  usuario."); 
            return 0;            
        }
        
        if(!file_exists("/volume1/Publisher/"))
        {
            XML::ResponseXML("Error", 0, "<p>Un administrador necesita crear un directorio compartido llamado <b>Publisher</b> con permisos para todos los usuarios para realizar la <b>Carga Masiva</b></p>"); 
            return 0;            
        }
        
        if(!file_exists("/volume1/Publisher/$DataBaseName/$UserName"))
        {
            if(!mkdir("/volume1/Publisher/$DataBaseName/$UserName",0777,true))
                {
                    XML::ResponseXML("Error", 0, "Imposible crear directorio de Carga Masiva para el usuario en <b>Publisher/$DataBaseName/$UserName</b>"); 
                    return 0;                    
                }
        }
            
        $archivo="$UserPathMassiveUpload/$fecha.ini";
        if(!($config=  fopen($archivo, "w"))){XML::ResponseXML("Error", 0, "Error al crear archivo de configuración."); return 0;}  /* Error al abrir y crear el archivo de config */
        else
        {
            fwrite($config, "DataBaseName=\"$DataBaseName\"".PHP_EOL);
            fwrite($config, "RepositoryName=\"$NombreRepositorio\"".PHP_EOL);
            fwrite($config, "IdRepository=\"$IdRepositorio\"".PHP_EOL);
            fwrite($config, "IdEnterprise=\"$IdEmpresa\"".PHP_EOL);     
            fwrite($config, "EnterpriseName=\"".$NombreEmpresa."\"".PHP_EOL);
            fwrite($config, "IdDirectory=\"$IdDirectory\"".PHP_EOL);
            fwrite($config, "Path=\"$Path\"".PHP_EOL);
            fwrite($config, "IdUser=$IdUser".PHP_EOL);
            fwrite($config, "UserName=$UserName".PHP_EOL);
            fwrite($config, "Random=$Random".PHP_EOL);
            fwrite($config, "SourceMassiveUpload=$SourceMassiveUpload".PHP_EOL);
            fclose($config);
            
            
            if(($CreateCatalogsFiles = $this->CreateTempCatalogsFiles($DataBaseName, $NombreRepositorio ,$IdRepositorio, $UserName)) != 1)
            {
                unlink($archivo);
                XML::XMLReponse("Error", 0, "<p><b>Error</b> al crear los catálogos temporales</p>");
                return 0;
            }
            
            if(!file_exists($archivo)){XML::ResponseXML("Error", 0, "Error al crear archivo de configuración."); return 0;}
        }
        
        return 1;
    }
    
    private function NewMassiveUpload()
    {

        $DataBaseName = filter_input(INPUT_POST, "DataBaseName");
        $UserName = filter_input(INPUT_POST, "UserName");
        
        $this->DeleteSettingsMassiveUpload($DataBaseName, $UserName);
        if(($CreateSettings = $this->CreateSettingsMassiveUpload())!=1)
                return 0;
        
        $this->StartMassiveUpload();
    }
    
    private function ResumeMassiveUpload()
    {
        $this->StartMassiveUpload();
    }
    
    /* Se descarga */
    
    private function CreateTempCatalogsFiles($DataBaseName, $NombreRepositorio ,$IdRepository, $UserName)
    {
        $DB = new DataBase();
        $RoutFile = filter_input(INPUT_SERVER, "DOCUMENT_ROOT");
        $Catalog = new Catalog();
        
        $Catalogs = $Catalog->getArrayCatalogsNames($DataBaseName, $IdRepository);
        
        if(!is_array($Catalogs))
            return XML::XMLReponse ("Error", 0, "<b>Error</b> al recuperar el listado de catálogos. <br><br>Detalles:<br><br>$Catalogs");
        
        for($cont = 0; $cont < count($Catalogs); $cont++)
        {
            $CatalogName = $Catalogs[$cont]['NombreCatalogo'];
            
            $CatalogRecords = $Catalog->getCatalogRecords($DataBaseName, $NombreRepositorio, $CatalogName);
            
            if(!is_array($CatalogRecords))
                return XML::XMLReponse ("Error", 0, $CatalogRecords);
            
            $PathTempFile = "$RoutFile/Configuracion/Catalogs/$DataBaseName/$UserName";
            
            if(!file_exists($PathTempFile))
            {                
                if(($mkdir = mkdir($PathTempFile, 0777, true))!=1)
                {
                    XML::XMLReponse("Error", 0, "<p><b>Error</b> al generar la ruta del catálogo temporal <b>$CatalogName</p></p><br>Detalles:<br><br>".$mkdir);
                    return 0;
                }
            }
            
            if(($TempFile=  fopen($PathTempFile."/$NombreRepositorio"."_"."$CatalogName.ini", "w")))
            {
                for($aux = 0; $aux < count($CatalogRecords); $aux++)
                {
                    $RecordsString_ = '';
                    foreach ($CatalogRecords[$aux] as $Key => $value)
                    {
                        $value = preg_replace('["]', "",$value);
                        $RecordsString_.=" $value,";
                    }
                    $RecordsString = trim($RecordsString_, ",");
                    fwrite($TempFile,$CatalogName."[".$CatalogRecords[$aux]['Id'.$CatalogName]."]".'="'.$RecordsString.'"'. PHP_EOL);                    
                }
                fclose($TempFile);                                       
            }  
            else
            {
                XML::ResponseXML("Error", 0, "Error al crear archivo temporal del catálogo <b>$CatalogName</b>.");             
                return 0;     
            }                                                           
        }
        
        return 1;
    }       

    private function StartMassiveUpload()
    {
        $Catalog = new Catalog();
        $designer=new DesignerForms();
        
        $RoutFile = filter_input(INPUT_SERVER, "DOCUMENT_ROOT");        
        $DataBaseName=  filter_input(INPUT_POST, "DataBaseName");
        $IdUser=filter_input(INPUT_POST, "IdUser");
        $UserName=  filter_input(INPUT_POST, "UserName");
        
        $SettingsMassiveUpload = $this->GetSettingsUploadMassive($DataBaseName, $UserName);
        
        if(!is_array($SettingsMassiveUpload))
        {
            echo "<p>No fué posible abrir el archivo de sesión de carga masiva.</p>";
            return 0;
        }                  
        
        $NombreRepositorio = $SettingsMassiveUpload['RepositoryName'];
        $IdRepositorio =  $SettingsMassiveUpload['IdRepository'];
        $IdDirectory = $SettingsMassiveUpload['IdDirectory'];
        $Path = $SettingsMassiveUpload['Path'];

        $directorio="/volume1/Publisher/$DataBaseName/$UserName";
        
        if(!file_exists("$RoutFile/Configuracion/$DataBaseName.ini")){XML::ResponseXML("Error", 0, "No existe el archivo de Configuración del $NombreRepositorio"); return;}
        $EstructuraConfig=parse_ini_file ("$RoutFile/Configuracion/$DataBaseName.ini",true);
 
        $ArrayStructureUser=$designer->ReturnStructure($NombreRepositorio,$EstructuraConfig[$NombreRepositorio]);
        $ArrayStructureDefault=$designer->ReturnStructureDefault($NombreRepositorio,$EstructuraConfig[$NombreRepositorio]);        
        
        /* Listado de Catálogos */
        $Catalogos = $Catalog->getArrayCatalogsNames($DataBaseName, $IdRepositorio);
        
        if(!is_array($Catalogos))
            return XML::XMLReponse ("Error", 0, "<b>Error</b> al obtener el listado de catálogos.<br><br>Detalles:<br><br>$Catalogos");
        
        /* Función recursiva para leer los directorios por debajo de root  */
        
        $ScanDirResult = $this->scan_dir($ArrayStructureDefault,$ArrayStructureUser,$Catalogos,$UserName,$directorio,$IdDirectory,$SettingsMassiveUpload,$Path);

        if(strcasecmp($ScanDirResult, 1)==0)
        {
//            $this->DeleteDirectoryMassiveUpload($directorio);
//            $this->DeleteSettingsMassiveUpload($DataBaseName, $UserName);
        }
        
        /* Se limpian los directorios de carga */
//        $this->DeleteDirectoryMassiveUpload($directorio);       
        $this->LogLoadMassive($UserName, "---------------------------------------Carga Finalizada-------------------------------------");
    }    
    
    private function GetSettingsUploadMassive($DataBaseName, $UserName)
    {
        $RoutFile = filter_input(INPUT_SERVER, "DOCUMENT_ROOT");                        
        $SettingsFile = "$RoutFile/Configuracion/MassiveUpload/$DataBaseName/$UserName";
        $FileName = 0;
        
        if(!file_exists($SettingsFile))
        {
            echo "<p>No existe el archivo de sesión de carga masiva</p>";
            return 0;
        }
        
        if(($Scandir = scandir($SettingsFile))==FALSE)
        {
            echo "<p><b>Error</b> al explorar el directorio en busca del archivo de configuración de carga masiva</p><br><br>Detalles:<br><br>$Scandir";
            return 0;
        }

        for($cont = 0; $cont < count($Scandir); $cont++)
        {
            if(is_file("$SettingsFile/".$Scandir[$cont]))
                $FileName = $Scandir[$cont];
        }
        
        $Settings = array();
        if(file_exists("$SettingsFile/$FileName"))
        {
            $Settings = parse_ini_file("$SettingsFile/$FileName", true);
        }
        else
            $Settings = 0;
        
        return $Settings;
    }
        
        /****************************************************************************
     * Recorre el Directorio compartido Publisher para obtener la estructura de
     * Directorios e insertarlo en La BD
     * Se recorre y construye por niveles el árbol de directorios y luego se van insertando 
     * los archivos.
     * 
     * Una vez recorrido el nivel se crea un archivo de .ini donde indica la estructura del nivel
     * con cada uno de los directorios y el Id de carga que le corresponde, este archivo servirá como guía
     * para no repetir el proceso de recorrer el nivel nuevamente por si la carga se vio interrumpida en 
     * algún momento.
     * 
     * Nombre del archivo .ini = level.ini
     * 
     */

    /* Escanea el directorio introducido por el usuario y lo inserta en la BD 
    1ero se realiza el registro del directorio y luego de los archivos que contiene,
     * sino es posible cargar el directorio no se cargan los archivos      */
    
    private function scan_dir($ArrayStructureDefault,$EstructuraProperties,$Catalogos,$NombreUsuario,$dir,$idParent,$ConfigCarga,$Path)
    {
        $RoutFile = filter_input(INPUT_SERVER, "DOCUMENT_ROOT");
        
        $NombreRepositorio=$ConfigCarga['RepositoryName'];
        $DataBaseName=$ConfigCarga['DataBaseName'];
        $files=array();
        $childs=array();
        $root = scandir($dir); 
 
        foreach($root as $value)/* Recorre la fila de directorios */ 
        { 
            if($value === '.' || $value === '..' || $value=='@eaDir') {continue;} 
            
            if(is_file("$dir/$value")) {$files[]=$value;continue;} 
            
            if(is_dir("$dir/$value"))
            {                                               
                /* Se comprueba si el directorio se encuentra registrado */                
                $ExistDir=$this->CheckExistDir($dir, $value);                 
                if($ExistDir>0)
                {
                    $childs[]=array("title"=>$value,"IdParent"=>$ExistDir,"Path"=>$Path."/".$ExistDir); 
                    $this->LogLoadMassive($NombreUsuario, "El directorio \"$value\" existe.");
                }
                else
                {
                    if(!($idParent>0))
                        continue;
                    
                    $IdDirectory = $this->InsertDirectory($DataBaseName, $ConfigCarga['UserName'], $ConfigCarga['RepositoryName'], $idParent, $value, $Path);

                    if($IdDirectory>0)  /* Sí fué insertado */
                    {
                        $RutaBase = "$RoutFile/Estructuras/$DataBaseName/$NombreRepositorio";
                        mkdir($RutaBase."/".$Path."/".$IdDirectory,0777,true);
                        $childs[]=array("title"=>$value,"IdParent"=>$IdDirectory,"Path"=>$Path."/".$IdDirectory); /* Array de Recursividad */
                        $this->RegisterDir($dir,$value,$IdDirectory);
                        $this->LogLoadMassive($NombreUsuario, "Directorio Ingresado: $value");
                    }
                    else  /* Sino se inserto el directorio */
                    {                        
//                        $childs[]=array("title"=>$value,"IdParent"=>0,"Path"=>$Path."/".$value); /* Array de Recursividad */
                        $this->LogLoadMassive($NombreUsuario, "Se detuvo la carga al intentar ingresar el directorio: $value  Mensaje: $IdDirectory");
                        return;
                    }
                }                                                
            }
        }
        
//        echo "-----> Archivos de ".basename($dir)."<br>".var_dump($files)."<------<br><br>";
        $InsertFiles = $this->SearchCoupleFile($ArrayStructureDefault,$EstructuraProperties,$Catalogos,$files, $idParent,$dir,$Path, $ConfigCarga);
        if($InsertFiles==0)
            return 0;
                        
        /*  Se hace recursión con la fila de directorios obtenida */
        for ($cont=0; $cont<count($childs); $cont++)
        {
            $ScanDirResult = $this->scan_dir($ArrayStructureDefault,$EstructuraProperties,$Catalogos,$NombreUsuario,"$dir/".$childs[$cont]['title'],$childs[$cont]['IdParent'],$ConfigCarga,$childs[$cont]['Path']);            
        }        
        
        return $InsertFiles;
    }    
    
    private function InsertDirectory($DataBaseName,$UserName, $RepositoryName,$idParent,$DirectoryTitle,$Path)
    {
        $BD = new DataBase();
        
        $InsertDir="INSERT INTO dir_$RepositoryName (parent_id,title, path) VALUES ($idParent,'$DirectoryTitle','$Path/')";
        $IdDirectory = $BD->ConsultaInsertReturnId($DataBaseName, $InsertDir);
        if(!$IdDirectory>0)        
            $this->LogLoadMassive($UserName, "Se detuvo la carga al intentar ingresar el directorio: $DirectoryTitle  Mensaje: $IdDirectory");
        
        return $IdDirectory;
    }

    
    /* Registra el directorio en el Archivo de Directories.ini  */ 
    private function RegisterDir($Path,$NameDirectory,$IdDirectory)
    {  
       
        if(($config=  fopen($Path."/.Directories.ini", "a+")))
        {
            fwrite($config, basename($NameDirectory)."=\"$IdDirectory\"".PHP_EOL);
            fclose($config);
        }        
    }
    
    /***************************************************************************
     * Se comprueba si el directorio ya ha sido ingresado en el sistema, esto es cuando se 
     * reanuda alguna carga.
     * 
     * Sí existe el directorio se devuelve su Id sino se devuelve False
     */
    
    private function CheckExistDir($Path,$NameDirectory)
    {
        $Exist=false;
        if(file_exists($Path."/.Directories.ini"))
        {
            $Directories=parse_ini_file ($Path."/.Directories.ini");
            if(isset($Directories[$NameDirectory]))
            {
                $Exist=$Directories[$NameDirectory];
            }
        }                
        return $Exist;
    }
    
    /***************************************************************************
     *  Log de la carga Masiva 
     */
    public static function LogLoadMassive($NombreUsuario,$Mensaje)
    {
        $DataBaseName=  filter_input(INPUT_POST, "DataBaseName");
        if(($config = fopen("/volume1/Publisher/$DataBaseName/Log_Carga_de_$NombreUsuario.ini", "a+")))
        {
            fwrite($config, $Mensaje.PHP_EOL);
            fclose($config);
        }        
    }
  
    /***************************************************************************
     *  Se buscan los pares de XML y de PDF dentro de Publisher
     * Parámetros:
     *      ArrayFiles:  Arreglo con todos los pares de archivo
     *      IdDirectory:  Id del directorio donde se quiere cargar
     *      PathDestino: Path del directorio donde se van a mover los archivos
     *      PathBase:   Ruta donde se encuentran los archivos a cargar
     */
    private function clean($string) {
//        $string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.

        return preg_replace('/[^A-Za-z0-9\-\.\_\ ]/', '', $string); // Removes special chars.
    }

    private function SearchCoupleFile($ArrayStructureDefault,$EstructuraProperties,$Catalogos,$ArrayArchivos,$idDirectory,$PathBase,$PathDestino, $MassiveUploadSettings)
    {        
        echo "<br>";
        echo "<p>---------Buscando en ".basename($PathBase)."-----------</p>";
        $DB = new DataBase();
        $XML = new XML();
        
        $SourceKey = $MassiveUploadSettings['SourceMassiveUpload'];        
        $UploadSource = new UploadSources($SourceKey);
        $XsdPath = $UploadSource->GetPathXsd();

        if(!file_exists($XsdPath))
        {
            echo "<p>Se ha detenido la carga masiva debido a que no se encontró el XSD de la carga seleccionada</p>";
            return 0;
        }

        $NombreRepositorio = $MassiveUploadSettings['RepositoryName'];
        $DataBaseName = $MassiveUploadSettings['DataBaseName'];
        $NombreUsuario = $MassiveUploadSettings['UserName'];
        $IdRepositorio = $MassiveUploadSettings['IdRepository'];
        $PathPrincipal = "Estructuras/".$DataBaseName."/";
        $PathDestino = '../'.$PathPrincipal.$NombreRepositorio.$PathDestino.'/'; 
        $IdEmpresa = $MassiveUploadSettings['IdEnterprise'];
        $NombreEmpresa = $MassiveUploadSettings['EnterpriseName'];
        $UserName = $MassiveUploadSettings['UserName'];
        $Today = date("Y-m-d");
        
        if(count($ArrayArchivos)==0)
            echo "<p>Sin documentos en ".basename($PathBase)."</p>";
        
        for($cont=0; $cont<count($ArrayArchivos); $cont++)
        {
            $NombreArchivo=$ArrayArchivos[$cont];

            $NombrePar='';
            $extension = pathinfo($NombreArchivo, PATHINFO_EXTENSION);
            if(strcasecmp($extension,'ini')==0){
                unset($ArrayArchivos[$cont]);
                $ArrayArchivos=array_values($ArrayArchivos);
                $cont=-1;
                continue;
            }     

            /* Cuando no sea extensión XML se busca su nombre par xml */
            if (strcasecmp($extension, 'xml') == 0)
            {
                continue;
            }
            
            $NombrePar = basename($NombreArchivo, '.'.$extension).".xml"; 
            $NOMBRE_PAR = basename($NombreArchivo, '.'.$extension).".XML"; 
                                    
            $KeyResult=false;
            /*********** Se busca el par en el arreglo ***********/
            for($cont2=0; $cont2<count($ArrayArchivos); $cont2++)
            {
                if(strcasecmp($NombrePar, $ArrayArchivos[$cont2])==0)
                {
                    $KeyResult=$cont2;
                    break;
                }
                if(strcasecmp($NOMBRE_PAR, $ArrayArchivos[$cont2])==0)
                {
                    $NombrePar = $NOMBRE_PAR;
                    $KeyResult=$cont2;
                    break;
                }
            }
                /* Sí se encuentra el archivo */
            if($KeyResult!=false)
            {                                                                
                if(($ValidatingXml = $XML->ValidateXml($XsdPath,$PathBase."/".$NombrePar))!=1)
                {
                    unset($ArrayArchivos[$cont]);
                    $ArrayArchivos=array_values($ArrayArchivos);
                    $cont=-1;
                    echo "<p>No pudo ser validado ".basename($NombrePar) ."$ValidatingXml</p>";
                    $this->LogLoadMassive($UserName, "No pudo ser validado ".basename($NombrePar) ."$ValidatingXml");
                    continue;
                }
                
                $renombrado=0;
                $PathDestinoArchivo=$PathDestino.$this->clean($NombreArchivo);   /* PDF */
                $PathDestinoXml=$PathDestino.$NombrePar;           /* XML */
                
                echo "<p> Procesando a ".basename($PathDestinoArchivo) ."</p>";
                echo "<p> Procesando ". basename($PathDestinoXml)."</p>";
                
                /* Se renombran los archivos si existen */
                if(file_exists($PathDestinoArchivo))
                {
//                    $OldXmlName = pathinfo(basename($PathDestinoXml), PATHINFO_FILENAME);        
                    $OldExtensionXml = pathinfo($PathDestinoXml, PATHINFO_EXTENSION);

//                    $OldPdfName = pathinfo($PathDestinoArchivo, PATHINFO_FILENAME);        
                    $OldExtensionPdf = pathinfo($PathDestinoArchivo, PATHINFO_EXTENSION);
                    
                    $renombrado=1;
                    $RenameName = $this->RenameFile(dirname($PathDestinoArchivo), basename($PathDestinoArchivo));
//                    echo "<p>RenaName = $RenameName</p>";
                    $NewXmlName = pathinfo($RenameName,PATHINFO_FILENAME);
//                    echo "<p>NewXmlName = $NewXmlName</p>";
                    $PathDestinoXml = dirname($PathDestinoArchivo)."/.$NewXmlName.$OldExtensionXml";
                    $PathDestinoArchivo = dirname($PathDestinoXml)."/".$NewXmlName.".$OldExtensionPdf";
//                    echo "<p>xml = $PathDestinoXml</p>";
//                    echo "<p>pdf = $PathDestinoArchivo</p>";
                }                                                
//                continue;
                if($renombrado==1)
                {
                    echo "<p>Se renombró el archivo $NombreArchivo a ".basename($PathDestinoArchivo)."</p>";
                    $this->LogLoadMassive($UserName, "Se renombró el archivo $NombreArchivo a ".basename($PathDestinoArchivo));
                }
                
                $XmlValues = $UploadSource->ReadXml($ArrayStructureDefault, $EstructuraProperties, $NombreRepositorio, $Catalogos, $PathBase."/".$NombrePar, $MassiveUploadSettings);
                
                if(!is_array($XmlValues))
                    continue;
                
                if(is_array($XmlValues))
                    if($XmlValues['Estado']!=1)
                        continue;                                                                                    
                
                $FieldsChain = trim($XmlValues['Fields'],",");
                $ValuesChain = trim($XmlValues['Values'], ",");
                
                $Full = trim($XmlValues['Full']).", $UserName, $extension, $Today, ".basename($PathDestinoArchivo);
                
                if(count($FieldsChain)>0)
                    $FieldsChain.=", IdDirectory, IdEmpresa, RutaArchivo,UsuarioPublicador,TipoArchivo,FechaIngreso,NombreArchivo,Full";
                if(count($ValuesChain)>0)
                    $ValuesChain.=", $idDirectory, $IdEmpresa, '$PathDestinoArchivo', '$UserName', '$extension', '$Today', '".basename($PathDestinoArchivo)."', '$Full'";                                               
                
                if(!($RenamXml = rename($PathBase."/$NombreArchivo", $PathDestinoArchivo)))
                {
                    $this->LogLoadMassive($NombreUsuario, "Error al mover el documento $NombreArchivo a su destino.".PHP_EOL."$RenamXml".PHP_EOL);
                    continue;
                }
                
                if(!($RenamePdf = rename($PathBase."/$NombrePar", $PathDestinoXml)))
                {
                    $this->LogLoadMassive($NombreUsuario, "Error al mover el documento $NombreArchivo a su destino.".PHP_EOL."$RenamePdf".PHP_EOL);
                    continue;
                }
                
                $InsertIntoRepository = "INSERT INTO $NombreRepositorio ($FieldsChain) VALUES ($ValuesChain)"; 

                $IdFile = $DB->ConsultaInsertReturnId($DataBaseName, $InsertIntoRepository);
                if(!($IdFile>0))
                {
                    $this->LogLoadMassive($NombreUsuario, "Error al cargar el archivo $NombreArchivo.".PHP_EOL."Cadena de valores $ValuesChain".PHP_EOL.$IdFile.PHP_EOL);
                    continue;
                }                                
                
                $InsertIntoGlobal = "INSERT INTO RepositorioGlobal (IdFile, IdEmpresa, IdRepositorio, NombreEmpresa, "
                    . "NombreRepositorio, IdDirectory, NombreArchivo, TipoArchivo, RutaArchivo,UsuarioPublicador, FechaIngreso, Full) "
                    . "VALUES ($IdFile, $IdEmpresa, $IdRepositorio, '$NombreEmpresa', '$NombreRepositorio', $idDirectory, "
                    . "'$NombreArchivo', '$extension', '$PathDestinoArchivo', '$NombreUsuario', '$Today', '$Full')";
                    
                    $ResultInsertGlobal = $DB ->ConsultaInsertReturnId($DataBaseName, $InsertIntoGlobal);
                    
                    if($ResultInsertGlobal>0)
                    {                                                                            
                        $this->LogLoadMassive($NombreUsuario, "Archivo $NombreArchivo Cargado en $NombreRepositorio");
                        echo "Documento  <b>$NombreArchivo</b> ingresado al sistema ";
                    }
                    else
                    {
                        $this->LogLoadMassive($NombreUsuario, "Error al cargar el archivo $NombreArchivo. $ResultInsertGlobal. ".PHP_EOL."Consulta Completa:".$InsertIntoGlobal.PHP_EOL);
                        $DeleteFile = "DELETE FROM $NombreRepositorio WHERE IdRepositorio=$IdFile";
                        $DB->ConsultaQuery($DataBaseName, $DeleteFile);
                    }
                                
                unset($ArrayArchivos[$KeyResult]);
                unset($ArrayArchivos[$cont]);

                $ArrayArchivos = array_values($ArrayArchivos);
                $cont=-1;   
                    
            }             
        }

        return 1;
    }
    
    private function RenameFile($destination,$NewRouteDestinationXml)
    {
        $increment = 1; 
        $name = pathinfo($NewRouteDestinationXml, PATHINFO_FILENAME);
        $extension = pathinfo($NewRouteDestinationXml, PATHINFO_EXTENSION);
        while(file_exists($destination."/".$name . $increment . '.' . $extension)) {
            $increment++;
        }

        $basename = $name . $increment . '.' . $extension;
        return $basename;
    }    
    
    /* Retorna los errores que ocurren durante la validación de un XML */
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
            
    private function deleteDirectory($dir)
    {
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
}

$MassiveUpload = new MassiveUpload();
