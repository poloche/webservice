<?php

/**
 * Viaje
 *
 * @author Administrador
 * @version
 */
require_once 'Zend/Db/Table/Abstract.php';

class App_Model_ItemEncomienda extends Zend_Db_Table_Abstract {

    /**
     * The default table name
     */
    protected $_name = 'item_encomienda';
    protected $_primary = 'id_item_encomienda';

    /**
     * Recupera todos los items de una encomienda
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
    function getByEncomienda($encomienda) {
        $db = $this->getAdapter();
        $db->setFetchMode ( Zend_Db::FETCH_OBJ );
        $select = $db->select();
        $select->distinct(true);
        $select->from(array('i' => 'item_encomienda'), array("id_item_encomienda","cantidad", "detalle", "monto", "peso"));
        $select->where("i.encomienda='$encomienda' AND i.estado='Activo'");
        
//      echo $select->__toString();
        $log = Zend_Registry::get("log");
        $log->info("recuperando manifiestos :" . $select->__toString());
        return $db->fetchAll($select);
    }

    /**
     * Registra en base de datos la lleda de las encomiendas a destino
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version beta rc1
     * @date $(now)
     */
    function saveArribo($encomiendas, $usuario, $sucursal, $manifiesto) {
        $db = $this->getAdapter();
        $db->beginTransaction();
        $log = Zend_Registry::get("log");
        $movimientoModel = new App_Model_MovimientoEncomienda();
        $manifiestoModel = new App_Model_ManifiestoModel();

        try {

            $fecha = date("Y-m-d");
            $hora = date("H:i:s");
//            $cabecera = array(
//                "titleEmp" => App_Util_Statics::$nombreEmpresa,
//                "nombreEmp" => App_Util_Statics::$nombreEmpresa2,
//                "nitEmp" => App_Util_Statics::$nitEmpresa,
//                "numeroSuc" => $suc->numero,
//                "nombSuc" => $suc->nombre,
//                "telefono" => $suc->telefono,
//                "dir1" => $suc->direccion,
//                "dir2" => $suc->direccion2,
//                "ciudad" => App_Util_Statics::$SISTEM_DEFAULTS["Ciudad_Sistema"],
//                "user" => $user->nombres,
//                "autoimpresor" => ""
//            );
            foreach ($encomiendas as $idEncomienda) {
                $idEncomienda = base64_decode($idEncomienda);
                $selectE = $db->select();
                $selectE->from(array("e" => "encomienda"), "sucursal_de");
                $selectE->where("id_encomienda=?", $idEncomienda);
                $sucDestino = $db->fetchOne($selectE);
//                $log->info("SQL: ".$selectE->__toString()."comparando traspaso sucDestino:".$sucDestino."  sucursal:".$sucursal);
                $estado = "TRASPASO";
                $obs = "Arribo a destino despues de un traspaso";
                if ($sucDestino != false && $sucDestino == $sucursal) {
                    $obs = "Arribo a destino sin problemas";
                    $estado = "ARRIBO";
                }
                $dataEncomienda = array(
                    "estado" => $estado
                );
                $where[] = "id_encomienda='" . $idEncomienda . "'";
                if ($this->update($dataEncomienda, $where) == 0) {
                    throw new Zend_Db_Exception("No se ha podido actualizar el estado una de las encomiendas ", 125);
                }
                $where = null;

                $movimiento = array(
                    "id_movimiento" => "nuevo",
                    "fecha" => $hoy,
                    "hora" => $hora,
                    "movimiento" => $estado,
                    "usuario" => $usuario,
                    "encomienda" => $idEncomienda,
                    "sucursal" => $sucursal,
//                "bus"=>"",
                    "observacion" => $obs,
//                "manifiesto"=>"",
                );

                $movimientoModel->insert($movimiento);
            }
            $select = $db->select();
            $select->from(array("e" => "encomienda"), "COUNT(*)");
            $select->where("manifiesto=?", $manifiesto);
            $select->where("estado=?", "ENVIADO");
            if ($db->fetchOne($select) == 0) {
                $updateMan["estado"] = "ENTREGADO";
                $manWhere[] = "id_manifiesto='$manifiesto'";
                if ($manifiestoModel->update($updateMan, $manWhere) == 0) {
                    throw new Zend_Db_Exception("No se ha podido actualizar el estado del manifiesto", 125);
                }
            }
            $db->commit();
            $resp = array("mensaje" => "ok");
            return $resp;
        } catch (Zend_Db_Exception $zdbe) {
            $db->rollBack();

            $log->info($zdbe);
            throw new Zend_Db_Exception($zdbe->getMessage(), 125);
        }
    }

    /**
     * Recupera la lista de encomiendas para ser entrgadas
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version beta
     * @date 2010-08-05
     *
     */
    function getForEntrega($fecha, $remitente, $guia) {
        $db = $this->getAdapter();
        $select = $db->select();
        $select->distinct(true);
        $select->from(array('e' => 'encomienda'), array("id_encomienda", "remitente", "guia", "detalle", "sucursal_de", "nombre_destino", "destinatario", "tipo", "fecha"));
        if (isset($fecha) && $fecha != "") {
            $select->where("e.fecha='$fecha'");
        }
        if (isset($remitente) && $remitente != "") {
            $select->where("e.remitente like '%" . strtoupper($remitente) . "%'");
        }
        if (isset($guia) && $guia != "" && $guia != "0") {
            $select->where("e.guia='$guia'");
        }
        $select->where("(UPPER(e.estado)='ARRIBO' OR UPPER(e.estado)='TRASPASO')");
//        $select->orWhere("UPPER(e.estado)=?", 'TRASPASO');
        $select->order("nombre_destino");
//      echo $select->__toString();
        $log = Zend_Registry::get("log");
        $log->info("recuperando encomiendas lista para entregar :" . $select->__toString());
        return $db->fetchAll($select);
    }

    /**
     * Lista la informacion de encomiendas que se hayan recebido en esta ciudad
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version   
     * @date 2011-04-01
     */
    function getRecibida($fecha, $remitente, $guia) {
        $db = $this->getAdapter();
        $select = $db->select();
        $select->distinct(true);
        $select->from(array('e' => 'encomienda'), array(
            "id_encomienda", "remitente", "guia", "detalle",
            "sucursal_de", "nombre_destino", "destinatario",
            "tipo", "fecha"));
        $select->join(array('me'=>'movimiento_encomienda'),"me.encomienda=e.id_encomienda");
        if (isset($fecha) && $fecha != "") {
            $select->where("e.fecha='$fecha'");
        }
        if (isset($remitente) && $remitente != "") {
            $select->where("e.remitente like '%" . strtoupper($remitente) . "%'");
        }
        if (isset($guia) && $guia != "" && $guia != "0") {
            $select->where("e.guia='$guia'");
        }
        $select->where("UPPER(me.movimiento)='RECIBIDO'");
//        $select->orWhere("UPPER(e.estado)=?", 'TRASPASO');
        $select->order("nombre_destino");
//      echo $select->__toString();
        $log = Zend_Registry::get("log");
        $log->info("recuperando encomiendas lista para entregar :" . $select->__toString());
        return $db->fetchAll($select);
    }

    /**
     * Registra la entrega de encomiendas de encomiendas
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version beta
     * @date 2010-08-05
     *
     */
    function saveEntrega($encomienda, $receptor, $carnet, $usuarioEntrego, $nombreSucursal) {
        $db = $this->getAdapter();
        $db->beginTransaction();
        $log = Zend_Registry::get("log");
        $movimientoModel = new App_Model_MovimientoEncomienda();
        $manifiestoModel = new App_Model_ManifiestoModel();

        try {

            $fecha = date("Y-m-d");
            $hora = date("H:i:s");

            $selectE = $db->select();
            $selectE->from(array("e" => "encomienda"), "nombre_destino", "guia");
            $selectE->where("id_encomienda=?", $encomienda);
            $dataEnc = $db->fetchRow($selectE);
            $sucDestino = $dataEnc->nombre_destino;
//                $log->info("SQL: ".$selectE->__toString()."comparando traspaso sucDestino:".$sucDestino."  sucursal:".$sucursal);
            $estado = "ENTREGADO";
            $obs = "Entregado fuera de destino en un traspaso";
            if ($sucDestino != false && $sucDestino == $nombreSucursal) {
                $obs = "Entrega sin problemas sin problemas";
            }
            $dataEncomienda = array(
                "estado" => $estado,
                "receptor" => $receptor,
                "carnet" => $carnet
            );
            $where[] = "id_encomienda='" . $encomienda . "'";
            if ($this->update($dataEncomienda, $where) == 0) {
                throw new Zend_Db_Exception("No se ha podido actualizar el estado la encomiendas ", 125);
            }
            $where = null;

            $movimiento = array(
                "id_movimiento" => "nuevo",
                "fecha" => $hoy,
                "hora" => $hora,
                "movimiento" => $estado,
                "nombre_usuario" => $usuarioEntrego,
                "encomienda" => $encomienda,
                "sucursal" => $nombreSucursal,
//                "bus"=>"",
                "observacion" => $obs,
//                "manifiesto"=>"",
            );

            $movimientoModel->insert($movimiento);
            $db->commit();
            return array("guia" => $dataEnc->guia);
        } catch (Zend_Db_Exception $zdbe) {
            $db->rollBack();

            $log->info($zdbe);
            throw new Zend_Db_Exception($zdbe->getMessage(), 125);
        }
    }

    /**
     * Recupera todos los movimientos de la encomienda
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version   
     * @date 2011-04-04
     */
    function getMovimientos($id) {
        $db = $this->getAdapter();
//        $db->setFetchMode ( Zend_Db::FETCH_OBJ );
        $select = $db->select();
        $select->distinct(true);
        $select->from(array('m' => 'movimiento_encomienda'), array("id_movimiento", "fecha", "hora", "usuario", "sucursal", "observacion"));
        $select->where("m.encomienda=?",$id);
        $select->order("fecha");
//      echo $select->__toString();
        $log = Zend_Registry::get("log");
        $log->info("recuperando movimientos de la encomieda :" . $select->__toString());
        return $db->fetchAll($select);
    }
    
    /**
     * recupera la informacion de la factura de una encomienda
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version   
     * @date 2011-04-05
     */
    function getFactura($id) {
        $db = $this->getAdapter();
        $db->setFetchMode ( Zend_Db::FETCH_OBJ );
        $select = $db->select();
        $select->distinct(true);
        $select->from(array('f' => 'factura_encomienda'), array(
                "id_factura", "fecha", "hora", "nit", "nombre", "monto",
                "numero_factura","codigo_control","fecha_limite"
            ));
        $select->joinInner(array('df'=>'datos_factura'),"df.id_datos_factura=f.dosificacion",array("autorizacion"));
        $select->joinInner(array('e'=>'encomienda'),"e.factura=f.id_factura",null);
        $select->joinInner(array('p'=>'persona'),"p.id_persona=f.vendedor",array("identificador"));
        $select->where("e.id_encomienda=?",$id);
//      echo $select->__toString();
        $log = Zend_Registry::get("log");
        $log->info("recuperando movimientos de la encomieda :" . $select->__toString());
        return $db->fetchRow($select);
    }
}
