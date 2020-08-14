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
  * Activitymap module view map
  *
  * @package    mod_activitymap
  * @copyright  2020 Guenther Hutter, Andreas Poetscher
  * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
  */


class backup_activitymap_activity_structure_step extends backup_activity_structure_step {
 
    protected function define_structure() {
 
        // To know if we are including userinfo
        $userinfo = $this->get_setting_value('userinfo');
 
        // Define each element separated
        $activitymap = new backup_nested_element('activitymap', array('id'), array(
            'name', 'intro', 'introformat', 'graphdirection',
            'content', 'nodeseperation', 'sectionbackgroundcolor', 'edgestyle',
            'elementshape'));

        // Build the tree
        // No Tree needed. We have only one db table

        // Define sources
        $activitymap->set_source_table('activitymap', array('id' => backup::VAR_ACTIVITYID));
  
        // Define id annotations
 
        // Define file annotations
        $activitymap->annotate_files('mod_activitymap', 'intro', null, $contextid = null); // This file area does not have an itemid.

        // Return the root element (actionmao), wrapped into standard activity structure
        return $this->prepare_activity_structure($activitymap);
    }
}
