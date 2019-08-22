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
**/
  class CytoscapeController extends ControllerBase {
 
     public static function getRequest(Request $request){
       return $request;  
     }
     public function getJson(Response $re) {
       $json_data = $re->get();
       return new JsonResponse();
     }       

  /**
   * Define Container and Style for Cytoscape library
   * and Visualize Graph
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
           
       //convert to xpath: structure of path_array is like source. 
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
         $counter_of_paths++;
             
       }
       //dpm($children_infos_array, 'children arrays');
         
       $arr_x = array();
       $arr_y = array(); 
       $j = 0;         
       $k = 0;   
       for($i = 0; $i < sizeof($children_infos_array); $i++){
            
         if(($children_infos_array[$i][1]%2)){
           
           $arr_y[$j] = ['data'=>
                          ['id'=> $i,
                           'label' => basename($children_infos_array[$i][3]), 
                           'source'=> $children_infos_array[$i-1][3],
                           'target'=> $children_infos_array[$i+1][3] 
                           ]
                        ];
           $j++;                                                                                                                                                                              
         }
         else{
           $arr_x[$k] = ['data' =>
                          ['id' =>  $children_infos_array[$i][3], 
                           'label' =>  basename($children_infos_array[$i][3])
                          ]
                        ];
           $k++;                                                                                                                           
         }
           
       }
       
           
       //dpm($arr_x, "nodes");
       //dpm($arr_y, "edges");
       $json_structure_oyto = ['nodes'=> $arr_x, 'edges' => $arr_y];
       //dpm($json_structure_oyto, 'passt');
        
       $json_file = json_encode($json_structure_oyto);                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                  
                      
       $form = array();               
       $form['#markup'] = '<div id="viewer"></div>';      
       $form['#allowed_tags'] = array('div', 'select', 'option','a', 'script', 'style', 'height', 'width');
       $form['#attached']['library'][] = "wisski_pb_vis/vis";
    
       $json_file = Json::decode($json_file);
       $form['#attached']['drupalSettings']['wisski']['vis']['data'] = $json_file;                                          
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                       
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                             
       //dpm($form, 'w');                                                                                                                                                                                                                                                                                                                                                                                                                                                                            
       return $form;
     }                                                                                                                                                                                                                                                                   
   }