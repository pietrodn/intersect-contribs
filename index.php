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

require_once 'config.php';
require_once 'includes/Database.class.php';
require_once 'includes/WikiUtils.php';
require_once 'includes/Intersect.php';
require_once 'includes/View.php';

define('DEFAULT_USERS', 8);
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

            <p>This tool intersects the contributions of two or more users on a given WMF project, showing the pages edited by all of them.<br />
                It can help in discovering sockpuppets.
            </p>

            <div class="alert alert-warning">
                <b>Update</b>: On 2016-04-05 I updated the tool to support the intersection of the contributions of <b>multiple users</b>.
                Unfortunately, to handle this in a reasonable way, I had to <b>change the format of the URL parameters</b>.
                The old URL format ("user1" and "user2") is still supported, but eventually this will be removed.<br />
                This means that existing links to the tool referring to a specific couple of users will be broken.
            </div>

            <ul>
                <li>You can provide between 2 and 8 users. Empty fields will be ignored.</li>
                <li>The pages can be filtered by namespace.</li>
                <li>Please note that intersecting edits of users with huge contribution histories may take some time.</li>
            </ul>

            <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="get" class="well well-lg">
                <div id="options">
                    <div class="form-group" id="projectForm">
                        <label for="wikiDb">Project</label>:
                        <select class="form-control" name="project" id="wikiDb" required>
                            <?php
                            /* Generates the project chooser dropdown */
                            $selectedProject = (isset($_GET['project']) ? $_GET['project'] : NULL);
                            projectChooser($selectedProject);
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="namespaceFilter">Include only pages in namespace:</label>
                        <select class="form-control" name="namespaceFilter" id="namespaceFilter">
                            <option value="all" id="allNamespaces">All</option>
                        </select>
                    </div>
                </div>

                <div id="user-forms">
                    <?php
                    $providedUsers = (isset($_GET['users']) ? count($_GET['users']) : 0);

                    for($i=1; $i<=max(DEFAULT_USERS, $providedUsers); $i++) {
                        echo '<div class="form-group">' .
                        "<label for=\"user$i\">User $i:</label>" .
                        "<input placeholder=\"User $i\" class=\"form-control\" type=\"text\" "
                        . ($i <= 2? "required" : "") . " name=\"users[]\" value=\"" .
                        (isset($_GET['users'][$i-1]) ? htmlentities($_GET['users'][$i-1], ENT_QUOTES, 'UTF-8') : "") .
                        "\"/></div>";
                    }
                    ?>
                </div>

                <div class="form-group" id="sorting">
                    <label class="radio control-label">Sorting:</label>
                    <div class="radio">
                        <label>
                            <input type="radio" name="sort" value="0" required <?php print (empty($_GET['sort']) || $_GET['sort'] == 0 ? 'checked' : '') ?> />
                            Sort by namespace, then by title (alphabetical)
                        </label>
                    </div>
                    <div class="radio">
                        <label>
                            <input type="radio" name="sort" value="1" required <?php print (isset($_GET['sort']) && $_GET['sort'] == 1 ? 'checked' : '') ?> />
                            Sort by no. of first user's edits
                        </label>
                    </div>
                </div>

                <input class="btn btn-primary" id="SubmitButton" type="submit" value="Submit" />
            </form>
        </div>
    </div>

    <div class="container">
        <?php
        /* Getting valid users by removing empty fields.
        * array_values re-indexes the array.
        * Escaping is done by the database and output functions.
        */
        $users = ( isset($_GET['users']) ? array_values(array_filter($_GET['users'])) : array() );

        /* Legacy support for older URL format (to be removed at a certain time) */
        if(isset($_GET['user1']) && isset($_GET['user2'])) {
            $users[0] = $_GET['user1'];
            $users[1] = $_GET['user2'];
        }

        // Checks input integrity
        if(empty($_GET['project']) and count($users)==0) {
            echo "";
        } else if(empty($_GET['project']) or count($users)<2) {
            printError('Some parameters are missing.');
        } else if(!($wikihost = getWikiDomainFromDBName($_GET['project']))) {
            printError('You tried to select a non-existent wiki!');
        } else {
            // Valid input, we can proceed.

            $db_host = $_GET['project'] . '.web.db.svc.eqiad.wmflabs'; // Database host name
            $db_name = $_GET['project'] . '_p';

            if(OVERRIDE_DB) { /* override for local debug purposes */
                $db_host = DEFAULT_HOST;
                $db_name = DEFAULT_DB;
            }

            $db = Database::database($db_host, $db_name);

            $howSort = 0; // Default
            if(!empty($_GET['sort']) && is_numeric($_GET['sort'])) {
                $sort = intval($_GET['sort']);
                if ($sort >= 0 && $sort <= 1) {/* Sanity check */
                    $howSort = $sort;
                }
            }

            /* Namespace filter */
            if(isset($_GET['namespaceFilter']) && is_numeric($_GET['namespaceFilter'])) {
                $nsFilter = intval($_GET['namespaceFilter']);
            } else {
                $nsFilter = FALSE;
            }

            // Computes the intersection of contributions of the users.
            $contributionList = intersectContribs(
            $db,
            $users,
            ($howSort == 0 ? SORT_ALPHANUM : SORT_EDITS),
            $nsFilter);

            // Output list of pages
            printPageList($contributionList, $wikihost, ($howSort != 0), $users[0]);
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
