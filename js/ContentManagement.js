/**
 * @description Script que crea la interfaz principal del content management.
 * @type type
 */
/* global BotonesWindow, EnvironmentData, dHeight, dWidth, Enterprise, Repository */

var WindowContentManagement = {width: dWidth, height: dHeight, title: "CSDocs", minWidth: 800, minHeight: 800, closeOnEscape: false};

$(document).ready(function () {
    $('.LinkContainer').click(function () {
        var contentManagement = new ContentMnagement();
        contentManagement.buildContent();

        CleaningContent();

        $("#tabsContent a:first").tab("show");
        $("#tabsContent a:first").click();

        var enterprises = Enterprise.GetEnterprises();
        $("#CM_select_empresas option").remove();
        $("#CM_select_empresas").append("<option value='0'>Seleccione una Empresa</option>");
        $(enterprises).find('Enterprise').each(function ()
        {
            var IdEnterprise = $(this).find('IdEmpresa').text();
            var EnterpriseKey = $(this).find('ClaveEmpresa').text();
            var EnterpriseName = $(this).find('NombreEmpresa').text();
            $("#CM_select_empresas").append("<option value=\"" + EnterpriseKey + "\" id = \"" + IdEnterprise + "\">" + EnterpriseName + "</option>");
        });

    });

});

var ContentMnagement = function () {
    this.buildContent = function () {
        var content = $('<div>', {id: "content_management", class: "content_management"});

        var tabs = $('<div>', {}); /* Se le quito la clase */

        tabs.append('\
            <ul id = "tabsContent" class = "nav nav-tabs">\n\
                <li data-target = "#tabs-1" data-toggle = "tab" class="active">\n\
                    <a href="#tabs-1"><spam class = "fa fa-product-hunt fa-lg"></spam> Panel</a>\n\
                </li>\n\
                <li data-target = "#tabs-2" data-toggle = "tab">\n\
                    <a href="#tabs-2"><spam class = "fa fa-search fa-lg"></spam> Búsqueda</a>\n\
                </li>\n\
            </ul>');

        var contentTab = $('<div>', {id: "tabs-1"});
        contentTab.append('');

        var nav = $('<nav>', {class: "navbar navbar-custom", role: "navigation"});
        var divCollapsed = $('<div>', {class: "collapse navbar-collapse", id: "navbarContent"});
        var containerFuild = $('<div>', {class: "container-fluid"});
        var navbarHeader = $('<div>', {class: "navbar-header"});

        var buttonCollapsed = $('<button>', {
            type: "button",
            class: "navbar-toggle collapsed",
            "data-toggle": "collapse",
            "data-target": "#navbarContent",
            "aria-expanded": "false"
        }).append('\n\
                <span class="sr-only">Toggle navigation</span>\n\
                <span class="icon-bar"></span>\n\
                <span class="icon-bar"></span>\n\
                <span class="icon-bar"></span>');

        divCollapsed.append('<form class = "navbar-form navbar-left" role = "search">\n\
                                <div class = "form-group">\n\
                                    <i class="fa fa-building fa-lg" style = "color:#1b437d"></i>\n\
                                    <select id="CM_select_empresas" class="form-control">\n\
                                        <option>Cargando Empresas...</option>\n\
                                    </select>\n\
                                </div>\n\
                            </form>');

        divCollapsed.append('<form class = "navbar-form navbar-left" role = "search">\n\
                                <i class="fa fa-database fa-lg" style = "color:#1b437d"></i>\n\
                                <div class = "form-group">\n\
                                    <select id="CM_select_repositorios" class="form-control">\n\
                                        <option>Esperando Empresa...</option>\n\
                                    </select>\n\
                                </div>\n\
                            </form>');

        navbarHeader.append(buttonCollapsed);
        navbarHeader.append('<a class="navbar-brand" href="#"><i class="fa fa-archive fa-lg"></i></a>');
        containerFuild.append(navbarHeader);

        divCollapsed.append('\n\
        <ul class="nav navbar-nav">\n\
            <li class="dropdown">\n\
                <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">\n\
                    <i class="fa fa-folder-open fa-lg"></i> Directorios<span class="caret"></span>\n\
                </a>\n\
                <ul class="dropdown-menu">\n\
                    <li class = "CMNewDirectory"><a href="#"><i class="fa fa-plus-circle fa-lg"></i> Nuevo</span> </a></li>\n\
                    <li class = "CMModifyDirectory"><a href="#"><i class="fa fa-pencil-square fa-lg"></i> Editar</span> </a></li>\n\
                    <li class = "CMDeleteDirectory"><a href="#"><i class="fa fa-trash fa-lg"></i> Eliminar</span> </a></li>\n\
                </ul>\n\
            </li>\n\
            <li class="dropdown">\n\
                <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">\n\
                    <i class="fa fa-book fa-lg"></i> Documentos<span class="caret"></span>\n\
                </a>\n\
                <ul class="dropdown-menu">\n\
                    <li class = "CMCutFile"><a href="#"><i class="fa fa-scissors fa-lg"></i> Cortar</span> </a></li>\n\
                    <li class = "CMCopyFile"><a href="#"><i class="fa fa-files-o fa-lg"></i> Copiar</span> </a></li>\n\
                    <li class = "CMPasteFile"><a href="#"><i class="fa fa-clipboard fa-lg"></i> Pegar</span> </a></li>\n\
                    <li class = "CMEditFile"><a href="#"><i class="fa fa-pencil-square-o fa-lg"></i> Renombrar</span> </a></li>\n\
                    <li class = "CMDeleteFile"><a href="#"><i class="fa fa-trash-o fa-lg"></i> Eliminar</span> </a></li>\n\
                    <li role="separator" class="divider"></li>\n\
                    <li class = "CMMassiveUpload"><a href="#"><i class="fa fa-globe fa-lg"></i> Carga Masiva</span> </a></li>\n\
                    <li class = "CMUploadFile"><a href="#"><i class="fa fa-upload fa-lg"></i> Cargar Documento</span> </a></li>\n\
                </ul>\n\
            </li>\n\
        </ul>');

        containerFuild.append(divCollapsed);

        nav.append(containerFuild);

        var contentTabDiv = $('<div>', {class: "tab-content"});

        var contentTree = $('<div>', {
            id: "contentTreeStructure",
            class: "col-xs-3 col-sm-3 col-md-3 col-lg-3"
        }).css({"overflow": "auto", 'max-height': $(window).height() - 230}).append('<div id = "contentTree"></div>');

        var contentTab = $('<div>', {id: "tabs-1", class: "tab-pane"});

        contentTab.append(nav);

        contentTab.append(contentTree);
        contentTab.append('<div class = "contentDetail contentDocuments col-xs-9 col-sm-9 col-md-9 col-lg-9"></div>');

        contentTabDiv.append(contentTab);

        /*------------------------ Tab Búsqueda --------------------------- */

        nav = $('<nav>', {class: "navbar navbar-custom", role: "navigation"});
        divCollapsed = $('<div>', {class: "collapse navbar-collapse", id: "navbarSearcher"});
        containerFuild = $('<div>', {class: "container-fluid"});
        navbarHeader = $('<div>', {class: "navbar-header"});

        buttonCollapsed = $('<button>', {type: "button", class: "navbar-toggle collapsed",
            "data-toggle": "collapse", "data-target": "#navbarSearcher",
            "aria-expanded": "false"}).append('\
                <span class="sr-only">Toggle navigation</span>\n\
                <span class="icon-bar"></span>\n\
                <span class="icon-bar"></span>\n\
                <span class="icon-bar"></span>');

        navbarHeader.append(buttonCollapsed);
        navbarHeader.append('<a class="navbar-brand" href="#"><i class="fa fa-search fa-lg"></i></a>');
        containerFuild.append(navbarHeader);

        divCollapsed.append('<ul class="nav navbar-nav navbar-right">\n\
                                        <li id="userPage">\n\
                                            <a href="#"><i class="icon-user"></i> <input type = "checkbox" id="advanceSearch"> Búsqueda avanzada</a>\n\
                                        </li>\n\
                                    </ul>\n\
                                    <form class="navbar-form">\n\
                                        <div class="form-group" style="display:inline;">\n\
                                            <div class="input-group" style="display:table;">\n\
                                                <span class="input-group-addon" style="width:1%;"><span class="glyphicon glyphicon-search"></span></span>\n\
                                                <input class="form-control" id = "form_engine" placeholder="Realizar búsqueda" autocomplete="off" autofocus="autofocus" type="text">\n\
                                            </div>\n\
                                        </div>\n\
                                    </form>');

        containerFuild.append(divCollapsed);

        nav.append(containerFuild);


        var engineTab = $('<div>', {id: "tabs-2", class: "tab-pane"}).append(nav);
        engineTab.append('<div class="contentDetailEngine col-xs-12 col-sm-12 col-md-12 col-lg-12"></div>');
        engineTab.append('\
            <div id="advanceSearchContainer" class="col-xs-12 col-sm-12 col-md-12 col-lg-12"  style="display:none">\n\
                <div id="controlsAdvanceSearchContainer">\n\
                        <select id="searchAdvanceFields"></select>\n\
                </div>\n\
            </div>\n\
        ');

        contentTabDiv.append(engineTab);

        content.append(tabs);
        content.append(contentTabDiv);

        $('body').append(content);

        _initContentInterface();

    };

    var _initContentInterface = function () {
        var contentArbol = new ContentArbol();
        var advancedSearch = new AdvancedSearch();
        $('#content_management').dialog(WindowContentManagement, {close: function () {
                $(this).remove();
            }, resize: function (event, ui) {

            }
        }).dialogExtend(BotonesWindow);

        $('#CM_select_empresas').unbind('change').change(function () {
            CleaningContent();
            var EnterpriseKey = $('#CM_select_empresas').val();
            if (EnterpriseKey !== "0") {
                $("#CM_select_repositorios option").remove();
                $("#CM_select_repositorios").append("<option value='0'>Seleccione un Repositorio</option>");

                var repository = new ClassRepository();
                var repositories = repository.GetRepositories(EnterpriseKey);

                $(repositories).find('Repository').each(function () {
                    var IdRepository = $(this).find('IdRepositorio').text();
                    var RepositoryName = $(this).find('NombreRepositorio').text();
                    var option = $('<option>', {value: IdRepository, "repositoryName": RepositoryName, "idRepository": IdRepository}).append(RepositoryName);
                    $('#CM_select_repositorios').append(option);
                });
            } else {
                $('#CM_select_repositorios').empty().append("<option value=\"" + 0 + "\">Seleccione una Empresa</option>");

                CleaningContent();
            }
        });

        $('#CM_select_repositorios').unbind('change').change(function () {
            var IdRepositorio = $('#CM_select_repositorios option:selected').attr('idRepository');
            console.log(IdRepositorio);
            if (parseInt(IdRepositorio) > 0) {
                var permissions = new ClassPermissions();
                permissions.ApplyUserPermissions(IdRepositorio);
                CM_getTree();
            } else
                CleaningContent();
        });

        $('#form_engine').unbind('keydown').keydown(function (event) {
            if (event.which === 13)
                EngineSearch();
        });


        $('.CMModifyDirectory').unbind('click').click(function () {
            var node = $("#contentTree").dynatree("getActiveNode");

            if (node)
                editNode(node);
            else
                Advertencia("Seleccione un directorio");
        });

        $('.CMDeleteDirectory').unbind('click').click(function (){
            var node = $("#contentTree").dynatree("getActiveNode");
            if (node !== null)
                contentArbol.ConfirmDeleteDir(node);
            else
                Advertencia("Seleccione un directorio");
        });

        $('.CMCopyFile').unbind('click').click(function () {
            CopyFile();
        });

        $('.CMPasteFile').unbind('click').on('click', PasteFile);

        $('.CMCutFile').unbind('click').on('click', CutFile);

        $('.CMDeleteFile').unbind('click').on('click', deleteFileConfirmation);

        $('.CMMassiveUpload').unbind('click').click(function () {
            var tools = new ClassTools();
            tools.DisplayMassiveUploadDialog();
        });

        $('.CMEditFile').unbind('click').on('click', FileDedit);

        $('.CMUploadFile').unbind('click').on('click', CM_CargarArchivo); /* ContentManagemenet.js */

        $('.CMNewDirectory').unbind('click').click(function () {
            if ($('#contentTree').is(":empty"))
                return Advertencia("Debe consultar un repositorio y seleccionar un directorio para agregar uno nuevo.");

            var node = $("#contentTree").dynatree("getActiveNode");

            if (typeof node === 'object') {
                contentArbol.addNewDirectoryPanel();
            } else
                Advertencia("Seleccione un directorio");
        });

        $(document).on('click', '.navbar-collapse.in', function (e) {
            if ($(e.target).is('a')) {
                $(this).collapse('hide');
            }
        });

        $('#advanceSearch').unbind("click").on("click", function () {
             ($(this).is(':checked')) ?  advancedSearch.show() : advancedSearch.hide();
        });
        
        advancedSearch.init();

    };

};

function CleaningContent()
{
    /* Se limpia árbol y contenedor de archivos */
    $('#TreeRefresh').remove();
    var emptyTest = $('#contentTree').is(':empty');
    if (!emptyTest)
    {
        var node = $("#contentTree").dynatree("getActiveNode");
        if (node)
            $('#contentTree').dynatree("destroy");

        $('#contentTree').empty();
    }

    $('.contentDetail').empty();
}


