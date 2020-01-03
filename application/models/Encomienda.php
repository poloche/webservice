<?php

/**
 * Viaje
 *
 * @author Administrador
 * @version
 */
require_once 'Zend/Db/Table/Abstract.php';

class App_Model_Encomienda extends Zend_Db_Table_Abstract {

    /**
     * The default table name
     */
    protected $_name = 'encomienda';
    protected $_primary = 'id_encomienda';

    /**
     * Muestra el dialogo de venta para los asientos seleccionados
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
    function getByManifiesto($manifiesto) {
        $db = $this->getAdapter();
//        $db->setFetchMode ( Zend_Db::FETCH_OBJ );
        $select = $db->select();
        $select->distinct(true);
        $select->from(array('e' => 'encomienda'), array("id_encomienda", "remitente", "guia", "detalle", "sucursal_de", "nombre_destino"));
        $select->joinInner(array("i" => "item_encomienda"), "e.id_encomienda=i.encomienda", array("id_item_encomienda", "detalle", "cantidad", "monto", "peso", "estado"));
        $select->where("e.manifiesto=?", $manifiesto);
        $select->where("UPPER(e.estado)=?", App_Util_Statics::$ESTADOS_ENCOMIENDA["Envio"]);
        $select->order("nombre_destino");
//      echo $select->__toString();
        $log = Zend_Registry::get("log");
        $log->info("recuperando encomiendas por manifiesto :" . $select->__toString());
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
            foreach ($encomiendas as $idEncomienda) {
                $idEncomienda = base64_decode($idEncomienda);
                $selectE = $db->select();
                $selectE->from(array("e" => "encomienda"), "sucursal_de");
                $selectE->where("id_encomienda=?", $idEncomienda);
                $sucDestino = $db->fetchOne($selectE);
                $estado = App_Util_Statics::$ESTADOS_ENCOMIENDA['Traspaso'];
                $obs = "La encomienda esta en una sucursal diferente a la de destino";
                if ($sucDestino != false && $sucDestino == $sucursal) {
                    $obs = "Arribo a destino sin problemas";
                    $estado = App_Util_Statics::$ESTADOS_ENCOMIENDA['Arribo'];
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
                    "id_movimiento" => "0",
                    "fecha" => $hoy,
                    "hora" => $hora,
                    "movimiento" => $estado,
                    "usuario" => $usuario,
                    "encomienda" => $idEncomienda,
                    "sucursal" => $sucursal,
//                "bus"=>"",
                    "observacion" => $obs
//                    "manifiesto" => $manifiesto,
                );
                if (!is_numeric($usuario)) {
                    $movimiento['usuario'] = null;
                    $movimiento['nombre_usuario'] = $usuario;
                }
                if (!is_numeric($sucursal)) {
                    $movimiento['sucursal'] = 0;
                    $movimiento['nombre_sucursal'] = $sucursal;
                }

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
            $log = Zend_Registry::get("log");
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
        $select->from(array('e' => 'encomienda'), array("id_encomienda", "remitente", "guia", "detalle", "sucursal_de", "nombre_destino", "destinatario", "tipo", "fecha", "total"));
        if (isset($fecha) && $fecha != "") {
            $select->where("e.fecha='$fecha'");
        }
        if (isset($remitente) && $remitente != "") {
            $select->where("e.remitente like '%" . strtoupper($remitente) . "%'");
        }
        if (isset($guia) && $guia != "" && $guia != "0") {
            $select->where("e.guia='$guia'");
        }
        $estados = App_Util_Statics::$ESTADOS_ENCOMIENDA;
        $select->where("(UPPER(e.estado)='" . $estados['Arribo'] . "' OR UPPER(e.estado)='TRASPASO')");
//        $select->orWhere("UPPER(e.estado)=?", 'TRASPASO');
        $select->order("nombre_destino");
//      echo $select->__toString();
        $log = Zend_Registry::get("log");
        $log->info("recuperando encomiendas lista para entregar :" . $select->__toString());
        return $db->fetchAll($select);
    }

    /**
     * Busca encomiendas segun los parametros enviados
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version 1.1
     * @date creation 2012-09-06 09:58
     */
    function findEncomiendas($fecha, $remitente, $guia, $estado, $origen, $destino, $tipo) {
        $db = $this->getAdapter();

        $select = $db->select();
        $select->distinct(true);
        $select->from(array('e' => 'encomienda'), array("id_encomienda", "remitente", "guia", "detalle", "sucursal_de", "nombre_destino", "destinatario", "tipo", "fecha", "total"));
        if (isset($fecha) && $fecha != "") {
            $select->where("e.fecha=?", $fecha);
        }
        if (isset($remitente) && $remitente != "") {
            $select->where("e.remitente like '%" . strtoupper($remitente) . "%'");
        }
        if (isset($guia) && $guia != "" && $guia != "0") {
            $select->where("UPPER(e.guia)=?", strtoupper($guia));
        }
        if (isset($tipo) && $tipo != "" && $tipo != "0") {
            $select->where("UPPER(e.tipo)=?", strtoupper($tipo));
        }
        if (isset($estado) && $estado != "" && $estado != "0") {
            if ($estado == "ARRIBO") {
                $select->where("(UPPER(e.estado)='" . strtoupper($estado) . "' OR UPPER(e.estado)='TRASPASO')");
            } else {
                $select->where("UPPER(e.estado)=?", strtoupper($estado));
            }
        } else {
            $select->where("UPPER(e.estado)<>?", 'ANULADO');
        }
        if (isset($origen) && $origen != "" && $origen != "0") {
            $select->join(array('s' => 'sucursal'), "s.id_sucursal=e.sucursal_or", array("nombre as sucursalOrigen"));
            $select->where("s.ciudad=?", $origen);
        }
        if (isset($destino) && $destino != "" && $destino != "0") {
            $select->where("e.ciudad_de=?", $destino);
        }

        $select->order("nombre_destino");
        $log = Zend_Registry::get("log");
        $log->info("recuperando encomiendas lista para entregar :" . $select->__toString());
        return $db->fetchAll($select);
    }

    /**
     * Busca encomiendas segun un parametro de busqueda
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version 1.1
     * @date creation 2012-09-06 09:58
     */
    function findByCriteria($fecha, $remitente, $guia, $estado, $origen, $destino, $tipo, $destinatario, $orderBy = array()) {
        $db = $this->getAdapter();
        $db->setFetchMode(Zend_Db::FETCH_ASSOC);
        $select = $db->select();
        $select->distinct(true);
        $select->from(array('e' => 'encomienda'), array("id_encomienda", "remitente", "guia", "detalle", "sucursal_de", "nombre_destino", "destinatario", "tipo", "fecha", "total", "estado"));
        if (isset($fecha) && $fecha != "") {
            $select->where("e.fecha=?", $fecha);
        }
        if (isset($remitente) && $remitente != "") {
            $select->where("e.remitente like '%" . strtoupper($remitente) . "%'");
        }
        $destinatario = trim($destinatario);
        if (isset($destinatario) && $destinatario != "") {
            $select->where("e.destinatario ilike '%" . strtoupper($destinatario) . "%'");
        }
        if (isset($guia) && $guia != "" && $guia != "0") {
            $select->where("UPPER(e.guia)=?", strtoupper($guia));
        }
        if (isset($tipo) && $tipo != "" && $tipo != "0") {
            $select->where("UPPER(e.tipo)=?", strtoupper($tipo));
        }
        if (isset($estado) && $estado != "" && $estado != "0") {
            if ($estado == "ARRIBO") {
                $config = new App_Model_ConfiguracionSistema();
                $option = $config->getByKey(App_Util_Statics::$TIPOENTREGA)->value;
                if ($option == "si") {
                    $select->where("(UPPER(e.estado)='" . strtoupper($estado) . "' OR UPPER(e.estado)='TRASPASO')");
                } else {
                    $select->where("(UPPER(e.estado)='" . strtoupper($estado) . "' OR UPPER(e.estado)='TRASPASO'  OR UPPER(e.estado)='ENVIADO')");
                }
            } else {
                $select->where("UPPER(e.estado)=?", strtoupper($estado));
            }
        } else {
            $select->where("UPPER(e.estado)<>?", 'ANULADO');
        }
        if (isset($origen) && $origen != "" && $origen != "0") {
            $select->join(array('s' => 'sucursal'), "s.id_sucursal=e.sucursal_or", array("nombre as sucursalOrigen"));
            $select->where("s.ciudad=?", $origen);
        }
        if (isset($destino) && $destino != "" && $destino != "0") {
            $select->where("e.ciudad_de=?", $destino);
        }
        if (count($orderBy) > 0) {
            foreach ($orderBy as $table => $colum) {
                $select->order($colum);
            }
        } else {
            $select->order("nombre_destino");
        }
        $log = Zend_Registry::get("log");
        $log->info("recuperando encomiendas lista para entregar :" . $select->__toString());
        return $db->fetchAll($select, null, Zend_Db::FETCH_ASSOC);
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
        $select->join(array('me' => 'movimiento_encomienda'), "me.encomienda=e.id_encomienda", null);
        if (isset($fecha) && $fecha != "") {
            $select->where("e.fecha='$fecha'");
        }
        if (isset($remitente) && $remitente != "") {
            $select->where("e.remitente like '%" . strtoupper($remitente) . "%'");
        }
        if (isset($guia) && $guia != "" && $guia != "0") {
            $select->where("lower(e.guia)=?", strtolower($guia));
        }
//        $select->where("UPPER(me.movimiento)='RECIBIDO'");
//        $select->orWhere("UPPER(e.estado)=?", 'TRASPASO');
        $select->order("fecha");
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
        $db->setFetchMode(Zend_Db::FETCH_OBJ);
        $log = Zend_Registry::get("log");
        $movimientoModel = new App_Model_MovimientoEncomienda();
        $manifiestoModel = new App_Model_ManifiestoModel();

        try {

            $fecha = date("Y-m-d");
            $hora = date("H:i:s");

            $selectE = $db->select();
            $selectE->from(array("e" => "encomienda"), array("nombre_destino", "guia", "remitente", "destinatario", "detalle", "total", "telefono_remitente", "telefono_destinatario"));
            $selectE->join(array("s" => "sucursal"), "s.id_sucursal=e.sucursal_or", array("nombre AS origen"));
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
                "id_movimiento" => "-1",
                "fecha" => $fecha,
                "hora" => $hora,
                "movimiento" => $estado,
                "nombre_usuario" => $usuarioEntrego,
                "encomienda" => $encomienda,
                "sucursal" => $nombreSucursal,
//                "bus"=>"",
                "observacion" => $obs,
//                "manifiesto"=>"",
            );

            if (!is_numeric($nombreSucursal)) {
                $movimiento['sucursal'] = 0;
                $movimiento['nombre_sucursal'] = $nombreSucursal;
            }

            $movimientoModel->insert($movimiento);
            $db->commit();
            return $dataEnc;
        } catch (Zend_Db_Exception $zdbe) {
            $db->rollBack();

            $log->info($zdbe);
            throw new Zend_Db_Exception($zdbe->getMessage(), 125);
        }
    }

    /**
     * Registra el rollback de la transaccion de entrega de encomienda en caso de error
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version 1.1
     * @date creation 2012-10-31 9:29
     */
    function rollbackEntrega($id) {
        $db = $this->getAdapter();
        $db->beginTransaction();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);
        $log = Zend_Registry::get("log");
        $movimientoModel = new App_Model_MovimientoEncomienda();
        $manifiestoModel = new App_Model_ManifiestoModel();

        try {

            $fecha = date("Y-m-d");
            $hora = date("H:i:s");
            $arribo = strtoupper(App_Util_Statics::$ESTADOS_ENCOMIENDA['Arribo']);
            $traspaso = strtoupper(App_Util_Statics::$ESTADOS_ENCOMIENDA['Traspaso']);
            $entrega = strtoupper(App_Util_Statics::$ESTADOS_ENCOMIENDA['Entrega']);
            $sql = "select distinct m.usuario,m.nombre_usuario,m.sucursal,m.nombre_sucursal,m.movimiento 
                    from movimiento_encomienda m 
                    where m.encomienda='$id' and UPPER(m.movimiento)='$entrega'";

            $dataEnc = $db->fetchRow($sql);

//            $sqlDelete = "delete from movimiento where m.encomienda='$id' and m.fecha=(
//                        select max(m1.fecha)
//                        from movimiento_encomienda m1 
//                        where m1.encomienda=$id and UPPER(movimiento)='$entrega')
//                    )";
//
//            $dataEnc = $db->query($sqlDelete);


            $estado = $dataEnc->movimiento;
            $dataEncomienda = array("estado" => $arribo);
            $where[] = "id_encomienda='" . $id . "'";
            if ($this->update($dataEncomienda, $where) == 0) {
                throw new Zend_Db_Exception("No se ha podido actualizar el estado la encomiendas ", 125);
            }
            $where = null;

            $movimiento = array(
                "id_movimiento" => "-1",
                "fecha" => $fecha,
                "hora" => $hora,
                "movimiento" => $arribo,
                "usuario" => $usuarioEntrego,
                "encomienda" => $id,
                "sucursal" => $dataEnc->sucursal,
                "observacion" => "La entrega de la encomienda no fue satisfactoria",
            );

            if (!is_numeric($dataEnc->sucursal)) {
                $movimiento['sucursal'] = 0;
                $movimiento['nombre_sucursal'] = $dataEnc->nombre_sucursal;
            }

            $movimientoModel->insert($movimiento);
            $db->commit();
            return $dataEnc;
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
        $select->from(array('m' => 'movimiento_encomienda'), array("id_movimiento", "fecha", "hora", "usuario", "sucursal", "observacion", "movimiento", "nombre_usuario", "nombre_sucursal"));
        $select->joinLeft(array('b' => 'bus'), "m.bus=b.id_bus", array("numero AS interno"));
        $select->joinLeft(array('p' => 'persona'), "m.usuario=p.id_persona", array("identificador"));
        $select->joinLeft(array('s' => 'sucursal'), "m.sucursal=s.id_sucursal", array("nombre as nombreSucursal"));
        $select->where("m.encomienda=?", $id);
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
        $db->setFetchMode(Zend_Db::FETCH_OBJ);
        $select = $db->select();
        $select->distinct(true);
        $select->from(array('f' => 'factura_encomienda'), array(
            "id_factura", "fecha", "hora", "nit", "nombre", "monto",
            "numero_factura", "codigo_control", "fecha_limite"
        ));
        $select->joinInner(array('df' => 'datos_factura'), "df.id_datos_factura=f.dosificacion", array("autorizacion"));
        $select->joinInner(array('e' => 'encomienda'), "e.factura=f.id_factura", null);
        $select->joinInner(array('p' => 'persona'), "p.id_persona=f.vendedor", array("identificador"));
        $select->where("e.id_encomienda=?", $id);
//      echo $select->__toString();
        $log = Zend_Registry::get("log");
        $log->info("recuperando movimientos de la encomieda :" . $select->__toString());
        return $db->fetchRow($select);
    }

    /**
     * recupera la informacion de la factura sin sus items
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version   
     * @date 2011-04-06
     */
    function getById($id) {
        $db = $this->getAdapter();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);
        $select = $db->select();
        $select->distinct(true);
        $select->from(array('e' => 'encomienda'), array(
            "id_encomienda", "tipo", "fecha", "hora", "remitente",
            "destinatario", "telefono_remitente", "telefono_destinatario",
            "guia", "receptor", "carnet", "detalle", "nombre_destino", "nombre_ciudad_destino"
        ));
        $select->joinLeft(array('f' => 'factura'), "f.id_factura=e.factura", array("nit", "f.nombre"));
        $select->joinInner(array('s' => 'sucursal'), "s.id_sucursal=e.sucursal_or", array("s.nombre AS sucursal"));
        $select->joinInner(array('c' => 'ciudad'), "c.id_ciudad=s.ciudad", array("c.nombre AS ciudad"));
        $select->joinLeft(array('sd' => 'sucursal'), "sd.id_sucursal=e.sucursal_de", array("COALESCE(sd.nombre,'Noregistrado') AS sucursalDestino"));
        $select->joinInner(array('cd' => 'ciudad'), "cd.id_ciudad=e.ciudad_de", array("cd.nombre AS ciudadDestino"));
        $select->joinInner(array('m' => 'movimiento_encomienda'), "m.encomienda=e.id_encomienda", array("usuario"));
        $select->where("e.id_encomienda=?", $id);
//      echo $select->__toString();
        $log = Zend_Registry::get("log");
        $log->info("recuperando movimientos de la encomieda :" . $select->__toString());
        return $db->fetchRow($select);
    }

    /**
     * Recupera los items de una encomienda segun su identificador
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version 1.1
     * @date creation 2012-07-25 12:26
     */
    function getItems($encomienda) {
        $db = $this->getAdapter();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);
        $select = $db->select();
        $select->distinct(true);
        $select->from(array('i' => 'item_encomienda'), array("cantidad", "detalle", "monto", "peso"));
        $select->where("i.encomienda=?", $encomienda);

//      echo $select->__toString();
        $log = Zend_Registry::get("log");
        $log->info("recuperando items de la encomieda :" . $select->__toString());
        return $db->fetchAll($select);
    }

   function getPorPagarInDates($desde, $hasta, $filtro, $destino, $guias) {
        $log = Zend_Registry::get("log");
        $db = $this->getAdapter();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);
        $select = $db->select();
        $select->distinct(true);
        $select->from(array('e' => 'encomienda'), array("remitente", "nombre_destino", "nombre_ciudad_destino", "guia", "e.estado", "total", "remitente", "destinatario", "e.fecha", "e.hora"));
        $select->join(array('s' => 'sucursal'), "e.sucursal_or=s.id_sucursal", array("nombre as sucOrigen"));
        $select->joinLeft(array('mr' => 'movimiento_encomienda'), "mr.encomienda=e.id_encomienda AND mr.movimiento='RECIBIDO'", array("fecha as fechaRecepcion"));


        if ($filtro == "todo") {
            $select->joinLeft(array("p" => "persona"), "mr.usuario=p.id_persona", array("nombres as recibio"));

            $select->where("e.tipo=?", 'POR PAGAR');
            $select->where("(mr.fecha between '$desde' and '$hasta')");
            if (isset($destino) && $destino != "") {
                $select->where("UPPER(e.nombre_ciudad_destino) =?", strtoupper($destino));
            }
            $select->order("mr.fecha");

        } elseif ($filtro == "entregados") {
            $select->joinLeft(array("f" => "factura_encomienda"), "e.factura=f.id_factura", array("numero_factura","fecha as fechaEntrega"));
            $select->joinLeft(array("p" => "persona"), "f.vendedor=p.id_persona", array("nombres as entrego"));
            $select->where("e.is_porpagar_entregada=?", 'true');
            $log->info("Guias parameter  :" . $guias);
            if ($guias != null) {
                $select->where("e.guia in (?)", explode(",", $guias));
            } else {
                $select->where("(e.fecha between '$desde' and '$hasta')");
            }
            if (isset($destino) && $destino != "") {
                $select->where("UPPER(e.nombre_ciudad_destino) =?", strtoupper($destino));
            }

            $select->order("f.fecha");
            
        } elseif ($filtro == "rezagados") {
            $select->joinLeft(array("p" => "persona"), "mr.usuario=p.id_persona", array("nombres as recibio"));
            $select->where("e.tipo=?", 'POR PAGAR');
            $select->where("(mr.fecha between '$desde' and '$hasta')");
            $select->where("e.estado !=?", 'ENTREGADO');
            
            $select->order("mr.fecha");
        }



//      echo $select->__toString();
        $log->info("recuperando encomiendas por pagar :" . $select->__toString());
        return $db->fetchAll($select);
    }
}
