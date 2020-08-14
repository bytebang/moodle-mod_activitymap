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
 * Activitymap module edit form
 *
 * @package    mod_activitymap
 * @copyright  2020 Guenther Hutter, Andreas Poetscher
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once ($CFG->dirroot.'/course/moodleform_mod.php');
require_once ($CFG->dirroot.'/mod/activitymap/lib.php');


class mod_activitymap_mod_form extends moodleform_mod {
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
        //getString takes the specified string from the lang file activitymap.php (last param)
        $mform->addElement('header', 'activitymapsettings', get_string('activitymapsettings', 'activitymap'));
        
        //A Testelement is added here. The next step is activitymap_update_instance() oder activitymap_add_instance method
        //in lib.php. The modform object is passed to this functions.
        
        //Direction
        $GraphDirections = array(
            "LR" => get_string('LR', 'activitymap'),
            "RL" => get_string('RL', 'activitymap'),
            "TB" => get_string('TB', 'activitymap'),
            "BT" => get_string('BT', 'activitymap'),
        );

        $mform->addElement('select', 'graphdirection', get_string('graphdirection', 'activitymap'), $GraphDirections);
        
        //Splines setting
        $EdgeStyle = array(
            "spline" => get_string('spline', 'activitymap'),
            "ortho" => get_string('ortho', 'activitymap'),
            "curved" => get_string('curved', 'activitymap'),
            "polyline" => get_string('polyline', 'activitymap'),
            "line" => get_string('line', 'activitymap'),
            "none" => get_string('none', 'activitymap'),
        );

        $mform->addElement('select', 'edgestyle', get_string('edgestyle', 'activitymap'), $EdgeStyle);

        //BackgroundColors
        $BackgroundColors = array(
            "random" => get_string('random', 'activitymap'),
            "aliceblue" => get_string('aliceblue', 'activitymap'),
            "transparent" => get_string('transparent', 'activitymap'),
            "ghostwhite" => get_string('ghostwhite', 'activitymap'),
            "beige" => get_string('beige', 'activitymap'),
            "lightgray" => get_string('lightgray', 'activitymap'),
            "lightpink" => get_string('lightpink', 'activitymap'),
            "lightyellow" => get_string('lightyellow', 'activitymap'),
            "palegreen" => get_string('palegreen', 'activitymap'),
        );

        $mform->addElement('select', 'sectionbackgroundcolor', get_string('sectionbackgroundcolor', 'activitymap'), $BackgroundColors);
        
        //Element Shapes
        $ElementShapes = array(
            "box" => get_string('box', 'activitymap'),
            "ellipse" => get_string('ellipse', 'activitymap'),
            "diamond" => get_string('diamond', 'activitymap'),
            "parallelogram" => get_string('parallelogram', 'activitymap'),
            "star" => get_string('star', 'activitymap'),
            "note" => get_string('note', 'activitymap'),
            "tab" => get_string('tab', 'activitymap'),
            "folder" => get_string('folder', 'activitymap'),  
        );

        $mform->addElement('select', 'elementshape', get_string('elementshape', 'activitymap'), $ElementShapes);

        //Node Seperation
        $mform->addElement('float', 'nodeseperation', get_string('nodeseperation', 'activitymap'));
        $mform->setDefault('nodeseperation', 1.0);

        //contentSetting
        $contentSetting = array(
            "allSectionsGrouped" => get_string('allSectionsGrouped', 'activitymap'),
            "allSections" => get_string('allSections', 'activitymap'),
            "currentSection" => get_string('currentSection', 'activitymap'),  
        );

        $mform->addElement('select', 'content', get_string('content', 'activitymap'), $contentSetting);

        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }


    function validation($data, $files) {
        $errors = parent::validation($data, $files);
        return $errors;
    }

}
