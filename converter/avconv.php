<?php

/**
 * @author Robert Boloc <robert.boloc@urv.cat>
 * @copyright 2014 Servei de Recursos Educatius (http://www.sre.urv.cat)
 */

require_once __DIR__ . '/converter.php';

class avconv extends converter {
    
    public function convert($origin, $target) {
        $output = null;
        $result = 1; // Assume fail

        exec($this->binary . ' -i ' . $origin . ' ' . $this->params . ' ' . $target . ' > /dev/null', $output, $result);

        return $result;
    }
}
