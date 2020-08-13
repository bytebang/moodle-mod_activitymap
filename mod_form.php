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
 * Actionmap module edit form
 *
 * @package    mod_actionmap
 * @copyright  2020 Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once ($CFG->dirroot.'/course/moodleform_mod.php');
require_once ($CFG->dirroot.'/mod/actionmap/lib.php');


class mod_actionmap_mod_form extends moodleform_mod {
    function definition() {
        global $CFG, $DB, $PAGE;
        $mform = $this->_form;

        $mform->addElement('header', 'general', get_string('general', 'form'));
        $mform->addElement('text', 'name', get_string('name'), array('size'=>'48'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $this->standard_intro_elements();
        $element = $mform->getElement('introeditor');

        //poa
        //getString takes the specified string from the lang file actionmap.php (last param)
        $mform->addElement('header', 'actionmapsettings', get_string('actionmapsettings', 'actionmap'));
        
        //A Testelement is added here. The next step is actionmap_update_instance() oder actionmap_add_instance method
        //in lib.php. The modform object is passed to this functions.
        
        //Direction
        $GraphDirections = array(
            "LR" => get_string('LR', 'actionmap'),
            "RL" => get_string('RL', 'actionmap'),
            "TB" => get_string('TB', 'actionmap'),
            "BT" => get_string('BT', 'actionmap'),
        );

        $mform->addElement('select', 'graphdirection', get_string('graphdirection', 'actionmap'), $GraphDirections);
        
        //Splines setting
        $EdgeStyle = array(
            "spline" => get_string('spline', 'actionmap'),
            "ortho" => get_string('ortho', 'actionmap'),
            "curved" => get_string('curved', 'actionmap'),
            "polyline" => get_string('polyline', 'actionmap'),
            "line" => get_string('line', 'actionmap'),
            "none" => get_string('none', 'actionmap'),
        );

        $mform->addElement('select', 'edgestyle', get_string('edgestyle', 'actionmap'), $EdgeStyle);

        //BackgroundColors
        $BackgroundColors = array(
            "random" => get_string('random', 'actionmap'),
            "aliceblue" => get_string('aliceblue', 'actionmap'),
            "ghostwhite" => get_string('ghostwhite', 'actionmap'),
            "beige" => get_string('beige', 'actionmap'),
            "lightgray" => get_string('lightgray', 'actionmap'),
            "lightpink" => get_string('lightpink', 'actionmap'),
            "lightyellow" => get_string('lightyellow', 'actionmap'),
            "palegreen" => get_string('palegreen', 'actionmap'),
        );

        $mform->addElement('select', 'sectionbackgroundcolor', get_string('sectionbackgroundcolor', 'actionmap'), $BackgroundColors);
        
        //Element Shapes
        $ElementShapes = array(
            "box" => get_string('box', 'actionmap'),
            "ellipse" => get_string('ellipse', 'actionmap'),
            "diamond" => get_string('diamond', 'actionmap'),
            "parallelogram" => get_string('parallelogram', 'actionmap'),
            "star" => get_string('star', 'actionmap'),
            "note" => get_string('note', 'actionmap'),
            "tab" => get_string('tab', 'actionmap'),
            "folder" => get_string('folder', 'actionmap'),  
        );

        $mform->addElement('select', 'elementshape', get_string('elementshape', 'actionmap'), $ElementShapes);

        //Node Seperation
        $mform->addElement('float', 'nodeseperation', get_string('nodeseperation', 'actionmap'));
        $mform->setDefault('nodeseperation', 1.0);

        //contentSetting
        $contentSetting = array(
            "allSectionsGrouped" => get_string('allSectionsGrouped', 'actionmap'),
            "allSections" => get_string('allSections', 'actionmap'),
            "currentSection" => get_string('currentSection', 'actionmap'),  
        );

        $mform->addElement('select', 'content', get_string('content', 'actionmap'), $contentSetting);

        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }


    function validation($data, $files) {
        $errors = parent::validation($data, $files);
        return $errors;
    }

}
