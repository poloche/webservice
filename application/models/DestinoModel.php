<?php

/**
 * Viaje
 *
 * @author Administrador
 * @version
 */
require_once 'Zend/Db/Table/Abstract.php';

class App_Model_DestinoModel extends Zend_Db_Table_Abstract {

    /**
     * The default table name
     */
    protected $_name = 'destino';
    protected $_primary = 'id_destino';

    /**
     * Recupera todos los destinos en una ciudad de origen
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version beta rc1
     * @date $(now)
     */
    function getDestinos($origen) {
        $db = $this->getAdapter();
//        $db->setFetchMode ( Zend_Db::FETCH_OBJ );
        $select = $db->select();
        $select->distinct(true);
        $select->from(array('d' => $this->_name), array("id_destino"));
        $select->joinInner(array('co' => 'ciudad'), "co.id_ciudad=d.salida", array('id_ciudad AS idCO', 'nombre AS origen'));
        $select->joinInner(array('cd' => 'ciudad'), "cd.id_ciudad=d.llegada", array('id_ciudad AS idCD', 'nombre AS destino'));
        $select->where("d.salida='$origen'");
        $select->order("cd.nombre");
//      echo $select->__toString();
        $log = Zend_Registry::get("log");
        $log->info("recuperando sucursales :" . $select->__toString());
        return $db->fetchAll($select);
    }

}
