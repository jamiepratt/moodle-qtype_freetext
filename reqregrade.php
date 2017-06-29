<?php

require_once('../../../config.php');

$returnurl = required_param('returnurl', PARAM_URL);
$qaid = required_param('id', PARAM_INT);

require_sesskey();

$toinsert = new stdClass();
$toinsert->qattemptid = $qaid;
$toinsert->userid = $USER->id;

// check for record first to prevent duplicates
if (!$DB->record_exists('question_freetext_reqregrade', array('qattemptid' => $qaid))) {
    $DB->insert_record('question_freetext_reqregrade', $toinsert);
}

redirect($returnurl);
