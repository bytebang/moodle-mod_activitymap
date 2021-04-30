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
$module = $DB->get_record('activitymap', array('id'=>$cm->instance), '*', MUST_EXIST);

//------------------------------------------------------------------------------
//                              HELPER FUNCTIONS
//------------------------------------------------------------------------------
/**
    Produces the HTML Tags which are loading the needed scripts
*/
function activitymap_html_loadscripts()
{
    $ret = "";
    $ret = $ret . "<script src='javascript/viz.js'></script>" . PHP_EOL;
    $ret = $ret . "<script src='javascript/full.render.js'></script>" . PHP_EOL;
    $ret = $ret . "<script src='javascript/svg-pan-zoom.js'></script>" . PHP_EOL;
    return $ret;
}


/**
    Searches all images from the course activities and returns an array of 
    images for it
    
    @param courseid The course from where the images should be taken
    @returns an array uf unique urls
*/
function activitymap_readImageUrlsFromCourse($courseid)
{
    $modinfo = get_fast_modinfo($courseid);
    
    $moduleimageurls = array();
    foreach ($modinfo->cms as $otherid => $othercm) {
        $cmIconUrl = new moodle_url('/mod/' . $othercm->modname . '/pix/icon.png');
        array_push($moduleimageurls, strval($cmIconUrl));
    }

    return array_unique($moduleimageurls, SORT_STRING);
}
//------------------------------------------------------------------------------
/**
    @param id The id of the activity map (course module id)
    @param dynamic What kind of output should be generated.
               o false produces an static image element (png)
               o true produces an dynamic svg element with pan & zoom
    @param injectedImages Injects the given images into the graph where they
           can be accessed from afterwards. 
               
   
*/
function activitymap_html_generateMapScript($id, $injectedImages, $dynamic)
{

    $ret = "";

    $ret = $ret . "<div id='activitymap_$id'></div>"                            . PHP_EOL;
    $ret = $ret . "<script>"                                                . PHP_EOL;
    $ret = $ret . "    if (window.XMLHttpRequest)"                          . PHP_EOL;
    $ret = $ret . "    {// code for IE7+, Firefox, Chrome, Opera, Safari"   . PHP_EOL;
    $ret = $ret . "        xmlhttp=new XMLHttpRequest();"                   . PHP_EOL;
    $ret = $ret . "    }"                                                   . PHP_EOL;
    $ret = $ret . "    else"                                                . PHP_EOL;
    $ret = $ret . "    {// code for IE6, IE5"                               . PHP_EOL;
    $ret = $ret . "        xmlhttp=new ActiveXObject('Microsoft.XMLHTTP');" . PHP_EOL;
    $ret = $ret . "    }"                                                   . PHP_EOL;
    $ret = $ret . "    xmlhttp.onreadystatechange=function()"               . PHP_EOL;
    $ret = $ret . "    {"                                                   . PHP_EOL;
    $ret = $ret . "        if (xmlhttp.readyState==4 && xmlhttp.status==200)" . PHP_EOL;
    $ret = $ret . "        {"                                               . PHP_EOL;
    $ret = $ret . "            // We have received something"               . PHP_EOL;
    $ret = $ret . "            //alert(xmlhttp.responseText);   "           . PHP_EOL;
    $ret = $ret . "            var viz = new Viz();"                        . PHP_EOL;
    
    

    // Image or SVG ?
    if($dynamic == true)
    {
         $ret = $ret . "      viz.renderSVGElement(xmlhttp.responseText"    . PHP_EOL;

    }
    else
    {
         $ret = $ret . "       viz.renderImageElement(xmlhttp.responseText" . PHP_EOL;
    }

    $ret = $ret . ",{ images: ["                                            . PHP_EOL;

    // Inject the images from the course into Emscripten's in-memory filesystem 
    foreach($injectedImages as $img)
    {
        $ret = $ret . "{ path: '$img', width: '30px', height: '30px' },"    . PHP_EOL;
    }
    
    // and build the remeaning java script
    $ret = $ret . "]})"                                                     . PHP_EOL;
    $ret = $ret . "          .then(function(element) {"                     . PHP_EOL;
    $ret = $ret . "                document.getElementById('activitymap_$id').appendChild(element);" . PHP_EOL;
    $ret = $ret . "                // Pan and zoom via 3rd party library "  . PHP_EOL;
    $ret = $ret . "                panZoom = svgPanZoom(element, {"         . PHP_EOL;
    $ret = $ret . "                  zoomEnabled: true,"                    . PHP_EOL;
    $ret = $ret . "                  controlIconsEnabled: true,"            . PHP_EOL;
    $ret = $ret . "                  fit: true,"                            . PHP_EOL;
    $ret = $ret . "                  center: true,"                         . PHP_EOL;
    $ret = $ret . "                  minZoom: 0.1"                          . PHP_EOL;
    $ret = $ret . "                });"                                     . PHP_EOL;
    $ret = $ret . "            })"                                          . PHP_EOL;
    $ret = $ret . "            .catch(error => {"                           . PHP_EOL;
    $ret = $ret . "                // Create a new Viz instance (@see Caveats page for more info)" . PHP_EOL;
    $ret = $ret . "                viz = new Viz();"                        . PHP_EOL;
    $ret = $ret . "                // Possibly display the error"           . PHP_EOL;
    $ret = $ret . "                console.error(error);"                   . PHP_EOL;
    $ret = $ret . "            });"                                         . PHP_EOL;
    $ret = $ret . "        }"                                               . PHP_EOL;
    $ret = $ret . "    }"                                                   . PHP_EOL;
    $ret = $ret . "xmlhttp.open('GET', 'gvizdot.php?id={$id}', false);"     . PHP_EOL;

    
    $ret = $ret . "xmlhttp.send(); "                                        . PHP_EOL;
    $ret = $ret . "</script>"                                               . PHP_EOL;

    return $ret;
}

//------------------------------------------------------------------------------
//                     ACTUAL PAGE OUTPUT STARTS HERE
//------------------------------------------------------------------------------

// Start the document
if($plainrendering == false && $lightweightrendering == false)
{
    print($OUTPUT->header());
}
else
{
    print("<!DOCTYPE html>" . PHP_EOL);
    print("<html>" . PHP_EOL);
    print("    <head>" . PHP_EOL);
    // If embedded then open link in parent frame
    print("        <base target='_parent' />" . PHP_EOL); 
    print("    </head>" . PHP_EOL);
    
    // Start body
    print("    <body>" . PHP_EOL);
}

// Include the needed javascripts
print(activitymap_html_loadscripts());


// Load all image urls for this course
$moduleimageurls = activitymap_readImageUrlsFromCourse($courseid);


// Quick and dirty Ajax get (since i dont know how AMD works)
// NOTE: THIS IS ABSOLUTELY UGLY AND HAS TO BE FIXED IN THE FURURE !!!
if($plainrendering == true)
{
    print(activitymap_html_generateMapScript($id, $moduleimageurls, false));
}
else
{
    print(activitymap_html_generateMapScript($id, $moduleimageurls, true));
}


// Finish the document
if($plainrendering == false && $lightweightrendering == false)
{
    print($OUTPUT->footer());
}
else
{
    print("</html>" . PHP_EOL);
}

