/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/* global CatalogAdmin, BotonesWindow, EnvironmentData, Enterprise */

var WindowCatalogo={minHeight:500,minWidth:800,width:$(window).width()-100, height:600, closeOnEscape:false};
var CatalogTabledT = undefined;
var CatalogTableDT = undefined;

$(document).ready(function()
{
        /********* Efectos sobre tabla dentro de acordeón ***********/
    $('#TableAccordionCatalogs').on( 'click', 'tr', function ()
    {
        var active = $('#TableAccordionCatalogs tr.TableInsideAccordionFocus');                
        $('#TableAccordionCatalogs tr').removeClass('TableInsideAccordionFocus');
        $('#TableAccordionCatalogs tr').removeClass('TableInsideAccordionActive');
        $(active).addClass('TableInsideAccordionFocus');
        $(this).removeClass('TableInsideAccordionHoverWithoutClass');
        $(this).addClass('TableInsideAccordionActive');     
    });
    $('#TableAccordionCatalogs tr').hover(function()
    {
        if($(this).hasClass('TableInsideAccordionActive') || $(this).hasClass('TableInsideAccordionFocus'))
            $(this).addClass('TableInsideAccordionHoverWithClass');
        else
            $(this).addClass('TableInsideAccordionHoverWithoutClass');
    });
    $('#TableAccordionCatalogs tr').mouseout(function()
    {
        if($(this).hasClass('TableInsideAccordionActive') || $(this).hasClass('TableInsideAccordionFocus'))
            $(this).removeClass('TableInsideAccordionHoverWithClass');
        else
            $(this).removeClass('TableInsideAccordionHoverWithoutClass');
    });
    /* Fin de Efectos  */
    
    $("#tree_catalogos").dynatree();
   $('.LinkCatalogs').click(function()
   {
       $('#div_consola_catalogos').dialog(WindowCatalogo,{ title:"Consola de Catálogos"}).dialogExtend(BotonesWindow);
       $("#AccordionCatalogs").accordion({ header: "h3", collapsible: true, heightStyle: "content" });
       $('#tr_NewCatalogo').click();
   });
   $('#tr_NewCatalogo').click(function(){CatalogAdmin.CM_AddCatalogo();});
   $('#tr_Catalogos').click(function(){CatalogAdmin.GetListCatalogos()();});
   $('#tr_ViewCatalog').click(function(){CatalogAdmin.ViewCatalog();});
});

ClassCatalogAdministrator = function()
{
    var self = this;
    this.CatalogType = undefined;
    this.CatalogName = undefined;    
    this.IdCatalog = 0;
    this.IdRepository = undefined;
    this.RepositoryName = undefined;   
    
   /*******************************************************************************
    * Una vez seleccionada la empresa y el repositorio, se carga el XML que el usuario elegío.
    * @returns {undefined}
    */
   _CM_AddCatalogoXML = function()
   {
       Loading();
       var ClaveEmpresa=$('#SelectEmpresasAddCatalogo').val();
       var IdRepositorio=$('#SelectRepositoriosAddCatalogo').val()
       NombreRepositorio=$('#SelectRepositoriosAddCatalogo option:selected').html(),
       xml_usuario=document.getElementById("InputFile_AddCatalogo"), archivo = xml_usuario.files;   
   
       var data = new FormData();

         for(i=0; i<archivo.length; i++)
         {
               data.append('archivo',archivo[i]);
               data.append('opcion','AddCatalogoXML');
               data.append('id_usuario',EnvironmentData.IdUsuario);
               data.append('DataBaseName',EnvironmentData.DataBaseName);
               data.append('ClaveEmpresa',ClaveEmpresa);
               data.append('NombreRepositorio',NombreRepositorio);
               data.append('IdRepository', IdRepositorio);
               data.append('nombre_usuario',EnvironmentData.NombreUsuario);
         } 

       $('#InputFile_AddCatalogo').remove();
       $('#WS_Catalogo').append('<input type ="file" accept="text/xml" id="InputFile_AddCatalogo">');
       $('#InputFile_AddCatalogo').change(function(){_CM_AddCatalogoXML();});  

        $.ajax({
        async:true, 
        cache:false,
        dataType:'html', 
        type: 'POST',   
        url: "php/Catalog.php",
        processData: false,
        contentType: false,
        data: data, 
        success:  function(xml){   
            $('#Loading').dialog('close');
            Salida(xml);                  
            
            $(xml).find("Error").each(function()
            {
                var $Instancias=$(this);
                var estado=$Instancias.find("Estado").text();
                var mensaje=$Instancias.find("Mensaje").text();
                Error(mensaje);
            });     
        },
        beforeSend:function(){},
        error: function(jqXHR, textStatus, errorThrown){Error(textStatus +"<br>"+ errorThrown);}
        });
   };

   _BuildCatalogTable = function(CatalogRecords, IdRepository)
   {          
       var CatalogName = self.getCatalogName();
       var CatalogStructure = GeStructure('Catalogo_'+CatalogName);      
       var Columns= [{"className":"int", "targets": [0]}];
       if(CatalogStructure === undefined)
           return;
       
       var CatalogFields = new Array(), cont = 0, thead = '<thead><tr><th>ID</th>';
       $('#WS_Catalogo').append('<table class = "display hover" id = "CatalogsAdminTable"></table>');

       $(CatalogStructure).find('Campo').each(function()
       {    
            var List = $(this).find("tipo").text();
            var FieldType = $(this).find('type').text();
            var name= $(this).find("name").text();
            
            if(List.length>0)/* Tipo del List */    
                return;   
            
            CatalogFields[cont] = name;
            Columns[Columns.length] = {"className":FieldType, "targets": [cont+1]};
            thead+='<th>'+name+'</th>';            
            cont++;            
       });
       
       thead+='</tr></thead>';
       
       $('#CatalogsAdminTable').append(thead);              
       
        CatalogTabledT = $('#CatalogsAdminTable').dataTable({
            "columnDefs":Columns,
            "dom": 'f<"ButtonCatalogAdminTable">lTrtip',
            "oTableTools": {
                "aButtons": [
                    {"sExtends":"text", "sButtonText": "Nuevo Registro", "fnClick" :function(){_FormsCatalogToAddRecord();}},
                    {"sExtends":"text", "sButtonText": "Nueva Columna", "fnClick" :function(){_AddNewColumnToCatalog();}},
                    {"sExtends": "copy","sButtonText": "Copiar al portapapeles"},
                    {
                        "sExtends":    "collection",
                        "sButtonText": "Guardar como...",
                        "aButtons":    [ "csv", "xls", "pdf" ]
                    }                    
                ]
            },
            "autoWidth" : false, 
            "sSwfPath": "../apis/DataTables/extensions/TableTools/swf/copy_csv_xls_pdf.swf",
            "fnCreatedRow": function( nRow, aData, iDataIndex ) {
                _Jedit(nRow, IdRepository);    /* Función que se invoca para editar una celda de la tabla catálogo */
            }
        });    
        
        CatalogTableDT = new $.fn.dataTable.Api( '#CatalogsAdminTable' );       
        
//        $('div.ButtonCatalogAdminTable').append('<input type = "button" value = "Nuevo Registro" id = "ButtonCatalogAdminTableAdd" onclick = "_FormsCatalogToAddRecord();">');
//        $('#ButtonCatalogAdminTableAdd').button();
        
        $(CatalogRecords).find("CatalogRecord").each(function()
        {             
           var $Campo=$(this);     
           var IdRecord = $Campo.find('Id'+CatalogName).text();
           var data = [
                        /*[0]*/ IdRecord 
                      ];  
           
           for(var cont=0; cont<CatalogFields.length; cont++)  /* Recorrer los datos que contiene cada fila del catálogo */
           {
               var field = CatalogFields[cont];
               var value = $Campo.find(field).text();               
               data[data.length] = value;
           }   
           
            var ai = CatalogTableDT.row.add(data).draw();
            var n = CatalogTabledT.fnSettings().aoData[ ai[0] ].nTr;
            n.setAttribute('id',IdRecord);           
       });
       

       $('#CatalogsAdminTable tbody').on( 'click', 'tr', function (){
               CatalogTableDT.$('tr.selected').removeClass('selected');
               $(this).addClass('selected');                     
        } );
        
   };  
   
   /*----------------------------------------------------------------------------
    * description: Permite la edición de las celdas de cada fila en un catálogo
    * (pulsando doble click sobre ellas y con enter para modificar)
    * 
    * @param {type} nRow
    * @param {type} IdRepository
    * @returns {undefined}
    * 
    ----------------------------------------------------------------------------*/
   _Jedit = function(nRow, IdRepository)
   {
       var row = undefined;
        $(nRow).children().each(function()
        {
            var cell = $(this);
            if($(this).index()===0)
                return;
                        
            $(this).editable( '../php/Catalog.php',
            {
                indicator : '<img src="../img/loadinfologin.gif">', 
                tooltip: 'Click para editar...', 
                name: "NewValue", 
                id: "IdCatalog", 
                "height" : "22px",
                method: "POST",
                event:"dblclick",
                onsubmit:function( settings, Row){row = Row;},  
                submitdata: 
                {
                    opcion:"ModifyCatalogRecord",
                    IdUser: EnvironmentData.IdUsuario, 
                    UserName:EnvironmentData.NombreUsuario, 
                    DataBaseName:EnvironmentData.DataBaseName,                     
                    IdRepositorio:IdRepository, 
                    CatalogName:function(){return self.getCatalogName();},
                    IdCatalog:function(){return $(row).parent().attr('id');}, 
                    "FieldName":function()
                    {   /* FieldName es el campo del catalogo a modificar */
                        var idx = CatalogTableDT.cell( row ).index().column;
                        var title = CatalogTableDT.column( idx ).header();
                        var FieldName = $(title).html();
                        return FieldName;
                    },
                    "FieldType":function(){ return $(cell).attr('class');   }
                },                                                                
                data: function(value, settings)
                {
                    /* Convert <br> to newline. */
                    var retval = value.replace(/<br[\s\/]?>/gi, '\n');
                    return retval;
                },
                "callback": function( xml, settings )
                {                      
                    /* Redraw the table from the new data on the server */                     
//                    if($.parseXML( xml )===null){ Error(xml); return 0;}else xml=$.parseXML( xml );
                }
            } );
        });
   };
      
    /*--------------------------------------------------------------------------
     * Agrega los formularios necesarios para ingresar un registro al catálogo
     * 
     * @returns {undefined}
     ---------------------------------------------------------------------------*/
   _FormsCatalogToAddRecord = function()
   {
       $('#FormsCatalogToAddRegister').remove();
       $('body').append('<div id = "FormsCatalogToAddRegister" class = "detalle_archivo"><div class = "titulo_ventana">Nuevo Registro a'+self.getCatalogName()+'</div></div>');
        $('#FormsCatalogToAddRegister').append('<table id = "CatalogTableToAddNewRecord"></table>');
        var CatalogName = self.getCatalogName();
        
        SetTableStructura("Catalogo_"+CatalogName,'CatalogTableToAddNewRecord',0);     
        
        var Forms = $('#CatalogTableToAddNewRecord tr td input');
        
        var ValidateForms = new ClassFieldsValidator();
        ValidateForms.InspectCharacters(Forms);
        
        $('#FormsCatalogToAddRegister').dialog({title:"Nuevo Registro", width:500, minWidth:500, Height:500, minHeight:500, closeOnEscape:true, modal:true, 
            buttons:{
                "Agregar":{click:function(){_AddNewRecord();},text:"Agregar"},
                "Cancelar":{click:function(){$(this).dialog('destroy');}, text:"Cancelar"}
            }});
   };
    
    
   /*---------------------------------------------------------------------------
    * Se almacenan los valores introducidos por el usuario.
    * @returns {undefined}
    ---------------------------------------------------------------------------*/
    _AddNewRecord = function()
    {
        var Forms = $('#CatalogTableToAddNewRecord tr td input');
//        console.log(Forms);
        var ValidateForms = new ClassFieldsValidator();
        var ResultValidate = ValidateForms.ValidateFields(Forms);
        console.log("Resultado de la validacion = "+ResultValidate);
        if(!ResultValidate)
            return 0 ;
        else
            console.log('Validacion correcta');
        
        $('#FormsCatalogToAddRegister').append('<div class="PlaceWaiting" id = "CatalogsPlaceWaiting"><img src="../img/loadinfologin.gif"></div>');
        var IdRepositorio=$('#SelectRepositoriosAddCatalogo').val();
        var IdEmpresa=$('#SelectEmpresasAddCatalogo').val();
        var CatalogName = self.getCatalogName();
        var XMLResponse = _CreateNewXmlRecord(CatalogName);     
                
         $.ajax({
         async:false, 
         cache:false,
         dataType:"html", 
         type: 'POST',   
         url: "php/Catalog.php",
         data: "opcion=AddNewRecord&XmlReponse="+XMLResponse+"&DataBaseName="+EnvironmentData.DataBaseName+"&IdRepositorio="+IdRepositorio+"&IdEmpresa"+IdEmpresa+"&CatalogName="+CatalogName+'&IdUser='+EnvironmentData.IdUsuario+'&UserName='+EnvironmentData.NombreUsuario, 
         success:  function(xml){
             $('#CatalogsPlaceWaiting').remove();
            if($.parseXML( xml )===null){Error(xml);return 0;}else xml = $.parseXML( xml );
            $(xml).find('AddNewRecord').each(function(){   
                var Mensaje = $(this).find('Mensaje').text();
                var IdCatalog = $(this).find('IdCatalog').text();
                var data = [IdCatalog];
                
                $(this).find('Field').each(function(){
                    var Field = this;
                    var FieldName = $(Field).find('FieldName').text();
                    var FieldValue = $(Field).find('FieldValue').text();
                    var FieldType = $(Field).find('FieldType').text();
                    data[data.length] = FieldValue;
                });
                                
                var ai = CatalogTableDT.row.add(data).draw();
                var n = CatalogTabledT.fnSettings().aoData[ ai[0] ].nTr;
                n.setAttribute('id',IdCatalog);
                Notificacion(Mensaje);
                
                $('#FormsCatalogToAddRegister').dialog('destroy');
                
            });
            $(xml).find("Error").each(function()
            {
                var $Instancias=$(this);
                var estado=$Instancias.find("Estado").text();
                var mensaje=$Instancias.find("Mensaje").text();
                Error(mensaje);
                xml = 0;
            });
         },
         beforeSend:function(){},
         error: function(jqXHR, textStatus, errorThrown){$('#CatalogsPlaceWaiting').remove();Error(textStatus +"<br>"+ errorThrown);}
       });
    };
    
    /* -------------------------------------------------------------------------
     * Genera un XML con los datos del nuevo registro a insertar en el catálogo
     * 
     * @param {type} CatalogName
     * @returns {XML|String}
     ---------------------------------------------------------------------------*/
    _CreateNewXmlRecord = function(CatalogName)
    {
        var xmlStruct = GeStructure("Catalogo_"+CatalogName);
        var XMLResponse = "<MetaDatas version='1.0' encoding='UTF-8'>";
        $(xmlStruct).find("Campo").each(function()
        {               
            var $Campo=$(this);
            var tipo=$Campo.find("tipo").text();
            if(tipo.length>0){return;}
            var name=$Campo.find("name").text();
            var type=$Campo.find("type").text();
            var long=$Campo.find("long").text();
            var required=$Campo.find("required").text();
            var id='_'+name;
            var value=$('#'+id).val();/* Id que contendrá cada elemento recuperado */

            var XML='<MetaData>\n\
                        <name>'+name+'</name>\n\
                        <value>'+value+'</value>\n\
                        <type>'+type+'</type>\n\
                        <long>'+long+'</long>\n\
                     </MetaData>';
            XMLResponse=XMLResponse+XML;
        });

          /* Campos por default como el IdDirectory y IdEmpresa se envian en el XML */
          XMLResponse+='</MetaDatas>';  
          
        return XMLResponse;
    };
    
    _AddNewColumnToCatalog = function()
    {
        $('#DivNewColumnToCatalog').remove();
        $('body').append('<div id = "DivNewColumnToCatalog"></div>');
        $('#DivNewColumnToCatalog').append('<div class = "titulo_ventana"></div>');
        _AddFormsNewColumn();
        $('#DivNewColumnToCatalog').dialog({title:"Agregar nueva columna al catálogo "+self.getCatalogName(), width:500, height:400, minWidth:400, minHeight:400, modal:true, 
        buttons:{"Agregar":{click:function(){_AddNewColumn();}, text:"Agregar"},
                 "Cancelar":{click:function(){$(this).dialog('destroy');}, text:"Cancelar"}}});    
    };
    
    _AddFormsNewColumn = function()
    {
        $('#DivNewColumnToCatalog').append('\
            <table>\n\
                <tr><td>Nombre del Campo: </td><td><input type = "text" id = "CatalogNewFieldName" class = "FormStandart required" title = "Debe ser una alfanumérica de no más de 64 caracteres, es posible usar \'_\' "></td></tr>\n\
                <tr><td>Tipo: </td><td>\n\
                    <select id = "CatalogNewFieldType" class = "FormStandart">\n\
                        <option value = "TEXT">Texto</option>\n\
                        <option value = "varchar">Varchar</option>\n\
                        <option value = "Int">Entero</option>\n\
                        <option value = "double">Double</option>\n\
                        <option value = "date">Fecha</option>\n\
                    </select>\n\
                </td></tr>\n\
                <tr><td>Longitud</td><td><input type = "text" id = "CatalogFieldLength" class = "FormStandart required"></td></tr>\n\
                <tr><td>Requerido</td><td>\n\
                    <select id = "CatalogRequiredField" class = "FormStandart">\n\
                        <option value = "true">Si</option>\\n\
                        <option value = "false">No</option>n\
                    </select>\n\
                </td></tr>\n\
            </table>');     
        
        var FieldType = $('#CatalogNewFieldType').val();
        if(FieldType === 'varchar')
            $('#CatalogFieldLength').prop("disabled", false); 
        else                
        {
            $('#CatalogFieldLength').prop("disabled", true); 
            $('#CatalogFieldLength').val('0');
        }

        $('#CatalogNewFieldType').change(function()
        {
            var FieldType = $('#CatalogNewFieldType').val();
            
            if(FieldType === 'varchar')
                $('#CatalogFieldLength').prop("disabled", false); 
            else                
            {
                $('#CatalogFieldLength').prop("disabled", true); 
                $('#CatalogFieldLength').val('0');
            }
                        
        });
        
        var RegularExpressionFieldName = /[^a-zA-Z0-9\_]/g;
        
        $('#CatalogNewFieldName').keyup(function()
        {             
            $(this).attr('title', 'Debe ser una alfanumérica de no más de 64 caracteres, es posible usar \'_\'');
            _ReplaceInvalidCharacters(RegularExpressionFieldName,this);
            if(_CheckIfExistColumn(this.value))
            {
                if(!$('#CatalogNewFieldName').hasClass('RequiredActivo'))
                    $('#CatalogNewFieldName').addClass('RequiredActivo');  
                $('#CatalogNewFieldName').attr('title','Ya existe el campo');
            }
            else                
                if($('#CatalogNewFieldName').hasClass('RequiredActivo'))
                {
                    $('#CatalogNewFieldName').removeClass('RequiredActivo'); 
                    $('#CatalogNewFieldName').attr('title', 'Campo válido'); 
                }       
        });
        
        var RegularExpressionFieldLength = /[^0-9]/g;
        $('#CatalogFieldLength').keyup(function()
        {
            _ReplaceInvalidCharacters(RegularExpressionFieldLength, this);
        });
        
        $('#CatalogNewFieldName').tooltip();
        $('#CatalogFieldLength').tooltip();
        
    };
    
    _ReplaceInvalidCharacters = function(RegularExpresion, Form)
    {
        Form.value = Form.value.replace(RegularExpresion,'');
    };
    
    _ValidatingFieldsNewColumn = function()
    {
//        RequiredActivo
        var RegularExpressionFieldName = /[^a-zA-Z0-9\_]/g;
        var RegularExpressionFieldLength = /[^0-9]/g;
        
        var FormFieldName = $('#CatalogNewFieldName');
        var FormFieldLenght = $('#CatalogFieldLength');
        
//        console.log(FormFieldName);
//        console.log(FormFieldLenght);
        
        _ReplaceInvalidCharacters(RegularExpressionFieldName, FormFieldName[0]);
        _ReplaceInvalidCharacters(RegularExpressionFieldLength, FormFieldLenght[0]);
        
        var FlagMistake = 1;
        
                /*          Campo Nombre de columna          */
        
        if(!$('#CatalogNewFieldName').val().length>0)
        {
            FlagMistake = 0;
            if(!$('#CatalogNewFieldName').hasClass('RequiredActivo'))
                $('#CatalogNewFieldName').addClass('RequiredActivo');   
            
            $('#CatalogNewFieldName').attr('title','No puede ser un campo vacio');
        }
        else
        {
            if(_CheckIfExistColumn($('#CatalogNewFieldName').val()))
            {
                if(!$('#CatalogNewFieldName').hasClass('RequiredActivo'))
                    $('#CatalogNewFieldName').addClass('RequiredActivo');                 
            }
            else                
                if($('#CatalogNewFieldName').hasClass('RequiredActivo'))
                {
                    $('#CatalogNewFieldName').removeClass('RequiredActivo'); 
                    $('#CatalogNewFieldName').attr('title', 'Campo válido'); 
                }                                                       
        }
                /*          Campo Longitud de columna    */       
                
        if($('#CatalogFieldLength').attr('disabled')==='disabled')
        {
            $('#CatalogFieldLength').val('0');
            if($('#CatalogFieldLength').hasClass('RequiredActivo'))
                $('#CatalogFieldLength').removeClass('RequiredActivo');
        }
        else
        {
            if($.isNumeric($('#CatalogFieldLength').val()))
            {
                
                if(_CheckFieldLenght($('#CatalogNewFieldType').val()))
                {
                    if($('#CatalogFieldLength').hasClass('RequiredActivo'))
                        $('#CatalogFieldLength').removeClass('RequiredActivo');                
                }
                else
                {
                    if(!$('#CatalogFieldLength').hasClass('RequiredActivo'))
                        $('#CatalogFieldLength').addClass('RequiredActivo');   
                    FlagMistake = 0;
                }
            }
            else
            {
                FlagMistake = 0;
                if(!$('#CatalogFieldLength').hasClass('RequiredActivo'))
                {
                    $('#CatalogFieldLength').addClass('RequiredActivo');
                    $('#CatalogFieldLength').attr('title', 'Debe ser un campo numérico');
                }
                        
            }
                 
        }                        
        
        return FlagMistake;
    };
    
    _CheckFieldLenght = function(FieldType)
    {
        var Lenght = $('#CatalogFieldLength').val();
        var flag = true;
        switch(FieldType)
        {
            case 'varchar':
                $('#CatalogFieldLength').attr('title','Para varchar el rango deber ser entre 1 y 255');
                flag = (Lenght>0 && Lenght<=255)?true:false;
                break;                
        }      
        return flag;
    };
    
    _CheckIfExistColumn = function(NewColumnName)
    {     
        var ExistanceFlag = false;
        NewColumnName = NewColumnName.toLowerCase();
        var columns = CatalogTableDT.columns().header();
        $.each(columns, function(index,properties){
            
            var ColumnName = properties.innerHTML;
            ColumnName = ColumnName.toLowerCase();
            
//            console.log('if '+ColumnName+' '+' ('+ColumnName.length+') '+' === '+NewColumnName+' ('+NewColumnName.length+') ');
            if(ExistanceFlag)
                return;
       
            
            if(ColumnName===NewColumnName)
            {
//                console.log('La columna '+ NewColumnName +' existe ');
                ExistanceFlag = true;                
                return;
            }
            else
            {
//                console.log('No existe la columna '+NewColumnName);
            }
        });
        
        return ExistanceFlag;
    };
    
    _AddNewColumn = function()
    {
        var FieldName = $('#CatalogNewFieldName').val();
        var FieldType = $('#CatalogNewFieldType').val();
        var FieldLength = $('#CatalogFieldLength').val();
        var RequiredField = $('#CatalogRequiredField').val();
        var RepositoryName = $('#SelectRepositoriosAddCatalogo option:selected').html();
        var IdRepository = $('#SelectRepositoriosAddCatalogo option:selected').val();
        var CatalogName = self.getCatalogName();
        
        var validating = _ValidatingFieldsNewColumn();        
        if(!validating)
            return 0;
        
        $.ajax({
         async:false, 
         cache:false,
         dataType:"html", 
         type: 'POST',   
         url: "php/Catalog.php",
         data: "opcion=AddNewColumn&DataBaseName="+EnvironmentData.DataBaseName+'&IdUser='+EnvironmentData.IdUsuario+'&UserName='+EnvironmentData.NombreUsuario+'&CatalogName='+CatalogName+'&FieldName='+FieldName+'&FieldType='+FieldType+'&FieldLength='+FieldLength+'&RequiredField='+RequiredField+'&RepositoryName='+RepositoryName, 
         success:  function(xml){
            if($.parseXML( xml )===null){Error(xml);return 0;}else xml = $.parseXML( xml );
            
            $(xml).find('AddNewColumn').each(function()
            {
                var Mensaje = $(this).find('Mensaje').text();
                Notificacion(Mensaje);
                CatalogTableDT.destroy();                
                $('#CatalogsAdminTable').remove();
                var CatalogXml = _GetCatalogRecordsInXml(CatalogName, '');
                                 
                 if(CatalogXml !== undefined)
                    _BuildCatalogTable(CatalogXml, IdRepository);
                
                $('#DivNewColumnToCatalog').dialog('destroy');
            });
            
            $(xml).find("Error").each(function()
            {
                var $Instancias=$(this);
                var estado=$Instancias.find("Estado").text();
                var mensaje=$Instancias.find("Mensaje").text();
                Error(mensaje);
                xml = 0;
            });
         },
         beforeSend:function(){},
         error:function(objXMLHttpRequest){Error(objXMLHttpRequest);}
       });
    };
                   
    /*--------------------------------------------------------------------------
     * 
     * @param {type} IdRepositorio
     * @param {type} IdCatalogOption
     * @returns {undefined}
     */
    
    _getCatalogos = function(IdRepositorio,IdCatalogOption) 
    {
        var GetCatalogs = self.getCatalogos(IdRepositorio,IdCatalogOption);
        return GetCatalogs;
    };
    
    _GetCatalogRecordsInXml = function(CatalogName, CatalogType)
    {
        var CatalogXml = self.GetCatalogRecordsInXml(CatalogName, CatalogType);
        return CatalogXml;
    };
    
    _DeleteCatalogTable = function()
    {
        if($('#CatalogsAdminTable').length>0)
        {
            CatalogTableDT.destroy();
            $('#CatalogsAdminTable').remove();
        }                    
    };
    
};

/*-----------------------------------------------------------------------------*
 *                                                                             *
 *            Definición de Métodos Públicos mediante Prototipos               *
 *                                                                             *
 *-----------------------------------------------------------------------------*/

    /*******************************************************************************
    *  Define una nueva estructura para un Catalogo a través de un XML
    * @returns {undefined}
    */
   ClassCatalogAdministrator.prototype.CM_AddCatalogo = function()
   {
       $('#WS_Catalogo').empty();
       $('#WS_Catalogo').append('<div class="titulo_ventana">Agregar un Nuevo Catálogo</div>');
       $('#WS_Catalogo').append('<p>Seleccione un XML con la estructura del Catálogo en base al XSD definido por el sistema.</p>');
       $('#WS_Catalogo').append('<br><table id="TableCatalogosAdd"></table>');
       $('#TableCatalogosAdd').append('<tr><td>Empresa:</td> <td><select class = "FormStandart" id="SelectEmpresasAddCatalogo"><option>Seleccione una empresa...</option></select></td></tr>');
       $('#TableCatalogosAdd').append('<tr><td>Repositorio:</td><td> <select class = "FormStandart" id="SelectRepositoriosAddCatalogo"><option>Esperando empresa...</option></select></td></tr>');
       
       var enterprises = Enterprise.GetEnterprises();

       $(enterprises).find('Enterprise').each(function()
        {
            var IdEnterprise = $(this).find('IdEmpresa').text();
            var EnterpriseKey = $(this).find('ClaveEmpresa').text();
            var EnterpriseName = $(this).find('NombreEmpresa').text();
           $("#SelectEmpresasAddCatalogo").append("<option value=\""+EnterpriseKey+"\">"+EnterpriseName+"</option>");
        });
       
       $('#SelectEmpresasAddCatalogo').change(function()
       {
           var EnterpriseKey = $('#SelectEmpresasAddCatalogo').val();
           if(EnterpriseKey!=="0")
           {
              var repositories = Repository.GetRepositories(EnterpriseKey);
              
              $("#SelectRepositoriosAddCatalogo option").remove();
               $("#SelectRepositoriosAddCatalogo").append("<option value='0'>Seleccione un Repositorio</option>");
              $(repositories).find('Repository').each(function()
                {
                    var IdRepository = $(this).find('IdRepositorio').text();
                    var RepositoryName = $(this).find('NombreRepositorio').text();
                    $('#SelectRepositoriosAddCatalogo').append('<option value = "'+IdRepository+'">'+RepositoryName+'</option>');
                });
              
              $('#SelectRepositoriosAddCatalogo').change(function(){
                  if($('#SelectRepositoriosAddCatalogo').val()!=="0")
                  {
                      $('#InputFile_AddCatalogo').remove();
                      $('#WS_Catalogo').append('<input type="file" id="InputFile_AddCatalogo" accept="text/xml">');
                      $('#InputFile_AddCatalogo').change(function(){_CM_AddCatalogoXML();});
                  }
                  else
                      $('#InputFile_AddCatalogo').remove();
              });
           }
           else
           {
               $('#SelectRepositoriosAddCatalogo option').remove();
               $('#SelectRepositoriosAddCatalogo').append('<option value="0">Esperando Empresa...</option>');
               $('#InputFile_AddCatalogo').remove();
           }
       });     
   };


   /*******************************************************************************
    * Devuelve el listado de Catalogos ordenados por empresa y repositorio (árbol)
    * @returns {undefined}
    */
   ClassCatalogAdministrator.prototype.GetListCatalogos = function()
   {
       Loading();
       $('#WS_Catalogo').empty();
       var DataBaseName=$('#database_name').val();
       var IdUsuario=$('#id_usr').val();
       $('#tree_catalogos').remove();
       $('#consola_catalogos_tree').append('<div id="tree_catalogos"></div>');   
       $('#tree_catalogos').append('<ul><li id="Tree_Repository" class="folder expanded" data="icon: \'database.png\'">'+DataBaseName+'<ul id="Tree_Repository_"></ul></ul>');
       ajax=objetoAjax();
       ajax.open("POST", 'php/ContentManagement.php',true);
       ajax.setRequestHeader("Content-Type", "application/x-www-form-urlencoded;charset=utf-8;");
       ajax.send("opcion=GetListCatalogos&DataBaseName="+DataBaseName+'&IdUsuario='+IdUsuario);
       ajax.onreadystatechange=function() 
       {
           $('#Loading').dialog('close');
          if (ajax.readyState===4 && ajax.status===200) 
          {
             if(ajax.responseXML===null){Error(ajax.responseText);return;     }  
              var xml = ajax.responseXML;
              var cont=0;
              var ArrayDirectories=new Array();
              var ArrayEmpresas=new Array();
              var ArrayRepositorios=new Array();
              var ArrayCatalogos=new Array();
              $(xml).find("Empresas").each(function()
               {                
                  var $Empresas=$(this);
                   var ClaveEmpresa=$Empresas.find("ClaveEmpresa").text();
                   var NombreEmpresa = $Empresas.find("NombreEmpresa").text();
                   var IdEmpresa = $Empresas.find("IdEmpresa").text();
                   var IdRepositorio=$Empresas.find("IdRepositorio").text();
                   var IdCatalogo=$Empresas.find("IdCatalogo").text();
                   var NombreRepositorio=$Empresas.find("NombreRepositorio").text();
                   var NombreCatalogo=$Empresas.find("NombreCatalogo").text();
                   var Index=ArrayDirectories.indexOf(IdEmpresa);
                   var IdexRepositorio=ArrayRepositorios.indexOf(IdRepositorio);                
                   var IndexCatalogo=ArrayCatalogos.indexOf(IdCatalogo);

                   /* Comprobación para no repetir empresas */
                  if(Index === (-1)) 
                  {
                      ArrayDirectories[IdEmpresa]=IdEmpresa;
                      $('#Tree_Repository_').append('<li id="'+ClaveEmpresa+'" class="unselectable expanded folder" data="icon: \'enterprise.png\'">'+NombreEmpresa+'<ul id="'+ClaveEmpresa+'_clave"></ul>');
                  }

                  if(IdexRepositorio === (-1)) 
                  {
                      ArrayRepositorios[IdRepositorio]=IdRepositorio;
                      $('#'+ClaveEmpresa+'_clave').append('<li id="'+NombreRepositorio+'" class="unselectable expanded folder" data="icon: \'Repositorio.png\'">'+NombreRepositorio+'<ul id="rep_'+IdRepositorio+'"></ul>');
                  } 

   //                   ArrayCatalogos[IndexCatalogo]=IndexCatalogo;
                      $('#rep_'+IdRepositorio).append('<li id="'+IdCatalogo+'" class="unselectable expanded folder" data="icon: \'Catalogo.png\'">'+NombreCatalogo+'<ul id="'+IdCatalogo+'_catalogo"></ul>');
               });     

               $(xml).find("Error").each(function()
               {
                   var $Instancias=$(this);
                   var estado=$Instancias.find("Estado").text();
                   var mensaje=$Instancias.find("Mensaje").text();
                   Error(mensaje);
               });

               $("#tree_catalogos").dynatree({onActivate: function(node) {                    
                   if(node.data.key>0) /* Condición que solo cumplen los repositorios en este árbol */
                   {
                       $('#TableStructureCatalogos').remove();
                       $('#WS_Catalogo').empty();
                       $('#WS_Catalogo').append('<div class="titulo_ventana">Estructura de Catálogo</div>'); 
                       $('#WS_Catalogo').append('<table id="TableStructureCatalogos"  class="TablaPresentacion"><thead><tr><th>Nonbre del Campo</th><th>Tipo de Campo</th><th>Longitud</th><th>Requerido</th></tr></thead></table>');
                       /* SetTableStructura es una función localizada en Designer.js */
                       SetTableStructura("Catalogo_"+node.data.title,'TableStructureCatalogos',1);/* En el archivo de Configuración
                        *                                                          Los nombres de cada catálogo anteceden con Catalogo_ */
                   }                    
               }});
          }       
      };
   };

   ClassCatalogAdministrator.prototype.GetCatalogRecordsInXml = function(CatalogName, CatalogType)
   {       
       var xml = undefined;
       
       $.ajax({
         async:false, 
         cache:false,
         dataType:"html", 
         type: 'POST',   
         url: "php/Catalog.php",
         data: "opcion=GetCatalogRecordsInXml&DataBaseName="+EnvironmentData.DataBaseName+'&IdUser='+EnvironmentData.IdUsuario+'&UserName='+EnvironmentData.NombreUsuario+'&CatalogType='+CatalogType +'&CatalogName='+CatalogName, 
         success:  function(respuesta){
            if($.parseXML( respuesta )===null){Error(respuesta);return 0;}else xml = $.parseXML( respuesta );

            $(xml).find("Error").each(function()
            {
                var $Instancias=$(this);
                var estado=$Instancias.find("Estado").text();
                var mensaje=$Instancias.find("Mensaje").text();
                Error(mensaje);
                xml = 0;
            });
         },
         beforeSend:function(){},
         error:function(objXMLHttpRequest){Error(objXMLHttpRequest);}
       });
       
       return xml;
   };

ClassCatalogAdministrator.prototype.ViewCatalog = function()
   {            

        $('#WS_Catalogo').empty();
        $('#WS_Catalogo').append('<div class="titulo_ventana">Consulta de un Catálogo</div>');
        $('#WS_Catalogo').append('<p>Seleccione los datos solicitados</p>');
        $('#WS_Catalogo').append('<table id="TableCatalogosAdd"><tbody><tr><td></td><td></td></tbody></table>');
        $('#WS_Catalogo').append('<p>Empresa: <select id="SelectEmpresasAddCatalogo" class = "FormStandart"><option value = "0">Seleccione una empresa...</option></select></p>');
        $('#WS_Catalogo').append('<p>Repositorio: <select id="SelectRepositoriosAddCatalogo" class = "FormStandart"><option value = "0">Esperando empresa...</option></select></p>');
        $('#WS_Catalogo').append('<p>Catalogos: <select id="SelectCatalogosAddOption" class = "FormStandart"><option value = "0">Esperando Repositorio...</option></select></p>');

        var enterprises = Enterprise.GetEnterprises();

        $(enterprises).find('Enterprise').each(function()
        {
            var IdEnterprise = $(this).find('IdEmpresa').text();
            var EnterpriseKey = $(this).find('ClaveEmpresa').text();
            var EnterpriseName = $(this).find('NombreEmpresa').text();
           $("#SelectEmpresasAddCatalogo").append("<option value=\""+EnterpriseKey+"\">"+EnterpriseName+"</option>");
        });

        $('#SelectEmpresasAddCatalogo').change(function()
        {
            
            _DeleteCatalogTable();    
            var EnterpriseKey = $('#SelectEmpresasAddCatalogo').val();
            if(EnterpriseKey!=="0")
            {
              var repositories = Repository.GetRepositories(EnterpriseKey);
              
              $("#SelectRepositoriosAddCatalogo option").remove();
               $("#SelectRepositoriosAddCatalogo").append("<option value='0'>Seleccione un Repositorio</option>");
              $(repositories).find('Repository').each(function()
                {
                    var IdRepository = $(this).find('IdRepositorio').text();
                    var RepositoryName = $(this).find('NombreRepositorio').text();
                    $('#SelectRepositoriosAddCatalogo').append('<option value = "'+IdRepository+'">'+RepositoryName+'</option>');
                });
                
               $('#SelectRepositoriosAddCatalogo').change(function(){
                   
                   _DeleteCatalogTable();
                
                   if($('#SelectRepositoriosAddCatalogo').val()!=="0")
                   {                       
                        var IdRepositorio=$('#SelectRepositoriosAddCatalogo').val();     

                        _getCatalogos(IdRepositorio,'SelectCatalogosAddOption');
                       
                        /* Al elegir Catálogo se insertan los formularios para captura */
                        $('#SelectCatalogosAddOption').change(function(){
                           
                            _DeleteCatalogTable();
                           
                             var Catalogo=$('#SelectCatalogosAddOption').val();                          
                             if(Catalogo==="0")
                             {
//                                 Advertencia("El catálogo seleccionado no contiene un nombre válido");
                                 return;
                             }
                                 var CatalogName = $('#SelectCatalogosAddOption option:selected').html();
                                 ClassCatalogAdministrator.CatalogName = CatalogName;
                                 var CatalogXml = _GetCatalogRecordsInXml(CatalogName, '');
                                 
                                 if(CatalogXml !== undefined)
                                    _BuildCatalogTable(CatalogXml, IdRepositorio);
                        });
                   }
                   else
                   {
                       $('#SelectCatalogosAddOption option').remove();
                       $('#SelectCatalogosAddOption').append('<option value="0">Esperando Empresa...</option>');
                       $('#AceptarAddCatalogOption').remove();
                   }
               });
            }
            else
            {
                $('#SelectRepositoriosAddCatalogo option').remove();
                $('#SelectRepositoriosAddCatalogo').append('<option value="0">Esperando Empresa...</option>');
                $('#SelectCatalogosAddOption option').remove();
                $('#SelectCatalogosAddOption').append('<option value="0">Esperando Repositorio...</option>');
                $('#AceptarAddCatalogOption').remove();
            }

        });     
    };
ClassCatalogAdministrator.prototype.getCatalogName = function()
{
    return ClassCatalogAdministrator.CatalogName;
};

ClassCatalogAdministrator.prototype.SetCatalogName = function(CatalogName)
{
    ClassCatalogAdministrator.CatalogName = CatalogName;
};

ClassCatalogAdministrator.prototype.getCatalogos = function(IdRepositorio,SelectCatalogos)
{        
    var DataBaseName=$('#database_name').val();
    var IdUsuario=$('#id_usr').val();

    $.ajax({
    async:false, 
    cache:false,
    dataType:"html", 
    type: 'POST',   
    url: "php/ContentManagement.php",
    data: "opcion=getCatalogos&DataBaseName="+DataBaseName+'&IdUsuario='+IdUsuario+"&IdRepositorio="+IdRepositorio, 
    success:  function(respuesta){
    var xml=respuesta; 
    $("#"+SelectCatalogos+" option").remove();
    $("#"+SelectCatalogos).append("<option value = \"0\">Seleccione un Catálogo</option>");
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
        Error(mensaje);
    });
    },
    beforeSend:function(){},
    error:function(objXMLHttpRequest){Error(objXMLHttpRequest);}
    });
};