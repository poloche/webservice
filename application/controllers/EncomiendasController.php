<?php

class EncomiendasController extends Zend_Controller_Action {

    public function init() {
        $this->_helper->Layout->disableLayout();
        $this->_helper->ViewRenderer->setNoRender();
    }

    public function restAction() {

        $log = Zend_Registry::get("log");
        $log->info("Encomiendas Controller WEB SERVICE ");
        $params = $this->_request->getParams();
        unset($params['controller']);
        unset($params['action']);
        unset($params['module']);
        $this->logger($params);
        $log->info("El metohod es " . $params["method"]);
        $parameters = array("method" => $params["method"]);
        $parameters["userid"] = $params["userid"];
        $parameters["apikey"] = $params["apikey"];
        switch ($params["method"]) {
            case "getEncomiendas":
                $log->info("getEncomiendas ");
                $parameters["manifiesto"] = base64_decode($params["manif"]);
                break;
            case "saveArribo":
                $log->info("saveArribo ");
                $encomiendas = $params['listReceived'];
//                $encomiendas = str_replace("'", '"', $encomiendas);
//                $encomiendas = str_replace("\\", '', $encomiendas);
//                $log->info("JSON QUE LLEGA :".$encomiendas);
//                $encomiendas = Zend_Json::decode($encomiendas);
                if ($encomiendas[strlen($encomiendas)] == ",") {
                    $encomiendas = substr($encomiendas, 0, strlen($encomiendas) - 1);
                }
                $encomiendas = split(",", $encomiendas);
                $parameters["encomiendas"] = $encomiendas;
                $parameters["responsable"] = base64_decode($params['responsable']);
                $parameters["sucursalLLegada"] = base64_decode($params['sucReceived']);
                $parameters["manifiesto"] = $params['manifiesto'];
                break;
            case "findEncomiendas":
                $log->info("findEncomiendas ");
                unset($parameters["userid"]);
                unset($parameters["apikey"]);
                $parameters["fecha"] = $params["fecha"];
                $parameters["remitente"] = $params["remitente"];
                $parameters["guia"] = $params["guia"];
                $parameters["estado"] = $params["estado"];
                break;
            case "findEncomiendas2":
                $log->info("findEncomiendas2 ");
                unset($parameters["userid"]);
                unset($parameters["apikey"]);
                $parameters["fecha"] = $params["fecha"];
                $parameters["remitente"] = $params["remitente"];
                $parameters["guia"] = $params["guia"];
                $parameters["estado"] = base64_decode($params["estado"]);
                $parameters["origen"] = base64_decode($params["origen"]);
                $parameters["destino"] = base64_decode($params["destino"]);
                $parameters["tipo"] = base64_decode($params["tipo"]);
                $parameters["destinatario"] = base64_decode($params["destinatario"]);
                $parameters["fechade"] = base64_decode($params["fechade"]);
                break;
            case "saveEntrega":
                $log->info("saveEntrega ");
                $parameters["encomienda"] = base64_decode($params["encomienda"]);
                $parameters["receptor"] = $params["receptor"];
                $parameters["carnet"] = $params["ci"];
                $parameters["usuarioEntrego"] = base64_decode($params["entrego"]);
                $parameters["nombreSucursal"] = base64_decode($params["suc"]);
                break;
            case "getMonitoring":
                $log->info("getMonitoring ");
                unset($parameters["userid"]);
                unset($parameters["apikey"]);
                $parameters["encomienda"] = base64_decode($params["id"]);
                break;
            case "getFactura":
                $log->info("getFactura ");
                $parameters["encomienda"] = base64_decode($params["id"]);
                break;
            case "getDetail":
                $log->info("getDetail ");
                $parameters["encomienda"] = base64_decode($params["id"]);
                break;
            case "rollbackEntregaPorPagar":
                $log->info("RollbackPorPagar ");
                $parameters["id"] = base64_decode($params["encomienda"]);
                break;
            default:
                $log->info("default ");
                break;
        }
        $server_prefix = 'App_Rest_';

        $server_class = $server_prefix . 'EncomiendaService';


        $server = new App_Rest_Handler();
        $server->setClass($server_class);
        $server->returnResponse(true);
        $responseXML = $server->handle($parameters);

        if (!array_key_exists('format', $params)) {
            if ($this->_request->isXmlHttpRequest())
                $format = 'json';
            else
                $format = 'xml';
        } else
            $format = $params['format'];

        switch ($format) {
            case 'xml':
                $log->info("Response xml:" . $responseXML);
                $this->_response->setHeader('Content-Type', 'text/plain')->setBody($responseXML);
                break;
            case 'json':
                $responseData = Zend_Json::fromXML($responseXML);
                if (array_key_exists('callback', $params)) {
                    $callback = $params['callback'];
                    $responseData = "$callback(" . Zend_Json::fromXML($responseXML) . ")";
                }
                $log->info("Response json:" . $responseData);
                $this->_response->setHeader('Content-Type', 'application/json')->setBody($responseData);
                break;
        }
    }

    public function finderAction() {

        $log = Zend_Registry::get("log");
        $log->info("Encomiendas Controller WEB SERVICE (" . $params["method"]) . ")";
        $params = $this->_request->getParams();
        unset($params['controller']);
        unset($params['action']);
        unset($params['module']);
        $this->logger($params);
        $parameters = array("method" => $params["method"]);

        $parameters["fecha"] = $params["fecha"];
        $parameters["remitente"] = $params["remitente"];
        $parameters["guia"] = $params["guia"];
        $estado = $params["estado"];

        $log->info("<br/> \n llegando estado = (" . $estado . ")");
        if (base64_encode(base64_decode($estado)) === $estado) {
            $estado = base64_decode($params["estado"]);
        }
        $parameters["estado"] = $estado;
        $parameters["origen"] = base64_decode($params["origen"]);
        $parameters["destino"] = base64_decode($params["destino"]);
        $parameters["destinatario"] = $params["destinatario"];
        $parameters["tipo"] = base64_decode($params["tipo"]);
        $parameters["fechade"] = $params["fecha"];



        $server_prefix = 'App_Rest_';

        $server_class = $server_prefix . 'EncomiendaService';


        $server = new App_Rest_Handler();
        $server->setClass($server_class);
        $server->returnResponse(true);
        $responseXML = $server->handle($parameters);

        if (!array_key_exists('format', $params)) {
            if ($this->_request->isXmlHttpRequest())
                $format = 'json';
            else
                $format = 'xml';
        } else
            $format = $params['format'];

        switch ($format) {
            case 'xml':
                $log->info("Response xml:" . $responseXML);
                $this->_response->setHeader('Content-Type', 'text/plain')->setBody($responseXML);
                break;
            case 'json':
                $simpleXmlElementObject = simplexml_load_string($responseXML);
                $log->info("simpleXmlElementObject:" . $simpleXmlElementObject->asXML());
                $log->info("Convert Response from xml:" . $responseXML);
                $responseData = Zend_Json::fromXML($responseXML);
                if (array_key_exists('callback', $params)) {
                    $callback = $params['callback'];
                    $responseData = "$callback(" . Zend_Json::fromXML($responseXML) . ")";
                }
                $log->info("Response json:" . $responseData);
                $this->_response->setHeader('Content-Type', 'application/json')->setBody($responseData);
                break;
        }
    }

    /**
     * 
     */
    public function reportePorPagarAction() {
        $params = $this->_request->getParams();

        $log = Zend_Registry::get("log");
        $log->info("Encomiendas Controller WEB SERVICE (" . $params["method"]) . ")";

        unset($params['controller']);
        unset($params['action']);
        unset($params['module']);
        $this->logger($params);
        $parameters = array("method" => "reportePorPagar");

        $parameters["desde"] = $params["desde"];
        $parameters["hasta"] = $params["hasta"];
        $parameters["filtro"] = $params["filtro"];
        $parameters["destino"] = $params["destino"];

        $parameters["guias"] = "";
        if (key_exists("guias", $params)) {
            $parameters["guias"] = $params["guias"];
        }

        $server_class = 'App_Rest_EncomiendaService';
        $format = $params['format'];
        if (!array_key_exists('format', $params)) {
            if ($this->_request->isXmlHttpRequest()) {
                $format = 'json';
            } else {
                $format = 'xml';
            }
        }
        $callback = array("call" => false);
        if (array_key_exists('callback', $params)) {
            $callback["call"] = false;
            $callback["function"] = $params['callback'];
        }

        $this->callRest($server_class, $parameters, $format, $callback);
    }

    function callRest($server_class, $parameters, $format, $callback) {
        $log = Zend_Registry::get("log");
        $server = new App_Rest_Handler();
        $server->setClass($server_class);
        $server->returnResponse(true);
        $responseXML = $server->handle($parameters);

        switch ($format) {
            case 'xml':
                $log->info("Response xml:" . $responseXML);
                $this->_response->setHeader('Content-Type', 'text/plain')->setBody($responseXML);
                break;
            case 'json':
                $simpleXmlElementObject = simplexml_load_string($responseXML);
                $log->info("simpleXmlElementObject:" . $simpleXmlElementObject->asXML());
                $log->info("Convert Response from xml:" . $responseXML);
                $responseData = Zend_Json::fromXML($responseXML);
                if ($callback['call'] == true) {
                    $function = $callback['function'];
                    $responseData = "$function(" . Zend_Json::fromXML($responseXML) . ")";
                }
                $log->info("Response json:" . $responseData);
                $this->_response->setHeader('Content-Type', 'application/json')->setBody($responseData);
                break;
        }
    }

    private function logger($arraylog) {
        $log = Zend_Registry::get("log");
        $str = "{";
        foreach ($arraylog as $key => $value) {
            $str .= "\t\t\t\t $key:$value ,\n";
        }
        $str.="}";
        $log->info("parametros :" . $str);
    }

}
