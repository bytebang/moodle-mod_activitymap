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
 * Activitymap module to graphviz compiler
 *
 * @package    mod_activitymap
 * @copyright  2021 Guenther Hutter, Andreas Poetscher
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require('../../config.php');

use availability_completion\condition;
require_once($CFG->libdir . '/completionlib.php');

$debug = optional_param('debug', false, PARAM_BOOL);

$cmid       = optional_param('id', 0, PARAM_INT);        // Course module ID
$mapid = get_coursemodule_from_id('activitymap', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id'=>$mapid->course), '*', MUST_EXIST);
$courseid = $mapid->course;
$coursecontext = context_course::instance($courseid);
require_course_login($course, true, $mapid);


$activitymap = $DB->get_record('activitymap', array('id'=>$mapid->instance), '*', MUST_EXIST);
$mapcontext = context_module::instance($mapid->id);
require_capability('mod/activitymap:view', $mapcontext);

$PAGE->set_url('/mod/activitymap/gvizdot.php', array('id' => $mapid->id));
$modinfo = get_fast_modinfo($courseid);

$completion = new completion_info($course);

$feature_show_cmIcons = true;
$feature_mergeSameConditionNodes = true;

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

/**
    since array_key_exists is deprecated on objects in PHP 7.4 this function 
    remodels the functionality can be used as a drop-in replacement
    for the functionality
*/
function array_contains_key($key_or_property, $array_or_class) 
{ 
    return is_object($array_or_class) ? property_exists($array_or_class, $key_or_property) : array_key_exists($key_or_property, $array_or_class); 
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
    
    // first: Remove all attributes of the html tags (=fragile !)
    $ret = preg_replace('/<(\w+)[^>]*>/', '<$1>', $content);

    // Now do the replacements of your choice
    $ret = str_replace("<br></div>", "::DO_A_LINE_BREAK::", $ret); 
    $ret = str_replace("<br></p>", "::DO_A_LINE_BREAK::", $ret);
    $ret = str_replace("</p>", "::DO_A_LINE_BREAK::", $ret);
    $ret = str_replace("<br/>", "::DO_A_LINE_BREAK::", $ret);
    $ret = str_replace("<br>", "::DO_A_LINE_BREAK::", $ret);
    $ret = str_replace("</div>", "::DO_A_LINE_BREAK::", $ret);

    $ret = str_replace("<b>", "::BOLD_START::", $ret);
    $ret = str_replace("</b>", "::BOLD_END::", $ret);    
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
    if(array_contains_key("op", $cond))
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
                $joinstyle["label"] = htmlentities(get_string('condition_AND_label', 'activitymap'));
                $joinstyle["tooltip"] = htmlentities(get_string('condition_AND_tooltip', 'activitymap'));
            }
            if($cond->op == "|")
            {
                $joinnode = "condition_" . $level . "_OR_" . $basecm; 
                $joinstyle["label"] = htmlentities(get_string('condition_OR_label', 'activitymap'));
                $joinstyle["tooltip"] = htmlentities(get_string('condition_OR_tooltip', 'activitymap')); 
            }
            
            // Add the joinnode to the list of nodes
            $joinstyle["shape"] = "circle";
            $joinstyle["style"] = "filled";
            $joinstyle["fillcolor"] = "lightgrey";

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
            if(array_contains_key("op", $condition))       
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
                else if($condition->type == "grade")
                {
                
                    // Create nodes for grades
                    $gradenode = "grade_" . $basecm . "_" . $level . "_" . $condition->type; 
                    $gradenodestyle = array();
                    $gradenodestyle["shape"] = "octagon";
                    
                    // Compose a node with the settings inside
                    $gradenodestyle["label"] = "";

                    if(array_contains_key("min", $condition))
                    {
                        $gradenodestyle["label"] = $gradenodestyle["label"] . $condition->min . " <= ";
                    }
                    
                    $gradenodestyle["label"] = $gradenodestyle["label"] . get_string('score', 'activitymap');
                    
                    if(array_contains_key("max", $condition))
                    {
                        $gradenodestyle["label"] = $gradenodestyle["label"] . " <= ". $condition->max;
                    }

                    $gradenodestyle["label"] = "&#129351;<BR/>" . htmlentities($gradenodestyle["label"]);
                    
                    // and a beautiful hint
                    $gradenodestyle["tooltip"] = get_string('score_course', 'activitymap') . " ";
                    if(array_contains_key("min", $condition) == true && array_contains_key("max", $condition) == true)
                    {
                        $gradenodestyle["tooltip"] = $gradenodestyle["tooltip"] . get_string('between', 'activitymap') . " " . $condition->min . " ". get_string('and', 'activitymap') . " " . $condition->max;
                    }
                    
                    if(array_contains_key("min", $condition) == true && array_contains_key("max", $condition) == false)
                    {
                        $gradenodestyle["tooltip"] = $gradenodestyle["tooltip"] . get_string('higher', 'activitymap') . " " . $condition->min;
                    }
                    
                    if(array_contains_key("min", $condition) == false && array_contains_key("max", $condition) == true)
                    {
                        $gradenodestyle["tooltip"] = $gradenodestyle["tooltip"] . get_string('lower', 'activitymap') . " " . $condition->max;
                    }
                    
                    // DANGER: PERHAPS THE $condition->id REFERENCES A GRADEITEM_ID AND NOT A COURSE ID.
                    //print_r($cond); print(PHP_EOL);
                    
                    // Link the course module where we are dependent from to the condition node
                    array_push($edges, ["cm_".$condition->id, $gradenode, array()]); 

                    // Insert the node into the nodes list
                    $nodes[$gradenode] = $gradenodestyle;
                             
                    // If there is no condition node (because the date is the only restriction), 
                    // then we link directly to the base node
                    if(count($cond->c) == 1 && startsWith($basecm, "cm_") == 0 && startsWith($basecm, "condition_") == 0)
                    {
                        $basecm = "cm_" . $basecm; 
                    }
        
                    // And the dependency into the dependency list
                    array_push($edges, [$gradenode, $basecm, array()]); 
                    
                    // joinnodes belong to the same subgraph as the node where they originate from
                    array_push($subgraph, $gradenode); 
                    
                    
                }
                else if($condition->type == "date")
                {
                    // Create nodes for timestamps
                    $tstmpnode = "timestamp_" . $basecm . "_" . $level . "_" . $condition->t; 
                    $tstmpstyle = array();
                    $tstmpstyle["shape"] = "octagon";
                   
                    // Give the user a hint how long it is to wait
                    $daydiff = (int) (($condition->t - time()) / (60*60*24));
                    $tstmpstyle["tooltip"] = htmlentities(get_string('this_is', 'activitymap')) . " " . $daydiff . " " . htmlentities(get_string('days_from_now', 'activitymap'));
                    
                    if($condition->d == "<")
                    {
                        $tstmpstyle["label"] = "&#8986; " . htmlentities(get_string('before', 'activitymap')) . " " . str_replace(",", "<BR/>", userdate($condition->t));
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
                        $tstmpstyle["label"] = "&#8986; " . htmlentities(get_string('after', 'activitymap')) . " " .  str_replace(",", "<BR/>", userdate($condition->t));
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
                             
                    // If there is no condition node (because the date is the only restriction), 
                    // then we link directly to the base node
                    if(count($cond->c) == 1 && startsWith($basecm, "cm_") == 0 && startsWith($basecm, "condition_") == 0)
                    {
                        $basecm = "cm_" . $basecm; 
                    }
        
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
/**
    Finds the previous node which can be completed.
    @param ccm The current course module, where we are trying to find its prececessor
    @param modinfo The full course information which should be traversed. (We could read it here as well, but for performance and memory reasons we would like to gat it handed over as parameter)
    @returns the name of the predecessor or (if not found) then it returns the string "unknown_predecessor_for_" followed by the value of ccm
*/
function findPreviousCompletionModule($ccm, $modinfo)
{
    $predecessor = null;

    foreach($modinfo->cms as $id => $cm)
    {
        // if the searched course module equals the current one, then we have found a solution
        if($ccm == "cm_".$id && $predecessor != null)
        {
            return "cm_".$predecessor;
        }
        
        // remember the curent one if it is completable
        if($cm->completion)
        {
            $predecessor = $id;
        }
    }
    
    return "unknown_predecessor_for_" . $ccm;
}

//------------------------------------------------------------------------------
/**
    Finds nodes which are connected with the target node
    
    @targetnode the node where the other node should related to
    @relation String "INCOMING" oder "OUTGOING" to tell the funciton if it 
              should return the nodes which are connected via the incoming 
              our outgoing nodes
    @edgeliste the list of nodes where should be searched within
*/
function findLinkedNodes($targetnode, $relation, $edgelist)
{
    $ret = array();

    foreach ($edgelist as $edge) 
    {
        if($relation == "INCOMING" && $targetnode == $edge[TO])
        {
            array_push($ret, $edge[FROM]);
        }
    
        if($relation == "OUTGOING" && $targetnode == $edge[FROM])
        {
            array_push($ret, $edge[TO]);
        }
    }
    
    return array_unique($ret);
}

//------------------------------------------------------------------------------
/**
    Merges condition nodes. It tries to find nodes with similar inputs, and if
    it finds some then an it prevents the rendering of the second (similar) node.
    
    I know that this approach is not very performant - approx O(n²), so 
    if somebody finds a better solution file a pull request.
    
    @param conditionNodes Nodes which should be simplified. Usually all AND or al OR nodes
    @param edges all edges which should be traversed 
    @param nodes all noes which should be traversed
    @returns nothing

*/
function mergeConditionNodes($conditionNodes, &$edges, &$nodes)
{
    // if you find a better solution file a pull request 
    
    $processedConditionNodes = array(); // conditions that we have already examined
    for($i = 0; $i < sizeof($conditionNodes); $i++) 
    {
        $a = $conditionNodes[$i];
        
        // for each condition find other conditions that have the same inputs
        $incomingNodes_a = findLinkedNodes($a, "INCOMING", $edges);
        array_push($processedConditionNodes, $a);
        
        // search all other nodes
        for($j = $i+1; $j < sizeof($conditionNodes); $j++) 
        {
            $b = $conditionNodes[$j];
            // we dont have to look at the processed nodes again
            if(in_array($b, $processedConditionNodes, true))
            {
                continue;
            }           
            
            // clarification: Now we have foud two nodes which have potentially 
            // the same inputs lets compare them
            $incomingNodes_b = findLinkedNodes($b, "INCOMING", $edges);
            
           
            // Compare if all nodes from a are in b and vice versa
            // otherwise conditions a&b  is treated as different to b&a    
            if(array_diff($incomingNodes_a, $incomingNodes_b) == array_diff($incomingNodes_b, $incomingNodes_a))
            //if($incomingNodes_a == $incomingNodes_b)
            {
                // YES - we found a pair of nodes which have exactly the same inputs

                // Make the incoming and outgoing links for $b invisible
                for($k = 0; $k < sizeof($edges); $k++) 
                {
                    if($edges[$k][FROM] == $b || $edges[$k][TO] == $b)
                    {
                        $edges[$k][STYLE]["color"] = "red";
                        $edges[$k][STYLE]["DO_NOT_RENDER"] = true;
                    }
                }                
                
                // reattach outgoing nodes
                $outgoingNodes_b = findLinkedNodes($b, "OUTGOING", $edges);
                foreach($outgoingNodes_b as $outb)
                {
                    $theNewLink = [$a, $outb, array("arrowhead" => "open")];
                    //$theNewLink[STYLE]["color"] = "green";
                    array_push($edges, $theNewLink); 
                }
                
                // Make $b invisible
                $nodes[$b]["color"] = "red";
                $nodes[$b]["DO_NOT_RENDER"] = true;                
                
                // Remember that we have processed this node
                array_push($processedConditionNodes, $b);
            }
        }
    }
}
//------------------------------------------------------------------------------
//              ACTUAL CODE STARTS HERE
//------------------------------------------------------------------------------
echo ("digraph course_".$courseid.PHP_EOL);
print("{".PHP_EOL);
print("graph [fontname = \"helvetica\" tooltip=\"" . htmlentities($course->fullname) . "\" ranksep=\"". ($activitymap->nodeseperation*0.5) ."\" nodesep=\"" . ($activitymap->nodeseperation*0.25) . "\" splines=\"" . ($activitymap->edgestyle)."\" ];".PHP_EOL);

print("node [fontname = \"helvetica\"];".PHP_EOL);
print("edge [fontname = \"helvetica\"];".PHP_EOL);
//Value for the  graphdirection from the Activitymap Database record is used
print("rankdir=".$activitymap->graphdirection.";".PHP_EOL);


// Process the conditions
$gvnodes = array(); //<! Nodes which should be rendered key=name_of_the_node, value=styleattributes 

// Improove readability
const FROM = 0;
const TO = 1;
const STYLE = 2;
$gvedges = array(); //<! Graphviz links between the course modules   [0]=from, [1]=to, [2]=styleattributes 
$gvsubgr = array(); //<! List of Subgraphs with the course modules in it

foreach ($modinfo->cms as $id => $cm) {
    
    // Add each course-module if it has completion turned on and is not
    // the one currently being edited.
    if ($cm->completion && (empty($mapid) || $mapid->id != $id) && !$cm->deletioninprogress && $cm->visibleoncoursepage == 1) {

        // If mode is to display only the current section content, then we dont need to process the others
        if($activitymap->content == "currentSection" && $mapid->section != $cm->section)
        {
            continue;
        } 
        $gvnodeattributes = array(); //<! Graphviz node attributes         
        $gvnodeattributes["shape"] = $activitymap->elementshape;
        $gvnodeattributes["label"] = "<b>" . htmlentities($cm->name) . "</b>";
        $gvnodeattributes["tooltip"] = htmlentities($cm->name);
      
        // Get the icon url of the activity and append it (See #14)
        // If this mechanism is changed, then it also has to be changed in the view.php
        if($feature_show_cmIcons == true)
        {
            $cmIconUrl = new moodle_url('/mod/' . $cm->modname . '/pix/monologo.png');
            $gvnodeattributes["label"] = "<TABLE BORDER=\"0\" CELLPADDING=\"0\" CELLSPACING=\"0\"><TR><TD><IMG SRC=\"$cmIconUrl\"/></TD><TD>&nbsp; " . $gvnodeattributes["label"] . "</TD></TR></TABLE>";
        }

        // Issue #16: Add tick/cross dependent on completion.
        $cdata = $completion->get_data($cm, false, $USER->id);
        if ($cdata->completionstate == COMPLETION_COMPLETE || $cdata->completionstate == COMPLETION_COMPLETE_PASS)
        {
            $gvnodeattributes["label"] = "<TABLE BORDER=\"0\" CELLPADDING=\"0\" CELLSPACING=\"0\"><TR><TD>" . $gvnodeattributes["label"] . "</TD><TD><FONT COLOR=\"limegreen\" POINT-SIZE=\"20\">&nbsp; &#10004;</FONT></TD></TR></TABLE>";
        }
        else if($cdata->completionstate == COMPLETION_COMPLETE_FAIL)
        {
            $gvnodeattributes["label"] = "<TABLE BORDER=\"0\" CELLPADDING=\"0\" CELLSPACING=\"0\"><TR><TD>" . $gvnodeattributes["label"] . "</TD><TD><FONT COLOR=\"crimson\" POINT-SIZE=\"20\">&nbsp; &#10060;</FONT></TD></TR></TABLE>";
        }

        // Issue #10: Activities hidden by restriction condition should not be displayed
        if($cm->visible == false)
        {
            $gvnodeattributes["shape"] = "point";
            $gvnodeattributes["label"] = "";
            $gvnodeattributes["tooltip"] = get_string('hidden_activity', 'activitymap');
            $gvnodeattributes["peripheries"] = "0";
            $gvnodeattributes["height"] = "0.1";
            $gvnodeattributes["width"] = "0.1";
        }
                
        // Remember in which section this cm is
        if(array_key_exists($cm->sectionnum , $gvsubgr) == false)
        {
            $gvsubgr[$cm->sectionnum] = array();
        }
        array_push($gvsubgr[$cm->sectionnum], "cm_" . $cm->id);

        // Display available activities in black, others in grey 
        if ($cm->uservisible == 1)
        {
            $gvnodeattributes["fontcolor"] = "black";
            $gvnodeattributes["URL"] = $cm->url;
            $gvnodeattributes["target"] = "_parent";

            
            // If we are allowed to edit activities, then provide a link here, then we can directly jump to the activity-editor module
            if (has_capability('moodle/course:manageactivities', $coursecontext)) 
            {
                // Link to the edit page of the activity
                $editUrl = new moodle_url('/course/modedit.php', ['update' => $cm->id]);
                
                // Add a edit symbol in front of tne label
                $gvnodeattributes["label"] = "<TABLE BORDER=\"0\" CELLPADDING=\"0\" CELLSPACING=\"0\"><TR><TD>" . $gvnodeattributes["label"] . "</TD><TD HREF=\"". $editUrl->__toString() ."\" target=\"_parent\"><FONT POINT-SIZE=\"20\">&nbsp; &#x2699;</FONT></TD></TR></TABLE>";
            }
        }
        else
        {
            $gvnodeattributes["fontcolor"] = "grey";
        }

        // Print description if we should
        if($cm->showdescription)
        {
            $gvnodeattributes["label"] = "<table border=\"0\" cellborder=\"0\" cellspacing=\"1\"> <tr><td align=\"center\">" . $gvnodeattributes["label"] . "</td></tr><hr/><tr><td balign=\"left\">" . convertToGraphvizTextitem($cm->content) . "</td></tr></table>";
        }
        
        // Check if the availability depends on the completen of other modules, and if they have to be explored
        if ($cm->availability) 
        {
            // User cannot access the activity, but on the course page they will
            // see a link to it, greyed-out, with information (HTML format) from
            
            $availabilityinfo = json_decode($cm->availability, false);
            generateConditionLinks($cm->id, $availabilityinfo, $gvedges, $gvnodes, $gvsubgr[$cm->sectionnum], 0);

        } 
        
        // Add node to the list of nodes which should be processed
        $gvnodes["cm_".$cm->id] = $gvnodeattributes;

    }
}

//------------------------------------------------------------------------------
//              Postprocessing
//------------------------------------------------------------------------------
// Simplify the graph by merging nodes with similar inputs together 

if($feature_mergeSameConditionNodes == true)
{
    // Find the condition nodes
    $andConditions = array();
    $orConditions = array();
    foreach ($gvnodes as $node => $attributes) 
    {
        if(startsWith($node, "condition_") == 0)
        {
            continue;
        }
     
        if(strpos($node, "_AND_") > 0)
        {
            array_push($andConditions, $node);
        }
        
        if(strpos($node, "_OR_") > 0)
        {
            array_push($orConditions, $node);
        }
    }
    
    // OK, now find nodes with the same inputs. Because we can merge
    // these ones together into one node with the same inputs but more outputs.
    
    // This functionality has been refactored into a function - otherwise it meight
    // become unmaintainable in the future
    mergeConditionNodes($andConditions, $gvedges, $gvnodes);
    mergeConditionNodes($orConditions, $gvedges, $gvnodes);
}
  


//------------------------------------------------------------------------------
//              Generating output
//------------------------------------------------------------------------------
print(PHP_EOL . "# All activities" . PHP_EOL);
foreach ($gvnodes as $node => $attributes) 
{

        // This is a non-dot conformant extension which prevents the output of an edge.
        if(array_key_exists("DO_NOT_RENDER",$attributes))
        {
            continue;
        }
        
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


// Output the conditions
print(PHP_EOL . "# Things that need to be completed" . PHP_EOL);

// Process the edges
$nodesWithoutInfo = array();
foreach ($gvedges as $edge) 
{

    // Replace the "previous conditions" in the list of edges which are now named "cm_-1" with their correct counterparts
    // See Issue #11
    if($edge[FROM] == "cm_-1")
    {
        // Search for the previous completabe module and replace the source node with the correct name
        $edge[FROM] = findPreviousCompletionModule($edge[TO], $modinfo);
    }

    // Look if we have a edge without a node that has beed processed.
    // this is sometime the case when we display only the current section
    // so lets remember this one and add a node later
    if(array_key_exists($edge[FROM], $gvnodes) == false or startswith($edge[FROM], "unknown"))
    {
        array_push($nodesWithoutInfo, $edge[FROM]);
    }
    
    // This prevents the output of an edge.
    if(array_key_exists("DO_NOT_RENDER", $edge[STYLE]))
    {
        continue;
    }
    
    // And finally paint the edge
    print(" " . $edge[FROM] . " -> " . $edge[TO] . " [");
    foreach ($edge[STYLE] as $attribute => $value) 
    {
       print(" ". $attribute . "=\"" . $value . "\"");
    }
    
    print(" ];".PHP_EOL);

}


// Add the missing nodes (in order to avoid nodes with just a 
// non-meaningful name like cm_54
print(PHP_EOL . "# Dependent nodes which are not part of the current selection" . PHP_EOL);
foreach ($nodesWithoutInfo as $node) 
{
    // Try to read info from this node
    if(startswith($node, "cm_")) // cm indicates a course module
    {
        $nodeid = substr($node, 3, strlen($node));
       
        if($nodeid == 0)
        {
            // This should never happen, but sometime it does ...
            // e.g. if some dependencies are unmaintained.
            print($node . " [style=\"dotted\" fontcolor=\"red\" label=\"" . $node . "\" tooltip=\"" . get_string('not_existing_activity', 'activitymap') . "\" ]" . PHP_EOL);
	    }
	    elseif($modinfo->cms[$nodeid]->name == "")
	    {
	        // Should also not happen, but .. guess what .. sometime it does
            print($node . " [style=\"dotted\" fontcolor=\"red\" label=\"" . $node . "\" tooltip=\"" . get_string('unknown_existing_activity', 'activitymap') . "\" ]" . PHP_EOL);
	    }
        else
        {
        
            if($modinfo->cms[$nodeid]->visible == true)
            {
                // Lets find out the name of the course module
                print($node . " [style=\"dotted\" fontcolor=\"slategrey\" label=<<I>" . htmlentities($modinfo->cms[$nodeid]->name) . "</I>> tooltip=\"" . get_string('activity_from_other_section', 'activitymap') . "\"]" . PHP_EOL);
            }
            else
            {
                // We have a coursemodule from another section which is hidden
                // Should also not happen, but .. guess what .. sometime it does
            print($node . " [style=\"dotted\" fontcolor=\"slategrey\" label=\"" . get_string('hidden_activity', 'activitymap') . "\" tooltip=\"" . get_string('activity_from_other_section', 'activitymap') . "\" ]" . PHP_EOL);
            }

        }

    }
    else
    {    
        // actually we should not end up here
        print($node . " [style=\"dotted\" fontcolor=\"red\" label=\"" . $node . "\" tooltip=\"" . get_string('activity_from_other_section', 'activitymap') . "\" ]" . PHP_EOL);
    }
}
     
  
// Build subclusters (=Topics)
if ($activitymap->content == "allSectionsGrouped")
{
    print(PHP_EOL . "# Activites according to sections" . PHP_EOL);
    foreach($gvsubgr as $subgraph => $nodeids) 
    {
        $secInfo = $modinfo->get_section_info($subgraph);

        // If the section has a name, then we are grouping
        if($secInfo->name)
        {
            print("subgraph cluster_section" . $subgraph . "{" . PHP_EOL);
            print("  tooltip=<" . htmlentities($secInfo->name) . ">;" . PHP_EOL);
            
            // Print description if we should
            if($secInfo->summary)
            {
                print("  label=< <FONT POINT-SIZE=\"20\"><B><U>" . htmlentities($secInfo->name) . "</U></B></FONT> <BR/><BR/>" . PHP_EOL . convertToGraphvizTextitem($secInfo->summary) . " >" . PHP_EOL);
            }
            else
            {
                print("  label=< <FONT POINT-SIZE=\"20\"><B><U>" . htmlentities($secInfo->name) . "</U></B></FONT> >" . PHP_EOL);
            }
            
            // Hide nodes on hidden sections
            if($secInfo->visible == 0)
            {
                print("  style=invisible;" . PHP_EOL);
            }
            else
            {
                print("  style=filled;" . PHP_EOL);

                if($activitymap->sectionbackgroundcolor == "random")
                {
                    // Pseudo random color: same sectionname should give the same color
                    $color = strtoupper(md5($secInfo->name));
                    $color = preg_replace("/[^B-E]/", '', $color);
                    $color = substr($color . "ABCDEF", 0, 6);
                    print("  color=\"#" . $color . "\";" . PHP_EOL);
                }
                else
                {
                    print("  color=".$activitymap->sectionbackgroundcolor.";" . PHP_EOL);
                }
                
            }
            
            foreach($nodeids as $node) 
            {   
                // This prevents the output of intentionally hidden nodes in the subgraph
                if(array_key_exists("DO_NOT_RENDER", $gvnodes[$node]))
                {
                    continue;
                }
                print("  " . $node . ";" . PHP_EOL);
            }
            print(PHP_EOL . "}" . PHP_EOL);
        }
    }
}
print("}");



