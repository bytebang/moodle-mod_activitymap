<?php
 
require_once($CFG->dirroot . '/mod/actionmap/backup/moodle2/backup_actionmap_stepslib.php'); // Because it exists (must)
//require_once($CFG->dirroot . '/mod/choice/actionmap/moodle2/backup_actionmap_settingslib.php'); // Because it exists (optional)
//not implemented yet. maybe not neccesary.

/**
 * actionmap backup task that provides all the settings and steps to perform one
 * complete backup of the activity
 */
class backup_actionmap_activity_task extends backup_activity_task {
 
    /**
     * Define (add) particular settings this activity can have
     */
    protected function define_my_settings() {
        // No particular settings for this activity
    }
 
    /**
     * Define (add) particular steps this activity can have
     */
    protected function define_my_steps() {
        // Choice only has one structure step
    }
 
    /**
     * Code the transformations to perform in the activity in
     * order to get transportable (encoded) links
     */
    static public function encode_content_links($content) {
        return $content;
    }
}