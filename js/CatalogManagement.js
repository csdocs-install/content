/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/* global BotonesWindow, EnvironmentData, Enterprise, LanguajeDataTable, Repository, BootstrapDialog */

var WindowCatalogo = {minHeight: 400, minWidth: 300, width: 750, height: 500, closeOnEscape: false};
var CatalogTabledT = undefined;
var CatalogTableDT = undefined;

$(document).ready(function ()
{
    $('.LinkCatalogs').click(function () {
        var catalogManager = new ClassCatalogAdministrator();
        catalogManager.buildConsole();
    });
});

var ClassCatalogAdministrator = function ()
{
    var self = this;
    this.CatalogType = undefined;
    this.CatalogName = undefined;
    this.IdCatalog = 0;
    this.IdRepository = undefined;
    this.RepositoryName = undefined;
    var cont = 0;

    this.buildConsole = function () {
        $('#div_consola_catalogos').remove();

        $('body').append('\n\
                    <div id="div_consola_catalogos" style="display: none">\n\
                <div class="menu_lateral">\n\
                    <div id="AccordionCatalogs">\n\
                        <div>\n\
                            <h3><a href="#">Catálogos</a></h3>\n\
                            <div>\n\
                                <table id ="TableAccordionCatalogs" class = "TableInsideAccordion">\n\
                                    <thead>\n\
                                        <tr>\n\
                                            <th></th>\n\
                                            <th></th>\n\
                                        </tr>\n\
                                    </thead>\n\
                                    <tr id="tr_ViewCatalog" title="Vista de un Catálogo">\n\
                                        <td><img src="img/CatalogoAgregar.png"></td>\n\
                                        <td>Consultar</td>\n\
                                    </tr>\n\
                                    <tr id="tr_NewCatalogo" title="Nuevo Catalogo">\n\
                                        <td><img src="img/CatalogoAgregar.png" ></td>\n\
                                        <td>Nuevo Catálogo</td>\n\
                                    </tr>\n\
                                    <tr id="tr_Catalogos" title="Catalogos">\n\
                                        <td><img src="img/Catalogo.png"></td>\n\
                                        <td>Catálogos</td>\n\
                                    </tr>\n\
                                    <tr>\n\
                                        <td colspan="2"><div id="consola_catalogos_tree"></div></td>\n\
                                    </tr>\n\
                                </table>\n\
                            </div>\n\
                        </div>\n\
                    </div>\n\
                </div>\n\
                <div class="work_space" id="WS_Catalogo"></div>\n\
            </div>');

        /********* Efectos sobre tabla dentro de acordeón ***********/
        $('#TableAccordionCatalogs').on('click', 'tr', function ()
        {
            var active = $('#TableAccordionCatalogs tr.TableInsideAccordionFocus');
            $('#TableAccordionCatalogs tr').removeClass('TableInsideAccordionFocus');
            $('#TableAccordionCatalogs tr').removeClass('TableInsideAccordionActive');
            $(active).addClass('TableInsideAccordionFocus');
            $(this).removeClass('TableInsideAccordionHoverWithoutClass');
            $(this).addClass('TableInsideAccordionActive');
        });
        $('#TableAccordionCatalogs tr').hover(function ()
        {
            if ($(this).hasClass('TableInsideAccordionActive') || $(this).hasClass('TableInsideAccordionFocus'))
                $(this).addClass('TableInsideAccordionHoverWithClass');
            else
                $(this).addClass('TableInsideAccordionHoverWithoutClass');
        });
        $('#TableAccordionCatalogs tr').mouseout(function ()
        {
            if ($(this).hasClass('TableInsideAccordionActive') || $(this).hasClass('TableInsideAccordionFocus'))
                $(this).removeClass('TableInsideAccordionHoverWithClass');
            else
                $(this).removeClass('TableInsideAccordionHoverWithoutClass');
        });
        /* Fin de Efectos  */

        var catalogManager = new ClassCatalogAdministrator();

        $('#tr_NewCatalogo').click(function () {
            catalogManager.createNewCatalogInterface();
        });
        $('#tr_Catalogos').click(function () {
            catalogManager.GetListCatalogos();
        });
        $('#tr_ViewCatalog').click(function () {
            catalogManager.ViewCatalog();
        });

        $("#tree_catalogos").dynatree();

        $('#div_consola_catalogos').dialog(WindowCatalogo, {title: "Consola de Catálogos"}).dialogExtend(BotonesWindow);
        $("#AccordionCatalogs").accordion({header: "h3", collapsible: true, heightStyle: "content"});
        $('#tr_ViewCatalog').click();

    };

    this.createNewCatalogInterface = function ()
    {
        $('#WS_Catalogo').empty();

        var content = $('<div>');
        var form = $('<select>', {id: "SelectEmpresasAddCatalogo", class: "form-control"});
        form.append("<option>Seleccione una empresa</option>");
        var label = '<label>Empresa<label>';
        var $formGroup = $('<div>', {class: "form-group"});

        $formGroup.append(label);
        $formGroup.append(form);
        content.append($formGroup);

        $formGroup = $('<div>', {class: "form-group"});
        form = $('<select>', {id: "SelectRepositoriosAddCatalogo", class: "form-control"});
        form.append("<option>Seleccione un repositorio</option>");
        label = '<label>Repositorio<label>';

        $formGroup.append(label);
        $formGroup.append(form);
        content.append($formGroup);

        $formGroup = $('<div>', {class: "form-group"});
        form = $('<input>', {class: "form-control", id: "catalogNameNew", class:"form-control"});
        label = '<label>Nombre Catálogo<label>';

        $formGroup.append(label);
        $formGroup.append(form);
        content.append($formGroup);

        $('#WS_Catalogo').append(content);

        var $table = $('<table>', {id: "newCatalogTable", class: "table table-striped table-bordered table-hover table-condensed display hover"});
        $table.append("<thead><tr><th>Nombre</th><th>Tipo</th><th>Longitud</th><th>Requerido</th></tr></thead>");

        $('#WS_Catalogo').append($table);

        CatalogTabledT = $('#newCatalogTable').dataTable({
            'bPaginate': false, 'bInfo': false, bFilter: false, "bSort": false, "autoWidth": false, "oLanguage": LanguajeDataTable,
            "dom": 'lfTrtip',
            "oTableTools": {
                "aButtons": [
                    {"sExtends": "text", "sButtonText": '<i class="fa fa-plus-circle fa-lg"></i> Nuevo Campo', "fnClick": function () {
                            _newFieldInterface();
                        }},
                    {"sExtends": "text", "sButtonText": '<i class="fa fa-trash-o fa-lg"></i> Eliminar', sButtonClass: "", "fnClick": function () {
                            _confirmDeleteFieldOfNewCatalog();
                        }},
                    {"sExtends": "text", "sButtonText": '<i class="fa fa-cogs fa-lg"></i> Construir Catálogo', sButtonClass: "", "fnClick": function () {
                            _buildCatalog();
                        }},
                    {
                        "sExtends": "collection",
                        "sButtonText": '<i class="fa fa-floppy-o fa-lg"></i>',
                        "aButtons": ["copy", "csv", "xls", "pdf"]
                    }
                ]
            },
            "sSwfPath": "../apis/DataTables/extensions/TableTools/swf/copy_csv_xls_pdf.swf"
        });

        CatalogTableDT = new $.fn.dataTable.Api('#newCatalogTable');

        $('#newCatalogTable tbody').on('click', 'tr', function ()
        {
            CatalogTableDT.$('tr.selected').removeClass('selected');
            $(this).addClass('selected');
        });

        var enterpriseManager = new ClassEnterprise();
        var enterprises = enterpriseManager.GetEnterprises();

        $(enterprises).find('Enterprise').each(function () {
            var IdEnterprise = $(this).find('IdEmpresa').text();
            var EnterpriseKey = $(this).find('ClaveEmpresa').text();
            var EnterpriseName = $(this).find('NombreEmpresa').text();
            $("#SelectEmpresasAddCatalogo").append("<option value=\"" + EnterpriseKey + "\">" + EnterpriseName + "</option>");
        });

        $('#btnBuildCatalog').on("click", "_buildCatalog");

        _setActionsToSelects();

    };

    /*******************************************************************************
     * Una vez seleccionada la empresa y el repositorio, se carga el XML que el usuario elegío.
     * @returns {undefined}
     */
    _CM_AddCatalogoXML = function ()
    {
        Loading();
        var ClaveEmpresa = $('#SelectEmpresasAddCatalogo').val();
        var IdRepositorio = $('#SelectRepositoriosAddCatalogo').val();
        NombreRepositorio = $('#SelectRepositoriosAddCatalogo option:selected').html(),
            xml_usuario = document.getElementById("InputFile_AddCatalogo"), archivo = xml_usuario.files;

        var data = new FormData();

        for (i = 0; i < archivo.length; i++)
        {
            data.append('archivo', archivo[i]);
            data.append('opcion', 'AddCatalogoXML');
            data.append('id_usuario', EnvironmentData.IdUsuario);
            data.append('DataBaseName', EnvironmentData.DataBaseName);
            data.append('ClaveEmpresa', ClaveEmpresa);
            data.append('NombreRepositorio', NombreRepositorio);
            data.append('IdRepository', IdRepositorio);
            data.append('nombre_usuario', EnvironmentData.NombreUsuario);
        }

        $('#InputFile_AddCatalogo').remove();
        $('#WS_Catalogo').append('<input type ="file" accept="text/xml" id="InputFile_AddCatalogo">');
        $('#InputFile_AddCatalogo').change(function () {
            _CM_AddCatalogoXML();
        });

        $.ajax({
            async: true,
            cache: false,
            dataType: 'html',
            type: 'POST',
            url: "php/Catalog.php",
            processData: false,
            contentType: false,
            data: data,
            success: function (xml) {
                $('#Loading').dialog('close');
                Salida(xml);

                $(xml).find("Error").each(function ()
                {
                    var $Instancias = $(this);
                    var estado = $Instancias.find("Estado").text();
                    var mensaje = $Instancias.find("Mensaje").text();
                    errorMessage(mensaje);
                });
            },
            beforeSend: function () {},
            error: function (jqXHR, textStatus, errorThrown) {
                errorMessage(textStatus + "<br>" + errorThrown);
            }
        });
    };

    _BuildCatalogTable = function (CatalogRecords, RepositoryName, IdRepository)
    {
        var CatalogName = self.getCatalogName();
        var CatalogStructure = GeStructure(RepositoryName + '_' + CatalogName);
        var Columns = [{"className": "int", "targets": [0]}];
        if (CatalogStructure === undefined)
            return;

        var CatalogFields = new Array(), cont = 0, thead = '<thead><tr><th>ID</th>';
        $('#WS_Catalogo').append('<table class = "display hover" id = "CatalogsAdminTable"></table>');

        $(CatalogStructure).find('Campo').each(function ()
        {
            var List = $(this).find("tipo").text();
            var FieldType = $(this).find('type').text();
            var name = $(this).find("name").text();

            if (List.length > 0)/* Tipo del List */
                return;

            CatalogFields[cont] = name;
            Columns[Columns.length] = {"className": FieldType, "targets": [cont + 1]};
            thead += '<th>' + name + '</th>';
            cont++;
        });

        thead += '</tr></thead>';

        $('#CatalogsAdminTable').append(thead);

        CatalogTabledT = $('#CatalogsAdminTable').dataTable({
            "columnDefs": Columns,
            "dom": 'f<"ButtonCatalogAdminTable">lTrtip',
            "oTableTools": {
                "aButtons": [
                    {"sExtends": "text", "sButtonText": '<i class="fa fa-plus-circle fa-lg"></i> Nuevo Registro', "fnClick": function () {
                            _FormsCatalogToAddRecord(RepositoryName);
                        }},
                    {"sExtends": "text", "sButtonText": '<i class="fa fa-cogs fa-lg"></i> Agregar Columna', "fnClick": function () {
                            _AddNewColumnToCatalog();
                        }},
                    {"sExtends": "text", "sButtonText": '<i class="fa fa-exclamation-triangle fa-lg"></i> Eliminar Columna', "fnClick": function () {
                            _DeleteColum();
                        }},

                    {"sExtends": "text", "sButtonText": '<i class="fa fa-trash-o fa-lg"></i> Eliminar Catálogo', "fnClick": function () {
                            _CM_Ctalog(RepositoryName);
                        }},
                    {
                        "sExtends": "collection",
                        "sButtonText": '<i class="fa fa-floppy-o fa-lg"></i>',
                        "aButtons": ["csv", "xls", "pdf", "copy"]
                    }
                ]
            },
            "autoWidth": false,
            "sSwfPath": "../apis/DataTables/extensions/TableTools/swf/copy_csv_xls_pdf.swf",
            "fnCreatedRow": function (nRow, aData, iDataIndex) {
                _Jedit(nRow, IdRepository, RepositoryName);    /* Función que se invoca para editar una celda de la tabla catálogo */
            }
        });

        CatalogTableDT = new $.fn.dataTable.Api('#CatalogsAdminTable');

//        $('div.ButtonCatalogAdminTable').append('<input type = "button" value = "Nuevo Registro" id = "ButtonCatalogAdminTableAdd" onclick = "_FormsCatalogToAddRecord();">');
//        $('#ButtonCatalogAdminTableAdd').button();

        $(CatalogRecords).find("CatalogRecord").each(function ()
        {
            var $Campo = $(this);
            var IdRecord = $Campo.find('Id' + CatalogName).text();
            var data = [
                /*[0]*/ IdRecord
            ];

            for (var cont = 0; cont < CatalogFields.length; cont++)  /* Recorrer los datos que contiene cada fila del catálogo */
            {
                var field = CatalogFields[cont];
                var value = $Campo.find(field).text();
                data[data.length] = value;
            }

            var ai = CatalogTableDT.row.add(data).draw();
            var n = CatalogTabledT.fnSettings().aoData[ ai[0] ].nTr;
            n.setAttribute('id', IdRecord);
        });


        $('#CatalogsAdminTable tbody').on('click', 'tr', function () {
            CatalogTableDT.$('tr.selected').removeClass('selected');
            $(this).addClass('selected');
        });

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
    _Jedit = function (nRow, IdRepository, repositoryName)
    {
        var row = undefined;
        $(nRow).children().each(function ()
        {
            var cell = $(this);
            if ($(this).index() === 0)
                return;

            $(this).editable('../php/Catalog.php',
                {
                    indicator: '<img src="../img/loadinfologin.gif">',
                    tooltip: 'Click para editar...',
                    name: "NewValue",
                    id: "IdCatalog",
                    "height": "22px",
                    method: "POST",
                    event: "dblclick",
                    onsubmit: function (settings, Row) {
                        row = Row;
                    },
                    submitdata:
                        {
                            opcion: "ModifyCatalogRecord",
                            repositoryName: repositoryName,
                            CatalogName: function () {
                                return self.getCatalogName();
                            },
                            IdCatalog: function () {
                                return $(row).parent().attr('id');
                            },
                            "FieldName": function ()
                            {   /* FieldName es el campo del catalogo a modificar */
                                var idx = CatalogTableDT.cell(row).index().column;
                                var title = CatalogTableDT.column(idx).header();
                                var FieldName = $(title).html();
                                return FieldName;
                            },
                            "FieldType": function () {
                                return $(cell).attr('class');
                            }
                        },
                    data: function (value, settings)
                    {
                        /* Convert <br> to newline. */
                        var retval = value.replace(/<br[\s\/]?>/gi, '\n');
                        return retval;
                    },
                    "callback": function (xml, settings)
                    {
                        /* Redraw the table from the new data on the server */
//                    if($.parseXML( xml )===null){ errorMessage(xml); return 0;}else xml=$.parseXML( xml );
                    }
                });
        });
    };

    /*--------------------------------------------------------------------------
     * Agrega los formularios necesarios para ingresar un registro al catálogo
     *
     * @returns {undefined}
     ---------------------------------------------------------------------------*/
    _FormsCatalogToAddRecord = function (repositoryName)
    {
        $('#FormsCatalogToAddRegister').remove();
        $('body').append('<div id = "FormsCatalogToAddRegister" class = "detalle_archivo"><div class = "titulo_ventana">Nuevo Registro a' + self.getCatalogName() + '</div></div>');
        $('#FormsCatalogToAddRegister').append('<table id = "CatalogTableToAddNewRecord"></table>');
        var CatalogName = self.getCatalogName();

        SetTableStructura(repositoryName + "_" + CatalogName, 'CatalogTableToAddNewRecord', 0);

        var Forms = $('#CatalogTableToAddNewRecord tr td input');

        var ValidateForms = new ClassFieldsValidator();
        ValidateForms.InspectCharacters(Forms);

        $('#FormsCatalogToAddRegister').dialog({title: "Nuevo Registro", width: 500, minWidth: 500, Height: 500, minHeight: 500, closeOnEscape: true, modal: true,
            buttons: {
                "Agregar": {click: function () {
                        _AddNewRecord();
                    }, text: "Agregar"},
                "Cancelar": {click: function () {
                        $(this).dialog('destroy');
                    }, text: "Cancelar"}
            }});
    };


    /*---------------------------------------------------------------------------
     * Se almacenan los valores introducidos por el usuario.
     * @returns {undefined}
     ---------------------------------------------------------------------------*/
    _AddNewRecord = function ()
    {
        var Forms = $('#CatalogTableToAddNewRecord tr td input');
//        console.log(Forms);
        var ValidateForms = new ClassFieldsValidator();
        var ResultValidate = ValidateForms.ValidateFields(Forms);
        console.log("Resultado de la validacion = " + ResultValidate);
        if (!ResultValidate)
            return 0;
        else
            console.log('Validacion correcta');

        $('#FormsCatalogToAddRegister').append('<div class="PlaceWaiting" id = "CatalogsPlaceWaiting"><img src="../img/loadinfologin.gif"></div>');
        var IdRepositorio = $('#SelectRepositoriosAddCatalogo').val();
        var repositoryName = $('#SelectRepositoriosAddCatalogo option:selected').html();
        var IdEmpresa = $('#SelectEmpresasAddCatalogo').val();
        var CatalogName = self.getCatalogName();
        var XMLResponse = _CreateNewXmlRecord(repositoryName, CatalogName);

        $.ajax({
            async: false,
            cache: false,
            dataType: "html",
            type: 'POST',
            url: "php/Catalog.php",
            data: "opcion=AddNewRecord&XmlReponse=" + XMLResponse + "&IdRepositorio=" + IdRepositorio + '&repositoryName=' + repositoryName + "&IdEmpresa=" + IdEmpresa + "&CatalogName=" + CatalogName,
            success: function (xml) {
                $('#CatalogsPlaceWaiting').remove();
                if ($.parseXML(xml) === null) {
                    errorMessage(xml);
                    return 0;
                } else
                    xml = $.parseXML(xml);
                $(xml).find('AddNewRecord').each(function () {
                    var Mensaje = $(this).find('Mensaje').text();
                    var IdCatalog = $(this).find('IdCatalog').text();
                    var data = [IdCatalog];

                    $(this).find('Field').each(function () {
                        var Field = this;
                        var FieldName = $(Field).find('FieldName').text();
                        var FieldValue = $(Field).find('FieldValue').text();
                        var FieldType = $(Field).find('FieldType').text();
                        data[data.length] = FieldValue;
                    });

                    var ai = CatalogTableDT.row.add(data).draw();
                    var n = CatalogTabledT.fnSettings().aoData[ ai[0] ].nTr;
                    n.setAttribute('id', IdCatalog);
                    Notificacion(Mensaje);

                    $('#FormsCatalogToAddRegister').dialog('destroy');

                });
                $(xml).find("Error").each(function ()
                {
                    var $Instancias = $(this);
                    var estado = $Instancias.find("Estado").text();
                    var mensaje = $Instancias.find("Mensaje").text();
                    errorMessage(mensaje);
                    xml = 0;
                });
            },
            beforeSend: function () {},
            error: function (jqXHR, textStatus, errorThrown) {
                $('#CatalogsPlaceWaiting').remove();
                errorMessage(textStatus + "<br>" + errorThrown);
            }
        });
    };

    /* -------------------------------------------------------------------------
     * Genera un XML con los datos del nuevo registro a insertar en el catálogo
     *
     * @param {type} CatalogName
     * @returns {XML|String}
     ---------------------------------------------------------------------------*/
    _CreateNewXmlRecord = function (repositoryName, CatalogName)
    {
        var xmlStruct = GeStructure(repositoryName + "_" + CatalogName);
        var XMLResponse = "<MetaDatas version='1.0' encoding='UTF-8'>";
        $(xmlStruct).find("Campo").each(function ()
        {
            var $Campo = $(this);
            var tipo = $Campo.find("tipo").text();
            if (tipo.length > 0) {
                return;
            }
            var name = $Campo.find("name").text();
            var type = $Campo.find("type").text();
            var long = $Campo.find("long").text();
            var required = $Campo.find("required").text();
            var id = '_' + name;
            var value = $('#' + id).val();/* Id que contendrá cada elemento recuperado */

            var XML = '<MetaData>\n\
                        <name>' + name + '</name>\n\
                        <value>' + value + '</value>\n\
                        <type>' + type + '</type>\n\
                        <long>' + long + '</long>\n\
                     </MetaData>';
            XMLResponse = XMLResponse + XML;
        });

        /* Campos por default como el IdDirectory y IdEmpresa se envian en el XML */
        XMLResponse += '</MetaDatas>';

        return XMLResponse;
    };

    _AddNewColumnToCatalog = function ()
    {
        $('#DivNewColumnToCatalog').remove();
        $('body').append('<div id = "DivNewColumnToCatalog"></div>');
        $('#DivNewColumnToCatalog').append('<div class = "titulo_ventana"></div>');
        _AddFormsNewColumn();
        $('#DivNewColumnToCatalog').dialog({title: "Agregar nueva columna al catálogo " + self.getCatalogName(), width: 500, height: 400, minWidth: 400, minHeight: 400, modal: true,
            buttons: {"Agregar": {click: function () {
                        _AddNewColumn();
                    }, text: "Agregar"},
                "Cancelar": {click: function () {
                        $(this).dialog('destroy');
                    }, text: "Cancelar"}}});
    };
    //Modificando las columnas
    _DeleteColum = function ()
    {
        $('#DivDeleteColumn').remove();
        $('body').append('<div id = "DivDeleteColumn"></div>');
        $('#DivDeleteColumn').append('<div class = "titulo_ventana"></div>');
        $('#DivDeleteColumn').append('<label>Selecciona el campo:</label><select class="option FormStandart" style="width:60%;margin-left: 20px "></select>');
        $('.option', {id: "SelectColumnCatalogo"});
        _FormsDeleteColumn();
        $('#DivDeleteColumn').dialog({title: "Eliminar columna al catalago " + self.getCatalogName(), width: 500, height: 400, minWidth: 400, minHeight: 400, modal: true,
            buttons: {"eliminar": {click: function () {
                        _CM_Column();
                        $(this).dialog('destroy');
                    }, text: "Eliminar"},
                "Cancelar": {click: function () {
                        $(this).dialog('destroy');
                    }, text: "Cancelar"}}});
    };


    _AddFormsNewColumn = function ()
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
        if (FieldType === 'varchar')
            $('#CatalogFieldLength').prop("disabled", false);
        else
        {
            $('#CatalogFieldLength').prop("disabled", true);
            $('#CatalogFieldLength').val('0');
        }

        $('#CatalogNewFieldType').change(function ()
        {
            var FieldType = $('#CatalogNewFieldType').val();

            if (FieldType === 'varchar')
                $('#CatalogFieldLength').prop("disabled", false);
            else
            {
                $('#CatalogFieldLength').prop("disabled", true);
                $('#CatalogFieldLength').val('0');
            }

        });

        var RegularExpressionFieldName = /[^a-zA-Z0-9\_]/g;

        $('#CatalogNewFieldName').keyup(function ()
        {
            $(this).attr('title', 'Debe ser una alfanumérica de no más de 64 caracteres, es posible usar \'_\'');
            _ReplaceInvalidCharacters(RegularExpressionFieldName, this);
            if (_CheckIfExistColumn(this.value))
            {
                if (!$('#CatalogNewFieldName').hasClass('RequiredActivo'))
                    $('#CatalogNewFieldName').addClass('RequiredActivo');
                $('#CatalogNewFieldName').attr('title', 'Ya existe el campo');
            } else
            if ($('#CatalogNewFieldName').hasClass('RequiredActivo'))
            {
                $('#CatalogNewFieldName').removeClass('RequiredActivo');
                $('#CatalogNewFieldName').attr('title', 'Campo válido');
            }
        });

        var RegularExpressionFieldLength = /[^0-9]/g;
        $('#CatalogFieldLength').keyup(function ()
        {
            _ReplaceInvalidCharacters(RegularExpressionFieldLength, this);
        });

        $('#CatalogNewFieldName').tooltip();
        $('#CatalogFieldLength').tooltip();

    };
    //Formulario para seleccionar la columna a eliminar
    _FormsDeleteColumn = function () {

        var RepositoryName = $('#SelectRepositoriosAddCatalogo option:selected').html();
        var CatalogName = self.getCatalogName();
        var CatalogStructure = GeStructure(RepositoryName + '_' + CatalogName);
        console.log(CatalogStructure);

        $(CatalogStructure).find('Campo').each(function ()
        {
            var name = $(this).find("name").text();
            var option = $('<option>', {value: name}).append(name);
            if (option.val().length!==0)
            {
                $('.option').append(option);
            }else
            {
                $('.option').append('<option style="display: none">Esperando la columna...</option>');
            }

        });
    }
    //Confirmar Eliminacion de columnas
    _CM_Column = function ()
    {
        var selectColumn = $('.option').val();
        var FieldName = self.getCatalogName();
        BootstrapDialog.show({
            title: '<i class="fa fa-trash-o fa-lg"></i> Eliminar Catálago',
            message: '<p>La columna  <b>' + selectColumn + '</b> será eliminado del catálogo <b>' + FieldName +  '</b> ¿Desea Continuar?</p>',
            closable: true,
            closeByBackdrop: true,
            closeByKeyboard: true,
            size: BootstrapDialog.SIZE_SMALL,
            type: BootstrapDialog.TYPE_DANGER,
            buttons: [
                {
                    hotkey: 13,
                    icon: "fa fa-trash fa-lg",
                    label: 'Eliminar',
                    cssClass: "btn-danger",
                    action: function (dialogRef) {
                        var button = this;
                        button.spin();
                        dialogRef.enableButtons(false);
                        dialogRef.setClosable(false);

                        D_Column()
                        dialogRef.close();
                        button.stopSpin();
                    }
                },
                {
                    label: 'Cancelar',
                    action: function(dialogRef){
                        dialogRef.close();
                    }
                }
            ],
            onshown: function(dialogRef){

            }
        });
    }
    //Confirmar eliminacion del catalogo
    var _CM_Ctalog = function(){
        var FieldName = self.getCatalogName();

        BootstrapDialog.show({
            title: '<i class="fa fa-trash-o fa-lg"></i> Eliminar Catálago',
            message: '<p>El catálogo <b>' + FieldName + '</b> será eliminado permanentemente de la base de datos. ¿Desea Continuar?</p>',
            closable: true,
            closeByBackdrop: true,
            closeByKeyboard: true,
            size: BootstrapDialog.SIZE_SMALL,
            type: BootstrapDialog.TYPE_DANGER,
            buttons: [
                {
                    hotkey: 13,
                    icon: "fa fa-trash fa-lg",
                    label: 'Eliminar',
                    cssClass: "btn-danger",
                    action: function (dialogRef) {
                        var button = this;
                        button.spin();
                        dialogRef.enableButtons(false);
                        dialogRef.setClosable(false);

                        D_Catalog()
                        dialogRef.close();
                        button.stopSpin();
                    }
                },
                {
                    label: 'Cancelar',
                    action: function(dialogRef){
                        dialogRef.close();
                    }
                }
            ],
            onshown: function(dialogRef){

            }
        });
    }
    //Enviar y recibir datos al backend para eliminar el catalogo
    D_Column = function()
    {
        var FieldName = self.getCatalogName();
        var RepositoryName = $('#SelectRepositoriosAddCatalogo option:selected').html();
        var selectColumn = $('.option').val();
        //var selectId = "#"+selectColumn;
        //var RequiredField = $('#CatalogRequiredField').val();
        var DataBaseName = EnvironmentData.DataBaseName;

        $.ajax({
            async: true,
            cache: false,
            dataType: "json",
            type: 'POST',
            url: "php/Catalog.php",
            data: {
                opcion: "D_Column",
                NameCatalog: FieldName,
                RepositoryName: RepositoryName,
                dataBaseName: DataBaseName,
                NameColumn: selectColumn
            },
            success:  function(response)
            {
                if (response.status == true) {
                    Notificacion(response.message);
                } else {
                    errorMessage(response.message);
                }
            },
            beforeSend:function(){},
            error: function(jqXHR, textStatus, errorThrown){
                errorMessage(textStatus +"<br>"+ errorThrown);
            }
        });
    }
    //Enviar y recibir datos del backend para eliminar el catalogo
    var D_Catalog = function ()
    {
        var FieldName = self.getCatalogName();
        var RepositoryName = $('#SelectRepositoriosAddCatalogo option:selected').html();
        //var IdRepository = $('#SelectRepositoriosAddCatalogo option:selected').val();
        var DataBaseName = EnvironmentData.DataBaseName;


        $.ajax({
            async: true,
            cache: false,
            dataType: "json",
            type: 'POST',
            url: "php/Catalog.php",
            data: {
                opcion: "D_Catalog",
                NameCatalog: FieldName,
                RepositoryName: RepositoryName,
                dataBaseName: DataBaseName

            },
            success:  function(response)
            {
                if (response.status) {
                    $('#CatalogsAdminTable').remove();
                    Notificacion(response.message);
                } else {
                    errorMessage(response.message);
                }
            },
            beforeSend:function(){},
            error: function(jqXHR, textStatus, errorThrown){
                errorMessage(textStatus +"<br>"+ errorThrown);
            }
        });

    }

    _ReplaceInvalidCharacters = function (RegularExpresion, Form)
    {
        Form.value = Form.value.replace(RegularExpresion, '');
    };

    _ValidatingFieldsNewColumn = function ()
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

        if (!$('#CatalogNewFieldName').val().length > 0)
        {
            FlagMistake = 0;
            if (!$('#CatalogNewFieldName').hasClass('RequiredActivo'))
                $('#CatalogNewFieldName').addClass('RequiredActivo');

            $('#CatalogNewFieldName').attr('title', 'No puede ser un campo vacio');
        } else
        {
            if (_CheckIfExistColumn($('#CatalogNewFieldName').val()))
            {
                if (!$('#CatalogNewFieldName').hasClass('RequiredActivo'))
                    $('#CatalogNewFieldName').addClass('RequiredActivo');
            } else
            if ($('#CatalogNewFieldName').hasClass('RequiredActivo'))
            {
                $('#CatalogNewFieldName').removeClass('RequiredActivo');
                $('#CatalogNewFieldName').attr('title', 'Campo válido');
            }
        }
        /*          Campo Longitud de columna    */

        if ($('#CatalogFieldLength').attr('disabled') === 'disabled')
        {
            $('#CatalogFieldLength').val('0');
            if ($('#CatalogFieldLength').hasClass('RequiredActivo'))
                $('#CatalogFieldLength').removeClass('RequiredActivo');
        } else
        {
            if ($.isNumeric($('#CatalogFieldLength').val()))
            {

                if (_CheckFieldLenght($('#CatalogNewFieldType').val()))
                {
                    if ($('#CatalogFieldLength').hasClass('RequiredActivo'))
                        $('#CatalogFieldLength').removeClass('RequiredActivo');
                } else
                {
                    if (!$('#CatalogFieldLength').hasClass('RequiredActivo'))
                        $('#CatalogFieldLength').addClass('RequiredActivo');
                    FlagMistake = 0;
                }
            } else
            {
                FlagMistake = 0;
                if (!$('#CatalogFieldLength').hasClass('RequiredActivo'))
                {
                    $('#CatalogFieldLength').addClass('RequiredActivo');
                    $('#CatalogFieldLength').attr('title', 'Debe ser un campo numérico');
                }

            }

        }

        return FlagMistake;
    };

    _CheckFieldLenght = function (FieldType)
    {
        var Lenght = $('#CatalogFieldLength').val();
        var flag = true;
        switch (FieldType)
        {
            case 'varchar':
                $('#CatalogFieldLength').attr('title', 'Para varchar el rango deber ser entre 1 y 255');
                flag = (Lenght > 0 && Lenght <= 255) ? true : false;
                break;
        }
        return flag;
    };

    _CheckIfExistColumn = function (NewColumnName)
    {
        var ExistanceFlag = false;
        NewColumnName = NewColumnName.toLowerCase();
        var columns = CatalogTableDT.columns().header();
        $.each(columns, function (index, properties) {

            var ColumnName = properties.innerHTML;
            ColumnName = ColumnName.toLowerCase();

//            console.log('if '+ColumnName+' '+' ('+ColumnName.length+') '+' === '+NewColumnName+' ('+NewColumnName.length+') ');
            if (ExistanceFlag)
                return;


            if (ColumnName === NewColumnName)
            {
//                console.log('La columna '+ NewColumnName +' existe ');
                ExistanceFlag = true;
                return;
            } else
            {
//                console.log('No existe la columna '+NewColumnName);
            }
        });

        return ExistanceFlag;
    };

    _AddNewColumn = function ()
    {
        var FieldName = $('#CatalogNewFieldName').val();
        var FieldType = $('#CatalogNewFieldType').val();
        var FieldLength = $('#CatalogFieldLength').val();
        var RequiredField = $('#CatalogRequiredField').val();
        var RepositoryName = $('#SelectRepositoriosAddCatalogo option:selected').html();
        var IdRepository = $('#SelectRepositoriosAddCatalogo option:selected').val();
        var CatalogName = self.getCatalogName();

        var validating = _ValidatingFieldsNewColumn();
        if (!validating)
            return 0;

        $.ajax({
            async: false,
            cache: false,
            dataType: "html",
            type: 'POST',
            url: "php/Catalog.php",
            data: "opcion=AddNewColumn&" + '&CatalogName=' + CatalogName + '&FieldName=' + FieldName + '&FieldType=' + FieldType + '&FieldLength=' + FieldLength + '&RequiredField=' + RequiredField + '&RepositoryName=' + RepositoryName,
            success: function (xml) {
                if ($.parseXML(xml) === null) {
                    errorMessage(xml);
                    return 0;
                } else
                    xml = $.parseXML(xml);

                $(xml).find('AddNewColumn').each(function ()
                {
                    var Mensaje = $(this).find('Mensaje').text();
                    Notificacion(Mensaje);
                    CatalogTableDT.destroy();
                    $('#CatalogsAdminTable').remove();
                    var CatalogXml = _GetCatalogRecordsInXml(RepositoryName, CatalogName, '');

                    if (CatalogXml !== undefined)
                        _BuildCatalogTable(CatalogXml, RepositoryName, IdRepository);

                    $('#DivNewColumnToCatalog').dialog('destroy');
                });

                $(xml).find("Error").each(function ()
                {
                    var $Instancias = $(this);
                    var estado = $Instancias.find("Estado").text();
                    var mensaje = $Instancias.find("Mensaje").text();
                    errorMessage(mensaje);
                    xml = 0;
                });
            },
            beforeSend: function () {},
            error: function (objXMLHttpRequest) {
                errorMessage(objXMLHttpRequest);
            }
        });
    };

    /*--------------------------------------------------------------------------
     *
     * @param {type} IdRepositorio
     * @param {type} IdCatalogOption
     * @returns {undefined}
     */

    _getCatalogos = function (IdRepositorio, IdCatalogOption)
    {
        var GetCatalogs = self.getCatalogos(IdRepositorio, IdCatalogOption);
        return GetCatalogs;
    };

    _GetCatalogRecordsInXml = function (repositoryName, CatalogName, CatalogType)
    {
        var CatalogXml = self.GetCatalogRecordsInXml(repositoryName, CatalogName, CatalogType);
        return CatalogXml;
    };

    _DeleteCatalogTable = function ()
    {
        if ($('#CatalogsAdminTable').length > 0)
        {
            CatalogTableDT.destroy();
            $('#CatalogsAdminTable').remove();
        }
    };

    _setActionsToSelects = function () {
        $('#SelectEmpresasAddCatalogo').change(function ()
        {
            var EnterpriseKey = $('#SelectEmpresasAddCatalogo').val();
            if (EnterpriseKey !== "0")
            {
                var repositories = Repository.GetRepositories(EnterpriseKey);

                $("#SelectRepositoriosAddCatalogo option").remove();
                $("#SelectRepositoriosAddCatalogo").append("<option value='0'>Seleccione un Repositorio</option>");
                $(repositories).find('Repository').each(function ()
                {
                    var IdRepository = $(this).find('IdRepositorio').text();
                    var RepositoryName = $(this).find('NombreRepositorio').text();
                    var option = $('<option>', {value: IdRepository, id: IdRepository, name: RepositoryName}).append(RepositoryName);
//                    $('#SelectRepositoriosAddCatalogo').append('<option value = "'+IdRepository+'">'+RepositoryName+'</option>');
                    $('#SelectRepositoriosAddCatalogo').append(option);
                });

                $('#SelectRepositoriosAddCatalogo').change(function () {
                    if ($('#SelectRepositoriosAddCatalogo').val() !== "0")
                    {
                        $('#InputFile_AddCatalogo').remove();
//                      $('#WS_Catalogo').append('<input type="file" id="InputFile_AddCatalogo" accept="text/xml">');
                        $('#InputFile_AddCatalogo').change(function () {
                            _CM_AddCatalogoXML();
                        });
                    } else
                        $('#InputFile_AddCatalogo').remove();
                });
            } else
            {
                $('#SelectRepositoriosAddCatalogo option').remove();
                $('#SelectRepositoriosAddCatalogo').append('<option value="0">Esperando Empresa...</option>');
                $('#InputFile_AddCatalogo').remove();
            }
        });
    };

    _newFieldInterface = function () {

        if (!parseInt($('#SelectRepositoriosAddCatalogo').val()) > 0 && !parseInt($('#SelectEmpresasAddCatalogo').val()) > 0)
            return Advertencia("Debe seleccionar una empresa y un repositorio");

        var fieldsManager = new FieldsManager();
        fieldsManager.windowNewField(this._AddNewFieldToCatalog);
    };
    /***************************************************************************
     *                                                                         *
     * @param {type} dialogRef: Cuadro de dialogo con las opciones del tipo de *
     *                          campo disponibles                              *
     * @returns {Number}                                                       *
     ***************************************************************************/
    _AddNewFieldToCatalog = function (dialogRef)
    {

        var fieldsManager = new FieldsManager();
        var FieldsValues = fieldsManager.GetFieldsValues(CatalogTabledT, CatalogTableDT);

        if (!$.isPlainObject(FieldsValues))
            return 0;
        cont++;
        var required = "No";

        if (String(FieldsValues.RequiredField).toLowerCase() === "true")
            required = "Si";

        var data = [FieldsValues.FieldName, FieldsValues.FieldType, FieldsValues.FieldLength, required];
        var ai = CatalogTableDT.row.add(data).draw();
        var n = CatalogTabledT.fnSettings().aoData[ ai[0] ].nTr;
        n.setAttribute('id', cont);

        CatalogTabledT.find('tr[id=' + cont + ']').click();

        dialogRef.close();
    };

    _buildCatalog = function () {
        var idRepository = $('#SelectRepositoriosAddCatalogo option:selected').attr('id');
        var repositoryName = $('#SelectRepositoriosAddCatalogo option:selected').attr('name');
        var xml = _getCatalogXml(idRepository, repositoryName);

        if (!$.parseXML(xml))
            return 0;

        var data = {"opcion": "buildNewCatalog", xml: xml};

        $.ajax({
            async: false,
            cache: false,
            dataType: "html",
            type: 'POST',
            url: "php/Catalog.php",
            data: data,
            success: function (xml)
            {
                if ($.parseXML(xml) === null) {
                    Salida(xml);
                    return 0;
                } else
                    xml = $.parseXML(xml);

                $(xml).find('newCatalogBuilded').each(function () {
                    var Mensaje = $(xml).find('Mensaje').text();
                    Notificacion(Mensaje);

                });

                $(xml).find("Error").each(function ()
                {
                    var $Error = $(this);
                    var mensaje = $Error.find("Mensaje").text();
                    errorMessage(mensaje);
                });

            },
            beforeSend: function () {},
            error: function (jqXHR, textStatus, errorThrown) {
                errorMessage(textStatus + "<br>" + errorThrown);
            }
        });
    };

    var _confirmDeleteFieldOfNewCatalog = function ()
    {
        var IdField = CatalogTabledT.find('tr.selected').attr('id');
        var FieldName = undefined;

        if (!(parseInt(IdField) > 0))
            return Advertencia('Debe seleccionar al menos un campo');

        $('#newCatalogTable tr.selected').each(function ()
        {
            var position = CatalogTabledT.fnGetPosition(this); // getting the clicked row position
            FieldName = CatalogTabledT.fnGetData(position)[0];
        });

        BootstrapDialog.show({
            title: '<i class="fa fa-exclamation-triangle fa-lg"></i> Eliminar Campo',
            type: BootstrapDialog.TYPE_DANGER,
            size: BootstrapDialog.SIZE_SMALL,
            message: '<p>El campo <b>' + FieldName + '</b> será removido del proceso de construcción del nuevo Catálogo. ¿Desea Continuar?</p>',
            closable: true,
            closeByBackdrop: true,
            closeByKeyboard: true,
            buttons: [
                {
                    label: 'Eliminar',
                    icon: 'fa fa-trash-o fa-lg',
                    cssClass: "btn-danger",
                    action: function (dialogRef) {
                        var button = this;
                        button.spin();
                        dialogRef.enableButtons(false);
                        dialogRef.setClosable(false);

                        if (_DeleteNewCatalogField(IdField))
                            dialogRef.close();
                        else {
                            dialogRef.enableButtons(true);
                            dialogRef.setClosable(true);
                            button.stopSpin();
                        }
                    }
                },
                {
                    label: "Cerrar",
                    action: function (dialogRef) {
                        dialogRef.close();
                    }
                }
            ],
            onshown: function (dialogRef) {

            }
        });
    };

    var _DeleteNewCatalogField = function (IdField)
    {
        CatalogTableDT.row('tr[id=' + IdField + ']').remove().draw(false);

        return 1;
    };

    /********************************************************************************
     * Genera la estructura XML que se va a utilizar para generar el nuevo Catálogo *
     *                                                                              *
     * @returns {Xml}                                                         *
     ********************************************************************************/
    _getCatalogXml = function (idRepository, repositoryName) {
        var FieldsValidator = new ClassFieldsValidator();
        var RegularExpression = /^([a-zA-Z0-9\_])+$/g;
        var catalogName = $('#catalogNameNew').val();
        catalogName = $.trim(catalogName);

        if (catalogName.length === 0) {
            FieldsValidator.AddClassRequiredActive($('#catalogNameNew'));
            $('#catalogNameNew').attr('title', 'El nombre del catálogo es obligatorio');
            return 0;
        } else {
            FieldsValidator.RemoveClassRequiredActive($('#catalogNameNew'));
            $('#catalogNameNew').attr('title', '');
        }

        if (!RegularExpression.test(catalogName))
        {
            FieldsValidator.AddClassRequiredActive($('#catalogNameNew'));
            $('#catalogNameNew').attr('title', 'Nombre inválido');
            return 0;
        } else {
            FieldsValidator.RemoveClassRequiredActive($('#catalogNameNew'));
            $('#catalogNameNew').attr('title', '');
        }

        if (CatalogTableDT.rows().data().length === 0)
        {
            Advertencia('Debe agregar por lo menos un campo en el nuevo catálogo');
            return;
        }

        var Xml = "";

        /* Se genera la misma estructura de xml de carga de repositorio a travÃ©s del attachment 'Nueva Instancia' */
        var Xml = "<NewCatalog version='1.0' encoding='UTF-8'>\n\
                        <CrearEstructuraCatalogo>";
        Xml += '<NombreCatalogo idRepositorio = "' + idRepository + '" nombreRepositorio = "' + repositoryName + '">' + catalogName + '</NombreCatalogo>\n\
                            <DefinitionUsersProperties>';

        var Rows = CatalogTableDT.rows().data().each(function (value, index)
        {
            var FieldName = value[0];
            var FieldType = value[1];
            var FieldLength = value[2];
            var RequiredField = value[3];

            if (RequiredField === 'Si')
                RequiredField = true;
            else if (RequiredField === 'No')
                RequiredField = false;

            Xml += '<Properties name = "' + FieldName + '" long = "' + FieldLength + '" type = "' + FieldType + '" required = "' + RequiredField + '" />';
        });

        Xml += '</DefinitionUsersProperties>\n\
                        </CrearEstructuraCatalogo>\n\
                    </NewCatalog>';

        return Xml;

    };
};

/*******************************************************************************
 * Devuelve el listado de Catalogos ordenados por empresa y repositorio (árbol)
 * @returns {undefined}
 */

ClassCatalogAdministrator.prototype.GetListCatalogos = function ()
{
    Loading();
    $('#WS_Catalogo').empty();
    $('#tree_catalogos').remove();
    $('#consola_catalogos_tree').append('<div id="tree_catalogos"></div>');
    $('#tree_catalogos').append('<ul><li id="Tree_Repository" class="folder expanded" data="icon: \'database.png\'">' + EnvironmentData.DataBaseName + '<ul id="Tree_Repository_"></ul></ul>');


    $.ajax({
        async: false,
        cache: false,
        dataType: "html",
        type: 'POST',
        url: "php/Catalog.php",
        data: {opcion: "GetListCatalogos"},
        success: function (xml) {
            $('#Loading').dialog('close');
            if ($.parseXML(xml) === null) {
                errorMessage(xml);
                return 0;
            } else
                xml = $.parseXML(xml);

            var cont = 0;
            var ArrayDirectories = new Array();
            var ArrayEmpresas = new Array();
            var ArrayRepositorios = new Array();
            var ArrayCatalogos = new Array();
            $(xml).find("Empresas").each(function ()
            {
                var $Empresas = $(this);
                var ClaveEmpresa = $Empresas.find("ClaveEmpresa").text();
                var NombreEmpresa = $Empresas.find("NombreEmpresa").text();
                var IdEmpresa = $Empresas.find("IdEmpresa").text();
                var IdRepositorio = $Empresas.find("IdRepositorio").text();
                var IdCatalogo = $Empresas.find("IdCatalogo").text();
                var NombreRepositorio = $Empresas.find("NombreRepositorio").text();
                var NombreCatalogo = $Empresas.find("NombreCatalogo").text();
                var Index = ArrayDirectories.indexOf(IdEmpresa);
                var IdexRepositorio = ArrayRepositorios.indexOf(IdRepositorio);
                var IndexCatalogo = ArrayCatalogos.indexOf(IdCatalogo);

                /* Comprobación para no repetir empresas */
                if (Index === (-1))
                {
                    ArrayDirectories[IdEmpresa] = IdEmpresa;
                    $('#Tree_Repository_').append('<li id="' + ClaveEmpresa + '" class="unselectable expanded folder" data="icon: \'enterprise.png\'">' + NombreEmpresa + '<ul id="' + ClaveEmpresa + '_clave"></ul>');
                }

                if (IdexRepositorio === (-1))
                {
                    ArrayRepositorios[IdRepositorio] = IdRepositorio;
                    $('#' + ClaveEmpresa + '_clave').append('<li id="' + NombreRepositorio + '" class="unselectable expanded folder" data="icon: \'Repositorio.png\'">' + NombreRepositorio + '<ul id="rep_' + IdRepositorio + '"></ul>');
                }

                //                   ArrayCatalogos[IndexCatalogo]=IndexCatalogo;
                $('#rep_' + IdRepositorio).append('<li id="' + IdCatalogo + '" class="unselectable expanded folder" data="icon: \'Catalogo.png\'">' + NombreCatalogo + '<ul id="' + IdCatalogo + '_catalogo"></ul>');
            });

            $(xml).find("Error").each(function ()
            {
                var $Instancias = $(this);
                var estado = $Instancias.find("Estado").text();
                var mensaje = $Instancias.find("Mensaje").text();
                errorMessage(mensaje);
            });

            $("#tree_catalogos").dynatree({onActivate: function (node) {
                    if (node.data.key > 0) /* Condición que solo cumplen los repositorios en este árbol */
                    {

                        $('#TableStructureCatalogos').remove();
                        $('#WS_Catalogo').empty();
                        $('#WS_Catalogo').append('<div class="titulo_ventana">Estructura de Catálogo</div>');
                        $('#WS_Catalogo').append('<table id="TableStructureCatalogos"  class="TablaPresentacion"><thead><tr><th>Nonbre del Campo</th><th>Tipo de Campo</th><th>Longitud</th><th>Requerido</th></tr></thead></table>');
                        /* SetTableStructura es una función localizada en Designer.js */
                        SetTableStructura(node.getParent().data.title + "_" + node.data.title, 'TableStructureCatalogos', 1);/* En el archivo de Configuración
                         *                                                          Los nombres de cada catálogo anteceden con NombreRepositorio_ */
                    }
                }});
        },
        beforeSend: function () {},
        error: function (jqXHR, textStatus, errorThrown) {
            $('#Loading').dialog('close');
            errorMessage(textStatus + "<br>" + errorThrown);
        }
    });

};

ClassCatalogAdministrator.prototype.GetCatalogRecordsInXml = function (repositoryName, CatalogName, CatalogType)
{
    var xml = undefined;

    $.ajax({
        async: false,
        cache: false,
        dataType: "html",
        type: 'POST',
        url: "php/Catalog.php",
        data: "opcion=GetCatalogRecordsInXml&CatalogType=" + CatalogType + '&CatalogName=' + CatalogName + '&repositoryName=' + repositoryName,
        success: function (respuesta) {
            if ($.parseXML(respuesta) === null) {
                errorMessage(respuesta);
                return 0;
            } else
                xml = $.parseXML(respuesta);

            $(xml).find("Error").each(function ()
            {
                var $Instancias = $(this);
                var estado = $Instancias.find("Estado").text();
                var mensaje = $Instancias.find("Mensaje").text();
                errorMessage(mensaje);
                xml = 0;
            });
        },
        beforeSend: function () {},
        error: function (objXMLHttpRequest) {
            errorMessage(objXMLHttpRequest);
        }
    });

    return xml;
};

ClassCatalogAdministrator.prototype.ViewCatalog = function ()
{
    $('#WS_Catalogo').empty();

    var content = $('<div>');
    var formGroup = $('<div>', {class: "form-group"});
    var enterpriseLabel = $('<label>', {}).append('Empresa');
    var enterpriseSelect = $('<select>', {class: "form-control", id: "SelectEmpresasAddCatalogo"})
        .append($('<option>', {value: 0}).append('Seleccione una Empresa...'));

    formGroup.append(enterpriseLabel).append(enterpriseSelect);
    content.append(formGroup);

    formGroup = $('<div>', {class: "form-group"});
    var repositoryLabel = $('<label>', {}).append('Repositorio');
    var repositorySelect = $('<select>', {class: "form-control", id: "SelectRepositoriosAddCatalogo"}).append($('<option>', {value: 0}).append('Esperando Empresa...'));

    formGroup.append(repositoryLabel).append(repositorySelect);
    content.append(formGroup);

    formGroup = $('<div>', {class: "form-group"});
    var catalogLabel = $('<label>', {}).append('Catálogo');
    var catalogSelect = $('<select>', {class: "form-control", id: "SelectCatalogosAddOption"})
        .append($('<option>').append('Seleccione una Empresa...'));

    formGroup.append(catalogLabel).append(catalogSelect);
    content.append(formGroup);

    $('#WS_Catalogo').append(content);

    var enterprise = new ClassEnterprise();
    var enterprises = enterprise.GetEnterprises();

    $(enterprises).find('Enterprise').each(function ()
    {
        var IdEnterprise = $(this).find('IdEmpresa').text();
        var EnterpriseKey = $(this).find('ClaveEmpresa').text();
        var EnterpriseName = $(this).find('NombreEmpresa').text();
        var option = $('<option>', {value: EnterpriseKey, id: IdEnterprise, EnterpriseKey: EnterpriseKey}).append(EnterpriseKey + ' (' + String(EnterpriseName).slice(0, 60) + ')');
        enterpriseSelect.append(option);
    });

    enterpriseSelect.change(function ()
    {

        _DeleteCatalogTable();
        var repositoryManager = new ClassRepository();
        var idEnterprise = enterpriseSelect.find('option:selected').attr('id');
        var EnterpriseKey = enterpriseSelect.find('option:selected').attr('EnterpriseKey');

        if (parseInt(idEnterprise) > 0) {
            var repositories = repositoryManager.GetRepositories(EnterpriseKey);
            repositorySelect.find('option').remove();
            repositorySelect.append("<option value='0'>Seleccione un Repositorio</option>");
            $(repositories).find('Repository').each(function () {
                var IdRepository = $(this).find('IdRepositorio').text();
                var RepositoryName = $(this).find('NombreRepositorio').text();
                var option = $('<option>', {value: IdRepository, id: IdRepository, name: RepositoryName}).append(RepositoryName);
                repositorySelect.append(option);
            });

            repositorySelect.change(function () {

                _DeleteCatalogTable();
                var IdRepositorio = repositorySelect.find('option:selected').attr('id');
                if (parseInt(IdRepositorio) > 0) {

                    _getCatalogos(IdRepositorio, 'SelectCatalogosAddOption');

                    /* Al elegir Catálogo se insertan los formularios para captura */
                    catalogSelect.change(function () {

                        _DeleteCatalogTable();

                        var Catalogo = $('#SelectCatalogosAddOption').val();
                        if (Catalogo === "0")
                        {
//                                 Advertencia("El catálogo seleccionado no contiene un nombre válido");
                            return 0;
                        }
                        var RepositoryName = $('#SelectRepositoriosAddCatalogo option:selected').html();
                        var CatalogName = $('#SelectCatalogosAddOption option:selected').html();

                        ClassCatalogAdministrator.CatalogName = CatalogName;

                        var CatalogXml = _GetCatalogRecordsInXml(RepositoryName, CatalogName, '');

                        if (CatalogXml !== undefined)
                            _BuildCatalogTable(CatalogXml, RepositoryName, IdRepositorio);
                    });
                } else
                {
                    $('#SelectCatalogosAddOption option').remove();
                    $('#SelectCatalogosAddOption').append('<option value="0">Esperando Empresa...</option>');
                    $('#AceptarAddCatalogOption').remove();
                }
            });
        } else
        {
            $('#SelectRepositoriosAddCatalogo option').remove();
            $('#SelectRepositoriosAddCatalogo').append('<option value="0">Esperando Empresa...</option>');
            $('#SelectCatalogosAddOption option').remove();
            $('#SelectCatalogosAddOption').append('<option value="0">Esperando Repositorio...</option>');
            $('#AceptarAddCatalogOption').remove();
        }

    });
};
ClassCatalogAdministrator.prototype.getCatalogName = function ()
{
    return ClassCatalogAdministrator.CatalogName;
};

ClassCatalogAdministrator.prototype.SetCatalogName = function (CatalogName)
{
    ClassCatalogAdministrator.CatalogName = CatalogName;
};

ClassCatalogAdministrator.prototype.getCatalogos = function (IdRepositorio, SelectCatalogos)
{

    $.ajax({
        async: false,
        cache: false,
        dataType: "html",
        type: 'POST',
        url: "php/ContentManagement.php",
        data: "opcion=getCatalogos&DataBaseName=" + EnvironmentData.DataBaseName + '&IdUsuario=' + EnvironmentData.IdUsuario + "&IdRepositorio=" + IdRepositorio,
        success: function (xml) {
            if ($.parseXML(xml) === null) {
                errorMessage(xml);
                return 0;
            } else
                xml = $.parseXML(xml);

            $("#" + SelectCatalogos + " option").remove();
            $("#" + SelectCatalogos).append("<option value = \"0\">Seleccione un Catálogo</option>");

            $(xml).find("Empresa").each(function ()
            {
                var $Empresa = $(this);
                var IdCatalogo = $Empresa.find("IdCatalogo").text();
                var NombreCatalogo = $Empresa.find("NombreCatalogo").text();
                $("#" + SelectCatalogos).append("<option value=\"" + NombreCatalogo + "\">" + NombreCatalogo + "</option>");
            });

            $(xml).find("Error").each(function ()
            {
                var $Instancias = $(this);
                var estado = $Instancias.find("Estado").text();
                var mensaje = $Instancias.find("Mensaje").text();
                errorMessage(mensaje);
            });
        },
        beforeSend: function () {},
        error: function (objXMLHttpRequest) {
            errorMessage(objXMLHttpRequest);
        }
    });
};