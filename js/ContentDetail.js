/* 
 * Operaciones sobre el repositorio de archivos (Borrado, Edición, etc)
 */

/* global TableContentdT, EnvironmentData, TableEnginedT, Hdetalle, Wdetalle, GlobalDatePicker, WindowConfirmacion, LanguajeDataTable, DownloadTabledT */

TableContentDT = '';
TableContentdT = '';
$(document).ready(function()
{
    
});

/********************************************************************************
 *  Obtiene los metadatas del archivo
 *  
 * @param {type} Source
 * @param {type} IdGlobal
 * @param {type} IdFile
 * @returns {Number}
 */
function GetDetalle(Source, IdGlobal, IdFile)
{    
    var xml=0;

    var DocumentEnvironment = new ClassDocumentEnvironment(Source, IdGlobal, IdFile);
    DocumentEnvironment.GetProperties();
    console.log(DocumentEnvironment);
    if(!DocumentEnvironment.IdFile>0){Advertencia("No selecciono un documento."); return 0;}
        
    Loading();
    
    $('#div_detalle').empty();
    $('#div_detalle').append('<div class="titulo_ventana">Detalle del Documento '+DocumentEnvironment.FileName+'</div>');
    $('#div_detalle').append('<center><table id="tabla_DetalleArchivo"><thead><tr><th>Nombre del Campo</th><th>Valor</th></tr></thead></table></center>');
    
    $.ajax({
      async:false, 
      cache:false,
      dataType:"html", 
      type: 'POST',   
      url: "php/ContentManagement.php",
      data: 'opcion=GetDetalle&IdRepositorio='+DocumentEnvironment.IdRepository+'&NombreRepositorio='+DocumentEnvironment.RepositoryName+"&IdArchivo="+IdFile+'&NombreArchivo='+DocumentEnvironment.FileName, 
      success:  function(xml)
      {                
          $('#Loading').dialog('close');       
           if($.parseXML( xml )===null){errorMessage(xml);return 0;}else xml=$.parseXML( xml );
                                      
            $(xml).find("CampoRepositorio").each(function()
            {
                var $CampoRepositorio=$(this);
                var Campo=$CampoRepositorio.find('Campo').text();
                var Valor=$CampoRepositorio.find('Valor').text();
                var type=$CampoRepositorio.find('type').text();
                var TipoCampo=$CampoRepositorio.find('TipoCampo').text();
                var required=$CampoRepositorio.find('required').text();
                var length=$CampoRepositorio.find("long").text();
                var disabled='';
                var CampoVisible=$CampoRepositorio.find('CampoVisible').text();
                
                if(required==="true" || required === true)
                    required = 'class = "required FormStandart"';
                else
                    required='class = "FormStandart"';
                                
                if(TipoCampo==='Default' || TipoCampo==='default'){disabled='disabled'; required='class = "FormStandart"';}   /* Los campos por default no pueden modificarse */
//                console.log('campo de usuario '+Campo+' '+type+' '+length);         

                /* Los campos por default del repositorio no son visibles por lo tanto se esconden 
                 * al usuario y se agregan dos filas para no alterar el intercalado de colores de la tabla*/
                if(CampoVisible==='0')
                {
                    CampoVisible='style = "display:none"';  
                    $('#tabla_DetalleArchivo tr:last').after('<tr '+CampoVisible+'><td>'+Campo+'</td><td><input type="text" id="det_'+Campo+'" value="'+Valor+'" '+disabled+'></td></tr><tr '+CampoVisible+'></tr>');
                }
                else
                {
                    $('#tabla_DetalleArchivo tr:last').after('<tr '+CampoVisible+'><td>'+Campo+'</td><td><input type="text" id="det_'+Campo+'" value="'+Valor+'"  '+required+' '+disabled+'></td></tr>');
                    if(disabled!=='disabled')
                    {
//                        console.log('campo de usuario '+Campo+' '+type+' '+length);
                        $('#det_'+Campo).attr('FieldType',type);
                        $('#det_'+Campo).attr('FieldLength',length);
                    }
                }
                if(type==='DATE'){$('#det_'+Campo).datepicker(GlobalDatePicker);}
                if(type==='date'){$('#det_'+Campo).datepicker(GlobalDatePicker);}
                     
            });  
                        
            if($(xml).find('CampoRepositorio').length>0)
            {
                $('#div_detalle').dialog({width:Wdetalle, minWidth:600, Height:Hdetalle, minHeight:600,position:{ my: "center", at: "center", of: window }, 
                title:"Vista de Detalle", modal:true,
                buttons:{
                  "Modificar":{click:function(){ConfirmDetailModify(xml,DocumentEnvironment);}, class:'CMModifyButton', text:"Modificar"},  /* Se modifica el detalle mostrado en pantalla */
                  "Cerrar":{click:function(){$(this).dialog('close');},text:'Cerrar'}
                }});
            
            }                    
            
            var Cadena='';  /* Cadena con los valores  */
            $(xml).find("Catalogo").each(function()
            {
                var $Catalogo=$(this);
                $Catalogo.children('Valor').each(function(){
                    var $NodoCampo=$(this);
                    var Campo=$NodoCampo.find('Campo').text();
                    var Valor=$NodoCampo.text();
                    Cadena+=Valor+' , ';
                });
                var Tipo=$Catalogo.find('Tipo').text();                
                var NombreCatalogo=$Catalogo.find('NombreCatalogo').text();
                var IdCatalogo=$Catalogo.find('IdCatalogo').text();

                /* En la tabla que muestra el detalle los tipo List Search contieen un botón y un select con
                 * la información que selecciono el usuario al cargar el archivo. Al pulsar se muestra el
                 * listado del mismo para hacer alguna modificación. */
                
                var EstructuraCatalogo = GeStructure(DocumentEnvironment.RepositoryName+"_"+NombreCatalogo); 
                if(String(Tipo) === 'ListSearch')/* Se introduce un botón para elegir un nuevo elemento del catálogo */
                {
                    console.log("ListSearch::"+NombreCatalogo);
                    $('#tabla_DetalleArchivo tr:last').after('<tr><td><input type="button" value="Abrir '+NombreCatalogo+'" id="det_button_'+NombreCatalogo+'"></td><td><select id="det_select_'+NombreCatalogo+'" class="FormStandart"><option value="'+IdCatalogo+'">'+Cadena.slice(0,60)+'</select></td></tr>');                    
                    DetailSetValuesToListSearch(DocumentEnvironment.RepositoryName, EstructuraCatalogo,NombreCatalogo);                    
                }
                
                $('#det_button_'+NombreCatalogo).button();
                
                if(String(Tipo) === 'List')
                {
                    $('#tabla_DetalleArchivo tr:last').after('<tr><td>'+NombreCatalogo+'</td><td><select class="FormStandart" id="det_select_'+NombreCatalogo+'"><option value="'+IdCatalogo+'">'+Cadena.slice(0,60)+'</select></td></tr>');                                       
                    DetailSetValuesToList(DocumentEnvironment.RepositoryName, EstructuraCatalogo,NombreCatalogo);
                }
                Cadena='';                
            });  
            
            var Forms = $('#tabla_DetalleArchivo tr td input.FormStandart');
            var FieldsValidator = new ClassFieldsValidator();   
            FieldsValidator.InspectCharacters(Forms);  
            
            $(xml).find("Error").each(function()
            {
                var $Instancias=$(this);
                var estado=$Instancias.find("Estado").text();
                var mensaje=$Instancias.find("Mensaje").text();
                errorMessage(mensaje);
            });                      
      },
      beforeSend:function(){},
      error:function(objXMLHttpRequest){$('#Loading').dialog('close'); errorMessage(objXMLHttpRequest);}
    });
    
    return xml;
}

/**
 * @description Llena un Select tipo List (Catálogo).
 * @param {type} repositoryName
 * @param {type} xmlStruct
 * @param {type} NombreCatalogo
 * @returns {undefined}
 */
   function DetailSetValuesToList(repositoryName, xmlStruct,NombreCatalogo)
   {
        var catalogManager = new ClassCatalogAdministrator();
        var xml = catalogManager.GetCatalogRecordsInXml(repositoryName, NombreCatalogo,'List');
//        alert("List"+xml);
        var ArrayStruct=new Array();var cont=0;
        $(xmlStruct).find("Campo").each(function()
        {               
            var $Campo=$(this);
            var tipo=$Campo.find("tipo").text();
            if(tipo.length>0){return;}       /* Tipo del List */    
            var name=$Campo.find("name").text();
            ArrayStruct[cont]=name;
            cont++;
        });
       /* El Array Struct Contiene la estructura del catálogo y a partir de ella se recorre el XML que contiene
        * su información (Del catálogo) */
        $(xml).find("CatalogRecord").each(function()
        {               
            var $Campo=$(this);     
            var IdRegistro=$Campo.find('Id'+NombreCatalogo).text();
            var valores='', campo='';
            for(var cont=0; cont<ArrayStruct.length; cont++)
            {
                valores+=' , '+$Campo.find(ArrayStruct[cont]).text();
            }
            valores=valores.slice(0,60);/* Se acorta el texto del Select */
            $('#det_select_'+NombreCatalogo).append('<option value="'+IdRegistro+'">'+valores+'</option>');
        });             
   }

/************************ Lenado de ListSearch************************* */
/****************************************************************************
    * Llena un div y se le inserta una tabla con la información de un catálogo tipo ListSearch (Catálogo)
    /**
     * @param {type} repositoryName
     * @param {type} xmlStruct
     * @param {type} NombreCatalogo
     * @returns {undefined}  */
   function DetailSetValuesToListSearch(repositoryName, xmlStruct,NombreCatalogo)
   {              
       var TableCatalogdT = undefined, TableCatalogDT = undefined;
       
       $('#div_CatalogoDetalle_'+NombreCatalogo).remove();
              
       $('#div_detalle').append('<div id="div_CatalogoDetalle_'+NombreCatalogo+'" style="display:none">\n\
            <div class="titulo_ventana">Contenido en '+NombreCatalogo+'</div>\n\
            <table id="table_CatalogoDetalle_'+NombreCatalogo+'" class="display hover"></table>\n\
        </div>');             
       
       /* Construcción de Tabla con registro de elementos del catálogo seleccionado (Al pulsar el botón con el nombre del catálogo en la vista con metadatos) */
        var catalogManager = new ClassCatalogAdministrator();
        var xml = catalogManager.GetCatalogRecordsInXml(repositoryName ,NombreCatalogo,'ListSearch');        
        var ArrayStruct = new Array();var cont=0;
        var thead='<thead><tr>';
        $(xmlStruct).find("Campo").each(function()
        {               
            var $Campo=$(this);
            var tipo=$Campo.find("tipo").text();
            if(tipo.length>0){return;}   /* Tipo del List */    
            var name=$Campo.find("name").text();
            ArrayStruct[cont]=name;
            cont++;
            thead+='<th>'+name+'</th>';
       });
       thead+="</tr></thead>";
       /* Columnas que contienen la información de un list search */
       $('#table_CatalogoDetalle_'+NombreCatalogo).append(thead);
       
       TableCatalogdT = $('#table_CatalogoDetalle_'+NombreCatalogo).dataTable({oLanguage:LanguajeDataTable});    
       TableCatalogDT = new $.fn.dataTable.Api('#table_CatalogoDetalle_'+NombreCatalogo);
       
       /* El Array Struct Contiene la estructura del catálogo y a partir de ella se recorre el XML que contiene
        * su información (Del catálogo) */       
       $(xml).find("CatalogRecord").each(function()
        {             
           var $Campo=$(this);     
           var IdRecord = $Campo.find('Id'+NombreCatalogo).text();
           var data = [];  /* Guarda la fila que será insertada */
           
           for(var cont=0; cont<ArrayStruct.length; cont++)  /* Recorrer los datos que contiene cada fila del catálogo */
           {
               var field = ArrayStruct[cont];
               var value = $Campo.find(field).text();               
               data[data.length] = value;
           }   
           
            var ai = TableCatalogDT.row.add(data).draw();
            var n = TableCatalogdT.fnSettings().aoData[ ai[0] ].nTr;
            n.setAttribute('id',IdRecord);           
       });

       $('#table_CatalogoDetalle_'+NombreCatalogo+' tbody').on( 'click', 'tr', function (){
               TableCatalogDT.$('tr.selected').removeClass('selected');
               $(this).addClass('selected');                               
        } );
       
        /*---- Respuesta al pulsar sobre los botones de Catálogo (Vista de Metadatos) ---*/
        ActionsOfCatalogButton(NombreCatalogo, TableCatalogdT, TableCatalogDT);              
   }
   
    /* Respuesta al pulsar sobre el botón con el nombre del catálogo en la vista con metadatos*/
   function ActionsOfCatalogButton(NombreCatalogo, TableCatalogdT, TableCatalogDT)
   {        
        $('#det_button_'+NombreCatalogo).click(function()
        {
            $('#div_CatalogoDetalle_'+NombreCatalogo).dialog({width:800,minWidth:800,height:450,minHeight:450,modal:true,title:NombreCatalogo,buttons:{                  
            "Aceptar":function()
            {
                var IdOpcion = $('#table_CatalogoDetalle_'+NombreCatalogo+' tr.selected').attr('id');
                var TextoSeleccion='';
                $('#table_CatalogoDetalle_'+NombreCatalogo+' tr.selected').each(function()
                {
                    var rowData = TableCatalogDT.row(this).data();
                    for(var cont=0; cont<rowData.length; cont++)
                    {
                        TextoSeleccion+=rowData[cont]+' | ';
                    }                        
                });
                TextoSeleccion=TextoSeleccion.slice(0,60);
                if(IdOpcion>0)
                {
                    $('#det_select_'+NombreCatalogo+' option').remove();
                    $('#det_select_'+NombreCatalogo).append('<option value="'+IdOpcion+'">'+TextoSeleccion+'</option>');                       
                }                    
                $(this).dialog('close');
            },
            "Cancelar":function(){$(this).dialog('close');}}});
        });
   }
   
   function ConfirmDetailModify(xml,DocumentEnvironment)
   {
       var idRepository = DocumentEnvironment.IdRepository;
       if(!parseInt(idRepository) > 0)
           return Advertencia("No fue posible obtener el identificador del repositorio");
       
       if(!validateSystemPermission(idRepository, '3c59dc048e8850243be8079a5c74d079', 1))
           return Advertencia("No tiene permiso de realizar esta acción");
           
        var Forms = $('#tabla_DetalleArchivo tr td input.FormStandart');
        var FieldsValidator = new ClassFieldsValidator();   
        var validation = FieldsValidator.ValidateFields(Forms);

        if(validation===0)
            return 0;
        
        $('#ConfirmDetailModify').remove();
        var content = $('<div>', {id: "ConfirmDetailModify"});
        $('body').append(content);
        $('#ConfirmDetailModify').append('Está a punto de modificar los metadatos del documento. ¿Desea continuar?');
        $('#ConfirmDetailModify').dialog(WindowConfirmacion,{buttons: {"Aceptar": function() { $(this).dialog('close'); DetailModify(xml,DocumentEnvironment);},Cancelar: function() {$( this ).dialog( "close" );}}});
   }
                
/*
 * 
 * @param {type} XmlDetalle
 * @param {type} IdFile: Desde la tabla de búsqueda se envia el IdGlobal y desde la tabla de documentos (Content) se envía el Id del documento
 * @param {type} NombreArchivo
 * @returns {undefined}
 */

function DetailModify(XmlDetalle,DocumentEnvironment) 
{                             
    var CatalogosXml = '';    
    var active = $( "#tabs" ).tabs( "option", "active" );               
        
    $('#div_detalle').append('<div class="Loading" id = "CMModifyDetail"><img src="../img/loadinfologin.gif"></div>');
               
    $(XmlDetalle).find("Catalogo").each(function()
    {
        var $Catalogo=$(this);
        $Catalogo.children('Valor').each(function()
        {
            var $NodoCampo=$(this);
            var Campo=$NodoCampo.find('Campo').text();
            var Valor=$NodoCampo.text();

        });

        var Tipo=$Catalogo.find('Tipo').text();                
        var NombreCatalogo=$Catalogo.find('NombreCatalogo').text();
        var IdCatalogo=$Catalogo.find('IdCatalogo').text();                
        var id=$('#det_select_'+NombreCatalogo).val();  /* Se toman los valores de cada catalogo */
        var TextoSelectCatalogo=$('#det_select_'+NombreCatalogo+' option:selected').html();

        CatalogosXml+='<Catalogo><name>'+NombreCatalogo+'</name><value>'+id+'</value><type>INT</type><TextoSelect>'+TextoSelectCatalogo+'</TextoSelect></Catalogo>';

    });   
        
    var XMLResponse="<MetaDatas version='1.0' encoding='UTF-8'>";
    $(XmlDetalle).find("CampoRepositorio").each(function()
    {               
       var $Campo=$(this);
       var name=$Campo.find("Campo").text();
       var type=$Campo.find("type").text();

       var value=$('#det_'+name).val();/* Id que contendrá cada elemento recuperado */

       var XML='<Detalle>\n\
                    <name>'+name+'</name>\n\
                    <value>'+value+'</value>\n\
                    <type>'+type+'</type>\n\
                </Detalle>';
        XMLResponse=XMLResponse+XML;
   });
             
       XMLResponse+=CatalogosXml;
       XMLResponse+='</MetaDatas>';  

    $.ajax({
    async:true,
    cache:false,
    dataType:"json",
    type: 'POST',   
    url: "php/ContentManagement.php",
    //data: 'opcion=DetailModify&'+'&IdRepositorio='+DocumentEnvironment.IdRepository+ '&IdEmpresa = '+DocumentEnvironment.IdEnterprise+ '&NombreEmpresa = '+ DocumentEnvironment.EnterpriseName +'&NombreRepositorio='+DocumentEnvironment.RepositoryName+"&IdFile="+DocumentEnvironment.IdFile+'&XMLResponse='+XMLResponse+'&NombreArchivo='+DocumentEnvironment.FileName+'&IdGlobal='+DocumentEnvironment.IdGlobal,
    data: {
        opcion:'DetailModify',
        IdRepositorio:DocumentEnvironment.IdRepository,
        //IdEmpresa:DocumentEnvironment.IdEnterprise
        //NombreEmpresa:DocumentEnvironment.EnterpriseName
        NombreRepositorio:DocumentEnvironment.RepositoryName,
        IdFile:DocumentEnvironment.IdFile,
        NombreArchivo:DocumentEnvironment.FileName,
        XMLResponse:XMLResponse,
        IdGlobal:DocumentEnvironment.IdGlobal
    },
    success:  function(reponse){
        if(reponse.status){
            $('#CMModifyDetail').remove();
            Notificacion(reponse.message);

        }else {
            errorMessage(reponse.message);
        }
    },
    beforeSend:function(){},
    error: function(jqXHR, textStatus, errorThrown){$('#CMModifyDetail').remove();errorMessage(textStatus +"<br>"+ errorThrown);}
  });
      
      $('#Loading').dialog('close');
   }


/*******************************************************************************
 *  Al activar un directorio se obtiene la lista de archivos que se encuentran 
 *  en ese directorio.
 *
 * @param {type} IdDirectory
 * @returns {undefined}
 */
function GetFiles(IdDirectory)
{
    var IdRepositorio=$('#CM_select_repositorios').val();
    var NombreRepositorio=$('#CM_select_repositorios option:selected').html();
    var Search=$('#form_engine').val();
    var arbol = $('#contentTree').dynatree("getTree");

    if($.type(arbol)==='object')
        $("#contentTree").dynatree("disable");
    else
        return;
    
    $.ajax({
        async:true, 
        cache:false,
        dataType:"html", 
        type: 'POST',   
        url: "php/ContentManagement.php",
        data: 'opcion=GetFiles&DataBaseName='+EnvironmentData.DataBaseName+'&IdUser='+EnvironmentData.IdUsuario+'&UserName='+EnvironmentData.NombreUsuario+'&IdGroup='+EnvironmentData.IdGrupo+'&IdRepository='+IdRepositorio+'&RepositoryName='+NombreRepositorio+'&Search='+Search+"&IdDirectory="+IdDirectory, 
        success:  function(xml){
            if($.parseXML( xml )===null){errorMessage(xml); return 0;}else xml=$.parseXML( xml );
            var emptyTest = $('#contentTree').is(':empty');
            if(!emptyTest)
            $("#contentTree").dynatree("enable");
            $(xml).find("Error").each(function()
            {
                var $Instancias=$(this);
                var estado=$Instancias.find("Estado").text();
                var mensaje=$Instancias.find("Mensaje").text();
                errorMessage(mensaje);
                return;
            });

            SetSearchResult(IdRepositorio,xml);                        
        },
        beforeSend:function(){},
    error: function(jqXHR, textStatus, errorThrown){errorMessage(textStatus +"<br>"+ errorThrown);}
    });
}

/* Se dibuja la tabla con el listado de archivos dentro de un directorio */
function SetSearchResult(IdRepository,xml)
{
    $('.contentDetail').empty();
    $('.contentDetail').append('<table id="table_DetailResult" class="display hover"></table>');
    $('#table_DetailResult').append('<thead><tr><th>Nombre</th><th>Fecha</th><th>Tipo</th><th>Resumen</th><th>Ver</th><th>Metadatos</th><th>Ruta</th><th></th></tr></thead><tbody></tbody>');
     TableContentdT = $('#table_DetailResult').dataTable({
         oLanguage:LanguajeDataTable, 
         "columns": [null,null,null,null,{ "width": "7%" },{ "width": "16%" },null,null]
     });
     
    TableContentDT = new $.fn.dataTable.Api( '#table_DetailResult' );
    
    var cont = 0;
    
     $(xml).find("Resultado").each(function()
    {               
        var $Resultado=$(this);
        var TipoArchivo=$Resultado.find("TipoArchivo").text();
        var FechaIngreso=$Resultado.find("FechaIngreso").text();
        var NombreArchivo=$Resultado.find("NombreArchivo").text();
        var Full=$Resultado.find("Full").text();
        var IdRepositorio=$Resultado.find("IdRepositorio").text();
        var Ruta=$Resultado.find("RutaArchivo").text();
        Full = Full.slice(0,200);
        
        var data=[
            /*[0]*/NombreArchivo,
            /*[1]*/FechaIngreso,
            /*[2]*/TipoArchivo,
            /*[3]*/Full,                                                                                                        /*Source, IdGlobal, IdFile */
            /*[4]*/'<img src="img/acuse.png" title="vista previa de "'+NombreArchivo+'" onclick="Preview(\''+TipoArchivo+'\', \'0\', \''+ IdRepositorio +'\' , \'Content\')">',
            /*[5]*/'<img src="img/metadata.png" title="Metadatos de '+NombreArchivo+'" onclick="GetDetalle(\'Content\', \'0\', \''+IdRepositorio+'\')">',
            /*[6]*/Ruta,
            /*[7]*/'<center><input type="checkbox" id="'+IdRepositorio+'"  class="checkbox_detail"></center>'
          ];   
         
        var ai = TableContentDT.row.add(data);
        var n = TableContentdT.fnSettings().aoData[ ai[0] ].nTr;
        n.setAttribute('id',IdRepositorio);

        if(cont===60){
            TableContentDT.draw();
            cont=0;
        }
        cont++;         
        
        /* Sí se encuentra el archivo en la lista de descarga se selecciona el Checkbox  */
        if(DownloadTabledT.$('tr[id='+IdRepository+IdRepositorio+']').length > 0)
            TableContentdT.$('tr[id='+IdRepositorio+']').find('.checkbox_detail').each(function(){
                $(this).prop("checked", "checked");
            });
        
    });
    
    TableContentDT.draw();          
    TableContentdT.fnSetColumnVis(6,false);   
    
    var downloads = new Downloads();
    
    /* Se recoge el estado del CheckBox para agregarlo a la lista de descarga */
    TableContentdT.$('.checkbox_detail').click(function(){
        var check = $(this).is(':checked');
        var IdCheck = $(this).attr('id');
        
        if(check)
            downloads.AddRow('Content',0,IdCheck);
        else
            downloads.RemoveRow(IdRepository, IdCheck);
    });        
                        
    $('#table_DetailResult tbody').on( 'click', 'tr', function (){
        TableContentDT.$('tr.selected').removeClass('selected');
        $(this).addClass('selected');

    } );
}






/*-----------------------------------------------------------------------------*
 *                          Carga manual de archivo
 ------------------------------------------------------------------------------*/
        
        
        
        
        
                
/*******************************************************************************
 *  Sube un Archivo en un directorio seleccionado llenando sus datos de forma manual
 * @returns {undefined}
 */
function CM_CargarArchivo()
{
    var repositoryName=$('#CM_select_repositorios option:selected').html();
    var IdRepositorio=$('#CM_select_repositorios').val();
    
    $('#CM_Carga').remove();
    var content = $('<div>', {id: 'CM_Carga', class: "detalle_archivo"});
    $('body').append(content);
    $('#CM_Carga').append('<center><img src="img/Archivos_converted.png" title="Caerga de Archivo"></center>');
    $('#CM_Carga').append('<table id="CM_TableMetadatasCarga"><thead><tr><th>Nombre del Campo</th><th>Valor</th></tr></thead></table>');
    $('#CM_TableMetadatasCarga').append('<tr><td><input type="file" id="CM_InputFileCarga" enctype="multipart/form-data"></td><td></td></tr>');

    
    var xml=SetTableStructura(repositoryName,"CM_TableMetadatasCarga",0);/* XML con la estructura de la tabla */
    var Catalogos=new Array();
    
    Catalogos = getCatalogs(IdRepositorio, repositoryName);
    
    var Forms = $('#CM_TableMetadatasCarga :text');
    var FieldsValidator = new ClassFieldsValidator();   
    FieldsValidator.InspectCharacters(Forms);
    
    $( "#CM_Carga").dialog({ width: 600,height:600,modal:true,minWidth:600,minHeight:600, resizable:true, buttons:{}, title:"Carga de un Archivo" });
    $( "#CM_Carga").dialog({buttons:{"Aceptar":function(){UploadMetadatas(IdRepositorio,xml,Catalogos);/*$(this).dialog('close');*/},"Cancelar":function(){$(this).dialog('close');}}});                     
}
   
   /*****************************************************************************
   * 
   * @param {type} IdRepositorio
   * @returns {undefined}Recupera de la BD los catálogos asociados a un repositorio 
   */
  function getCatalogs(IdRepositorio, repositoryName)
  {
      var ArrayCatalogos=new Array();
      $.ajax({
      async:false, 
      cache:false,
      dataType:"html", 
      type: 'POST',   
      url: "php/ContentManagement.php",
      data: "opcion=getCatalogos&DataBaseName="+EnvironmentData.DataBaseName+'&IdUsuario='+EnvironmentData.IdUsuario+"&IdRepositorio="+IdRepositorio, 
      success:  function(respuesta){
           var xml=respuesta; 
           
           var cont=0;
           $(xml).find("Empresa").each(function()
            {
               var $Empresa=$(this);
               var IdCatalogo=$Empresa.find("IdCatalogo").text();               
               var NombreCatalogo=$Empresa.find("NombreCatalogo").text();
               SetListProperties(repositoryName+"_"+NombreCatalogo, repositoryName ,NombreCatalogo);
               ArrayCatalogos[cont]=NombreCatalogo;
               cont++;
            });
            $(xml).find("Error").each(function()
            {
                var $Instancias=$(this);
                var estado=$Instancias.find("Estado").text();
                var mensaje=$Instancias.find("Mensaje").text();
                errorMessage(mensaje);
            });
      },
      beforeSend:function(){},
      error:function(objXMLHttpRequest){errorMessage(objXMLHttpRequest);}
    });
    return ArrayCatalogos;
  }
   /****************************************************************************
    * Inserta los catálogos en la pantalla de captura (Carga de Archivo).
    * 
    * @param {type} TypeStructure
    * @param {type} NombreCatalogo
    * @returns {undefined}
    */
   function SetListProperties(TypeStructure, repositoryName ,NombreCatalogo)
   {
       var xml = GeStructure(TypeStructure);

       $(xml).find("Campo").each(function()
        {               
           var $Campo=$(this);
           var tipo=$Campo.find("tipo").text();
           if(tipo.length>0)
           {
               if(tipo==="List"){SetValuesToList(xml,repositoryName, NombreCatalogo,tipo);} 
               if(tipo==='ListSearch'){SetValuesToListSearch(xml, repositoryName, NombreCatalogo,tipo);}
               return;
           }
           var name=$Campo.find("name").text();
           var type=$Campo.find("type").text();
           var long=$Campo.find("long").text();
           var required=$Campo.find("required").text();
       });
   }
   
   /*---------------------------------------------------------------------------
    * Llena un Select tipo List (Catálogo)
    *
    * @param {type} xmlStruct
    * @param {type} NombreCatalogo
    * @returns {undefined}
    ---------------------------------------------------------------------------*/
   function SetValuesToList(xmlStruct,NombreCatalogo)
   {
        $('#Catalogo_'+NombreCatalogo).remove();
        var IdRepositorio=$('#CM_select_repositorios').val();
        var repositoryName = $('#CM_select_repositorios option:selected').html();
        $('#CM_Carga').append('<p>'+NombreCatalogo+'<select id="Catalogo_'+NombreCatalogo+'"></select></p>');              
//       var xml=bringInformationCatalog(NombreCatalogo,'List'); 
        var catalogManager = new ClassCatalogAdministrator();
        var xml = catalogManager.GetCatalogRecordsInXml(repositoryName ,NombreCatalogo,'List');
       var ArrayStruct=new Array();var cont=0;
       $(xmlStruct).find("Campo").each(function()
        {               
           var $Campo=$(this);
           var tipo=$Campo.find("tipo").text();
           if(tipo.length>0){return;}       /* Tipo del List */    
           var name=$Campo.find("name").text();
           ArrayStruct[cont]=name;
           cont++;
       });
       /* El Array Struct Contiene la estructura del catálogo y a partir de ella se recorre el XML que contiene
        * su información (Del catálogo) */
       $(xml).find("CatalogRecord").each(function()
        {               
           var $Campo=$(this);     
           var IdRegistro=$Campo.find('Id'+NombreCatalogo).text();
           var valores='', campo='';
           for(var cont=0; cont<ArrayStruct.length; cont++)
           {
               valores+=$Campo.find(ArrayStruct[cont]).text()+", ";
           }
           valores=valores.slice(0,60);/* Se acorta el texto del Select */
           $('#Catalogo_'+NombreCatalogo).append('<option value="'+IdRegistro+'">'+valores+'</option>');
       });              
   }
   
   /*----------------------------------------------------------------------------
    * Llena un div y se le inserta una tabla con la información de un catálogo tipo ListSearch (Catálogo)
    * 
    * @param {type} xmlStruct
    * @param {type} NombreCatalogo
    * @returns {undefined}
    ----------------------------------------------------------------------------*/
   function SetValuesToListSearch(xmlStruct, repositoryName ,NombreCatalogo)
   {
       var TableCatalogdT, TableCatalogDT;
       $('#Catalogo_'+NombreCatalogo).remove();
       $('#div_Catalogo_'+NombreCatalogo).remove();
       $('#CM_Carga').append('<div id="div_Catalogo_'+NombreCatalogo+'" style="display:none"><table id="table_Catalogo_'+NombreCatalogo+'" class="hover"></table></div>');
       $('#CM_TableMetadatasCarga').append('<tr><td><input type="button" value="'+NombreCatalogo+'" id="button_'+NombreCatalogo+'"></td><td>\n\
        <select id="Catalogo_'+NombreCatalogo+'"><option value="0">Esperando Selección en '+NombreCatalogo+'</option></select></td></tr>');
       $('#button_'+NombreCatalogo).button();
       
  /* Construcción de Tabla con registro de elementos del catálogo seleccionado (Al pulsar el botón con el nombre del catálogo en la vista con metadatos) */
        var catalogManager = new ClassCatalogAdministrator();
        var xml = catalogManager.GetCatalogRecordsInXml(repositoryName ,NombreCatalogo,'ListSearch');        
        var ArrayStruct = new Array();var cont=0;
        var thead='<thead><tr>';
        $(xmlStruct).find("Campo").each(function()
        {               
            var $Campo=$(this);
            var tipo=$Campo.find("tipo").text();
            if(tipo.length>0){return;}   /* Tipo del List */    
            var name=$Campo.find("name").text();
            ArrayStruct[cont]=name;
            cont++;
            thead+='<th>'+name+'</th>';
       });
       thead+="</tr></thead>";
       /* Columnas que contienen la información de un list search */
       $('#table_Catalogo_'+NombreCatalogo).append(thead);
       
       TableCatalogdT = $('#table_Catalogo_'+NombreCatalogo).dataTable();    
       TableCatalogDT = new $.fn.dataTable.Api('#table_Catalogo_'+NombreCatalogo);
       
              /* El Array Struct Contiene la estructura del catálogo y a partir de ella se recorre el XML que contiene
        * su información (Del catálogo) */       
       $(xml).find("CatalogRecord").each(function()
        {             
           var $Campo=$(this);     
           var IdRecord = $Campo.find('Id'+NombreCatalogo).text();
           var data = [];  /* Guarda la fila que será insertada */
           
           for(var cont=0; cont<ArrayStruct.length; cont++)  /* Recorrer los datos que contiene cada fila del catálogo */
           {
               var field = ArrayStruct[cont];
               var value = $Campo.find(field).text();               
               data[data.length] = value;
           }   
           
            var ai = TableCatalogDT.row.add(data).draw();
            var n = TableCatalogdT.fnSettings().aoData[ ai[0] ].nTr;
            n.setAttribute('id',IdRecord);           
       });

       $('#table_Catalogo_'+NombreCatalogo+' tbody').on( 'click', 'tr', function (){
               TableCatalogDT.$('tr.selected').removeClass('selected');
               $(this).addClass('selected');                               
        } );
       
       /*************************Propiedades de la Tabla ************************/
       $('#button_'+NombreCatalogo).click(function()
       {
           $('#div_Catalogo_'+NombreCatalogo).dialog({width:800,minWidth:800,height:450,minHeight:450,modal:true,
               title:NombreCatalogo,buttons:{
                   /* Botón Aceptar al Seleccionar un elemento de un catálogo tipo ListSarch */
               "Aceptar":function(){
                   var IdOpcion=$('#table_Catalogo_'+NombreCatalogo+' tr.selected').attr('id');
                   var TotalColumnas=$('#table_Catalogo_'+NombreCatalogo+' >thead >tr >th');
                   var TextoSeleccion='';
                   $('#table_Catalogo_'+NombreCatalogo+' tr.selected').each(function()
                    {
                        for(var cont=0; cont<TotalColumnas.length; cont++)
                        {
                            TextoSeleccion+=$(this).find("td").eq(cont).html()+', ';
                        }                        
                    });
                   TextoSeleccion=TextoSeleccion.slice(0,60);
                   if(IdOpcion>0){
                       $('#Catalogo_'+NombreCatalogo+' option').remove();
                       $('#Catalogo_'+NombreCatalogo).append('<option value="'+IdOpcion+'">'+TextoSeleccion+'</option>');                       
                   }                   
                   $(this).dialog('close');
               },
               "Cancelar":function(){$(this).dialog('close');}}});
       });      
   }
  
   function _CollectNewMetadatas(xml, Catalogs)
   {
        $('#CM_Carga').append('<div class="Loading" id = "CMUpload"><img src="../img/loadinfologin.gif"></div>');
        
        var Forms = $('#CM_TableMetadatasCarga :text');
        var FieldsValidator = new ClassFieldsValidator();   
        FieldsValidator.ValidateFields(Forms);
        
        if(!FieldsValidator)
            return 0;

        var IdRepositorio = $('#CM_select_repositorios').val();
        var NombreRepositorio = $('#CM_select_repositorios option:selected').html();
        var IdEmpresa = $('#CM_select_empresas option:selected').attr('id');
        IdEmpresa = parseInt(IdEmpresa);
        var NombreEmpresa = $('#CM_select_empresas option:selected').html();
    
        if(!(IdEmpresa)>0)
            return Advertencia("El identificador de la empresa no es correcto");
        
        var ArchivoFile = $('#CM_InputFileCarga').val();
        ArchivoFile = ArchivoFile.split('\\');
        var NombreArchivo = ArchivoFile[ArchivoFile.length-1];
        
        var node = $("#contentTree").dynatree("getActiveNode");
        if(!node){ Advertencia("Seleccione un directorio"); return 0;}
        
        var IdDirectory = node.data.key, Path = node.getKeyPath(), IdParentDirectory = node.getParent();
        
        IdParentDirectory = IdParentDirectory.data.key;        
        var FlagCamposDetalle = 0;  /* Validación de los campos */
        var CatalogosXml = '';
                        
        /*--------------- Se recoge el valor de los catálogos -----------------*/
        
        for(var cont = 0; cont<Catalogs.length; cont++)
        {
            var CatalogName = Catalogs[cont];
            var id = $('#Catalogo_'+Catalogs[cont]).val();  /* Se toman los valores de cada catalogo */
            
            if(!(id>0))
            {               
                FieldsValidator.AddClassRequiredActive($('#Catalogo_'+Catalogs[cont]));
                FlagCamposDetalle=1;                                
            }
            else
                FieldsValidator.RemoveClassRequiredActive($('#Catalogo_'+Catalogs[cont]));
            
            var TextoSelectCatalogo = $('#Catalogo_'+Catalogs[cont]+' option:selected').html();
            
            CatalogosXml+='<Catalogo><name>'+CatalogName+'</name><value>'+id+'</value><type>INT</type><TextoSelect>'+TextoSelectCatalogo+'</TextoSelect></Catalogo>';                        
        }
        
        /* Valores de Cada Campos de Texto */
        
        var XMLResponse = "<MetaDatas version='1.0' encoding='UTF-8'>";
        $(xml).find("Campo").each(function()
        {               
           var $Campo=$(this);
           var name=$Campo.find("name").text();
           var type=$Campo.find("type").text();
           var long=$Campo.find("long").text();
           var required=$Campo.find("required").text();           
           var id='_'+name;
           var value=$('#'+id).val();                                            
           var XML='<MetaData>\n\
                        <name>'+name+'</name>\n\
                        <value>'+value+'</value>\n\
                        <type>'+type+'</type>\n\
                        <long>'+long+'</long>\n\
                    </MetaData>';
            XMLResponse=XMLResponse+XML;                        
       });
       
       /* Campos por default como el IdDirectory y IdEmpresa se envian en el XML */
       XMLResponse+='<MetaData><name>IdDirectory</name><value>'+IdDirectory+'</value><type>INT</type></MetaData>';
       XMLResponse+='<MetaData><name>UsuarioPublicador</name><value>'+EnvironmentData.NombreUsuario+'</value><type>VARCHAR</type></MetaData>';
       
       XMLResponse+=CatalogosXml;
       XMLResponse+='</MetaDatas>';                     /* Fin del XML */
                                                
       var xml_usuario=document.getElementById("CM_InputFileCarga");
       var archivo = xml_usuario.files;        
       var data = new FormData();
       
       if(archivo.length===0)
       {
           FieldsValidator.AddClassRequiredActive($('#CM_InputFileCarga'));
           return 0;
       }
       else
           FieldsValidator.RemoveClassRequiredActive($('#CM_InputFileCarga'));
       
       if(FlagCamposDetalle)
           return 0;            

      for(i=0; i<archivo.length; i++)
      {
            data.append('archivo',archivo[i]);
            data.append('opcion','UploadMetadatas');
            data.append('IdUsr',EnvironmentData.IdUsuario);
            data.append('UploadMetadatas',UploadMetadatas);
            data.append('DataBaseName',EnvironmentData.DataBaseName);
            data.append('IdEmpresa',IdEmpresa);
            data.append('NombreEmpresa', NombreEmpresa);
            data.append('nombre_usuario',EnvironmentData.NombreUsuario);
            data.append('IdParentDirectory',IdParentDirectory);
            data.append('IdDirectory',IdDirectory);
            data.append('XmlReponse',XMLResponse);
            data.append('IdRepositorio',IdRepositorio);
            data.append('Path',Path);
            data.append('NombreRepositorio',NombreRepositorio);
            data.append('NombreArchivo',NombreArchivo);
      }
      
      return data;
   }
  
/*------------------------------------------------------------------------------
 * 
 * @param {type} xml
 * @param {type} Catalogos
 * @returns {undefined}
 * 
 * Descripción: Función que construye el XML que transporta los datos al servidor para
 * carga el nuevo documento.
 ------------------------------------------------------------------------------*/

   function UploadMetadatas(IdRepositorio,xml, Catalogos)
   {
       var data = _CollectNewMetadatas(xml, Catalogos);

       if(data ===0 || data===undefined || data ==='0')  
       {
           $('#CMUpload').remove();
           return 0;
       }

      $.ajax({
      async:false, 
      cache:false,
      processData: false,
      contentType: false,
      dataType:"html", 
      type: 'POST',   
      url: "php/ContentManagement.php",
      data: data, 
      success:  function(xml)
      {
        $('#CMUpload').remove();
        if($.parseXML( xml )===null){errorMessage(xml);return 0;}else{ xml=$.parseXML( xml );}
        
        if($(xml).find("SetMetadatas").length>0)
            AddNewRow(IdRepositorio, xml);

              
        $(xml).find("Error").each(function()
        {
            var mensaje = $(this).find("Mensaje").text();
            errorMessage(mensaje);
        });       
        
      },
      beforeSend:function(){},
      error: function(jqXHR, textStatus, errorThrown){$('#CMUpload').remove();errorMessage(textStatus +"<br>"+ errorThrown);}
    });
   }

function AddNewRow(IdRepository, xml)
{
    $(xml).find("SetMetadatas").each(function()
    {                                                        
        var $SetMetadatas = $(this);
        var mensaje = $SetMetadatas.find("Mensaje").text();
        var IdFile = $SetMetadatas.find('IdRepositorio').text();
        var NombreArchivo = $SetMetadatas.find("NombreArchivo").text();
        var FechaIngreso = $SetMetadatas.find("FechaIngreso").text();
        var TipoArchivo = $SetMetadatas.find("TipoArchivo").text();
        var Detalle = $SetMetadatas.find("Full").text();
        var IdRepositorio = $SetMetadatas.find("IdRepositorio").text();
        var Ruta = $SetMetadatas.find("RutaArchivo").text();

        Notificacion(mensaje);

        var data =
        [
            NombreArchivo,
            FechaIngreso,
            TipoArchivo,
            Detalle,
            '<img src="img/acuse.png" title="vista previa de "'+NombreArchivo+'" onclick="Preview(\''+TipoArchivo+'\', \'0\' ,\''+IdRepositorio+'\', \'Content\')">',
            '<img src="img/metadata.png" title="vista previa de '+NombreArchivo+'" onclick="GetDetalle(\'Content\', \'0\', \''+IdRepositorio+'\')">',
            Ruta,
            '<center><input type="checkbox" id="'+IdRepositorio+'"  class="checkbox_detail"></center>'
        ];

        /* Se inserta la Fila y su Id */
        $('#table_DetailResult tr').removeClass('selected');
        var ai = TableContentDT.row.add(data);         
        var n = TableContentdT.fnSettings().aoData[ ai[0] ].nTr;
        n.setAttribute('id',IdRepositorio);
        n.setAttribute('class','selected');
        TableContentDT.draw();

        $('#CM_Carga').dialog('destroy');
    });
    
    var downloads = new Downloads();
    
    /* Se recoge el estado del CheckBox para agregarlo a la lista de descarga */
    $('#table_DetailResult tbody tr input').click(function()
    {
        var check= $(this).is(':checked');
        var IdCheck = $(this).attr('id');
        
        if(check)
            downloads.AddRow('Content',0,IdCheck);
        else
            downloads.RemoveRow(IdRepository, IdCheck);        
    });  
}
  
/*------------------------------------------------------------------------------
 * 
 * @param {type} IdRepositorio
 * @param {type} SelectCatalogos
 * @returns {undefined}
 * Recupera el contenido de cada catálogo seleccionando el repositorio
 ------------------------------------------------------------------------------*/  
function getCatalogOptions(IdRepositorio,SelectCatalogos)
{   

   $.ajax({
      async:false, 
      cache:false,
      dataType:"html", 
      type: 'POST',   
      url: "php/ContentManagement.php",
      data: "opcion=getCatalogos&DataBaseName="+EnvironmentData.DataBaseName+'&IdUsuario='+EnvironmentData.IdUsuario+"&IdRepositorio="+IdRepositorio, 
      success:  function(respuesta){
           var xml=respuesta; 
           $("#"+SelectCatalogos+" option").remove();
           $("#"+SelectCatalogos).append("<option value='0'>Seleccione un Catálogo</option>");
           $(xml).find("Empresa").each(function()
            {
               var $Empresa=$(this);
               var IdCatalogo=$Empresa.find("IdCatalogo").text();               
               var NombreCatalogo=$Empresa.find("NombreCatalogo").text();
               $("#"+SelectCatalogos).append("<option value=\""+NombreCatalogo+"\">"+NombreCatalogo+"</option>");
            });
            $(xml).find("Error").each(function()
            {
                var $Instancias=$(this);
                var estado=$Instancias.find("Estado").text();
                var mensaje=$Instancias.find("Mensaje").text();
                errorMessage(mensaje);
            });
      },
      beforeSend:function(){},
      error:function(objXMLHttpRequest){errorMessage(objXMLHttpRequest);}
    });
}    
