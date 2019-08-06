<?php

namespace Drupal\wisski_pb_vis\Form;


use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenDialogCommand;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Form\FormBuilder;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;
use Drupal\wisski_core;
use Drupal\wisski_salz\AdapterHelper;
use Drupal\wisski_salz\Plugin\wisski_salz\Engine\Sparql11Engine;
use Drupal\wisski_core\WisskiCacheHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Serialization\JSON;
use Drupal\Core\Serialization\PHP;   
      
/**
 * Submit Form constructor for PathbuilderTemplate-XML-File-URL.
 */
  class CytoscapeForm extends FormBase {
              
     /** public function __construct(FormBuilder $formBuilder) {
        $this->formBuilder = $formBuilder;
      }           
                                                            
      public static function create(ContainerInterface $container) {
        return new static($container->get('form_builder'));
      }                                   
                                                                                                                       
      public static function getRequest(Request $request){
        return $request;
      }**/
      public function getFormId() {
        return 'wisski_pb_vis';
      } 
      public function validateForm(array &$form, FormStateInterface $form_state) {
       
      } 
         /**Form constructor.
         *
         *   @param array $form
         *   An associative array containing the structure of the form.
         *   @param \Drupal\Core\Form\FormStateInterface $form_state
         *   The current state of the form.
         *   @return array
         *   The form structure.
         **/                                                                                                                                                                      
      public function buildForm(array $form, FormStateInterface $form_state) {
                                                                                                                                                                                                   
         $form['description'] = ['#type' => 'item',
                                 '#markup' => $this->t('Please enter the url of the Pathbuildertemplate File and submit.'),
                                  ];        
         $form['url'] = ['#type' => 'textfield',
                         '#title' => $this->t('File URL'),
                         '#required' => TRUE,
                          ];
         $form['actions'] = ['#type' => 'actions',];                 
         $form['actions']['submit'] = ['#type' => 'submit',
                                        '#value' => $this->t('Submit'),
                                      ];
         //$form['#attached']['drupalSettings']['wisski_cytoscape']= $url;                            
         return $form;    
     }
     /**
        * Form submission handler.
        *
        * @param array $form
        *   An associative array containing the structure of the form.
        * @param \Drupal\Core\Form\FormStateInterface $form_state
        *   The current state of the form.
      **/
     public function submitForm(array &$form, FormStateInterface $form_state) {
          // get the file and decode it
          $url = $form_state->getValue('url');
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
            //  $count = 0;
              //$x_array[] = $path_arrays[$i]['x'];
              //$y_array[] = $path_arrays[$i]['y'];
             /** if($y_array[$i]!='NULL'){
                dpm($x_array[$i]['y'], 'dibadiadu');
               // dpm($c++, 'counter');
                while(count($y_array[$i]['y']) < $count){
                  $edges[] = [array('id'=>$y_array[$i]['y'][$count],'source'=>$x_array[$i]['x'][$count], 'target'=>$x_array[$i]['x'][$count+1])]; 
                  $count++;
                 }
              }
         **/
          }
         $nodes = $x_array;
         $edges = $y_array;
         $jsonforcytoscape = ['nodes'=> $nodes, 'edges' => $edges];
         //dpm($jsonforcytoscape, 'am I CytoJSON?');
         $json_file = json_encode($jsonforcytoscape);
         //dpm($json_file, '?'); 
         /** foreach($x_array as $k => $val){
            if(is_array($val)){
             //dpm($val, 'x_val');
             foreach($val as $key => $value){
             dpm($value, 'values');
             $nodes = [array('id' => $value, 'label' => $value)];
             }
            }else{
             
            }
          }**/
          //dpm($x_array,'X');
          //dpm($y_array, 'Y');
          //array-key of path_arrays is an integer
          /**foreach($path_arrays as $key => $value){
            for($i = 0; $i < count($path_arrays); $i++){
              dpm($path_arrays[$i], 'hhhh');
                //path consists at least of one property
                if(count($path_arrays[$i]) > 1){
                    
                }
                                  
            } 
                                            
          }**/
          
          //dpm($path_arrays, 'Hey, path arrays');
          /**foreach($path_arrays as $x_y => $ontology_class_property){
            
            if($x_y =='x'){
              
              $nodes[] = array("id" => $ontology_class_property, "label"=> $ontology_class_property);
            }
            elseif($x_y =='y'){
            }
          } dpm($nodes, 'Hey, nodes');**/
          
          //encode to json
          
          //$url = Url::fromUserInput('/wisski/pb_vis');
          //$form_state->setRedirect($url);
          //$response = new JsonResponse($json_file);
          $form['#attached']['drupalSettings']['wisski']['vis']['data']= $json_file;
          $form['#attached']['library'][] = 'wisski_pb_vis/vis';
          
          dpm($form, "yay?");
          return FALSE;           
          $form_state->setRedirect('wisski_pb_vis.mainpage');
     }
                                                                          
 }                                                                                                                                         