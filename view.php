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


require('../../config.php');



use availability_completion\condition;

require_once($CFG->libdir . '/completionlib.php');

$id = optional_param('id', 0, PARAM_INT);        // Course module ID
$plainrendering = optional_param('plain', 0, PARAM_INT); 
$cm = get_coursemodule_from_id('actionmap', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);
$courseid = $cm->course;

require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/actionmap:view', $context);
$PAGE->set_url('/mod/actionmap/view.php', array('id' => $cm->id));

/// Print the page header
if($plainrendering == false)
{
    echo $OUTPUT->header();
}

$module = $DB->get_record('actionmap', array('id'=>$cm->instance), '*', MUST_EXIST);


// So put it directly onto the page
// Quick and dirty Ajax get (since i dont know how AMD works)
// NOTE: THIS IS ABSOLUTELY UGLY AND HAS TO BE FIXED IN THE FURURE !!!
?>

<script src="javascript/viz.js"></script>
<script src="javascript/full.render.js"></script>
<script src="javascript/svg-pan-zoom.js"></script>
<div id="actionmap"></div>
<script>
    if (window.XMLHttpRequest)
    {// code for IE7+, Firefox, Chrome, Opera, Safari
        xmlhttp=new XMLHttpRequest();
    }
    else
    {// code for IE6, IE5
        xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
    }
    xmlhttp.onreadystatechange=function()
    {
        if (xmlhttp.readyState==4 && xmlhttp.status==200)
        {
            // We have received something
            //alert(xmlhttp.responseText);   
            var viz = new Viz();
<?php
// Within plain it is enough to render an image
if($plainrendering == true)
{
    echo "            viz.renderImageElement(xmlhttp.responseText)".PHP_EOL;
}
else
{
    echo "            viz.renderSVGElement(xmlhttp.responseText)".PHP_EOL;
}
?>
            .then(function(element) {
                document.getElementById("actionmap").appendChild(element);
                
                // Enable to pan and zoom via 3rd party library 
                panZoom = svgPanZoom(element, {
                  zoomEnabled: true,
                  controlIconsEnabled: true,
                  fit: true,
                  center: true,
                  minZoom: 0.1
                });
        
            })
            .catch(error => {
                // Create a new Viz instance (@see Caveats page for more info)
                viz = new Viz();
                // Possibly display the error
                console.error(error);
            });
            

        }
    }
    <?php
    print("xmlhttp.open('GET', 'gvizdot.php?id={$id}', false);");
    ?>
    xmlhttp.send(); 
</script>

<?php

// --------------------------------
// Print the footer
if($plainrendering == false)
{
    echo $OUTPUT->footer();
}

