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
  * Actionmap module view map
  *
  * @package    mod_actionmap
  * @copyright  2020 Günther Hutter, Robert Schrenk, Andreas Pötscher
  * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
  */


class backup_actionmap_activity_structure_step extends backup_activity_structure_step {
 
    protected function define_structure() {
 
        // To know if we are including userinfo
        $userinfo = $this->get_setting_value('userinfo');
 
        // Define each element separated
        $actionmap = new backup_nested_element('actionmap', array('id'), array(
            'name', 'intro', 'introformat', 'graphdirection',
            'content', 'nodeseperation', 'sectionbackgroundcolor', 'edgestyle',
            'elementshape'));

        // Build the tree
        // No Tree needed. We have only one db table

        // Define sources
        $actionmap->set_source_table('actionmap', array('id' => backup::VAR_ACTIVITYID));
  
        // Define id annotations
 
        // Define file annotations
        $actionmap->annotate_files('mod_actionmap', 'intro', null, $contextid = null); // This file area does not have an itemid.

        // Return the root element (actionmao), wrapped into standard activity structure
        return $this->prepare_activity_structure($actionmap);
    }
}