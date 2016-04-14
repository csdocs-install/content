/* 
 * Copyright 2016 danielunag.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/* global BootstrapDialog, TemplateDesigner */


/**
 * @description Clase que se encarga de asociar los campos definidos en una plantilla
 * con los campos pertenecientes a la BD.
 * 
 * @returns {FieldsAssociator}
 */
var FieldsAssociator = function () {
    var enterpriseSelect;
    var repositorySelect;
    var repositorySelect;
    var enterpriseSelect;
    var templateSelect;
    var objectAssociated = [];   /*Array que contiene la estructura de campos a asociar */
    var templateName;
    var enterpriseKey;
    var repositoryName;

    this.setActionToLink = function () {
        $('.LinkFieldsAssociator').click(function () {
            _showInterface();
        });
    };

    /**
     * @description Muestra la interfaz que pide los datos necesarios para identificar la plantilla a asociar.
     * @returns {undefined}
     */
    var _showInterface = function () {
        objectAssociated = [];
        templateName = "";
        var content = $('<div>');
        var formGroup = $('<div>', {class: "form-group"});
        var enterpriseSelectInterface = $('<select>', {class: "form-control"});
        formGroup.append($('<label>').append('Empresa'))
                .append(enterpriseSelectInterface);
        content.append(formGroup);

        var repositorySelectInterface = $('<select>', {class: "form-control"});
        formGroup = $('<div>', {class: "form-group"}).append($('<label>').append('Repositorio'))
                .append(repositorySelectInterface);

        content.append(formGroup);

        var templateSelect = $('<select>', {class: "form-control"});
        formGroup = $('<div>', {class: "form-group"}).append($('<label>').append("Plantilla"))
                .append(templateSelect);

        content.append(formGroup);

        BootstrapDialog.show({
            title: '<i class = "fa fa-tag fa-lg"></i> Asociación de Campos',
            size: BootstrapDialog.SIZE_SMALL,
            closeByBackdrop: false,
            closeByKeyboard: true,
            message: content,
            buttons: [
                {
                    hotkey: 13,
                    label: 'Aceptar',
                    cssClass: "btn-primary",
                    action: function (dialogRef) {
                        var button = this;
                        button.spin();
                        dialogRef.enableButtons(false);
                        var templateForm = $(templateSelect).find('option:selected')[0];
                        console.log("Form template");
                        console.log(templateForm);
                        if (_showInterfaceOfAssociate(templateForm))
                            dialogRef.close();
                        else {
                            button.stopSpin();
                            dialogRef.enableButtons(true);
                        }
                    }
                },
                {
                    label: 'Cerrar',
                    action: function (dialogRef) {
                        dialogRef.close();
                    }
                }
            ],
            onshown: function (dialogRef) {
                enterpriseSelectInterface.append($('<option>').append("Seleccione una empresa"));
                _cleanSelectForm(repositorySelectInterface, "Esperando Empresa");
                templateSelect.append($('<option>').append('Esperando Repositorio'));

                var enterprise = new ClassEnterprise();
                var repository = new ClassRepository();
                var enterprises = enterprise.GetEnterprises();

                $(enterprises).find('Enterprise').each(function ()
                {
                    var idEnterprise = $(this).find('IdEmpresa').text();
                    var enterpriseKey = $(this).find('ClaveEmpresa').text();
                    var enterpriseName = $(this).find('NombreEmpresa').text();

                    var option = $('<option>', {
                        idEnterprise: idEnterprise,
                        enterpriseKey: enterpriseKey,
                        enterpriseName: enterpriseName
                    }).append(enterpriseName);

                    enterpriseSelectInterface.append(option);
                });

                $(enterpriseSelectInterface).unbind('change').change(function () {
                    var enterpriseOption = $(this).find('option:selected')[0];
                    var idEnterprise = $(enterpriseOption).attr('idEnterprise');
                    var enterpriseKey = $(enterpriseOption).attr('enterpriseKey');

                    if (parseInt(idEnterprise) > 0) {
                        repositorySelectInterface.find('option').remove();
                        repositorySelectInterface.append($('<option>').append('Seleccione repositorio'));
                        var repositories = repository.GetRepositories(enterpriseKey);

                        $(repositories).find('Repository').each(function () {
                            var idRepository = $(repositories).find('IdRepositorio').text();
                            var repositoryName = $(repositories).find('NombreRepositorio').text();

                            if (!parseInt(idRepository) > 0)
                                return _cleanSelectForm(templateSelect, "Esperando Repositorio");

                            var option = $('<option>', {
                                value: idRepository,
                                idRepository: idRepository,
                                repositoryName: repositoryName
                            }).append(repositoryName);

                            repositorySelectInterface.append(option);

                        });

                        $(repositorySelectInterface).unbind('change').change(function () {
                            var idRepositoryInterface = $(this).find('option:selected').attr('idrepository');
                            var templates = TemplateDesigner.getTemplates();
                            _setTemplates(templates, templateSelect, idRepositoryInterface);
                        });
                    } else {
                        _cleanSelectForm(repositorySelectInterface, "Esperando Empresa");
                        _cleanSelectForm(templateSelect, "Esperando Repositorio");
                    }

                });


            }
        });
    };

    /**
     * @description Limpia un formulario de tipo select.
     * @param {Object} select   Select a limpiar
     * @param {String} message Mensaje que se ingresa como opción seleccionada.
     * @returns {undefined}
     */
    var _cleanSelectForm = function (select, message) {
        $(select).find('option').remove();
        $(select).append($('<option>', {}).append(message));
    };

    /**
     * @description Ingresa los templates en el select  "PLantilla".
     * @param {XML} templates
     * @param {Object} templatesSelect
     * @param {Integer} idRepositoryInterface Id del repositorio seleccionado
     * @returns {Boolean}
     */
    var _setTemplates = function (templates, templatesSelect, idRepositoryInterface) {
        $(templatesSelect).find('option').remove();
        $(templates).find('template').each(function () {
            var idRepository = $(this).find('idRepository').text();
            var repositoryName = $(this).find('repositoryName').text();
            var enterpriseKey = $(this).find('enterpriseKey').text();

            $(this).find('templateList').each(function () {
                $(this).find('templateName').each(function () {
                    var templateName = $(this).text();
                    templateName = templateName.replace(/\.[^/.]+$/, "");

                    var option = $('<option>', {
                        "idRepository": idRepository,
                        "repositoryName": repositoryName,
                        "enterpriseKey": enterpriseKey,
                        "templateName": templateName
                    }).append(templateName);

                    if (String(idRepository) === String(idRepositoryInterface))
                        $(templatesSelect).append(option);

                });
            });


        });
        return 1;
    };

    /**
     * @description Muestra la interfaz para asociación de campos.
     * @param {object} enterpriseselect
     * @param {object} repositoryselect
     * @returns {undefined}
     */
    var _showInterfaceOfAssociate = function (templateselect) {
        console.log("Template");
        console.log(templateselect);

        if (!parseInt($(templateselect).attr('idrepository')) > 0)
            return Advertencia("No se pudo obtener el identificador del repositorio");

        templateName = $(templateselect).attr("templatename");
        enterpriseKey = $(templateselect).attr("enterprisekey");
        repositoryName = $(templateselect).attr("repositoryname");
        
        var content = $('<div>');
        var formGroup = $('<div>', {class: "form-group"});
        var systemFieldsSelect = $('<select>', {class: "form-control"});
        formGroup.append($('<label>').append('Campo Sistema'))
                .append(systemFieldsSelect);

        content.append(formGroup);
        formGroup = $('<div>', {class: "form-group"});

        var disassociatedFieldsSelect = $('<select>', {class: "form-control"});
        formGroup.append($('<label>').append('Campo Usuario'))
                .append(disassociatedFieldsSelect);

        content.append(formGroup);

        BootstrapDialog.show({
            title: '<i class = "fa fa-tag fa-lg"></i> Asociación de Campos',
            size: BootstrapDialog.SIZE_SMALL,
            closeByBackdrop: false,
            closeByKeyboard: true,
            message: content,
            buttons: [
                {
                    hotkey: 13,
                    icon: 'fa fa-link fa-lg',
                    label: 'Asociar',
                    cssClass: "btn-primary",
                    action: function (dialogRef) {
                       if(_associate(systemFieldsSelect, disassociatedFieldsSelect) === 0)
                           dialogRef.close();
                    }
                },
                {
                    icon: "fa fa-cogs fa-lg",
                    label: "Generar",
                    cssClass: "",
                    action: function(dialogRef){
                        var button = this;
                        button.spin();
                        dialogRef.setClosable(false);
                        dialogRef.enableButtons(false);
                        if(_createAssociation())
                            dialogRef.close();
                        else{
                            button.stopSpin();
                            dialogRef.setClosable(true);
                            dialogRef.enableButtons(true);
                        }
                    }
                },
                {
                    label: 'Cerrar',
                    action: function (dialogRef) {
                        dialogRef.close();
                    }
                }
            ],
            onshown: function (dialogRef) {
                var systemFields = _getSystemFields();
                console.log(systemFields);
                var fieldsDissasociated = _getFieldsDissasociated(repositoryName);
                console.log(fieldsDissasociated);

                for (var i = 0; i < systemFields.length; i++) {
                    var obj = systemFields[i];
                    for (var key in obj) {
                        var tableName = key;
                        var fields = obj[key];
                        for(var cont = 0; cont < fields.length; cont++){
                            var fieldObject = fields[cont];
                            var fieldTag = fieldObject.fieldTag;
                            var columnName = fieldObject.columnName;
                            
                            var option = $('<option>', {
                                columnName: columnName, 
                                fieldTag:fieldTag,
                                tableName: tableName
                            }).append(fieldTag);
                            
                            console.log(option);
                            
                            systemFieldsSelect.append(option);
                            
                        }
                        
                    }
                }

                $(fieldsDissasociated).find("Campo").each(function () {
                    var $Campo = $(this);
                    var tipo = $Campo.find("tipo").text();
                    var name = $Campo.find("name").text();
                    var type = $Campo.find("type").text();
                    var length = $Campo.find("long").text();
                    var required = $Campo.find("required").text();

                    var option = $('<option>', {fieldType: type, fieldName: name, fieldLength: length, required: required}).append(name);
                    disassociatedFieldsSelect.append(option);
                });
            }
        });
        
        return 1;
    };

    /**
     * @description Retorna los campos del sistema por asociar.
     * @returns {xml} Xml con el registro de campos del sistema.
     */
    var _getSystemFields = function () {
        var fields =
                [
                    {
                        "DocumentaryDisposition": [
                            {fieldName: "fondo", columnName: "Name", fieldTag: "Fondo", table: "DocumentaryDisposition"},
                            {fieldName: "section", columnName: "Name", fieldTag: "Sección", table: "DocumentaryDisposition"},
                            {fieldName: "Serie", columnName: "Name", fieldTag: "Serie", table: "DocumentaryDisposition"}
                        ]},
                    {
                        "DocumentValidity": [
                            {fieldName: "numExpedient", fieldTag: "Número de Expediente"},
                            {fieldName: "administrativo", columnName: "Administrativo", fieldTag: "Administrativo"},
                            {fieldName: "legal", columnName: "Legal", fieldTag: "Legal"},
                            {fieldName: "fiscal", columnName: "Fiscal", fieldTag: "Fiscal"},
                            {fieldName: "legal", columnName: "Legal", fieldTag: "Legal"},
                            {fieldName: "archivoTramite", columnName: "ArchivoTramite", fieldTag: "Archivo en Trámite"},
                            {fieldName: "archivoConcentracion", columnName: "ArchivoConcentracion", fieldTag: "Archivo Concentración"},
                            {fieldName: "archivoDesconcentracion", columnName: "ArchivoDesconcentracion", fieldTag: "Archivo Desconcentracion"},
                            {fieldName: "fundamentoLegal", columnName: "idLegalFoundation", fieldTag: "Fundamento Legal", tableRelation: "LegalFoundation"},
                            {fieldName: "eliminacion", columnName: "concentracion", fieldTag: "Cocentración"},
                            {fieldName: "concentracion", columnName: "Concentracion", fieldTag: "Concentración"},
                            {fieldName: "muestreo", columnName: "Muestreo", fieldTag: "Muestreo"},
                            {fieldName: "publica", columnName: "Publica", fieldTag: "Pública"},
                            {fieldName: "reservada", columnName: "reservada", fieldTag: "Reservada"},
                            {fieldName: "confidencial", columnName: "Confidencial", fieldTag: "Confidencial"},
                            {fieldName: "parcialmenteReservada", columnName: "ParcialmenteReservada", fieldTag: "Pacialmente Reservada"}
                        ]},
                    {
                        "repository": [
                            {fieldName: "fechaApertura", columnName: "FechaApertura", fieldTag: "Fecha Apertura", isRepository: true},
                            {fieldName: "fechaCierre", columnName: "FechaCierre", fieldTag: "Fecha Cierre Expediente", isRepository: true},
                            {fieldName: "alarmaPrimaria", columnName: "AlarmaPrimaria", fieldTag: "Alarma Primaria", isRepository: true},
                            {fieldName: "alarmaTransfSec", columnName: "AlarmaTransfSec", fieldTag: "Fecha Apertura", isRepository: true}
                        ]
                    }
                ];

        return fields;
    };

    /**
     * @description Obtiene los campos definidos en la plantilla.
     * @param {type} repositoryName
     * @returns {undefined}
     */
    var _getFieldsDissasociated = function (repositoryName) {
        console.log("Obteniendo campos del sistema..." + repositoryName);

        return  GeStructure(repositoryName);
    };

    /**
     * @description Mantiene en memoria un objeto con los campos por asociar.
     * @param {object} disassociatedFieldsSelect Objeto select con el listado de campos por asociar.
     * @param {object} systemFieldsSelect Objeto con el listado de campos del sistema.
     * @returns {Number}
     */
    var _associate = function (systemFieldsSelect, disassociatedFieldsSelect) {    
        systemFieldsSelect = $(systemFieldsSelect).find('option:selected')[0];
        disassociatedFieldsSelect = $(disassociatedFieldsSelect).find('option:selected')[0];
        
        var systemField =  
                {
                    columnName: $(systemFieldsSelect).attr('columnName'), 
                    fieldTag: $(systemFieldsSelect).attr('fieldTag'),
                    tableName: $(systemFieldsSelect).attr('tableName')
                };
                            
        var userField = 
                {
                    fieldType: $(disassociatedFieldsSelect).attr('fieldType'), 
                    fieldName: $(disassociatedFieldsSelect).attr('fieldName'), 
                    fieldLength: $(disassociatedFieldsSelect).attr('fieldLength'), 
                    required: $(disassociatedFieldsSelect).attr('required')
                };
        
        var fields = {"system": systemField, userField: userField};
        console.log("_associate");
        console.log(fields);
        objectAssociated.push(fields);
        
        $(systemFieldsSelect).remove();
        $(disassociatedFieldsSelect).remove();
        
        var systemFieldsSize = $(systemFieldsSelect).find('option[]').length;
        var userFieldsSize = $(disassociatedFieldsSelect).length;
        
        if(systemFieldsSize !== userFieldsSize)
            return 0;
        
        return 1;
    };
    
    /**
     * @description Genera un xml a partir de un objeto que se mantiene en memoria con los campos por asociar.
     * @returns {XML} Devuelve un String con el XML de los campos asociados.
     */
    var _createXml = function(){
        var fields = objectAssociated;
        console.log("creating xml");

        var xml = "<association version='1.0' encoding='UTF-8' templateName = '"+templateName+"' enterpriseKey = '" + enterpriseKey + "' repositoryName = '" + repositoryName + "'>";
        
            for(var cont = 0; cont < fields.length; cont++){
                console.log(fields[cont]);
                xml+= "<field>";
                    xml+= "<system>";
                        xml+= "<columnName>" + fields[cont].system.columnName + "</columnName>";
                        xml+= "<fieldTag>" + fields[cont].system.fieldTag + "</fieldTag>";
                        xml+= "<tableName>" + fields[cont].system.tableName + "</tableName>";
                    xml+= "</system>";
                    xml+= "<userField>";
                        xml+= "<fieldType>" + fields[cont].userField.fieldType + "</fieldType>";
                        xml+= "<fieldName>" + fields[cont].userField.fieldName + "</fieldName>";
                        xml+= "<fieldLength>" + fields[cont].userField.fieldLength + "</fieldLength>";
                        xml+= "<required>" + fields[cont].userField.required + "</required>";
                    xml+= "</userField>";
                xml+= "</field>";
            }
        
        xml+= "</association>";
        
        return xml;
    };
    
    /**
     * @returns {Boolean}
     */
    var _createAssociation = function(){
        var status = 0;
        if(objectAssociated.length === 0)
            return Advertencia("Debe asociar los campos del sistema con los campos de usuario.");
        
        var xml = _createXml();
//        console.log(xml);
        $.ajax({
            async: false,
            cache: false,
            dataType: "html",
            type: 'POST',
            url: "Modules/php/FieldsAssociator.php",
            data: {option: "createAssociation", xml: xml},
            success: function (xml) {
                if ($.parseXML(xml) === null)
                    return errorMessage(error);
                else
                    xml = $.parseXML(xml);
                
                $(xml).find('fieldsAssociated').each(function(){
                    var message = $(this).find('Mensaje').text();
                    Notificacion(message);
                    status = 1;
                });
                
                $(xml).find("Error").each(function (){
                    var mensaje = $(this).find("Mensaje").text();
                    errorMessage(mensaje);
                });

            },
            error: function(jqXHR, textStatus, errorThrown){errorMessage(textStatus +"<br>"+ errorThrown);}
        });
        return status;
    };
};