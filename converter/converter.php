<?php

/**
 * @author Robert Boloc <robert.boloc@urv.cat>
 * @copyright 2014 Servei de Recursos Educatius (http://www.sre.urv.cat)
 */

abstract class converter {

    protected $binary;
    protected $params;

    public function __construct() {
        $config = include __DIR__ . '/../config/local.php';

        $this->binary = $config['converters'][get_class($this)]['binary_path'];
        $this->params = $config['converters'][get_class($this)]['params'];
    }

    abstract function convert($origin, $target);
}
