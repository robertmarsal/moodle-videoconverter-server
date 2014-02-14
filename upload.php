<?php

/**
 * @author Robert Boloc <robert.boloc@urv.cat>
 * @copyright 2014 Servei de Recursos Educatius (http://www.sre.urv.cat)
 */

require_once __DIR__ . '/api_caller.php';

$config = include __DIR__ . '/config/local.php';

$token = filter_input(INPUT_POST, 'token');

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

/* Deal with the upload */

// Check upload exists (only file "video" is of interest)
if (!isset($_FILES['video'])) {
    api_response(array(
        'status' => 'error',
        'message' => 'error:unknownfile',
    ));
}

// Check for errors
if (!isset($_FILES['video']['error']) ||
        is_array($_FILES['video']['error'])) {
    api_response(array(
        'status' => 'error',
        'message' => 'error:invalidparams',
    ));
}

// Check error value
switch ($_FILES['video']['error']) {
    case UPLOAD_ERR_OK:
        break;
    case UPLOAD_ERR_NO_FILE:
        api_response(array(
            'status' => 'error',
            'message' => 'error:nofilesent',
        ));
    case UPLOAD_ERR_INI_SIZE:
    case UPLOAD_ERR_FORM_SIZE:
        api_response(array(
            'status' => 'error',
            'message' => 'error:filetoobig',
        ));
    default:
        api_response(array(
            'status' => 'error',
            'message' => 'error:unknownerror',
        ));
}

// Check the size
if ($_FILES['video']['size'] > $config['max_size']) {
    api_response(array(
        'status' => 'error',
        'message' => 'error:filetoobig',
    ));
}

// Create the folder for the user
$token_user = $api_caller->get('token.user');
if ($token_user->status !== 'success') {
    api_response(array(
        'status' => 'error',
        'message' => 'error:useroftokennotfound'
    ));
}

$user_dir = $config['uploads_dir'] . '/' . $token_user->data->userid;
if (!is_dir($user_dir)) {
    mkdir($user_dir);
}

if (!is_dir($user_dir)) {
    api_response(array(
        'status' => 'error',
        'message' => 'error:failedcreatinguserdir'
    ));
}

// Store the file reference into the db
$file = array(
    'name' => $_FILES['video']['name'],
    'hash' => sha1_file($_FILES['video']['tmp_name']),
    'size' => $_FILES['video']['size']
);

$file_record = $api_caller->post('file.create', $file);

if ($file_record->status !== 'success') {
    // Something went wrong
    api_response(array(
        'status' => 'error',
        'message' => $file_record->message,
    ));
}

// Move the uploaded file
if (!move_uploaded_file(
                $_FILES['video']['tmp_name'], $user_dir . '/' . $file['hash'])) {
    api_response(array(
        'status' => 'error',
        'message' => 'error:movingfile'
    ));

    //@TODO cleanup created file record if move fails
}

// Enqueue the video remotely
$queue_item = array(
    'userid' => $token_user->data->userid,
    'fileid' => $file_record->data->fileid,
);

$enqueued = $api_caller->post('queue.new', $queue_item);

if ($enqueued->status !== 'success') {
    api_response(array(
        'status' => 'error',
        'message' => $enqueued->message,
    ));
}

// Enqueue the video locally
$local_queue_cache_file = $config['uploads_dir'] . '/.jobs';
if (!is_file($local_queue_cache_file)) {
    file_put_contents($local_queue_cache_file, json_encode(array()));
}

$current_queue = json_decode(file_get_contents($local_queue_cache_file), true);

$current_queue[$enqueued->data->position] = array(
    'queue_item_id' => $enqueued->data->queue_item_id,
    'token' => $token,
    'file' => $user_dir . '/' . $file['hash']
);

file_put_contents($local_queue_cache_file, json_encode($current_queue));

// Return success message
api_response(array(
    'status' => 'success',
));

/* Functions */

function api_response(array $response)
{
    echo json_encode($response);
    exit;
}
