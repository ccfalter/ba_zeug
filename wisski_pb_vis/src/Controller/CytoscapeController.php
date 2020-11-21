<?php

namespace Drupal\wisski_pb_vis\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Defines CytoscapeController class for pathbuilder visualisation.
 **/
class CytoscapeController extends ControllerBase
{

    public static function getRequest(Request $request)
    {
        return $request;
    }
    public function getJson(Response $re)
    {
        $json_data = $re->get();
        return new JsonResponse();
    }

    /**
     * Define Container and Style for Cytoscape library
     * and Visualize Graph
     **/
    public function start()
    {
        //establishing database connection
        $database = \Drupal::database();

        //select the url from the database that are selected
        $query_string = $database->query("SELECT * FROM pathbuilder WHERE selected = 1");
        $query = $query_string->fetchAll();
        $db_count = count($query);

        //deselect the file in the database to prepare for the next request
        $query_string = $database->query("UPDATE pathbuilder SET selected=0");

        //if nothing was selected throw an error
        if ($db_count == 0) {
            //informing the user to load the pathbuilderfile again
            $form['description'] = ['#type' => 'item',
                '#markup' => $this->t('Please load the pathbuilder file again. The cached version was deleted.'),
            ];
            return $form;
        }

        //getting all the urls from the database where selected was set
        //and retrieving the xml(s) from the url(s) and merge them if needed
        $url = array_column($query, 'url');

        for ($i = 0; $i < $db_count; $i++) {
            //check if one of the urls is not correct
            if (filter_var($url[$i], FILTER_VALIDATE_URL) === false) {
                //informing the user that one of the urls was not valid
                $form['description'] = ['#type' => 'item',
                    '#markup' => $this->t('One of the URLs was not valid.'),
                ];
                return $form;
            }
            $xmlfile[$i] = file_get_contents($url[$i]);
            $xml2object[$i] = simplexml_load_string($xmlfile[$i]);

            //rebuild the json file internally as a object
            $json_path_object[$i] = json_decode(json_encode($xml2object[$i]), true);

            //shifting the array to get the information one level higher
            if ($i === 0) {
                $json_object = array_shift($json_path_object[$i]);
            } else {
                $temp_json_object = array_shift($json_path_object[$i]);
                $json_object = array_merge($json_object, $temp_json_object);
            }

        }

        // Uncommment if you want to test the loading and merging of the XML Pathbuilderfiles
        //   ini_set('xdebug.var_display_max_depth', '10');
        //   ini_set('xdebug.var_display_max_children', '256');
        //   ini_set('xdebug.var_display_max_data', '1024');
        //   dvm(var_dump($json_object));

        // Create arrays that represent a graph.V for Vertex and E for Edges
        $V = array();
        $E = array();

        // Iterator through the object to create the Graph of the XML
        $jsonobj_iterator = 0;
        foreach ($json_object as $j) {
            //Check if the object has an 'y' path or not. If ...
            if (!(isset($j['path_array']['y']))) {
                // ... not found, information are of an Vertex. Adding to V if not present
                if (!in_array(basename($j['path_array']['x']), array_column($V, 'basename'), true)) {
                    //Creating two adjacency matrices
                    //One to handle the outgoing edges of the adjacency matrix and one for the incoming edges
                    $temp1 = $j;
                    $temp1['out_adjacency_matrix'] = array();
                    $temp1['inc_adjacency_matrix'] = array();
                    $temp1['basename'] = basename($j['path_array']['x']);
                    $temp1['fullname'] = $j['path_array']['x'];
                    $temp1['fullinfo'] = '1';
                    $V[] = $temp1;
                } else {
                    //If needed find and replace missing info of vertices added in the edge part where missing Vertexes where added.
                    //Check if fullinfo is present and include more info if not.
                    $key = array_search(basename($j['path_array']['x']), array_column($V, 'basename'));
                    if ($V[$key]['fullinfo'] === '0') {
                        $j['fullinfo'] = '1';
                        $V[$key] = array_merge($V[$key], $j);
                    }
                }
            } else {
                // ... found, information are of an Edge. Adding all the missing vertices V (if not present) ...
                foreach ($j['path_array']['x'] as $k) {
                    if (!in_array(basename($k), array_column($V, 'basename'), true)) {
                        //Creating two adjacency matrices
                        //One to handle the outgoing edges of the adjacency matrix and one for the incoming edges
                        $temp2['out_adjacency_matrix'] = array();
                        $temp2['inc_adjacency_matrix'] = array();
                        $temp2['basename'] = basename($k);
                        $temp2['fullname'] = $k;
                        $temp2['fullinfo'] = '0';
                        $V[] = $temp2;
                    }
                }
                // ...  then creating all the Edges E (if not present).
                // If it is an array go through every Edge ...
                $edge_iterator = 0;
                if (is_array($j['path_array']['y'])) {
                    foreach ($j['path_array']['y'] as $l) {
                        $source_obj = $j['path_array']['x'][$edge_iterator];
                        $source = basename($source_obj);
                        $target = basename($j['path_array']['x'][$edge_iterator + 1]);

                        //adding the source and target to the adjacency matrix of both vertices
                        $sourcekey = array_search($source, array_column($V, 'basename'));
                        $targetkey = array_search($target, array_column($V, 'basename'));
                        $V[$sourcekey]['out_adjacency_matrix'][] = $targetkey;
                        $V[$targetkey]['inc_adjacency_matrix'][] = $sourcekey;

                        ///add Info of the path elements to the first node to show the node information
                        if ($edge_iterator == 0) {
                            $V[$sourcekey]['all_paths_starting_from_node']['id'][] = $j['id'];
                            $V[$sourcekey]['all_paths_starting_from_node']['group_id'][] = $j['group_id'];
                            $V[$sourcekey]['all_paths_starting_from_node']['path']['x'][] = $j['path_array']['x'];
                            $V[$sourcekey]['all_paths_starting_from_node']['path']['y'][] = $j['path_array']['y'];
                            $V[$sourcekey]['all_paths_starting_from_node']['disamb'][] = $j['disamb'];
                            $V[$sourcekey]['all_paths_starting_from_node']['name'][] = $j['name'];
                        }

                        //create the Edge info array and append it to the Edge array
                        $E[] = array(
                            'name' => $j['name'],
                            'id' => $j['id'],
                            'enabled' => $j['enabled'],
                            'path' => array('source' => $source, 'label' => basename($l), 'target' => $target),
                            'disamb' => $j['disamb'],
                            'uuid' => $j['uuid'],
                        );

                        $edge_iterator++;
                    }
                }
                //... if not just add the one Edge
                else {
                    $source = basename($j['path_array']['x'][$edge_iterator]);
                    $target = basename($j['path_array']['x'][$edge_iterator + 1]);

                    //adding the source and target to the adjacency matrix of both vertices
                    $sourcekey = array_search($source, array_column($V, 'basename'));
                    $targetkey = array_search($target, array_column($V, 'basename'));
                    $V[$sourcekey]['out_adjacency_matrix'][] = $targetkey;
                    $V[$targetkey]['inc_adjacency_matrix'][] = $sourcekey;

                    //add Info of the path elements to the first node to show the node information
                    $V[$sourcekey]['all_paths_starting_from_node']['id'][] = $j['id'];
                    $V[$sourcekey]['all_paths_starting_from_node']['group_id'][] = $j['group_id'];
                    $V[$sourcekey]['all_paths_starting_from_node']['path']['x'][] = $j['path_array']['x'];
                    $V[$sourcekey]['all_paths_starting_from_node']['path']['y'][] = $j['path_array']['y'];
                    $V[$sourcekey]['all_paths_starting_from_node']['disamb'][] = $j['disamb'];
                    $V[$sourcekey]['all_paths_starting_from_node']['name'][] = $j['name'];

                    //create the Edge info array and append it to the Edge array
                    $E[] = array(
                        'name' => $j['name'],
                        'id' => $j['id'],
                        'enabled' => $j['enabled'],
                        'path' => array('source' => $source, 'label' => basename($j['path_array']['y']), 'target' => $target),
                    );
                }

            }
            $jsonobj_iterator++;
        }

        // BFS on the undirectional graph to get the weak connected graphs
        $V_count = count($V);
        $V_visited = array();
        $amount_of_graphs = 0;
        $stack = array(0);
        $graph_head_basename = $V[0]['basename'];

        while (count($V_visited) < $V_count) {
            //counting up the id of the weak_connected_graph_id. Every ID corresponds to an weak_connected_graph_id. Starts at 1.
            $amount_of_graphs++;
            //getting the new head of the weak connected graph after the first iteration
            if ($amount_of_graphs > 1) {
                for ($i = 0; $i < $V_count; $i++) {
                    if (!(in_array($i, $V_visited))) {
                        array_push($stack, $i);
                        $graph_head_basename = $V[$i]['basename'];
                        break;
                    }
                }
            }
            while (count($stack) != 0) {
                //getting the current node from the stack array and saving it as a visited node
                $current_visit = array_shift($stack);
                $V_visited[] = $current_visit;

                //creating an directional_unique_adjacency_matrix to be able to calculate the depth to any reachable vertices
                $directional_unique_adjacency_matrix = array_unique($V[$current_visit]['out_adjacency_matrix']);
                //reseting the keys of the values back to start at [0] to [count(directional_unique_adjacency_matrix)] again.
                $directional_unique_adjacency_matrix = array_values($directional_unique_adjacency_matrix);

                //creating an undirectional_unique_adjacency_matrix from both out- and incoming adjacency_matrix with array_unique and then sorting to correctly push onto the stack
                $undirectional_adjacency_matrix = array_merge($V[$current_visit]['out_adjacency_matrix'], $V[$current_visit]['inc_adjacency_matrix']);
                $undirectional_unique_adjacency_matrix = array_unique($undirectional_adjacency_matrix);
                sort($undirectional_unique_adjacency_matrix);
                //reseting the keys of the values back to start at [0] to [count(undirectional_unique_adjacency_matrix)] again.
                $undirectional_unique_adjacency_matrix = array_values($undirectional_unique_adjacency_matrix);
                //calculating the degree centrality of a node

                //saving the unique_adjacency_matrix and the connected graph ID in the edge details
                $V[$current_visit]['directional_unique_adjacency_matrix'] = $directional_unique_adjacency_matrix;
                $V[$current_visit]['undirectional_unique_adjacency_matrix'] = $undirectional_unique_adjacency_matrix;
                $V[$current_visit]['weak_connected_graph_id'] = $amount_of_graphs;
                $V[$current_visit]['in_degree_centrality'] = count($V[$current_visit]['inc_adjacency_matrix']);
                $V[$current_visit]['out_degree_centrality'] = count($V[$current_visit]['out_adjacency_matrix']);

                //pushing every node that is not visited and not already on the stack onto the stack.
                for ($i = 0; $i < count($undirectional_unique_adjacency_matrix); $i++) {
                    $potential_next_visitor = $undirectional_unique_adjacency_matrix[$i];
                    if (!(in_array($potential_next_visitor, $V_visited))) {
                        if (!(in_array($potential_next_visitor, $stack))) {
                            array_push($stack, $potential_next_visitor);
                        }
                    }
                }
            }
            //sorting and reseting the keys of the visited array to find the next unused vertice for the next weak connected graph
            sort($V_visited);
            $V_visited = array_values($V_visited);
        }

        //analysis of the XML-files complete.
        //    V[] now contains all vertices with all the info from the pathbuilder XML-File and the additional info of the ...
        //... V['basename'] as the uniqueid (we get no uuid of the vertices that have no fullinfo, so we need to use the next best identifier) ...
        //... V['out_adjacency_matrix'] contains the outgoing ajacency matrix. The amount of Edges from one Vertices to one spcific other Vertices is stored as duplicate entrys
        //... V['inc_adjacency_matrix'] contains the incoming ajacency matrix. The amount of Edges from one Vertices to one spcific other Vertices is stored as duplicate entrys
        //... V['fullinfo'] contains the information if we got the data of the vertices from the pathbuilder XML-File (=1) or not (=0).
        //... V['directional_unique_adjacency_matrix'] contains an array of the indeces of all direct reachable vertices (no duplicates).
        //... V['undirectional_unique_adjacency_matrix'] contains an array of the indeces of all vertices that are connected with the vertice (no duplicates and undirectional).
        //... V['weak_connected_graph_id'] contains the id of the weak connected graph this vertice belongs to.
        //... V['in_degree_centrality'] contains the amount of incoming edges of the vertice.
        //... V['out_degree_centrality'] contains the amount of outgoing edges of the vertice.

        //    E['path'] contains all the Edges stored as an array in the form of a RDF-Triplet...
        //... E['path']['source'] are the subjects. E['path']['label'] are the predicates. E['path']['target'] are the objects.
        //    E[] also contains more information like 'name','id', and 'enabled' of the pathbuilderfile. If more are needed you can add them in 'The create the Edge info array and append it to the Edge array'
        //... part of the code (add them in both parts, because one handles single Edges and one if the Edges are stored in an array).

        //uncomment the next 2 lines if you want to inspect V[] and E[] after the XML-analysis part.
        //dvm($V);
        //dvm($E);

        $json_structure_tmp = ['nodes' => $V, 'edges' => $E];
        $json_file = json_encode($json_structure_tmp);
        $json_file = Json::decode($json_file);

        $legend = "<table>
                    <thead><tr><th colspan='2'>Legend</th></tr>
                    </thead>
                    <tr><th colspan='2'>Node Shape and Color</th></tr>
                    <tr><td>Circle cyan Node</td><td>The node represents a (Sub-)Bundle</td></tr>
                    <tr><td>Square blue Node</td><td>The node represents a Main Bundle</td></tr>
                    <tr><td>Yellow Node</td><td>The node represents a disamb node that is in one of the Paths of the selected Node </td></tr>
                    <tr><td>Node with green Label</td><td>Currently selected Node</td></tr>
                    <tr><th colspan='2'>Edge Shape</th></tr>
                    <tr><td>Line</td><td>Singular Edge </td></tr>
                    <tr><td>Dotted</td><td>Multiple Edges</td></tr>
                    <tr><th colspan='2'>Edge Color</th></tr>
                    <tr><td>Purple</td><td>The incoming Edges of the selected Node with an length of one</td></tr>
                    <tr><td>Orange</td><td>The outgoing Edges of the selected Node Paths before the disamb Node </td></tr>
                    <tr><td>Yellow</td><td>The outgoing Edges of the selected Node Paths after the disamb Node </td></tr>
                    </table>";

        $form = array();
        $form['#markup'] =
            '<div id="btn-group">
            <button class="mybutton" id = "back">Back</button>
            <button class="mybutton" id="zoom">Zoom to fit</button>
            <button class="mybutton" id="reset_layout">Reset layout</button>
            <button class="mybutton" id="circle_layout">Toggle Circle Layout</button>
            <button class="mybutton" id="label_overlap">Toggle label overlap</button>
            <button class="mybutton" id="toggle_info">Infobox</button>
            <button class="mybutton" id="legendbutton">Legend</button>
            <button class="mybutton" id="save_png">Save graph as png</button>
        </div>
        <div id = "viewer-group">
            <div id="viewer"></div>
            <div id="infobox"><h1>No Node selected</h1></div>
            <div id="edgebox"><h1>No Node selected</h1></div>
            <div id="legend">' . $legend . '</div>
        </div>';
        $form['#allowed_tags'] = array('div', 'select', 'option', 'a', 'script', 'style', 'height', 'width', 'position', 'button', 'class', 'offset-position', 'right', 'top', 'table', 'thead', 'th', 'tr', 'td', 'b', 'p');
        $form['#attached']['library'][] = "wisski_pb_vis/vis";

        $form['#attached']['drupalSettings']['wisski']['vis']['data'] = $json_file;

        return $form;
    }
}
