<?php

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap {

    
    protected function _initModuleAutoloader() {
        
        $al = new Zend_Loader_Autoloader_Resource(
                        array('basePath' => dirname(__FILE__), 'namespace' => 'App'));
        $al->addResourceType('util', 'utils', 'Util');
        $al->addResourceType('bean', 'beans', 'Bean');
        $al->addResourceType('rest', 'services', 'Rest');
        $app = new Zend_Application_Module_Autoloader(array('namespace' => 'App', 'basePath' => dirname(__FILE__)));
    }

    protected function _initLog() {
        $date = new Zend_Date();
        $name = $date->toString("YYYY-MM-dd");
        $stream = @fopen("../log/" . $name . ".log", 'a', false);
        if (!$stream) {
            throw new Exception('Failed to open stream');
        }
        $writer = new Zend_Log_Writer_Stream($stream);
        $log = new Zend_Log($writer);
        Zend_Registry::set("log", $log);
    }

}

