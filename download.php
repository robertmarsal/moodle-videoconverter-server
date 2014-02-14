<?php

/**
 * @author Robert Boloc <robert.boloc@urv.cat>
 * @copyright 2014 Servei de Recursos Educatius (http://www.sre.urv.cat)
 */

require_once __DIR__ . '/api_caller.php';

$config = include __DIR__ . '/config/local.php';

$token = filter_input(INPUT_GET, 'token');
$file_disk_name = filter_input(INPUT_GET, 'file');
$queue_item_id = filter_input(INPUT_GET, 'queue_item_id');

if (empty($token)) {
    api_response(array(
        'status' => 'error',
        'message' => 'error:accessdenied',
    ));
}

$api_caller = new api_caller($token, $config);

// validate the token before proceeding
$token_validation = $api_caller->get('token.validate');

if ($token_validation->status !== 'success') {
    api_response(array(
        'status' => 'error',
        'message' => 'error:accessdenied'
    ));
}

// Obtain the user from the token
$token_user = $api_caller->get('token.user');
if ($token_user->status !== 'success') {
    api_response(array(
        'status' => 'error',
        'message' => 'error:useroftokennotfound'
    ));
}

$user_file = $config['uploads_dir'] . '/' . $token_user->data->userid . '/' . $file_disk_name . '.mp4';
if (!is_file($user_file)) {
    api_response(array(
        'status' => 'error',
        'message' => 'error:unknownfile',
    ));
}

// Update item status
$queue_update = $api_caller->post('queue.status', array(
    'queue_item_id' => $queue_item_id,
    'time' => time(),
    'queue_status' => 'downloaded',
        ));

if ($queue_update->status !== 'success') {
    // Something goes wrong
    exit;
}

// Enqueue the file to the expire list (24 hours from now)
$expire_file = $config['uploads_dir'] . '/.expire';

$expire_queue = array();
if (is_file($expire_file)) {
    $expire_queue = json_decode(file_get_contents($expire_file), true);
}

// Check is not already in queue (multiple downloads)
if (!isset($expire_queue[$user_file])) {
    $expire_queue[$user_file] = array(
        'token' => $token,
        'queue_item_id' => $queue_item_id,
        'file' => $user_file,
        'timeexpires' => time() + 86400
    );
}

file_put_contents($expire_file, json_encode($expire_queue));

// All ok, send the file
$videoFileInfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $videoFileInfo->file($user_file);
header("Content-type: " . $mime);
header('Content-Disposition: attachment; filename="' . basename($user_file) . '"');
header("Content-Length: " . filesize($user_file));
readfile($user_file);

/* Functions */

function api_response(array $response)
{
    echo json_encode($response);
    exit;
}
