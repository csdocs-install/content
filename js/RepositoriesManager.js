/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


/* global EnvironmentData, BotonesWindow, LanguajeDataTable, ConsoleSettings, BootstrapDialog */
var RepositoryDetaildT, RepositoryDetailDT;
$(document).ready(function () {
    $('.LinkRepositories').click(function ()
    {
        if ($('#DivRepositoriesManager').is(':visible'))
            return 0;

        var classRepository = new ClassRepository();
        classRepository.BuildRepositoriesManager();

    });

});

var ClassRepository = function ()
{
    var self = this;
    self.IdRepositorio = undefined;
    self.NombreRepositorio = undefined;
    self.AutoincrementId = 0;
    var FormsNewRepositorydT;
    var FormsNewRepositoryDT;

    this.BuildRepositoriesManager = function ()
    {
        $('#DivRepositoriesManager').remove();
        $('body').append('\n\
            <div id="DivRepositoriesManager">\n\
            <div class="menu_lateral">\n\
                    <div id="accordion_repository">\n\
                        <div>\n\
                          <h3><a href="#">Repositorios</a></h3>\n\
                          <div id="consola_repository_tree">\n\
                              <table class="TableInsideAccordion">\n\
                              <tr class = "tr_RepositoryAdmin" title="Administracion">\n\
                                      <td><img src="img/RepositorioAgregar.png" ></td>\n\
                                      <td>Administrar</td>\n\
                                  </tr>\n\
                                  <tr class = "tr_NewRepository" title="Nuevo Repositorio">\n\
                                      <td><img src="img/RepositorioAgregar.png" ></td>\n\
                                      <td>Nuevo</td>\n\
                                  </tr>\n\
                                  <tr class = "tr_RepositoryDetail" title="Nuevo Repositorio">\n\
                                      <td><img src="img/RepositorioAgregar.png" ></td>\n\
                                      <td>Detalle</td>\n\
                                  </tr>\n\
                              </table>\n\
                          </div>\n\
                        </div>\n\
                    </div>\n\
            </div>\n\
            <div class="work_space" id="WS_Repository"></div>\n\
        </div>');

        $('.tr_NewRepository').on('click', self.NewRepository);

        $('.tr_RepositoryAdmin').click(function () {
            OptionAdministrator();
        });

        $('.tr_RepositoryDetail').on('click', CM_Repository);

        $("#accordion_repository").accordion({header: "h3", collapsible: true, heightStyle: "content"});
        $('#DivRepositoriesManager').dialog(ConsoleSettings, {title: "Consola de Repositorios", close: function () {
                $(this).remove();
            }}).dialogExtend(BotonesWindow);

        /********* Efectos sobre tabla dentro de acordeÃ³n ***********/
        $('#DivRepositoriesManager table').on('click', 'tr', function ()
        {
            var active = $('#DivRepositoriesManager table tr.TableInsideAccordionFocus');
            $('#DivRepositoriesManager table tr').removeClass('TableInsideAccordionFocus');
            $('#DivRepositoriesManager table tr').removeClass('TableInsideAccordionActive');
            $(active).addClass('TableInsideAccordionFocus');
            $(this).removeClass('TableInsideAccordionHoverWithoutClass');
            $(this).addClass('TableInsideAccordionActive');
        });
        $('#DivRepositoriesManager table tr').hover(function ()
        {
            if ($(this).hasClass('TableInsideAccordionActive') || $(this).hasClass('TableInsideAccordionFocus'))
                $(this).addClass('TableInsideAccordionHoverWithClass');
            else
                $(this).addClass('TableInsideAccordionHoverWithoutClass');
        });
        $('#DivRepositoriesManager table tr').mouseout(function ()
        {
            if ($(this).hasClass('TableInsideAccordionActive') || $(this).hasClass('TableInsideAccordionFocus'))
                $(this).removeClass('TableInsideAccordionHoverWithClass');
            else
                $(this).removeClass('TableInsideAccordionHoverWithoutClass');
        });

        $('#tr_NewUser').addClass('TableInsideAccordionActive');
        /* Fin de Efectos  */


        CM_Repository();

        $('.tr_RepositoryAdmin').click();

    };

    var OptionAdministrator = function ()
    {
        $('#WS_Repository').empty();

        var content = $('<div>');
        var formGroup = $('<div>', {class: "form-group"});
        var enterpriseLabel = $('<label>').append('Empresa');
        var enterpriseSelect = $('<select>', {class: "form-control required", id: "RMSelectEnterprises"});

        formGroup.append(enterpriseLabel);
        formGroup.append(enterpriseSelect);

        content.append(formGroup);

        var enterpriseOption = $('<option>', {value: "0"}).append("Seleccione una empresa");

        enterpriseSelect.append(enterpriseOption);

        formGroup = $('<div>', {class: "form-group"});
        var repositoryLabel = $('<label>').append("Repositorio");
        var repositorySelect = $('<select>', {class: "form-control required", id: "RMSelectRepositories"});

        formGroup.append(repositoryLabel);
        formGroup.append(repositorySelect);

        content.append(formGroup);

        var repositoryOption = $('<option>', {value: "0"}).append("Esperando Empresa");
        repositorySelect.append(repositoryOption);

        $('#WS_Repository').append(content);

        var buttons = {};

        $('#DivRepositoriesManager').dialog('option', 'buttons', buttons);

        var Enterprise = new ClassEnterprise();
        var Enterprises = Enterprise.GetEnterprises();

        $(Enterprises).find('Enterprise').each(function () {
            var $Empresa = $(this);
            var id = $Empresa.find("IdEmpresa").text();
            var nombre = $Empresa.find("NombreEmpresa").text();
            var ClaveEmpresa = $Empresa.find('ClaveEmpresa').text();

            var option = $('<option>', {value: ClaveEmpresa, id: id}).append(ClaveEmpresa + " (" + String(nombre).slice(0, 60) + " )");
            enterpriseSelect.append(option);
        });

        enterpriseSelect.change(function () {
            var idEnterpriseSelected = $(this).find('option:selected').attr('id');
            var EnterpriseKey = $(this).val();

            if (parseInt(idEnterpriseSelected) > 0) {
                var Repositories = self.GetRepositories(EnterpriseKey);

                /* Select con lista de repositorios de la empresa seleccionada */
                $('#RMSelectRepositories').empty().append('<option value = "0">Seleccione un repositorio...</option>');
                $('#DivRepositoryDetail').remove();

                if ($(Repositories).find('Repository').length === 0)
                    repositorySelect.empty().append($('<option>', {value: 0, id: 0}).append('No existen Repositorios'));
                else
                    repositorySelect.empty().append($('<option>', {value: 0, id: 0}).append('Seleccione un Repositorio'));

                $(Repositories).find('Repository').each(function () {
                    var idRepository = $(this).find('IdRepositorio').text();
                    var RepositoryName = $(this).find('NombreRepositorio').text();
                    var option = $('<option>', {value: idRepository, id: idRepository, name: RepositoryName}).append(RepositoryName);

                    repositorySelect.append(option);
                });

                /* Cuando el usuario seleccione un repositorio, se muestra su detalle del mismo */
                repositorySelect.change(function () {
                    var idRepository = $(this).find('option:selected').attr('id');

                    if (parseInt(idRepository) > 0) {
                        var repositoryName = $(this).find('option:selected').attr('name');
                        var repositoryStructure = GeStructure(repositoryName);

                        _BuildTableRepositoryDetail(repositoryStructure);
                    } else    /* Elimina la tabla con el detalle del repositorio previamente seleccionado */
                        $('#DivRepositoryDetail').remove();

                });
            } else
            {
                repositorySelect.empty().append($('<option>', {value: 0, id: 0}).append('Seleccione una empresa'));
                $('#DivRepositoryDetail').remove();
            }
        });
    };

    this.GetRepositories = function (EnterpriseKey)
    {
        var RepositoriesXml = 0;
        var data = {opcion: "GetListRepositories", EnterpriseKey: EnterpriseKey};
        $.ajax({
            async: false,
            cache: false,
            dataType: "html",
            type: 'POST',
            url: "php/Repository.php",
            data: data,
            success: function (xml)
            {
                if ($.parseXML(xml) === null) {
                    errorMessage(xml);
                    return 0;
                } else
                    xml = $.parseXML(xml);

                RepositoriesXml = xml;

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

        return RepositoriesXml;
    };
    
    this.getRepositoriesByCompany = function(){
        var repositories = null;
        $.ajax({
            async: false,
            cache: false,
            dataType: "xml",
            type: 'POST',
            url: "php/Repository.php",
            data: {opcion: "getRepositoriesByEnterprise"},
            success: function (response) {
                repositories = repositories;
            },
            beforeSend: function () {},
            error: function (jqXHR, textStatus, errorThrown) {
                errorMessage(textStatus + "<br>" + errorThrown);
            }
        });
        
        return repositories;
    }

    /* Interfaz dedicada a agregar un nuevo repositorio  
     * RM = RepositoriesManager*/
    this.NewRepository = function ()
    {
        var self = this;
        self.AutoincrementId = 0;
        var buttons = {"Generar Repositorio": {text: "Generar Repositorio", click: function () {
                    _BuildNewRepository();
                }}, "Limpiar": {text: "Limpiar", click: function () {
                    self.NewRepository();
                }}};
        $('#DivRepositoriesManager').dialog("option", "buttons", buttons);

        $('#WS_Repository').empty();

        var Enterprise = new ClassEnterprise();
        var enterprises = Enterprise.GetEnterprises();

        var content = $('<div>');
        content.append('<center><i class="fa fa-spinner fa-spin fa-lg"></i></center>');
        $('#WS_Repository').append(content);

        var formGroup = $('<div>', {class: "form-group"});
        var enterpriseLabel = $('<label>').append("Empresa");
        var enterpriseSelect = $('<select>', {class: "form-control required", id: "RMSelectEnterprises", FieldType: "varchar", FieldLength: "100"})
                .append($('<option>', {value: 0}).append("Seleccione una empresa"));

        formGroup.append(enterpriseLabel);
        formGroup.append(enterpriseSelect);

        content.append(formGroup);

        formGroup = $('<div>', {class: "form-group"});
        var repositoryLabel = $('<label>').append("Nombre del Repositorio");
        var repositoryForm = $('<input>', {type: "text", id: "RepositoryNameRM", class: "form-control required", FieldType: "varchar", FieldLength: "50"});

        formGroup.append(repositoryLabel);
        formGroup.append(repositoryForm);

        content.append(formGroup);

        if (typeof enterprises !== 'object')
            return Advertencia("No fue posible recuperar el listado de empresas");

        if ($(enterprises).find('Enterprise').length === 0)
            enterpriseSelect.empty().append($('<option>', {value: 0, id: 0}).append('No existen empresas'));
        else
            enterpriseSelect.empty().append($('<option>', {value: 0, id: 0}).append('Seleccione una empresa'));

        $(enterprises).find("Enterprise").each(function ()
        {
            var $Empresa = $(this);
            var id = $Empresa.find("IdEmpresa").text();
            var nombre = $Empresa.find("NombreEmpresa").text();
            var ClaveEmpresa = $Empresa.find('ClaveEmpresa').text();
            var option = $('<option>', {value: ClaveEmpresa, id: id}).append(ClaveEmpresa + " (" + String(nombre).slice(0, 60) + ")");
            enterpriseSelect.append(option);
        });

        $('#WS_Repository').append('<table id = "TableFieldsNewRepository" class = "display hover"><thead><tr><th>Campo</th><th>Tipo</th><th>Longitud</th><th>Requerido</th></tr></thead></table>');

        FormsNewRepositorydT = $('#TableFieldsNewRepository').dataTable(
                {
                    'bPaginate': false, 'bInfo': false, bFilter: false, "bSort": false,
                    "dom": 'lfTrtip',
                    "tableTools": {
                        "aButtons": [
                            {"sExtends": "text", "sButtonText": '<i class="fa fa-plus-circle fa-lg"></i> Agregar Campo', "fnClick": function () {
                                    _displayWindowNewField();
                                }},
                            {"sExtends": "text", "sButtonText": '<i class="fa fa-pencil fa-lg"></i> Editar Campo', "fnClick": function () {
                                    _EditNewRepositoryField();
                                }},
                            {"sExtends": "text", "sButtonText": '<i class="fa fa-trash fa-lg"></i> Eliminar', "fnClick": function () {
                                    _ConfirmDeleteNewRepositoryField();
                                }},
                            //                {"sExtends":"text", "sButtonText": "Agregar desde XML", "fnClick" :function(){_FormAddNewRepositoryXml();}},
                            {
                                "sExtends": "collection",
                                "sButtonText": '<i class="fa fa-floppy-o fa-lg"></i>',
                                "aButtons": ["csv", "xls", "pdf", "copy"]
                            }
                        ]
                    },
                    "autoWidth": false,
                    "oLanguage": LanguajeDataTable
                });

        $('div.DTTT_container').css({"margin-top": "1em"});
        $('div.DTTT_container').css({"float": "left"});

        FormsNewRepositoryDT = new $.fn.dataTable.Api('#TableFieldsNewRepository');
        $('#TableFieldsNewRepository tbody').on('click', 'tr', function ()
        {
            FormsNewRepositoryDT.$('tr.selected').removeClass('selected');
            $(this).addClass('selected');
        });

        $(content).find('.fa-spinner').remove();
    };

    var _displayWindowNewField = function ()
    {
        var fieldsManager = new FieldsManager();
        var dialogRef = fieldsManager.windowNewField(_validateNewField);
    };

    var _validateNewField = function (dialogRef)
    {
        var fieldsManager = new FieldsManager();
        var FieldsValues = fieldsManager.GetFieldsValues(FormsNewRepositorydT, FormsNewRepositoryDT);
        if (!$.isPlainObject(FieldsValues))
            return 0;

        dialogRef.close();

        self.AutoincrementId++;

        if (FieldsValues.RequiredField)
            FieldsValues.RequiredField = 'Si';
        else
            FieldsValues.RequiredField = 'No';

        var data = [FieldsValues.FieldName, FieldsValues.FieldType, FieldsValues.FieldLength, FieldsValues.RequiredField];

        var ai = FormsNewRepositoryDT.row.add(data).draw();
        var n = FormsNewRepositorydT.fnSettings().aoData[ ai[0] ].nTr;
        n.setAttribute('id', self.AutoincrementId);

        Notificacion('Campo ' + FieldsValues.FieldName + ' preparado');

        FormsNewRepositorydT.$('tr[id = ' + self.AutoincrementId + ']').click();

    };

    /* Genera el nuevo Repositorio (BotÃ³n 'Generar Repositorio' del dialog)*/
    var _BuildNewRepository = function ()
    {
        var FieldsValidator = new ClassFieldsValidator();
        var RegularExpression = /^([a-zA-Z0-9\_])+$/g;
        var EnterpriseKey = $('#RMSelectEnterprises').val();
        var RepositoryName = $('#RepositoryNameRM').val();
        var Forms = $('#WS_Repository input.FormStandart');

        $('#RMSelectEnterprises').tooltip();
        $('#RepositoryNameRM').tooltip();

        if (EnterpriseKey === 0 || EnterpriseKey === '0')
        {
            FieldsValidator.AddClassRequiredActive($('#RMSelectEnterprises'));
            return 0;
        } else
            FieldsValidator.RemoveClassRequiredActive($('#RMSelectEnterprises'));

        var validation = FieldsValidator.ValidateFields(Forms);
        if (validation === 0)
            return;

        if (!RegularExpression.test(RepositoryName)) {
            FieldsValidator.AddClassRequiredActive($('#RepositoryNameRM'));
            $('#RepositoryNameRM').attr('title', 'Nombre inválido');
            return 0;
        } else
            $('#RepositoryNameRM').attr('title', '');

        var ExistedRepository = 0;
        var Repositories = self.GetRepositories(EnterpriseKey);

        $(Repositories).find('Repository').each(function ()
        {
            if (RepositoryName === $(this).find('NombreRepositorio').text())
                ExistedRepository = 1;
        });

        if (ExistedRepository)
        {
            FieldsValidator.AddClassRequiredActive($('#RepositoryNameRM'));
            $('#RepositoryNameRM').attr('title', 'El repositorio ya existe');
            return 0;
        } else
        {
            $('#RepositoryNameRM').attr('title', '');
            FieldsValidator.RemoveClassRequiredActive($('#RepositoryNameRM'));
        }

        /* Se genera la misma estructura de xml de carga de repositorio a travÃ©s del attachment 'Nueva Instancia' */
        var Xml = "<NewRepository version='1.0' encoding='UTF-8'>\n\
                        <CrearEstructuraRepositorio DataBaseName = \"" + EnvironmentData.DataBaseName + "\" ClaveEmpresa = \"" + EnterpriseKey + "\">";
        Xml += "<NombreRepositorio>" + RepositoryName + "</NombreRepositorio>\n\
                            <DefinitionUsersProperties>";
        var Rows = FormsNewRepositoryDT.rows().data().each(function (value, index)
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

        if (Rows.length === 0)
        {
            Advertencia('Debe agregar por lo menos un campo en el nuevo repositorio');
            return;
        }

        Xml += '</DefinitionUsersProperties>\n\
                        </CrearEstructuraRepositorio>\n\
                    </NewRepository>';

        $('#WS_Repository').append('<div class="Loading" id = "IconWaitingNewRepository"><img src="../img/loadinfologin.gif"></div>');

        var data = {opcion: 'NewRepository', DataBaseName: EnvironmentData.DataBaseName, IdUser: EnvironmentData.IdUsuario, UserName: EnvironmentData.NombreUsuario, Xml: Xml};

        $.ajax({
            async: false,
            cache: false,
            dataType: "html",
            type: 'POST',
            url: "php/Repository.php",
            data: data,
            success: function (xml)
            {
                Salida(xml);

            },
            beforeSend: function () {},
            error: function (jqXHR, textStatus, errorThrown) {
                errorMessage(textStatus + "<br>" + errorThrown);
            }
        });


        $('#IconWaitingNewRepository').remove();

    };

    var _ConfirmDeleteNewRepositoryField = function ()
    {
        var IdField = $('#TableFieldsNewRepository tr.selected').attr('id');
        var FieldName = undefined;

        if (!(IdField > 0)) {
            Advertencia('Debe seleccionar al menos un campo');
            return 0;
        }

        $('#TableFieldsNewRepository tr.selected').each(function ()
        {
            var position = FormsNewRepositorydT.fnGetPosition(this); // getting the clicked row position
            FieldName = FormsNewRepositorydT.fnGetData(position)[0];
        });

        BootstrapDialog.show({
            title: '<i class="fa fa-exclamation-triangle fa-lg"></i> Eliminar Campo',
            type: BootstrapDialog.TYPE_DANGER,
            size: BootstrapDialog.SIZE_SMALL,
            message: '<p>El campo <b>' + FieldName + '</b> será removido del proceso de construcción del nuevo repositorio. ¿Desea Continuar?</p>',
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

                        if (_DeleteNewRepositoryField(IdField))
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

    var _DeleteNewRepositoryField = function (IdField)
    {
        FormsNewRepositoryDT.row('tr[id=' + IdField + ']').remove().draw(false);

        return 1;
    };

    var _EditNewRepositoryField = function ()
    {
        var IdField = $('#TableFieldsNewRepository tr.selected').attr('id');
        var FieldName, FieldType, FieldLength, RequiredField;

        if (!(parseInt(IdField) > 0))
            return Advertencia('Debe seleccionar al menos un campo');

        FormsNewRepositorydT.$('tr.selected').each(function () {
            var position = FormsNewRepositorydT.fnGetPosition(this); // getting the clicked row position
            FieldName = FormsNewRepositorydT.fnGetData(position)[0];
            FieldType = FormsNewRepositorydT.fnGetData(position)[1];
            FieldLength = FormsNewRepositorydT.fnGetData(position)[2];
            RequiredField = FormsNewRepositorydT.fnGetData(position)[3];
        });

        var fieldsManager = new FieldsManager();

        fieldsManager.windowNewField(function (dialogRef) {
            var FieldsValues = fieldsManager.GetFieldsValues(FormsNewRepositorydT, FormsNewRepositoryDT, 0);
            if (!$.isPlainObject(FieldsValues))
                return 0;

            dialogRef.close();

            if (FieldsValues.RequiredField)
                FieldsValues.RequiredField = 'Si';
            else
                FieldsValues.RequiredField = 'No';

            FormsNewRepositorydT.$('tr.selected').each(function ()
            {
                var position = FormsNewRepositorydT.fnGetPosition(this); // getting the clicked row position
                FormsNewRepositorydT.fnUpdate([FieldsValues.FieldName], position, 0, false);
                FormsNewRepositorydT.fnUpdate([FieldsValues.FieldType], position, 1, false);
                FormsNewRepositorydT.fnUpdate([FieldsValues.FieldLength], position, 2, false);
                FormsNewRepositorydT.fnUpdate([FieldsValues.RequiredField], position, 3, false);
            });

        }, function (dialogRef) {
            var buttonEdit = dialogRef.getButton('accept');
            $(buttonEdit).html('<i class="fa fa-pencil-square-o fa-lg"></i> Modificar');
            $(buttonEdit).removeClass('btn-primary').addClass('btn-warning');
            dialogRef.setType(BootstrapDialog.TYPE_WARNING);
            dialogRef.setTitle('<i class="fa fa-pencil-square-o fa-lg"></i> Editar Campo');

        },
                function (dialogRef) {
                    var fieldNameForm = dialogRef.getData('fieldName');
                    var fieldTypeForm = dialogRef.getData('fieldType');
                    var fieldLength = dialogRef.getData('fieldLength');
                    var fieldCheck = dialogRef.getData('requiredCheck');

                    $(fieldNameForm).val(FieldName);
                    $(fieldTypeForm).val(FieldType);
                    $(fieldLength).val(FieldLength);

                    if (String(RequiredField) === 'No')
                        $(fieldCheck).prop('checked', false);
                    else if (String(RequiredField) === 'Si')
                        $(fieldCheck).prop('checked', true);
                });

    };

    /* Tabla que muestra los campos y su detalle de los mismos pertenecientes a un repositorio */
    var _BuildTableRepositoryDetail = function (RepositoryDetail)
    {
        self.AutoincrementId = 0;
        $('#DivRepositoryDetail').remove();
        $('#WS_Repository').append('<div id = "DivRepositoryDetail"><table id = "TableRepositoryDetail" class = "display hover"></table></div>');
        $('#DivRepositoryDetail').append('<div class="Loading" id = "IconWaitingNewRepository"><img src="../img/loadinfologin.gif"></div>');
        $('#TableRepositoryDetail').append('<thead><tr><th>Campo</th><th>Tipo</th><th>Longitud</th><th>Requerido</th></tr></thead>');

        RepositoryDetaildT = $('#TableRepositoryDetail').dataTable(
                {
                    'bPaginate': false, 'bInfo': false, bFilter: false, "bSort": false, "autoWidth": false, "oLanguage": LanguajeDataTable, "dom": 'lfTrtip',
                    "tableTools": {
                        "aButtons": [
                            {"sExtends": "text", "sButtonText": '<i class="fa fa-plus-circle fa-lg"></i> Agregar Campo', "fnClick": function () {
                                    _addFieldToRepository();
                                }},
                            {"sExtends": "text", "sButtonText": '<i class="fa fa-trash fa-lg"></i> Campo', "fnClick": function () {
                                    _ConfirmDeleteRepositoryField();
                                }},
                            {"sExtends": "text", "sButtonText": '<i class="fa fa-exclamation-triangle"></i> Eliminar Repositorio', "fnClick": function () {
                                    _ConfirmDeleteRepository();
                                }},
                            {
                                "sExtends": "collection",
                                "sButtonText": '<i class="fa fa-floppy-o fa-lg"></i>',
                                "aButtons": ["csv", "xls", "pdf", "copy"]
                            }
                        ]
                    }
                });

        $('div.DTTT_container').css({"margin-top": "1em"});
        $('div.DTTT_container').css({"float": "left"});

        RepositoryDetailDT = new $.fn.dataTable.Api('#TableRepositoryDetail');

        $('#TableRepositoryDetail tbody').on('click', 'tr', function ()
        {
            RepositoryDetailDT.$('tr.selected').removeClass('selected');
            $(this).addClass('selected');
        });

        /* Llenado de la tabla con los campos del repositorio */
        $(RepositoryDetail).find('Campo').each(function ()
        {
            var FieldName = $(this).find('name').text();
            var FieldType = $(this).find('type').text();
            var FieldLength = $(this).find('long').text();
            var RequiredField = $(this).find('required').text();

            if (RequiredField === '' || RequiredField === 'false')
                RequiredField = "No";
            else
            if (RequiredField === 'true')
                RequiredField = "Si";

            var data = [FieldName, FieldType, FieldLength, RequiredField];

            var ai = RepositoryDetailDT.row.add(data).draw();
            var n = RepositoryDetaildT.fnSettings().aoData[ ai[0] ].nTr;
            n.setAttribute('id', self.AutoincrementId);

        });


        $('#IconWaitingNewRepository').remove();
    };

    var _ConfirmDeleteRepository = function ()
    {
        var RepositoryName = $('#RMSelectRepositories option:selected').attr('name');

        BootstrapDialog.show({
            title: '<i class="fa fa-exclamation-triangle fa-lg"></i> Mensaje de Confirmación',
            type: BootstrapDialog.TYPE_DANGER,
            size: BootstrapDialog.SIZE_SMALL,
            message: '<p>Realmente desea eliminar el repositorio <b>' + RepositoryName + '</b></p>',
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

                        if (_DeleteRepository())
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

    var _DeleteRepository = function ()
    {
        var status = 0;
        var IdRepository = $('#RMSelectRepositories option:selected').val();
        var RepositoryName = $('#RMSelectRepositories option:selected').html();
        var IdEnterprise = $('#RMSelectEnterprises option:selected').attr('id');

        var data = {opcion: 'DeleteRepository', DataBaseName: EnvironmentData.DataBaseName, IdUser: EnvironmentData.IdUsuario, UserName: EnvironmentData.NombreUsuario, IdRepository: IdRepository, RepositoryName: RepositoryName, IdEnterprise: IdEnterprise};

        $.ajax({
            async: false,
            cache: false,
            dataType: "html",
            type: 'POST',
            url: "php/Repository.php",
            data: data,
            success: function (xml)
            {
                if ($.parseXML(xml) === null) {
                    errorMessage(xml);
                    return 0;
                } else
                    xml = $.parseXML(xml);

                if ($(xml).find('DeletedRepository').length > 0)
                {
                    var Mensaje = $(xml).find('Mensaje').text();
                    Notificacion(Mensaje);

                    $("#RMSelectRepositories option:first").click();
                    $('#RMSelectRepositories option[value=' + IdRepository + ']').remove();
                    $('#DivRepositoryDetail').remove();
                    status = 1;
                }

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

        return status;
    };

    var _ConfirmDeleteRepositoryField = function ()
    {
        var IdField = $('#TableRepositoryDetail').find('tr.selected').length;
        var RepositoryName = $('#RMSelectRepositories option:selected').text();
        var FieldName = undefined;
        if (!(IdField > 0))
            return Advertencia('Debe seleccionar al menos un campo');

        $('#TableRepositoryDetail').find('tr.selected').each(function ()
        {
            var position = RepositoryDetaildT.fnGetPosition(this); // getting the clicked row position
            FieldName = RepositoryDetaildT.fnGetData(position)[0];
        });

        $('body').append('<div id = "DivConfirmDeleteRepositoryField"></div>');
        $('#DivConfirmDeleteRepositoryField').append('<p>¿Realmente desea eliminar el campo <b>' + FieldName + '</b> del repositorio  <b>' + RepositoryName + '</b>?. Este proceso no puede revertirse.</p>');
        $('#DivConfirmDeleteRepositoryField').dialog({title: "Mensaje de confirmación", width: 250, minWidth: 200, heigth: 250, minHeigth: 200, modal: true, buttons: {
                "Cancelar": function () {
                    $(this).remove();
                },
                "Aceptar": function () {
                    $(this).remove();
                    _DeleteRepositoryField();
                }
            }
        });
    };

    var _DeleteRepositoryField = function ()
    {
        var IdField = $('#TableRepositoryDetail').find('tr.selected').length;
        var FieldName = undefined;
        if (!(IdField > 0))
            return Advertencia('Debe seleccionar al menos un campo');

        var IdRepository = $('#RMSelectRepositories').val();
        var RepositoryName = $('#RMSelectRepositories option:selected').text();

        $('#TableRepositoryDetail').find('tr.selected').each(function ()
        {
            var position = RepositoryDetaildT.fnGetPosition(this); // getting the clicked row position
            FieldName = RepositoryDetaildT.fnGetData(position)[0];
        });

        var data = {opcion: "DeleteRepositoryField", DataBaseName: EnvironmentData.DataBaseName, IdRepository: IdRepository, RepositoryName: RepositoryName, IdUser: EnvironmentData.IdUsuario, UserName: EnvironmentData.NombreUsuario, FieldName: FieldName};

        $.ajax({
            async: false,
            cache: false,
            dataType: "html",
            type: 'POST',
            url: "php/Repository.php",
            data: data,
            success: function (xml)
            {
                if ($.parseXML(xml) === null) {
                    errorMessage(xml);
                    return 0;
                } else
                    xml = $.parseXML(xml);

                if ($(xml).find('DeletedField').length > 0)
                {
                    var Mensaje = $(xml).find('Mensaje').text();
                    Notificacion(Mensaje);
                    RepositoryDetailDT.row('tr.selected').remove().draw(false);
                }

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

    var _addFieldToRepository = function ()
    {

        var fieldsManager = new FieldsManager();
        fieldsManager.windowNewField(function (dialogRef) {


            var fieldsManager = new FieldsManager();
            var FieldsValues = fieldsManager.GetFieldsValues(RepositoryDetaildT, RepositoryDetailDT);
            if (!$.isPlainObject(FieldsValues))
                return 0;

            var RepositoryName = $('#RMSelectRepositories option:selected').text();

            var data = {opcion: "AddNewFieldToRepository", RepositoryName: RepositoryName, FieldName: FieldsValues.FieldName, FieldType: FieldsValues.FieldType, FieldLength: FieldsValues.FieldLength, RequiredField: FieldsValues.RequiredField};

            $.ajax({
                async: false,
                cache: false,
                dataType: "html",
                type: 'POST',
                url: "php/Repository.php",
                data: data,
                success: function (xml)
                {
                    if ($.parseXML(xml) === null) {
                        Salida(xml);
                        return 0;
                    } else
                        xml = $.parseXML(xml);

                    if ($(xml).find('AddedField').length > 0)
                    {
                        var Mensaje = $(xml).find('Mensaje').text();

                        Notificacion(Mensaje);

                        FieldsValues.RequiredField = FieldsValues.RequiredField.toString();
                        if (FieldsValues.RequiredField == "false")
                            FieldsValues.RequiredField = "No";
                        else
                            FieldsValues.RequiredField = "Si";

                        var data = [FieldsValues.FieldName, FieldsValues.FieldType, FieldsValues.FieldLength, FieldsValues.RequiredField];

                        var ai = RepositoryDetailDT.row.add(data).draw();
                        var n = RepositoryDetaildT.fnSettings().aoData[ ai[0] ].nTr;
                        dialogRef.close();
                    }

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

        });
    };

    /* Dialog que pide el xml para insertar el nuevo repositorio */
    var _FormAddNewRepositoryXml = function ()
    {
        $('#DivFormAddNewRepository').remove();
        $('body').append('<div id = "DivFormAddNewRepository"></div>');
        $('#DivFormAddNewRepository').append('<p>Seleccione el xml con la estructura del repositorio a insertar</p>');
        $('#DivFormAddNewRepository').append('<input type ="file" accept="text/xml" id="AddRepository_SelectFile">');
        $('#DivFormAddNewRepository').dialog({title: "Agregar Nuevo Repositorio", width: 350, minWidth: 150, heigth: 300, minHeigth: 150, modal: true, buttons: {
                "Cancelar": function () {},
                "Construir": function () {
                    _AddNewRepositoryXml();
                }}
        });
    };

    /* Toma un xml a través de un XML */
    var _AddNewRepositoryXml = function ()
    {
        var xml_usuario = document.getElementById("AddRepository_SelectFile");
        var archivo = xml_usuario.files;
        var data = new FormData();

        for (i = 0; i < archivo.length; i++)
        {
            data.append('archivo', archivo[i]);
            data.append('opcion', 'XMLInsertRepositorio');
            data.append('IdUser', EnvironmentData.IdUsuario);
            data.append('DataBaseName', EnvironmentData.DataBaseName);
            data.append('UserName', EnvironmentData.NombreUsuario);
        }

        $('#DivFormAddNewRepository').append('<div class="Loading" id = "IconWaitingNewRepository"><img src="../img/loadinfologin.gif"></div>');

        $.ajax({
            async: false,
            cache: false,
            processData: false,
            contentType: false,
            dataType: "html",
            type: 'POST',
            url: "php/Repository.php",
            data: data,
            success: function (xml) {

                $('#DivFormAddNewRepository').remove();
                Salida(xml);
            },
            beforeSend: function () {},
            error: function (jqXHR, textStatus, errorThrown) {
                errorMessage(textStatus + "<br>" + errorThrown);
            }
        });

    };

    /*******************************************************************************
     * Muestra el Arbol de Empresas y Repositorios
     * @returns {undefined}
     */
    var CM_Repository = function ()
    {
        var buttons = {};
        $('#DivRepositoriesManager').dialog("option", "buttons", buttons); /* Se eliminan los botones de la ventana de dialogo */

        $('#WS_Repository').empty();
        $('#WS_Repository').append('<div class="titulo_ventana">Estructura de Repositorio</div>');
        $('#tree_repository').remove();
        $('#consola_repository_tree').append('<div id="tree_repository"></div>');
        $('#tree_repository').append('<ul><li id="Tree_Repository" class="folder expanded " data="icon: \'database.png\'">' + EnvironmentData.DataBaseName + '<ul id="Tree_Repository_"></ul></ul>');

        $.ajax({
            async: false,
            cache: false,
            dataType: "html",
            type: 'POST',
            url: 'php/Tree.php',
            data: 'opcion=GetListReposity',
            success: function (xml)
            {
                if ($.parseXML(xml) === null)
                    return errorMessage(xml);
                else
                    xml = $.parseXML(xml);

                var ArrayDirectories = new Array();
                var cont = 0;
                $(xml).find("Empresas").each(function ()
                {
                    var $Empresas = $(this);
                    var ClaveEmpresa = $Empresas.find("ClaveEmpresa").text();
                    var NombreEmpresa = $Empresas.find("NombreEmpresa").text();
                    var IdEmpresa = $Empresas.find("IdEmpresa").text();
                    var Index = ArrayDirectories.indexOf(IdEmpresa);
                    /* Comprobaciónn para no repetir empresas */
                    if (Index == (-1))
                    {
                        ArrayDirectories[IdEmpresa] = IdEmpresa;
                        $('#Tree_Repository_').append('<li id="' + ClaveEmpresa + '" class="unselectable expanded folder" data="icon: \'enterprise.png\'">' + NombreEmpresa + '<ul id="' + ClaveEmpresa + '_"></ul>');
                    }
                });

                $(xml).find("Repositorios").each(function ()
                {
                    var $Repositorios = $(this);
                    var ClaveEmpresa = $Repositorios.find("EmpresaClaveEmpresa").text();
                    var NombreRepositorio = $Repositorios.find("NombreRepositorio").text();
                    var IdRepositorio = $Repositorios.find("IdRepositorio").text();
                    //                  $('#'+ClaveEmpresa+'_').append('<li id="'+IdRepositorio+'" class="folder">'+NombreRepositorio+'<ul id="'+IdRepositorio+'_"></ul>');
                    $('#' + ClaveEmpresa + '_').append('<li id="' + IdRepositorio + '" data="icon: \'Repositorio.png\'">' + NombreRepositorio + '<ul id="' + IdRepositorio + '_"></ul>');
                });

                /*************** Al Activar un Nodo del Arbol ************
                 * ****** Se muestra la tabla con los campos de la estructura******/

                $("#tree_repository").dynatree({onActivate: function (node) {
                        if (node.data.key > 0) {    /* CondiciÃƒÂ³n que solo cumplen los repositorios en este ÃƒÂ¡rbol */
                            var buttons = {};
                            $('#DivRepositoriesManager').dialog("option", "buttons", buttons); /* Se eliminan los botones de la ventana de dialogo */
                            $('#TableStructureRepositorios').remove();
                            $('#WS_Repository').empty();
                            $('#WS_Repository').append('<div class="titulo_ventana">Estructura de Repositorio</div>');
                            $('#WS_Repository').append('<table id="TableStructureRepositorios" class="TablaPresentacion"><thead><tr><th>Nonbre del Campo</th><th>Tipo de Campo</th><th>Longitud</th><th>Requerido</th></tr></thead></table>');
                            /* SetTableStructura es una funciÃƒÂ³n localizada en Designer.js */
                            SetTableStructura(node.data.title, 'TableStructureRepositorios', 1);
                        }

                    }});

                $(xml).find("Error").each(function () {
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

};


