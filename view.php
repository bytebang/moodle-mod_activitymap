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


require('../../config.php');
use availability_completion\condition;
require_once($CFG->libdir . '/completionlib.php');

// Parameters
$id = optional_param('id', 0, PARAM_INT);        // Course module ID
$plainrendering = optional_param('plain', 0, PARAM_INT); // Display borderless as image
$lightweightrendering = optional_param('lightweight', 0, PARAM_INT); // Display borderless as svg
$cm = get_coursemodule_from_id('activitymap', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);
$courseid = $cm->course;


require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/activitymap:view', $context);
$PAGE->set_url('/mod/activitymap/view.php', array('id' => $cm->id));

/// Print the page header
if($plainrendering == false && $lightweightrendering == false)
{
    echo $OUTPUT->header();
}

$module = $DB->get_record('activitymap', array('id'=>$cm->instance), '*', MUST_EXIST);


// Find the images of the activities and store them for later
$modinfo = get_fast_modinfo($courseid);
$moduleimageurls = array();
foreach ($modinfo->cms as $otherid => $othercm) {
    $cmIconUrl = new moodle_url('/mod/' . $othercm->modname . '/pix/icon.png');
    array_push($moduleimageurls, strval($cmIconUrl));
}
$moduleimageurls = array_unique($moduleimageurls, SORT_STRING);

// Quick and dirty Ajax get (since i dont know how AMD works)
// NOTE: THIS IS ABSOLUTELY UGLY AND HAS TO BE FIXED IN THE FURURE !!!
?>

<script src="javascript/viz.js"></script>
<script src="javascript/full.render.js"></script>
<script src="javascript/svg-pan-zoom.js"></script>
<div id="activitymap"></div>
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
    print("            viz.renderImageElement(xmlhttp.responseText");
}
else
{
    print("            viz.renderSVGElement(xmlhttp.responseText");
}

print(",{ images: [".PHP_EOL);

// Inject the images from the course into Emscripten's in-memory filesystem 
foreach($moduleimageurls as $img)
{
    print("{ path: '$img', width: '30px', height: '30px' },".PHP_EOL);
}
print("]})".PHP_EOL);
?>
          
          .then(function(element) {
                document.getElementById("activitymap").appendChild(element);
                
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
if($plainrendering == false && $lightweightrendering == false)
{
    echo $OUTPUT->footer();
}

