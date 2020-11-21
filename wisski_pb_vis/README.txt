CONTENTS OF THIS FILE	
---------------------
* Introduction
* Requirements
* Installation


INTRODUCTION
------------
wisski_pb_vis is an abbreviation for wisski-pathbuilder-visualisation-modul.
This module is a prototyp. It was developed as part of the Bachelor thesis
for the visualization of pathbuilder templates in WissKI. The library
Cytoscape.js was used for the visualisation. 
It currently consists of a submitpage, a controller and a drupal behaviour.
Due to difficulties at the end of the Bachelor thesis the development of the
prototyp is not terminated. 

original concept: 
The user submit a XML-file of the pathbuildertemplate at
the submitpage. The data are parsed and transformed for the library in the
form and then delivered to the drupalbehaviour per drupal.settings. There
the container for Cytoscape is initialised and the result returned to the
Controller for visualisation in WissKI.

REQUIREMENTS
------------
* Drupal
* WissKI
* jquery
* drupal.settings
* Cytoscape.js

INSTALLATION
------------
- Download the library and the extentions you want to the directory /libraries/cytoscape  
- Download the module on git (git clone). 
- In your System go to Extends and activate the module.
- Pages and pathes can be found in the routing.yml file.
- Load the xml-file in the Controller
- Clear cache and go to the page
