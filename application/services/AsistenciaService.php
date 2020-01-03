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
class App_Rest_AsistenciaService extends App_Rest_Server {

    public function ingreso($ci, $ip) {
        $asistenciaM = new App_Model_AsistenciaModel();
        try {
            $fecha = date("Y-m-d");
            $hora = date("H:m");
            $ci = base64_decode($ci);
            $person = $asistenciaM->getByCI($ci);
            if (!$person) {
                throw new Zend_Db_Exception("El documento registrado no es valido por favor verigfique la informacion", 1);
            }
            $asistenciaM->saveTX($person->id_persona, $ci, $fecha, $hora, $ip);

            $usuario = $asistenciaM->getLastByUser($ci);
            $data["user"] = array("nombres" => $usuario->nombres." ".$usuario->apellido_paterno, "time_in" => $usuario->time_in, "time_out" => $usuario->time_out, "date_in" => $usuario->date_in);
            $data["mensaje"] = "Su ingreso se marco satisfactoriamente POLOCHE";
            $data["error"] = false;
        } catch (Zend_Db_Exception $exc) {
            $exc->getTraceAsString();
            $data["mensaje"] = $exc->getMessage();
            $data["error"] = true;
        }


        $info = array("info" => $data, "horaActual" => date("H:i"));
        return App_Rest_Response::Generate($info, true);
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
    function salida($ci, $ip) {
        $asistenciaM = new App_Model_AsistenciaModel();
        try {
            $fecha = date("Y-m-d");
            $hora = date("H:m");
            $ci = base64_decode($ci);
            $person = $asistenciaM->getByCI($ci);
            if (!$person) {
                throw new Zend_Db_Exception("El documento registrado no es valido por favor verigfique la informacion", 1);
            }
            $asistenciaM->updateSalida($person->id_persona, $ci, $fecha, $hora, $ip);
            
            $usuario = $asistenciaM->getLastByUser($ci);
            $data["user"] = array("nombres" => $usuario->nombres, "time_in" => $usuario->time_in, "time_out" => $usuario->time_out, "date_in" => $usuario->date_in);
            $data["mensaje"] = "Su salida se marco satisfactoriamente ";
            $data["error"] = false;
        } catch (Zend_DB_Exception $exc) {
            $exc->getTraceAsString();
            $data["mensaje"] = $exc->getMessage();
            $data["error"] = true;
        }

        $info = array("info" => $data, "horaActual" => date("H:i"));
        return App_Rest_Response::Generate($info, true);
    }

    /**
     * recupera la lista de usuarios que marcaron asistencia
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version 1.2
     * @date creation 2012-04-11
     */
    function lista($fecha) {
        $asistenciaM = new App_Model_AsistenciaModel();
        $persons = $asistenciaM->getByDate($fecha);
        $lista = array();
        $i = 0;
        foreach ($persons as $asistencia) {
            $lista["a" . $i] = array(
                "id" => base64_encode($asistencia->id_persona),
                "nombre" => $asistencia->nombres,
                "apellidoPaterno" => $asistencia->apellido_paterno,
                "ingreso" => $asistencia->time_in,
                "ip" => $asistencia->ip,
                "salida" => $asistencia->time_out
            );
            $i++;
        }

        $listaManifiestos = array("lista" => $lista, "horaActual" => date("H:i"), "size" => count($lista));
        return App_Rest_Response::Generate($listaManifiestos, true);
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
    public function getTime() {
        $viajeModel = new App_Model_Viaje();
        $hora = $viajeModel->getTime();
        $lista = array("time" => $hora);
        $date = new Zend_Date();
        $date->setTimezone('America/Argentina/Buenos_Aires');
        $fecha = ucwords($date->toString("EEEE, dd 'de' MMMM 'de' yyyy"));
        $fecha = str_ireplace("De", "de", $fecha);
        $listaViajes = array("horaServer" => $lista, "horaActual" => date("H:i"), "size" => count($lista), "fechaActual" => $fecha);
        return App_Rest_Response::Generate($listaViajes, true);
    }

}

?>
