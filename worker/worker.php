<?php

/**
 * @author Robert Boloc <robert.boloc@urv.cat>
 * @copyright 2014 Servei de Recursos Educatius (http://www.sre.urv.cat)
 */

require_once __DIR__ . '/../api_caller.php';
require_once __DIR__ . '/../converter/converter.php';

$config = include_once __DIR__ . '/../config/local.php';

// Name this worker
$worker = uniqid();

if (!register_worker($worker, $config)) {
    // No more workers accepted
    exit(0);
}

$jobs_config_file = $config['uploads_dir'] . '/.jobs';

// Read the job queue
$job_queue = array();
if (is_file($jobs_config_file)) {
    $job_queue = json_decode(file_get_contents($jobs_config_file), true);
}

if (empty($job_queue)) {
    // Remove worker
    remove_worker($worker, $config);
    exit(0);
}

// Obtain the next file in queue
$job = (object) array_shift($job_queue);

// Remove from queue (so that another worker does not take it ["Mi tesoroooooo"])
file_put_contents($jobs_config_file, json_encode($job_queue));

// Obtain the token
$token = $job->token;
$api_caller = new api_caller($token, $config);

// Change status to converting
$api_caller->post('queue.status', array(
    'queue_item_id' => $job->queue_item_id,
    'queue_status' => 'converting',
    'time' => time(),
));

// Convert it
$converter = new converter();
$file_info = pathinfo($job->file);
$converted_file_name = $file_info['dirname'] . '/' . $file_info['filename'] . '.mp4';

$result = $converter->convert($job->file, $converted_file_name, true);

// Report conversion ok/failed
$status = 'converted';
if ((int) $result !== 0) {
    $status = 'failed';
} else {
    // Cleanup
    unlink($job->file);
}

$api_caller->post('queue.status', array(
    'queue_item_id' => $job->queue_item_id,
    'queue_status' => $status,
    'time' => time(),
));

// Remove worker
remove_worker($worker, $config);

/* Functions */

function register_worker($worker, $config)
{

    // Check worker max instances
    $max_workers = $config['workers']['max_instances'];
    $workers_config_file = $config['uploads_dir'] . '/.workers';

    if (!is_file($workers_config_file)) {
        touch($workers_config_file);
        file_put_contents($workers_config_file, json_encode(array(
            'instances' => array(),
        )));
    }

    $workers_config = json_decode(file_get_contents($workers_config_file), true);

    if (count($workers_config['instances']) === (int) $max_workers) {
        // Already using max workers, do nothing
        return false;
    }

    // Register the new worker
    $workers_config['instances'][] = $worker;
    file_put_contents($workers_config_file, json_encode($workers_config));

    return true;
}

function remove_worker($worker, $config)
{
    $workers_config_file = $config['uploads_dir'] . '/.workers';
    $workers_config = json_decode(file_get_contents($workers_config_file), true);

    foreach ($workers_config['instances'] as $worker_item => $worker_instance) {
        if ($worker_instance === $worker) {
            unset($workers_config['instances'][$worker_item]);
        }
    }

    file_put_contents($workers_config_file, json_encode($workers_config));
}
