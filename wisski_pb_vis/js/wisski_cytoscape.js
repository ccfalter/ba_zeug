/*Drupal behaviour get access to variable from CytoscapeController form. 
 *Initialize container "viewer" for library cytoscape.
 */
(function ($, Drupal, drupalSettings) {
   Drupal.behaviors.wisski_cytoscape_behaviour = {
     attach: function (context, settings) {
     //alert(drupalSettings.wisski.vis.data); //alerts the value of PHP's $value
     //get data from controller  
     //var json_data = drupalSettings.wisski.vis.data;
     $('div#viewer', context).once('wisski_cytoscape').each(function () { 
       var json_data = drupalSettings.wisski.vis.data;
       //console.log(json_data);
     
       $.getJSON("https://testrakete.gnm.de/sites/default/files/2019-06/path_example.json", function (json_data) {
       //initialize container "viewer" for library cytoscape.  
       var cy = cytoscape({
                container: document.getElementById("viewer"),
                elements: json_data,
                style:[{
                       selector: "node",
                       style:{
                              "label": "data(label)",
                              "width": "30px",
                              "height": "30px",
                              "color": "black",
                              "background-fit": "contain",
                              "background-clip": "none",
                              "text-background-color": "orange",
                              "text-background-opacity": 0.4
                              }
                       },
                       {
                       selector: "edge",
                       style: {
                              "width": "5px",
                              "curve-style": "bezier",
                              "text-background-color": "blue",
                              "text-background-opacity": 0.4,
                              "line-color": "#777777",
                              "target-arrow-color": "#777777",
                              "target-arrow-shape": "triangle",
                              "label": "data(label)"
                              }
                       }
                       ],
                layout:{
                       name: "circle",
                       minNodeSpacing: 10,
                       nodeDimensionsIncludeLabels: false,
                       padding: 30,
                       avoidOverlap: true,
                       startAngle: 3 / 2 * Math.PI,
                       clockwise: true,
                       fit: true,
                       boundingBox: undefined,
                       spacingFactor: undefined,
                       sweep: undefined,
                       radius: undefined
                                                                                                                                                                                                                                                         
/*                            name: "concentric",
                              minNodeSpacing: 10,
                              nodeDimensionsIncludeLabels: false,
                              padding: 30,
                              sweep: undefined,
                              equidistant: false,
                              boundingBox: undefined,
                              spacingFactor: undefined,
                              avoidOverlap: true,
                              startAngle: 3 / 2 * Math.PI,
                              clockwise: true,
                              fit: true,
                              radius: undefined,
                              concentric: function( node ){ // returns numeric value for each node, placing higher nodes in levels towards the centre
                                return node.degree();
                              },
                              levelWidth: function( nodes ){ // the letiation of concentric values in each level
                                return nodes.maxDegree() / 4;
                              },
                              animate: false,
                              animationDuration: 500,
                              animationEasing: undefined,
                              animateFilter: function ( node, i ){ return true; },
                              ready: undefined,
                              stop: undefined,
                              transform: function (node, position ){ return position; } // transform a given node position. Useful for changing flow direction in discrete layouts
*/                              
                      }
             });
             //alert(cy);        
             //console.log(cy);
             
       cy.ready();                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                               
       });     
    }
  };
})(jQuery, Drupal, drupalSettings);