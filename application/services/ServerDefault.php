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
class App_Rest_ServerDefault extends App_Rest_Server {

    public function getVersion($userid, $apikey) {
        $this->authenticate($userid, $apikey);

        return App_Rest_Response::Generate(array("version" => 1.0));
    }

    /**
     * recupera la hora actual del servidor
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version 1.2
     * @date creation 2012-04-12
     */
    function getTime() {
        $viajeModel = new App_Model_Viaje();
        return App_Rest_Response::Generate(array("time" => $viajeModel->getTime()),true,'json');
    }
}
?>
