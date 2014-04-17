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
    include_once 'pietrodnUtils.php';
?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset="UTF-8">
		<meta name="keywords" content="intersect contributions wmflabs pietrodn" />
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<link rel="shortcut icon" href="/favicon.ico" />

		<title>Intersect Contribs - Wikimedia Tool Labs</title>
		<link href="//tools.wmflabs.org/static/res/bootstrap/3.1.1/css/bootstrap.min.css" rel="stylesheet">
        <link href="pietrodn.css" rel="stylesheet">
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
				<div class="form-group">
					<label for="user1">User 1:</label>
					<input placeholder="First user" class="form-control" type="text" required name="user1" id="user1" value="<?php
						if(isset($_GET['user1']))
							print htmlentities($_GET['user1'], ENT_QUOTES, 'UTF-8');
					?>"/>
				</div>
				<div class="form-group">
					<label for="user2">User 2:</label>
					<input placeholder="Second user" class="form-control" type="text" required name="user2" id="user2" value="<?php
						if(isset($_GET['user2']))
							print htmlentities($_GET['user2'], ENT_QUOTES, 'UTF-8');
					?>"/>
				</div>
				<div class="radio">
					<label>Sort by namespace, alphabetical</label>
					<input type="radio" name="sort" value="0" required <?php print (empty($_GET['sort']) || $_GET['sort'] == 0 ? 'checked' : '') ?> />
				</div>
				<div class="radio">
					<label>Sort by edits of user 1</label>
					<input type="radio" name="sort" value="1" required <?php print (isset($_GET['sort']) && $_GET['sort'] == 1 ? 'checked' : '') ?> />
				</div>
				<div class="radio">
					<label>Sort by edits of user 2</label>
					<input type="radio" name="sort" value="2" required <?php print (isset($_GET['sort']) && $_GET['sort'] == 2 ? 'checked' : '') ?>  />
				</div>
			<input class="btn btn-default" id="SubmitButton" type="submit" value="Submit" />
			</form>
		</div>
	</div>
	
	<div class="container">

<?php
	// Checks input integrity
    if(empty($_GET['project']) and empty($_GET['user1']) and empty($_GET['user2']))
        echo "";
    else if(empty($_GET['project']) or empty($_GET['user1']) or empty($_GET['user2']))
        printError('Some parameters are missing.');
    else if(!($wikihost = getWikiHost($_GET['project'])))
		printError('You tried to select a non-existent wiki!');
    else {
    	// Valid input, we can proceed.
    	
    	$wikiDb = $_GET['project'];
        $db_host = $wikiDb . '.labsdb'; // Database host name
        $database = $wikiDb . '_p';
        
        if(OVERRIDE_DB) { /* override for local debug purposes */
        	$database = DEFAULT_DB;
        	$db_host = DEFAULT_HOST;
        }
        
        $db = new mysqli($db_host, DB_USER, DB_PASSWORD, $database);
        if ($db == FALSE)
            die ("MySQL error.");
        
        $uName_1 = $db->real_escape_string($_GET['user1']);
        $uName_2 = $db->real_escape_string($_GET['user2']);
        
        /* Sorting options:
        		0: sort by namespace, page name
        		1: sort by <User1> number of edits
        		2: sort by <User2> number of edits
        */
        $howSort = 0; // Default
        if(is_numeric($_GET['sort'])) {
        	$sort = intval($_GET['sort']);
			if ($sort >= 0 && $sort <= 2) /* Sanity check */
				$howSort = $sort;
        }
        
        if($howSort == 2) {
        	// Swap user names
        	$swap = $uName_1;
        	$uName_1 = $uName_2;
        	$uName_2 = $swap;
        	$howSort = 1;
        }
        
        /* Gets namespaces */
        $nsArray = getNamespacesAPI($wikihost);
        
        /* Intersection and ordering are done directly by the database.
        	*revision_userindex*: indexed view of the revision table (more performant) */
        
        $revTable = REVISION_OPTIMIZED;	/* revision or revision_userindex (see config.php) */
        $query = "SELECT page_title, page_namespace"
			. ($howSort ? ", COUNT(page_id) AS eCount" : "") .
			" FROM $revTable, page
			WHERE rev_user_text LIKE \"$uName_1\"
			AND page_id=rev_page
			AND page_id IN (
				SELECT DISTINCT rev_page FROM $revTable
				WHERE rev_user_text LIKE \"$uName_2\"
			)
			GROUP BY page_id
			ORDER BY " . ($howSort ? "eCount DESC, " : "") . "page_namespace, page_title;";
        
        $res = $db->query($query) or die($db->error);
        
        if($res->num_rows !== 0) {
			// Printing output.
			echo '<div class="alert alert-success">';
			echo $res->num_rows . ' results found.';
			echo '</div>';	
			print "<ol id=\"PageList\">";
			while($i = $res->fetch_assoc()) {
				// Prints an entry for each page
			
				$curPageName = $i['page_title'];
				$curPageNamespace = $i['page_namespace'];
			
				// Number of edits, if needed.
				if($howSort)
					$edits = $i['eCount'];
			
				$curPageNamespaceName = $nsArray[$curPageNamespace];
				// If not ns0, adds namespace prefix.
				$pageTitle = ($curPageNamespaceName
					? $curPageNamespaceName . ":" . $curPageName
					: $curPageName);
			
				// Number of times user 1 (or 2, switched before) edited this page
				$editMsg = ($howSort
					? ' (edits by ' . htmlentities($uName_1, ENT_COMPAT, 'UTF-8') . ': ' . $edits . ')'
					: '');
				$url = "//$wikihost/w/index.php?title=" . urlencode($pageTitle);
				$displayTitle = htmlentities(str_replace('_', ' ', $pageTitle), ENT_COMPAT, 'UTF-8');
				print "<li><a href=\"$url\">" . $displayTitle . "</a>$editMsg</li>";
			}
		
			print "</ol>";
		} else {
			echo '<div class="alert alert-info">';
			echo 'No results found.';
			echo '</div>';	
		}
        $db->close();
    }
	?>
	</div>
	<div id="footer">
		<div class="container">
			<a href="//tools.wmflabs.org/"><img id="footer-icon" src="//tools.wmflabs.org/static/img/powered-by-tool-labs.png" title="Powered by Wikimedia Tool Labs" alt="Powered by Wikimedia Tool Labs" /></a>
			<p class="text-muted credit">
			Made by <a href="//wikitech.wikimedia.org/wiki/User:Pietrodn">Pietro De Nicolao (Pietrodn)</a>.
			Licensed under the
			<a href="//www.gnu.org/licenses/gpl.html">GNU GPL</a> license.
			</p>
		</div>
	</div>
	
	<!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
    <script src="//tools.wmflabs.org/static/res/jquery/2.1.0/jquery.min.js"></script>
    <!-- Include all compiled plugins (below), or include individual files as needed -->
    <script src="//tools.wmflabs.org/static/res/bootstrap/3.1.1/js/bootstrap.min.js"></script>
    
	</body>
</html>