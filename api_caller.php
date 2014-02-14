<?php

/**
 * @author Robert Boloc <robert.boloc@urv.cat>
 * @copyright 2014 Servei de Recursos Educatius (http://www.sre.urv.cat)
 */

class api_caller {

    protected $token;
    protected $config;

    public function __construct($token, $config) {
        $this->token = $token;
        $this->config = $config;
    }

    /**
     * Curl handler is not reused because it does not
     * work well when mixing GET/POST requests. Because
     * the use is not intensive we can regenerate the
     * handle on each request without a major performance
     * impact.
     */
    public function get($request, $params = array()) {
        $params['request'] = $request;
        $params['token'] = $this->token;
        $request_url = $this->config['api_entry'] . '?' . http_build_query($params);

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $request_url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $response = curl_exec($ch);

        return (object) json_decode($response);
    }

    /**
     * Curl handler is not reused because it does not
     * work well when mixing GET/POST requests. Because
     * the use is not intensive we can regenerate the
     * handle on each request without a major performance
     * impact.
     */
    public function post($request, $params = array()) {
        $params['request'] = $request;
        $params['token'] = $this->token;

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->config['api_entry']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);

        return (object) json_decode($response);
    }
}
