<?php

class MobiusController extends Zend_Controller_Action {

    public function init() {
        /* Initialize action controller here */
    }

    public function getUrlAction() {
        $this->_helper->Layout->disableLayout();
        $this->_helper->ViewRenderer->setNoRender();
        $log = Zend_Registry::get("log");
        $log->info("entrando al controlador mobius");
        $params = $this->_request->getParams();
        unset($params['controller']);
        unset($params['action']);
        unset($params['module']);


        unset($params['userid']);
        unset($params['apikey']);
        $str = "";

        $bootstrap = $this->getInvokeArg('bootstrap');
        $aConfig = $bootstrap->getOptions();
        $paramsAdapter = $aConfig['externaldb'];
        $db = Zend_Db::factory($paramsAdapter['adapter'],$paramsAdapter['params']);
        $params['adapter'] = $db;

        $server_class = 'App_Rest_MobiusService';
        $server = new App_Rest_Handler();
        $server->setClass($server_class);
        $server->returnResponse(true);
//        $log->info("Class WebService :" . $server_class);
        $responseXML = $server->handle($params);

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

    public function controlAction() {
        $this->_helper->Layout->disableLayout();
        $this->_helper->ViewRenderer->setNoRender();
        $log = Zend_Registry::get("log");
        $log->info("entrando al controlador /Viaje/control/");
        $params = $this->_request->getParams();
        unset($params['controller']);
        unset($params['action']);
        unset($params['module']);
        $str = "(";

        $server_prefix = 'App_Rest_';
        $parameters = array("method" => $params["method"]);
        $parameters["userid"] = $params["userid"];
        $parameters["apikey"] = $params["apikey"];
        switch ($params["method"]) {
            case "itinerarioControl":
                $parameters["fecha"] = $params["fecha"] == "" ? date("Y-m-d") : $params["fecha"];
                $parameters["propietario"] = $params["propietario"];

                break;
            case "modelo":
                $parameters["interno"] = $params["bus"];
                $parameters["hora"] = base64_decode($params["hora"]);
                $parameters["destino"] = $params["destino"];
                break;
        }

        ////////////////////////////////////////////////////////////
        //fines solo informativos verificamos los parametros
        // que nos envian y lo mandamos al log
        foreach ($parameters as $key => $value) {
            $str .= "[$key]=$value,";
        }
        $log->info("parametros :" . $str . ")");
        /////////////////////////////////////////////////////

        $server_class = $server_prefix . 'Viaje';
        $server = new App_Rest_Handler();
        $server->setClass($server_class);
        $server->returnResponse(true);
        $log->info("Class WebService :" . $server_class);
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

}