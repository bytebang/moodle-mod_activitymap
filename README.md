# moodle-mod_actionmap

This module allows teachers to vizualize dependencies between actions in a interactive graph. 

## Purpose
An actionmap is an automatically generated graph which visualizes the dependencies between action that arise due to completion restrictions.

![Example of a rendered dependency graph](pix/sample_dependency_graph.png)

It can be used give the students a clue about the different actions and in which order they have to be completed to reach a certain goal. In this terms it is a very versatile tool for individualization and helps to improve the ability of the participants to choose their own learning path through a course.


## Usage

### Creating a graph

We have good news for you - you do not need to draw or create a graph by your own; It is automatically generated by the actions within your course - as long as you have enabled completion tracking within your course.


As you surely know, actions like Assignments, Pages, URLs, ...  can have (nested) restrictions under which circumstances the activity is enabled. If the restricion is of the type _Required completion Status_ then we draw a directed edge to the current action from the action which is required to be completed. 

If there are no restrictions, then we only draw the activity boxes - without edges between them.

We have tried to display the same things in the graph, as you would display in your course. If you add a description to an activity or an a section (and the description is shown on the course page) then it will also be shown in the graph. for security reasons we only display the plain text with a few HTML attributes (`<strong>`, `<em>`, `<u>`, `<del>`, `<sub>`, `<sup>`). All other elements are stripped.

The font color of the nodes is representing the completion state:

* grey: This activity is not available for the user because the preconditions have not been fulfilled. 
* black: This activity can be processed.

additionally there can be two symbols:

* green checkmark: The activity has been (sucessfully) completed.
* red croxx: The activity has been completed, but the student failed.

### Module Settings

The default settings allow the user to create an overview about the whole course dependencies, grouped by the sections where the actions are appearing in. If you want to change this behaviour or the appearence of the graph then you have the following options

#### Graph direction
The main-direction of the graph can be either `Top -> Down` or `Left -> Right` (or the other way around) and influences the visual appearence of the graph. If `Top -> Down` is selected then the graph is laid out from top to bottom - meaning that the directed edges  tend to go from top to bottom. 

#### Section background color
It is possible to group the action nodes by their section they appear in. This option defines the background color of this grouping. If you choose `Random`, then every block will have its distinct color.

#### Element shape
The actions can appear in different shapes. Here you can define which shape should be used.

#### Edge type
The arrows between the actions (=edges) can appear in different shapes. Here you can define which shape should be used.

#### Node seperation
This setting defines the minimum space between two adjacent nodes. If you think that the nodes are too close to each other then try to play with this setting ba increasing it.

#### Content 

This setting defines what content of the course should be queried to calculate the graph. There are three different settings available:

* `All sections` draws a graph where all actions of all sections are rendered into a single graph. 
* `All sections grouped` draws also a graph with all activities - but it groups them togehter into their sections. The background color can be modified by the _Section background color_ setting.
* `Current section` draws a graph which shows only the activities within the section where the actionmap is placed and its direct dependencies. If there are references from outside the current section, then these nodes will be elipse-shaped with a grey font have a dotted border. The color of this outside nodes does have nothing to do with their completion status.


### Frequently Asked Questions

#### Can I provide more information about an activity in the graph ?
Yes. Just place the additional information for the activity in the _Description_ field of the activity and check the _Display description on course page_ option.
  
#### Can I provide more information about a section in the graph ?
Yes. Just place the additional information for the section in the _Description_ field of the activity and check the _Display description on course page_ option.

#### How can i exclude things from the graph ?
We only display actions which have some sort of completion tracking. So if you want to exclude something from the graph set the _Completion tracking_ option to the value _Do not indicate activity completion_.

#### Is it possible to show the graph directly in the course page ?
Yes - Create a label and place an `<iframe>` tag that references the actionmap. By appending the parameter `plain=1` you will get the pure graph - without the moodle headers and footers.

Here an example `https://my.moodle.com/moodle/mod/actionmap/view.php?id=47&plain=1`

#### Which activity restricions are beeing processed ?
We only process the _Activity completion_ and the _Date_ restricion. Other restrictions are neither processed nor visualized.


## Authors

* Günther Hutter (guenther.hutter@htl-leoben.at)
* Andreas Pötscher (andreas.poetscher@htl-leoben.at)

## Credits

We want to thank Robert Schrenk for providing us the base knowledge to create this beautiful plugin.


Furthermore we want to give credits to the [viz.js](https://github.com/mdaines/viz.js) framework which is used to render the graphs on the clientside by utilizing a webasembly version of the [Graphviz](https://www.graphviz.org/) graph visualization software and [svg-pan-zoom](https://github.com/ariutta/svg-pan-zoom) for a better user expierience with huge graphs.

## License

We release this software under the [GNU GENERAL PUBLIC LICENSE v3](https://www.gnu.org/licenses/gpl-3.0.html).
