<?php

namespace Drupal\test_visualisations\Controller;


use Symfony\Component\HttpFoundation\Request; 
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;   
use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Form\FormStateInterface; 
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\wisski_core;
use Drupal\wisski_salz\AdapterHelper;
use Drupal\wisski_salz\Plugin\wisski_salz\Engine\Sparql11Engine;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url; 
use Drupal\Core\Link;
use Drupal\wisski_core\WisskiCacheHelper;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenDialogCommand;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Form\FormBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;



/**
 * Defines VisNetworkController class for pathbuilder visualisation.
  */
  class VisNetworkController extends ControllerBase {
 
           
  /**
   Define Container and Style for Vis.js library
   and Visualize Graph
  **/
     public function start() {           
           $form = array();               
           $form['#markup'] = '<div id="viewer"></div>';
           $form['#allowed_tags'] = array('div', 'select', 'option','a', 'script');
           $form['#attached']['library'][] = "test_visualisations/test_vis";
           $response = new Response();
               $response->setContent('<!DOCTYPE html>
                   <html lang="en">
                     <head>
                           <title>Simple Viewer</title>
                           <meta charset="UTF-8">
                           <meta name="viewport" content="width=device-width, initial-scale=1.0">
                           <style>#viewer {
                                   width: 100%;
                                   height: 100%;
                                   position: absolute;
                                   top: 0px;
                                   left: 0px;
                                   display: block;
                                   }
                           </style>
                           <script src="/libraries/vis/dist/vis-network.min.js"></script>
                             <link href="/libraries/vis/dist/vis-network.min.css" rel="stylesheet" type="text/css" />
                           <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.0/jquery.min.js"></script>
                     </head>
                     <body>
                        <div id="viewer"></div>
                        <script>
                           var get_json = $.getJSON("libraries/vis/examples/network/datasources/personen.json").done(
                            function(data) {
                            var data = {
                                nodes: data.nodes,
                                edges: data.edges
                            };
                            
                             var options = {autoResize: true,
                                            height: "100%",
                                            width: "100%",
                                            clickToUse: true,
                                            edges:{
                                                arrows: "to",
                                                color: "red",
                                                font: "12px arial #ff0000" 
                                           },
                                           nodes:{
                                           color: "black"
                                           },
                                           interaction:{
                                           dragNodes: true,
                                           hover: true,
                                           navigationButtons: true,
                                           selectable: true,
                                           selectConnectedEdges: true,
                                           zoomView: true
           
                                           }
                                         };
                            
                             var container = document.getElementById("viewer");
                             var network = new vis.Network(container, data, options);
                            }); 
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                          
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            
                          </script>
                      </body>
                  </html>'
                  );
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                     
           return $response;
        }                                                                                                                                                                                                                                                                   
   }