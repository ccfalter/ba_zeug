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
      }
   **/
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
     // get the file 
     $url = $form_state->getValue('url');
     $xmlfile = file_get_contents($url);
     $form['#attached']['drupalSettings']['wisski']['vis']['data']= $xmlfile;
         
     //$response = new JsonResponse($json_file);
     $form['#attached']['library'][] = 'wisski_pb_vis/vis';          
     $form_state->setRedirect('wisski_pb_vis.mainpage');
   }
                                                                          
 }                                                                                                                                         