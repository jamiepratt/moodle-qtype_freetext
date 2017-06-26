<?php

function log_to_file($tolog) {
    if (!is_dir('/tmp/jamiesensei')) {
        mkdir('/tmp/jamiesensei', 0777, true);

    }
    if (!is_string($tolog)) {
        $tolog = print_r($tolog, true);
    }
    file_put_contents("/tmp/jamiesensei/debugger.log", $tolog."\n", FILE_APPEND);
}

log_to_file(date('D, d M Y H:i:s') . ' request received.');

$json = file_get_contents('php://input');
$params = json_decode($json);

log_to_file(compact('params'));

if (false !== strpos($params->answer,'to investigate hemispheric lateralisation')) {
    $toreturn = array(
        'answer' => $params->answer,
        'justification' => array('To investigate hemispheric lateralisation '),
        'mark' => 1,
        'q_id' => $params->q_id
    );
} else {
    $toreturn = array(
        'answer' => $params->answer,
        'justification' => array(),
        'mark' => 0,
        'q_id' => $params->q_id
    );
}

echo (json_encode($toreturn));

log_to_file(compact('params', 'toreturn'));

