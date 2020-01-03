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
class App_Rest_CiudadService extends App_Rest_Server {
    /*     * *
     * recupera la lista de sucursales que tiene esta ciudad
     * @param String $userid
     * @param String $apikey
     * @param String $manifiesto
     */

    public function getSucursales($userid, $apikey, $ciudad) {
        $ciudadM = new App_Model_Ciudad();
        $sucursales = $ciudadM->getSucursalesByCiudad($ciudad);
        $lista = array();
        $i = 0;
        foreach ($sucursales as $suc) {
            $lista["s" . $i] = array(
                "id" => base64_encode($suc["id_sucursal"]),
                "nombre" => $suc["nombre"],
                "ciudad" => $suc["ciudad"],
                "ciudadNombre" => $suc["ciudadNombre"],
                "abreviacion" => $suc["abreviacion"]
            );
            $i++;
        }
        $listaViajes = array("sucursales" => $lista, "size" => count($lista));
//        $log = Zend_Registry::get("log");
//        $log->info("Resultado :: " . Zend_Json::encode($listaViajes));
        return App_Rest_Response::Generate($listaViajes, true);
    }

    /**
     * Recupera la lista de dosificaciones de una ciudad
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version 1.2
     * @date creation 2011-09-29
     */
    function getDosificacion($ciudad) {
        $dosificacionM = new App_Model_DosificacionModel();
        $dosificaciones = $dosificacionM->getByCiudad($ciudad);
        $lista = array();
        $i = 0;
        foreach ($dosificaciones as $dos) {
            $lista["d" . $i] = array(
                "id" => base64_encode($dos["id_datos_factura"]),
                "limite" => $dos["fecha_limite"],
                "sucursal" => $dos["nombre"],
                "autorizacion" => $dos["autorizacion"]
            );
            $i++;
        }
        $listaViajes = array("dosificaciones" => $lista, "size" => count($lista));
        return App_Rest_Response::Generate($listaViajes, true);
    }
    
    /**
     * Recupera la lista de ciudades destino en base a una ciudad origen
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version 1.2
     * @date creation 2012-08-13
     */
    function getDestinos($ciudad) {
        $dosificacionM = new App_Model_DestinoModel();
        $dosificaciones = $dosificacionM->getByCiudad($ciudad);
        $lista = array();
        $i = 0;
        foreach ($dosificaciones as $dos) {
            $lista["d" . $i] = array(
                "id" => base64_encode($dos["id_datos_factura"]),
                "limite" => $dos["fecha_limite"],
                "sucursal" => $dos["nombre"],
                "autorizacion" => $dos["autorizacion"]
            );
            $i++;
        }
        $listaViajes = array("dosificaciones" => $lista, "size" => count($lista));
        return App_Rest_Response::Generate($listaViajes, true);
    }
    
    
    
    
    
    
    

    /**
     * registra la llegada de encomiendas a destino
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version beta 1
     * @date $(now)
     */
    function saveArribo($userid, $apikey, $encomiendas, $responsable, $sucursalLLegada, $manifiesto) {
        $encomiendaM = new App_Model_Encomienda();
        try {
            $encomiendas = $encomiendaM->saveArribo($encomiendas, $responsable, $sucursalLLegada, $manifiesto);
            $mensaje = "Las encomiendas ha sido registradas";
        } catch (Zend_Db_Exception $zdbe) {
            $mensaje = "No se ha podido registrar las encomiendas";
        }

        $listaViajes = array("message" => $mensaje);
        return App_Rest_Response::Generate($listaViajes, true);
    }

}

?>
