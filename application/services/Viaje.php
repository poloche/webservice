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
class App_Rest_Viaje extends App_Rest_Server {

    public function itinerario($userid, $apikey, $fecha, $origen, $destino) {
        $viajeM = new App_Model_Viaje();
        $viajes = $viajeM->getItinerarioActual($fecha, $origen, $destino);
        $lista = array();
        foreach ($viajes as $viaje) {
            $disponibles = $viajeM->asientosDisponibles($viaje["id_viaje"]);
            $lista[$viaje["id_viaje"]] = array(
                "id" => $viaje["id_viaje"],
                "hora" => $viaje["hora"],
                "interno" => $viaje["numero"],
                "modelo" => $viaje["descripcion"],
                "tarifa" => $viaje["pasaje"],
                "disponibles" => $disponibles
            );
        }
        $date = new Zend_Date();
        $date->setTimezone('America/Argentina/Buenos_Aires');
        $fecha = ucwords($date->toString("EEEE, dd 'de' MMMM 'de' yyyy"));
        $fecha = str_ireplace("De", "de", $fecha);
        $listaViajes = array("viajes" => $lista, "horaActual" => date("H:i"), "size" => count($lista), "fechaActual" => $fecha);
        return App_Rest_Response::Generate($listaViajes, true);
    }
    
    /**
     * Recupera toda la lista de viajes de un origen sin importar el destino en una fecha
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version 1.2
     * @date creation 2011-10-19
     */
    function listItinerary($fecha,$origen) {
        $viajeM = new App_Model_Viaje();
        $viajes = $viajeM->getItinerarioByOrigenFecha($fecha, $origen);
        $lista = array();
        $dest = 0;
        foreach ($viajes as $viaje) {
            $lista["v".$dest] = array(
                "id" => base64_encode($viaje->id_viaje),
                "hora" => $viaje->hora,
                "interno" => $viaje->numero,
                "modelo" => $viaje->descripcion,
                "ruta" => base64_encode($viaje->destino),
                "origen" => $viaje->origen,
                "destino" => $viaje->destino,
            );
            $dest++;
        }
        $date = new Zend_Date();
        $date->setTimezone('America/Argentina/Buenos_Aires');
        $fecha = ucwords($date->toString("EEEE, dd 'de' MMMM 'de' yyyy"));
        $fecha = str_ireplace("De", "de", $fecha);
        $listaViajes = array("viajes" => $lista, "horaActual" => date("H:i"), "size" => count($lista), "fechaActual" => $fecha);
        return App_Rest_Response::Generate($listaViajes, true);
    }
    
    /**
     * Recupera la planilla de un viaje con todos sus items y estado de ellos
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version 1.2
     * @date creation 2011-10-19
     */
    function planillaView($viaje,$bus) {
        $viajeM = new App_Model_Viaje();
        $modeloItems = $viajeM->getItemsByViaje($bus, $viaje);
        $lista = array();
        $i = 0;
        foreach ($modeloItems as $man) {
            $tipo = $this->getTipo(strtolower($man->nombre));
            $lista["a" . $i] = array(
                "x" => $man->x,
                "y" => $man->y,
                "t" => $tipo,
                "e" => $man->estado,
                "n" => $man->numero);
            $i++;
        }
        
        $choferes = $viajeM->getChoferes($viaje);
        foreach ($choferes as $chofer) {
            $listChofers["c".$i] =array(
                "n"=>$chofer->nombre_chofer,
                "l"=>$chofer->numero_licencia,
                "c"=>$chofer->cargo
            ); 
            $i++;
        }

        $listaManifiestos = array("modelo" => $lista, "horaActual" => date("H:i"), "size" => count($lista),"choferes"=>$listChofers);

        return App_Rest_Response::Generate($listaManifiestos, true);
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
            $lista["m".$man["id_manifiesto"]] = array(
                "id" => base64_encode($man["id_manifiesto"]),
                "interno" => $man["interno"],
                "fecha" => $man["fecha"],
                "encargado" => $man["nombres"],
                "destino" => $man["destino"],
                "nroEncomiendas" => $man["nroEncomiendas"],
                "chofer" => $man["nombre_chofer"] == "" ? "N/A" : $man["nombre_chofer"]
            );
        }

        $listaManifiestos = array("manifiestos" => $lista, "horaActual" => date("H:i"), "size" => count($lista));
        return App_Rest_Response::Generate($listaManifiestos, true);
    }

    /**
     * recupera la informacion de los pasajeros 
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version beta rc1
     * @date $(now)
     */
    function busqueda($userid, $apikey, $desde, $hasta, $busqueda) {
        $viajeM = new App_Model_AsientoModel();
        $pasajeros = $viajeM->getPasajeros($desde, $hasta, $busqueda);
        $lista = array();
        $i = 0;
        foreach ($pasajeros as $pasajero) {
            $lista["a" . $i] = array(
                "nombre" => $pasajero["nombre"],
                "nit" => $pasajero["nit"],
                "f" => $pasajero["fecha_venta"],
                "h" => $pasajero["hora_venta"]
            );
        }

        $listaManifiestos = array("pasajeros" => $lista, "horaActual" => date("H:i"), "size" => count($lista));
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
     * Busca todos los destinos a los cuales salen los viajes de una ciudad origen
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version 1.2
     * @date creation 2011-09-30
     */
    function destinationsList($origen) {
        $viajeM = new App_Model_DestinoModel();
        $pasajeros = $viajeM->getDestinos($origen);
        $lista = array();
        $i = 0;
        foreach ($pasajeros as $pasajero) {
            $lista["a" . $i] = array(
                "id" => base64_encode($pasajero["id_destino"]),
                "origen" => $pasajero["origen"],
                "idCO" => base64_encode($pasajero["idCO"]),
                "idCD" => base64_encode($pasajero["idCD"]),
                "destino" => $pasajero["destino"],
            );
            $i++;
        }

        $listaManifiestos = array("destinos" => $lista, "horaActual" => date("H:i"), "size" => count($lista));
        return App_Rest_Response::Generate($listaManifiestos, true);
    }

    /**
     * Busca pasajeros en un rango de fechas con un destino espesifico
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version 1.2
     * @date creation 2011-09-30
     */
    function findPassenger($destino, $desde, $hasta, $nombres) {

        $viajeM = new App_Model_AsientoModel();

        $pasajeros = $viajeM->getPasajerosByDestinoFechas($destino, $desde, $hasta, $nombres);
        $lista = array();
        $i = 0;
        foreach ($pasajeros as $pasajero) {
            $lista["a" . $i] = array(
                "nombre" => $pasajero["nombre"],
                "nit" => $pasajero["nit"],
                "fecha" => $pasajero["fecha_venta"],
                "hora" => $pasajero["hora_venta"],
                "horaViaje" => $pasajero["hora_viaje"],
                "origen" => $pasajero["origen"],
                "destino" => $pasajero["destino"],
                "fechaViaje" => $pasajero["fecha_viaje"]
            );
            $i++;
        }
        

        $listaManifiestos = array("pasajeros" => $lista, "horaActual" => date("H:i"), "size" => count($lista));
        return App_Rest_Response::Generate($listaManifiestos, true);
    }

}

?>
