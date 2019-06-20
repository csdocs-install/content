<?php

/*------------------------------------------------------------------------------
 * Clase que ejecuta procesos en segundo plano a través de un una pila.
 * El archivo Fifo.ini contiene la pila de procesos pendientes por ejecutar.
 * Cada uno de los procesos contiene un archivo de Estado.ini para deter el proceso
 -------------------------------------------------------------------------------*/

/**
 * Description of Fifo
 *
 * @author daniel
 */

class Fifo {
     protected $APP_ROUTE;

    public function __construct() {
        $this->APP_ROUTE = dirname(getcwd());

        $this->CreateStack();
    }
    
    private function CreateStack()
    {
        $fifo="$this->APP_ROUTE/Fifo/Fifo.ini";
        $FifoDirectory="$this->APP_ROUTE/Fifo/";

        if(!file_exists($FifoDirectory)) {
            mkdir($FifoDirectory, 0777);
        }

        if(!file_exists($fifo)) {
            touch($fifo);
        }                    
    }
    /*--------------------------------------------------------------------------
     * Inicializa un proceso. 
     --------------------------------------------------------------------------*/
    function StartProcess($KeyProcess)
    {
        $RoutFile = dirname(getcwd());
        $FifoFile="/volume1/web/Fifo/Fifo.ini";     
        
        if(!file_exists($FifoFile)){echo "No existe el archivo Fifo"; return 0;}        
        
        $Fifo=parse_ini_file ($FifoFile,true);
        
        if(!isset($Fifo[$KeyProcess])){echo "No existe el proceso $KeyProcess"; return 0;}
        
        $InfoProcess=$Fifo[$KeyProcess];        
        $TypeProcess=$InfoProcess['ProcessName'];
        $Path=$InfoProcess['Path'];        

        $php = (phpversion() >= 5.6) ? "php56" : "php";

        /* Los procesos son ejecutados como servicio y con su respectiva clase lo atenderá */
        
        switch ($TypeProcess)
        {
            case "DeleteDirectory": 
                $command="$php $RoutFile/php/ServiceDeleteDirectory.php $KeyProcess ".dirname($Path)."/$KeyProcess.ini >>".dirname($Path)."/$KeyProcess.txt 2>>".dirname($Path)."/$KeyProcess.txt &";
                system($command); 
                return 1;
                
            case "RestoreDirectories":
                $command="$php $RoutFile/php/Trash.php $KeyProcess ".dirname($Path)."/$KeyProcess.ini RestoreDir >>".dirname($Path)."/$KeyProcess.txt 2>>".dirname($Path)."/$KeyProcess.txt &";
                system($command); 
                return 1;
                
            case "RestoreFiles":
                $command="$php $RoutFile/php/Trash.php $KeyProcess ".dirname($Path)."/$KeyProcess.ini RestoreFiles >>".dirname($Path)."/$KeyProcess.txt 2>>".dirname($Path)."/$KeyProcess.txt &";
                system($command); 
                return 1;
                
            case "DeleteDirectories":
                $command="$php $RoutFile/php/Trash.php $KeyProcess ".dirname($Path)."/$KeyProcess.ini DeleteDirectories >>".dirname($Path)."/$KeyProcess.txt 2>>".dirname($Path)."/$KeyProcess.txt &";
                system($command); 
                return 1;
                
            case "DeleteFiles":
                $command="$php $RoutFile/php/Trash.php $KeyProcess ".dirname($Path)."/$KeyProcess.ini DeleteFiles >>".dirname($Path)."/$KeyProcess.txt 2>>".dirname($Path)."/$KeyProcess.txt &";
                system($command); 
                return 1;
            default: return 0;
        }               
    }
       
    /*--------------------------------------------------------------------------
     * Agrega un proceso a la pila.
     *-------------------------------------------------------------------------*/
    function AddToStack($ProcessName,$UserName, $Path)
    {
        $KeyProcess=  $UserName.date("mdhis"); 
        $fifo="/volume1/web/Fifo/Fifo.ini";
        
        if(!($config=  fopen($fifo, "a+"))){return 0;}
        fwrite($config, "[$KeyProcess]".PHP_EOL);
        fwrite($config, "ProcessName=$ProcessName".PHP_EOL);
        fwrite($config, "UserName=$UserName".PHP_EOL);
        fwrite($config, "Date=".date('Y-m-d').PHP_EOL);
        fwrite($config, "Path=".  dirname($Path)."/$KeyProcess.ini".PHP_EOL);
        fclose($config);        
        
        return $KeyProcess;
    }
    
    function DeleteProcess($KeyProcess)
    {        
        $fifo="/volume1/web/Fifo/Fifo.ini";
        if(!file_exists($fifo)){echo "No se encontró el archivo fifo."; return 0;}
        $Fifo=parse_ini_file ($fifo,true);
        unlink($fifo);
        if(!($config=  fopen($fifo, "a+"))){return 0;}
        
        foreach ($Fifo as  $campo => $valor)
        {
            if(strcasecmp($campo, $KeyProcess)==0){echo "Se eliminó el proceso $KeyProcess"; continue;}            
            fwrite($config, "[$campo]".PHP_EOL);
            foreach ($Fifo[$campo] as $Nodo=>$Value)
            {
                fwrite($config, "$Nodo=$Value".PHP_EOL);
            }                        
        }
                    
        fclose($config);                                
    }
    
    
    /*--------------------------------------------------------------------------
     * Detiene un proceso activo.
     * Cambia el archivo de estado del proceso a => 0
     --------------------------------------------------------------------------*/
    function StopProcess($KeyProcess)
    {
        
    }
    
    /*--------------------------------------------------------------------------
     *  Inicializa la cola de Procesos pendientes .
     *  Esta función se ejecuta al iniciar la NAS.
     *  
     --------------------------------------------------------------------------*/
    private function StartAllProcessStack()
    {
        
    }
}

$fifo = new Fifo();
