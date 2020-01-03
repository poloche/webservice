<?php

/**
 * Viaje
 *
 * @author Administrador
 * @version
 */
require_once 'Zend/Db/Table/Abstract.php';

class App_Model_BusModel extends Zend_Db_Table_Abstract {

    /**
     * The default table name
     */
    protected $_name = 'ciudad';
    protected $_primary = 'id_ciudad';

    /**
     * recupera todas las sucursales de una ciudad
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version beta rc1
     * @date $(now)
     */
    function getByNumero($numero) {
        $db = $this->getAdapter();
        $db->setFetchMode ( Zend_Db::FETCH_OBJ );
        $select = $db->select();
        $select->distinct(true);
        $select->from(array('b' => 'bus'), array("id_bus", "numero", "placa"));
        $select->joinInner(array('m' => 'modelo'), "m.id_modelo = b.modelo", array("descripcion"));
        $select->joinInner(array('pb' => 'persona_bus'), "pb.bus = b.id_bus", null);
        $select->joinInner(array('p' => 'persona'), "pb.persona=p.id_persona", array("nombres","apellido_paterno","apellido_materno","documento"));
        $select->where("b.numero=?",$numero);
//      echo $select->__toString();
        return $db->fetchRow($select);
    }

    /**
     * Recupera la informacion de todos los buses de un propietario
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version   
     * @date 2011-01-17
     */
    function getByPropietario($propietario) {
        $db = $this->getAdapter();
        $db->setFetchMode ( Zend_Db::FETCH_OBJ );
        $select = $db->select();
        $select->distinct(true);
        $select->from(array('b' => 'bus'), array("id_bus", "numero", "placa"));
        $select->joinInner(array('m' => 'modelo'), "m.id_modelo = b.modelo", array("descripcion"));
        $select->joinInner(array('pb' => 'persona_bus'), "pb.bus = b.id_bus", null);
        $select->joinInner(array('p' => 'persona'), "pb.persona=p.id_persona", array("nombres","apellido_paterno","apellido_materno","documento"));
        $select->where("p.id_persona=?",$propietario);
        $select->where("b.estado=?","Activo");
//      echo $select->__toString();
        return $db->fetchAll($select);
    }
}
