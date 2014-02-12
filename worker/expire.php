<?php

/**
 * @author Robert Boloc <robert.boloc@urv.cat>
 * @copyright 2014 Servei de Recursos Educatius (http://www.sre.urv.cat)
 */

require_once __DIR__ . '/../api_caller.php';

$config = include_once __DIR__ . '/../config/local.php';

//@TODO: iterate over .expire contents and delete the expired files
$expire_queue_file = $config['uploads_dir'] . '/.expire';

$expire_queue = array();
if(is_file($expire_queue_file)) {
    $expire_queue = json_decode(file_get_contents($expire_queue_file), true);
}

if(empty($expire_queue)) {
	// Nothing to do
	exit;
}

// Obtain the next file in queue
$expire = (object) array_shift($expire_queue);
file_put_contents($expire_queue_file, json_encode($expire_queue));

// Obtain the token
$token = $expire->token;
$api_caller = new api_caller($token, $config);

// Check timestamp
if($expire->timeexpires > time()) {
	// Not yet!
	exit;
}

// Remove from queue
$response = $api_caller->post('queue.remove', array(
    'queue_item_id' => $expire->queue_item_id,
));

if($response->status !== 'success') {
	// Something is wrong :(
	exit;
}

// Remove from disk
if(is_file($expire->file)) {
	unlink($expire->file);
}