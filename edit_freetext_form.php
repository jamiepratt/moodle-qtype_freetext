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
 * Defines the editing form for the freetext question type.
 *
 * @package    qtype
 * @subpackage freetext
 * @copyright  2007 Jamie Pratt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();


/**
 * Free text question editing form definition.
 *
 * @copyright  2007 Jamie Pratt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_freetext_edit_form extends question_edit_form {

    /**
     * @param MoodleQuickForm $mform
     */
    protected function definition_inner($mform) {
        $menu = array(
            get_string('caseno', 'qtype_freetext'),
            get_string('caseyes', 'qtype_freetext')
        );
        $mform->addElement('select', 'usecase',
                get_string('casesensitive', 'qtype_freetext'), $menu);

        $this->add_interactive_settings();

        // Remove the default mark field from form and replace with constant.
        $mform->removeElement('defaultmark');
        $mform->addElement('hidden', 'defaultmark');
        $mform->setConstant('defaultmark', 1);

    }


    protected function data_preprocessing($question) {
        $question = parent::data_preprocessing($question);
        $question = $this->data_preprocessing_hints($question);

        return $question;
    }


    public function qtype() {
        return 'freetext';
    }
}
