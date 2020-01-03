<?php

/**
 * Viaje
 *
 * @author Administrador
 * @version
 */
require_once 'Zend/Db/Table/Abstract.php';

class App_Model_FacturaModel extends Zend_Db_Table_Abstract {

    /**
     * The default table name
     */
    protected $_name = 'factura';
    protected $_primary = 'id_factura';

    /**
     * recupera todas las factura con el numero indicado
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
    function getByNumero($numero) {
        $db = $this->getAdapter();
//        $db->setFetchMode ( Zend_Db::FETCH_OBJ );
        $select = $db->select();
        $select->distinct(true);
        $select->from(array('f' => $this->_name), array("id_factura", "numero_factura", "nit","nombre","fecha","monto","fecha_viaje","codigo_control","estado","fecha_viaje","hora_viaje","modelo","numero_bus","asientos",'fecha_limite'));
        $select->joinInner(array('d'=>'datos_factura'),"d.id_datos_factura=f.dosificacion",array('autorizacion','autoimpresor'));
        $select->joinInner(array('p'=>'persona'),"p.id_persona=f.vendedor",array('identificador'));
        $select->joinInner(array('s'=>'sucursal'),"s.id_sucursal=p.sucursal",array('nombre AS sucursal','telefono','direccion','direccion2','carril'));
        $select->joinInner(array('c'=>'ciudad'),"c.id_ciudad=s.ciudad",array('nombre AS ciudad'));
//        $select->joinInner(array('s'=>'sucursal'),"s.id_sucursal=d.sucursal",array('nombre'));
        $select->where("f.numero_factura='$numero'");
        $select->order("f.fecha");
//      echo $select->__toString();
        $log = Zend_Registry::get("log");
        $log->info("recuperando sucursales :" . $select->__toString());
        return $db->fetchAll($select);
    }
    /**
     * recupera todas las facturas con el numero indicado en la dosificacion indicada
     *
     * @param $numero
     * @param $dosificacion
     * 
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version beta rc1
     * @date $(now)
     */
    function getByNumeroDosificacion($numero,$dosificacion) {
        $db = $this->getAdapter();
//        $db->setFetchMode ( Zend_Db::FETCH_OBJ );
        $select = $db->select();
        $select->distinct(true);
        $select->from(array('f' => $this->_name), array("id_factura", "numero_factura", "nit","nombre","fecha","monto","fecha_viaje","codigo_control","estado"));
        $select->joinInner(array('d'=>'datos_factura'),"d.id_datos_factura=f.dosificacion",array('autorizacion'));
        $select->where("f.numero_factura=$numero");
        $select->where("f.dosificacion='$dosificacion'");
        $select->order("f.fecha");
//      echo $select->__toString();
        $log = Zend_Registry::get("log");
        $log->info("recuperando sucursales :" . $select->__toString());
        return $db->fetchAll($select);
    }

}
