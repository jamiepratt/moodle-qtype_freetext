<?php

require_once('../../../config.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');

$returnurl = required_param('returnurl', PARAM_URL);
$qaid = required_param('id', PARAM_INT);

require_sesskey();

if (!$qa = $DB->get_record('question_attempts', array('id' => $qaid))) {
    print_error('invalidqaid', 'qtype_freetext');
}
if (!$userid = $DB->get_field_sql("SELECT qs.userid ".
                  "FROM {question_attempt_steps} qs, {question_attempt_step_data} qsd ".
                  "WHERE qsd.name = ? AND qsd.attemptstepid = qs.id AND qs.questionattemptid = ? ".
                  "ORDER BY qs.sequencenumber DESC LIMIT 1",
                    array('answer', $qaid))) {
    print_error('answernotfound', 'qtype_freetext');
}

if ($userid != $USER->id) {
    print_error('notyouranswer', 'qtype_freetext');
}

$toinsert = new stdClass();
$toinsert->qattemptid = $qaid;
$toinsert->userid = $USER->id;

// check for record first to prevent duplicates
if (!$DB->record_exists('question_freetext_reqregrade', array('qattemptid' => $qaid))) {
    $DB->insert_record('question_freetext_reqregrade', $toinsert);
}

redirect($returnurl);
