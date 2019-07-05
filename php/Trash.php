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

            case 'RestoreDirectories': $this->RestoreDirectories(); break;

            case 'DeleteDirectories': $this->DeleteDirectories(); break;
            case 'DeleteFiles': $this->DeleteFiles(); break;

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
        $Path=$xml->Directory->Path;

        $Route="../Estructuras/$DataBaseName/$NombreRepositorio".$Path.$IdDirectory;

        $QueryDeleteDirectoryGlobal="DELETE FROM RepositorioGlobal WHERE IdRepositorio=$IdRepositorio AND NombreRepositorio='$NombreRepositorio' AND IdDirectory IN (select * from (select IdDirectory from dir_$NombreRepositorio , (select @pv := '$IdDirectory') initialisation where find_in_set(parent_id, @pv) and length(@pv := concat(@pv, ',', IdDirectory)) or IdDirectory=$IdDirectory) dirs)";
        $deleteGlobal=$BD->ConsultaQuery($DataBaseName, $QueryDeleteDirectoryGlobal);
        if($deleteGlobal==1){
            $QueryDeleteDirectory="DELETE drb, rb FROM dir_$NombreRepositorio AS drb LEFT JOIN $NombreRepositorio AS rb USING(IdDirectory) WHERE drb.IdDirectory IN (select * from (select IdDirectory from dir_$NombreRepositorio , (select @pv := '$IdDirectory') initialisation where find_in_set(parent_id, @pv) and length(@pv := concat(@pv, ',', IdDirectory)) or IdDirectory=$IdDirectory) dirs)";
            $delete=$BD->ConsultaQuery($DataBaseName, $QueryDeleteDirectory);
            if($delete==1){
                $this->DeleteDirNas($Route);
//                rmdir("$Route");
                return $XML->ResponseXML("Delete", 0, "Directorio eliminado con éxito");
            }else{
                return $XML->ResponseXML("Error", 0, $delete);
            }

        }else{
            return $XML->ResponseXML("Error", 0, $deleteGlobal);
        }


    }

    private function DeleteDirNas($Path){
        foreach(glob($Path . "/*") as $element){
            if (is_dir($element)){
                $this->DeleteDirNas($element);
            }else{
                unlink($element);
            }
        }
        rmdir($Path);
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

            $NombreArchivo=$xml->File->NombreArchivo;
            $IdFile=$xml->File->IdRepositorio;
            $IdDirectory=$xml->File->IdDirectory;
            $IdEmpresa=$xml->File->IdEmpresa;
            $Path=$xml->File->RutaArchivo;


        $QueryDeleteFile="DELETE FROM $NombreRepositorio WHERE IdRepositorio=$IdFile AND status = 0";
        $delete=$BD->ConsultaQuery($DataBaseName, $QueryDeleteFile);

        if($delete==1){
            $QueryDeleteFileGlobal="DELETE FROM RepositorioGlobal WHERE IdFile=$IdFile AND IdEmpresa=$IdEmpresa AND IdRepositorio=$IdRepositorio AND IdDirectory=$IdDirectory";
            $deleteGlobal=$BD->ConsultaQuery($DataBaseName, $QueryDeleteFileGlobal);

            if($deleteGlobal==1){
                unlink("$Path");
                return $XML->ResponseXML("Delete", 0, "Archivo eliminado con éxito");
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

            $Path = $doc->createElement("Path",$ResultadoConsulta[$cont][3]);
            $Directory->appendChild($Path);


            $root->appendChild($Directory);
        }
        header ("Content-Type:text/xml");
        echo $doc->saveXML();

    }

    private function getDirectoriesQuery($DataBaseName, $NombreRepositorio, $IdUsuario, $NombreUsuario){
        $BD= new DataBase();
        $QueryDirectoriesTrashed="SELECT parent_id, IdDirectory, title, path FROM dir_$NombreRepositorio WHERE status=0 AND parent_id NOT IN(SELECT IdDirectory FROM dir_$NombreRepositorio WHERE status=0)";

        /* Los administradores pueden ver todos los elementos de la papelera */
        if(strcasecmp($NombreUsuario, "root")==0)
        {
            $QueryDirectoriesTrashed="SELECT parent_id, IdDirectory, title, path FROM dir_$NombreRepositorio WHERE status=0 AND parent_id NOT IN(SELECT IdDirectory FROM dir_$NombreRepositorio WHERE status=0)";

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
        $db = new DataBase();
        $RoutFile = dirname(getcwd());
        $XmlRestore = filter_input(INPUT_POST, 'XmlRestore');
        $XML =new XML();
        //$Log = new Log();

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

        $QueryRestoreFile="UPDATE $NombreRepositorio SET status=1 WHERE idRepositorio=$IdFile";

        $restore = $db->ConsultaQuery($DataBaseName, $QueryRestoreFile);

        if($restore==1)
        {
            $QueryRestoreFileGlobal="UPDATE RepositorioGlobal SET status=1 WHERE IdEmpresa=$IdEmpresa AND IdRepositorio=$IdRepositorio AND IdDirectory=$IdDirectory AND idFile=$IdFile";
            $restoreFileGlobal=$db->ConsultaQuery($DataBaseName,$QueryRestoreFileGlobal);
            if($restoreFileGlobal==1)
            {
                Log::WriteEvent("27", $IdUsuario, $NombreUsuario, $xml->File->NombreArchivo, $DataBaseName);

                return $XML->ResponseXML("Restore", 0, "Archivo restaurado con éxito.");
            }else {
                return XML::XMLReponse("Error", 0, "No fue posible restaurar el archivo.".$restoreFileGlobal);
            }
        }else {
            return XML::XMLReponse("Error", 0, "No fue restaurar eliminar el archivo.".$restore);
        }

    }

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

//        foreach ($xml->Directory as $nodo){
            $IdDirectory = $xml->Directory->IdDirectory;
//        }

        $QueryRestoreDirectory="UPDATE dir_$NombreRepositorio LEFT JOIN $NombreRepositorio ON dir_$NombreRepositorio.IdDirectory=$NombreRepositorio.IdDirectory
                        SET dir_$NombreRepositorio.status=1,$NombreRepositorio.status=1
                        WHERE dir_$NombreRepositorio.IdDirectory IN
                        (SELECT * FROM (SELECT IdDirectory FROM dir_$NombreRepositorio , (SELECT @pv := '$IdDirectory') initialisation WHERE find_in_set(parent_id, @pv) and length(@pv := concat(@pv, ',', IdDirectory)) or IdDirectory=$IdDirectory) dirs)
       ";
        $restore = $BD->ConsultaQuery($DataBaseName, $QueryRestoreDirectory);

        if ($restore == 1){
            $QueryRestoreFileGlobal="UPDATE RepositorioGlobal SET status=1
                                   WHERE IdRepositorio=$IdRepositorio AND NombreRepositorio='$NombreRepositorio' AND IdDirectory
                                   IN (SELECT * FROM (SELECT IdDirectory FROM dir_$NombreRepositorio , (SELECT @pv := '$IdDirectory') initialisation WHERE find_in_set(parent_id, @pv) and length(@pv := concat(@pv, ',', IdDirectory)) OR IdDirectory=$IdDirectory) dirs)
                                    ";
            $restoreFileGlobal=$BD->ConsultaQuery($DataBaseName, $QueryRestoreFileGlobal);

            if ($restoreFileGlobal == 1){
                Log::WriteEvent("22", $IdUsuario, $NombreUsuario, $xml->Directory->title);

                return XML::XMLReponse("Restore", 0, "Directorio restaurado con éxito");
            }else{
                return XML::XMLReponse("Error", 0, "No fue posible restaurar el directorio".$restoreFileGlobal);
            }
        }

        return XML::XMLReponse ("Error", 0, "No fue posible restaurar el directorio");

    }

}

$Trash = new Trash();
