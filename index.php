<?php
/*
This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

require_once "config.php";
require_once "includes/Database.class.php";
require_once 'includes/WikiUtils.php';
require_once 'includes/Intersect.php';
require_once 'includes/View.php';

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="keywords" content="intersect contributions wmflabs pietrodn" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="/favicon.ico" />

    <title>Intersect Contribs - Wikimedia Tool Labs</title>
    <link href="//tools-static.wmflabs.org/cdnjs/ajax/libs/twitter-bootstrap/3.3.6/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/pietrodn.css" rel="stylesheet">
</head>
<body>
    <!-- Fixed navbar -->
    <div class="navbar navbar-inverse navbar-fixed-top" role="navigation">
        <div class="container">
            <div class="navbar-header">
                <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <a class="navbar-brand" href="//tools.wmflabs.org/">WMF Tool Labs</a>
            </div>
            <div class="navbar-collapse collapse">
                <ul class="nav navbar-nav">
                    <li class="active"><a href=".">Intersect Contribs</a></li>
                    <li><a href="//github.com/pietrodn/intersect-contribs">Source (GitHub)</a></li>
                    <li><a href="//wikitech.wikimedia.org/wiki/User:Pietrodn">Pietrodn</a></li>
                    <li><a href="../section-links/">Section Links</a></li>
                </ul>
            </div><!--/.nav-collapse -->
        </div>
    </div>

    <div class="jumbotron">
        <div class="container">
            <div class="media">
                <a class="pull-left" href="#">
                    <img id="wikitech-logo" class="media-object" src="images/WikitechLogo.png" alt="Wikitech Logo">
                </a>
                <div class="media-body">
                    <h1>Intersect Contribs<br />
                        <small>Wikimedia Tool Labs — Pietrodn's tools.</small>
                    </h1>
                </div>
            </div>
            <!-- start content -->
            <p>This tool intersects the contributions of two users on a given WMF project, showing the pages edited by both of them.<br />
                It can help in discovering sockpuppets.</p>
                <p>Please note that intersecting edits of users with huge contribution histories may take some time.</p>

                <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="get">
                    <div class="form-group">
                        <label for="wikiDb">Project</label>:
                        <select class="form-control" name="project" id="wikiDb" required>
                            <?php
                            /* Generates the project chooser dropdown */
                            $selectedProject = (isset($_GET['project']) ? $_GET['project'] : NULL);
                            projectChooser($selectedProject);
                            ?>
                        </select>
                    </div>
                    <table id="userForms">
                        <tr>
                            <td class="form-group">
                                <label for="user1">User 1:</label>
                                <input placeholder="First user" class="form-control" type="text" required name="user1" id="user1" value="<?php
                                if(isset($_GET['user1']))
                                print htmlentities($_GET['user1'], ENT_QUOTES, 'UTF-8');
                                ?>"/>
                            </td>
                            <td class="form-group">
                                <label for="user2">User 2:</label>
                                <input placeholder="Second user" class="form-control" type="text" required name="user2" id="user2" value="<?php
                                if(isset($_GET['user2']))
                                print htmlentities($_GET['user2'], ENT_QUOTES, 'UTF-8');
                                ?>"/>
                            </td>
                        </tr>
                    </table>
                    <div class="form-group">
                        <label class="radio control-label">Sorting:</label>
                        <div class="radio">
                            <label>
                                <input type="radio" name="sort" value="0" required <?php print (empty($_GET['sort']) || $_GET['sort'] == 0 ? 'checked' : '') ?> />
                                Sort by namespace, alphabetical
                            </label>
                        </div>
                        <div class="radio">
                            <label>
                                <input type="radio" name="sort" value="1" required <?php print (isset($_GET['sort']) && $_GET['sort'] == 1 ? 'checked' : '') ?> />
                                Sort by edits of user 1
                            </label>
                        </div>
                        <div class="radio">
                            <label>
                                <input type="radio" name="sort" value="2" required <?php print (isset($_GET['sort']) && $_GET['sort'] == 2 ? 'checked' : '') ?>  />
                                Sort by edits of user 2
                            </label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="namespaceFilter">Include only pages in namespace:</label>
                        <select class="form-control" name="namespaceFilter" id="namespaceFilter">
                            <option value="all" id="allNamespaces">All</option>
                        </select>
                    </div>
                    <input class="btn btn-primary" id="SubmitButton" type="submit" value="Submit" />
                </form>
            </div>
        </div>

        <div class="container">
            <?php
            // Checks input integrity
            if(empty($_GET['project']) and empty($_GET['user1']) and empty($_GET['user2'])) {
                echo "";
            } else if(empty($_GET['project']) or empty($_GET['user1']) or empty($_GET['user2'])) {
                printError('Some parameters are missing.');
            } else if(!($wikihost = getWikiDomainFromDBName($_GET['project']))) {
                printError('You tried to select a non-existent wiki!');
            } else {
                // Valid input, we can proceed.

                $wikiDb = $_GET['project'];
                $db_host = $wikiDb . '.labsdb'; // Database host name
                $db_name = $wikiDb . '_p';

                if(OVERRIDE_DB) { /* override for local debug purposes */
                    $db_host = DEFAULT_HOST;
                    $db_name = DEFAULT_DB;
                }

                $db = Database::database($db_host, $db_name);

                $uName_1 = $db->escape($_GET['user1']);
                $uName_2 = $db->escape($_GET['user2']);

                $howSort = 0; // Default
                if(!empty($_GET['sort']) && is_numeric($_GET['sort'])) {
                    $sort = intval($_GET['sort']);
                    if ($sort >= 0 && $sort <= 2) {/* Sanity check */
                        $howSort = $sort;
                    }
                }

                if($howSort == 2) {
                    // Swap user names
                    $swap = $uName_1;
                    $uName_1 = $uName_2;
                    $uName_2 = $swap;
                    $howSort = 1;
                }

                /* Namespace filter */
                if(isset($_GET['namespaceFilter']) && is_numeric($_GET['namespaceFilter'])) {
                    $nsFilter = intval($_GET['namespaceFilter']);
                } else {
                    $nsFilter = FALSE;
                }

                // Computes the intersection of contributions of the users.
                $contributionList = intersectContribs($db, [$uName_1, $uName_2], ($howSort == 0 ? SORT_ALPHANUM : SORT_EDITS), $nsFilter);

                // Output list of pages
                printPageList($contributionList, $wikihost, ($howSort != 0), $uName_1);
            }
            ?>
        </div>
        <div id="footer">
            <div class="container">
                <a href="//tools.wmflabs.org/"><img id="footer-icon" src="//tools-static.wmflabs.org/static/logos/powered-by-tool-labs.png" title="Powered by Wikimedia Tool Labs" alt="Powered by Wikimedia Tool Labs" /></a>
                <p class="text-muted credit">
                    Made by <a href="//wikitech.wikimedia.org/wiki/User:Pietrodn">Pietro De Nicolao (Pietrodn)</a>.
                    Licensed under the
                    <a href="//www.gnu.org/licenses/gpl.html">GNU GPL</a> license.
                </p>
            </div>
        </div>

        <!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
        <script src="//tools-static.wmflabs.org/cdnjs/ajax/libs/jquery/2.2.2/jquery.min.js"></script>
        <!-- Include all compiled plugins (below), or include individual files as needed -->
        <script src="//tools-static.wmflabs.org/cdnjs/ajax/libs/twitter-bootstrap/3.3.6/js/bootstrap.min.js"></script>

        <!-- Script to generate the namespace filter dropdown -->
        <script src="js/ns-filter.js"></script>
    </body>
    </html>
