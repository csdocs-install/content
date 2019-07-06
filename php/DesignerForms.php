<?php

/**************************************************************************
 *  Se devuelve un XML con la Estrcutra generada por el usuario.
 *      
 *      El parámetro estructura tiene la siguiente forma (Ejemplo)
 * 
 *      Estructura {
 *                  "Seccion"=>Array{
 *                      Empresa=Empresa
                        Empresa[]=NombreEmpresa###type###VARCHAR###long###100###required###true###
                        Empresa[]=Descripcion###type###TEXT###required###true###
                        Empresa[]=ClaveEmpresa###type###VARCHAR###long###50###required###true###
                        Empresa[]=Properties###name###Direccion###type###VARCHAR###long###200###required###false###
                        Empresa[]=Properties###name###Telefono###type###INT###long###15###required###false###
                        Empresa[]=Properties###name###FechaFundacion###type###DATE###required###false###
 *              }
 *          }
 *  Los valores vienen separados por ###, siempre se comienza por un valor de default en este caso es 
 * NombreEmpresa y Descripcion
 * Luego se siguen las propiedades dadas por el usuario y empiezan con "Properties".
 * 
 * Description of DesignerForms
 *
 * @author daniel
 * 
 */


require_once 'XML.php';
require_once 'Session.php';


if(!isset($_SESSION))
    session_start();

class DesignerForms {
    public function __construct() {
        $this->ajax();
    }
    
    private function ajax()
    {
        if(filter_input(INPUT_POST, "opcion")!=NULL and filter_input(INPUT_POST, "opcion")!=FALSE){
            
//            $idSession = Session::getIdSession();
            
//            if($idSession == null)
//                return XML::XMLReponse ("Error", 0, "DesignerForms::No existe una sesión activa, por favor vuelva a iniciar sesión");

            $userData = Session::getSessionParameters();
            switch (filter_input(INPUT_POST, "opcion"))
            {
                case 'GetStructure': $this->GetStructure($userData); break; 
                case 'GetAllStructure': $this->GetAllStructure($userData); break;
            }
        }
    }   
    /*************************************************************************
     * Se obtiene la estructura de la Tabla diseñada por el usuario, tomandola
     * del archivo de configuración generado al Construir una instancia
     */
    private function GetStructure($userData)
    {
        $TypeStructure = filter_input(INPUT_POST, "TypeStructure");
        $DataBaseName = $userData['dataBaseName'];
        
        $fullStructure = $this->getArrayStructureFile($DataBaseName);
   
        if(!is_array($fullStructure))
            return XML::XMLReponse ("Error", 0, "$fullStructure");
        
        if(array_key_exists($TypeStructure,$fullStructure))
        {
            $Estructura = $fullStructure["$TypeStructure"];
            $ArrayEstructura = $this->getPropertiesFromStructure($TypeStructure,$Estructura);
            if(is_array($ArrayEstructura))
                $this->CreateXMLStruct($ArrayEstructura['structure'],$ArrayEstructura['catalog']);    
            else
                XML::XMLReponse ("Error", 0, $ArrayEstructura);

        }
        else
            return XML::XMLReponse("Error", 0,"<p>No existe el registro de estructura para <b>$TypeStructure</b>, o puede que no se haya creado correctamente</p>");
    
    }

    /**
     * @param $dataBaseName
     * @return array|bool|string
     */
    public function getArrayStructureFile($dataBaseName) {
        try {
            $RoutFile = dirname(getcwd());

            if(!file_exists("$RoutFile/Configuracion/$dataBaseName.ini"))
                return "No existe el archivo de configuración estructural.";

            $Estructura = file_get_contents ("$RoutFile/Configuracion/$dataBaseName.ini");
            $ini_utf8     = iconv("ISO-8859-1", "UTF-8", $Estructura);
            $ini_array    = parse_ini_string($ini_utf8, TRUE);

            return $ini_array;
        } catch (Exception $e) {
            return "Error al abrir el registro de estructura de la instancia $dataBaseName. Detalles: ".$e->getMessage();
        }

    }

    /**
     * @param $dataBaseName
     * @return array|bool|string
     */
    public static function getArrayStructure($dataBaseName) {
        try {
            $RoutFile = dirname(getcwd());

            if(!file_exists("$RoutFile/Configuracion/$dataBaseName.ini"))
                return "No existe el archivo de configuración estructural.";

            $Estructura = file_get_contents ("$RoutFile/Configuracion/$dataBaseName.ini");
            $ini_utf8     = iconv("ISO-8859-1", "UTF-8", $Estructura);
            $ini_array    = parse_ini_string($ini_utf8, TRUE);

            return $ini_array;
        } catch (Exception $e) {
            return "Error al abrir el registro de estructura de la instancia $dataBaseName. Detalles: ".$e->getMessage();
        }

    }
    
    /* Devuelve la estrucutura por default y la definida por el usuario */
    private function GetAllStructure($userData)
    {
        $XML=new XML();
        $TypeStructure = filter_input(INPUT_POST, "TypeStructure");
        $DataBaseName = $userData['dataBaseName'];
        $catalogo=0;
        $Estructura_= $this->getArrayStructureFile($DataBaseName);
        if(!array_key_exists($TypeStructure,$Estructura_))
        {
            $XML->ResponseXML("Error", 0,"<p>No existe el registro de estructura para $TypeStructure, o puede que no se haya creado correctamente</p>");
            return 0;
        }       
        
        $Estructura=$Estructura_["$TypeStructure"];
        $ArrayEstructura=array();
        for($cont=0;$cont<count($Estructura);$cont++)
        {
            $cadena=explode('###', $Estructura[$cont]);
            $ArrayIteracion=array();

            if($cadena[0]=='Properties')/* Definición dada por el usuario */
            {
                for($a=1;$a<count($cadena);$a++)/* En 1 se ignora el primer elemento que es Properties */
                {
                    $CadenaCampo=  explode(" ", $cadena[$a]); /* Dívide en dos cada bloque type###valor  del  archivo de configuración*/
                    if(!array_filter($CadenaCampo))continue;
                    $type=$CadenaCampo[0];$type_valor=$CadenaCampo[1];
                    $ArrayIteracion[$type]=$type_valor;
                }
                $ArrayEstructura[]=$ArrayIteracion;
            }
            else
            {
                if($cadena[0]=='Tipo')/* Cuando es un catálogo */
                {
                    $ArrayIteracion['Tipo']=$cadena[1];
                    $ArrayEstructura[]=$ArrayIteracion;
                    $catalogo=1;       /* Bandera que detecta cuando es de tipo catálogo */
                }
                $ArrayIteracion['Campo']='P';
                $ArrayIteracion['name']=$cadena[0];
                for($a=1;$a<count($cadena);$a++)/* En 1 se ignora el primer elemento que es Properties */
                {
                    $CadenaCampo=  explode(" ", $cadena[$a]);
                    if(!array_filter($CadenaCampo))continue;
                    $type=$CadenaCampo[0];$type_valor=$CadenaCampo[1];
                    $ArrayIteracion[$type]=$type_valor;
                }     
                $ArrayEstructura[]=$ArrayIteracion;
            }              
        }
               
        if(!(count($ArrayEstructura)>0)){$XML->ResponseXML("Error", 0,"<p>No existe la estructura <b>$TypeStructure</b></p>");}        
        $this->CreateXMLStruct($ArrayEstructura,$catalogo); 
    }
    

    function getPropertiesFromStructure($TypeStructure,$Estructura)
    {
        $catalogo=0;
        $XML=new XML();
        $ArrayEstructura=array();
        for($cont=0;$cont<count($Estructura);$cont++)
        {
            $cadena=explode('###', $Estructura[$cont]);
            $ArrayIteracion=array();

            if($cadena[0]=='Properties')/* Definición dada por el usuario */
            {
                for($a=1;$a<count($cadena);$a++)/* En 1 se ignora el primer elemento que es Properties */
                {
                    $CadenaCampo=  explode(" ", $cadena[$a]); /* Dívide en dos cada bloque type###valor  del  archivo de configuración*/
                    if(!array_filter($CadenaCampo))continue;
                    $type=$CadenaCampo[0];$type_valor=$CadenaCampo[1];
                    $ArrayIteracion[$type]=$type_valor;
                }
                $ArrayEstructura[]=$ArrayIteracion;
            }
            else
            {
                if($cadena[0]=='Tipo')/* Cuando es un catálogo */
                {
                    $ArrayIteracion['Tipo']=$cadena[1];
                    $ArrayEstructura[]=$ArrayIteracion;
                    $catalogo=1;       /* Bandera que detecta cuando es de tipo catálogo */
                }
//                $ArrayIteracion['Campo']='P';
//                $ArrayIteracion['name']=$cadena[0];
//                for($a=1;$a<count($cadena);$a++)/* En 1 se ignora el primer elemento que es Properties */
//                {
//                    $CadenaCampo=  explode(" ", $cadena[$a]);
//                    if(!array_filter($CadenaCampo))continue;
//                    $type=$CadenaCampo[0];$type_valor=$CadenaCampo[1];
//                    $ArrayIteracion[$type]=$type_valor;
//                }     
//                $ArrayEstructura[]=$ArrayIteracion;
            }                        
        }
        
        if(!(count($ArrayEstructura)>0))
            return "No existen metadatos definidos por el usuario.";
        
        return array('structure'=>$ArrayEstructura, 'catalog'=>$catalogo);
    }
    
    /***************************************************************************
     * Devuelve los campos por default
     */
    function ReturnStructureDefault($TypeStructure,$Estructura)
    {
        $ArrayEstructura=array();
        for($cont=0;$cont<count($Estructura);$cont++)
        {
            $cadena=explode('###', $Estructura[$cont]);
            $ArrayIteracion=array();

            if($cadena[0]!='Properties')/* Definición dada por el usuario */
            {
                if($cadena[0]=='Tipo')/* Cuando es un catálogo */
                {
                    $ArrayIteracion['Tipo']=$cadena[1];
                    $ArrayEstructura[]=$ArrayIteracion;
                }
                $ArrayIteracion['Campo']='Default';
                $ArrayIteracion['name']=$cadena[0];
                for($a=1;$a<count($cadena);$a++)/* En 1 se ignora el primer elemento que es Properties */
                {
                    $CadenaCampo=  explode(" ", $cadena[$a]);
                    if(!array_filter($CadenaCampo)){continue;}
                    $type=$CadenaCampo[0];$type_valor=$CadenaCampo[1];
                    $ArrayIteracion[$type]=$type_valor;
                }     
                $ArrayEstructura[]=$ArrayIteracion;
            }                        
        }
        
        return $ArrayEstructura;
    }
    
    function ReturnStructure($TypeStructure,$Estructura)
    {
        $catalogo=0;
        $ArrayEstructura=array();
        
        for($cont=0;$cont<count($Estructura);$cont++)
        {
            $cadena=explode('###', $Estructura[$cont]);
            $ArrayIteracion=array();

            if($cadena[0]=='Properties')/* Definición dada por el usuario */
            {
                for($a=1;$a<count($cadena);$a++)/* En 1 se ignora el primer elemento que es Properties */
                {
                    $CadenaCampo=  explode(" ", $cadena[$a]); /* Dívide en dos cada bloque type###valor  del  archivo de configuración*/
                    if(!array_filter($CadenaCampo))continue;
                    $type=$CadenaCampo[0];$type_valor=$CadenaCampo[1];
                    $ArrayIteracion[$type]=$type_valor;
                }
                $ArrayEstructura[]=$ArrayIteracion;
            }
            else
            {
                if($cadena[0]=='Tipo')/* Cuando es un catálogo */
                {
                    $ArrayIteracion['Tipo']=$cadena[1];
                    $ArrayEstructura[]=$ArrayIteracion;
                    $catalogo=1;       /* Bandera que detecta cuando es de tipo catálogo */
                }
//                $ArrayIteracion['Campo']='P';
//                $ArrayIteracion['name']=$cadena[0];
//                for($a=1;$a<count($cadena);$a++)/* En 1 se ignora el primer elemento que es Properties */
//                {
//                    $CadenaCampo=  explode(" ", $cadena[$a]);
//                    if(!array_filter($CadenaCampo))continue;
//                    $type=$CadenaCampo[0];$type_valor=$CadenaCampo[1];
//                    $ArrayIteracion[$type]=$type_valor;
//                }     
//                $ArrayEstructura[]=$ArrayIteracion;
            }                        
        }
        
        return $ArrayEstructura;
    }
    
    private function CreateXMLStruct($ArrayStruct,$catalogo)
    {
        $doc  = new DOMDocument('1.0','utf-8');
        $doc->formatOutput = true;
        $root = $doc->createElement("Estructura");
        $doc->appendChild($root);       
        for($cont=0;$cont<count($ArrayStruct);$cont++)
        {
            $Campo=$doc->createElement("Campo");
            if($catalogo==1){$Campo=$doc->createElement("Campo");$tipo=$doc->createElement("tipo",$ArrayStruct[$cont]['Tipo']);$Campo->appendChild($tipo); $root->appendChild($Campo); $catalogo=0; continue;}
                        
            $name=$doc->createElement("name",$ArrayStruct[$cont]['name']);
            $Campo->appendChild($name);
            $type=$doc->createElement("type",$ArrayStruct[$cont]['type']);            
            $Campo->appendChild($type);
            if(isset($ArrayStruct[$cont]['long']))
            {
                $long=$doc->createElement("long",$ArrayStruct[$cont]['long']);
                $Campo->appendChild($long);
            }
            if(isset($ArrayStruct[$cont]['required']))
            {
                $required=$doc->createElement("required",$ArrayStruct[$cont]['required']);
                 $Campo->appendChild($required);
            }
            
            $root->appendChild($Campo);
        }       
        header ("Content-Type:text/xml");
        echo $doc->saveXML();
    }
    
    public static function CreateProperty($FieldName, $FieldType, $FieldLength, $RequiredField)
    {
        $FieldTypeMysql = '';
        
        if((int)($FieldLength)>0)
        {
//            $FieldType.="($FieldLength)";
            $FieldTypeMysql = "$FieldType($FieldLength)";
            $FieldLength = "long ".$FieldLength."###";         
        }    
        else
            $FieldTypeMysql = $FieldType;
        
        if(strcasecmp($RequiredField, "true")==0)
                $FieldTypeMysql.= " NOT NULL";
        
        if(strcasecmp($RequiredField, "true")!=0 and strcasecmp($RequiredField, "false")!=0)
                $RequiredField = '';
        else
            $RequiredField = "required $RequiredField###";
        
        $NewProperty = "Properties###name $FieldName###type $FieldType###$FieldLength$RequiredField";
    
        $FieldDetail = array("FieldDetail"=>$NewProperty, "FieldMySql"=>$FieldTypeMysql);
        
        return $FieldDetail;
    }
    
    /* Función que elimina del archivo de configuración un campo de la estructura */
    public static function DeleteField($DataBaseName, $StructureName,  $FieldName)
    {
        $RoutFile = dirname(getcwd());        
        
        if(!file_exists("$RoutFile/Configuracion/$DataBaseName.ini"))
            return XML::XMLReponse ("Error", 0, "<p><b>Error</b> no existe el registro de estructura de la intsnaica <b>$DataBaseName</b></p>");
        
        $Structure = self::getArrayStructure($DataBaseName);

        if(!($gestor = fopen("$RoutFile/Configuracion/$DataBaseName.ini", "w")))
                return XML::XMLReponse("Error", 0, "<p><b>Error</b> al intentar abrir el registro de estructura de la instancia <b>$DataBaseName</b><br>Detalles:<br><br>$gestor");
        
        foreach ($Structure as $key =>$Section)
        {
            fwrite($gestor, "$key='$key'".PHP_EOL);
            for($cont = 0; $cont < count($Section); $cont++)
            {
                $property = explode("###", $Section[$cont]);
                if(strcasecmp($StructureName, $key)==0)
                    if(strcasecmp($property[0], "Properties")==0)
                    {
                        $Field = explode(" ", $property[1]);
                        if(strcasecmp($FieldName, $Field[1])==0)
                            continue;
                    }
                fwrite($gestor, $key."[]='".$Section[$cont]."'".PHP_EOL);
            }
        }
        
        fclose($gestor);
        
        return 1;
    }
    
    public static function AddPropertyIntoStructureConfig($DataBaseName,$StructureName, $NewProperty)
    {
        $Structure = self::getArrayStructure($DataBaseName);

        $Structure[$StructureName][] = $NewProperty;
        
        if(!($gestor = fopen("../Configuracion/$DataBaseName.ini", "w")))
                return $gestor;
        
        foreach ($Structure as $key =>$Section)
        {
            fwrite($gestor, "$key='$key'".PHP_EOL);
            for($cont = 0; $cont < count($Section); $cont++)
            {
                fwrite($gestor, $key."[]='".$Section[$cont]."'".PHP_EOL);
            }
        }
        
        fclose($gestor);
        
        return 1;
    }
    
    /* Elimina una estructura completa p.e. un repositorio */
    public static function DeleteStructure($DataBaseName, $StructureName)
    {
        $RoutFile = dirname(getcwd());

        $Structure = self::getArrayStructure($DataBaseName);

        if(!($gestor = fopen("$RoutFile/Configuracion/$DataBaseName.ini", "w")))
                return XML::XMLReponse("Error", 0, "<p><b>Error</b> al intentar abrir el registro de estructura de la instancia <b>$DataBaseName</b><br>Detalles:<br><br>$gestor");
        
            foreach ($Structure as $key =>$Section) {
                    if(strcasecmp($StructureName, $key) != 0) {
                        fwrite($gestor, "$key='$key'".PHP_EOL);
                        for($cont = 0; $cont < count($Section); $cont++)
                        {
                            fwrite($gestor, $key."[]='".$Section[$cont]."'".PHP_EOL);
                        }
                    }           
        }
        
        fclose($gestor);
        
        return 1;
    }

}

$designer=new DesignerForms();
