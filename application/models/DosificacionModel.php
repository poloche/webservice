<?php

/**
 * Viaje
 *
 * @author Administrador
 * @version
 */
require_once 'Zend/Db/Table/Abstract.php';

class App_Model_DosificacionModel extends Zend_Db_Table_Abstract {

    /**
     * The default table name
     */
    protected $_name = 'datos_factura';
    protected $_primary = 'id_datos_factura';

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
    function getByCiudad($ciudad) {
        $db = $this->getAdapter();
//        $db->setFetchMode ( Zend_Db::FETCH_OBJ );
        $select = $db->select();
        $select->distinct(true);
        $select->from(array('d' => $this->_name), array("id_datos_factura", "autorizacion", "fecha_limite"));
        $select->joinInner(array('s'=>'sucursal'),"s.id_sucursal=d.sucursal",array('nombre'));
        $select->where("s.ciudad='$ciudad'");
        $select->order("s.nombre");
        $select->order("d.fecha_limite");
//      echo $select->__toString();
        $log = Zend_Registry::get("log");
        $log->info("recuperando sucursales :" . $select->__toString());
        return $db->fetchAll($select);
    }

}
