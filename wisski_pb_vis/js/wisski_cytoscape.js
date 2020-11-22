/*Drupal behaviour get access to variable from CytoscapeController form. 
 *Initialize container "viewer" for library cytoscape.
 */

(function($, Drupal, drupalSettings) {

    Drupal.behaviors.wisski_cytoscape_behaviour = {
        attach: function(context, settings) {
            //alert(drupalSettings.wisski.vis.data); //alerts the value of PHP's $value
            //get data from controller
            $('div#viewer', context).once('wisski_cytoscape').each(function() {
                var json_data = drupalSettings.wisski.vis.data;

                //initialize container "viewer" for library cytoscape.  
                var cy = cytoscape({
                    container: document.getElementById("viewer"),
                    style: [{
                            selector: "node",
                            style: {
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
                                "label": "data(label)",
                                "target-endpoint": "outside-to-node-or-label",
                            }
                        }
                    ],
                });
                //adding vertices
                var weak_linked_graph = null;
                var rootsele = [];
                var mainbundlesele = [];
                for (var i = 0; i < json_data['nodes'].length; i++) {
                    var tmp_id = json_data['nodes'][i]['basename'];
                    var tmp_label = json_data['nodes'][i]['basename'];
                    var tmp_group_id = json_data['nodes'][i]['group_id'];
                    //Create a box around the weak connected graphs
                    if (weak_linked_graph === null || weak_linked_graph !== json_data['nodes'][i]['weak_connected_graph_id']) {
                        weak_linked_graph = json_data['nodes'][i]['weak_connected_graph_id'];
                        rootsele.push(tmp_label);
                        recently_added = cy.add({
                            data: {
                                group: 'top_level',
                                id: 'weak_connected_graph ' + weak_linked_graph,
                                label: 'weak connected graph'
                            }
                        });
                        //adding the root vertices
                        recently_added = cy.add({
                            data: {
                                group: 'root',
                                id: tmp_id,
                                parent: 'weak_connected_graph ' + weak_linked_graph,
                                label: tmp_label
                            }
                        });
                        recently_added.style('background-color', 'cyan')
                    } else {
                        //adding the internal and leaf vertices
                        recently_added = cy.add({
                            data: {
                                id: tmp_id,
                                parent: 'weak_connected_graph ' + weak_linked_graph,
                                label: tmp_label
                            }
                        });
                        recently_added.style('background-color', 'cyan')
                    }
                    // Change the layout and information of the node if it was special. 
                    // nodes with group_id = '0' tells us that the node is of an main bundle
                    if (tmp_group_id == '0') {
                        recently_added.data('main_bundle', '1');
                        recently_added.data('active_selection_count', '0');
                        mainbundlesele.push(recently_added);
                        recently_added.style('shape', 'square')
                        recently_added.style('background-color', 'blue')
                    }
                }

                //adding edges 
                for (var i = 0; i < json_data['edges'].length; i++) {
                    var tmp_id = json_data['edges'][i]['path']['label'] + ',' + json_data['edges'][i]['path']['source'] + ',' + json_data['edges'][i]['path']['target'];
                    var tmp_label = json_data['edges'][i]['path']['label'];
                    //check if the edge is already in the graph. If true, then increase its counter and change its style. If false, then add the edge
                    if (cy.getElementById(tmp_id).isEdge()) {
                        var tmp_modify_node = cy.getElementById(tmp_id);
                        //text-wrap 
                        tmp_modify_node.style('text-wrap', 'wrap');
                        tmp_modify_node.style('line-style', 'dashed');
                        tmp_modify_node.style('width', '7px');
                        tmp_modify_node.data('counter', (tmp_modify_node.data('counter') + 1));
                        tmp_modify_node.data('label', tmp_label + '\nAmount of paths: ' + tmp_modify_node.data('counter'))
                    } else {
                        cy.add({
                            data: {
                                id: tmp_id,
                                label: tmp_label,
                                source: json_data['edges'][i]['path']['source'],
                                target: json_data['edges'][i]['path']['target'],
                                counter: 1
                            }
                        });

                    }
                }

                //creating the initial layout of the graph
                var layout_breadthfirst_nodeDimensionsIncludeLabels_true = cy.layout({
                    name: 'breadthfirst',
                    fit: true, // whether to fit the viewport to the graph
                    directed: false, // whether the tree is directed downwards (or edges can point in any direction if false)
                    padding: 30, // padding on fit
                    circle: false, // put depths in concentric circles if true, put depths top down if false
                    grid: true, // whether to create an even grid into which the DAG is placed (circle:false only)
                    spacingFactor: 1.25, // positive spacing factor, larger => more space between nodes (N.B. n/a if causes overlap)
                    boundingBox: undefined, // constrain layout bounds; { x1, y1, x2, y2 } or { x1, y1, w, h }
                    avoidOverlap: true, // prevents node overlap, may overflow boundingBox if not enough space
                    nodeDimensionsIncludeLabels: true, // Excludes the label when calculating node bounding boxes for the layout algorithm
                    roots: rootsele, // the roots of the trees
                    maximal: true, // whether to shift nodes down their natural BFS depths in order to avoid upwards edges (DAGS only)
                    animate: false, // whether to transition the node positions
                    animationDuration: 500, // duration of animation in ms if enabled
                    animationEasing: undefined, // easing of animation if enabled,
                    animateFilter: function(node, i) { return true; }, // a function that determines whether the node should be animated.  All nodes animated by default on animate enabled.  Non-animated nodes are positioned immediately when the layout starts
                    ready: undefined, // callback on layoutready
                    stop: undefined, // callback on layoutstop
                    transform: function(node, position) { return position; } // transform a given node position. Useful for changing flow direction in discrete layouts
                }).run()

                //initial cytoscape initialization done. Adding functions to the buttons
                //Button zoom gets the function to zoom to fit the cytoscape nodes and edges to the canvas
                document.getElementById("zoom").onclick = function() {
                    cy.fit();
                };

                ////Button back gets the function to go back in the webbrowser
                document.getElementById("back").onclick = function() {
                    window.history.back();
                };

                ///Button label_overlap gets the function to toggle beetween 'nodeDimensionsIncludeLabels' true and false
                var toogle_label_overlap = true;
                document.getElementById("label_overlap").onclick = function() {
                    if (toogle_label_overlap) {
                        if (boolean_circle_layout) {
                            cy.layout({
                                name: 'breadthfirst',
                                fit: true, // whether to fit the viewport to the graph
                                directed: false, // whether the tree is directed downwards (or edges can point in any direction if false)
                                padding: 30, // padding on fit
                                circle: true, // put depths in concentric circles if true, put depths top down if false
                                grid: false, // whether to create an even grid into which the DAG is placed (circle:false only)
                                spacingFactor: 1.25, // positive spacing factor, larger => more space between nodes (N.B. n/a if causes overlap)
                                boundingBox: undefined, // constrain layout bounds; { x1, y1, x2, y2 } or { x1, y1, w, h }
                                avoidOverlap: true, // prevents node overlap, may overflow boundingBox if not enough space
                                nodeDimensionsIncludeLabels: false, // Excludes the label when calculating node bounding boxes for the layout algorithm
                                roots: rootsele, // the roots of the trees
                                maximal: true, // whether to shift nodes down their natural BFS depths in order to avoid upwards edges (DAGS only)
                                animate: false, // whether to transition the node positions
                                animationDuration: 500, // duration of animation in ms if enabled
                                animationEasing: undefined, // easing of animation if enabled,
                                animateFilter: function(node, i) { return true; }, // a function that determines whether the node should be animated.  All nodes animated by default on animate enabled.  Non-animated nodes are positioned immediately when the layout starts
                                ready: undefined, // callback on layoutready
                                stop: undefined, // callback on layoutstop
                                transform: function(node, position) { return position; } // transform a given node position. Useful for changing flow direction in discrete layouts
                            }).run()
                        } else {
                            cy.layout({
                                name: 'breadthfirst',
                                fit: true, // whether to fit the viewport to the graph
                                directed: false, // whether the tree is directed downwards (or edges can point in any direction if false)
                                padding: 30, // padding on fit
                                circle: false, // put depths in concentric circles if true, put depths top down if false
                                grid: true, // whether to create an even grid into which the DAG is placed (circle:false only)
                                spacingFactor: 1.25, // positive spacing factor, larger => more space between nodes (N.B. n/a if causes overlap)
                                boundingBox: undefined, // constrain layout bounds; { x1, y1, x2, y2 } or { x1, y1, w, h }
                                avoidOverlap: true, // prevents node overlap, may overflow boundingBox if not enough space
                                nodeDimensionsIncludeLabels: false, // Excludes the label when calculating node bounding boxes for the layout algorithm
                                roots: rootsele, // the roots of the trees
                                maximal: true, // whether to shift nodes down their natural BFS depths in order to avoid upwards edges (DAGS only)
                                animate: false, // whether to transition the node positions
                                animationDuration: 500, // duration of animation in ms if enabled
                                animationEasing: undefined, // easing of animation if enabled,
                                animateFilter: function(node, i) { return true; }, // a function that determines whether the node should be animated.  All nodes animated by default on animate enabled.  Non-animated nodes are positioned immediately when the layout starts
                                ready: undefined, // callback on layoutready
                                stop: undefined, // callback on layoutstop
                                transform: function(node, position) { return position; } // transform a given node position. Useful for changing flow direction in discrete layouts
                            }).run()
                        }
                        document.querySelector('#label_overlap').innerText = 'Avoid label overlap';
                        toogle_label_overlap = false;

                    } else {
                        if (boolean_circle_layout) {
                            cy.layout({
                                name: 'breadthfirst',
                                fit: true, // whether to fit the viewport to the graph
                                directed: false, // whether the tree is directed downwards (or edges can point in any direction if false)
                                padding: 30, // padding on fit
                                circle: true, // put depths in concentric circles if true, put depths top down if false
                                grid: false, // whether to create an even grid into which the DAG is placed (circle:false only)
                                spacingFactor: 1.25, // positive spacing factor, larger => more space between nodes (N.B. n/a if causes overlap)
                                boundingBox: undefined, // constrain layout bounds; { x1, y1, x2, y2 } or { x1, y1, w, h }
                                avoidOverlap: true, // prevents node overlap, may overflow boundingBox if not enough space
                                nodeDimensionsIncludeLabels: true, // Excludes the label when calculating node bounding boxes for the layout algorithm
                                roots: rootsele, // the roots of the trees
                                maximal: true, // whether to shift nodes down their natural BFS depths in order to avoid upwards edges (DAGS only)
                                animate: false, // whether to transition the node positions
                                animationDuration: 500, // duration of animation in ms if enabled
                                animationEasing: undefined, // easing of animation if enabled,
                                animateFilter: function(node, i) { return true; }, // a function that determines whether the node should be animated.  All nodes animated by default on animate enabled.  Non-animated nodes are positioned immediately when the layout starts
                                ready: undefined, // callback on layoutready
                                stop: undefined, // callback on layoutstop
                                transform: function(node, position) { return position; } // transform a given node position. Useful for changing flow direction in discrete layouts
                            }).run()
                        } else {
                            layout_breadthfirst_nodeDimensionsIncludeLabels_true.run();
                        }
                        document.querySelector('#label_overlap').innerText = 'Allow label overlap';
                        toogle_label_overlap = true;
                    }

                    cy.fit();
                };

                //Button reset_layout gets the function to reset the graph without toggling beetween 'nodeDimensionsIncludeLabels' true and false
                document.getElementById("reset_layout").onclick = function() {
                    layout_breadthfirst_nodeDimensionsIncludeLabels_true.run();
                    boolean_circle_layout = false;
                    document.querySelector('#circle_layout').innerText = 'Toggle Circle Layout';
                    toogle_label_overlap = true;
                    document.querySelector('#label_overlap').innerText = 'Allow label overlap';
                    cy.fit();
                };

                //Button circle_layout switches beetween circle layout and grid layout
                var boolean_circle_layout = false;
                document.getElementById("circle_layout").onclick = function() {
                    if (boolean_circle_layout) {
                        layout_breadthfirst_nodeDimensionsIncludeLabels_true.run();
                        boolean_circle_layout = false;
                        document.querySelector('#circle_layout').innerText = 'Toggle Circle Layout';
                        document.querySelector('#label_overlap').innerText = 'Allow label overlap';
                        toogle_label_overlap = true;
                    } else {
                        cy.layout({
                            name: 'breadthfirst',
                            fit: true, // whether to fit the viewport to the graph
                            directed: true, // whether the tree is directed downwards (or edges can point in any direction if false)
                            padding: 30, // padding on fit
                            circle: true, // put depths in concentric circles if true, put depths top down if false
                            grid: false, // whether to create an even grid into which the DAG is placed (circle:false only)
                            spacingFactor: 1.0, // positive spacing factor, larger => more space between nodes (N.B. n/a if causes overlap)
                            boundingBox: undefined, // constrain layout bounds; { x1, y1, x2, y2 } or { x1, y1, w, h }
                            avoidOverlap: true, // prevents node overlap, may overflow boundingBox if not enough space
                            nodeDimensionsIncludeLabels: true, // Excludes the label when calculating node bounding boxes for the layout algorithm
                            roots: rootsele, // the roots of the trees
                            maximal: false, // whether to shift nodes down their natural BFS depths in order to avoid upwards edges (DAGS only)
                            animate: false, // whether to transition the node positions
                            animationDuration: 500, // duration of animation in ms if enabled
                            animationEasing: undefined, // easing of animation if enabled,
                            animateFilter: function(node, i) { return true; }, // a function that determines whether the node should be animated.  All nodes animated by default on animate enabled.  Non-animated nodes are positioned immediately when the layout starts
                            ready: undefined, // callback on layoutready
                            stop: undefined, // callback on layoutstop
                            transform: function(node, position) { return position; } // transform a given node position. Useful for changing flow direction in discrete layouts
                        }).run()
                        boolean_circle_layout = true;
                        document.querySelector('#circle_layout').innerText = 'Toggle Topdown Layout';
                        document.querySelector('#label_overlap').innerText = 'Allow label overlap';
                        toogle_label_overlap = true;
                    }
                    cy.fit();
                };

                //Button save_png gets the function to save the current canvas as an png
                document.getElementById("save_png").onclick = function() {
                    var download = document.createElement('a');
                    download.href = cy.png();
                    download.download = 'graph.png';
                    download.click();
                }


                //Toggle the infobox to show hide in the canvas
                var infob = document.getElementById("infobox");
                var edgeob = document.getElementById("edgebox");
                infob.style.display = "none";
                document.getElementById("toggle_info").onclick = function() {
                    if (infob.style.display === "none") {
                        infob.style.display = "block";
                        edgeob.style.display = "block";
                    } else {
                        infob.style.display = "none";
                        edgeob.style.display = "none";
                    }
                }

                //Toggle the infobox to show hide in the canvas
                var legendob = document.getElementById("legend");
                legendob.style.display = "none";
                document.getElementById("legendbutton").onclick = function() {
                    if (legendob.style.display === "none") {
                        legendob.style.display = "block";
                    } else {
                        legendob.style.display = "none";
                    }
                }

                //onclick event, that changes the color of the selected node and all its neighbours temporarly and shows details in the infobox and edgeinfobox
                var neighbors = [];
                var currentselection = null;
                var current_node_json_data = null;
                var current_disamb_nodes = [];
                var current_path_edges = [];
                cy.on('click', 'node', function(evt) {
                    //exclude the top_level nodes from getting selected
                    if (evt.target.data('group') !== 'top_level') {
                        //cleanup and deselect the old nodes and edges
                        if (neighbors.length > 0) {
                            neighbors.forEach(function(ele) {
                                if (ele.isNode()) {
                                    ele.style('color', 'black')
                                }
                                if (ele.isEdge()) {
                                    ele.style('text-background-color', 'blue')
                                }
                            })
                        }
                        if (currentselection != null) {
                            currentselection.style('text-background-color', 'orange');
                            currentselection.connectedEdges().style('line-color', '#777777');
                            currentselection.connectedEdges().style('target-arrow-color', '#777777');
                        }
                        if (current_disamb_nodes.length > 0) {
                            for (var i = 0; i < current_disamb_nodes.length; i++) {
                                var tmp_element = cy.getElementById(current_disamb_nodes[i]);
                                if (tmp_element.data('main_bundle') == '1') {
                                    tmp_element.style('background-color', 'blue')
                                } else {
                                    tmp_element.style('background-color', 'cyan')
                                }

                            }
                            current_disamb_nodes = [];
                        }
                        if (current_path_edges.length > 0) {
                            for (var i = 0; i < current_path_edges.length; i++) {
                                var tmp_element = current_path_edges[i];
                                tmp_element.style('line-color', '#777777');
                                tmp_element.style('target-arrow-color', '#777777');
                                tmp_element.style('text-background-color', 'blue');
                            }
                            current_path_edges = [];
                        }

                        cy.elements().forEach(function(ele) {
                                if (ele.isEdge()) {
                                    ele.style('target-label', '');
                                }
                                if (ele.data('main_bundle') == '1') {
                                    ele.data('active_selection_count', '0');
                                }
                            })
                            //cleanup complete

                        //color the incoming edges and nodes
                        incomers = evt.target.incomers();
                        incomers.forEach(function(ele) {
                            if (ele.isNode()) {
                                ele.style('color', '#7F007D')
                            }
                            if (ele.isEdge()) {
                                ele.style('text-background-color', '#B19CD9')
                                ele.style('line-color', '#B19CD9');
                                ele.style('target-arrow-color', '#B19CD9');
                            }
                            neighbors.push(ele);
                        })

                        //get the json of the node
                        for (var i = 0; i < json_data['nodes'].length; i++) {
                            var json_tmp_id = json_data['nodes'][i]['basename'];
                            var selection_tmp_id = evt.target.data('id');
                            if (json_tmp_id === selection_tmp_id) {
                                current_node_json_data = json_data['nodes'][i];
                                break;
                            }
                        }
                        //create the edgeinfo of the selected node
                        if (current_node_json_data['all_paths_starting_from_node']) {
                            var current_node_id = current_node_json_data['all_paths_starting_from_node']['id'];
                            var current_node_group_id = current_node_json_data['all_paths_starting_from_node']['group_id'];
                            var current_node_name = current_node_json_data['all_paths_starting_from_node']['name'];
                            var current_node_disamb = current_node_json_data['all_paths_starting_from_node']['disamb'];
                            var current_node_path = current_node_json_data['all_paths_starting_from_node']['path'];
                            var edge_inner_HTML = '<table><caption><b>Datatable Edge</b></caption>' +
                                '<thead><tr><th colspan="5">All Paths starting from ' + current_node_json_data.basename + '</th></tr>' +
                                '<tr><th>Label</th><th>Name</th><th>Disamb Node</th><th>Group ID</th><th>ID</th></tr>'
                            for (var i = 0; i < current_node_id.length; i++) {
                                edge_inner_HTML +=
                                    '<tr>' +
                                    '<td>' + (i + 1).toString() + '</td>' +
                                    '<td>' + current_node_name[i] + '</td>'
                                if (current_node_disamb[i] > 0) {
                                    //color the disamb nodes and save them to restore the color on unselect
                                    var basename_disamb_node = current_node_path['x'][i][(current_node_disamb[i] - 1)].split(/[\\/]/).pop();
                                    current_disamb_nodes.push(basename_disamb_node);
                                    cy.getElementById(basename_disamb_node).style('background-color', 'yellow');
                                    edge_inner_HTML += '<td>' + basename_disamb_node + '</td>';

                                } else {
                                    edge_inner_HTML += '<td>' + 'No Disamb Node' + '</td>';
                                }

                                edge_inner_HTML +=
                                    '<td>' + current_node_group_id[i] + '</td>' +
                                    '<td>' + current_node_id[i] + '</td>' +
                                    '</tr>'

                                //get the paths from the current node and put it on the infobox
                                var current_node_path_x = current_node_path['x'][i];
                                var current_node_path_y = current_node_path['y'][i];
                                var tmp_length_y;
                                if (Array.isArray(current_node_path_y)) {
                                    tmp_length_y = current_node_path_y.length;
                                } else {
                                    tmp_length_y = 1;
                                }
                                for (var j = 0; j < tmp_length_y; j++) {
                                    var tmp_label;
                                    var tmp_source = current_node_path_x[j].split(/[\\/]/).pop();
                                    var tmp_target = current_node_path_x[j + 1].split(/[\\/]/).pop();
                                    var tmp_target_element = cy.getElementById(tmp_target);
                                    //count the amount of direct paths current Main Node and all others
                                    if (tmp_target_element.data('main_bundle') == '1') {
                                        tmp_target_element.data('active_selection_count', parseInt(tmp_target_element.data('active_selection_count')) + 1);
                                    }
                                    if (Array.isArray(current_node_path_y)) {
                                        tmp_label = current_node_path_y[j].split(/[\\/]/).pop();
                                    } else {
                                        tmp_label = current_node_path_y.split(/[\\/]/).pop();
                                    }
                                    var tmp_id = tmp_label + ',' + tmp_source + ',' + tmp_target;
                                    var tmp_edge = cy.getElementById(tmp_id);
                                    current_path_edges.push(tmp_edge);
                                    if (current_node_disamb[i] == '0' || (current_node_disamb[i] - 1) <= j) {
                                        tmp_edge.style('line-color', 'yellow');
                                        tmp_edge.style('target-arrow-color', 'yellow');
                                        tmp_edge.style('text-background-color', 'yellow');
                                        var old_target_data = '';
                                        old_target_data = old_target_data.concat(tmp_edge.style('target-label'))
                                        if (old_target_data != '') {
                                            old_target_data = old_target_data.concat(',')
                                        }
                                        tmp_edge.style('target-label', old_target_data.concat((i + 1).toString()));
                                    } else {
                                        tmp_edge.style('line-color', 'orange');
                                        tmp_edge.style('target-arrow-color', 'orange');
                                        tmp_edge.style('text-background-color', 'orange');
                                        var old_target_data = '';
                                        old_target_data = old_target_data.concat(tmp_edge.style('target-label'))
                                        if (old_target_data != '') {
                                            old_target_data = old_target_data.concat(',')
                                        }
                                        tmp_edge.style('target-label', old_target_data.concat((i + 1).toString()));
                                    }
                                }

                            }
                            edge_inner_HTML += '</table>'
                        } else {
                            edge_inner_HTML = 'No Paths start from here';
                        }

                        //add the current element to the infobox no fullinfo
                        if (current_node_json_data.fullinfo == '0') {
                            var degree_centrality = parseInt(current_node_json_data.in_degree_centrality) + parseInt(current_node_json_data.out_degree_centrality);
                            document.getElementById("infobox").innerHTML =
                                '<table><caption><b>' + 'Datatable Node' + '</b></caption>' +
                                '<thead><tr><th colspan="2">' + current_node_json_data.basename + '</th></tr>' +
                                '<tr><th>Attribute</th><th>Value</th></tr>' +
                                '</thead>' +
                                '<tr><td>basename</td><td>' + current_node_json_data.basename + '</td></tr>' +
                                '<tr><td>fullpath</td><td>' + current_node_json_data.fullname + '</td></tr>' +
                                '<tr><td>degree centrality</td><td>' + degree_centrality + '</td></tr>' +
                                '<tr><td>in degree centrality</td><td>' + current_node_json_data.in_degree_centrality + '</td></tr>' +
                                '<tr><td>out degree centrality</td><td>' + current_node_json_data.out_degree_centrality + '</td></tr>' +
                                '</table>';
                            document.getElementById("edgebox").innerHTML = edge_inner_HTML;

                        }
                        //add the current element to the infobox with all the info available
                        else {
                            var tmp_mainbundles_info = '';
                            for (var i = 0; i < mainbundlesele.length; i++) {
                                tmp_mainbundles_info += '<tr><td>' + mainbundlesele[i].data('id') + '</td><td>' + mainbundlesele[i].data('active_selection_count') + '</td></tr>';
                            }
                            var degree_centrality = parseInt(current_node_json_data.in_degree_centrality) + parseInt(current_node_json_data.out_degree_centrality);
                            document.getElementById("infobox").innerHTML =
                                '<table><caption><b>' + 'Datatable Node' + '</b></caption>' +
                                '<thead><tr><th colspan="2">' + current_node_json_data.basename + '</th></tr>' +
                                '<tr><th>Attribute</th><th>Value</th></tr>' +
                                '</thead>' +
                                '<tr><td>ID</td><td>' + current_node_json_data.id + '</td></tr>' +
                                '<tr><td>name</td><td>' + current_node_json_data.name + '</td></tr>' +
                                // '<tr><td>weight</td><td>' + current_node_json_data.weight + '</td></tr>' +
                                // '<tr><td>group_id</td><td>' + current_node_json_data.group_id + '</td></tr>' +
                                // '<tr><td>is_group</td><td>' + current_node_json_data.is_group + '</td></tr>' +
                                '<tr><td>basename</td><td>' + current_node_json_data.basename + '</td></tr>' +
                                '<tr><td>fullpath</td><td>' + current_node_json_data.fullname + '</td></tr>' +
                                '<tr><td>degree centrality</td><td>' + degree_centrality + '</td></tr>' +
                                '<tr><td>in degree centrality</td><td>' + current_node_json_data.in_degree_centrality + '</td></tr>' +
                                '<tr><td>out degree centrality</td><td>' + current_node_json_data.out_degree_centrality + '</td></tr>' +
                                '<tr><td>uuid</td><td>' + current_node_json_data.uuid + '</td></tr>' +
                                '<tr><td>enabled</td><td>' + current_node_json_data.enabled + '</td></tr>' +
                                '</table>' +
                                '<table><caption><b>' + 'Mainbundles' + '</b></caption>' +
                                '<thead><tr><th>Mainbundles</th><th>Direct Relations</th></tr>' +
                                '</thead>' +
                                tmp_mainbundles_info +
                                '</table>';
                            document.getElementById("edgebox").innerHTML = edge_inner_HTML;
                        }

                        //select the new nodes and edges
                        currentselection = evt.target;
                        currentselection.style('text-background-color', 'green');
                    }
                });

                cy.ready();
            });
        }
    };
})(jQuery, Drupal, drupalSettings);