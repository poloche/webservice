<?php

class IndexController extends Zend_Controller_Action {

    public function init() {
        /* Initialize action controller here */
    }

    public function indexAction() {
        $this->_helper->Layout->disableLayout();
        $this->_helper->ViewRenderer->setNoRender();

        $params = $this->_request->getParams();
        unset($params['controller']);
        unset($params['action']);
        unset($params['module']);

        $server_class = 'App_Rest_ServerDefault';

//        print_r($params);
        $server = new App_Rest_Handler();
        $server->setClass($server_class);
        $server->returnResponse(true);

        $responseXML = $server->handle($params);

        if (!array_key_exists('format', $params)) {
            $format = $this->_request->isXmlHttpRequest() == true ? 'json' : 'xml';
        } else
            $format = $params['format'];

        switch ($format) {
            case 'xml':
                $this->_response->setHeader('Content-Type', 'text/plain')->setBody($responseXML);
                break;
            case 'json':
                
                $this->_response->setHeader('Content-Type', 'application/json')->setBody(Zend_Json::fromXML($responseXML));
                break;
        }
    }

    function restAction() {
        $this->_helper->Layout->disableLayout();
        $this->_helper->ViewRenderer->setNoRender();
        $encodedValue = '{"0":{"cantidad":"1","detalle":"sobre cerrado","peso":"5","valor":"5"}}';
        print_r(Zend_Json::decode($encodedValue));
    }

}

