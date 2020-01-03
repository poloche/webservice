<?php

class CiudadController extends Zend_Controller_Action {

    public function init() {
        $this->_helper->Layout->disableLayout();
        $this->_helper->ViewRenderer->setNoRender();
    }

    public function restAction() {

        $log = Zend_Registry::get("log");
        $log->info("Ciudad Controller WEB SERVICE");
        $params = $this->_request->getParams();
        unset($params['controller']);
        unset($params['action']);
        unset($params['module']);
        $str = "";
        $parameters = array("method" => $params["method"]);
        $parameters["userid"] = $params["userid"];
        $parameters["apikey"] = $params["apikey"];
        switch ($params["method"]) {
            case "getSucursales":
                $parameters["ciudad"] = base64_decode($params["id"]);
                break;
            case "getDosificacion":
                unset($parameters["userid"]);
                unset($parameters["apikey"]);
                $parameters["ciudad"] = base64_decode($params["id"]);
                break;
            case "getDestinos":
                unset($parameters["userid"]);
                unset($parameters["apikey"]);
                $parameters["ciudad"] = base64_decode($params["origen"]);
                break;
            default:
                break;
        }
        $server_prefix = 'App_Rest_';

        $server_class = $server_prefix . 'CiudadService';

        foreach ($params as $key => $value) {
            $str .= "params[$key]=$value,";
        }
        $log->info("parametros :" . $str);
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

}