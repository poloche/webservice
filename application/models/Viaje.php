<?php

/**
 * Viaje
 *
 * @author Administrador
 * @version
 */
require_once 'Zend/Db/Table/Abstract.php';

class App_Model_Viaje extends Zend_Db_Table_Abstract {

    /**
     * The default table name
     */
    protected $_name = 'viaje';
    protected $_primary = 'id_viaje';

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
    public function asientosDisponibles($idviaje) {
        $db = $this->getAdapter();
        //		$db->getFetchMode(Zend_Db::FETCH_OBJ);
        $select = $db->select();
        $select->from(array('a' => 'asiento'), array('count(*)'));
        $select->where('viaje=?', $idviaje);
        $select->where("(estado='Vacante' OR estado='Reserva')");
        $results = $db->fetchOne($select);
        return $results;
    }

    /**
     *  recupera el itinerario con salidas reales
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version V1.0
     * @date 24-02-2010
     */
    function getItinerarioActual($fecha, $ciudadOrigen, $ciudadDestino) {
        $db = $this->getAdapter();
//        $db->setFetchMode ( Zend_Db::FETCH_OBJ );
        $select = $db->select();
        $select->distinct(true);
        $select->from(array('v' => 'viaje'), array("id_viaje", "to_char(hora,'HH24:MI') as hora", "pasaje"));
        $select->joinInner(array('d' => 'destino'), "v.destino=d.id_destino", null);
        $select->joinInner(array('b' => 'bus'), "v.bus=b.id_bus", array("numero"));
        $select->joinInner(array('m' => 'modelo'), "b.modelo = m.id_modelo", array('m.descripcion'));
        $select->where("d.salida='$ciudadOrigen' and d.llegada='$ciudadDestino' and v.fecha='$fecha' AND v.estado='Activo'");
        $select->order('hora');
        $select->order('m.descripcion');
        $select->order('v.id_viaje');
        $select->order('v.pasaje');
        $select->order('b.numero');
//      echo $select->__toString();
        return $db->fetchAll($select);
    }

    /**
     *  recupera el itinerario con salidas de un origen sin importar el destino en una fecha dada
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version V1.0
     * @date 2011-10-19
     */
    function getItinerarioByOrigenFecha($fecha, $ciudadOrigen) {
        $db = $this->getAdapter();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);
        $select = $db->select();
        $select->distinct(true);
        $select->from(array('v' => 'viaje'), array("id_viaje", "to_char(hora,'HH24:MI') as hora", "pasaje", 'v.destino'));
        $select->joinInner(array('d' => 'destino'), "v.destino=d.id_destino", null);
        $select->joinInner(array('co' => 'ciudad'), "co.id_ciudad=d.salida", array("nombre AS origen"));
        $select->joinInner(array('cd' => 'ciudad'), "cd.id_ciudad=d.llegada", array("nombre AS destino"));
        $select->joinInner(array('b' => 'bus'), "v.bus=b.id_bus", array("numero"));
        $select->joinInner(array('m' => 'modelo'), "b.modelo = m.id_modelo", array('m.descripcion'));
        $select->where("d.salida='$ciudadOrigen' AND v.fecha='$fecha' AND v.estado='Activo'");
        $select->order('v.destino');
        $select->order('hora');
        $select->order('v.id_viaje');
        $select->order('v.pasaje');
        $select->order('b.numero');
//      echo $select->__toString();
        return $db->fetchAll($select);
    }

    /**
     * recupera el itinerario de los buses de un propietario
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version   
     * @date 2010-29-12
     */
    function getItinerarioPropietario($fecha, $propietario) {
        $db = $this->getAdapter();
//        $db->setFetchMode ( Zend_Db::FETCH_OBJ );
        $select = $db->select();
        $select->distinct(true);
        $select->from(array('v' => 'viaje'), array("id_viaje", "fecha", "to_char(hora,'HH24:MI') as hora", "pasaje", "numero_salida"));
        $select->joinInner(array('d' => 'destino'), "v.destino=d.id_destino", null);
        $select->joinInner(array('co' => 'ciudad'), "d.salida=co.id_ciudad", array("nombre AS origen"));
        $select->joinInner(array('cd' => 'ciudad'), "d.llegada=cd.id_ciudad", array("nombre AS destino"));
        $select->joinInner(array('b' => 'bus'), "v.bus=b.id_bus", array("numero", "placa"));
        $select->joinInner(array('pb' => 'persona_bus'), "pb.bus = b.id_bus", null);
        $select->where("v.fecha='$fecha' and pb.persona='$propietario'");
        $select->order('hora');
        $select->order('v.id_viaje');
        $select->order('b.numero');
//      echo $select->__toString();
        return $db->fetchAll($select);
    }

    /**
     * recupera los anifiestos con origen y destino especificados
     * que ademas no esten marcados como recepcionados
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version beta rc1
     * @date $(now)
     * TODO : esto no pertenece a esta clase y debera ser borrado
     * esto solo se puede hacer desde el modulo de venta de pasajes
     */
    function getManifiestos($fecha, $origen, $destino) {
        $db = $this->getAdapter();
//        $db->setFetchMode ( Zend_Db::FETCH_OBJ );
        $select = $db->select();
        $select->distinct(true);
        $select->from(array('m' => 'manifiesto'), array("id_manifiesto", "fecha","(select count(*) from manifiesto mm join encomienda ee on ee.manifiesto=mm.id_manifiesto and upper(ee.estado)='ENVIADO' where mm.id_manifiesto=m.id_manifiesto)  as nroEncomiendas"));
        $select->joinInner(array('v' => 'viaje'), "v.id_viaje=m.viaje", null);
        $select->joinInner(array('d' => 'destino'), "d.id_destino=v.destino", null);
        $select->joinInner(array('c' => 'ciudad'), "c.id_ciudad=d.llegada", array('nombre as destino'));
        $select->joinInner(array('b' => 'bus'), "b.id_bus=m.bus", array("numero as interno"));
        $select->joinInner(array('p' => 'persona'), "p.id_persona=m.despachador", array("nombres"));
        $select->joinLeft(array('cho' => 'chofer'), "cho.id_chofer= m.chofer", array('nombre_chofer'));
        $select->where("m.origen='$origen' and m.destino='$destino'");
        if ($fecha != "") {
            $select->where("m.fecha=?", $fecha);
        }
        $select->order('interno');

//      echo $select->__toString();
        $log = Zend_Registry::get("log");
        $log->info("recuperando manifiestos :" . $select->__toString());
        return $db->fetchAll($select);
    }

    /**
     * Elimina o da de baja un recibo dependiendo de que tipo sea
     * haciendolo inactivo
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
    function getModelItems($bus, $hora, $fecha) {
        $db = $this->getAdapter();
//		$db->getFetchMode(Zend_Db::FETCH_OBJ);
        $select = $db->select();
        $select->from(array('i' => 'item'), array('id_item', 'posicion_x as x', 'posicion_y as y', 'numero'));
        $select->joinInner(array('ti' => 'tipo_item'), 'i.tipo_item=ti.id_tipo_item', array('nombre'));
        $select->joinInner(array('p' => 'piso'), 'p.id_piso=i.piso', null);
        $select->joinInner(array('t' => 'tipo'), 't.id_tipo=p.tipo', null);
        $select->joinInner(array('m' => 'modelo'), 'm.tipo=t.id_tipo', null);
        $select->joinInner(array('b' => 'bus'), 'b.modelo=m.id_modelo', null);
        $select->where('b.numero=?', $bus);
//		echo $select->__toString();
        return $db->fetchAll($select);
    }

    /**
     * Recupera las lista de asientos, sus posiciones, el estado, el numero  y el tipo de item 
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version 1.2
     * @date creation 2011-10-19
     */
    function getItemsByViaje($interno, $viaje) {
        $db = $this->getAdapter();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);
        $select = $db->select();
        $select->from(array('i' => 'item'), array('id_item', 'piso', 'posicion_x as x', 'posicion_y as y', 'tipo_item', 'numero'));
        $select->joinInner(array('ti' => 'tipo_item'), 'i.tipo_item=ti.id_tipo_item', array('nombre'));
        $select->joinLeft(array('p' => 'piso'), 'p.id_piso=i.piso', null);
        $select->joinLeft(array('t' => 'tipo'), 't.id_tipo=p.tipo', null);
        $select->joinInner(array('m' => 'modelo'), 'm.tipo=t.id_tipo', array("descripcion"));
        $select->joinInner(array('b' => 'bus'), "b.modelo=m.id_modelo and b.numero='$interno'", array('numero as interno', 'id_bus'));
        $select->joinLeft(array('a' => 'asiento'), "a.item=i.id_item and a.viaje='$viaje'", array('id_asiento as idAsiento', 'estado', 'pasaje', 'nombre as pasajero', 'nit', 'numero_factura', 'vendedor', 'sucursal'));
        $select->joinLeft(array('v' => 'viaje'), "a.viaje=v.id_viaje and v.id_viaje='$viaje' ", array('fecha', 'hora', 'oficina'));
        $select->joinLeft(array('b1' => 'bus'), "b1.id_bus=v.bus", null);
        $select->joinLeft(array('pe' => 'persona'), "a.vendedor=pe.id_persona and a.viaje='$viaje'", array('nombres as vendedor'));
        $log = Zend_Registry::get("log");
        $log->info("Query getItems :" . $select->__toString());
        return $db->fetchAll($select);
    }

    function getPasajeros($desde, $hasta, $busqueda) {
        $db = $this->getAdapter();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);
        $select = $db->select();
        $select->from(array('a' => 'asiento'), array('pasajero', 'nit', 'numero_factura', 'fecha'));
        $select->joinInner(array('i' => 'item'), 'i.id_item=a.item', array("numero AS asiento"));
        $select->joinInner(array('v' => 'viaje'), 'v.id_viaje=a.viaje', array('fecha AS fecha_viaje'));
        $select->where("v.fecha BETWEEN '$desde' AND '$hasta'");
        $select->where("a.pasajero ilike '%$busqueda%'");
//		echo $select->__toString();
        return $db->fetchAll($select);
    }

    /**
     * Recupera los choferes que realizaron un viaje
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version 1.2
     * @date creation 2011-10-21
     */
    function getChoferes($viaje) {
        $db = $this->getAdapter();
        $select = $db->select();
        $select->from(array('v' => 'viaje'), array('id_viaje'));
        $select->joinInner(array('cv' => 'chofer_viaje'), 'v.id_viaje=cv.viaje', array("cargo"));
        $select->joinInner(array('ch' => 'chofer'), 'cv.chofer=ch.id_chofer', array('ch.id_chofer', 'ch.numero_licencia', 'ch.nombre_chofer', 'ch.telefono',
            'ch.fecha_exp'));
        $select->where('v.id_viaje=?', $viaje);
        $select->where('ch.estado=?', 'Activo');
        $select->order("cv.cargo");
        $results = $db->fetchAll($select);
        return $results;
    }

    /**
     * getTimeServer
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version 1.2
     * @date creation 2012-04-12
     */
    function getTime() {
        $db = $this->getAdapter();
        $sql = "SELECT current_time";
        $results = $db->fetchOne($sql);
        ;
        return $results;
    }

}
