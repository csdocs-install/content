/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

var Process = new Array();
var _NombreUsuario = '';
var NombreRol = '';

var CopyIdFile = 0; /* Id del Archivo que se va a copiar */
var CopyNombreArchivo = 0;
var CopyRutaArchivo = 0;

var CutIdFile = 0; /* Id del Archivo que se va a copiar */
var CutNombreArchivo = 0;
var CutRutaArchivo = 0;
var CutIdDirectory = 0;


var w_Width = $(window).width();
var w_Height = $(window).height();
var visor_Width = w_Width * .65;
var visor_Height = w_Height * 0.9;

var wWidth = $(window).width();
var dWidth = wWidth * .99;
var wHeight = $(window).height();
var dHeight = wHeight * 0.92;

var Wvisor = wWidth * .99;
var Hvisor = wHeight * .90;
var minWvisor = wWidth * .40;
var minHvisor = wHeight * .40;

var Wdetalle = wWidth * .50;
var Hdetalle = wHeight * .50;

var DTdHeight = dHeight - 260;

var Random = Math.floor(Math.random() * 1000) + 10;  /* Carga Masiva */

var ConsoleSettings = {minHeight: 500, minWidth: 800, width: 800, height: 500};

/* funciones  que devuelven la altura de los dialogs que muestran metadatos (Lista de formularios de manera vertical) */


function GetHeightDialogMetadatas()
{
    var HeightDialogMetadatas;
    
    if(w_Height>1000)
        HeightDialogMetadatas = 1000;
    else
        if(w_Height>=600)
            HeightDialogMetadatas = w_Height - 100;
    else
        if(w_Height<=600)
            HeightDialogMetadatas = w_Height-50;
    
    return HeightDialogMetadatas;
}

function GetWidthDialogMetadatas()
{
    var WidthDialogMetadas;

    if(w_Width>=2000)
        WidthDialogMetadas = 750;
    else
        if(w_Width>=400)
            WidthDialogMetadas = 400;
    else
        if(w_Width<=400)
            WidthDialogMetadas = w_Width;
    
    return WidthDialogMetadas;
}

function GetMinSize(Size)
{
    var min = Size * .4;
    return Size - min;
}

/* Dimensiones de la ventana de dialogo presentada para agregar un nuevo registro (Nueva Empresa, Usuario, Grupo) */
var HeightDialogNewRegister = GetHeightDialogMetadatas();
var WidthDialogMetadatas = GetWidthDialogMetadatas();

var DimensionsDialogMetadatas = { width: WidthDialogMetadatas, minWidth:GetMinSize(WidthDialogMetadatas), height:HeightDialogNewRegister, minHeight:GetMinSize(HeightDialogNewRegister), modal:true};

var LanguajeDataTable = {
    "sLengthMenu": "Mostrar _MENU_ registros",
    "sZeroRecords": "No se encontraron resultados",
    "sInfo": "Mostrados _START_ de _END_ de _TOTAL_ registro(s)",
    "sInfoEmpty": "Mostrados 0 de 0 of 0 registros",
    "sInfoFiltered": "(Filtrando desde _MAX_ total registros)",
    "sSearch": "Buscar"
};

var BotonesWindow = {"closable": true, // enable/disable close button
    "maximizable": true, // enable/disable maximize button
    "minimizable": true, // enable/disable minimize button
//        "collapsable" : true, // enable/disable collapse button
    "dblclick": "maximize", // set action on double click. false, 'maximize', 'minimize', 'collapse'
    //"titlebar" : "transparent", // false, 'none', 'transparent'
    "minimizeLocation": "left", // sets alignment of minimized dialogues
    "icons": {// jQuery UI icon class
        "close": "ui-icon-circle-close",
        "maximize": "ui-icon-circle-plus",
        "minimize": "ui-icon-circle-minus"
                //"collapse" : "ui-icon-triangle-1-s",
                //"restore" : "ui-icon-bullet"}
    }
};

var DataTable =
        {
            "sPaginationType": "full_numbers", "oLanguage":
                    {
                        "sLengthMenu": "Mostrar _MENU_ registros por página",
                        "sZeroRecords": "No se encontraron resultados",
                        "sInfo": "Mostrados _START_ de _END_ de _TOTAL_ registro(s)",
                        "sInfoEmpty": "Mostrados 0 de 0 of 0 registros",
                        "sInfoFiltered": "(Filtrando desde _MAX_ total registros)",
                        "oPaginate": {
                            "sLast": "Fin",
                            "sFirst": "Inicio",
                            "sNext": "Siguiente",
                            "sPrevious": "Atrás"
                        },
                        "sSearch": "Buscar:"
                    },
            "scrollY": DTdHeight,
            "scrollCollapse": true
        };

var OptionDataTable = {
    "sPaginationType": "full_numbers", "oLanguage":
            {
                "sLengthMenu": "Mostrar _MENU_ registros por página",
                "sZeroRecords": "No se encontraron resultados",
                "sInfo": "Mostrados _START_ de _END_ de _TOTAL_ registro(s)",
                "sInfoEmpty": "Mostrados 0 de 0 of 0 registros",
                "sInfoFiltered": "(Filtrando desde _MAX_ total registros)",
                "oPaginate": {
                    "sLast": "Fin",
                    "sFirst": "Inicio",
                    "sNext": "Siguiente",
                    "sPrevious": "Atrás"
                },
                "sSearch": "Buscar:"
            }
}

function objetoAjax() {
    var xmlhttp = false;
    try {
        xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
    } catch (e) {
        try {
            xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
        } catch (E) {
            xmlhttp = false;
        }
    }

    if (!xmlhttp && typeof XMLHttpRequest != 'undefined') {
        xmlhttp = new XMLHttpRequest();
    }
    return xmlhttp;
}

var GlobalDatePicker = {
//    defaultDate: "+1w",
    dateFormat: 'yy-mm-dd',
//    changeMonth: true,
//    numberOfMonths: 3,
//    isRTL: true,
//    onClose: function (selectedDate) {
//        
//    }
};

$.datepicker.regional['es'] = {
    closeText: 'Cerrar',
    prevText: '<Ant',
    nextText: 'Sig>',
    currentText: 'Hoy',
    monthNames: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'],
    monthNamesShort: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
    dayNames: ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'],
    dayNamesShort: ['Dom', 'Lun', 'Mar', 'Mié', 'Juv', 'Vie', 'Sáb'],
    dayNamesMin: ['Do', 'Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sá'],
    weekHeader: 'Sm',
    dateFormat: 'yy/mm/dd',
    firstDay: 1,
    isRTL: false,
    showMonthAfterYear: false,
    yearSuffix: ''
};

$.datepicker.setDefaults($.datepicker.regional['es']);


$.fn.dialogButtons = function (name, state) {
    var buttons = $(this).next('div').find('button');
    if (!name)
        return buttons;
    return buttons.each(function () {
        var text = $(this).text();
        if (text == name && state == 'disabled') {
            $(this).attr('disabled', true).addClass('ui-state-disabled');
            return this;
        }
        if (text == name && state == 'enabled') {
            $(this).attr('disabled', false).removeClass('ui-state-disabled');
            return this;
        }
        if (text == name) {
            return this;
        }
        if (name == 'disabled') {
            $(this).attr('disabled', true).addClass('ui-state-disabled');
            return buttons;
        }
        if (name == 'enabled') {
            $(this).attr('disabled', false).removeClass('ui-state-disabled');
            return buttons;
        }
    });
};




//Script para validar los campos de contraseña
var rw = {'special': /[\W]/g};

function ValidPassword(o) {
    o.value = o.value.replace(rw['special'], '');
}

var r = {'special': /[&\/\\#+~%*?<>{}]/g};
function valid(o) {
    o.value = o.value.replace(r['special'], '');
}

var ValidNode = {'special': /[&\/\\#,+~%*?<>{}]/g};
function ValidatingNodesOfTree(o)
{
    o.value = o.value.replace(ValidNode['special'], '');
}



function Notificacion(titulo, mensaje)
{
    $.gritter.add({
        // (string | mandatory) the heading of the notification
        title: titulo,
        // (string | mandatory) the text inside the notification
        text: mensaje,
        // (string | optional) the image to display on the left
//            image: 'http://a0.twimg.com/profile_images/59268975/jquery_avatar_bigger.png',
        // (bool | optional) if you want it to fade out on its own or just sit there
        sticky: false,
        // (int | optional) the time you want it to be alive for before fading out
        time: ''
    });

    return false;

}


function Trim(x) {
    return x.replace(/^\s+|\s+$/gm, '');
}


/* Returns:

 0:  exact match

-1:  string_a < string_b

 1:  string_b > string_b

 */
function strcmp(a, b) {
    a = a.toString(), b = b.toString();
    for (var i=0,n=Math.max(a.length, b.length); i<n && a.charAt(i) === b.charAt(i); ++i);
    if (i === n) return 0;
    return a.charAt(i) > b.charAt(i) ? -1 : 1;
}