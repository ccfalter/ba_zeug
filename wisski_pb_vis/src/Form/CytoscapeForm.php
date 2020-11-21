<?php

namespace Drupal\wisski_pb_vis\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormBuilder;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Submit Form constructor for PathbuilderTemplate-XML-File-URL.
 */
class CytoscapeForm extends FormBase
{
    public function getFormId()
    {
        return 'wisski_pb_vis';
    }
    public function validateForm(array &$form, FormStateInterface $form_state)
    {

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
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $form['name'] = ['#type' => 'textfield',
            '#title' => $this->t('Please enter a name for the Pathbuildertemplate File'),
            '#required' => true,
        ];
        $form['url'] = ['#type' => 'textfield',
            '#title' => $this->t('Please enter the FILE URL of the Pathbuildertemplate'),
            '#required' => true,
        ];
        $form['url2'] = ['#type' => 'textfield',
            '#title' => $this->t('If you want you can enter another pathbuilderfile in order to combine them in the graph'),
        ];
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
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        // get the file
        $url = $form_state->getValue('url');
        $url2 = $form_state->getValue('url2');
        $pathbuilder_name = $form_state->getValue('name');

        //rebuild the json file internally as a object to be able to store information in the DB
        //Uncomment if needed
        //$xmlfile = file_get_contents($url);
        //$xml2object = simplexml_load_string($xmlfile);
        //$json_path_object = json_decode(json_encode($xml2object), true);

        //Using the database to store the information used in the mainpage
        //establishing database connection
        $database = \Drupal::database();

        //create new table shema for the database
        $schema = $database->schema();
        $table_name = 'pathbuilder';  //edit this tablename and any occurences of 'pathbuilder' in CytoscapeForm and CytoscapeController to a name of choice if 'pathbuilder' already exits in drupaldb.
        $table_schema = [
            'fields' => [
                'uid' => [
                    'type' => 'int',
                    'not null' => true,
                ],
                'created' => [
                    'type' => 'int',
                    'not null' => true,
                ],
                'name' => [
                    'type' => 'text',
                    'not null' => true,
                ],
                'url' => [
                    'type' => 'text',
                    'not null' => true,
                ],
                'xml' => [
                    'type' => 'text',
                    'not null' => true,
                ],
                'selected' => [
                    'type' => 'int',
                    'not null' => true,
                ],
            ],
            'primary key' => ['uid'],
        ];

        //Create the Table or skip if Database already exits
        try {
            $schema->createTable($table_name, $table_schema);
        } catch (\Throwable $th) {
            //Database already exits.Ignore the throwable
        }
        
        //Getting the amount of pathbuilder files to set the correct uid
        $query_string = $database->query("SELECT * FROM pathbuilder");
        $query = $query_string->fetchAll();
        $db_count = count($query);

        //Insert the file into the database.
        $result = $database->insert('pathbuilder')
        ->fields([
        'uid' => $db_count,
        'created' => \Drupal::time()->getRequestTime(),
        'name' => $pathbuilder_name,
        'url' => $url,
        'xml' => 'NULL', //Possible to parse the $json_path_object and save it instead of just the link. If wanted, uncomment the code at 'rebuild the json file internally' and use its value here
        'selected' => 1,
        ])
        ->execute();

        //add the additional url to the database if provided
        if($url2!=""){
            $db_count++;
            $result = $database->insert('pathbuilder')
            ->fields([
            'uid' => $db_count,
            'created' => \Drupal::time()->getRequestTime(), //gets time and saves it in the database as a Unix Timestamp 
            'name' => $pathbuilder_name,
            'url' => $url2,
            'xml' => 'NULL', //Possible to parse the $json_path_object and save it instead of just the link. If wanted, uncomment the code at 'rebuild the json file internally' and use its value here
            'selected' => 1,
            ])
            ->execute();
        }
        
        //Disabled caching in options of wisski_pb_vis.routing.yml of wisski/pb_vis with options: no_cache: 'TRUE'.
        //If you want caching you need to make sure the redirected site is not cached with uncommenting the next line (very time consuming) or...
        //...set the cache-rulings for the site yourself.
        //Flushes all cashed sites or the new site could be an old one
        //drupal_flush_all_caches();
        
        //redirect to the pb_vis viewer
        $form_state->setRedirect('wisski_pb_vis.mainpage');
    }

}
