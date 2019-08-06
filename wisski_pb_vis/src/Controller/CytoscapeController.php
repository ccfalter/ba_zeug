<?php

namespace Drupal\wisski_pb_vis\Controller;


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
 * Defines CytoscapeController class for pathbuilder visualisation.
  */
  class CytoscapeController extends ControllerBase {
 
     public static function getRequest(Request $request){
       return $request;  
     }
     public function getJson(Response $re) {
       $json_data = $re->get();
      // dpm($json_data, "Hallo");
       return new JsonResponse();
     }       
//     public function getUrlForData(Url $url){
//      $template_data = $url;
//     }
     protected §formBuilder;
     public function __construct(FormBuilder $formBuilder){
      $this->formBuilder = $formBuilder;
     }
     
     public static function create(ContainerInterface $container) {
      return new static(
          $container->get('form_builder')
           );
     }
     
                       
  /**
   Get xml file and parse to json. Delivers json-data to drupal.settings and
   visualize Graph with cytoscape.js
  **/
     public function start() {
          //test: direct url load, parse xmlfile to json and give it to drupalSettings 
           $url = "https://testrakete.gnm.de/sites/default/files/2019-06/hauptpb_20170810T110118.xml";
          
           $xmlfile = file_get_contents($url);
           $xml2object =  simplexml_load_string($xmlfile);
           //dpm(§xml2object, "why");
           $object2json  = json_encode($xml2object);
           $json2phparray = json_decode($object2json,true);
           $nodes = array();
           $edges = array();
           $path_arrays = array();
           $pathentries = $json2phparray['path'];
            foreach ($pathentries as $pathkey => $path_table) {
              foreach ($path_table  as $rowkey => $row){
                if($rowkey == 'path_array'){
                   $path_arrays[] = $path_table['path_array'];
                }
              }
            }                                               
           //dpm($path_arrays, 'all Patharrays of File');
             
           //Concepts x and Properties y split in arrays for directed edges
           $x_array= array();
           $y_array = array();
           for($i = 0; $i < count($path_arrays); $i++){
             //only one node in the path
             if(count($path_arrays[$i]) == 1){
                $x_array[$i] = ['data' =>['id' => $path_arrays[$i]['x'], 'label' => $path_arrays[$i]['x']]];
                // dpm($x_array[$i], 'Fall 1');
              }
             if(count($path_arrays[$i]['x']) == 2){
               $x_array[$i] =[
                             ['data'
                             => ['id' => $path_arrays[$i]['x'][0],
                                'label'=> $path_arrays[$i]['x'][0]
                                ]
                             ],
                             ['data'
                             =>['id' => $path_arrays[$i]['x'][1],
                               'label'=> $path_arrays[$i]['x'][1]
                               ]
                             ]
                           ];
               $y_array[$i] = ['data'
                        =>['id'=> $path_arrays[$i]['y'],
                          'source'=> $path_arrays[$i]['x'][0],
                          'target'=>$path_arrays[$i]['x'][1]
                           ]
                          ];
              //  dpm($x_array[$i], 'Fall 2');
             //  dpm($y_array[$i], 'Fall 2');
            }
            else{ //pathlength >=3
              for($j = 0; $j < count($path_arrays[$i]['x']); $j++){
                
                $x_array[$i] = [ 'data' =>
                               ['id'=>$path_arrays[$i]['x'][$j],
                                'label'=> $path_arrays[$i]['x'][$j]
                                ]
                               ];
                                                                                  
                $y_array[$i] = ['data'
                             =>['id'=> $path_arrays[$i]['y'][$j],
                                'source'=> $path_arrays[$i]['x'][$j],
                                'target'=> $x_path_arrays[$i]['x'][$j+1]
                               ]
                              ];
                //dpm($x_array[$i], 'Fall 3');
                // dpm($y_array[$i], 'Fall 3');
              }
            }
           $nodes = $x_array;
           $edges = $y_array;
           $jsonforcytoscape = ['nodes'=> $nodes, 'edges' => $edges];
          // dpm($jsonforcytoscape, 'am I CytoJSON?');
           $json_file = json_encode($jsonforcytoscape);                                                                                                                                                                                                                                                                                                                                                                                                                            }                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                             
                    
                      
           $form = array();               
           $form['#markup'] = '<div id="viewer"></div>'; 
           // style="height:500px;width:500px;"></div>';
           $form['#allowed_tags'] = array('div', 'select', 'option','a', 'script', 'style', 'height', 'width');
           $form['#attached']['library'][] = "wisski_pb_vis/vis";
           
           /** $url = $form_state->getValue('url');
               $xmlfile = file_get_contents($url); 
               $form['#attached']['drupalSettings']['wisski']['vis']['data']= $xmlfile;
                                         
               //$xmlfile = file_get_contents($url); 
               $xml2object =  simplexml_load_string($xmlfile);
               // encode to json-string
               // dpm($xmlfile,'Hooo');   
               $object2json  = json_encode($xml2object);
               // decode to php-array   
               $json2phparray = json_decode($object2json,true);
                                                                                                             
               //transform array for cytoscape visualisation
               $nodes = array();
               $edges = array();
               $path_arrays = array();
               $pathentries = $json2phparray['path'];
          **/
 
           //delivers data to drupal.behaviour
           $form['#attached']['drupalSettings']['wisski']['vis']['data'] = $json_file;
           
/** //All started with the code below. Can be ignored. It is similar to the Controller for cytoscape.js in test_visualisations module    

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
                           <script src="/libraries/cytoscape/dist/cytoscape.min.js"></script>
                           <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.0/jquery.min.js"></script>
                     </head>
                     <body>
                        <div id="viewer"></div>
                        <script>
                           $.getJSON("https://testrakete.gnm.de/sites/default/files/2019-06/path_example_8.json", function (data) {
                            //console.log(data);
                             var cy = cytoscape({
                                      container: document.getElementById("viewer"),
                                       elements: data,
                                       style: [
                                       {
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
                                       layout: {
                                           name: "circle",
                                           minNodeSpacing: 10,
                                           nodeDimensionsIncludeLabels: false,
                                           padding: 30,
                                           avoidOverlap: true,
                                           startAngle: 3 / 2 * Math.PI,
                                           clockwise: true,
                                           fit: true,
                                           radius: undefined
                                           }                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                   
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                          
                                       }); 
                                     });                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        
                          </script>
                      </body>
                  </html>'
                  );
   **/                                                                                                                                                                                                                                                                                                                                                                                                                                                                                  
           return $form;
        }                                                                                                                                                                                                                                                                   
   }