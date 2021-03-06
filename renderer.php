<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Free text question renderer class.
 *
 * @package    qtype
 * @subpackage freetext
 * @copyright  2009 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();


/**
 * Generates the output for Free text questions.
 *
 * @copyright  2009 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_freetext_renderer extends qtype_renderer {
    public function formulation_and_controls(question_attempt $qa,
            question_display_options $options) {

        global $DB, $USER;
        $question = $qa->get_question();
        $currentanswer = $qa->get_last_qt_var('answer');

        $inputname = $qa->get_qt_field_name('answer');
        $inputattributes = array(
            'type' => 'text',
            'name' => $inputname,
            'value' => $currentanswer,
            'id' => $inputname,
            'size' => 80,
            'class' => 'form-control',
        );

        if ($options->readonly) {
            $inputattributes['readonly'] = 'readonly';
        }

        $feedbackimg = '';
        if ($options->correctness) {
            $fraction = \qtype_freetext\client::mark($question->wsqid, $currentanswer);
            $inputattributes['class'] .= ' ' . $this->feedback_class($fraction);
            $feedbackimg = $this->feedback_image($fraction);
        }

        $questiontext = $question->format_questiontext($qa);
        $placeholder = false;
        if (preg_match('/_____+/', $questiontext, $matches)) {
            $placeholder = $matches[0];
            $inputattributes['size'] = round(strlen($placeholder) * 1.1);
        }
        $input = html_writer::empty_tag('input', $inputattributes) . $feedbackimg;
        $textarea = $this->textarea($inputname, $currentanswer, $options->readonly) . $feedbackimg;

        if ($placeholder) {
            $inputinplace = html_writer::tag('label', get_string('answer'),
                    array('for' => $inputattributes['id'], 'class' => 'accesshide'));
            $inputinplace .= $input;
            $questiontext = substr_replace($questiontext, $inputinplace,
                    strpos($questiontext, $placeholder), strlen($placeholder));
        }

        $result = html_writer::tag('div', $questiontext, array('class' => 'qtext'));

        if (!$placeholder) {
            $result .= html_writer::start_tag('div', array('class' => 'ablock form-inline'));
            $result .= $textarea;
            $result .= html_writer::end_tag('div');
        }

        if ($qa->get_state() == question_state::$invalid) {
            $result .= html_writer::nonempty_tag('div',
                    $question->get_validation_error(array('answer' => $currentanswer)),
                    array('class' => 'validationerror'));
        }

        if ($options->marks == $options::MARK_AND_MAX || $options->correctness) {
            $qaid = $qa->get_database_id();
            if (!$DB->record_exists('question_freetext_reqregrade', array('qattemptid' => $qaid))) {
                //Since we don't know which activity is using this question and the activity keeps track of who
                //is attempting this activity, we'll look at who answered the question in order to decide who can
                //ask for a teacher regrade.
                $answerstep = $qa->get_last_step_with_qt_var('answer');
                // If no step is found an empty step is returned.
                if (($answerstep->has_qt_var('answer')) && $answerstep->get_user_id() == $USER->id) {
                    $url = new moodle_url('/question/type/freetext/reqregrade.php',
                        array('returnurl' => $this->page->url->out(),
                            'sesskey' => sesskey(), 'id' => $qaid));
                    $result .= html_writer::link($url, get_string('flagforregrade', 'qtype_freetext'));
                }
            } else {
                $result .= get_string('flaggedforregrade', 'qtype_freetext');
            }
        }

        return $result;
    }

    /**
     * Return html for a text area.
     *
     * @param string $name of textarea
     * @param string $contents of text area
     * @param bool $readonly is it readonly?
     * @return string html for text area
     */
    protected function textarea($name, $contents, $readonly) {

        $attributes = array(
            'type' => 'textarea',
            'name' => $name,
            'value' => $contents,
            'id' => $name,
            'rows' => 6,
            'cols' => 80,
            'class' => 'form-control answer',
        );

        if ($readonly) {
            $attributes['readonly'] = 'readonly';
        }
        // Use a hidden label for accessibility for those using screen readers.
        $label = html_writer::tag('label', get_string('answer'),
            array('for' => $attributes['id'], 'class' => 'accesshide'));
        return $label.html_writer::tag('textarea', s($contents), $attributes);
    }

    public function specific_feedback(question_attempt $qa) {
        $question = $qa->get_question();

        $response = $qa->get_last_qt_var('answer');
        $justification = \qtype_freetext\client::justification($question->wsqid, $response);

        if (count($justification) > 0) {
            $justificationstring = '"'. join('", "', $justification) . '"';

            $feedback = get_string('justification', 'qtype_freetext', $justificationstring);

            return $feedback;

        } else {
            return '';
        }
    }
}
