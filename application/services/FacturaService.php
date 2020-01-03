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
class App_Rest_FacturaService extends App_Rest_Server {

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
    function getFactura($numero, $dosificacion) {
        $asientoM = new App_Model_AsientoModel();
        $facturaM = new App_Model_FacturaModel();
        if ($dosificacion == "0" || $dosificacion == "") {
            $facturas = $facturaM->getByNumero($numero);
        } else {
            $facturas = $facturaM->getByNumeroDosificacion($numero, $dosificacion);
        }

        $confModel = new App_Model_ConfiguracionSistemaModel();
        $empresa = $confModel->getByKey("NOMBRE_EMPRESA");
        $nit = $confModel->getByKey("NIT_EMPRESA");

        $lista = array();
        $i = 0;
        foreach ($facturas as $fac) {
            $lista["f" . $i] = array(
                "id" => base64_encode($fac["id_factura"]),
                "numero" => $fac["numero_factura"],
                "nit" => $fac["nit"],
                "nombre" => $fac["nombre"],
                "fecha" => $fac["fecha"],
                "monto" => $fac["monto"],
                "fechaViaje" => $fac["fecha_viaje"],
                "control" => $fac["codigo_control"],
                "estado" => $fac["estado"],
                "autorizacion" => $fac["autorizacion"],
                "info" => array(
                    "numSuc" => $empresa,
                    "impresor" => $fac["autoimpresor"],
                    "dir1" => $fac["direccion"],
                    "dir2" => $fac["direccion2"],
                    "ciudad" => $fac['ciudad'],
                    "nitE" => $nit,
                    "numFac" => $fac["numero_factura"],
                    "autorizacion" => $fac["autorizacion"],
                    "fecha" => $fac["fecha"],
                    "nombFact" => $fac["nombre"],
                    "nitFact" => $fac["nit"],
                    "total" => $fac["monto"],
                    "literal" => $nit,
                    "control" => $fac["codigo_control"],
                    "fechaLimite" => $fac["fecha_limite"],
                    "usuario" => $fac["identificador"],
                    "destino" => $fac["destino"],
                    "fechaViaje" => $fac["fecha_viaje"],
                    "horaViaje" => $fac["hora_viaje"],
                    "salida" => $fac["numero_bus"],
                    "carril" => $fac["carril"],
                    "modelo" => $fac["modelo"],
                    "empresa" => $empresa,
                    "asientos" => $fac["asientos"],
                    "telf" => $fac["telefono"])
            );
            $asientos = $asientoM->getByFactura($fac['id_factura']);
            $j = 0;
            foreach ($asientos as $as) {
                $lista["f" . $i]["pasajeros"]["a" . $j] = array("pasajero" => $as["nombre"], "nit" => $as['nit'], 'numero' => $as['numero']);
                $j++;
            }
            $i++;
        }
        $listaViajes = array("facturas" => $lista, "size" => count($lista));
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
