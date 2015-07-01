/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/* global EnvironmentData */

TableEngineDT = '';
TableEnginedT = '';

$(document).ready(function()
{        
    $('#form_engine').keydown(function( event )
    {
      if ( event.which == 13 ) {
         EngineSearch();
      }
    });   
});

/********************************************************************************
 * Realiza las búsquedas correspondientes en el repositorio seleccionado
 * @returns {undefined}
 */
function EngineSearch()
{    
    var Search=$('#form_engine').val();
    
    if(!(Search.length>0)){return;}
    Loading();
    
    $.ajax({
      async:true, 
      cache:false,
      dataType:"html", 
      type: 'POST',   
      url: "php/ContentManagement.php",
      data: "opcion=EngineSearch&DataBaseName="+EnvironmentData.DataBaseName+'&IdUser='+EnvironmentData.IdUsuario+'&IdGroup='+ EnvironmentData.IdGrupo+'&Search='+Search, 
      success:  function(xml){
          $('#Loading').dialog('close');
             if($.parseXML( xml )===null){Error(xml);return 0;}else xml=$.parseXML( xml );
            $(xml).find("Error").each(function()
            {
                var $Instancias=$(this);
                var estado=$Instancias.find("Estado").text();
                var mensaje=$Instancias.find("Mensaje").text();
                Error(mensaje);
                return;
            });
            SetSearchEngineResult(xml);  /* Se envian los dato para mostrarse en la Tabla de resultados */
            
      },
      beforeSend:function(){},
      error:function(objXMLHttpRequest){Error(objXMLHttpRequest);}
    });
}

/*******************************************************************************
 * 
 *          Tabla con los datos del resultado de busqueda                       
 * 
 ******************************************************************************/

function SetSearchEngineResult(xml)
{
    $('.contentDetailEngine').empty();
    $('.contentDetailEngine').append('<table id="table_EngineResult" class="display hover"></table>');
    $('#table_EngineResult').append('<thead><tr><th>Empresa</th><th>Repositorio</th><th>Nombre del Archivo</th><th>Fecha de Ingreso</th><th>Tipo de Documento</th><th>Detalle</th><th>Vista Previa</th><th>Detalle</th><th>Ruta</th><th>IdFile</th><th>IdEmpresa</th><th>IdRepositorio</th></tr></thead><tbody></tbody>');
//    TableEngineDT=$('#table_EngineResult').DataTable(DataTable);
    TableEnginedT = $('#table_EngineResult').dataTable();
    TableEngineDT = new $.fn.dataTable.Api('#table_EngineResult');

var cont=1;

     $(xml).find("Resultado").each(function()
    {        
        var $Resultado = $(this);
        var TipoArchivo = $Resultado.find("TipoArchivo").text();
        var FechaIngreso = $Resultado.find("FechaIngreso").text();
        var NombreArchivo = $Resultado.find("NombreArchivo").text();
        var Full = $Resultado.find("Full").text();
        var IdFile = $Resultado.find("IdFile").text();
        var RutaArchivo = $Resultado.find("RutaArchivo").text();
        var NombreEmpresa = $Resultado.find("NombreEmpresa").text();
        var NombreRepositorio = $Resultado.find("NombreRepositorio").text();
        var IdRepositorio = $Resultado.find("IdRepositorio").text();
        var IdEmpresa = $Resultado.find("IdEmpresa").text();
        var IdGlobal = $Resultado.find("IdGlobal").text();
//        RutaArchivo=location.host+'/'+RutaArchivo;
        Full = Full.slice(0,200);

    /* Datos que serán insertados en la tabla */
    
        var data=
        [
            /*[0]*/NombreEmpresa,
            /*[1]*/NombreRepositorio,
            /*[2]*/NombreArchivo,
            /*[3]*/FechaIngreso,
            /*[4]*/TipoArchivo,
            /*[5]*/Full, 
            /*[6]*/'<img src="img/acuse.png" title="vista previa de "'+NombreArchivo+'" onclick="Preview(\''+TipoArchivo+'\', \''+IdGlobal+'\', \''+ IdFile +'\' , \'Content\')">',
            /*[7]*/'<img src="img/metadata.png" title="Metadatos de '+NombreArchivo+'" onclick="GetDetalle(\'Content\', \''+IdGlobal+'\', \''+IdFile+'\')">',                       
            /*[8]*/RutaArchivo,
            /*[9]*/IdFile,
            /*[10]*/IdEmpresa,
            /*[11]*/IdRepositorio
        ];  
                    
        var ai = TableEngineDT.row.add(data);         
        var n = TableEnginedT.fnSettings().aoData[ ai[0] ].nTr;
        n.setAttribute('id',IdGlobal);
        
        if(cont===60)
        {
            TableEngineDT.draw();            
            cont=0;
        }
        cont++;
    });
    
    TableEngineDT.draw();        

    TableEnginedT.fnSetColumnVis(8,false);
    TableEnginedT.fnSetColumnVis(9,false);
    TableEnginedT.fnSetColumnVis(10,false);
    TableEnginedT.fnSetColumnVis(11,false);
       
    $('#table_EngineResult tbody').on( 'click', 'tr', function ()
    {
        TableEngineDT.$('tr.selected').removeClass('selected');
        $(this).addClass('selected');
        var IdUser=$('#table_EngineResult tr.selected').attr('id');    
    } );    
}
