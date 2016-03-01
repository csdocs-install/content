/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/* Refresh del árbol */
/* global EnvironmentData, BootstrapDialog */

$(document).ready(function () {

});

var ContentArbol = function () {
    var activeNode = $('#contentTree').dynatree('getActiveNode');

    if (activeNode === null)
        return Advertencia("No fue posible obtener el nodo activo");

    if (String(activeNode) !== 'serie')
        return Advertencia("Solo puede agregar un legajo sobre una serie");

    var form = $('<input>', {type: "text", class: "form-control"});

    var div = $('<div>', {class: "form-group"});
    div.append("Nombre: ");
    div.append(form);

    this.addNewDirectoryPanel = function () {
        var dialog = BootstrapDialog.show({
            title: '<i class="fa fa-folder-open fa-lg"></i> Nuevo',
            message: div,
            closable: true,
            closeByBackdrop: true,
            closeByKeyboard: true,
            size: BootstrapDialog.SIZE_SMALL,
            buttons: [{
                    label: 'Cancelar',
                    action: function (dialogRef) {
                        dialogRef.close();
                    }
                }, {
                    hotkey: 13,
                    icon: "fa fa-plus-circle fa-lg",
                    label: 'Agregar',
                    cssClass: "btn-primary",
                    action: function (dialogRef) {

                        var button = this;
                        var title = form.val();
                        dialogRef.setClosable(false);
                        dialogRef.enableButtons(false);

                        if (String(title).trim().length === 0)
                            return Advertencia("El nombre no puede quedar vacio");

                        button.spin();

                        var node = activeNode.addChild({isFolder: true, title: title});

                        if (addNewDirectory(node))
                            dialogRef.close();
                        else {
                            button.stopSpin();
                            dialogRef.setClosable(true);
                            dialogRef.enableButtons(true);
                        }

                    }
                }],
            onshown: function (dialogRef) {
                form.focus();
            }
        });
    };

    var addNewDirectory = function (node) {
        var status = 0;
        var pathNode = node.getKeyPath();
        var NameDirectory = node.data.title;
        node.data.unselectable = true;
        node.activate(true);
        node.focus(true);

        $(".contentDetailTools").attr('disabled', 'disabled');

        var NombreRepositorio = $('#CM_select_repositorios option:selected').html();

        $.ajax({
            async: false,
            cache: false,
            dataType: "html",
            type: 'POST',
            url: "php/Tree.php",
            data: "opcion=InsertDir&NombreRepositorio=" + NombreRepositorio + "&NameDirectory=" + NameDirectory + "&Path=" + pathNode,
            success: function (xml) {

                if ($.parseXML(xml) === null) {
                    alert(error);
                    return 0;
                } else
                    xml = $.parseXML(xml);

                $(xml).find("NewDirectory").each(function ()
                {
                    var $NewDirectory = $(this);
                    var id = $NewDirectory.find("IdNewDir").text();

                    node.data.key = id;

                    status = 1;
                });

                $(xml).find("Error").each(function ()
                {
                    var mensaje = $(this).find("Mensaje").text();
                    errorMessage(mensaje);
                    node.remove();
                });

            },
            error: function (objXMLHttpRequest) {
                node.remove();
                errorMessage(objXMLHttpRequest);
            }
        });

        return status;
    };
};

/*******************************************************************************
 * 
 *  Obtiene el Árbol de directorios de un repositorio
 *  
 * @returns {undefined}
 */
function CM_getTree()
{
    var status = 1;
    var IdRepositorio = $('#CM_select_repositorios option:selected').attr('idRepository');
    var NombreRepositorio = $('#CM_select_repositorios option:selected').attr('repositoryName');

    if (!(parseInt(IdRepositorio) > 0))
        return Advertencia("El id del repositorio no es válido");
    ;

    $.ajax({
        async: false,
        cache: false,
        dataType: "html",
        type: 'POST',
        url: "php/Tree.php",
        data: "opcion=getTree" + '&NombreRepositorio=' + NombreRepositorio,
        success: function (xml)
        {
            if ($.parseXML(xml) === null)
                return Salida(xml);
            else
                xml = $.parseXML(xml);

            if ($(xml).find("Directory").length > 0)
                _buildTree(xml);

            $(xml).find("Error").each(function ()
            {
                var mensaje = $(this).find("Mensaje").text();
                errorMessage(mensaje);
            });

        },
        beforeSend: function () {},
        error: function (jqXHR, textStatus, errorThrown) {
            errorMessage(textStatus + "<br>" + errorThrown);
        }
    });

    return status;
}

var _buildTree = function (tree) {
    if ($('#TreeRefresh').length === 0)
        $('<li id = "TreeRefresh" class = "fa fa-refresh fa-lg"></li>')
                .css({"cursor": "pointer"})
                .insertBefore('#contentTree');

    var cont = 0;

    var emptyTest = $('#contentTree').is(':empty');

    if (!emptyTest) {
        $('#contentTree').dynatree("destroy");
        $('#contentTree').empty();
    }

    $(tree).find("Directory").each(function () {
        var $Directory = $(this);
        var id = $Directory.find("IdDirectory").text();
        var title = $Directory.find("title").text();
        var idParent = $Directory.find("parent_id").text();
        var catalogkey = $Directory.find("catalogKey").text();
        var parentCatalogKey = $Directory.find("parentCatalogKey").text();
        var type = $Directory.find("catalogType").text();

        if (String(catalogkey).length === 0)
            catalogkey = null;
        
        if(String(parentCatalogKey).length === 0)
            parentCatalogKey = null;

        var child = {
            title: title,
            idParent: idParent,
            key: id,
            isFolder: true,
            catalogkey: catalogkey,
            parentCatalogKey: parentCatalogKey,
            type: type
        };

        if (cont === 0)
            InitDynatree(child);
        else {
            var parent = $("#contentTree").dynatree('getTree').getNodeByKey(idParent);
            if (parent !== null)
                parent.addChild(child);
        }

        cont++;
    });

    $('#TreeRefresh').click(function () {
        if (!$(this).hasClass('fa-pulse')) {
            $(this).addClass('fa-pulse');
            CM_getTree();
            $(this).removeClass('fa-pulse');
        }
    });
};

function InitDynatree(child)
{

    var isMac = /Mac/.test(navigator.platform);
    var arbol = $("#contentTree").dynatree(
            {
                generateIds: false,
                keyboard: true,
                expand: true,
                minExpandLevel: 2,
                children: [child],
                onActivate: function (node) {
                    node.sortChildren(cmp, false);
                    GetFiles(node.data.key);
//                if( event.shiftKey ){                   
//                  editNode(node);                    
//                  return false;
//                }
                },
                onClick: function (node, event) {
                    console.log(node);
                },
                onDblClick: function (node, event) {
                    editNode(node);
                    return false;
                },
                onKeydown: function (node, event) {
                    switch (event.which) {
                        case 113: // [F2]
                            editNode(node);
                            return false;
                        case 13: // [enter]
                            if (isMac) {
                                editNode(node);
                                return false;
                            }
                    }
                }
            });

    $("#contentTree").dynatree("getTree").activateKey("1");
    var node = $("#contentTree").dynatree("getActiveNode");
    if (node !== null) {
        node.sortChildren(cmp, false);
        GetFiles(node.data.key);
    }

    return arbol;
}

var cmp = function (a, b) {
    a = a.data.title.toLowerCase();
    b = b.data.title.toLowerCase();
    return a > b ? 1 : a < b ? -1 : 0;
};

function editNode(node) {
    var prevTitle = node.data.title,
            tree = node.tree;
    var IdParent = node.getParent();

    IdParent = IdParent.data.key;

    if (!(parseInt(IdParent) > 0))
        return errorMessage("No se puede editar el directorio raíz del repositorio.");

    // Disable dynatree mouse- and key handling
    tree.$widget.unbind();
    // Replace node with <input>
    $(".dynatree-title", node.span).html("<input id = 'editNode' onkeyup=\"ValidatingNodesOfTree(this)\" value='" + prevTitle + "'>");

    // Focus <input> and bind keyboard handler
    $("input#editNode")
            .select()
            .focus()
            .keydown(function (event) {
                switch (event.which) {
                    case 27: // [esc]
                        // discard changes on [esc]
                        $("input#editNode").val(prevTitle);
                        $(this).blur();
                        break;
                    case 13: // [enter]
                        // simulate blur to accept new value
                        var title = $("input#editNode").val();
                        CM_ModifyDir(node.data.key, IdParent, title);
                        $(this).blur();
                        break;
                }
            }).blur(function (event) {
        // Accept new value, when user leaves <input>
        var title = $("input#editNode").val();
        node.setTitle(title);
        CM_ModifyDir(node.data.key, IdParent, title);

        // Re-enable mouse and keyboard handlling
        tree.$widget.bind();
        node.focus();

    });
}

function CM_ModifyDir(IdDirectory, IdParentDirectory, NameDirectory)
{
    var IdRepositorio = $('#CM_select_repositorios').val();
    var NombreRepositorio = $('#CM_select_repositorios option:selected').html();
    var IdEmpresa = $('#CM_select_empresas option:selected').attr('id');
    IdEmpresa = parseInt(IdEmpresa);

    ajax = objetoAjax();
    ajax.open("POST", 'php/Tree.php', true);
    ajax.setRequestHeader("Content-Type", "application/x-www-form-urlencoded;charset=utf-8;");
    ajax.send("opcion=ModifyDir&IdRepositorio=" + IdRepositorio + "&DataBaseName=" + EnvironmentData.DataBaseName + '&NombreRepositorio=' + NombreRepositorio + "&IdParentDirectory=" + IdParentDirectory + "&NameDirectory=" + NameDirectory + "&$IdEmpresa=" + IdEmpresa + "&IdDirectory=" + IdDirectory + '&nombre_usuario=' + EnvironmentData.NombreUsuario + '&id_usuario=' + EnvironmentData.IdUsuario);
    ajax.onreadystatechange = function ()
    {
        if (ajax.readyState === 4 && ajax.status === 200)
        {
            if (ajax.responseXML === null) {
                errorMessage(ajax.responseText);
                return;
            }
            var xml = ajax.responseXML;
            $(xml).find("Error").each(function ()
            {
                var $Error = $(this);
                var estado = $Error.find("Estado").text();
                var mensaje = $Error.find("Mensaje").text();
                errorMessage(mensaje);
            });
        }
    };
}

function ConfirmDeleteDir()
{
    var node = $("#contentTree").dynatree("getActiveNode");
    $('#MensajeConfirmacion').dialog(WindowConfirmacion, {buttons: {"Aceptar": function () {
                CM_DeleteDir();
            }, Cancelar: function () {
                $(this).dialog("close");
            }}});
    $('#MensajeConfirmacion').empty();
    $('#MensajeConfirmacion').append('<p>Está a punto de eliminar el directorio "' + node.data.title + '", ¿Desea Continuar?</p>');
}

function CM_DeleteDir()
{
    $('#MensajeConfirmacion').dialog("close");

    var node = $("#contentTree").dynatree("getActiveNode");
    if (!node) {
        errorMessage("Seleccione un Directorio");
        return;
    }
    var NameDirectory = node.data.title;
    var Path = node.getKeyPath();
    var IdDirectory = node.data.key;
    var IdParent_ = node.getParent().data.key;
    if (!(IdParent_ > 0)) {
        errorMessage("No se puede realizar esta acción sobre este elemento.");
        return;
    }
    var IdRepositorio = $('#CM_select_repositorios').val();
    var NombreRepositorio = $('#CM_select_repositorios option:selected').html();
    var IdEmpresa = $('#CM_select_empresas option:selected').attr('id');
    IdEmpresa = parseInt(IdEmpresa);
    var title = node.data.title;

    /* Se envia el listado de XML con cada uno de los Ids que seran eliminados (directorios) */
    var XMLResponse = "<Delete version='1.0' encoding='UTF-8'>";

    var Bodyxml = '';
    var Children = node.getChildren();
    var SubChildren = 0;


    if (Children !== null)
    {
        for (var cont = 0; cont < Children.length; cont++)
        {
            SubChildren = Children[cont].getChildren();
            if (SubChildren !== null)
            {
                for (var aux = 0; aux < SubChildren.length; aux++)
                {
                    Children[Children.length] = SubChildren[aux];
                }
            }

            var IdParent = Children[cont].getParent().data.key;
            if (!(IdParent) > 0) {
                IdParent = 0;
            }

            Bodyxml += '<Directory>\n\
                            <IdDirectory>' + Children[cont].data.key + '</IdDirectory>\n\
                            <IdParent>' + IdParent + '</IdParent>\n\
                            <title>' + Children[cont].data.title + '</title>\n\\n\
                            <Path>' + Children[cont].getKeyPath() + '</Path>\n\
                      </Directory>';
//            Cadena+="<p>Nombre=" + Children[cont].data.title + " Id="+Children[cont].data.key+"</p>";            
            SubChildren = null;
        }

    }

//    Salida(Cadena);
//    return;

    XMLResponse += Bodyxml + '</Delete>';

    ajax = objetoAjax();
    ajax.open("POST", 'php/Tree.php', true);
    ajax.setRequestHeader("Content-Type", "application/x-www-form-urlencoded;charset=utf-8;");
    ajax.send("opcion=DeleteDir&IdRepositorio=" + IdRepositorio + "&DataBaseName=" + EnvironmentData.DataBaseName + '&NombreRepositorio=' + NombreRepositorio + "&IdDirectory=" + IdDirectory + '&XMLResponse=' + XMLResponse + '&IdEmpresa=' + IdEmpresa + '&nombre_usuario=' + EnvironmentData.NombreUsuario + '&NameDirectory=' + NameDirectory + '&Path=' + Path + '&title=' + title + '&IdParent=' + IdParent_ + '&IdUsuario=' + EnvironmentData.IdUsuario);
    ajax.onreadystatechange = function ()
    {
        if (ajax.readyState === 4 && ajax.status === 200)
        {
            if (ajax.responseXML === null) {
                Salida(ajax.responseText);
                return;
            }
            var xml = ajax.responseXML;
            $(xml).find("DeleteDir").each(function ()
            {
                var $DeleteDir = $(this);
                var estado = $DeleteDir.find("Estado").text();
                var mensaje = $DeleteDir.find("Mensaje").text();
                var PathAdvancing = $DeleteDir.find("PathAdvancing").text();
                var PathStatus = $DeleteDir.find("PathStatus").text();
                var KeyProcess = $DeleteDir.find("KeyProcess").text();
                if (estado === "1")
                {
                    /* Se quita el directorio y se abre la barra de progreso */
                    $('.contentDetail').empty();
                    node.remove();
                    $('body').append('<div id ="' + KeyProcess + '"> <div id = "progress_' + KeyProcess + '"></div> <div id ="detail_' + KeyProcess + '"></div> </div>');
                    $('#detail_' + KeyProcess).append('<div class="loading"><img src="../img/loadinfologin.gif"></div>');
                    $('#' + KeyProcess).dialog({title: "Eliminando " + title, width: 350, height: 200, minWidth: 350, minHeight: 200,
                        buttons: {"Cancelar": {click: function () {
                                    CancelDeleteDir(PathStatus, PathAdvancing);
                                }, text: "Cancelar"}},
                    });
                    $('#' + KeyProcess).dialog({dialogClass: 'no-close'});
                    $('#progress_' + KeyProcess).progressbar({value: 0});
                    $('#detail_' + KeyProcess).append('<p>Obteniendo detalles de progreso</p>');

                    Process[KeyProcess] = setInterval("ProgressOfDeleting('" + PathAdvancing + "', '" + KeyProcess + "','" + title + "')", 2000);
                }
            });

            $(xml).find("Error").each(function ()
            {
                var $Error = $(this);
                var estado = $Error.find("Estado").text();
                var mensaje = $Error.find("Mensaje").text();
                errorMessage(mensaje);
            });
        }
    };
}
/* Muestra el progreso del proceso de borrado */
function ProgressOfDeleting(PathAdvancing, KeyProcess, title)
{
    $.ajax({
        async: true,
        cache: false,
        dataType: "html",
        type: 'POST',
        url: "php/ServiceDeleteDirectory.php",
        data: "opcion=CheckAdvancing&PathAdvancing=" + PathAdvancing + '&KeyProcess=' + KeyProcess,
        success: function (xml) {
            $('.loading').remove();
            ($.parseXML(xml) === null) ? $('#' + KeyProcess).dialog('close') : xml = $.parseXML(xml);
            $(xml).find("Progress").each(function ()
            {
                $('#detail_' + KeyProcess).empty();
                var $Advancing = $(this);
                var TotalDirectories = $Advancing.find("TotalDirectories").text();
                var TitleDirectory = $Advancing.find("TitleDirectory").text();
                var TitleFile = $Advancing.find("TitleFile").text();
                var NumberDirectory = $Advancing.find("NumberDirectory").text();

                var TotalProgress = (NumberDirectory / TotalDirectories) * 100;
                $('#detail_' + KeyProcess).append('<p>Eliminando ' + NumberDirectory + " de " + TotalDirectories + " directorios</p>");
                $('#detail_' + KeyProcess).append('<p>Procesando directorio: ' + TitleDirectory + "</p>");
                $('#detail_' + KeyProcess).append('<p>Documento : ' + TitleFile + "</p>");

                /* Avance de la barra de progreso */
                $('#progress_' + KeyProcess).progressbar({value: TotalProgress});
            });

            $(xml).find("NotFound").each(function ()
            {
                var $Advancing = $(this);
                var NotFound = $Advancing.find("NotFound").text();
                $('#' + KeyProcess).dialog('close');
                clearInterval(Process[KeyProcess]);
            });

            $(xml).find("Error").each(function ()
            {
                var $Error = $(this);
                var estado = $Error.find("Estado").text();
                var mensaje = $Error.find("Mensaje").text();
                $('#' + KeyProcess).dialog('close');
                errorMessage(mensaje);
                clearInterval(Process[KeyProcess]);
            });

            if ($(xml).find("Ok").length > 0)
            {
                $('#' + KeyProcess).dialog('close');
                clearInterval(Process[KeyProcess]);
                Notificacion("Borrado de directorios", "El usuario elimino el directorio: <br>" + title);
            }
        },
        beforeSend: function () {},
        error: function (objXMLHttpRequest) {
            errorMessage(objXMLHttpRequest);
            $('#DeletePathAdvancing').dialog('close');
            clearInterval(Process[KeyProcess]);
        }
    });
}

function CancelDeleteDir(PathStatus, PathAdvancing)
{
    $.ajax({
        async: true,
        cache: false,
        dataType: "html",
        type: 'POST',
        url: "php/ServiceDeleteDirectory.php",
        data: "opcion=CancelAdvancing&PathStatus=" + PathStatus + '&PathAdvancing=' + PathAdvancing,
        success: function (xml) {
            $('.loading').remove();
            ($.parseXML(xml) === null) ? Salida(xml) : xml = $.parseXML(xml);
            $('#DeletePathAdvancing').dialog('close');
            $(xml).find("CancelProgress").each(function ()
            {
                $('#DetailDeleteDir').empty();
            });

            $(xml).find("Error").each(function ()
            {
                var $Error = $(this);
                var estado = $Error.find("Estado").text();
                var mensaje = $Error.find("Mensaje").text();
                errorMessage(mensaje);
            });

        },
        beforeSend: function () {},
        error: function (objXMLHttpRequest) {
            errorMessage(objXMLHttpRequest);
            $('#DeletePathAdvancing').dialog('close');
        }
    });
}

var ClassTree = function ()
{
    this.GetSelectedNodes = function (Tree)
    {
        var ObjectTree = $(Tree).dynatree("getTree");
        if ($.type(ObjectTree) !== 'object')
            return 0;

        var selected_folders = ObjectTree.getSelectedNodes();

        return selected_folders;
    };

    this.GetUncheckNodes = function (Tree)
    {
        var ObjectTree = $(Tree).dynatree("getTree");
        if ($.type(ObjectTree) !== 'object')
            return 0;

        var nodeList = [];
        ObjectTree.visit(function (node) {
            if (!node.bSelected) {
                nodeList.push(node);
            }
        });
        return nodeList;
    };
};

/* Retorna la ruta de un directorio en el siguiente formato 
 * root/dir1/dir2/dir3/ActiveDir */

ClassTree.prototype.GetPath = function (IdTree)
{
    var node = $(IdTree).dynatree("getActiveNode");
    var Path = new Array();
    var KeyParent = node.getParent().data.key;
    var ParentName = node.getParent().data.title;
    Path[Path.length] = node.data.title;
    if ($.type(ParentName) !== "null")
        Path[Path.length] = ParentName;
    var PathArchivo = '';

    /* función recursiva para obtener el Path del nodo activo */
    while (KeyParent > 0)
    {
        KeyParent = $(IdTree).dynatree("getTree").getNodeByKey(KeyParent).getParent().data.key;
        if (KeyParent > 0)
        {
            var name = $(IdTree).dynatree("getTree").getNodeByKey(KeyParent).data.title;
            Path[Path.length] = name;
        }
    }

    for (var cont = (Path.length) - 1; cont >= 0; cont--)
    {
        PathArchivo += Path[cont] + "/";
    }

    return PathArchivo;
};

   