<?php

/**
 * Description of Repository
 *
 * @author daniel
 */
$RoutFile = dirname(getcwd());

require_once 'DataBase.php';
require_once 'XML.php';
require_once 'Log.php';
require_once 'DesignerForms.php';
require_once 'Session.php';
require_once 'Catalog.php';

if (!isset($_SESSION))
    session_start();

class Repository {

    protected $database;

    public function __construct() {
        $this->database = new DataBase();
        $this->Ajax();
    }

    private function Ajax() {
        if (filter_input(INPUT_POST, "opcion") != NULL and filter_input(INPUT_POST, "opcion") != FALSE) {

            $idSession = Session::getIdSession();

            if ($idSession == null)
                return XML::XMLReponse("Error", 0, "Repository::No existe una sesión activa, por favor vuelva a iniciar sesión");

            $userData = Session::getSessionParameters();

            switch (filter_input(INPUT_POST, "opcion")) {
                case 'GetListRepositories': $this->GetListRepositories($userData);
                    break;
                //            case 'GetRepositoriesDetail': $this->GetRepositoriesDetail();
                //                break;
                case 'NewRepository': $this->NewRepository();
                    break;
                case 'AddNewFieldToRepository': $this->AddNewFieldToRepository($userData);
                    break;
                case 'DeleteRepositoryField': $this->DeleteRepositoryField();
                    break;
                case 'XMLInsertRepositorio': $this->XMLInsertRepositorio();
                    break;
                case 'DeleteRepository': $this->DeletingRepository();
                    break;
                case 'getRepositoriesByEnterprise': $this->getRepositoriesByEnterprise($userData);
                    break;
            }
        }
    }

    private function XMLInsertRepositorio() {
//        $DataBaseName = filter_input(INPUT_POST, "DataBaseName");
//        $IdUser = filter_input(INPUT_POST, "IdUser");        
//        $UserName = filter_input(INPUT_POST, "UserName");

        $XML = new XML();
        $BD = new DataBase();

        $XmlPath = $XML->validacion_xml();

        if (strcasecmp($XmlPath, 0) == 0)
            return;

        if (!($xml = simplexml_load_file($XmlPath)))
            return XML::XMLReponse("Error", 0, "<p><b>Error</b> no pudo abrirse el XML</p><br><br>$XmlPath");

        foreach ($xml->children() as $valor) {
            $array_nodos[] = $valor->getName();
        }

        if (array_search('EstructuraRepositorio', $array_nodos) !== false) {
            /* Estrucutra Completa de CrearRepositorio */
            $Repositorio = $xml->EstructuraRepositorio->CrearEstructuraRepositorio;
            /* Estructura para la creación del repositorio */
            $InsertRepositorio = $BD->crear_repositorio($Repositorio);
            echo "<p>Resultado Final: $InsertRepositorio</p>";
        } else
            return $XML->ResponseXML("Erro", 0, "No se encontro una estructura para insertar");
    }

    private function DeleteRepositoryField() {
        $DB = new DataBase();

        $DataBaseName = filter_input(INPUT_POST, "DataBaseName");
//        $IdUser = filter_input(INPUT_POST, "IdUser");
//        $UserName = filter_input(INPUT_POST, "UserName");
//        $IdRepository = filter_input(INPUT_POST, "IdRepository");
        $RepositoryName = filter_input(INPUT_POST, "RepositoryName");
        $FieldName = filter_input(INPUT_POST, "FieldName");
        $RoutFile = filter_input(INPUT_SERVER, "DOCUMENT_ROOT"); /* /var/services/web */


        if (!file_exists("$RoutFile/Configuracion/$DataBaseName.ini"))
            return XML::XMLReponse("Error", 0, "<p><b>Error</b> no existe el registro de estructura de la intsnaica <b>$DataBaseName</b></p>");

        if (!($Structure = parse_ini_file("$RoutFile/Configuracion/$DataBaseName.ini")))
            return XML::XmlArrayResponse("Error", 0, "<p><b>Error</b> al intentar abrir el registro de estructura de la instancia <b>$DataBaseName</b></p>");

        if (!($gestor = fopen("$RoutFile/Configuracion/$DataBaseName.ini", "w")))
            return XML::XMLReponse("Error", 0, "<p><b>Error</b> al intentar abrir el registro de estructura de la instancia <b>$DataBaseName</b><br>Detalles:<br><br>$gestor");

        foreach ($Structure as $key => $Section) {
            fwrite($gestor, ";#############################################################################" . PHP_EOL);
            fwrite($gestor, ";--------  $key --------" . PHP_EOL);
            fwrite($gestor, ";#############################################################################" . PHP_EOL);
            fwrite($gestor, "$key=$key" . PHP_EOL);
            for ($cont = 0; $cont < count($Section); $cont++) {
                $property = explode("###", $Section[$cont]);
                if (strcasecmp($RepositoryName, $key) == 0)
                    if (strcasecmp($property[0], "Properties") == 0) {
                        $Field = explode(" ", $property[1]);
                        if (strcasecmp($FieldName, $Field[1]) == 0)
                            continue;
                    }
                fwrite($gestor, $key . "[]=" . $Section[$cont] . PHP_EOL);
            }
        }

        fclose($gestor);

        $AlterTable = "ALTER TABLE $RepositoryName DROP COLUMN $FieldName";
        if (($AlterTableResult = $DB->ConsultaQuery($DataBaseName, $AlterTable)) != 1)
            return XML::XMLReponse("Error", 0, "<p><b>Error</b> al eliminar la columna <b>$FieldName</b></p><br>Detalles:<br><br>$AlterTableResult");

        XML::XMLReponse("DeletedField", 1, "Campo $FieldName eliminado con éxito");
    }

    private function AddNewFieldToRepository($user) {
        $DB = new DataBase();

        $DataBaseName = $user['dataBaseName'];
//        $IdUser = filter_input(INPUT_POST, "IdUser");
//        $UserName = filter_input(INPUT_POST, "UserName");
//        $IdRepository = filter_input(INPUT_POST, "IdRepository");
        $RepositoryName = filter_input(INPUT_POST, "RepositoryName");
        $FieldName = filter_input(INPUT_POST, "FieldName");
        $FieldType = filter_input(INPUT_POST, "FieldType");
        $FieldTypeMysql = filter_input(INPUT_POST, "FieldType");
        $FieldLength = filter_input(INPUT_POST, "FieldLength");
        $Required = filter_input(INPUT_POST, "RequiredField");
//        var_dump($_POST);

        if ((int) ($FieldLength) > 0) {
            $FieldTypeMysql = $FieldTypeMysql . "($FieldLength) ";
            $FieldLength = "long " . $FieldLength . "###";
        } else
            $FieldLength = "";

        $NewProperty = "Properties###name $FieldName###type $FieldType###" . $FieldLength . "required $Required###";

        if (strcasecmp($Required, "true") == 0)
            $Required = "NOT NULL";
        else
            $Required = '';

        $AlterTable = "ALTER TABLE $RepositoryName ADD COLUMN $FieldName $FieldTypeMysql $Required";

        if (($AlterRes = $DB->ConsultaQuery($DataBaseName, $AlterTable)) != 1)
            return XML::XMLReponse("Error", 0, "<p><b>Error</b> al agregar el nuevo campo <b>$FieldName</b></p><br>Detalles:<br><br>$AlterRes");

        $AlterTableTemp = "ALTER TABLE temp_rep_$RepositoryName ADD COLUMN $FieldName $FieldTypeMysql $Required";

        if (($AlterRes = $DB->ConsultaQuery($DataBaseName, $AlterTableTemp)) != 1) {
            $DropColumn = "ALTER TABLE $RepositoryName DROP COLUMN $FieldName";
            if (($DropResult = $DB->ConsultaQuery($DataBaseName, $DropColumn)) != 1)
                return XML::XMLReponse("Error", 0, "<p><b>Error</b> al agregar al registro de estructura el nuevo campo '$FieldName'. No fué posible eliminar la columna posteriormente, debe reportarlo a CSDocs</p><br>Detalles:<br><br>$DropResult");
            else
                return XML::XMLReponse("Error", 0, "<p><b>Error</b> al agregar el nuevo campo <b>$FieldName</b></p><br>Detalles:<br><br>$AlterRes");
        }

        if (($AddField = DesignerForms::AddPropertyIntoStructureConfig($DataBaseName, $RepositoryName, $NewProperty)) != 1) {
            $DropColumn = "ALTER TABLE $RepositoryName DROP COLUMN $FieldName";
            if (($DropResult = $DB->ConsultaQuery($DataBaseName, $DropColumn)) != 1)
                return XML::XMLReponse("Error", 0, "<p><b>Error</b> al agregar al registro de estructura el nuevo campo '$FieldName'. No fué posible eliminar la columna posteriormente, debe reportarlo a CSDocs</p><br>Detalles:<br><br>$DropResult");
            else
                return XML::XMLReponse("Error", 0, "<p><b>Error</b> al agregar al registro de estructura el nuevo campo '$FieldName'</p><br>Detalles:<br><br>$AddField");
        }

        XML::XMLReponse("AddedField", 1, "Campo $FieldName agregado correctamente");
    }

    private function NewRepository() {
        $DB = new DataBase();

//        $DataBaseName = filter_input(INPUT_POST, "DataBaseName");
//        $IdUser = filter_input(INPUT_POST, "IdUser");
//        $UserName = filter_input(INPUT_POST, "UserName");
        $XMLResponse = filter_input(INPUT_POST, "Xml");
        $xml = simplexml_load_string($XMLResponse);

        /*   Se complementa el xml con los datos DefaultStructProperties     */

        $xml->CrearEstructuraRepositorio->addChild("DefaultStructProperties");
        $DefaultStructProperties = $xml->CrearEstructuraRepositorio->DefaultStructProperties;

        $DefaultStructProperties->addChild('TipoArchivo');
        $DefaultStructProperties->TipoArchivo->addAttribute("type", "VARCHAR");
        $DefaultStructProperties->TipoArchivo->addAttribute("long", "10");
        $DefaultStructProperties->TipoArchivo->addAttribute("required", "true");

        $DefaultStructProperties->addChild('RutaArchivo');
        $DefaultStructProperties->RutaArchivo->addAttribute("type", "TEXT");
        $DefaultStructProperties->RutaArchivo->addAttribute("required", "true");

        $DefaultStructProperties->addChild('UsuarioPublicador');
        $DefaultStructProperties->UsuarioPublicador->addAttribute("type", "VARCHAR");
        $DefaultStructProperties->UsuarioPublicador->addAttribute("long", "20");
        $DefaultStructProperties->UsuarioPublicador->addAttribute("required", "true");

        $DefaultStructProperties->addChild('FechaIngreso');
        $DefaultStructProperties->FechaIngreso->addAttribute("type", "DATE");
        $DefaultStructProperties->FechaIngreso->addAttribute("required", "true");

        $DefaultStructProperties->addChild('ResumenExtract');
        $DefaultStructProperties->ResumenExtract->addAttribute("type", "TEXT");
        $DefaultStructProperties->ResumenExtract->addAttribute("required", "true");

        $DefaultStructProperties->addChild('Autor');
        $DefaultStructProperties->Autor->addAttribute("type", "VARCHAR");
        $DefaultStructProperties->Autor->addAttribute("long", "100");
        $DefaultStructProperties->Autor->addAttribute("required", "true");

        $DefaultStructProperties->addChild('Topografia');
        $DefaultStructProperties->Topografia->addAttribute("type", "INT");
        $DefaultStructProperties->Topografia->addAttribute("long", "10");
        $DefaultStructProperties->Topografia->addAttribute("required", "true");

        $DefaultStructProperties->addChild('Clasificacion');
        $DefaultStructProperties->Clasificacion->addAttribute("type", "VARCHAR");
        $DefaultStructProperties->Clasificacion->addAttribute("long", "250");
        $DefaultStructProperties->Clasificacion->addAttribute("required", "true");

        $DefaultStructProperties->addChild('Gestion');
        $DefaultStructProperties->Gestion->addAttribute("type", "INT");
        $DefaultStructProperties->Gestion->addAttribute("long", "10");
        $DefaultStructProperties->Gestion->addAttribute("required", "true");

        $DefaultStructProperties->addChild('Expediente');
        $DefaultStructProperties->Expediente->addAttribute("type", "INT");
        $DefaultStructProperties->Expediente->addAttribute("long", "10");
        $DefaultStructProperties->Expediente->addAttribute("required", "true");

        $DefaultStructProperties->addChild('NombreArchivo');
        $DefaultStructProperties->NombreArchivo->addAttribute("type", "VARCHAR");
        $DefaultStructProperties->NombreArchivo->addAttribute("long", "100");
        $DefaultStructProperties->NombreArchivo->addAttribute("required", "true");

        $DefaultStructProperties->addChild('Full');
        $DefaultStructProperties->Full->addAttribute("type", "TEXT");
        $DefaultStructProperties->Full->addAttribute("required", "true");

        $CreateRepository = $DB->crear_repositorio($xml->CrearEstructuraRepositorio);
        echo "<p>$CreateRepository</p>";
    }

    private function GetListRepositories($userData) {

        $DataBaseName = $userData['dataBaseName'];
        $IdGroup = $userData['idGroup'];
        $idUser = $userData['idUser'];
        $userName = $userData['userName'];

        $EnterpriseKey = filter_input(INPUT_POST, "EnterpriseKey");

        $Repositories = $this->GetRepositoriesList($DataBaseName, $EnterpriseKey, $IdGroup, $idUser);

        if (is_array($Repositories))
            XML::XmlArrayResponse("Repositories", "Repository", $Repositories);
        else
            return XML::XMLReponse("Error", 0, "<p><b>Error</b> al obtener el listado de repositorios.</p><br>Detalles:<br><br>" . $Repositories);
    }

    public function GetRepositoriesList($DataBaseName, $EnterpriseKey, $IdGroup, $idUser) {
        $BD = new DataBase();

        if (strcasecmp($EnterpriseKey, 0) == 0)
            $query = "SELECT *FROM CSDocs_Repositorios";
        else
            $query = "SELECT  em.IdEmpresa, re.IdRepositorio, re.NombreRepositorio FROM CSDocs_Repositorios re "
                    . "INNER JOIN RepositoryControl rc ON rc.IdRepositorio = re.IdRepositorio "
                    . "INNER JOIN CSDocs_Empresas em on re.ClaveEmpresa=em.ClaveEmpresa "
                    . "WHERE rc.IdGrupo = $IdGroup AND re.ClaveEmpresa = '$EnterpriseKey'";

        $ResultSelect = $BD->ConsultaSelect($DataBaseName, $query);
        if ($ResultSelect['Estado'] != 1)
            return $ResultSelect['Estado'];

        return $ResultSelect['ArrayDatos'];
    }

    function DeletingRepository() {
        $DataBaseName = filter_input(INPUT_POST, "DataBaseName");
        $IdRepositorio = filter_input(INPUT_POST, "IdRepositorio");
        $IdGroup = filter_input(INPUT_POST, "IdGroup");
        $IdUsuario = filter_input(INPUT_POST, "IdUsuario");
        $NombreUsuario = filter_input(INPUT_POST, "NombreUsuario");
        $IdEnterprise = filter_input(INPUT_POST, "IdEnterprise");
        $IdRepository = filter_input(INPUT_POST, "IdRepository");
        $RepositoryName = filter_input(INPUT_POST, "RepositoryName");


        If (($DeletingResult = $this->DeleteRepository($DataBaseName, $IdEnterprise, $IdRepository, $RepositoryName)) != 1)
            return XML::XMLReponse("Error", 0, $DeletingResult);

        XML::XMLReponse("DeletedRepository", 1, "Repositorio eliminado con éxito");
    }

    /* Función llamda desde Enterprise.php
     * @DeleteForEnterprise: bandera utilizada para indicar que la eliminación de documentos de uno o varios rep de la tabla
     *                                   Global y del registro de repositorios (Tabla Repositorios)  se realizará en una sola consulta y no en varias */

    public function DeleteRepository($DataBaseName, $IdEnterprise, $IdRepository, $RepositoryName, $DeleteForEnterprise = 0) {
        $DB = new DataBase();
        $Catalog = new Catalog();

        $RoutFile = dirname(getcwd());
        $RepositoryPath = "$RoutFile/Estructuras/$DataBaseName/$RepositoryName";

        /* Eliminando catálogos del repositorio */
        $catalogs = $Catalog->getCatalogsArray($DataBaseName, $IdRepository);

        if (!is_array($catalogs))
            return "Error al obtener el listado de catálogos. Detalles: $catalogs";

        for ($cont = 0; $cont < count($catalogs); $cont++) {
            if (($resultDeleteCatalog = $Catalog->deleteCatalog($DataBaseName, $IdRepository, $RepositoryName, $catalogs[$cont]['NombreCatalogo'])) != 1)
                return "$resultDeleteCatalog";
        }

        if (!is_array($catalogs))
            return "<b>Error</b> al recuperar los catálogos del repositorio.<br><br>Detalles: $catalogs";

        if (file_exists($RepositoryPath))
            exec("rm -R $RepositoryPath");

        if (($DeleteStructure = DesignerForms::DeleteStructure($DataBaseName, $RepositoryName)) != 1)
            return "No pudo eliminarse la empresa desde el registro de estructura";

        /* Eliminando Repositorio */
        $DropRepository = "DROP TABLE IF EXISTS $RepositoryName  ";

        if (($ResultDropRepository = $DB->ConsultaQuery($DataBaseName, $DropRepository)) != 1)
            return "<p><b>Error</b> al eliminar el repositorio </p><br>Detalles:<br><br>$ResultDropRepository";

        /* Eliminando Tabla de directorios del Repositorio */
        $DropDirTable = "DROP TABLE IF EXISTS  dir_$RepositoryName ";

        if (($ResultDropDirTable = $DB->ConsultaQuery($DataBaseName, $DropDirTable)) != 1)
            return "<p><b>Error</b> al eliminar tabla de directorios del repositorio <b>$RepositoryName</b></p><br>Detalles:<br><br>$ResultDropDirTable";

        /* Eliminando Trash del Repositorio */
        $DropDirTrash = "DROP TABLE IF EXISTS temp_dir_$RepositoryName ";

        if (($ResultDropDirTrash = $DB->ConsultaQuery($DataBaseName, $DropDirTrash)) != 1)
            return "<p><b>Error</b>: al eliminar trash de directorios del repositorio <b>$RepositoryName</b> </p><br>Detalles:<br><br> $ResultDropDirTrash";

        $DropRepTrash = "DROP TABLE  IF EXISTS temp_rep_$RepositoryName ";

        if (($ResultDropRepTrash = $DB->ConsultaQuery($DataBaseName, $DropRepTrash)) != 1)
            return "<p><b>Error</b> al eliminar trash del repositorio <b>$RepositoryName</b></p><br>Detalles:<br><br>$ResultDropRepTrash";

        $DeletingOfGlobal = "";

        if ($DeleteForEnterprise == 0) {
            /* Eliminando del repositorio Global  todos los documentos del repositorio */

            $DeletingOfGlobal = "DELETE FROM RepositorioGlobal WHERE IdRepositorio = $IdRepository AND IdEmpresa = $IdEnterprise";

            if (($ResultDeletingOfGlobal = $DB->ConsultaQuery($DataBaseName, $DeletingOfGlobal)) != 1)
                return "<p><b>Error</b> al intentar eliminar los documentos del repositorio <b>$RepositoryName</b> localizados en Global</p><br>Detalles:<br><br>$ResultDeletingOfGlobal";

            $DeletingOfRepository = "DELETE FROM CSDocs_Repositorios WHERE IdRepositorio = $IdRepository";
            if (($ResultDeletingOfRepository = $DB->ConsultaQuery($DataBaseName, $DeletingOfRepository)) != 1)
                return "<p><b>Error</b> al intentar eliminar el repositorio <b>$RepositoryName</b> del registro de repositorios</p><br>Detalles:<br><br>$ResultDeletingOfRepository";
        }

        return 1;
    }

    /* Devuelve el detalle de empresas y repositorios a partir del nombre de un repositorio */

    function getEnterprisesAndRepositoriesDetail($instanceName, $repositoryName) {
        $DB = new DataBase();

        $query = "SELECT em.IdEmpresa, em.ClaveEmpresa, re.IdRepositorio, re.NombreRepositorio FROM CSDocs_Repositorios re
            RIGHT JOIN CSDocs_Empresas em ON re.ClaveEmpresa = em.ClaveEmpresa
            WHERE re.NombreRepositorio = '$repositoryName' ";

        $resultQuery = $DB->ConsultaSelect($instanceName, $query);

        if ($resultQuery['Estado'] != 1)
            return $resultQuery['Estado'];
        else
            return $resultQuery['ArrayDatos'];
    }

    function getRepositoriesByEnterprise($userData) {
        $DataBaseName = $userData['dataBaseName'];
        $IdGroup = $userData['idGroup'];
        $idUser = $userData['idUser'];
        $userName = $userData['userName'];

        $query = "SELECT  em.IdEmpresa, re.IdRepositorio, re.NombreRepositorio FROM CSDocs_Repositorios re "
                . "INNER JOIN RepositoryControl rc ON rc.IdRepositorio = re.IdRepositorio "
                . "INNER JOIN CSDocs_Empresas em on re.ClaveEmpresa=em.ClaveEmpresa "
                . "WHERE rc.IdGrupo = $IdGroup";

        $res = $this->database->ConsultaSelect($DataBaseName, $query);
        
        if ($res['Estado'] != 1)
            return XML::XMLReponse("Error", 0, "<p><b>Error</b> al obtener los repositorios por empresa. " . $res['Estado'] . "</p>");
        
        return XML::XmlArrayResponse("repositoriesByCompany", "repository", $res["ArrayDatos"]);
    }

}

$Repository = new Repository();
