<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of App_Rest_Server
 *
 * @author poloche
 */
class App_Rest_MobiusService extends App_Rest_Server {

    public function getURL($adapter, $pid, $code) {
        $mobiusModel = new App_Model_MobiusModel($adapter);
        $data = $mobiusModel->getDireccion($code, $pid);
        if ($data) {
            $info = array("info" => $data->direccion . $data->param, "horaActual" => date("H:i"));
        } else {
            $info = array("info" => "http://quantum.net.bo/principal.php" . $data->param, "horaActual" => date("H:i"));
        }
        return App_Rest_Response::Generate($info, true);
    }

}

?>
