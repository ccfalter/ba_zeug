CONTENTS OF THIS FILE
---------------------

* Introduction
* Requirements
* Installation
* Libraries/ Authors

INTRODUCTION
------------
test_visualisations is a drupal module for testing and evaluating graph libraries
in WissKI. It was developed as part of the Bachelor thesis for the
visualization of pathbuilder templates in WissKI. For each library a
controller was programmed to test the libraries with files for large,
directed graphs in the software environment and in the browser.
Controllers for cytoscape, jit, sigma, vis and d3 are included in the
module.
 
REQUIREMENTS
-----------
- Drupal
- WissKI
- jquery

INSTALLATION AND CONFIGURATION
-----------------------------

- Download the library and the extentions you want to the directory /libraries/[library name]: 
  cytoscape, jit, sigma, vis or visnetwork, d3
- Download test_visualisation on git (git clone).
- In your System go to Extends and activate the module.
- Testpages can be found in the routing.yml file.
- Test a JSON file: Every libraries provides JSON files as test data. 
  Copy the path to the file in the Controller at  $getJSON-Function and
  Save the Controller. 
- Clear cache and go to the page.

 
LIBRARIES/AUTHORS
------------------

test_visualisations by Corina Lehmann

Further Informations about the libraries:
 
https://github.com/cytoscape/cytoscape.js
https://github.com/jacomyal/sigma.js
https://github.com/visjs/vis-network
https://philogb.github.io/jit/
https://github.com/d3/d3
   