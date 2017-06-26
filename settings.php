<?php

/**
 * Admin menu configuration settings for the freetext question type.
 *
 * @package    qtype_freetext
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$settings->add(new admin_setting_heading('qtype_freetext',
                                        get_string('wsurl', 'qtype_freetext'),
                                        ''));
$settings->add(new admin_setting_configtext("qtype_freetext/wsurl",
    new lang_string('wsurl', 'qtype_freetext'), '','', PARAM_URL, 30));
