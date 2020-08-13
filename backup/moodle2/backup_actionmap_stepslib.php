<?php
 
/**
 * Define all the backup steps that will be used by the backup_choice_activity_task
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