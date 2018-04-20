<?php

/*
 * Clase que administra la Base de Datos
 */

/**
 *
 * @author daniel
 */
$RoutFile = dirname(getcwd());

class DataBase {

    public static $idDataBaseName = 0;
    public static $dataBaseName = null;

    function Conexion() {
        $RoutFile = dirname(getcwd());

        if (!file_exists($RoutFile . "/Configuracion/ConexionBD/BD.ini")) {
            echo "<p>No existe el archivo de Conexión.</p>";
            return 0;
        }

        $Conexion = parse_ini_file($RoutFile . "/Configuracion/ConexionBD/BD.ini", true);

        $User = $Conexion['Conexion']['User'];
        $Password = $Conexion['Conexion']['Password'];
        $Host = $Conexion['Conexion']['Host'];
        error_reporting(E_ALL ^ E_DEPRECATED);
        $enlace = mysqli_connect($Host, $User, $Password);
        mysqli_set_charset($enlace, 'utf8');
        return $enlace;
    }

    /*     * *************************************************************************
     * 
     * Se crea una Instancia llamada CS-DOCS la cual contiene una tabla llamada Instancias
     * Esta tabla se utiliza para llevar un registro de instancias creadas por el administrador.
     * 
     * ************************************************************************* */

    function CreateInstanciaCSDOCS() {

        $CreateCsDocs = "CREATE DATABASE IF NOT EXISTS `cs-docs` /*!40100 DEFAULT CHARACTER SET utf8 */";
        if (($ResultCreateCsDocs = $this->crear_tabla("", $CreateCsDocs)) != 1) {
            echo "<p><b>Error</b> al crear la instancia CSDocs $ResultCreateCsDocs</p>";
            return;
        }

        $CreateInstances = "CREATE TABLE IF NOT EXISTS `instancias` (IdInstancia INT(11) NOT NULL AUTO_INCREMENT,"
                . "NombreInstancia VARCHAR(50) NOT NULL,"
                . "fechaCreacion DATETIME NOT NULL DEFAULT 0,"
                . "usuarioCreador VARCHAR(50) DEFAULT 'root',"
                . "PRIMARY KEY (`IdInstancia`)) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8";

        if (($ResultCreateInstances = $this->crear_tabla("cs-docs", $CreateInstances)) != 1) {
            echo "<p><b>Error</b> al crear la Tabla Instancias en CSDocs. $ResultCreateInstances</p>";
            return 0;
        }

        $CreateRepositories = "CREATE TABLE IF NOT EXISTS `Repositorios` (IdRepositorio INT(11) NOT NULL AUTO_INCREMENT,"
                . "NombreRepositorio VARCHAR(200) NOT NULL,"
                . "PRIMARY KEY (`IdRepositorio`)) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8";

        if (($ResultCreateRepositories = $this->crear_tabla("cs-docs", $CreateRepositories)) != 1) {
            echo "<p><b>Error</b> al crear la Tabla Repositorios en CSDocs. $ResultCreateRepositories</p>";
            return 0;
        }

        $CreateCatalogs = "CREATE TABLE IF NOT EXISTS `Catalogos` ("
                . "IdCatalogo INT(11) NOT NULL AUTO_INCREMENT,"
                . "NombreCatalogo VARCHAR(200) NOT NULL,"
                . "PRIMARY KEY (`IdCatalogo`)"
                . ") ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8";

        if (($ResultCreateCatalogs = $this->crear_tabla("cs-docs", $CreateCatalogs)) != 1) {
            echo '<p><b>Error</b> al crear la Tabla Catalogos en CSDocs</p> ' . $ResultCreateCatalogs;
            return 0;
        }

        $CreateUsers = "CREATE TABLE IF NOT EXISTS `Usuarios` (IdUsuario INT(11) NOT NULL AUTO_INCREMENT,"
                . "Login VARCHAR(50) NOT NULL,"
                . "Password VARCHAR(50) NOT NULL,"
                . "PRIMARY KEY (`IdUsuario`)) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8";

        if (($ResultCreateUsers = $this->ConsultaQuery("cs-docs", $CreateUsers)) != 1) {
            echo "<p><b>Error</b> al crear la Tabla Usuarios en CSDocs $ResultCreateUsers</p>";
            return 0;
        }
    }

    /*     * ************************************************************************
      -                    Creación de una nueva instancia
     * ************************************************************************ */

    function CreateIntanciaDataBase($nombre_instancia_) {

        $userName = filter_input(INPUT_POST, "nombre_usuario");

        if (strcasecmp($userName, FALSE) == 0 or strcasecmp($userName, NULL) == 0)
            $userName = "";

        /* Se crea la instancia del Sistema por Default */
        $nombre_instancia = trim($nombre_instancia_);
        $this->CreateInstanciaCSDOCS();
        $root = $this->ExistUserRoot();

        if (($root['Peso'] == 0))
            $this->InsertUserRoot();

        $RoutFile = dirname(getcwd());

        echo "<p>Creando Instancia $nombre_instancia y estructuras de control del sistema CSDocs...</p>";

        $query = "SELECT NombreInstancia FROM instancias WHERE NombreInstancia='$nombre_instancia'";

        $CheckNuevaInstancia = $this->ConsultaSelect("cs-docs", $query);

        if ($CheckNuevaInstancia['Estado'] != 1)
            return "<p>Error al comprobar existencia de la instancia " . $CheckNuevaInstancia['Estado'] . "</p>";

        if (count($CheckNuevaInstancia['ArrayDatos']) >= 5)
            return "<p>Límite de instancias alcanzado para su versión.</p>";

        if (count($CheckNuevaInstancia['ArrayDatos']) > 0)
            return "<p>La <b>instancia</b> $nombre_instancia ya existe</p>";

        $sql = "CREATE DATABASE `$nombre_instancia` /*!40100 DEFAULT CHARACTER SET utf8 */;";

        if (($ResNuevaInstancia = $this->crear_tabla("cs-docs", $sql) != true))
            return "<p>Error al crear la instancia \"$nombre_instancia\". $ResNuevaInstancia</p>";

        $InsertInstancia = "INSERT INTO instancias (NombreInstancia, fechaCreacion, usuarioCreador) "
                . "VALUES ('$nombre_instancia', '" . date('Y-m-d H:i:s') . "', '$userName')";

        if ($this->CreateCSDocsControl($nombre_instancia) == 1) {
            echo "<p>Proceso de construcción del control de CSDocs terminado...</p>";

            if (($ResultInsertInstancia = $this->ConsultaQuery("cs-docs", $InsertInstancia)) != 1) {
                $this->DeleteInstance($nombre_instancia);
                return "Error al registrar la nueva instancias";
            }
        } else {
            $this->DeleteInstance($nombre_instancia);
            echo "<p>No fué posible crear el control de CSDocs</p>";
            echo "<p>La instancia <b>$nombre_instancia</b> no se pudo construir</p>";
            return 0;
        }

        return 1;
    }

    /* Crea la tablas de control de Menús, Repositorios y Usuarios  */

    function CreateCSDocsControl($DataBaseName) {
        $CreateGroupsUsers = "CREATE TABLE IF NOT EXISTS GruposUsuario ("
                . "IdGrupo INT NOT NULL AUTO_INCREMENT,"
                . "Nombre VARCHAR(50) NOT NULL,"
                . "Descripcion TEXT NOT NULL,"
                . "PRIMARY KEY (IdGrupo)"
                . ")ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8";

        if (($ResultCreateGroupsUsers = $this->ConsultaQuery($DataBaseName, $CreateGroupsUsers)) != 1)
            return "<p><b>Error</b> al crear <b>Grupos de Usuario</b> en $DataBaseName. $ResultCreateGroupsUsers</p>";

        $CreateGruposControl = "CREATE TABLE IF NOT EXISTS GruposControl ("
                . "IdGrupoControl INT AUTO_INCREMENT,"
                . "IdGrupo INT NOT NULL DEFAULT '0',"
                . "IdUsuario INT NOT NULL DEFAULT '0',"
                . "PRIMARY KEY (IdGrupoControl)"
                . ")ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8";

        if (($ResultCreateGruposControl = $this->ConsultaQuery($DataBaseName, $CreateGruposControl)) != 1)
            return "<p><b>Error</b> al crearl <b>Grupos de Control</b>. $ResultCreateGruposControl</p>";

        $InsertGrupoAdmin = "INSERT INTO GruposUsuario (IdGrupo, Nombre, Descripcion) VALUES (1,'Administradores','Grupo de Administradores del Sistema (Con todos los privilegios)')";
        $IdGroupAdmin = $this->ConsultaInsertReturnId($DataBaseName, $InsertGrupoAdmin);

        if (!($IdGroupAdmin > 0))
            return "<p><b>Error</b> al crear el Grupo <b>Admisnitradores</b>. $IdGroupAdmin</p>";

        $InsertIntoGruposControl = "INSERT INTO GruposControl (IdGrupo, IdUsuario) VALUES ($IdGroupAdmin, 1)";

        if (($ResultInsertIntoGruposControl = $this->ConsultaQuery($DataBaseName, $InsertIntoGruposControl)) != 1)
            return "<p><b>Error</b> al insert el usuario <b>root</b> al <b>Control de Grupos</b></p>";

        $CreateMenu = "CREATE TABLE IF NOT EXISTS SystemMenu ("
                . "IdMenu INT NOT NULL AUTO_INCREMENT,"
                . "IdParent INT NOT NULL DEFAULT '0',"
                . "Nombre VARCHAR(50) NOT NULL,"
                . "Type INT DEFAULT 1,"
                . "Icon VARCHAR(40) DEFAULT NULL,"
                . "PRIMARY KEY(IdMenu)"
                . ")ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8";

        if (($ResultCreateMenu = $this->ConsultaQuery($DataBaseName, $CreateMenu)) != 1)
            return "<p><b>Error</b> al crear <b>Menú</b> en $DataBaseName. $ResultCreateMenu</p>";

        if ($this->InsertMenuRecords($DataBaseName) != 1)
            return 0;

        $CreateControlMenu = "CREATE TABLE IF NOT EXISTS SystemMenuControl ("
                . "IdMenuControl INT NOT NULL AUTO_INCREMENT,"
                . "IdMenu INT NOT NULL,"
                . "IdGrupo INT DEFAULT '0',"
                . "IdUsuario INT DEFAULT '0',"
                . "IdRepositorio INT DEFAULT '0',"
                . "PRIMARY KEY (IdMenuControl)"
                . ")ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8";

        if (($ResultCreateControlMenu = $this->ConsultaQuery($DataBaseName, $CreateControlMenu)) != 1)
            return "<p><b>Error</b> al crear <b>Control de Ménu</b> $ResultCreateControlMenu en $DataBaseName. $ResultCreateControlMenu</p>";


        $InsertAdminIntoMenuControl = "INSERT INTO SystemMenuControl (IdMenu) SELECT IdMenu FROM SystemMenu";
        if (($ResultInsertAdminIntoMenuControl = $this->ConsultaQuery($DataBaseName, $InsertAdminIntoMenuControl)) != 1)
            return "<p><b>Error</b> al asignar permisos al grupo <b>Administradores</b></p>. <br>Detalles:<br><br>$ResultInsertAdminIntoMenuControl";

        $UpdateAdminPermissions = "UPDATE SystemMenuControl SET IdGrupo = 1";
        if (($ResultUpdateAdminsPermissions = $this->ConsultaQuery($DataBaseName, $UpdateAdminPermissions)) != 1)
            return "<p><b<Error</b> al asignar permisos al grupo <b>Administradores</b>. <br>Detalles:<br><br>$ResultUpdateAdminsPermissions</p>";

        $enterpriseTable = "CREATE TABLE IF NOT EXISTS CSDocs_Empresas ("
                . "IdEmpresa INT NOT NULL AUTO_INCREMENT,"
                . "NombreEmpresa VARCHAR(100) NOT NULL,"
                . "Descripcion TEXT,"
                . "ClaveEmpresa VARCHAR(50) NOT NULL,"
                . "PRIMARY KEY (IdEmpresa)"
                . ") ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8";

        if (($result = $this->ConsultaQuery($DataBaseName, $enterpriseTable)) != 1)
            return "<p><b>Error</b> al crear Empresas</p> <p> $result </p>";

        $CreateRepositoryControl = "CREATE TABLE IF NOT EXISTS RepositoryControl ("
                . "IdRepositoryControl INT NOT NULL AUTO_INCREMENT,"
                . "IdGrupo INT NOT NULL,"
                . "IdUsuario INT NOT NULL,"
                . "IdRepositorio INT NOT NULL,"
                . "PRIMARY KEY(IdRepositoryControl)"
                . ")ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8";

        if (($ResultCreateRepositoryControl = $this->ConsultaQuery($DataBaseName, $CreateRepositoryControl)) != 1)
            return "<p><b>Error</b> al crear el <b>Control de Repositorios</b> en $DataBaseName. $ResultCreateRepositoryControl</p>";


        $TablaRepositorio = "CREATE TABLE IF NOT EXISTS CSDocs_Repositorios "
                . "(IdRepositorio INT(11) NOT NULL AUTO_INCREMENT,"
                . "ClaveEmpresa VARCHAR(50) NOT NULL,"
                . "NombreRepositorio VARCHAR(200) NOT NULL,"
                . "PRIMARY KEY (`IdRepositorio`)"
                . ")DEFAULT CHARSET=utf8";

        if (($estado = $this->ConsultaQuery($DataBaseName, $TablaRepositorio)) != 1)
            return "<p><b>Error</b> al crear <b>Repositorios</b> en $DataBaseName. $TablaRepositorio</p>";

//            echo $ResulTRoles=($this->CreateTableRoles($nombre_instancia)) ? "<p>Se construyó <b>Roles</b> </p>" : "<p><b>Error</b> al crear la tabla Roles de Usuario</p>";
//            echo $ResultPermissions = ($this->CreateTablePermissions($nombre_instancia))? "<p>Se construyo <b>Permissions</b></p>":"<p>Error al crear Permissions $ResultPermissions</p>";

        $TablaCatalogos = "CREATE TABLE IF NOT EXISTS CSDocs_Catalogos ("
                . "IdCatalogo INT(11) NOT NULL AUTO_INCREMENT,"
                . "IdRepositorio INT(11) NOT NULL,"
                . "NombreCatalogo VARCHAR(100) NOT NULL,"
                . "PRIMARY KEY(IdCatalogo)"
                . ")DEFAULT CHARSET=utf8";

        if (($catalogos = $this->ConsultaQuery($DataBaseName, $TablaCatalogos)) != 1)
            return "<p><b>Error</b> al crear <b>Catálogos</b></p> Detalles: <br> $catalogos";

        $TablaGlobalRepositorios = "CREATE TABLE IF NOT EXISTS RepositorioGlobal ("
                . "IdGlobal INT NOT NULL AUTO_INCREMENT,"
//                    . "IdRepositorio INT NOT NULL,"
                . "IdFile INT NOT NULL,"
                . "IdEmpresa INT(11) NOT NULL,"
                . "IdRepositorio INT NOT NULL,"
                . "IdDirectory INT NOT NULL,"
                . "NombreEmpresa TEXT NOT NULL,"
                . "NombreRepositorio VARCHAR(100),"
                . "NombreArchivo VARCHAR(250) NOT NULL,"
                . "TipoArchivo VARCHAR(10) NOT NULL, "
                . "RutaArchivo TEXT NOT NULL,"
                . "UsuarioPublicador VARCHAR(50) NOT NULL,"
                . "FechaIngreso DATETIME NOT NULL,"
                . "Full TEXT CHARACTER SET latin1 COLLATE latin1_fulltext_ci NOT NULL,"
                . "PRIMARY KEY (IdGlobal),"
                . "FULLTEXT (Full)"
                . ")ENGINE = MYISAM DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci";

        if (($EstadoTablaGlobal = $this->ConsultaQuery($DataBaseName, $TablaGlobalRepositorios)) != 1)
            return "<p><b>Error</b> al crear <b>Global</b></p> Detalles: <br> $EstadoTablaGlobal";

        $TablaNotas = "CREATE TABLE IF NOT EXISTS CSDocs_Notes ("
                . "IdNote INT NOT NULL AUTO_INCREMENT,"
                . "IdUser INT NOT NULL,"
                . "UserName VARCHAR(50) NOT NULL,"
                . "IdRepository INT NOT NULL,"
                . "IdFile INT(11) NOT NULL,"
                . "CreationDate DATETIME NOT NULL,"
                . "Text TEXT NOT NULL,"
                . "Page INT(10) NOT NULL,"
                . "ApprovalStatus INT(1) NOT NULL DEFAULT '0',"
                . "UserApproved INT(5) NOT NULL DEFAULT '0',"
                . "PRIMARY KEY (IdNote)"
                . ")DEFAULT CHARSET=utf8";

        if (($EstadoTablaNotas = $this->ConsultaQuery($DataBaseName, $TablaNotas)) != 1)
            return "<p><b>Error al crear <b>Notas</p> en $DataBaseName. $EstadoTablaNotas</p>";

        $TablaSmtp = "CREATE TABLE IF NOT EXISTS CSDocs_Correos ("
                . "IdCorreo INT(11) NOT NULL AUTO_INCREMENT,"
                . "IdUsuario INT(11) NOT NULL,"
                . "NombreCuenta VARCHAR(100) NOT NULL,"
                . "Password VARCHAR(100) NOT NULL,"
                . "Servidor VARCHAR(200) NOT NULL,"
                . "Smtp VARCHAR(200) NOT NULL,"
                . "Seguridad VARCHAR(20) NOT NULL,"
                . "Auth INT(1) NOT NULL,"
                . "TituloMostrar VARCHAR(100) NOT NULL,"
                . "Puerto INT(10) NOT NULL,"
                . "HostImap VARCHAR(150) NOT NULL,"
                . "PRIMARY KEY (IdCorreo)"
                . ")DEFAULT CHARSET=utf8";

        if (($EstadoTablaCorreos = $this->ConsultaQuery($DataBaseName, $TablaSmtp)) != 1)
            return "<p><b>Error</b> al crear <b>Correo</b> en $DataBaseName. $EstadoTablaCorreos</p>";

        return 1;
    }

    function DeleteInstance($InstanceName) {
        $IdInstance = filter_input(INPUT_POST, "IdInstance");
        $RoutFile = dirname(getcwd());

        $QueryDrop = "DROP DATABASE IF EXISTS $InstanceName";
        if (($Result = $this->ConsultaQuery("cs-docs", $QueryDrop)) != 1) {
            XML::XMLReponse("Error", 0, "<p><b>Error</b> al eliminar la instancia $InstanceName</p>" . $QueryDrop);
            return 0;
        }

        $DeleteRegister = "DELETE FROM instancias WHERE NombreInstancia = $IdInstance";
        if (($ResultDelete = $this->ConsultaQuery("cs-docs", $DeleteRegister)) != 1) {
            XML::XMLReponse("Error", 0, "<p><b>Error</b> al eliminar el registro de la instancia en cs-docs</p>");
            return 0;
        }

        if (file_exists("$RoutFile/Configuracion/$InstanceName.ini"))
            unlink("$RoutFile/Configuracion/$InstanceName.ini");

        if (file_exists("$RoutFile/Configuracion/$InstanceName"))
            exec("rm -R $RoutFile/$InstanceName");

        if (file_exists("$RoutFile/Configuracion/Catalogos/$InstanceName"))
            exec("rm -R $RoutFile/Configuracion/Catalogos/$InstanceName");

        if (file_exists("$RoutFile/Estructuras/$InstanceName"))
            exec("rm -R $RoutFile/Estructuras/$InstanceName");

        if (file_exists("$RoutFile/Configuracion/MassiveUpload/$InstanceName"))
            exec("rm -R $RoutFile/Configuracion/MassiveUpload/$InstanceName");

        XML::XMLReponse("DeleteInstance", 1, "Instancia $InstanceName eliminada");
    }

    private function InsertMenuRecords($DataBaseName) {
                $InsertIntoHerramientas = "INSERT INTO SystemMenu (IdMenu, IdParent, Nombre, Type) VALUES 
            (1, 0, 'Administración', 0),
                (100, 1, 'Sistema', 0),
                (200, 1, 'Instancias', 0),
                (300, 1, 'Empresas', 0),
                (400, 1, 'Repositorios', 0),
                (500, 1, 'Catálogos', 0),
                (600, 1, 'Usuarios', 0),
            (15, 0, 'Herramientas', 1 ),    
                (16, 15, 'Nuevo Directorio', 1),
                (17, 15, 'Modificar Directorio', 1),
                (18, 15, 'Eliminar Directorio', 1),
                (19, 15, 'Carga Masiva', 1),
                (20, 15, 'Carga Manual', 1),
                (21, 15, 'Modificar Documento', 1),
                (22, 15, 'Eliminar Documento', 1),
                (23, 15, 'Copiar Documento', 1),
                (24, 15, 'Cortar Documento', 1),
                (25, 15, 'Pegar Documento', 1),
                (26, 15, 'Correo', 1),
                (27, 15, 'Papelera', 1),
                (2, 0, 'Visor', 1),
                    (28, 2, 'Acceso a Visor', 1),
                        (30, 28, 'Notas', 1),
                        (31, 28, 'Imprimir', 1),
                        (32, 28, 'Bloquear Página', 1),
                        (33, 28, 'Marcas de Agua', 1)";

        if (($ResultInsertIntoHerramientas = $this->ConsultaQuery($DataBaseName, $InsertIntoHerramientas)) != 1) {
            echo "<p><b>Error</b> al insertar registros en <b>Menú Herramientas</b></p>";
            return 0;
        }


        return 1;
    }

    /*     * *************************************************************************
     *                              EMPRESAS  
     * *************************************************************************
     *  Se extrae la información que contendrá la estructura de la tabla empresa
     * Para ser insertada en la BD señalada por el usuario
     */

    function CrearEstructEmpresa($EstructuraEmpresa) {
        echo "<p>*****Creando Control de Empresas: " . count($EstructuraEmpresa) . "******</p>";
//        $NodosEmpresa=$EstructuraEmpresa->children();
        foreach ($EstructuraEmpresa as $Empresa) {
            $DefaultEstruct = $Empresa->DefaultStructProperties->children();
            $DefinitionUser = array();
            if (count(count($Empresa->DefinitionUsersProperties)) > 0) {
                $DefinitionUser = $Empresa->DefinitionUsersProperties->children();
            }
            $DatabaseName = $Empresa['DataBaseName'];
            $TablaEmpresa = "CREATE TABLE IF NOT EXISTS CSDocs_Empresas (IdEmpresa int(11) NOT NULL AUTO_INCREMENT, ";

            /* Almacenando Estructura para Archivo de Configuracion */
            $atributos = array();
            foreach ($DefaultEstruct as $estructura) {

                $atributos[] = array("Campo" => $estructura->getName(), "Atributos" => $estructura->attributes());
                $required = $estructura['required'];
                if ($required == 'true') {
                    $required = "NOT NULL";
                } else {
                    $required = "DEFAULT NULL";
                }
                if ($estructura['long'] > 0) {
                    $TablaEmpresa.=$estructura->getName() . " " . $estructura['type'] . "(" . $estructura['long'] . ") $required, ";
                } else {
                    $TablaEmpresa.=$estructura->getName() . " " . $estructura['type'] . " $required , ";
                }
            }

            if (count($DefinitionUser) > 0) {
                foreach ($DefinitionUser as $estructura) {
                    /* Almacenando Estructura para Archivo de Configuracion */
                    $atributos[] = array("Campo" => $estructura->getName(), "Atributos" => $estructura->attributes());

                    $required = $estructura['required'];
                    if ($required == 'true') {
                        $required = "NOT NULL";
                    } else {
                        $required = "DEFAULT NULL";
                    }
                    if ($estructura['long'] > 0) {
                        $TablaEmpresa.=$estructura['name'] . " " . $estructura['type'] . "(" . $estructura['long'] . ") $required, ";
                    } else {
                        $TablaEmpresa.=$estructura['name'] . " " . $estructura['type'] . " $required , ";
                    }
                }
            }


            $TablaEmpresa.=" PRIMARY KEY (`IdEmpresa`)" /* Al modificar, modificar también en la llave foranea del query de repositorio */
                    . ") ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8";
//            echo  "<p>$TablaEmpresa</p>";
            
            $this->ConsultaQuery($DatabaseName, "DROP TABLE IF  EXISTS CSDocs_Empresas");
            
            if (($createEnterpriseResult = $this->ConsultaQuery($DatabaseName, $TablaEmpresa))) {
                echo "Control de empresas creado.";
                /* Se guarda la estructura de la tabla definida por el usuario */
                $configStructure = array("TipoEstructura" => "Empresa", "DataBaseName" => $DatabaseName, "Atributos" => $atributos);
                $this->WriteConfig("Empresa", $configStructure);
            }
            else
                echo "<p><b>Error</b> al crear el control de empresas. $createEnterpriseResult</p>";
        }
    }

    /*     * ************************************************************************
     *  Campos de Default para Empresa XML
     *  DataBaseName,NombreEmpresa, Descripcion
     */

    function insertar_empresa($detalle_empresa) {
        echo "<p>*****Insertando Empresas: " . count($detalle_empresa) . "******</p>";
        $InsertEmpresa = $detalle_empresa;
        foreach ($InsertEmpresa as $Empresa) {
            $DataBase = $Empresa['DataBaseName'];
            $CadenaCampos = '';
            $cadena_valores = '';
            foreach ($Empresa->children() as $InsertEmpresa) {

                if ($InsertEmpresa == 'ClaveEmpresa') {
                    $CE = $InsertEmpresa;
                    $ExistEmpresa = $this->ExistRegister($DataBase, 'CSDocs_Empresas', 'ClaveEmpresa', "'" . $InsertEmpresa['Value'] . "'");

                    if ($ExistEmpresa['Peso'] != 0) {
                        echo "<p>Error: La clave " . $InsertEmpresa['Value'] . " de empresa ya existe.</p>";
                        continue 2;
                    }
                }

                $Value = $InsertEmpresa['Value'];

                if (!(is_numeric("$Value"))) {
                    $Value = "'$Value'";
                }

                $cadena_valores.=$Value . ",";

                $CadenaCampos.= "$InsertEmpresa,";
            }

            $cadena_valores_ = trim($cadena_valores, ',');  /* Quita la última Coma ( , ) */
            $CadenaCampos_ = trim($CadenaCampos, ',');

            $query = "INSERT INTO CSDocs_Empresas ($CadenaCampos_) VALUES ($cadena_valores_)";

//            echo "<p>$query</p>";
            echo "<p><br> Registrando en la instancia <b>$DataBase</b></p>";

            if (($Insert = $this->ConsultaInsertReturnId($DataBase, $query)) > 0)
                echo "<p>Empresa registrada</p>";
            else
                echo "<b>Error</b> al registrar empresa con clave $CE. <br>" . $Insert;
        }
    }

    /*     * **************************************************************************
     *                              REPOSITORIOS                                *
     * **************************************************************************
     */

    function crear_repositorio($StructRepositorio) {
        foreach ($StructRepositorio as $Repositorio) {
            echo "<br><p>Creando el Repositorio <b>$Repositorio->NombreRepositorio</b></p><br>";

            $DataBaseName = 0;
            $ClaveEmpresa = 0;

            if (isset($Repositorio['DataBaseName']))
                $DataBaseName = $Repositorio['DataBaseName'];
            else
                $DataBaseName = filter_input(INPUT_POST, "DataBaseName");

            echo "Creando sobre la inastancia <b>$DataBaseName</b>";

            if (!strlen($DataBaseName) > 0)
                return "<p>Sin especificar BD</p>";

            if (isset($Repositorio['ClaveEmpresa']))
                $ClaveEmpresa = $Repositorio['ClaveEmpresa'];
            else
                $ClaveEmpresa = filter_input(INPUT_POST, "ClaveEmpresa");

            if (!strlen($DataBaseName) > 0 or ! strlen($ClaveEmpresa) > 0)
                return "Falta clave o empresa Nombre de BD.";

            $nombre_tabla = $Repositorio->NombreRepositorio;

            /*  Operaciones recursivas para creación de tablas de repositorio */
            $DefaultEstruct = $Repositorio->DefaultStructProperties->children();
            $DefinitionUser = $Repositorio->DefinitionUsersProperties->children();

            /* Almacenando Estructura para Archivo de Configuracion */
            $atributos = array();
            $atributosRepositorios = array();
            $FKRepositorio = '';
            $RepositorioIdCatalogo = array(); /* Guarda los nombres de cada catalogo para crear un campo id de cada uno en Repositorio  */
            $NombresCatalogo = '';
            $PKNombresCatalogo = ''; /* Cadena de definicion de PK */
            /*
             *  Definiciones creadas por el Usuario
             *      - Se obtienen a través de una función recursiva
             */
            $properties = '';
            $TablaCatalogo = '';

            /*             * ******************************************************************
             *          Se recorren Campos definidos por el Usuario             *
             *            (Campos en repositorio y Catálogos)                   *
             * ****************************************************************** */

            foreach ($DefinitionUser as $Definition) {
                $required = $Definition['required'];

                if ($required == 'true')
                    $required = "NOT NULL";
                else
                    $required = "DEFAULT NULL";

                if (count($Definition->attributes()) > 0)
                    $atributos[] = array("Campo" => $Definition->getName(), "Atributos" => $Definition->attributes());

                if ($Definition['name'] != null) /* Cuando es null significa que es un tipo List */
                    if ($Definition['long'] > 0)
                        $properties.=$Definition['name'] . " " . $Definition['type'] . "(" . $Definition['long'] . ") $required, ";
                    else
                        $properties.=$Definition['name'] . " " . $Definition['type'] . " $required, ";
                else /* Cuando es un catálogo */ {
                    /* Definiciones tipo List */
                    $ListProperties = $Definition->children();
                    foreach ($ListProperties as $list) {
                        /*                         * **********************CATALOGOS******************* */
                        $TipoCatalogo = $list['TipoCatalogo'];

                        if ($TipoCatalogo != true)
                            continue;

                        $NombreCatalogo = $list['name'];
                        $List = $list->children(); /* Properties de un List */
                        $TablaCatalogo = "CREATE TABLE IF NOT EXISTS $nombre_tabla" . "_$NombreCatalogo (Id$NombreCatalogo int(11) NOT NULL AUTO_INCREMENT, ";
                        $atributosRepositorios = array("Tipo" => $list->getName(), "Struct" => $ListProperties);
                        foreach ($List as $valor) {
//                            $atributosRepositorios[]=array("Campo"=>$valor->getName(),"Atributos"=>$valor->attributes());
                            if ($valor['long'] > 0)
                                $TablaCatalogo.=$valor['name'] . " " . $valor['type'] . "(" . $valor['long'] . "), ";
                            else
                                $TablaCatalogo.=$valor['name'] . " " . $valor['type'] . ", ";
                        }

                        $TablaCatalogo.="PRIMARY KEY (`Id$NombreCatalogo`)" /* Al modificar, modificar también en la llave foranea del query de repositorio */
                                . ") ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8";

//                        echo "<p>List Properties $TablaCatalogo</p>";
                        if ($this->crear_tabla($DataBaseName, $TablaCatalogo)) {
                            $configStructure = array("TipoEstructura" => "Empresa", "DataBaseName" => $DataBaseName, "Estructura" => $atributosRepositorios);
                            $this->WriteConfigCatalogo("$nombre_tabla" . "_$NombreCatalogo", $configStructure);
                        }
                        unset($atributosRepositorios);

                        $RepositorioIdCatalogo[] = $NombreCatalogo;
                    }
                }
            }/* Fin for Campos Definidos por el usuario */

            /*             * ************************* TABLA DE DIRECTORIOS ****************** */
//            echo "<br>";
            $tabla_directorios = "CREATE TABLE IF NOT EXISTS dir_$nombre_tabla "
                    . "(`IdDirectory` int(11) NOT NULL AUTO_INCREMENT,"
                    . "`parent_id` int(10) UNSIGNED NOT NULL DEFAULT '0',"
                    . "`title` varchar(255) NOT NULL,"
                    . "`path` varchar(255) NOT NULL DEFAULT '',"
                    . "PRIMARY KEY (`IdDirectory`)"
                    . ") ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8";

            $tabla_temporal_directorio = "CREATE TABLE IF NOT EXISTS temp_dir_$nombre_tabla "
                    . "(`IdDirectory` int(11) NOT NULL,"
                    . "`parent_id` int(10) UNSIGNED NOT NULL,"
                    . "`FlagFather` int(10) UNSIGNED NOT NULL," /* Es el directorio padre de todos los subdirectorios que se eliminaron */
                    . "`title` varchar(255) NOT NULL,"
                    . "`path` varchar(255) NOT NULL DEFAULT '',"
                    . "`IdUsuario` INT NOT NULL,"
                    . "NombreUsuario VARCHAR(50) NOT NULL,"
                    . "PRIMARY KEY (`IdDirectory`)"
                    . ") ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8";


            /*             * **************************Campos del Repositorio************************* */

            $tabla_repositorio = "CREATE TABLE IF NOT EXISTS $nombre_tabla "
                    . "(IdRepositorio INT NOT NULL AUTO_INCREMENT, "
                    . "IdDirectory INT NOT NULL,"
                    . "IdEmpresa INT NOT NULL,";
            $tabla_temporal_repositorio = "CREATE TABLE IF NOT EXISTS temp_rep_$nombre_tabla "
                    . "(IdRepositorio INT NOT NULL, "
                    . "IdDirectory INT NOT NULL,"
                    . "IdEmpresa INT NOT NULL,"
                    . "IdUsuario INT NOT NULL,"
                    . "NombreUsuario VARCHAR(50) NOT NULL,";

            /*             * ********** Se recorren los campos de Default ******************* */
            foreach ($DefaultEstruct as $estructura) {
                /* Campos de Default en el Archivo de Configuración */
                $atributos[] = array("Campo" => $estructura->getName(), "Atributos" => $estructura->attributes());

                $required = $estructura['required'];
                if ($required == 'true')
                    $required = "NOT NULL";
                else
                    $required = "DEFAULT '0'";

                if ($estructura['long'] > 0) {
                    $tabla_repositorio.=$estructura->getName() . " " . $estructura['type'] . "(" . $estructura['long'] . ") $required, ";
                    $tabla_temporal_repositorio.=$estructura->getName() . " " . $estructura['type'] . "(" . $estructura['long'] . ") $required, ";
                } else {
                    if (strcasecmp($estructura->getName(), "full") == 0) {
                        $tabla_repositorio.= $estructura->getName() . " " . $estructura['type'] . " CHARACTER SET latin1 COLLATE latin1_fulltext_ci $required , ";
                        $tabla_temporal_repositorio.= $estructura->getName() . " " . $estructura['type'] . " CHARACTER SET latin1 COLLATE latin1_fulltext_ci $required , ";
                    } else {
                        $tabla_repositorio.=$estructura->getName() . " " . $estructura['type'] . " $required , ";
                        $tabla_temporal_repositorio.=$estructura->getName() . " " . $estructura['type'] . " $required , ";
                    }
                }
            }

            $FKRepositorio.=' FOREIGN KEY (IdEmpresa) REFERENCES CSDocs_Empresas(IdEmpresa),'
                    . "FOREIGN KEY (IdDirectory) REFERENCES dir_$nombre_tabla(IdDirectory), ";

            /* Se crean los campos que guardan el Id de los catalogos */
            for ($cont = 0; $cont < count($RepositorioIdCatalogo); $cont++) {
                $NombresCatalogo.=$RepositorioIdCatalogo[$cont] . " INT(10) NOT NULL DEFAULT '0', ";
                $PKNombresCatalogo.=",`$RepositorioIdCatalogo[$cont]`";
                $FKRepositorio.=' FOREIGN KEY (' . $RepositorioIdCatalogo[$cont] . ') REFERENCES ' . $RepositorioIdCatalogo[$cont] . '(Id' . $RepositorioIdCatalogo[$cont] . '), ';
            }

            /*             * ************** Unificación de Cadenas para Insert de Repositorio ****************** */
            $tabla_repositorio.=$properties . $NombresCatalogo . $FKRepositorio;
            $tabla_repositorio.= " PRIMARY KEY (`IdRepositorio`), FULLTEXT (FULL)"
                    . ") ENGINE = MYISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci";

            $tabla_temporal_repositorio.=$properties . $NombresCatalogo;
            $tabla_temporal_repositorio.="PRIMARY KEY (`IdRepositorio`), FULLTEXT (FULL)"
                    . ") ENGINE = MYISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci";

            /* Se busca que no exista el repositoria para registrarlo en la tabla de Repositorios */
            $query = "SELECT *FROM CSDocs_Repositorios WHERE NombreRepositorio='$nombre_tabla'";

            $ExistRepositorio = $this->ConsultaSelect($DataBaseName, $query);
            /* Sino existe un registro del repositorio este se crea y se registra */
            if ($ExistRepositorio['Estado'] == TRUE and count($ExistRepositorio['ArrayDatos']) == 0) {
                echo "<P>Creando $nombre_tabla</P>";

                if (($ResultTempDir = ($this->crear_tabla($DataBaseName, $tabla_directorios))) == 1) {
                    echo "<p>Creado Repositorio $nombre_tabla</p>";

                    $RutaBase = "../Estructuras/$DataBaseName/$nombre_tabla/";
//                    $RutaPath=".../Configuracion/";                    
                    $InsertRoot = "INSERT INTO dir_$nombre_tabla (IdDirectory, parent_id, title, path) "
                            . "VALUES (1, 0,'$nombre_tabla','$RutaBase')";

                    $ResultInsertRoot = $this->ConsultaInsertReturnId($DataBaseName, $InsertRoot);
                    if (!($ResultInsertRoot > 0))
                        $this->SalidaLog("Error al insertar Root en $nombre_tabla", $ResultInsertRoot, "Root insertado en $nombre_tabla");

                    if (!file_exists("$RutaBase/$ResultInsertRoot"))
                        if (!($mkdir = mkdir("$RutaBase/$ResultInsertRoot", 0777, true)))
                            $this->SalidaLog("No pudo ser creado el directorio raíz del repositorio <b>$nombre_tabla</b>, debe ser generado nuevamente.</p><p>Sí esta creando una instancia eliminelá e intente nuevamente cargarla al sistema, si el problema persiste contacte con soporte ténico de CSDocs</p>", $mkdir, "Error al crear el directorio raíz de $nombre_tabla");


                    $configStructure = array("TipoEstructura" => "Empresa", "DataBaseName" => $DataBaseName, "Atributos" => $atributos);
                    $this->WriteConfig($nombre_tabla, $configStructure);
                    $ResultTablaTempDir = $this->crear_tabla($DataBaseName, $tabla_temporal_directorio);
                    $this->SalidaLog("Error al crear Temporal Directorio $nombre_tabla", $ResultTablaTempDir, "Temporal Directorio $nombre_tabla creada con éxito");

                    $ResultTablaTempRep = $this->crear_tabla($DataBaseName, $tabla_temporal_repositorio);
                    $this->SalidaLog("Error al crear Temporal Repositorio $nombre_tabla", $ResultTablaTempRep, "Temporal Repositorio $nombre_tabla creada con éxito");
                }
                else {
                    echo "<p>Error al Crear el Repositorio temporal $ResultTempDir";
                    continue;
                }

                /* Registro del Repositorio */
                $QueryRegistroRepositorio = "INSERT INTO CSDocs_Repositorios (NombreRepositorio,ClaveEmpresa) VALUES ('$nombre_tabla','$ClaveEmpresa')";

                if (($IdRepositorio = $this->ConsultaInsertReturnId($DataBaseName, $QueryRegistroRepositorio)) > 0) {
                    /* El catálogo recién creado se registra en la tabla catálogos */
                    $RegistroCatalogo = "INSERT INTO CSDocs_Catalogos (IdRepositorio, NombreCatalogo) VALUES ";
                    $CamposCatalogo = '';

                    for ($cont = 0; $cont < count($RepositorioIdCatalogo); $cont++) {
                        $CamposCatalogo.="($IdRepositorio, '$RepositorioIdCatalogo[$cont]'),";
                    }

                    $CamposCatalogoProcess = trim($CamposCatalogo, ',');
                    $RegistroCatalogo = $RegistroCatalogo . $CamposCatalogoProcess;

                    if (!($ResultRegistroCatalogo = $this->ConsultaInsertReturnId($DataBaseName, $RegistroCatalogo)) > 0)
                        return "<p>Error al registrar el catálogo $RepositorioIdCatalogo </p>";

                    $GetGroupAdministradores = "SELECT *FROM GruposUsuario WHERE Nombre = 'Administradores'";
                    $ResultGetGroupAdministradores = $this->ConsultaSelect($DataBaseName, $GetGroupAdministradores);
                    if ($ResultGetGroupAdministradores['Estado'] != 1) {
                        echo "<p><b>Error</b> al obtener el grupo <b>Administradores</b></p>";
                        return;
                    }

                    if (!(count($ResultGetGroupAdministradores['ArrayDatos']) > 0)) {
                        echo "<p><b>Error</b> no se encontró el grupo <b>Administradores</b></p>";
                        return 0;
                    }

                    $IdGroupAdmin = $ResultGetGroupAdministradores['ArrayDatos'][0]['IdGrupo'];
//                    $DescripcionAdmin = $ResultGetGroupAdministradores['ArrayDatos'][0]['Descripcion'];

                    $AddAccess_Of_Admin_Into_Repository = "INSERT INTO RepositoryControl (IdGrupo, IdUsuario, IdRepositorio) VALUES ($IdGroupAdmin, 0, $IdRepositorio)";
                    if (($ResultAddAccess = $this->ConsultaQuery($DataBaseName, $AddAccess_Of_Admin_Into_Repository)) != 1) {
                        echo "<p><b>Error</b> al dar acceso al grupo <b>Administradores</b> en el repositorio <b>$nombre_tabla</b></p>";
                        return 0;
                    }
                }
            } else {
                echo "<p>El repositorio no se creo por que ya estaba registrado</p>";
                continue;
            }

            if (!($ResultadoFinal = $this->crear_tabla($DataBaseName, $tabla_repositorio)))
                echo $ResultadoFinal;
            else
                echo "<p>Repositorio Creado <b>$Repositorio->NombreRepositorio</b></p>";
        }
    }

    /*     * *************************************************************************
     *                              USUARIOS                                   *
     * ************************************************************************* */

    /*
     *  Descripción: Crea la estructura de la tabla Usuario
     */

    function CrearStructUsuario($StructUsuario) {
        echo "<br><p>***** Creando Estructura de Usuarios ****</p><br>";
//        $NodosUsuario=$StructUsuario->children();
        $DefaultEstruct = $StructUsuario->DefaultStructProperties->children();
        $DefinitionUser = $StructUsuario->DefinitionUsersProperties->children();
        $DatabaseName = $StructUsuario['DataBaseName'];
        $TablaUsuarios = "CREATE TABLE IF NOT EXISTS CSDocs_Usuarios (IdUsuario int(11) NOT NULL AUTO_INCREMENT,"
                . "estatus INT NOT NULL DEFAULT '1', ";
//                        . "IdRol INT NOT NULL DEFAULT '1', ";  /* El rol 1 = sin grupo */

        /* Almacenando Estructura para Archivo de Configuracion */
        $atributos = array();
        foreach ($DefaultEstruct as $estructura) {

            $atributos[] = array("Campo" => $estructura->getName(), "Atributos" => $estructura->attributes());

            $required = $estructura['required'];

            if ($required == 'true')
                $required = "NOT NULL";
            else
                $required = "DEFAULT NULL";

            if ($estructura['long'] > 0)
                $TablaUsuarios.=$estructura->getName() . " " . $estructura['type'] . "(" . $estructura['long'] . ") $required, ";
            else
                $TablaUsuarios.=$estructura->getName() . " " . $estructura['type'] . " $required , ";
        }

        foreach ($DefinitionUser as $estructura) {
            /* Almacenando Estructura para Archivo de Configuracion */
            $atributos[] = array("Campo" => $estructura->getName(), "Atributos" => $estructura->attributes());

            $required = $estructura['required'];
            if ($required == 'true')
                $required = "NOT NULL";
            else
                $required = "DEFAULT NULL";

            if ($estructura['long'] > 0)
                $TablaUsuarios.=$estructura['name'] . " " . $estructura['type'] . "(" . $estructura['long'] . ") $required, ";
            else
                $TablaUsuarios.=$estructura['name'] . " " . $estructura['type'] . " $required , ";
        }

        $TablaUsuarios.="PRIMARY KEY (`IdUsuario`)" /* Al modificar, modificar también en la llave foranea del query de repositorio */
                . ") ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8";

        if (($Insert = $this->crear_tabla($DatabaseName, $TablaUsuarios))) {
            /* Se guarda la estructura de la tabla definida por el usuario */
            $configStructure = array("TipoEstructura" => "Usuarios", "DataBaseName" => $DatabaseName, "Atributos" => $atributos);
            $this->WriteConfig("Usuarios", $configStructure);
        } else
            return "<p>Error al crear Tabla Usuarios $Insert</p>";
    }

    /* Crea la estructura de empresas en el archivo de configuración de la instancia */

    function createEnterpriseDefaultConfiguration($dataBaseName) {
        $config = array();
        $config['DataBaseName'] = $dataBaseName;

        $config['Atributos'][0]['Campo'] = "NombreEmpresa";
        $config['Atributos'][0]['Atributos'] = array("type" => "VARCHAR", "long" => "100", "required" => "true");

        $config['Atributos'][1]['Campo'] = "Descripcion";
        $config['Atributos'][1]['Atributos'] = array("type" => "TEXT", "required" => "false");

        $config['Atributos'][2]['Campo'] = "ClaveEmpresa";
        $config['Atributos'][2]['Atributos'] = array("type" => "VARCHAR", "long" => "50", "required" => "true");

        $config['TipoEstructura'] = "Empresa";

        $section = "Empresa";

        return $this->WriteConfig($section, $config);
    }

    function createUsersDefaultConfiguration($dataBaseName) {
        $config = array();
        $config['DataBaseName'] = $dataBaseName;

        $config['Atributos'][0]['Campo'] = "Login";
        $config['Atributos'][0]['Atributos'] = array("type" => "VARCHAR", "long" => "50", "required" => "true");

        $config['Atributos'][1]['Campo'] = "Password";
        $config['Atributos'][1]['Atributos'] = array("type" => "VARCHAR", "long" => "50", "required" => "true");

        $config['Atributos'][2]['Campo'] = "Descripcion";
        $config['Atributos'][2]['Atributos'] = array("type" => "TEXT", "required" => "false");

        $config['TipoEstructura'] = "Usuarios";

        $section = "Usuarios";

        return $this->WriteConfig($section, $config);
    }

    function createUsersControl($dataBaseName) {
        $users = 'CREATE TABLE IF NOT EXISTS CSDocs_Usuarios ('
                . 'IdUsuario INT NOT NULL AUTO_INCREMENT,'
                . 'estatus INT NOT NULL DEFAULT "1",'
                . 'Login VARCHAR(50) NOT NULL,'
                . 'Password VARCHAR(50) NOT NULL,'
                . 'Descripcion TEXT,'
                . 'PRIMARY KEY (IdUsuario)'
                . ') ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8';

        if (($result = $this->ConsultaQuery($dataBaseName, $users)) != 1)
            return $result;

        return 1;
    }

    /*
     * Se comprueba la existencia del usuario Root en el sistema
     * return: true Sí existe el usuario Root ó false sino existe.
     */

    function ExistUserRoot() {
        $estado = 0;
        $Resultado = 0;

        $conexion = $this->Conexion();

        if (!$conexion) {
            $estado = mysqli_error($conexion);
            return $estado;
        }
        $rootPass = md5("root");
        $sql = "SELECT *FROM Usuarios WHERE Login='$rootPass'";

        mysqli_select_db($conexion,"cs-docs");
        $resultado = mysqli_query($conexion, $sql);

        if (!$resultado) {
            $estado = mysqli_error($conexion). "<br> ".  mysqli_errno($conexion);
            mysqli_close($conexion);
            return $estado;
        } else
            $Resultado = mysqli_fetch_array($resultado);

        mysqli_close($conexion);

        $PesoRoot = $Resultado;

        if ($PesoRoot == false)
            $PesoRoot = 0;

        $array = array("Estado" => $estado, "Peso" => $PesoRoot);
        return $array;
    }

    /*
     * Al crear la Tabla Usuario se inserta por default el Usuario Root
     */

    function InsertUserRoot() {
        $estado = false;
        $conexion = $this->Conexion();
        if (!$conexion) {
            $estado = mysqli_error($conexion);
            return $estado;
        }
        $rootPass = md5("root");
        $sql = "INSERT INTO Usuarios (IdUsuario, Login, Password) VALUES(1,'root','$rootPass')";
        mysqli_select_db($conexion, "cs-docs");
        $resultado = mysqli_query($conexion, $sql);
        if (!$resultado) {
            
        }
        mysqli_close($conexion);

        return $estado;
    }

    function CreateTablePermissions($DataBAse) {
        $estado = TRUE;
        $conexion = $this->Conexion();
        if (!$conexion) {
            $estado = mysqli_error($conexion)." <br> ".  mysqli_errno($conexion);
            return $estado;
        }
        mysqli_select_db($conexion, $DataBAse);
        $TablaPermisos = "CREATE TABLE IF NOT EXISTS Permisos"
                . "(IdPermiso INT(11) NOT NULL AUTO_INCREMENT,"
                . "ClavePermiso VARCHAR(10) NOT NULL,"
                . "Lectura INT(1) NOT NULL,"
                . "Escritura INT(1) NOT NULL,"
                . "LecturaEscritura INT(1) NOT NULL,"
                . "Ejecucion INT(1) NOT NULL,"
                . "PRIMARY KEY(IdPermiso)"
                . ")";

        $estado = (mysqli_query($conexion, $TablaPermisos)) ? 1 : "<p>Error al crear la tabla Permisos</p>";
        if ($estado != 1) {
            echo mysqli_error($conexion). "<br> ".  mysqli_errno($conexion);
            return;
        }
        $Insert = "INSERT INTO Permisos (IdPermiso,ClavePermiso,Lectura, Escritura, LecturaEscritura,Ejecucion) VALUES (1,'0',0,0,0,0),(2,'r',1,0,0,0),(3,'w',0,1,0,0),(4,'rw',1,1,1,0),(5,'rwx',1,1,1,1)";
        $estado = (mysqli_query($conexion, $Insert)) ? 1 : "<p>Error al llenar la tabla Permisos</p>" . mysqli_error($conexion) . mysqli_close($conexion);
        if ($estado != 1) {
            echo mysqli_error($conexion);
            return;
        }

//        mysqli_close($conexion);

        return $estado;
    }

    function insertar_usuario($detalle_usuario) {
        require_once 'DesignerForms.php';

        $designer = new DesignerForms();

        $GetTotalRegistros = $this->ConsultaSelect("cs-docs", "SELECT COUNT(*) FROM Usuarios");
        if ($GetTotalRegistros['Estado'] != 1) {
            echo "<p>Error al obtener el total de registros existentes en cs-docs. " . $GetTotalRegistros['Estado'] . "</p>";
            return 0;
        }

        $TotalRegistros = intval($GetTotalRegistros['ArrayDatos'][0]['COUNT(*)']);
        $TotalRegistros--;

        echo "<p>Total de usuarios en el sistema $TotalRegistros</p>";

        if ($TotalRegistros >= 5) {
            echo "<p>A llegado al límite de usuarios para su versión de CSDocs</p>";
            return 0;
        }

        foreach ($detalle_usuario as $Usuario) {

            if ($TotalRegistros >= 5) {
                echo "<p>A llegado al límite de usuarios para su versión de CSDocs</p>";
                return 0;
            }

            $DataBaseName = 0;
            if (isset($Usuario['DataBaseName']))
                $DataBaseName = $Usuario['DataBaseName'];
            if (isset($_POST["DataBaseName"]))
                $DataBaseName = filter_input(INPUT_POST, "DataBaseName");

            if ($DataBaseName === 0) {
                echo "Instancia no especificada";
                continue;
            }

            if (!file_exists("../Configuracion/$DataBaseName.ini")) {
                echo "<p>No existe el archivo de configuración estructural.</p>";
                return;
            }
            $EstructuraConfig = parse_ini_file("../Configuracion/$DataBaseName.ini", true);
            $EstructuraUsuarios = $designer->ReturnStructure("", $EstructuraConfig['Usuarios']);
            $EstructuraUsuariosDefault = $designer->ReturnStructureDefault("", $EstructuraConfig['Usuarios']);

            $CadenaCampos = '';
            $cadena_valores = '';
            $login = '';
            $password = '';

            $Nodes = array();

            /* Recolección de campos a insertar */
            foreach ($Usuario->children() as $insert) {
                $value = $insert['Value'];
                $Field = $insert;
                $Nodes["$Field"] = $value;
            }
            /* Se recorreo la estructura del sistema relacionada con Usuarios */

            $FlagFail = 0;  /* Bandera que se activa al haber algún error y no insertar el usuario */
            $StringFail = '';   /* Cadena que va almacenando los errores durante el recorrido de cada usuario en el XML */
            for ($cont = 0; $cont < count($EstructuraUsuariosDefault); $cont++) {
                $Campo = $EstructuraUsuariosDefault[$cont]['name'];
                $type = $EstructuraUsuariosDefault[$cont]['type'];
                $required = $EstructuraUsuariosDefault[$cont]["required"];

                if (!isset($Nodes[$Campo]))
                    if ($required === "true") {
                        $FlagFail = 1;
                        $StringFail.="<p>No existe el campo $Campo</p>";
                    }

                $value = $Nodes[$Campo];

                if ((strcasecmp($Campo, "Login") == 0)) {
                    $login = $value;

                    if (!count($login) < 4) {
                        echo "<p>El Login ' $login ' por lo menos debe tener 4 caracteres</p>";
                        continue 2;
                    }
                    if (strcasecmp($value, 'root') == 0) {
                        echo "El usuario " . $value . " es parte del sistema de CSDocs.";
                        continue 2;
                    }
                    $ExistUser = $this->ConsultaSelect($DataBaseName, "SELECT *FROM CSDocs_Usuarios WHERE Login = '$login'");
                    if ($ExistUser['Estado'] != 1) {
                        echo "<p>Error al comprobar existencia del usuario en el sistema. " . $ExistUser['Estado'] . "</p>";
                        continue 2;
                    }
                    if (count($ExistUser['ArrayDatos']) > 0) {
                        echo "<p>Error: El nombre de usuario <b>$value</b> ya existe.</p>";
                        continue 2;
                    }
                }

                if ((strcasecmp($Campo, "password") == 0)) {
                    if (!count($value) < 4) {
                        echo "<p>El Password de ' $login ' por lo menos debe tener 4 caracteres</p>";
                        continue 2;
                    }
                    $password = $value;
                }

                if (strcasecmp($type, "int") == 0 or strcasecmp($type, "float") == 0 || strcasecmp($type, "integer") == 0) /* Si detecta un tipo numerico */ {
                    if (!(is_numeric($value))) {
                        $FlagFail = 1;
                        $StringFail.=" <p>Se necesita un valor númerico en el campo '$Campo'</p>";
                    } else
                        $cadena_valores.=$value . ",";
                } else /* Demás tipos de datos llevan ' ' */
                    $cadena_valores.="'$value'" . ",";

                $CadenaCampos.=$Campo . ",";
            }

            for ($cont = 0; $cont < count($EstructuraUsuarios); $cont++) {
                $Campo = $EstructuraUsuarios[$cont]['name'];
                $type = $EstructuraUsuarios[$cont]['type'];
                $required = $EstructuraUsuarios[$cont]["required"];

                if (!isset($Nodes[$Campo])) {
                    if ($required === "true") {
                        $FlagFail = 1;
                        $StringFail.=" No existe el campo $Campo<br>";
                    }
                }

                $value = $Nodes[$Campo];

                if (strcasecmp($type, "int") == 0 or strcasecmp($type, "float") == 0 || strcasecmp($type, "integer") == 0) /* Si detecta un tipo numerico */ {
                    if (!(is_numeric($value))) {
                        $FlagFail = 1;
                        $StringFail.=" <p>Se necesita un valor númerico en el campo '$Campo'</p>";
                    } else
                        $cadena_valores.=$value . ",";
                } else /* Demás tipos de datos llevan ' ' */
                    $cadena_valores.="'$value'" . ",";

                $CadenaCampos.=$Campo . ",";
            }

            $cadena_valores_processed = trim($cadena_valores, ',');  /* Quita la última Coma ( , ) */
            $CadenaCamposProcessed = trim($CadenaCampos, ',');

            if ($FlagFail == 1) {
                echo "<p>Ocurrieron los siguientes errores $StringFail al intentar registrar al usuario <b>$login</b></p>";
                continue;
            }

            $query = "INSERT INTO CSDocs_Usuarios ($CadenaCamposProcessed) VALUES ($cadena_valores_processed)";
            $InsertUserIntoCsDocs = "INSERT INTO Usuarios (Login, Password) VALUES ('$login', '$password')";
            if (($IdUsuario = $this->ConsultaInsertReturnId($DataBaseName, $query)) > 0) {
                if (($IdReturnFromCsDocs = $this->ConsultaInsertReturnId("cs-docs", $InsertUserIntoCsDocs)) > 0) {
                    echo "<p>Usuario <b>$login</b> agregado con éxito al sistema.</p>";
                    $TotalRegistros++;
                } else {
                    echo "<p>Error al agregar al sistema al usuario <b>$login</b>. $IdReturnFromCsDocs</p>";
                    $DeleteUser = "DELETE FROM Usuarios WHERE IdUsuario = $IdUsuario";
                    $this->ConsultaQuery($DataBaseName, $DeleteUser);
                }
            } else
                echo "<p>$IdUsuario</p>";
        }
    }

    /*     * *****************************************************************************
     * Comprueba si existe un registro dentro de una instancia y tabla especificada
     * Parametros
     *  DataBaseName:  Nombre de la Instancia BD
     * Table: Tabla donde se realiza la consulta
     * field: Campo a comprobar
     * value: Valor del campo buscado, si es un String debe de llevar ' '
     * 
     * Return:
     *  Array
     *      ("Estado"=>$estado,"Peso"=>  PesoResultados/false)
     *          
     */

    function ExistRegister($DataBaseName, $Table, $field, $Value) {
        $estado = 0;
        $Resultado = 0;
        $conexion = $this->Conexion();
        if (!$conexion) {
            $estado = mysqli_error($conexion);
            return $estado;
        }

        $sql = "SELECT $field FROM $Table WHERE $field=$Value";
        mysqli_select_db($conexion, $conexion);
        $resultado = mysqli_query($conexion, $sql);
        if (!$resultado) {
            $estado = mysqli_error($conexion);
        } else {
            $Resultado = mysqli_fetch_array($resultado);
        }

        mysqli_close($conexion);
        $PesoRoot = $Resultado;
        if ($PesoRoot == false) {
            $PesoRoot = 0;
        }
        $array = array("Estado" => $estado, "Peso" => $PesoRoot);
        return $array;
    }

    function GetTableFields($DataBaseName, $TableName) {
        $estado = 1;
        $GetFields = "SHOW COLUMNS FROM $TableName";
        $ResultGetFields = $this->ConsultaSelect($DataBaseName, $GetFields);
        if ($ResultGetFields['Estado'] != 1) {
            $estado = 0;
        }

        return $Fields = array("Estado" => $estado, "ArrayDatos" => $ResultGetFields['ArrayDatos']);
    }

    /*     * *************************************************************************
     * 
     *  ARCHIVO DE CONFIGURACION
     * 
     *      Se guardan las estructuras creadas de cada repositorio, tablas usuario, empresa, etc
     *      
     * *************************************************************************
     */

    function WriteConfig($Seccion, $ArrayDatos) {
        $RoutFile = dirname(getcwd());

        if (!($gestor = fopen("$RoutFile/Configuracion/" . $ArrayDatos['DataBaseName'] . ".ini", "a+")))
            return $gestor;

        fwrite($gestor, ";#############################################################################" . PHP_EOL);
        fwrite($gestor, ";--------- $Seccion ---------" . PHP_EOL);
        fwrite($gestor, ";#############################################################################" . PHP_EOL);
        fwrite($gestor, "" . PHP_EOL);
        fwrite($gestor, "$Seccion=$Seccion" . PHP_EOL);
        for ($cont = 0; $cont < count($ArrayDatos['Atributos']); $cont++) {
            $valores = '';

            foreach ($ArrayDatos['Atributos'][$cont]['Atributos'] as $campo => $valor) {

                $valores.=$campo . " " . $valor . "###";
            }
//            echo "<p>$valores</p>";
            fwrite($gestor, "$Seccion" . "[]=" . $ArrayDatos['Atributos'][$cont]['Campo'] . "###$valores" . PHP_EOL);
        }
        fwrite($gestor, "" . PHP_EOL);
        fwrite($gestor, "" . PHP_EOL);
        fclose($gestor);

//        var_dump( parse_ini_file ("../Configuracion/".$ArrayDatos['DataBaseName'].".ini"),true);
        return 1;
    }

    /* Recibe como parámetro la estructura a partir del tipo de catálogo, p.e.: 
      <ListProperties>
      <ListSearch name="Expedientes" TipoCatalogo = "true">
      <Properties name="ClaveCarrera" type="VARCHAR" long="10"/>
      <Properties name="NombreCarrera" type="VARCHAR" long="10"/>
      <Properties name="NombreDocumento" type="VARCHAR" long="110"/>
      </ListSearch>
      </ListProperties>
     *  .
     *  .
     *  .
     * <ListSearch attributes></ListSearch> */

    function WriteConfigCatalogo($Seccion, $ArrayDatos) {
        $gestor = fopen("../Configuracion/" . $ArrayDatos['DataBaseName'] . ".ini", "a+");
        fwrite($gestor, ";#############################################################################" . PHP_EOL);
        fwrite($gestor, ";------------ $Seccion -----------" . PHP_EOL);
        fwrite($gestor, ";#############################################################################" . PHP_EOL);
        fwrite($gestor, "" . PHP_EOL);
        fwrite($gestor, "$Seccion=$Seccion" . PHP_EOL);

        $Estructura = $ArrayDatos['Estructura']['Struct'];
        $TipoCatalogo = $ArrayDatos['Estructura']['Tipo'];
        /* Se escribe en el archivo el tipo de list y su nombre que representa al catálogo */
        fwrite($gestor, "$Seccion" . "[]=Tipo###$TipoCatalogo");
        $properties = $Estructura->children();
        fwrite($gestor, "" . PHP_EOL);
        for ($cont = 0; $cont < count($properties); $cont++) {
            $valores = '';
            foreach ($properties[$cont]->attributes() as $campo => $valor) {
                $valores.=$campo . " " . $valor . "###";
            }
            fwrite($gestor, "$Seccion" . "[]=" . $properties[$cont]->getName() . "###$valores" . PHP_EOL);
        }
        fwrite($gestor, "" . PHP_EOL);
        fwrite($gestor, "" . PHP_EOL);
        fclose($gestor);
    }

    /*
     * Se reciben dos cadenas:
     * 1.- Específica los campos a insertar
     * 2.- Especifica los valores a insertar en esos campos
     */

    function ConsultaInsert($bd, $query) {
        $estado = true;
        $conexion = $this->Conexion();
        if (!$conexion) {
            $estado = mysqli_error($conexion);

            return $estado;
        }

        mysqli_select_db($conexion, $bd);
        $insertar = mysqli_query($conexion, $query);
        if (!$insertar) {
            $estado = mysqli_error($conexion);
            return $estado;
        }
        mysqli_close($conexion);

        return $estado;
    }

    function ConsultaInsertReturnId($bd, $query) {
        $estado = 0;
        $conexion = $this->Conexion();
        if (!$conexion) {
            $estado = mysqli_connect_error()." <br> ". mysqli_error($conexion)." <br> ".  mysqli_errno($conexion);
            mysqli_close($conexion);
            return $estado;
        }

        mysqli_select_db($conexion, $bd);
        $insertar = mysqli_query($conexion, $query);
        if (!$insertar) {
            $estado = mysqli_error($conexion);
            return $estado;
        }
        $estado = mysqli_insert_id($conexion);
        mysqli_close($conexion);

        return $estado;
    }

    function crear_tabla($bd, $query) {
        $estado = true;
        $conexion = $this->Conexion();
        if (!$conexion) {
            $estado = mysqli_error($conexion);

            return $estado;
        }

        mysqli_select_db($conexion, $bd);
        $insertar = mysqli_query($conexion, $query);
        if (!$insertar) {
            $estado = mysqli_error($conexion);
            return $estado;
        }
        mysqli_close($conexion);

        return $estado;
    }

    /*     * *****************************************************************************
     * Regresa un array asociativo si la consulta tuvo éxito sino devuelve el error
     *                                                              
     *  Resultado = {
     * 
     *          Estado=> True/False ,
     *          ArrayDatos=>  'Resultado de Consulta'
     * 
     * ***************************************************************************** */

    function ConsultaSelect($bd, $query) {
        $estado = true;
        $ResultadoConsulta = array();
        $conexion = $this->Conexion();
        if (!$conexion) {
            $estado = "Error al conectar mysql. ".mysqli_connect_errno($conexion)." <br> ".  mysqli_connect_error($conexion);
            $error = array("Estado" => $estado, "ArrayDatos" => 0);
            return $error;
        }

        mysqli_select_db($conexion, $bd);
        $select = mysqli_query($conexion, $query);
  
        if (!$select) {
            $estado = mysqli_error($conexion). " <br> ".  mysqli_errno($conexion);
            $error = array("Estado" => "Error al consultar datos. ".$estado, "ArrayDatos" => 0);
            return $error;
        } else {            
            while (($ResultadoConsulta[] = mysqli_fetch_assoc($select)) || array_pop($ResultadoConsulta));
            mysqli_free_result($select);
        }


        mysqli_close($conexion);

        $Resultado = array("Estado" => $estado, "ArrayDatos" => $ResultadoConsulta);
        return $Resultado;
    }

    /*     * *************************************************************************
     * Realiza una consulta especifícando la instancia de BD y el query a ejecutar.
     */

    function ConsultaQuery($DataBasaName, $query) {
        $estado = true;
        $conexion = $this->Conexion();
        if (!$conexion) {
            $estado = mysqli_error($conexion);
            return $estado;
        }

        mysqli_select_db($conexion, $DataBasaName);
        $select = mysqli_query($conexion, $query);
        if (!$select) {
            $estado = mysqli_error($conexion). " <br> ".  mysqli_errno($conexion);
        }

        mysqli_close($conexion);
        return $estado;
    }

    /*     * *************************************************************************
     *  En cada consulta realizada se obtiene un estado ya sea 1 ó Texto (Mensaje de Salida de la BD)
     */

    private function SalidaLog($Error, $resultado, $mensaje) {
        if ($resultado == 1) {
            echo "<p>" . $mensaje . "</p>";
        } else {
            echo "<p>$resultado. $Error</p>";
        }
    }

    public static function FieldFormat($FieldValue, $FieldType) {
        if ((strcasecmp($FieldType, "varchar") == 0)) {
//                $FieldValue_ = trim($FieldValue,'\'\t\n\r\0\x0B');
            $FormattedField = "'$FieldValue'";
            return $FormattedField;
        }

        if (strcasecmp($FieldType, "double") == 0) {
            if (strcasecmp(trim($FieldValue), "") == 0) /* cadena vacia */
                return "''";
//                $FormattedField = trim($FieldValue,'\'\t\n\r\0\x0B');
            if (!is_numeric($FieldValue))
                return 0;

            return $FieldValue;
        }

        if (strcasecmp($FieldType, "int") == 0) {
            if (strcasecmp(trim($FieldValue), "") == 0) /* cadena vacia */
                return 0;
//                $FormattedField = trim($FieldValue,'\'\t\n\r\0\x0B');
            if (!is_numeric($FieldValue))
                return 0;

            return $FieldValue;
        }

        if (strcasecmp($FieldType, "integer") == 0) {
            if (strcasecmp(trim($FieldValue), "") == 0) /* cadena vacia */
                return 0;
//                $FormattedField = trim($FieldValue,'\'\t\n\r\0\x0B');
            if (!is_numeric($FieldValue))
                return 0;

            return $FieldValue;
        }

        if (strcasecmp($FieldType, "float") == 0) {
            if (strcasecmp(trim($FieldValue), "") == 0) /* cadena vacia */
                return 0;
//                $FormattedField = trim($FieldValue,'\'\t\n\r\0\x0B');
            if (!is_numeric($FieldValue))
                return 0;

            return $FieldValue;
        }

        if (strcasecmp($FieldType, "date") == 0) {
//                $FieldValue_ = trim($FieldValue,'\'\t\n\r\0\x0B');
            $FormattedField = "'$FieldValue";
            return $FormattedField;
        }

        if (strcasecmp($FieldType, "text") == 0) {
//                $FieldValue_ = trim($FieldValue,'\'\t\n\r\0\x0B');
            $FormattedField = "'$FieldValue'";
            return $FormattedField;
        }

        return "No se reconoce el tipo de dato  FieldFormat::";
    }

}
