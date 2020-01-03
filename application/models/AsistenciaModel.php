<?php

/**
 * Viaje
 *
 * @author Administrador
 * @version
 */
require_once 'Zend/Db/Table/Abstract.php';

class App_Model_AsistenciaModel extends Zend_Db_Table_Abstract {

    /**
     * The default table name
     */
    protected $_name = 'attendance';
    protected $_primary = 'attendance_id';
    protected $_sequence = false;

    /**
     * registra la hora de entrada y salida segun corresponda de una persona
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version beta rc1
     * @date creation 20/08/2009
     */
    function saveTX($persona, $ci, $fecha, $hora, $Ip) {
        $db = $this->getAdapter();
        $db->beginTransaction();
        try {
            $dataAttendance["attendance_id"] = "nuevo";
            $dataAttendance["person"] = $persona;
            $dataAttendance["date_in"] = $fecha;
            $dataAttendance["time_in"] = $hora;
            $dataAttendance["ip"] = $Ip;
            $dataAttendance["source"] = "Cedula de Identidad";
            $dataAttendance["detail"] = $ci;
            $dataAttendance["state"] = "Activo";
            $dataAttendance["time_reference"] = $hora;
            $dataAttendance["tolerance"] = 0;
            $this->insert($dataAttendance);
            $db->commit();
        } catch (Zend_Db_Exception $zdbe) {
            $db->rollBack();
            $log = Zend_Registry::get("log");
            $log->info($zdbe);
            throw new Zend_Db_Exception($zdbe->getMessage(), $zdbe->getCode());
        }
    }

    /**
     * Recupera la informacion de una persona por su carnet de identidad
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version   
     * @date 2011-03-28
     */
    function getByCI($ci) {
        $db = $this->getAdapter();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);
        $select = $db->select();
        $select->from(array('p' => 'persona'), array("id_persona", "nombres", "apellido_paterno", "apellido_materno", "identificador"));
        $select->where("documento=?", $ci);
        $log = Zend_Registry::get("log");
        $log->info($select->__toString());
        return $db->fetchRow($select);
    }

    /**
     * recupera todas las personas que hayan marcado asistencia en una fecha dada
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version 1.2
     * @date creation 2012-04-11
     */
    function getByDate($date) {
        $db = $this->getAdapter();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);
        $select = $db->select();
        $select->from(array('p' => 'persona'), array("id_persona", "nombres", "apellido_paterno", "apellido_materno", "identificador"));
        $select->join(array('a' => 'attendance'), "a.person=p.id_persona", array("date_in", "time_in", "date_out", "time_out", "tolerance", "ip"));
        $select->where("date_in=?", $date);
        $log = Zend_Registry::get("log");
        $log->info($select->__toString());
        return $db->fetchAll($select);
    }

    /**
     * recupera todas la ultima asistencia marcada por un usuario con su carnet de identidad
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version 1.2
     * @date creation 2012-04-11
     */
    function getLastByUser($ci) {
        $hoy = date("Y-m-d");

        $db = $this->getAdapter();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);
        $select = $db->select();
        $select->from(array('p' => 'persona'), array("id_persona", "nombres", "apellido_paterno", "apellido_materno", "identificador"));
        $select->join(array('a' => 'attendance'), "a.person=p.id_persona", array("date_in", "time_in", "date_out", "time_out", "tolerance", "ip"));
        $select->where("date_in=?", $hoy);
        $select->where("detail=?", $ci);
        $log = Zend_Registry::get("log");
        $log->info("*********************************************");
        $log->info($select->__toString());
        return $db->fetchRow($select);
    }

    /**
     * ..... description
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version beta rc1
     * @date creation 20/08/2009
     */
    function updateSalida($persona, $ci, $dateOut, $timeOut, $ip) {
        $db = $this->getAdapter();
        $db->beginTransaction();
        try {
            $hoy = date("Y-m-d");
            //$asitencia = $this->getLastByPersonCI($persona,$ci);
            $dataAttendance["date_out"] = $dateOut;
            $dataAttendance["time_out"] = "now";
            $where[] = "person='$persona'";
            $where[] = "detail='$ci'";
            $where[] = "time_in=(SELECT max(time_in) FROM attendance WHERE date_in='$hoy' AND person='$persona' )";
            if ($this->update($dataAttendance, $where) > 0) {
                $db->commit();
            } else {
                throw new Zend_Db_Exception("No se pudo registrar la hora de salida 2", 125);
            }
        } catch (Zend_Db_Exception $zdbe) {
            $db->rollBack();
            Initializer::log_error($zdbe);
            throw new Zend_Db_Exception("No se pudo registrar la hora de salida", 125);
        }
    }

}
