<?php

/**
 * @author Robert Boloc <robert.boloc@urv.cat>
 * @copyright 2014 Servei de Recursos Educatius (http://www.sre.urv.cat)
 */

class converter {

    protected $binary;
    protected $fallback_binary;
    protected $params;
    protected $fallback_params;

    public function __construct() {
        $config = include __DIR__ . '/../config/local.php';

        $this->binary          = $config['converter']['binary_path'];
        $this->fallback_binary = $config['converter']['fallback_binary_path'];
        $this->params          = $config['converter']['params'];
        $this->fallback_params = $config['converter']['fallback_params'];
    }

    public function convert($origin, $target, $fallback = false) {
        $output = null;
        $result = 1; // Assume fail

        // Try the conversion with the first method
        exec($this->binary . ' -i ' . $origin . ' ' . $this->params . ' ' . $target . ' > /dev/null', $output, $result);

        if((int)$result !== 0 && $fallback === true) {
            // Try the fallback method
            exec($this->fallback_binary . ' ' . $origin . ' ' . $this->fallback_params . ' ' . $target . ' > /dev/null', $output, $result);
        }

        return $result;
    }
}
