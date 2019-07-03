/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/* Refresh del árbol */
/* global EnvironmentData, BootstrapDialog, Process, WindowConfirmacion */

$(document).ready(function(){ 
  
});

var ContentArbol = function(){
    this.addNewDirectoryPanel = function(){
        var form = $('<input>',{type: "text", class: "form-control"});
   
        var div = $('<div>', {class: "form-group"});
        div.append("Nombre: ");
        div.append(form);
    
        BootstrapDialog.show({
            title: '<i class="fa fa-folder-open fa-lg"></i> Nuevo',
            message: div,
            closable: true,
            closeByBackdrop: true,
            closeByKeyboard: true,
            size: BootstrapDialog.SIZE_SMALL,
            buttons: [
                {
                    hotkey: 13,
                    icon: "fa fa-plus-circle fa-lg",
                    label: 'Agregar',
                    cssClass: "btn-primary",
                    action: function(dialogRef){      

                        var button = this;
                        var title = form.val();
                        dialogRef.setClosable(false);
                        dialogRef.enableButtons(false);

                        if(String(title).trim().length === 0)
                            return Advertencia("El nombre no puede quedar vacio");

                        button.spin();

                        var activeNode = $('#contentTree').dynatree('getActiveNode');
                        var node = activeNode.addChild({isFolder: true, title: title});

                        if(addNewDirectory(node))
                            dialogRef.close();
                        else{
                            button.stopSpin();
                            dialogRef.setClosable(true);
                            dialogRef.enableButtons(true);
                        }

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
                form.focus();
            }
        });
    };
    
    var addNewDirectory = function(node){
        var status = 0;
        var pathNode = node.getKeyPath();
        var NameDirectory = node.data.title;
        node.data.unselectable = true; 
        node.activate(true);
        node.focus(true);  

        $(".contentDetailTools").attr('disabled', 'disabled');

        var NombreRepositorio = $('#CM_select_repositorios option:selected').html();

       $.ajax({
          async:false, 
          cache:false,
          dataType:"html", 
          type: 'POST',   
          url: "php/Tree.php",
          data: "opcion=InsertDir&NombreRepositorio="+NombreRepositorio+"&NameDirectory="+NameDirectory+"&Path="+pathNode, 
          success:  function(xml){

               if($.parseXML( xml )===null){  alert(error); return 0;}else xml=$.parseXML( xml );

               $(xml).find("NewDirectory").each(function()
                {               
                    var $NewDirectory=$(this);
                    var id = $NewDirectory.find("IdNewDir").text();                               

                    node.data.key = id;
                    
                    status = 1;
                });

                $(xml).find("Error").each(function()
                {
                    var mensaje=$(this).find("Mensaje").text();
                    errorMessage(mensaje);
                    node.remove();
                });

          },
          error:function(objXMLHttpRequest){node.remove(); errorMessage(objXMLHttpRequest);}
        });
        
        return status;
    };
    
    this.ConfirmDeleteDir = function(node){
        var content = $('<div>');

        content.append('<p>¿Realmente desea eliminar el directorio <b>'+node.data.title+'</b>?</p>');

        BootstrapDialog.show({
            title: '<i class="fa fa-trash-o fa-lg"></i> Eliminar Directorio',
            message: content,
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
                    action: function(dialogRef){      
                        var button = this;
                        dialogRef.setClosable(false);
                        dialogRef.enableButtons(false);
                        button.spin();

                        CM_DeleteDir(node);

                        dialogRef.close();
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
    };
    /**
     *
     * @param node
     * @returns {number|void}
     * @constructor
     */
    var CM_DeleteDir = function(node) {
        console.log("CM_DeleteDir");
        var status = 1;

        var NameDirectory   = node.data.title;
        var Path            = node.getKeyPath();
        var IdDirectory     = node.data.key;
        var IdParent_       = node.getParent().data.key;

        if(!(parseInt(IdParent_) > 0))
            return errorMessage("No se puede realizar esta acción sobre este elemento.");

        var IdRepositorio = $('#CM_select_repositorios option:selected').attr('idrepository');
        var NombreRepositorio = $('#CM_select_repositorios option:selected').attr('repositoryname');
        var IdEmpresa = $('#CM_select_empresas option:selected').attr('id');
        var title = node.data.title;

        if(!parseInt(IdRepositorio) > 0)
            return Advertencia("No fue posible obtener el identificador del repositorio");

        if(!parseInt(IdEmpresa) > 0)
            return Advertencia("No fue posible obtener el identificador de la empresa");

        $.ajax({
            async: false,
            cache: false,
            dataType: "json",
            type: 'POST',
            url: "php/Tree.php",
            data: {
                opcion: "DeleteDir",
                idRepositorio: IdRepositorio,
                idDirectory: IdDirectory,
                dirName: node.data.title,
                nombreRepositorio: NombreRepositorio,
                idEmpresa: IdEmpresa
            },
            success:  function(response)
            {
                if (response.status) {
                    /* Se quita el directorio y se abre la barra de progreso */
                    $('.contentDetail').empty();
                    node.remove();
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
        
    if(!(parseInt(IdRepositorio) > 0))
        return Advertencia("El id del repositorio no es válido");;
        
    var nodes = getNodes(0, NombreRepositorio);

    _buildTree(nodes, NombreRepositorio);
    
    return status;
}

/**
 *
 * @param idDirectory
 * @param NombreRepositorio
 * @returns {Array}
 */
function getNodes(idDirectory, NombreRepositorio) {
    var nodes = [];

    $.ajax({
        async:false,
        cache:false,
        dataType:"json",
        type: 'POST',
        url: "php/Tree.php",
        data: {
            opcion: "getTree",
            NombreRepositorio: NombreRepositorio,
            idDirectory: idDirectory
        },
        success:  function(response)
        {
            nodes = response;
        },
        beforeSend:function(){},
        error: function(jqXHR, textStatus, errorThrown){
            errorMessage(textStatus +"<br>"+ errorThrown);
        }
    });

    return nodes;
}

    var _buildTree = function(tree, NombreRepositorio){
        if($('#TreeRefresh').length === 0)
            $('<li id = "TreeRefresh" class = "fa fa-refresh fa-lg"></li>')
                .css({"cursor": "pointer"})
                .insertBefore('#contentTree');

        var cont = 0;
        
        var emptyTest = $('#contentTree').is(':empty');
           
        if(!emptyTest) {
            $('#contentTree').dynatree("destroy");
            $('#contentTree').empty();
        }

        InitDynatree(tree, NombreRepositorio);

    };

function InitDynatree(children, NombreRepositorio)
{
    console.log(children);
    var isMac = /Mac/.test(navigator.platform);
    var arbol= $("#contentTree").dynatree(
        {
            generateIds: false,
            keyboard: true,
            expand: false,
            minExpandLevel: 1,
            clickFolderMode: 1,
            children: children,
            onActivate: function(node) {
                node.sortChildren(cmp, false);
                GetFiles(node.data.key);                    
//                if( event.shiftKey ){                   
//                  editNode(node);                    
//                  return false;
//                }
            },
            onClick: function(node, event){

            },
            onDblClick: function(node, event) {
              return false;
            },
            onKeydown: function(node, event) {
              switch( event.which ) {
              case 113: // [F2]
                editNode(node);
                return false;
              case 13: // [enter]
                if( isMac ){
                  editNode(node);
                  return false;
                }
              }
            },
            onLazyRead: function(dtnode){
                var newNodes = getNodes(dtnode.data.key, NombreRepositorio);

                dtnode.append(newNodes);

                dtnode.sortChildren(cmp, false);

                dtnode.setLazyNodeStatus(DTNodeStatus_Ok);
            }
        });
        
        $("#contentTree").dynatree("getTree").activateKey("1");
        var node =  $("#contentTree").dynatree("getActiveNode");
        if(node !== null){
            node.sortChildren(cmp, false);
            GetFiles(node.data.key); 
        }
        
        return arbol;
}

var cmp = function(a, b) {
    a = a.data.title.toLowerCase();
    b = b.data.title.toLowerCase();
    return a > b ? 1 : a < b ? -1 : 0;
};

function editNode(node){
  var prevTitle = node.data.title,  
    tree = node.tree;
    var IdParent=node.getParent();

    IdParent = IdParent.data.key;
    
    if(!(parseInt(IdParent) > 0))
        return errorMessage("No se puede editar el directorio raíz del repositorio.");
    
    // Disable dynatree mouse- and key handling
    tree.$widget.unbind();
    // Replace node with <input>
    $(".dynatree-title", node.span).html("<input id = 'editNode' onkeyup=\"ValidatingNodesOfTree(this)\" value='" + prevTitle + "'>");
  
  // Focus <input> and bind keyboard handler
  $("input#editNode")
    .select()
    .focus()
    .keydown(function(event){
      switch( event.which ) {
      case 27: // [esc]
        // discard changes on [esc]
        $("input#editNode").val(prevTitle);
        $(this).blur();        
        break;
      case 13: // [enter]
        // simulate blur to accept new value
        var title = $("input#editNode").val();
        CM_ModifyDir(node.data.key,title);
        $(this).blur();
        break;        
      }
    }).blur(function(event){
      // Accept new value, when user leaves <input>
      var title = $("input#editNode").val();      
      node.setTitle(title);
      CM_ModifyDir(node.data.key,title);
      
      // Re-enable mouse and keyboard handlling
      tree.$widget.bind();
      node.focus();            
      
    });    
}

function CM_ModifyDir(IdDirectory,NameDirectory)
{
    var IdRepositorio = $('#CM_select_repositorios option:selected').attr('idrepository');
    var NombreRepositorio = $('#CM_select_repositorios option:selected').attr('repositoryname');
    var IdEmpresa = $('#CM_select_empresas option:selected').attr('id');
    
    if(!parseInt(IdRepositorio) > 0)
        return Advertencia('No fue posible recuperar el identificador del repositorio');
    
    if(!parseInt(IdEmpresa) > 0)
        return Advertencia('No fue posible obtener el identificador de la empresa');
    
    ajax=objetoAjax();
    ajax.open("POST", 'php/Tree.php',true);
    ajax.setRequestHeader("Content-Type", "application/x-www-form-urlencoded;charset=utf-8;");
    ajax.send('opcion=ModifyDir&NombreRepositorio='+NombreRepositorio+"&NameDirectory="+NameDirectory+"&IdDirectory="+IdDirectory);
    ajax.onreadystatechange=function() 
    {
       if (ajax.readyState===4 && ajax.status===200) 
       {
          if(ajax.responseXML===null){errorMessage(ajax.responseText);return;}              
           var xml = ajax.responseXML;
            $(xml).find("Error").each(function()
            {
                var $Error=$(this);
                var estado=$Error.find("Estado").text();
                var mensaje=$Error.find("Mensaje").text();
                errorMessage(mensaje);
            });            
       }       
   };
}

function CancelDeleteDir(PathStatus,PathAdvancing)
{        
    $.ajax({
      async:true, 
      cache:false,
      dataType:"html", 
      type: 'POST',   
      url: "php/ServiceDeleteDirectory.php",
      data: "opcion=CancelAdvancing&PathStatus="+PathStatus+'&PathAdvancing='+PathAdvancing, 
      success:  function(xml){
          $('.loading').remove();
          ($.parseXML( xml )===null) ? Salida(xml) : xml=$.parseXML( xml );
          $('#DeletePathAdvancing').dialog('close');
           $(xml).find("CancelProgress").each(function()
            {               
                $('#DetailDeleteDir').empty();          
            });
            
            $(xml).find("Error").each(function()
            {
                var $Error=$(this);
                var estado=$Error.find("Estado").text();
                var mensaje=$Error.find("Mensaje").text();
                errorMessage(mensaje);
            });     
            
      },
      beforeSend:function(){},
      error:function(objXMLHttpRequest){errorMessage(objXMLHttpRequest);$('#DeletePathAdvancing').dialog('close');}
    });
}

var ClassTree = function()
{    
    this.GetSelectedNodes =  function(Tree)
    {       
        var ObjectTree = $(Tree).dynatree("getTree") ;
        if($.type(ObjectTree)!=='object')
            return 0;           
        
        var selected_folders = ObjectTree.getSelectedNodes();       
        
        return selected_folders;
    };    
    
    this.GetUncheckNodes = function(Tree)
    {
        var ObjectTree = $(Tree).dynatree("getTree") ;
        if($.type(ObjectTree)!=='object')
            return 0;    
        
        var nodeList = [];
        ObjectTree.visit(function(node){
            if( !node.bSelected ) {
                nodeList.push(node);
            }
        });
        return nodeList;
    };
};

/* Retorna la ruta de un directorio en el siguiente formato 
 * root/dir1/dir2/dir3/ActiveDir */

ClassTree.prototype.GetPath = function(IdTree)
{
    var node = $(IdTree).dynatree("getActiveNode");
    var Path = new Array();
    var KeyParent=node.getParent().data.key;
    var ParentName = node.getParent().data.title;
    Path[Path.length]=node.data.title;
    if($.type(ParentName)!=="null")
        Path[Path.length]=ParentName;
    var PathArchivo='';
    
        /* función recursiva para obtener el Path del nodo activo */
    while(KeyParent>0)
    {            
        KeyParent=$(IdTree).dynatree("getTree").getNodeByKey(KeyParent).getParent().data.key;            
        if(KeyParent>0)
        {
            var name=$(IdTree).dynatree("getTree").getNodeByKey(KeyParent).data.title;
            Path[Path.length]=name;
        }                        
    }
    
    for(var cont=(Path.length)-1; cont>=0; cont--) 
    {
        PathArchivo+=Path[cont]+"/";
    }
    
    return PathArchivo;
};

