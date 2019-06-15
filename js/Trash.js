/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


/* global Repository, Enterprise, EnvironmentData */

$(document).ready(function()
{    
    $('.LinkTrash').click(function()
    {              
        $('#TrashDir_select_repositorios').empty().append("<option value=\""+0+"\">Seleccione una Empresa</option>");
        $('#TrashFiles_select_repositorios').empty().append("<option value=\""+0+"\">Seleccione una Empresa</option>");
        $('.DetailTrashDir').empty();
        $('.DetailTrashFiles').empty();
        OpenTrah();
        
        var enterprises = Enterprise.GetEnterprises();
       $("#TrashDir_select_empresas option").remove();
       $("#TrashDir_select_empresas").append("<option value='0'>Seleccione una Empresa</option>");
       $("#TrashFiles_select_empresas option").remove();
       $("#TrashFiles_select_empresas").append("<option value='0'>Seleccione una Empresa</option>");
       $(enterprises).find('Enterprise').each(function()
        {
            var IdEnterprise = $(this).find('IdEmpresa').text();
            var EnterpriseKey = $(this).find('ClaveEmpresa').text();
            var EnterpriseName = $(this).find('NombreEmpresa').text();
           $("#TrashFiles_select_empresas").append("<option value=\""+EnterpriseKey+"\" id = \""+IdEnterprise+"\">"+EnterpriseName+"</option>");
           $("#TrashDir_select_empresas").append("<option value=\""+EnterpriseKey+"\" id = \""+IdEnterprise+"\">"+EnterpriseName+"</option>");
        });
                    
    });
    
                /*  Validación de Selects */
                
    $('#TrashDir_select_empresas').change(function()
    {
        var IdEmpresa = $('#TrashDir_select_empresas option:selected').attr('id');
        var EnterpriseKey = $('#TrashDir_select_empresas').val();
        IdEmpresa = parseInt(IdEmpresa);
        
        if(IdEmpresa>0)
        {
            var repositories = Repository.GetRepositories(EnterpriseKey);
            $("#TrashDir_select_repositorios option").remove();
            $("#TrashDir_select_repositorios").append("<option value='0'>Seleccione un Repositorio</option>");   
            $(repositories).find('Repository').each(function()
            {
                var IdRepository = $(this).find('IdRepositorio').text();
                var RepositoryName = $(this).find('NombreRepositorio').text();
                $('#TrashDir_select_repositorios').append('<option value = "'+IdRepository+'">'+RepositoryName+'</option>');
            });
        }
        else
        {
            $('#TrashDir_select_repositorios').empty().append("<option value=\""+0+"\">Seleccione una Empresa</option>");
            $('.DetailTrashDir').empty();
        }
    });
    
    $('#TrashFiles_select_empresas').change(function()
    {
        var IdEnterprise = $('#TrashFiles_select_empresas option:selected').attr('id');
        IdEnterprise = parseInt(IdEnterprise);
        
        if(IdEnterprise>0)
        {
            var EnterpriseKey = $('#TrashFiles_select_empresas').val();
            var repositories = Repository.GetRepositories(EnterpriseKey);
            $("#TrashFiles_select_repositorios option").remove();
            $("#TrashFiles_select_repositorios").append("<option value='0'>Seleccione un Repositorio</option>");   
            $(repositories).find('Repository').each(function()
            {
                var IdRepository = $(this).find('IdRepositorio').text();
                var RepositoryName = $(this).find('NombreRepositorio').text();
                $('#TrashFiles_select_repositorios').append('<option value = "'+IdRepository+'">'+RepositoryName+'</option>');
            });
        }
        else
        {
            $('#TrashFiles_select_repositorios').empty().append("<option value=\""+0+"\">Seleccione una Empresa</option>");
            $('.DetailTrashFiles').empty();
        }
    });
    
    $('#TrashDir_select_repositorios').change(function()
    {
        if($('#TrashDir_select_repositorios').val() > 0)
        {
            ListDeleted();
        }
        else
        {
            $('.DetailTrashDir').empty();
        }
    });
    
    $('#TrashFiles_select_repositorios').change(function()
    {
        if($('#TrashFiles_select_repositorios').val()>0)
        {
            ListDeleted();
        }
        else
        {
            $('.DetailTrashFiles').empty();
        }
    });
    
    $('#IconTrashRestoreDir').click(function(){RestoreTrashed();});
    
    $('#IconTrashRestoreFiles').click(function(){RestoreTrashed();});
    
    $('#IconTrashDeleteDir').click(function(){ConfirmEmptyTrash();});
    
    $('#IconTrashDeleteFiles').click(function(){ConfirmEmptyTrash();});
    
    
    
});

/*------------------------------------------------------------------------------
 * Abre la Papelera de reciclaje
 * @returns {undefined}
 */
function OpenTrah()
{
    $( "#TrashTabs" ).tabs();
    $( "#TrashTabs li" ).removeClass( "ui-corner-top" );
    $('#ContentTrash').dialog({width:800, height:600, title:"Papelera de Reciclaje", minWidth:500, minHeight:400,closeOnEscape:false}).dialogExtend(BotonesWindow);
}

/*------------------------------------------------------------------------------
 *  Muestra el listado de directorios que fueron eliminados 
 *-----------------------------------------------------------------------------*/
function ListDeleted()
{            
    var active = $( "#TrashTabs" ).tabs( "option", "active" );  
    var NombreRepositorio=0;
    var IdRepositorio=0;
    var option=0;
    switch(active)
    {
    case 0:
        IdRepositorio=$('#TrashDir_select_repositorios').val();
        NombreRepositorio=$('#TrashDir_select_repositorios option:selected').html();
        option = "ListDirectories";
        $('#TrashTabDirectorios .DetailTrashDir').append('<div class="loading"><img src="../img/loadinfologin.gif"></div>');
        break;
    case 1:
        NombreRepositorio=$('#TrashFiles_select_repositorios option:selected').html();
        IdRepositorio=$('#TrashFiles_select_repositorios').val();
        option = "ListFiles";
        $('#TrashTabFiles .DetailTrashFiles').append('<div class="loading"><img src="../img/loadinfologin.gif"></div>');
      break;
    }     
    
    if(IdRepositorio===0){Advertencia("Seleccione un Reposiorio."); return;}               
    
    $.ajax({
      async:true, 
      cache:false,
      dataType:"html", 
      type: 'POST',   
      url: "php/Trash.php",
      data: 'opcion='+option+'&DataBaseName='+EnvironmentData.DataBaseName+'&IdUsuario='+EnvironmentData.IdUsuario+'&IdRepositorio='+IdRepositorio+'&NombreRepositorio='+NombreRepositorio+'&nombre_usuario='+EnvironmentData.NombreUsuario, 
      success:  function(xml){
          
          $('.loading').remove();

          ($.parseXML( xml )===null) ? Salida(xml) : xml=$.parseXML( xml );          
            
            if(active===0){BuildDeletedTableDirectories(xml);}
            if(active===1){BuildDeletedTableFiles(xml);}            
            
            $(xml).find("Error").each(function()
            {
                var $Error=$(this);
                var estado=$Error.find("Estado").text();
                var mensaje =$Error.find("Mensaje").text();
                errorMessage(mensaje);
            });                
      },
      beforeSend:function(){},
      error:function(objXMLHttpRequest){errorMessage(objXMLHttpRequest);}
    });
}


function BuildDeletedTableDirectories(xml)
{
    $('#TrashTabDirectorios .DetailTrashDir').empty();
    $('#TrashTabDirectorios .DetailTrashDir').append('<table id ="TableTrashDirectories" class = "display hover"><thead><tr><th>Directorio</th><th>Directorio Destino</th><th>IdParent</th><th>Title</th></tr></thead><tbody></tbody></table> ');        
    
    var TablaInsert=$('#TableTrashDirectories').dataTable();  
    var Tabla = new $.fn.dataTable.Api('#TableTrashDirectories');
   
    TablaInsert.fnSetColumnVis(2,false);    
    TablaInsert.fnSetColumnVis(3,false);    
    
    $('#TableTrashDirectories tbody').on( 'click', 'tr', function () {
        $(this).toggleClass('selected');
    } );   
    
    $(xml).find("Directory").each(function()
    {
        var $Directory=$(this);
        var Title=$Directory.find("title").text();
        var IdParent =$Directory.find("IdParent").text();
        var IdDirectory =$Directory.find("IdDirectory").text();
        if(!IdParent>0)IdParent=0;
        var img = 0;
        if(IdParent>0)
        img='<center><img src="img/DirectorioEnable.png"></center>';
        else
        img='<center><img src="img/DirectorioDesabled.png"></center>';
        
        var data = [
            '<img src="img/Directorio.png" width="25px" height="25px" title="'+Title+'">'+Title,
            img, 
            IdParent,
            Title
        ];
        
        var ai = Tabla.row.add(data);         
        var n = TablaInsert.fnSettings().aoData[ ai[0] ].nTr;
        n.setAttribute('id',IdDirectory);       
        
        Tabla.draw();
        Tabla.columns.adjust().draw();
    });      
}

function BuildDeletedTableFiles(xml)
{
    $('#TrashTabFiles .DetailTrashFiles').empty();
    $('#TrashTabFiles .DetailTrashFiles').append('<table id ="TableTrashFiles" class = "display hover"><thead><tr><th>Documento</th><th>Tipo Documento</th><th>Directorio</th><th>IdDirectory</th><th>NombreArchivo</th><th>RutaArchivo</th></tr></thead><tbody></tbody></table> ');    
        
    var TablaInsert=$('#TableTrashFiles').dataTable(DataTable);  
    var Tabla = $('#TableTrashFiles').DataTable();
    
    TablaInsert.fnSetColumnVis(3,false);    
    TablaInsert.fnSetColumnVis(4,false);
    TablaInsert.fnSetColumnVis(5,false); 
        
    $('#TableTrashFiles tbody').on( 'click', 'tr', function () {
        $(this).toggleClass('selected');
    } );    
    
    $(xml).find("File").each(function()
    {
        var $File=$(this);
        var IdFile=$File.find("IdRepositorio").text();
        var NombreArchivo=$File.find("NombreArchivo").text();
        var RutaArchivo =$File.find("RutaArchivo").text();
        var IdDirectory =$File.find("IdDirectory").text();
        var TipoDocumento =$File.find("TipoArchivo").text();
        var TitleDirectory = $File.find("title").text();
        var Estado        =$File.find("status").text()
        if(!IdDirectory>0)IdDirectory=0;
        
        var ColumnDirectory = '';   /* Icono de directorio y su nombre donde pertenece el documento. */
        if(Estado!="0"){ColumnDirectory = '<img src="img/DirectorioEnable.png" width="25px" height="25px" title="'+TitleDirectory+'">'+TitleDirectory;}
        else {ColumnDirectory = '<img src="img/DirectorioDesabled.png" width="25px" height="25px" title="'+TitleDirectory+'">'+"Directorio no disponible";}        
        
        var data = [
            '<img src="img/acuse.png" width="25px" height="25px" title="'+NombreArchivo+'">'+NombreArchivo,    /* [0] */
            TipoDocumento,                                                                                     /* [1] */
            ColumnDirectory,                                                                                   /* [2] */
            IdDirectory,                                                                                       /* [3] */
            NombreArchivo,                                                                                     /* [4] */
            RutaArchivo                                                                                        /* [5] */            
        ];
        
        var ai = Tabla.row.add(data);         
        var n = TablaInsert.fnSettings().aoData[ ai[0] ].nTr;
        n.setAttribute('id',IdFile);       
        
        Tabla.draw();
        Tabla.columns.adjust().draw();
    });      
}

/* Se obtiene el listado de los archivos seleccionados por el usuario para realizar
 * la restauración de los mismos */

function RestoreTrashed()
{    
    var active = $( "#TrashTabs" ).tabs( "option", "active" );  
    
    var NombreRepositorio=0, IdRepositorio=0, option=0, TablaSelected = 0, Tabla =0;
    
    switch(active)
    {
        case 0:
            TablaSelected = '#TableTrashDirectories tr.selected';
            Tabla='#TableTrashDirectories';
            option="RestoreDirectories";
            NombreRepositorio = $('#TrashDir_select_repositorios option:selected').html();
            IdRepositorio = $('#TrashDir_select_repositorios').val();
            break;
        case 1:        
            TablaSelected = '#TableTrashFiles tr.selected';                
            Tabla='#TableTrashFiles';
            option = "RestoreFiles";
            NombreRepositorio = $('#TrashFiles_select_repositorios option:selected').html();
            IdRepositorio = $('#TrashFiles_select_repositorios').val();
         break;
         
        default: break;
    }     

    /* Se genera el XML que contiene los  Ids de los directorios o documentos a restaurar.
     * Los Ids son tomados de las filas seleccionadas por el usuario */
    
    if(!$(TablaSelected).length>0){Advertencia('<p>Seleccione elementos a restaurar.</p>'); return 0;}
    
    var Flag=0;
    
    var XmlRestore="<RestoreTrashed version='1.0' encoding='UTF-8'>";
    
    var TableTrash=$(Tabla).dataTable();      
    
    $(TablaSelected).each(function()
    {
        /* Cuando esta activo la opción de restauración de directorios */
        if(active===0)
        {
            var position = TableTrash.fnGetPosition(this); 
            var IdParent=TableTrash.fnGetData(position)[2];
            var title = TableTrash.fnGetData(position)[3];
            XmlRestore+='<Directory>\n\
                            <title>'+ title +'</title>\n\
                            <IdDirectory>'+ $(this).attr('id') +'</IdDirectory>\n\
                            <IdParent>' + IdParent + '</IdParent>\n\
                        </Directory>';
            if(!(IdParent>0)){Flag=1; Advertencia('<p>El directorio destino de uno o más elementos no existe, por favor seleccione manualmente la ruta destino.</p>'); return;}
        }    
        
        if(active ===1)
        {
            var position = TableTrash.fnGetPosition(this); 
            var IdDirectory=TableTrash.fnGetData(position)[3];
            var NombreArchivo = TableTrash.fnGetData(position)[4];
            XmlRestore+='<File>\n\
                            <NombreArchivo>'+ NombreArchivo +'</NombreArchivo>\n\
                            <IdRepositorio>'+ $(this).attr('id') +'</IdRepositorio>\n\
                            <IdDirectory>'+ IdDirectory +'</IdDirectory>\n\
                        </File>';
            if(!(IdDirectory>0)){Flag=1; Advertencia('<p>El directorio destino de uno o más elementos no existe, por favor seleccione manualmente la ruta destino.</p>'); return;}
        }
        
    });
    XmlRestore+='</RestoreTrashed>';

    if(Flag===1){return;}                    
    
    $.ajax({
      async:true, 
      cache:false,
      dataType:"html", 
      type: 'POST',   
      url: "php/Trash.php",
      data: 'opcion='+option+'&DataBaseName='+EnvironmentData.DataBaseName+'&IdUsuario='+EnvironmentData.IdUsuario+'&IdRepositorio='+IdRepositorio+'&NombreRepositorio='+NombreRepositorio+'&nombre_usuario='+EnvironmentData.NombreUsuario+'&XmlRestore='+XmlRestore+'&UserGroup='+EnvironmentData.NombreGrupo, 
      success:  function(xml){
          
          $('.loading').remove();

          ($.parseXML( xml )===null) ? Salida(xml) : xml=$.parseXML( xml );    
          $(xml).find("RestoreDirectories").each(function()
            {
                var $Restore=$(this);
                var estado=$Restore.find("Estado").text();
                var mensaje=$Restore.find("Mensaje").text();
                var PathAdvancing=$Restore.find("PathAdvancing").text();
                var PathStatus=$Restore.find("PathStatus").text();
                var KeyProcess = $Restore.find("KeyProcess").text();
                
                $('body').append('<div id ="'+KeyProcess+'"> <div id = "progress_'+KeyProcess+'"></div> <div id ="detail_'+KeyProcess+'"></div> </div>');
                $('#detail_'+KeyProcess).append('<div class="loading"><img src="../img/loadinfologin.gif"></div>');
                $('#'+KeyProcess).dialog({title:"Restaurando Directorios", width:350, height:200, minWidth:350, minHeight:200,
                buttons:{"Cancelar":{click:function(){CancelRestoreDir(PathStatus,PathAdvancing,KeyProcess);$(this).dialog('close');clearInterval(Process[KeyProcess]);},text:"Cancelar"}}
                });
                $('#'+KeyProcess).dialog({ dialogClass: 'no-close' });
                $('#progress_'+KeyProcess).progressbar({ value: 0 });
                $('#detail_'+KeyProcess).append('<p>Obteniendo detalles de progreso</p>');   
                    
                Process[KeyProcess]=setInterval("ProgressRestoreDir('"+PathAdvancing+"', '"+KeyProcess+"' )", 2000);
            });
            
            
            $(xml).find("RestoreFiles").each(function()
            {
                var $Restore=$(this);
                var estado=$Restore.find("Estado").text();
                var mensaje=$Restore.find("Mensaje").text();
                var PathAdvancing=$Restore.find("PathAdvancing").text();
                var PathStatus=$Restore.find("PathStatus").text();
                var KeyProcess = $Restore.find("KeyProcess").text();
                
                $('body').append('<div id ="'+KeyProcess+'"> <div id = "progress_'+KeyProcess+'"></div> <div id ="detail_'+KeyProcess+'"></div> </div>');
                $('#detail_'+KeyProcess).append('<div class="loading"><img src="../img/loadinfologin.gif"></div>');
                $('#'+KeyProcess).dialog({title:"Restaurando Documentos", width:350, height:200, minWidth:350, minHeight:200,
                buttons:{"Cancelar":{click:function(){CancelRestoringOfFiles(PathStatus,PathAdvancing,KeyProcess);$(this).dialog('close');clearInterval(Process[KeyProcess]);},text:"Cancelar"}}
                });
                $('#'+KeyProcess).dialog({ dialogClass: 'no-close' });
                $('#progress_'+KeyProcess).progressbar({ value: 0 });
                $('#detail_'+KeyProcess).append('<p>Obteniendo detalles de progreso</p>');   
                    
                Process[KeyProcess]=setInterval("ProgressRestoreFiles('"+PathAdvancing+"', '"+KeyProcess+"' )", 2000);
            });
          
          
            
            $(xml).find("Error").each(function()
            {
                var $Error=$(this);
                var estado=$Error.find("Estado").text();
                var mensaje =$Error.find("Mensaje").text();
                errorMessage(mensaje);
            });                
      },
      beforeSend:function(){},
      error:function(objXMLHttpRequest){errorMessage(objXMLHttpRequest);}
    });
    
}

function ConfirmEmptyTrash()
{
    var  TablaSelected = 0;
    var active = $( "#TrashTabs" ).tabs( "option", "active" );  
    switch(active)
    {
        case 0:
            TablaSelected = '#TableTrashDirectories tr.selected';
            break;
        case 1:        
            TablaSelected = '#TableTrashFiles tr.selected';                
         break;
         
        default: break;
    }         
    
    if(!$(TablaSelected).length>0){Advertencia('<p>Seleccione elementos a eliminar permanentemente.</p>'); return 0;}
    
    
    $('#ConfirmEmptyTrash').empty();
    $('#ConfirmEmptyTrash').dialog({
        title: "Mensaje de Confirmación", modal: true, width: 300, height: 200, resizable: false,
        buttons: { "Aceptar":{  text:"Aceptar", click:function(){ $(this).dialog('close'); EmptyTrash();   }},
            "Cancelar":{ text: "Cancelar", click: function(){$(this).dialog('close');}}
        }
    });
    
    $('#ConfirmEmptyTrash').append("<p>Sí continua, los elementos seleccionados serán <b>eliminados</b> del equipo NAS <b>permanentemente</b>.</p>\n\
    <p>¿Desea continuar?</p>");
    
}

function CancelRestoreDir(PathStatus,PathAdvancing,KeyProcess)
{
    $.ajax({
      async:true, 
      cache:false,
      dataType:"html", 
      type: 'POST',   
      url: "php/Trash.php",
      data: "opcion=CancelRestoringOfDirectories&PathStatus="+PathStatus+'&PathAdvancing='+PathAdvancing+'&KeyProcess='+KeyProcess, 
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

/* Muestra el progreso del proceso de la restauración */
function ProgressRestoreDir(PathAdvancing,KeyProcess)
{        
    $.ajax({
      async:true, 
      cache:false,
      dataType:"html", 
      type: 'POST',   
      url: "php/Trash.php",
      data: "opcion=CheckAdvancingRestoreDir&PathAdvancing="+PathAdvancing+'&KeyProcess='+KeyProcess, 
      success:  function(xml){
          $('.loading').remove();
          ($.parseXML( xml )===null) ? $('#'+KeyProcess).dialog('close') : xml=$.parseXML( xml );
           $(xml).find("Progress").each(function()
            {                               
                var $Advancing=$(this);
                var TotalDirectories=$Advancing.find("TotalDirectories").text();
                var TitleDirectory=$Advancing.find("TitleDirectory").text();
                var TitleFile=$Advancing.find("TitleFile").text();
                var NumberDirectory=$Advancing.find("NumberDirectory").text();
                
                var TotalProgress=(NumberDirectory/TotalDirectories)*100;
                $('#detail_'+KeyProcess).empty();
                $('#detail_'+KeyProcess).append('<p>Restaurando <b>'+NumberDirectory+ "</b> de <b>"+TotalDirectories+" directorios</b></p>");
                $('#detail_'+KeyProcess).append('<p>Procesando directorio: <b>'+TitleDirectory+"</b></p>");
                $('#detail_'+KeyProcess).append('<p>Documento : <b>'+TitleFile+"</b></p>");
                
                /* Avance de la barra de progreso */
                $('#progress_'+KeyProcess).progressbar({ value:TotalProgress});                                                
            });
            
            $(xml).find("NotFound").each(function()
            {               
                var $Advancing=$(this);
                var NotFound=$Advancing.find("NotFound").text();                
                $('#'+KeyProcess).dialog('close');
                clearInterval(Process[KeyProcess]);                
            });
            
            $(xml).find("Error").each(function()
            {
                var $Error=$(this);
                var estado=$Error.find("Estado").text();
                var mensaje =$Error.find("Mensaje").text();
                $('#'+KeyProcess).dialog('close');
                errorMessage(mensaje);
                clearInterval(Process[KeyProcess]);
                ListDeleted();
            });       
            
            /* Cuando se detecte que el flujo de trabajo termino se cancelan las peticiones y se muestra el resultado */
            
            var Directories= '';
            if($(xml).find("Ok").length>0){$('#'+KeyProcess).dialog('close');clearInterval(Process[KeyProcess]);ListDeleted();}
            $(xml).find("Ok").each(function()
            {
                var $Ok=$(this);
                Directories+='<p>  '+$Ok.find("Directory").text()+'  </p> <br>\n';
                CM_getTree();
            });
            
            if(Directories.length>0)
            Notificacion("Restauración de directorio(s) finalizada.","Se restauraron los diguientes directorios:<br>"+Directories); 
      },
      beforeSend:function(){},
      error:function(objXMLHttpRequest){errorMessage(objXMLHttpRequest);$('#'+KeyProcess).dialog('close');clearInterval(Process[KeyProcess]);ListDeleted();}
    });
}


/*-------------------------------Restauración de Documentos ---------------------------------- */


function CancelRestoringOfFiles(PathStatus,PathAdvancing,KeyProcess)
{
    $.ajax({
      async:true, 
      cache:false,
      dataType:"html", 
      type: 'POST',   
      url: "php/Trash.php",
      data: "opcion=CancelRestoringOfFiles&PathStatus="+PathStatus+'&PathAdvancing='+PathAdvancing+'&KeyProcess='+KeyProcess, 
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


/* Muestra el progreso del proceso de la restauración */
function ProgressRestoreFiles(PathAdvancing,KeyProcess)
{        
    $.ajax({
      async:true, 
      cache:false,
      dataType:"html", 
      type: 'POST',   
      url: "php/Trash.php",
      data: "opcion=CheckAdvancingRestoreFiles&PathAdvancing="+PathAdvancing+'&KeyProcess='+KeyProcess, 
      success:  function(xml){
          $('.loading').remove();
          ($.parseXML( xml )===null) ? $('#'+KeyProcess).dialog('close') : xml=$.parseXML( xml );
           $(xml).find("Progress").each(function()
            {                               
                var $Advancing=$(this);
                var TotalDirectories=$Advancing.find("TotalFiles").text();
                var TitleFile=$Advancing.find("TitleFile").text();
                var NumberDirectory=$Advancing.find("NumberFile").text();
                
                var TotalProgress=(NumberDirectory/TotalDirectories)*100;
                $('#detail_'+KeyProcess).empty();
                $('#detail_'+KeyProcess).append('<p>Restaurando <b>'+NumberDirectory+ "</b> de <b>"+TotalDirectories+" Documento(s)</b></p>");
                $('#detail_'+KeyProcess).append('<p>Documento : <b>'+TitleFile+"</b></p>");
                
                /* Avance de la barra de progreso */
                $('#progress_'+KeyProcess).progressbar({ value:TotalProgress});                                                
            });
            
            $(xml).find("NotFound").each(function()
            {               
                var $Advancing=$(this);
                var NotFound=$Advancing.find("NotFound").text();                
                $('#'+KeyProcess).dialog('close');
                clearInterval(Process[KeyProcess]);                
            });
            
            $(xml).find("Error").each(function()
            {
                var $Error=$(this);
                var estado=$Error.find("Estado").text();
                var mensaje =$Error.find("Mensaje").text();
                $('#'+KeyProcess).dialog('close');
                errorMessage(mensaje);
                clearInterval(Process[KeyProcess]);
                ListDeleted();
            });       
            
            /* Cuando se detecte que el flujo de trabajo termino se cancelan las peticiones y se muestra el resultado */
            
            var Directories= '';
            if($(xml).find("Ok").length>0){$('#'+KeyProcess).dialog('close');clearInterval(Process[KeyProcess]);ListDeleted();}
            $(xml).find("Ok").each(function()
            {
                var $Ok=$(this);
                Directories+='<p>  '+$Ok.find("File").text()+'  </p> <br>\n';
//                CM_getTree();
            });
            
            if(Directories.length>0)
            Notificacion("Restauración de Documento(s) finalizada.","Se restauraron los diguientes documentos:<br>"+Directories); 
      },
      beforeSend:function(){},
      error:function(objXMLHttpRequest){errorMessage(objXMLHttpRequest);$('#'+KeyProcess).dialog('close');clearInterval(Process[KeyProcess]);ListDeleted();}
    });
}


/*------------------------------------------------------------------------------
 *          Eliminación permanente de la papelera   (Empty Trash)               *
 -------------------------------------------------------------------------------*/


/* Elimina permanentemente de la papelera los elementos seleccionados por el usuario */
function EmptyTrash()
{    
    var active = $( "#TrashTabs" ).tabs( "option", "active" );  
    
    var NombreRepositorio=0, IdRepositorio=0, option=0, TablaSelected = 0, Tabla =0;
    
    switch(active)
    {
        case 0:
            TablaSelected = '#TableTrashDirectories tr.selected';
            Tabla='#TableTrashDirectories';
            option="DeleteDirectories";
            NombreRepositorio = $('#TrashDir_select_repositorios option:selected').html();
            IdRepositorio = $('#TrashDir_select_repositorios').val();
            break;
        case 1:        
            TablaSelected = '#TableTrashFiles tr.selected';                
            Tabla='#TableTrashFiles';
            option = "DeleteFiles";
            NombreRepositorio = $('#TrashFiles_select_repositorios option:selected').html();
            IdRepositorio = $('#TrashFiles_select_repositorios').val();
         break;
         
        default: break;
    }     

    /* Se genera el XML que contiene los  Ids de los directorios o documentos a restaurar.
     * Los Ids son tomados de las filas seleccionadas por el usuario */
    
    if(!$(TablaSelected).length>0){Advertencia('<p>Seleccione elementos a eliminar permanentemente.</p>'); return 0;}
    
    var Flag=0;
    
    var XmlRestore="<EmptyTrash version='1.0' encoding='UTF-8'>";
    
    var TableTrash=$(Tabla).dataTable();      
    
    $(TablaSelected).each(function()
    {
        /* Cuando esta activo la opción de restauración de directorios */
        if(active===0)
        {
            var position = TableTrash.fnGetPosition(this); 
            var IdParent=TableTrash.fnGetData(position)[2];
            var title = TableTrash.fnGetData(position)[3];
            XmlRestore+='<Directory>\n\
                            <title>'+ title +'</title>\n\
                            <IdDirectory>'+ $(this).attr('id') +'</IdDirectory>\n\
                            <IdParent>' + IdParent + '</IdParent>\n\
                        </Directory>';
        }    
        
        if(active ===1)
        {
            var position = TableTrash.fnGetPosition(this); 
            var IdDirectory=TableTrash.fnGetData(position)[3];
            var NombreArchivo = TableTrash.fnGetData(position)[4];
            var RutaArchivo = TableTrash.fnGetData(position)[5];
            XmlRestore+='<File>\n\
                            <NombreArchivo>'+ NombreArchivo +'</NombreArchivo>\n\
                            <IdRepositorio>'+ $(this).attr('id') +'</IdRepositorio>\n\
                            <IdDirectory>'+ IdDirectory +'</IdDirectory>\n\
                            <RutaArchivo>'+ RutaArchivo +'</RutaArchivo>\n\
                        </File>';
        }
        
    });
    XmlRestore+='</EmptyTrash>';

    if(Flag===1){return;}
    
    
    $.ajax({
      async:true, 
      cache:false,
      dataType:"html", 
      type: 'POST',   
      url: "php/Trash.php",
      data: 'opcion='+option+'&DataBaseName='+EnvironmentData.DataBaseName+'&IdUsuario='+EnvironmentData.IdUsuario+'&IdRepositorio='+IdRepositorio+'&NombreRepositorio='+NombreRepositorio+'&nombre_usuario='+EnvironmentData.NombreUsuario+'&XmlEmpty='+XmlRestore+'&UserGroup='+EnvironmentData.NombreGrupo, 
      success:  function(xml){
          
          $('.loading').remove();

          ($.parseXML( xml )===null) ? Salida(xml) : xml=$.parseXML( xml );    
          $(xml).find("DeleteDirectories").each(function()
            {
                var $Restore=$(this);
                var estado=$Restore.find("Estado").text();
                var mensaje=$Restore.find("Mensaje").text();
                var PathAdvancing=$Restore.find("PathAdvancing").text();
                var PathStatus=$Restore.find("PathStatus").text();
                var KeyProcess = $Restore.find("KeyProcess").text();
                
                $('body').append('<div id ="'+KeyProcess+'"> <div id = "progress_'+KeyProcess+'"></div> <div id ="detail_'+KeyProcess+'"></div> </div>');
                $('#detail_'+KeyProcess).append('<div class="loading"><img src="../img/loadinfologin.gif"></div>');
                $('#'+KeyProcess).dialog({title:"Eliminando Directorios", width:350, height:200, minWidth:350, minHeight:200,
                buttons:{"Cancelar":{click:function(){CancelService(PathStatus,PathAdvancing,KeyProcess);$(this).dialog('close');clearInterval(Process[KeyProcess]);},text:"Cancelar"}}
                });
                $('#'+KeyProcess).dialog({ dialogClass: 'no-close' });
                $('#progress_'+KeyProcess).progressbar({ value: 0 });
                $('#detail_'+KeyProcess).append('<p>Obteniendo detalles de progreso</p>');   
                    
                Process[KeyProcess]=setInterval("CheckProgress('"+ option +"' , '"+PathAdvancing+"', '"+KeyProcess+"' )", 2000);
            });
            
            
            $(xml).find("RestoreFiles").each(function()
            {
                var $Restore=$(this);
                var estado=$Restore.find("Estado").text();
                var mensaje=$Restore.find("Mensaje").text();
                var PathAdvancing=$Restore.find("PathAdvancing").text();
                var PathStatus=$Restore.find("PathStatus").text();
                var KeyProcess = $Restore.find("KeyProcess").text();
                
                $('body').append('<div id ="'+KeyProcess+'"> <div id = "progress_'+KeyProcess+'"></div> <div id ="detail_'+KeyProcess+'"></div> </div>');
                $('#detail_'+KeyProcess).append('<div class="loading"><img src="../img/loadinfologin.gif"></div>');
                $('#'+KeyProcess).dialog({title:"Restaurando Documentos", width:350, height:200, minWidth:350, minHeight:200,
                buttons:{"Cancelar":{click:function(){CancelService(PathStatus,PathAdvancing,KeyProcess);$(this).dialog('close');clearInterval(Process[KeyProcess]);},text:"Cancelar"}}
                });
                $('#'+KeyProcess).dialog({ dialogClass: 'no-close' });
                $('#progress_'+KeyProcess).progressbar({ value: 0 });
                $('#detail_'+KeyProcess).append('<p>Obteniendo detalles de progreso</p>');   
                    
                Process[KeyProcess]=setInterval("CheckProgress('"+PathAdvancing+"', '"+KeyProcess+"' )", 2000);
            });                    
            
            $(xml).find("Error").each(function()
            {
                var $Error=$(this);
                var estado=$Error.find("Estado").text();
                var mensaje =$Error.find("Mensaje").text();
                errorMessage(mensaje);
            });                
      },
      beforeSend:function(){},
      error:function(objXMLHttpRequest){errorMessage(objXMLHttpRequest);}
    });
}


function CancelService(PathStatus,PathAdvancing,KeyProcess)
{
    $.ajax({
      async:true, 
      cache:false,
      dataType:"html", 
      type: 'POST',   
      url: "php/Trash.php",
      data: "opcion=CancelService&PathStatus="+PathStatus+'&PathAdvancing='+PathAdvancing+'&KeyProcess='+KeyProcess, 
      success:  function(xml){
          $('.loading').remove();
          ($.parseXML( xml )===null) ? Salida(xml) : xml=$.parseXML( xml );
          $('#'+KeyProcess).dialog('close');

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

/* -----------------------------------------------------------------------------
 * Muestra el detalle del progreso de una de las siguientes operaciones:
 *  Borrado
 *  Resturación de Directorios o Documentos
 *  Vaciado de la papelera de reciclaje
 * -----------------------------------------------------------------------------*/

function CheckProgress(OperationName,PathAdvancing,KeyProcess)
{            
    
    $.ajax({
      async:true, 
      cache:false,
      dataType:"html", 
      type: 'POST',   
      url: "php/Trash.php",
      data: "opcion=CheckProgress&PathAdvancing="+PathAdvancing+'&KeyProcess='+KeyProcess+'&OperationName='+OperationName, 
      success:  function(xml){
          $('.loading').remove();
          ($.parseXML( xml )===null) ? $('#'+KeyProcess).dialog('close') : xml=$.parseXML( xml );
           $(xml).find("Progress").each(function()
            {                               
                var $Advancing=$(this);
                var TotalDirectories = $Advancing.find("TotalDirectories").text();
                var TitleDirectory = $Advancing.find("TitleDirectory").text();
                var TotalFiles = $Advancing.find("TotalFiles").text();
                var NumberFile = $Advancing.find("NumberFile").text();
                var TitleFile = $Advancing.find("TitleFile").text();                
                var NumberDirectory = $Advancing.find("NumberDirectory").text();
                var NumberSubdirectory = $Advancing.find("NumberSubdirectory").text();
                var TotalSubdirectories = $Advancing.find("TotalSubdirectories").text();
                var TitleSubdirectory = $Advancing.find("TitleSubdirectory").text();
                
                var TotalProgress = 0;
                
                if(NumberDirectory <= TotalDirectories)
                    TotalProgress=(NumberDirectory/TotalDirectories)*100;
                
                if(NumberFile <= TotalFiles)
                    TotalProgress =(NumberFile/TotalFiles)*100;
                
                $('#detail_'+KeyProcess).empty();
                
                if(NumberDirectory !== null && NumberDirectory !== '')
                    $('#detail_'+KeyProcess).append('<p>Restaurando <b>'+NumberDirectory+ "</b> de <b>"+TotalDirectories+" directorios</b></p>");                
                
                if(TitleDirectory!== null && TitleDirectory!== '')
                    $('#detail_'+KeyProcess).append('<p>Procesando directorio: <b>'+TitleDirectory+"</b></p>");                                
                
                if(TotalSubdirectories>0 && NumberSubdirectory>0)
                    $('#detail_'+KeyProcess).append('<p>Subdirectorio <b>'+NumberSubdirectory+ "</b> de <b>"+TotalSubdirectories+" subdirectorios</b></p>");                
                
                if(TitleSubdirectory!== null && TitleSubdirectory!== '')
                    $('#detail_'+KeyProcess).append('<p>Procesando subdirectorio: <b>'+TitleSubdirectory+"</b></p>");
                
                if(NumberFile>0 && TotalFiles>0)
                    $('#detail_'+KeyProcess).append('<p>Procesando Documento: <b>'+NumberFile+"</b> de <b>"+TotalFiles+"</b></p>");
                
                if(TitleFile!== null && TitleFile!== '')
                    $('#detail_'+KeyProcess).append('<p>Procesando Documento : <b>'+TitleFile+"</b></p>");
                
                /* Avance de la barra de progreso */
                $('#progress_'+KeyProcess).progressbar({ value:TotalProgress});                                                
            });
            
            $(xml).find("NotFound").each(function()
            {               
                var $Advancing=$(this);
                var NotFound=$Advancing.find("NotFound").text();                
                $('#'+KeyProcess).dialog('close');
                clearInterval(Process[KeyProcess]);                
            });
            
            $(xml).find("Error").each(function()
            {
                var $Error=$(this);
                var estado=$Error.find("Estado").text();
                var mensaje =$Error.find("Mensaje").text();
                $('#'+KeyProcess).dialog('close');
                errorMessage(mensaje);
                clearInterval(Process[KeyProcess]);
                ListDeleted();
            });       
            
            /* Cuando se detecte que el flujo de trabajo termino se cancelan las peticiones y se muestra el resultado */
            
            var Directories= '';
            if($(xml).find("Ok").length>0){$('#'+KeyProcess).dialog('close');clearInterval(Process[KeyProcess]);ListDeleted();}
            $(xml).find("Ok").each(function()
            {
                var $Ok=$(this);
                var TituloNotificacion = $Ok.find("TituloNotificacion").text();
                var MensajeNotificacion = $Ok.find("MensajeNotificacion").text();
                
                Directories+='<p>  '+$Ok.find("Directory").text()+'  </p> <br>\n';                
                Notificacion(TituloNotificacion,MensajeNotificacion+Directories);
            });                            
             
      },
      beforeSend:function(){},
      error:function(objXMLHttpRequest){errorMessage(objXMLHttpRequest);$('#'+KeyProcess).dialog('close');clearInterval(Process[KeyProcess]);ListDeleted();}
    });
}
