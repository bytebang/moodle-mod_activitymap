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

$debug = optional_param('debug', false, PARAM_BOOL);

$id       = optional_param('id', 0, PARAM_INT);        // Course module ID
$cm = get_coursemodule_from_id('actionmap', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);
$courseid = $cm->course;

$advMap = $DB->get_record('actionmap', array('id'=>$cm->instance), '*', MUST_EXIST);

require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/actionmap:view', $context);

$PAGE->set_url('/mod/actionmap/gvizdot.php', array('id' => $cm->id));
$modinfo = get_fast_modinfo($courseid);

$completion = new completion_info($course);

//------------------------------------------------------------------------------
//              LINKS AND DOCUMENTATION
//------------------------------------------------------------------------------

// https://docs.moodle.org/dev/Conditional_activities_API -> Deprecated

// Test für Conditions: https://github.com/moodle/moodle/blob/6153be6850869cdc3a6ae925dcf6e688ac481333/availability/condition/completion/tests/condition_test.php
// Zugriffsbaum für Conditions: https://github.com/moodle/moodle/blob/6153be6850869cdc3a6ae925dcf6e688ac481333/availability/classes/tree.php
// Coole doku für die Klassen: https://wimski.org/api/3.8/d8/d46/classcore__availability_1_1info.html

// Interessante Tabellen: mdl_{course, couse_modules, course_sections, assign}


//------------------------------------------------------------------------------
//              DEBUGGING STUFF
//------------------------------------------------------------------------------

// If we want to debug, then just print the raw data and exit
if($debug)
{
    print_r($modinfo); 
    die;
}
//------------------------------------------------------------------------------
//              HELPER FUNCTIONS
//------------------------------------------------------------------------------
/**
    returns true if the string needle is at the beginning of the stirng haystack
    @param haystack String that should be tested
    @param needle String that is haystack supposed to start with 
*/
function startsWith($haystack, $needle)
{
     $length = strlen($needle);
     return (substr($haystack, 0, $length) === $needle);
}

//------------------------------------------------------------------------------
/**
    returns true if the string needle is at the end of the stirng haystack
    @param haystack String that should be tested
    @param needle String that is haystack supposed to end with 
*/
function endsWith($haystack, $needle)
{
    $length = strlen($needle);
    if ($length == 0) {
        return true;
    }

    return (substr($haystack, -$length) === $needle);
}
//------------------------------------------------------------------------------
/**
    Converts one or more lines of HTML code into the HTML dialect that DOT understands
    by stripping out unnecessary Tags and so on.
    
    @see https://graphviz.org/doc/info/shapes.html#html
    
*/
function convertToGraphvizTextitem($content)
{
    // Define all the allowed tags
    $allowed_tags = array(
            '<BR/>'     => '::DO_A_LINE_BREAK::',
            '<FONT>'    => '::FONT_CHANGE_START::',
            '</FONT>&nbsp;'   => '::FONT_CHANGE_END::',
            '<I>'       => '::ITALIC_START::',
            '</I>&nbsp;'      => '::ITALIC_END::',
            '<B>'       => '::BOLD_START::',
            '</B>&nbsp;'     => '::BOLD_END::',
            '<O>'       => '::O_START::',
            '</O>&nbsp;'      => '::O_END::',
            '<U>'       => '::UNDERLINE_START::',
            '</U>&nbsp;'      => '::UNDERLINE_END::',
            '<S>'       => '::STRIKETHROUGH_START::',
            '</S>&nbsp;'      => '::STRIKETHROUGH_END::',
            '<SUB>'     => '::SUBSCRIPT_START::',
            '</SUB>&nbsp;'    => '::SUBSCRIPT_END::',
            '<SUP>'     => '::SUPERSCRIPT_START::',
            '</SUP>&nbsp;'    => '::SUPERSCRIPT_END::'
            ); 
    
    $ret = $content;
    //return $ret;
    // Now do the replacements of your choice
    
    $ret = str_replace("</p>", "::DO_A_LINE_BREAK::", $ret);
    $ret = str_replace("<br/>", "::DO_A_LINE_BREAK::", $ret);
    $ret = str_replace("<br>", "::DO_A_LINE_BREAK::", $ret);
    
    $ret = str_replace("<strong>", "::BOLD_START::", $ret);
    $ret = str_replace("</strong>", "::BOLD_END::", $ret);
    
    $ret = str_replace("<em>", "::ITALIC_START::", $ret);
    $ret = str_replace("</em>", "::ITALIC_END::", $ret);

    $ret = str_replace("<u>", "::UNDERLINE_START::", $ret);
    $ret = str_replace("</u>", "::UNDERLINE_END::", $ret);
        
    $ret = str_replace("<del>", "::STRIKETHROUGH_START::", $ret);
    $ret = str_replace("</del>", "::STRIKETHROUGH_END::", $ret);

    $ret = str_replace("<sub>", "::SUBSCRIPT_START::", $ret);
    $ret = str_replace("</sub>", "::SUBSCRIPT_END::", $ret);
        
    $ret = str_replace("<sup>", "::SUPERSCRIPT_START::", $ret);
    $ret = str_replace("</sup>", "::SUPERSCRIPT_END::", $ret);

    $ret = str_replace("<li>", "&#8226; ", $ret);
    $ret = str_replace("</li>", "::DO_A_LINE_BREAK::", $ret);    
    
    // Strip all remeaning html tags
    $ret = strip_tags($ret);
    
    // and replace the paceholders with the one which are understood by GV
    $ret = str_replace(array_values($allowed_tags), array_keys($allowed_tags), $ret);
    
    // some final postprocessing
    // like strippeng <BR/> at the end     
    while(endsWith($ret, "<BR/>"))
    {
        $ret = substr($ret, 0, strlen($ret) - strlen("<br/>"));
    }
    
    return $ret;
}
//------------------------------------------------------------------------------
/**
    Generates conditions from the availability information
    
    @param coursemodule the current course module where the condiotions apply against
    @param cond Current condition (with ossible subconditions). i.e.: 
                {"op":"&","showc":[true,true],"c":[{"op":"|","c":[{"type":"completion","cm":24,"e":1},{"type":"completion","cm":25,"e":1}]},{"type":"completion","cm":28,"e":1}]}
    @param links an array where additional arays of the form [from, to, array(lonk_format)] can be pushed
    @param level current level of the tree
*/
function generateConditionLinks($basecm, $cond, &$edges, &$nodes, &$subgraph, $level)
{
    
    // increment level counter
    $level = $level + 1;
    
    //print_r($cond); print(PHP_EOL);
    if(array_key_exists("op", $cond))
    {
    
        // If there is more than one condition then we want to
        if(count($cond->c) > 1)
        {
            // print("basecm: " . $basecm . PHP_EOL);
            // Bei OR einen Knoten einfügen
            
            $joinstyle = array();
            
            if($cond->op == "&")
            {
                $joinnode = "condition_" . $level . "_AND_" . $basecm; 
                $joinstyle["label"] = htmlentities(get_string('condition_AND_label', 'actionmap'));
                $joinstyle["tooltip"] = htmlentities(get_string('condition_AND_tooltip', 'actionmap'));
            }
            if($cond->op == "|")
            {
                $joinnode = "condition_" . $level . "_OR_" . $basecm; 
                $joinstyle["label"] = get_string('condition_OR_label', 'actionmap');
                $joinstyle["tooltip"] = get_string('condition_OR_tooltip', 'actionmap'); 
            }
            
            // Add the joinnode to the list of nodes
            $joinstyle["shape"] = "circle";
            $joinstyle["style"] = "filled";
            $joinstyle["color"] = "lightgrey";
            $joinstyle["fixedsize"] = "true";
            $joinstyle["width"] = "0.5";
            $nodes[$joinnode] = $joinstyle;
            
            // Link the joinnode to the basenode
            if(startsWith($basecm, "condition"))
            {
                array_push($edges, [$joinnode, $basecm, array()]); 
                array_push($subgraph, $joinnode); // joinnodes belong to the same subgraph as the node where they originate from
            }
            else
            { 
                array_push($edges, [$joinnode, "cm_" . $basecm, array()]); 
                array_push($subgraph, $joinnode); // joinnodes belong to the same subgraph as the node where they originate from
            }
            // And set the join node as the current base node
            $basecm = $joinnode;
        }
        
        
        // Generate the Links
        foreach ($cond->c as $condition) 
        {
            // There may be a group of subconditions. 
            // This is indicated by the presence of an additional "op"
            if(array_key_exists("op", $condition))       
            {
                // Recursive !
                generateConditionLinks($basecm, $condition, $edges, $nodes, $subgraph, $level);
            } 
            else
            {
                if($condition->type == "completion")
                {
                    if(startsWith($basecm, "condition"))
                    {
                        array_push($edges, ["cm_".$condition->cm, $basecm, array()]); 
                    }
                    else
                    {
                        array_push($edges, ["cm_".$condition->cm, "cm_" . $basecm, array()]); 
                    }
                }
                else if($condition->type == "date")
                {
                    // Create nodes for timestamps
                    $tstmpnode = "timestamp_" . $basecm . "_" . $level . "_" . $condition->t; 
                    $tstmpstyle = array();
                    $tstmpstyle["shape"] = "octagon";
                   
                    // Give the user a hint how long it is to wait
                    $daydiff = (int) (($condition->t - time()) / (60*60*24));
                    $tstmpstyle["tooltip"] = htmlentities(get_string('this_is', 'actionmap')) . $daydiff . " " . htmlentities(get_string('days_from_now', 'actionmap'));
                    
                    if($condition->d == "<")
                    {
                        $tstmpstyle["label"] = "&#8986; " . htmlentities(get_string('before', 'actionmap')) . " " . str_replace(",", "<BR/>", userdate($condition->t));
                        if(time() < $condition->t)
                        {
                            $tstmpstyle["fontcolor"] = "black";
                        }
                        else
                        {
                            $tstmpstyle["fontcolor"] = "grey";
                        } 
                        
                    }
                    else if ($condition->d == ">=")
                    {
                        $tstmpstyle["label"] = "&#8986; " . htmlentities(get_string('after', 'actionmap')) . " " .  str_replace(",", "<BR/>", userdate($condition->t));
                        if(time() >= $condition->t )
                        {
                            $tstmpstyle["fontcolor"] = "black";
                        }
                        else
                        {
                            $tstmpstyle["fontcolor"] = "grey";
                        } 
                    }
                    
                    // Insert the node into the nodes list
                    $nodes[$tstmpnode] = $tstmpstyle;
                    
                    // And the dependency into the dependency list
                    array_push($edges, [$tstmpnode, $basecm, array()]); 
                    
                    // joinnodes belong to the same subgraph as the node where they originate from
                    array_push($subgraph, $tstmpnode); 
                }
            }
        }
    }
}

//------------------------------------------------------------------------------
//              ACTUAL CODE STARTS HERE
//------------------------------------------------------------------------------
echo ("digraph course_".$courseid.PHP_EOL);
print("{".PHP_EOL);
print("graph [fontname = \"helvetica\" tooltip=\"" . $course->fullname . "\" ranksep=\"". ($advMap->nodeseperation) ."\" nodesep=\"" . ($advMap->nodeseperation) . "\" splines=\"" . ($advMap->edgestyle)."\" ];".PHP_EOL);

print("node [fontname = \"helvetica\"];".PHP_EOL);
print("edge [fontname = \"helvetica\"];".PHP_EOL);
//Value for the  graphdirection from the Actionmap Database record is used
print("rankdir=".$advMap->graphdirection.";".PHP_EOL);


// Process the conditions
$gvnodes = array(); //<! Nodes which should be rendered
$gvedges = array(); //<! Graphviz links between the course modules    
$gvsubgr = array(); //<! List of Subgraphs with the course modules in it

foreach ($modinfo->cms as $id => $othercm) {
    // Add each course-module if it has completion turned on and is not
    // the one currently being edited.
    if ($othercm->completion && (empty($cm) || $cm->id != $id) && !$othercm->deletioninprogress && $othercm->visible) {

        // If mode is to display only the current section content, then we dont need to process the others
        if($advMap->content == "currentSection" && $cm->section != $othercm->section)
        {
            continue;
        } 

        $gvnodeattributes = array(); //<! Graphviz node attributes         
        $gvnodeattributes["shape"] = $advMap->elementshape;
        $gvnodeattributes["label"] = "<b>" . $othercm->name . "</b>";
        $gvnodeattributes["tooltip"] = $othercm->name;
        
        // Remember in which section this cm is
        if(array_key_exists ($othercm->sectionnum , $gvsubgr) == false)
        {
            $gvsubgr[$othercm->sectionnum] = array();
        }
        array_push($gvsubgr[$othercm->sectionnum], "cm_" . $othercm->id);

        // Print description if we should
        if($othercm->showdescription)
        {
            $gvnodeattributes["label"] = "<table border=\"0\" cellborder=\"0\" cellspacing=\"1\"> <tr><td align=\"center\">" . $gvnodeattributes["label"] . "</td></tr><hr/><tr><td balign=\"left\">" . convertToGraphvizTextitem($othercm->content) . "</td></tr></table>";
        }

        // Display available activities in black, others in grey 
        if ($othercm->uservisible == 1)
        {
            $gvnodeattributes["fontcolor"] = "black";
            $gvnodeattributes["URL"] = $othercm->url;
            
            // Print completed activities in green
            $cdata = $completion->get_data($othercm, false, $USER->id);
            if ($cdata->completionstate == COMPLETION_COMPLETE || $cdata->completionstate == COMPLETION_COMPLETE_PASS) 
            {
                $gvnodeattributes["fontcolor"] = "limegreen";
            }
            else if($cdata->completionstate == COMPLETION_COMPLETE_FAIL)
            {
                $gvnodeattributes["fontcolor"] = "crimson";
            }
            
        }
        else
        {
            $gvnodeattributes["fontcolor"] = "grey";
        }

        // Check if the availability depends on the completen of other modules, and if they have to be explored
        if ($othercm->availability) 
        {
            // User cannot access the activity, but on the course page they will
            // see a link to it, greyed-out, with information (HTML format) from
            
            $availabilityinfo = json_decode($othercm->availability, false);
            generateConditionLinks($othercm->id, $availabilityinfo, $gvedges, $gvnodes, $gvsubgr[$othercm->sectionnum], 0);

        } 
        
        // Add node to the list of nodes which should be processed
        $gvnodes["cm_".$othercm->id] = $gvnodeattributes;

    }
}

// Process the nodes
print(PHP_EOL . "# All activities" . PHP_EOL);
foreach ($gvnodes as $node => $attributes) 
{
        // Graphviz Knoten mit den Attributen aus $gvnodeattributes rendern
		print(" " . $node . " [");
        foreach ($attributes as $attrib => $value) 
        {
            if($attrib == "label")
            {
                // Labels are treated as HTML Strings
                print(" ". $attrib . "= < " . $value . " >");
            }
            else
            {
                // The rest is a so called escString
                print(" ". $attrib . "=\"" . $value . "\"");
            }
        }
        print(" ];".PHP_EOL);
}



// Process the conditions
print(PHP_EOL . "# Things that need to be completed" . PHP_EOL);
foreach ($gvedges as $edge) 
{
    print(" " . $edge[0] . " -> " . $edge[1] . " [");
    foreach ($edge[2] as $attribute => $value) 
    {
       print(" ". $attribute . "=\"" . $value . "\"");
    }
    
    print(" ];".PHP_EOL);

}
        
        
// Aufteilen in Subcluster (=Themen)
if ($advMap->content == "allSectionsGrouped")
{
    print(PHP_EOL . "# Activites according to sections" . PHP_EOL);
    foreach($gvsubgr as $subgraph => $nodeids) 
    {
        $secInfo = $modinfo->get_section_info($subgraph);

        // If the section has a name, then we are grouping
        if($secInfo->name)
        {
            print("subgraph cluster_section" . $subgraph . "{" . PHP_EOL);

            print("  tooltip=<" . $secInfo->name . ">;" . PHP_EOL);
            
            // Print description if we should
            if($secInfo->summary)
            {
                print("  label=< <FONT POINT-SIZE=\"20\"><B><U>" . $secInfo->name . "</U></B></FONT> <BR/><BR/>" . PHP_EOL . convertToGraphvizTextitem($secInfo->summary) . " >" . PHP_EOL);
            }
            else
            {
                print("  label=< <FONT POINT-SIZE=\"20\"><B>" . $secInfo->name . " </B></FONT> >" . PHP_EOL);
            }
            
            // Hide nodes on hidden sections
            if($secInfo->visible == 0)
            {
                print("  style=invisible;" . PHP_EOL);
            }
            else
            {
                print("  style=filled;" . PHP_EOL);
                print("  color=".$advMap->sectionbackgroundcolor.";" . PHP_EOL);
            }
            
            foreach($nodeids as $node) 
            {   
                print("  " . $node . ";" . PHP_EOL);
            }
            print(PHP_EOL . "}" . PHP_EOL);
        }
    }
}
print("}");



