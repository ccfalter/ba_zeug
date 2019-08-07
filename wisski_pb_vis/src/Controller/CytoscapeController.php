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

use Drupal\Component\Serialization\Json;

/**
 * Defines CytoscapeController class for pathbuilder visualisation.
  */
  class CytoscapeController extends ControllerBase {
 
     public static function getRequest(Request $request){
       return $request;  
     }
     public function getJson(Response $re) {
       $json_data = $re->get();
//       dpm($json_data, "Hallo");
       return new JsonResponse();
     }       
//     public function getUrlForData(Url $url){
//      $template_data = $url;
//     }
  /**
   Define Container and Style for Cytoscape library
   and Visualize Graph
  **/
     public function start() {
          //test: direct url load, parse xmlfile to json and give it to drupalSettings 
           //$url = "https://testrakete.gnm.de/sites/default/files/2019-06/hauptpb_20170810T110118.xml";
           $url = "https://testrakete.gnm.de/sites/default/files/2019-08/personen_institute_sammlungen_20190807T092054.xml";
          // $url = "http://objekte-im-netz.fau.de/projekt/sites/default/files/pathbuilder-template/med_sammlungsspezifika_20180829T103604.xml";
           $xmlfile = file_get_contents($url);
           $xml2object =  simplexml_load_string($xmlfile);
           //json_encode destroys xml-structure of path_array: multiple values for x and y
           
           $object2json  = json_encode($xml2object);
           //dpm($object2json, 'bu');
           //$size_arr_x = 0;
           //$size_arr_y = 0;
           //xpath destroys xml-structure of path_array: multiple values for x and y
           $result = $xml2object->xpath('/pathbuilderinterface/path/path_array');
           //dpm($result, 'path');
           $counter_of_children = 0;
           $counter_of_paths = 0;
           $children_infos_array = array();
           foreach($result as $r){
            $name_path_array = $r->getName();
            $r->addAttribute('path_array_id',  $counter_of_paths);
            $counter_of_path_elements = 0;
           //each children is a SimpleXMLElement with datatype string
             foreach($r as $children){
             
             $path_array_number = Intval($r->attributes()->__toString());
             $name_element = $children->getName();
            //returns each concepts and properties as string
             
             $children_infos = array($path_array_number,$counter_of_path_elements, $name_element, $children->__toString());
             $children_infos_array[$counter_of_children]= $children_infos;
             //dpm($children, 'children');
             
             //dpm($children_infos, 'children infos');
             $counter_of_path_elements++;
             $counter_of_children++;
              
             }
              //dpm($name_path_array, 'path array');
            //$r->addChild('path_infos', $child->__toString());
            //}
             $counter_of_paths++;
             
           }
         // dpm($children_infos_array, 'children arrays');
           /*
           foreach($children_infos_array as $infos){
             if($children_infos_array[2] == 'y'){
              $size_arr_y++;
             }
             else{
              $size_arr_x++;
             }
           }*/
           $arr_x = array();
           $arr_y = array(); 
           $j = 0;         
           $k = 0;   
           for($i = 0; $i < sizeof($children_infos_array); $i++){
            
             if(($children_infos_array[$i][1]%2)){
               $arr_y[$j] =  [ 'data'=>
                                ['id'=>  $i,// $children_infos_array[$i][3], //$i
                                 'label' => $children_infos_array[$i][3], 
                                 'source'=> $children_infos_array[$i-1][3],//$i-1
                                 'target'=> $children_infos_array[$i+1][3] //$i+1
                                ]
                             ];
               $j++;                                                                                                                                                                              
             }
            else{
              $arr_x[$k] = [ 'data' =>
                                ['id' =>  $children_infos_array[$i][3], //$i
                                 'label' =>  $children_infos_array[$i][3],
                                ]
                             ];
              $k++;                                                                                                                           
            }
           
          }
          //dpm($arr_x, "nodes");
         // dpm($arr_y, "edges");
           $json_structure_oyto = ['nodes'=> $arr_x, 'edges' => $arr_y];
#           dpm("nosebear, nosebear!");
         // dpm($json_structure_oyto, 'passt');
       /*
             
           $json2phparray = json_decode($object2json,true);
           $nodes = array();
           $edges = array();
           $path_arrays = array();
           $pathentries = $json2phparray['path'];
           // $tmp = array();
           //get every path_array in pathbuilder_template
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
                         ] ;
              //  dpm($x_array[$i], 'Fall 2');
             //  dpm($y_array[$i], 'Fall 2');
            }
            //else{ //pathlength >=3
              for($j = 0; $j < count($path_arrays[$i]['x']); $j++){
                if(count($path_arrays[$i]) == 1){
                  $x_array[$i] = [ 'data' =>
                               ['id' => $path_arrays[$i]['x'], 
                               'label' => $path_arrays[$i]['x']
                               ]
                             ];
                }
                if(count($path_arrays[$i]['x']) == 2){
                  
                  $x_array[$i] = ['data'
                                 => ['id' => $path_arrays[$i]['x'][$j],
                                    'label'=> $path_arrays[$i]['x'][$j]
                                    ]
                                  ,
                                  'data'
                                  =>['id' => $path_arrays[$i]['x'][$j+1],
                                 'label'=> $path_arrays[$i]['x'][$j+1]
                                  ]
                                  
                                 ];
                  $y_array[$i] = ['data'
                                   =>['id'=> $path_arrays[$i]['y'],
                                     'source'=> $path_arrays[$i]['x'][$j],
                                     'target'=>$path_arrays[$i]['x'][$j+1]
                                     ]
                                 ];
               }
               else{ //todo!!
                  $x_array[$i] = [ 'data' =>
                               ['id'=>$path_arrays[$i]['x'][$j],
                                'label'=> $path_arrays[$i]['x'][$j]
                                ]
                             ];
                                                                                  
                  $y_array[$i] = [ 'data'=>
                                ['id'=> $path_arrays[$i]['y'][$j],
                                'source'=> $path_arrays[$i]['x'][$j],
                                'target'=> $x_path_arrays[$i]['x'][$j+1]
                               ]
                             ];
                //dpm($x_array[$i], 'Fall 3');
            //     dpm($y_array[$i], 'y');
             //  }
            // }
             
           */
           // dpm($x_array, 'x da');
           //$nodes = $x_array;
           //$edges = $y_array;
           //$jsonforcytoscape = ['nodes'=> $nodes, 'edges' => $edges];
          // dpm($jsonforcytoscape, 'am I CytoJSON?');
          
         
           $json_file = json_encode($json_structure_oyto);                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         
           // dpm($json_file, 'tada');         
                      
           $form = array();               
           $form['#markup'] = '<div id="viewer"></div>'; 
           // style="height:500px;width:500px;"></div>';
           $form['#allowed_tags'] = array('div', 'select', 'option','a', 'script', 'style', 'height', 'width');
           $form['#attached']['library'][] = "wisski_pb_vis/vis";

           

#           $url = "https://testrakete.gnm.de/sites/default/files/2019-06/path_example.json";
#           $json_file = file_get_contents($url);
           $json_file = Json::decode($json_file);
#           dpm($json_file, "file?");
//           $json_file =  simplexml_load_string($xmlfile);
                   
//           $json_file = json_encode($xml2object);


           $form['#attached']['drupalSettings']['wisski']['vis']['data'] = $json_file;
           
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
           //$form['#attached']['drupalSettings']['vis']['data']= "wisski_pb_Vis/js/wisski_cytoscape.js";
           //$form['#attached']['drupalSettings']['wisski']['vis']['data'] = $json_file;
           
/**           $response = new Response();
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
   **/      // return false;
           //dpm($form, 'w');                                                                                                                                                                                                                                                                                                                                                                                                                                                                            
           return $form;
       }                                                                                                                                                                                                                                                                   
   }