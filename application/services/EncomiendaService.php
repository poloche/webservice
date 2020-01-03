<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of App_Rest_Server
 *
 * @author poloche
 */
class App_Rest_EncomiendaService extends App_Rest_Server {
    /*     * *
     * recupera la lista de encomienda desde un manifiesto
     * @param String $userid
     * @param String $apikey
     * @param String $manifiesto
     */

    public function getEncomiendas($userid, $apikey, $manifiesto) {
        $encomiendaM = new App_Model_Encomienda();
        $encomiendas = $encomiendaM->getByManifiesto($manifiesto);
        $lista = array();
        $i = 0;
        foreach ($encomiendas as $enc) {
            $lista["e" . $i] = array(
                "id" => base64_encode($enc["id_encomienda"]),
                "guia" => $enc["guia"],
                "detalle" => $enc["detalle"],
                "cantidad" => $enc["cantidad"],
                "sucursal" => $enc["sucursal_de"],
                "nombreSucursal" => $enc["nombre_destino"]
            );
            $i++;
        }
        $listaViajes = array("encomiendas" => $lista, "horaActual" => date("H:i"), "size" => count($lista), "manifiesto" => $manifiesto);
        return App_Rest_Response::Generate($listaViajes, true);
    }

    /**
     * registra la llegada de encomiendas a destino
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version beta 1
     * @date $(now)
     */
    function saveArribo($userid, $apikey, $encomiendas, $responsable, $sucursalLLegada, $manifiesto) {
        $encomiendaM = new App_Model_Encomienda();
        try {
            $encomiendas = $encomiendaM->saveArribo($encomiendas, $responsable, $sucursalLLegada, $manifiesto);
            $configModel = new App_Model_ConfiguracionSistema();
            $configs = $configModel->getAll();
            $cabecera["title"] = $configs[App_Util_Statics::$TITULO_FACTURA_1];
            $cabecera["emp"] = $configs[App_Util_Statics::$TITULO_FACTURA_2];
            $cabecera["direccion"] = "ARRIBO";
            $cabecera["usuario"] = $responsable;

            $manifiestoM = new App_Model_ManifiestoModel();
            $infoManifiesto = $manifiestoM->getById($manifiesto);
            $listaEnc = $manifiestoM->getByManifiestoEstadoResponsable($manifiesto, "ENVIADO", $responsable);
            $i = 0;
            foreach ($listaEnc as $enc) {
                $lista["e" . $i] = array(
                    "id" => $enc->id_encomienda,
                    "guia" => $enc->guia,
                    "detalle" => $enc->detalle,
                    "sucursal" => $enc->sucursal_de,
                    "total" => $enc->total,
                    "tipo" => $enc->tipo,
                    "nombreSucursal" => $enc->nombre_destino
                );
                $i++;
            }
            $dataManifiesto['chofer'] = $infoManifiesto->nombre_chofer;
            $dataManifiesto['fecha'] = $infoManifiesto->fecha;
            $dataManifiesto['hora'] = $infoManifiesto->hora;
            $dataManifiesto['destino'] = $infoManifiesto->ciudadOrigen . " " . $infoManifiesto->ciudadDestino;
            $dataManifiesto['numBus'] = $infoManifiesto->numero;

            $info['cabecera'] = $cabecera;
            $info['manifiesto'] = $dataManifiesto;
            $info['lista'] = $lista;

            $mensaje = "Las encomiendas han sido registradas";
            $error = false;
        } catch (Zend_Db_Exception $zdbe) {
            $log = Zend_Registry::get("log");
            $log->info($zdbe);
            $mensaje = $zdbe->getMessage();
            $error = true;
        }

        $listaViajes = array("message" => $mensaje, "error" => $error, "info" => $info);
        return App_Rest_Response::Generate($listaViajes, true);
    }

    /**
     * busca las encomiendas con estado ARRIBO que puedan ser entregadas
     * y que tengas la fecha, guia, remitente apropiados
     * @param Date fecha  fecha en la que se envio la encomienda
     * @param String remitente nombre de la persona que envio la encomienda
     * @param String guia  numero de guia de la encomienda
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version beta 
     * @date {now}
     */
    function findEncomiendas($fecha, $remitente, $guia, $estado) {
        $encomiendaM = new App_Model_Encomienda();
        if ($estado == "ARRIBO") {
            $encomiendas = $encomiendaM->getForEntrega($fecha, $remitente, $guia);
        } else {
            $encomiendas = $encomiendaM->getRecibida($fecha, $remitente, $guia);
        }
        $lista = array();
        foreach ($encomiendas as $enc) {
            $lista["e" . $enc["id_encomienda"]] = array(
                "id" => base64_encode($enc["id_encomienda"]),
                "guia" => $enc["guia"],
                "detalle" => $enc["detalle"],
                "remitente" => $enc["remitente"],
                "destinatario" => $enc["destinatario"],
                "tipo" => $enc["tipo"],
                "fecha" => $enc["fecha"],
                "sucursal" => $enc["sucursal_de"],
                "monto" => $enc["total"],
                "nombreSucursal" => $enc["nombre_destino"]
            );
        }

        $listaViajes = array("encomiendas" => $lista, "size" => count($lista));
        return App_Rest_Response::Generate($listaViajes, true);
    }

    /**
     * busca las encomiendas con estado ARRIBO que puedan ser entregadas
     * y que tengas la fecha, guia, remitente apropiados
     * @param Date fecha  fecha en la que se envio la encomienda
     * @param String remitente nombre de la persona que envio la encomienda
     * @param String guia  numero de guia de la encomienda
     * @param String estado  numero de guia de la encomienda
     * @param String origen  numero de guia de la encomienda
     * @param String destino  numero de guia de la encomienda
     * @param String tipo  numero de guia de la encomienda
     * @param String destinatario  numero de guia de la encomienda
     * @param String fechade  numero de guia de la encomienda
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version beta 
     * @date {now}
     */
    function findEncomiendas2($fecha, $remitente, $guia, $estado, $origen, $destino, $tipo, $destinatario, $fechade) {
        $encomiendaM = new App_Model_Encomienda();
        $encomiendas = $encomiendaM->findByCriteria($fecha, $remitente, $guia, $estado, $origen, $destino, $tipo, $destinatario, array("e" => "fecha"));

        $lista = array();
        foreach ($encomiendas as $enc) {
            $detalle = $this->getStringutf8($enc["detalle"]);
            $remitente = $this->getStringutf8($enc["remitente"]);
            $destinatario = $this->getStringutf8($enc["destinatario"]);
            $lista["e" . $enc["id_encomienda"]] = array(
                "id" => base64_encode($enc["id_encomienda"]),
                "guia" => $enc["guia"],
                "detalle" => $detalle,
                "remitente" => $remitente,
                "destinatario" => $destinatario,
                "tipo" => $enc["tipo"],
                "fecha" => $enc["fecha"],
                "sucursal" => $enc["sucursal_de"],
                "monto" => $enc["total"],
                "nombreSucursal" => $enc["nombre_destino"],
                "declarado" => $enc["valor_declarado"],
                "observacion" => $enc["observacion"],
                "estado" => $enc["estado"]
            );
        }

        $listaViajes = array("encomiendas" => $lista, "size" => count($lista));
        return App_Rest_Response::Generate($listaViajes, true);
    }

    function getStringutf8($string) {
        return mb_detect_encoding($string, "utf8") ? utf8_encode($string) : $string;
    }

    function base64Toggle($str) {
        if (!preg_match('~[^0-9a-zA-Z+/=]~', $str)) {
            $check = str_split(base64_decode($str));
            $x = 0;
            foreach ($check as $char)
                if (ord($char) > 126)
                    $x++;
            if ($x / count($check) * 100 < 30)
                return base64_decode($str);
        }
        return base64_encode($str);
    }

    /**
     * realiza la operacion de registro de entrega de encomiendas
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version beta
     * @date 2010-08-05
     * 
     * @param $encomienda   identificador de la encomienda la cual se entrega
     * @param $receptor     nombre de la persona que recibe la encomienda
     * @param $carnet       numero del documento con el cual se recoje la encomienda
     * @param $usuarioEntrego   nombre del usuario que realizo la entrega de la encomienda
     * @param $nombreSucursal   nombre de la sucursal en la cual se realiza la entrega de la encomienda 
     *
     */
    function saveEntrega($userid, $apikey, $encomienda, $receptor, $carnet, $usuarioEntrego, $nombreSucursal) {
        $encomiendaM = new App_Model_Encomienda();
//        $log = Zend_Registry::get("log");
//        $log->info("Registrando entrega encomienda :(".$encomienda.",".$receptor.",".$carnet.",".$usuarioEntrego.",".$nombreSucursal.")");
//        $log->info("Registrando entrega encomienda :(".(!isset($receptor))." || ".($receptor == "" )."|| ".($receptor == 0)." || ".($receptor == "0"));
        $error = null;
        try {
            if (!isset($carnet) || $carnet == "" || $carnet == 0 || $carnet == "0") {
                throw new Zend_Db_Exception("El documento no puede estar vacio", 125);
            } elseif (strlen($carnet) < 6) {
                throw new Zend_Db_Exception("El documento no es valido por favor introdusca uno mayor o igual a 6 digitos y menor a 15", 125);
            } elseif (!isset($receptor) || $receptor == "" || $receptor == "0") {
                throw new Zend_Db_Exception("El nombre de receptor no puede estar vacio", 125);
            } else {
                $encomiendaG = $encomiendaM->saveEntrega($encomienda, $receptor, $carnet, $usuarioEntrego, $nombreSucursal);

                $dataItems = $encomiendaM->getItems($encomienda);
//                print_r($dataItems);
                $items = array();
                $i = 0;
                foreach ($dataItems as $item) {
                    $items["e" . $i] = array(
                        "cantidad" => $item->cantidad,
                        "detalle" => $item->detalle,
                        "total" => $item->monto
                    );
                    $i++;
                }
                $mensaje = "La entrega se registro exitosamente";
                $error = false;
                $dataPrint = array(
                    "id" => base64_encode($encomiendaG->id_encomienda),
                    "entrego" => $usuarioEntrego,
                    "destinatario" => $encomiendaG->destinatario,
                    "remitente" => $encomiendaG->remitente,
                    "origen" => $encomiendaG->origen,
                    "destino" => $encomiendaG->nombre_destino,
                    "sucursalEntrega" => $nombreSucursal,
                    "telefonoRemitente" => $encomiendaG->telefono_remitente,
                    "telefonoDestinatario" => $encomiendaG->telefono_destinatario,
                    "detalle" => $encomiendaG->detalle,
                    "guia" => $encomiendaG->guia,
                    "total" => $encomiendaG->total,
                    "receptor" => $receptor,
                    "declarado" => $encomiendaG->valor_declarado,
                    "observacion" => $encomiendaG->observacion,
                    "carnet" => $carnet
                );

                $configModel = new App_Model_ConfiguracionSistema();
                $configs = $configModel->getAll();

                $empresa["title"] = $configs[App_Util_Statics::$TITULO_FACTURA_1];
                $empresa["nombre"] = $configs[App_Util_Statics::$TITULO_FACTURA_2];
                $empresa["nit"] = $configs[App_Util_Statics::$nitEmpresa];
                $cabecera["tipo"] = " DE ENTREGA";
                $cabecera["user"] = $usuarioEntrego;

                $info = array();
                $info['cabecera'] = $cabecera;
                $info['empresa'] = $empresa;
                $info['encomienda'] = $dataPrint;
                $info['items'] = $items;
            }
        } catch (Zend_Db_Exception $zdbe) {
            $mensaje = $zdbe->getMessage();
            $error = true;
        }

        $listaViajes = array("message" => $mensaje, "error" => $error, "info" => $info);
        return App_Rest_Response::Generate($listaViajes, true);
    }

    /**
     * Deshace la ultima transaccion de entrega de una encomienda por pagar pues no se pudo registrar la factura de la entrega en el servidor de entrega
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version 1.1
     * @date creation 2012-10-31 9:26
     */
    function rollbackEntregaPorPagar($id) {
        $encomiendaM = new App_Model_Encomienda();
//        $log = Zend_Registry::get("log");
//        $log->info("Registrando entrega encomienda :(".$encomienda.",".$receptor.",".$carnet.",".$usuarioEntrego.",".$nombreSucursal.")");
//        $log->info("Registrando entrega encomienda :(".(!isset($receptor))." || ".($receptor == "" )."|| ".($receptor == 0)." || ".($receptor == "0"));
        try {
            $error = true;
            if (!isset($id) || $id == "") {
                throw new Zend_Db_Exception("El identifiacdor de la encomienda no es valido", 125);
            } else {
                $encomiendaM->rollbackEntrega($id);
                $mensaje = "Se realizaron todos los cambios en la entrega ";
                $error = false;
            }
        } catch (Zend_Db_Exception $zdbe) {
            $mensaje = $zdbe->getMessage();
            $error = true;
        }

        $listaViajes = array("message" => $mensaje, "error" => $error, "info" => $info);
        return App_Rest_Response::Generate($listaViajes, true);
    }

    /**
     * Recupera los movimientos de la encomienda
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version   
     * @date 2011-04-04
     */
    function getMonitoring($encomienda) {
        $encomiendaM = new App_Model_Encomienda();
        $encomiendas = $encomiendaM->getMovimientos($encomienda);
        $lista = array();
        foreach ($encomiendas as $enc) {
            $user = $enc["identificador"];
            if ($enc["usuario"] == "") {
                $user = $enc["nombre_usuario"];
            }
            $sucursal = $enc["nombreSucursal"];
            if ($enc["sucursal"] == 0) {
                $sucursal = $enc["nombre_sucursal"];
            }
            $lista["e" . $enc["id_movimiento"]] = array(
                "f" => $enc["fecha"],
                "h" => substr($enc["hora"], 0, 8),
                "u" => $user,
                "s" => $sucursal,
                "o" => $enc["observacion"],
                "m" => $enc["movimiento"],
                "b" => $enc["interno"]
            );
        }

        $listaViajes = array("encomiendas" => $lista, "size" => count($lista));
        return App_Rest_Response::Generate($listaViajes, true);
    }

    /**
     * recupera la informacion de una factura de una encomienda caso de no 
     * tener factura devuelve nulo el campo encomienda
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version   
     * @date 2011-04-05
     */
    function getFactura($userid, $apikey, $encomienda) {
        $encomiendaM = new App_Model_Encomienda();
        $dataFact = $encomiendaM->getFactura($encomienda);
        $lista = array();
        if ($dataFact != null) {
            $lista = array(
                "fecha" => $dataFact->fecha,
                "hora" => $dataFact->hora,
                "usuario" => $dataFact->identificador,
                "numero" => $dataFact->numero_factura,
                "nombre" => $dataFact->nombre,
                "nit" => $dataFact->nit,
                "importe" => $dataFact->monto,
                "autorizacion" => $dataFact->autorizacion,
                "control" => $dataFact->codigo_control,
                "limite" => $dataFact->fecha_limite
            );
        } else {
            $lista = null;
        }

        $listaViajes = array("fac" => $lista, "size" => count($lista));
        return App_Rest_Response::Generate($listaViajes, true);
    }

    /**
     * recupera la informacion una encomienda
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version   
     * @date 2011-04-05
     */
    function getDetail($userid, $apikey, $encomienda) {
        $encomiendaM = new App_Model_Encomienda();
        $dataEnc = $encomiendaM->getById($encomienda);
        $lista = array();
        if ($dataEnc != null) {
            $itemModel = new App_Model_ItemEncomienda();
            $items = $itemModel->getByEncomienda($encomienda);
            $lista = array(
                "cOrigen" => $dataEnc->ciudad,
                "cDestino" => $dataEnc->nombre_ciudad_destino,
                "sOrigen" => $dataEnc->sucursal,
                "sDestino" => $dataEnc->nombre_destino,
                "tipo" => $dataEnc->tipo,
                "nit" => $dataEnc->nit,
                "nombre" => $dataEnc->nombre,
                "remitente" => $dataEnc->remitente,
                "telfR" => $dataEnc->telefono_remitente,
                "destinatario" => $dataEnc->destinatario,
                "telfD" => $dataEnc->telefono_destinatario,
                "receptor" => $dataEnc->receptor,
                "carnet" => $dataEnc->carnet
            );
            $dataItems = array();
            foreach ($items as $item) {
                $dataItems["i" . $item->id_item_encomienda] = array(
                    "cant" => $item->cantidad,
                    "detalle" => $item->detalle,
                    "peso" => round($item->peso, 2),
                    "precio" => $item->monto
                );
            }
            $lista["items"] = $dataItems;
        } else {
            $lista = null;
        }

        $listaViajes = array("enc" => $lista, "size" => count($lista));
        return App_Rest_Response::Generate($listaViajes, true);
    }

    /**
     * Funcion encargada de mostrar las encomiendas por pagar 
     * en un rango de fechas
     * @param type $desde
     * @param type $hasta
     * @param type $filtro
     * @param type $destino
     */
    function reportePorPagar($desde, $hasta, $filtro, $destino, $guias) {
        $encomiendaM = new App_Model_Encomienda();
        $encomiendas = $encomiendaM->getPorPagarInDates($desde, $hasta, $filtro, $destino, $guias);
        $lista = array();
        $i = 0;
        if ($encomiendas != null) {
            foreach ($encomiendas as $dataEnc) {

                 $lista["e" . $i] = array(
                    "vendedor" => $dataEnc->recibio,
                    "fecha" => $dataEnc->fecha,
                    "hora" => $dataEnc->hora,
					"remitente" => $dataEnc->remitente,
                    "destinatario" => $dataEnc->destinatario,
                    "sucOrigen" => $dataEnc->sucOrigen,
					"destino" => $dataEnc->nombre_ciudad_destino,
					"monto" => $dataEnc->total,
					"guia" => $dataEnc->guia,
					"estado" => $dataEnc->estado,
					"entrego" => $dataEnc->entrego,
                    "fechaEntrega" => $dataEnc->fechaEntrega
					);
                
                if (isset($dataEnc->numero_factura)) {
                    $lista["e" . $i]["factura"] = $dataEnc->numero_factura;
                }

                $i++;
            }
        } else {
            $lista = null;
        }

        $listaViajes = array("enc" => $lista, "size" => count($lista));
        return App_Rest_Response::Generate($listaViajes, true);
    }

}

?>
