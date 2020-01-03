<?php

/**
 * Viaje
 *
 * @author Administrador
 * @version
 */
require_once 'Zend/Db/Table/Abstract.php';

class App_Model_Ciudad extends Zend_Db_Table_Abstract {

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
    function getSucursalesByCiudad($ciudad) {
        $db = $this->getAdapter();
        $select = $db->select();
        
        $select->distinct(true);
        $select->from(array('s' => 'sucursal'), array("id_sucursal", "nombre", "telefono","abreviacion","ciudad"));
        $select->join(array('c'=>'ciudad'),"c.id_ciudad=s.ciudad AND s.estado='Activo'",array("nombre AS ciudadNombre"));
        $select->where("s.ciudad='$ciudad'");
        $select->order("s.nombre");
//      echo $select->__toString();
        $log = Zend_Registry::get("log");
        $log->info("SQL :: " . $select->__toString());
        return $db->fetchAll($select);
    }

}
