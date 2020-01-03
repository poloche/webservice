<?php

/**
 * Viaje
 *
 * @author Administrador
 * @version
 */
require_once 'Zend/Db/Table/Abstract.php';

class App_Model_ConfiguracionSistemaModel extends Zend_Db_Table_Abstract {

    /**
     * The default table name
     */
    protected $_name = 'configuracion_sistema';
//    protected $_primary = 'id_factura';
    protected $_sequence =false;

    /**
     * recupera todas las configuraciones del sistema
     *@param $numero = numero de la factura
     * 
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version beta rc1
     * @date $(now)
     */
    function getAll() {
        $db = $this->getAdapter();
//        $db->setFetchMode ( Zend_Db::FETCH_OBJ );
        $select = $db->select();
        $select->distinct(true);
        $select->from(array('cs' => $this->_name), array("key", "value"));
        $select->order("cs.key");
//      echo $select->__toString();
        $log = Zend_Registry::get("log");
        $log->info("recuperando sucursales :" . $select->__toString());
        return $db->fetchAssoc($select);
    }
    
    /**
     * recupera una configuracion por su nombre
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version 1.2
     * @date creation 2011-09-29
     */
    function getByKey($key) {
        $db = $this->getAdapter();
        $select = $db->select();
        $select->distinct(true);
        $select->from(array('cs' => $this->_name), array("value"));
        $select->where("cs.key='$key'");
//      echo $select->__toString();
        $log = Zend_Registry::get("log");
        $log->info("recuperando sucursales :" . $select->__toString());
        return $db->fetchOne($select);
        
    }
    

}
