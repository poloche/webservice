<?php

/**
 * Bus
 *
 * @author Administrador
 * @version
 */
require_once 'Zend/Db/Table/Abstract.php';

class App_Model_ManifiestoModel extends Zend_Db_Table_Abstract {

    /**
     * The default table name
     */
    protected $_name = 'manifiesto';
    protected $_primary = 'id_manifiesto';

    /**
     * recupera la informacion de un manifiesto por su identificador
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version 1.1
     * @date creation 2012-07-13 15:43
     */
    public function getById($idManifiesto) {
        $db = $this->getAdapter();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);
        $select = $db->select();
        $select->from(array('m' => 'manifiesto'), array('id_manifiesto', 'fecha', 'hora', 'despachador', 'viaje',
            'bus', 'chofer', 'total', 'destino', 'origen', 'estado', 'tipo'));
        $select->joinLeft(array("ch" => "chofer"), "ch.id_chofer=m.chofer", "nombre_chofer");
        $select->joinLeft(array("b" => "bus"), "b.id_bus=m.bus", "numero");
        $select->join(array("o" => "ciudad"), "o.id_ciudad=m.origen", "nombre as ciudadOrigen");
        $select->join(array("d" => "ciudad"), "d.id_ciudad=m.destino", "nombre as ciudadDestino");
        $select->where("id_manifiesto=?", $idManifiesto);
        $results = $db->fetchRow($select);
        return $results;
    }

    /**
     * Recupera todas las encomiendas que le pertencen a este manifiesto
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version beta rc1
     * @date $(now)
     */
    function getEncomiendasById($idMan) {
        $db = $this->getAdapter();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);
        $select = $db->select();
        $select->from(array('e' => 'encomienda'), array('id_encomienda', 'guia', 'fecha', 'detalle', 'remitente',
            'destinatario', 'total'));
        $select->where("manifiesto=?", $idMan);
        $select->where("estado=?", 'ENVIADO');
        $results = $db->fetchAll($select);
        return $results;
    }

    /**
     * Recupera todas las encomiendas que le pertencen a este manifiesto segun el estado de la encomienda
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version v1.2
     * @date 2012-07-13
     * 
     * @param $idManifiesto   identificador del manifiesto;
     * @param $estado         Estado de la encomienda ()
     * @param $estado         Estado de la encomienda ()
     */
    function getByManifiestoEstadoResponsable($idMan, $estado, $responsable) {
        $db = $this->getAdapter();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);
        $select = $db->select();
        $select->distinct(true);
        $select->from(array('e' => 'encomienda'), array('id_encomienda', 'guia', 'fecha', 'detalle', 'remitente',
            'destinatario', 'total', 'tipo'));
        $select->join(array('me' => 'movimiento_encomienda'), "me.encomienda=e.id_encomienda", null);
        $select->where("manifiesto=?", $idMan);
        $select->where("(movimiento='$estado' OR movimiento='TRASPASO')");
        $select->where("nombre_usuario=?", $responsable);
//        echo $select->__toString();
        $results = $db->fetchAll($select);

        return $results;
    }

}
