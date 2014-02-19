<?php

/**
 * @author Robert Boloc <robert.boloc@urv.cat>
 * @copyright 2014 Servei de Recursos Educatius (http://www.sre.urv.cat)
 */

class converter {

    protected $binary;
    protected $params;

    public function __construct() {
        $config = include __DIR__ . '/../config/local.php';

        $this->binary = $config['converter']['binary_path'];
        $this->params = $config['converter']['params'];
    }

    public function convert($origin, $target) {
        $output = null;
        $result = 1; // Assume fail

        exec($this->binary . ' -i ' . $origin . ' ' . $this->params . ' ' . $target . ' > /dev/null', $output, $result);

        return $result;
    }
}
