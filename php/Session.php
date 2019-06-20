<?php

/*
 * @author Daniel Luna
 */
if (!isset($_SESSION))
    session_start();

class Session {

    static $idSession = null;

    public static function createSession($idDataBase, $dataBaseName, $idUser, $userName, $idGroup, $groupName) {
        if (!isset($_SESSION))
            session_start();

        $sessionId = session_id();

        $_SESSION['idSession'] = $sessionId;
        $_SESSION['idDataBase'] = $idDataBase;
        $_SESSION['dataBaseName'] = $dataBaseName;
        $_SESSION['idUser'] = $idUser;
        $_SESSION['userName'] = $userName;
        $_SESSION['idGroup'] = $idGroup;
        $_SESSION['groupName'] = $groupName;
                     
        return $sessionId;
    }
    
    public static function setPermissions($permissionsArray){
        if (!isset($_SESSION))
            session_start();
        if(is_array($permissionsArray)){
            for ($cont = 0; $cont < count($permissionsArray); $cont++){
                $idMenu = md5($permissionsArray[$cont]['IdMenu']);
                $type = $permissionsArray[$cont]['Type'];
                
                if((int) $type > 0)
                    $idRepository = md5($permissionsArray[$cont]['IdRepositorio']);     
                else
                    $idRepository = md5 (0);
                
                $_SESSION['permissions'][$idRepository][$idMenu] = true;
            }
        }
    }

    public static function getSessionParameters() {
        if (!isset($_SESSION))
            session_start();

        if(!isset($_SESSION['idSession'])
            or !isset($_SESSION['dataBaseName'])
            or !isset($_SESSION['userName'])
            or !isset($_SESSION['idUser'])
            or !isset($_SESSION['idGroup'])
            or !isset($_SESSION['idSession'])
            or !isset($_SESSION['idDataBase']))
            return null;
        
        return array('idSession' => $_SESSION['idSession'], "dataBaseName" => $_SESSION['dataBaseName'],
            "userName" => $_SESSION['userName'], 'idUser' => $_SESSION['idUser'],
            'idGroup' => $_SESSION['idGroup'], 'groupName' => $_SESSION['groupName']);
    }

    public static function getIdSession() {
        if (isset($_SESSION['userName']) and isset($_SESSION['dataBaseName']))
            return $_SESSION['idSession'];
        else
            return null;
    }

    public static function checkTimeOut() {
        if (isset($_SESSION["lastActivity"])) {
            if (time() - $_SESSION["lastActivity"] > 1800) {
                // last request was more than 30 minutes ago
                session_unset();     // unset $_SESSION variable for the run-time 
                session_destroy();   // destroy session data in storage
            } else if (time() - $_SESSION["lastActivity"] > 60) {
                $_SESSION["lastActivity"] = time(); // update last activity time stamp
            }
        }
    }

    public static function destroySession() {
        // Si se desea destruir la sesión completamente, borre también la cookie de sesión.
        // Nota: ¡Esto destruirá la sesión, y no la información de la sesión!
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]
            );
        }
        
        if(!isset($_SESSION))
            session_start();

        // Finalmente, destruir la sesión.
        if (isset($_SESSION['dataBaseName']))
            session_destroy();
        
    }

}
