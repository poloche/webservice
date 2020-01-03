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
class App_Rest_BusService extends App_Rest_Server {

    public function dataBus($userid, $apikey, $interno) {
        $busM = new App_Model_BusModel();
        $bus = $busM->getByNumero($interno);
        $dataBus = array();
        $dataBus["bus"] = array(
            "numero" => $bus->numero,
            "placa" => $bus->placa,
            "nb" => $bus->nombres,
            "md" => $bus->modelo,
            "ap" => $bus->apellido_paterno,
            "am" => $bus->apellido_materno,
            "doc" => $bus->documento
        );
        $date = new Zend_Date();
        $date->setTimezone('America/Argentina/Buenos_Aires');
        $fecha = ucwords($date->toString("EEEE, dd 'de' MMMM 'de' yyyy"));
        $fecha = str_ireplace("De", "de", $fecha);
        $listaViajes = array("viajes" => $dataBus, "horaActual" => date("H:i"), "size" => count($dataBus), "fechaActual" => $fecha);
        return App_Rest_Response::Generate($listaViajes, true);
    }

    /**
     * recupera informacion del modelo de un bus en base a su numero interno
     * hora de viaje y destino del mismo
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version v1.1
     * @date $(date)
     * @date $(time)
     *
     */
    function modelo($userid, $apikey, $interno, $hora, $destino) {
        $viajeM = new App_Model_Viaje();
        $modeloItems = $viajeM->getModelItems($interno, $hora, $destino);
        $lista = array();
        $i = 0;
        foreach ($modeloItems as $man) {
            $tipo = $this->getTipo(strtolower($man["nombre"]));
            $lista["a" . $i] = array(
                "x" => $man["x"],
                "y" => $man["y"],
                "t" => $tipo,
                "n" => $man["numero"]);
//            $x = strlen($man["x"])==1?"0".$man["x"]:$man["x"];
//            $y = strlen($man["y"])==1?"0".$man["y"]:$man["y"];
//            $t = $tipo;
//            if(is_null($man['numero']) || $man['numero']==""){
//                $n="00";
//            }elseif(strlen($man["numero"])==1){
//                $n="0".$man["numero"];
//            }else{
//                $n = $man["numero"];
//            }
//            $lista["a".$i] = array(
//                "x" => $x.$y.$t.$n
//            );
            $i++;
        }

        $listaManifiestos = array("modelo" => $lista, "horaActual" => date("H:i"), "size" => count($lista));

        return App_Rest_Response::Generate($listaManifiestos, true);
    }

    /**
     * retorna un numero de acuerdo al tipo
     * 0 : vacio
     * 1 : asiento
     * 2 : television
     * 3 : direccion
     * 4 : entrada
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version v1.1
     * @date $(date)
     * @date $(time)
     *
     */
    function getTipo($nombre) {
        $resp = 0;
        switch ($nombre) {
            case "asiento":
                $resp = 1;
                break;
            case "televisor":
                $resp = 2;
                break;
            case "direccion":
                $resp = 3;
                break;
            case "entrada":
                $resp = 4;
                break;
            case "primer piso":
                $resp = 5;
                break;
            default:
                $resp = 0;
        }
        return $resp;
    }

    /**
     * recupera la informacion de un manifiestos enviados
     * desde la ciudad de origen a la ciudad de destino en la fecha especificada
     * que ademas este con estado Enviado
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version beta rc1
     * @date $(now)
     */
    function manifiestos($userid, $apikey, $fecha, $origen, $destino) {
        $viajeM = new App_Model_Viaje();
        $manifiestos = $viajeM->getManifiestos($fecha, $origen, $destino);
        $lista = array();
        foreach ($manifiestos as $man) {
            $lista[$man["id_manifiesto"]] = array(
                "id" => base64_encode($man["id_manifiesto"]),
                "interno" => $man["interno"],
                "fecha" => $man["fecha"],
                "encargado" => $man["nombres"],
                "chofer" => $man["nombre_chofer"]
            );
        }

        $listaManifiestos = array("manifiestos" => $lista, "horaActual" => date("H:i"), "size" => count($lista));
        return App_Rest_Response::Generate($listaManifiestos, true);
    }

    /**
     * Retorna toda la informacion del itinerario de viajes a todos los destinos 
     * de un propietario
     * Enfocado al sistema de control de buses
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version   
     * @date 2010-12-29
     */
    function itinerarioControl($userid, $apikey, $fecha, $propietario) {
        $viajeM = new App_Model_Viaje();
        $viajes = $viajeM->getItinerarioPropietario($fecha, $propietario);
        $lista = array();
        foreach ($viajes as $viaje) {
            $lista[$viaje["id_viaje"]] = array(
                "id" => $viaje["id_viaje"],
                "origen" => $viaje["origen"],
                "destino" => $viaje["destino"],
                "fecha" => $viaje["fecha"],
                "hora" => $viaje["hora"],
                "salida" => $viaje["numero_salida"],
                "numero" => $viaje["numero"],
                "placa" => $viaje["placa"]
            );
        }
//        usleep(3000000);
        $date = new Zend_Date();
        $date->setTimezone('America/Argentina/Buenos_Aires');
        $fecha = ucwords($date->toString("EEEE, dd 'de' MMMM 'de' yyyy"));
        $fecha = str_ireplace("De", "de", $fecha);
        $listaViajes = array("viajes" => $lista, "horaActual" => date("H:i"), "size" => count($lista), "fechaActual" => $fecha);
        return App_Rest_Response::Generate($listaViajes, true);
    }

    /**
     * Manda informacion de los buses de un propietario para la sincronizacion
     * con el servidor 
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version   
     * @date 2011-01-17
     */
    function getAll($userid, $apikey, $propietario) {
        $busM = new App_Model_BusModel();
        $buses = $busM->getByPropietario($propietario);
        $listaBuses = array();
        $i = 0;
        foreach ($buses as $bus) {
            $listaBuses["a" . $i] = array(
                "id" => $bus->id_bus,
                "numero" => $bus->numero,
                "placa" => $bus->placa,
                "modelo" => $bus->modelo,
                "propietario" => array('nb' => $bus->nombres, 'app' => $bus->apellido_paterno, 'apm' => $bus->apellido_materno, 'doc' => $bus->documento)
            );
            $i++;
        }
        $date = new Zend_Date();
        $date->setTimezone('America/Argentina/Buenos_Aires');
        $fecha = ucwords($date->toString("EEEE, dd 'de' MMMM 'de' yyyy"));
        $fecha = str_ireplace("De", "de", $fecha);
        $busesEnviar = array("viajes" => $listaBuses, "horaActual" => date("H:i"), "size" => count($listaBuses), "fechaActual" => $fecha);
        return App_Rest_Response::Generate($busesEnviar, true);
    }

}

?>
