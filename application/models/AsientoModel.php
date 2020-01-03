<?php

/**
 * Viaje
 *
 * @author Administrador
 * @version
 */
require_once 'Zend/Db/Table/Abstract.php';

class App_Model_AsientoModel extends Zend_Db_Table_Abstract {

    /**
     * The default table name
     */
    protected $_name = 'asiento';
    protected $_primary = 'id_asiento';

    /**
     * Recupera el numero de asientos Disponibles (Reserva o Vacante)
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version beta rc1
     * @date creation 25/06/2009
     */
    public function getPasajeros($desde, $hasta, $nombres) {
        $db = $this->getAdapter();
//	$db->setFetchMode(Zend_Db::FETCH_OBJ);
        $select = $db->select();
        $select->from(array('a' => 'asiento'), array('nombre', 'nit', 'numero_factura', 'fecha_venta'));
        $select->joinInner(array('i' => 'item'), 'i.id_item=a.item', array("numero AS asiento"));
        $select->joinInner(array('v' => 'viaje'), 'v.id_viaje=a.viaje', array('fecha AS fecha_viaje'));
        $select->where("v.fecha BETWEEN '$desde' AND '$hasta'");
        $select->where("a.nombre ilike %?%", $busqueda);
//		echo $select->__toString();
        $log = Zend_Registry::get("log");
        $log->info($select->__toString());
        return $db->fetchAll($select);
    }

    /**
     * Recupera todos los pasajeros que tuvieran un viaje en un destino en un rango de fechas 
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version 1.2
     * @date creation 2011-09-30
     */
    function getPasajerosByDestinoFechas($destino, $desde, $hasta, $nombres) {
        $db = $this->getAdapter();
//	$db->setFetchMode(Zend_Db::FETCH_OBJ);
        $select = $db->select();
        $select->from(array('a' => 'asiento'), array('nombre', 'nit', 'numero_factura', 'fecha_venta','hora_venta'));
        $select->joinInner(array('i' => 'item'), 'i.id_item=a.item', array("numero AS asiento"));
        $select->joinInner(array('v' => 'viaje'), 'v.id_viaje=a.viaje', array('fecha AS fecha_viaje','hora AS hora_viaje'));
        $select->joinInner(array('d' => 'destino'), 'd.id_destino=v.destino', null);
        $select->joinInner(array('co' => 'ciudad'), 'co.id_ciudad=d.salida', array('nombre AS origen'));
        $select->joinInner(array('cd' => 'ciudad'), 'cd.id_ciudad=d.llegada', array('nombre AS destino'));
        $select->where("v.fecha BETWEEN '$desde' AND '$hasta'");
        $select->where("a.nombre ilike ? ", '%' . $nombres . '%');
        if ($destino != "0" && $destino != "") {
            $select->where("d.id_destino =?", $destino);
        }
//		echo $select->__toString();
        $log = Zend_Registry::get("log");
        $log->info($select->__toString());
        return $db->fetchAll($select);
    }

    /**
     * Recupera todos los asientos de una factura
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version 1.2
     * @date creation 2011-09-29
     */
    function getByFactura($factura) {
        $db = $this->getAdapter();
//        $db->setFetchMode(Zend_Db::FETCH_OBJ);
        $select = $db->select();
        $select->from(array('a' => $this->_name), array("id_asiento", "nombre", "nit", "pasaje", "numero_factura"));
        $select->joinInner(array("i" => "item"), "i.id_item=a.item", array("numero"));
        $select->where("a.factura=?", $factura);
        $log = Zend_Registry::get("log");
        $log->info($select->__toString());
        return $db->fetchAll($select);
    }

}
