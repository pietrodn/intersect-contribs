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
		<link rel="shortcut icon" href="/favicon.ico" />

		<title>Intersect Contribs - Wikimedia Tool Labs</title>
        <link rel="stylesheet" type="text/css" href="pietrodn.css" />
	</head>
<body>
	<div id="globalWrapper">
	<div id="column-content">
	<div id="content">
		<a id="top"></a>
		<div id="siteNotice"></div>		<h1 class="firstHeading">Intersect Contribs</h1>

		<div id="bodyContent">
			<h3 id="siteSub">Wikimedia Tool Labs - Pietrodn's tools.</h3>
			<!-- start content -->
			<p>This tool intersects the contributions of two users on a given WMF project, showing the pages edited by both of them.<br />
			It can help in discovering sockpuppets.</p>
			<p>Please note that intersecting edits of users with large contribution histories can lead to poor performance.</p>

			<form id="ListaForm" action="<? echo $_SERVER['PHP_SELF']; ?>" method="get">
			<fieldset>
			<table id="FormTable">
			<tr><td>
			<label id="wikiDb"><b>Project</b>:
			<select name="project">
			<?php
				/* Generates the project chooser dropdown */
				$selectedProject = (isset($_GET['project']) ? $_GET['project'] : NULL);
				projectChooser2($selectedProject);
			?>
			</select></label>
			<label><b>User 1</b>:</label>
			<input type="text" size="20" name="user1" value="<?
				if(isset($_GET['user1']))
					print htmlentities($_GET['user1'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
			?>"/><br />
			<label><b>User 2</b>:</label>
			<input type="text" size="20" name="user2" value="<?
				if(isset($_GET['user2']))
					print htmlentities($_GET['user2'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
			?>"/>
			</td><td>
			<label><b>Sort</b> by:</label><br />
			<input type="radio" name="sort" value="0" <? print (empty($_GET['sort']) || $_GET['sort'] == 0 ? 'checked' : '') ?> /><label>Namespace, alphabetical</label><br />
			<input type="radio" name="sort" value="1" <? print (isset($_GET['sort']) && $_GET['sort'] == 1 ? 'checked' : '') ?> /><label>Edits of user 1</label><br />
			<input type="radio" name="sort" value="2" <? print (isset($_GET['sort']) && $_GET['sort'] == 2 ? 'checked' : '') ?>  /><label>Edits of user 2</label>
			</td>
			</tr>
			</table>
			</fieldset>
			<input id="SubmitButton" type="submit" value="Submit" />
			</form>

<?php
	// Checks input integrity
    if(empty($_GET['project']) and empty($_GET['user1']) and empty($_GET['user2']))
        echo "";
    else if(empty($_GET['project']) or empty($_GET['user1']) or empty($_GET['user2']))
        printError('Some parameters are missing.');
    else if(!in_array($_GET['project'], $wikiProjects))
        printError('You tried to select a non-existent wiki!');
    else {
    	$wikiDb = $_GET['project'];
        $db_host = substr($wikiDb, 0, -2) . '.labsdb';
        
        $db = new mysqli($db_host, DB_USER, DB_PASSWORD, $wikiDb);
        if ($db == FALSE)
            die ("MySQL error.");
        
        $uName_1 = $db->real_escape_string($_GET['user1']);
        $uName_2 = $db->real_escape_string($_GET['user2']);
        $sort = intval($db->real_escape_string($_GET['sort']));
        $howSort = 0;
        if ($sort >= 0 && $sort <= 2)
            $howSort = $sort;
        
        if($howSort == 2) {
        	// Swap user names
        	$swap = $uName_1;
        	$uName_1 = $uName_2;
        	$uName_2 = $swap;
        	$howSort = 1;
        }
        
        $wikihost = getWikiHost($wikiDb);
        $nsArray = getNamespacesForDb($wikiDb);
        
        // The intersection is done in the database
        $query = "SELECT page_title, page_namespace" . ($howSort ? ", COUNT(page_id) AS eCount" : "") .
        " FROM revision, page
        WHERE rev_user_text LIKE \"$uName_1\"
        AND page_id=rev_page
        AND page_id IN (
        	SELECT DISTINCT rev_page FROM revision
        	WHERE rev_user_text LIKE \"$uName_2\"
        )
        GROUP BY page_id
        ORDER BY " . ($howSort ? "eCount DESC, " : "") . "page_namespace, page_title;";
        
        $res = $db->query($query) or die($db->error);
        
        print "<ol id=\"PageList\">";
        while($i = $res->fetch_assoc()) {
        	// Prints an entry for each page
        	
            $curPageName = $i['page_title'];
            $curPageNamespace = $i['page_namespace'];
            
            // Number of edits
            if($howSort)
            	$edits = $i['eCount'];
            
            $curPageNamespaceName = $nsArray[$curPageNamespace];
            // If not ns0, adds namespace
            $pageTitle = ($curPageNamespaceName
                ? $curPageNamespaceName . ":" . $curPageName
                : $curPageName);
            
            // Number of times user 1 (or 2, switched before) edited this page
            $editMsg = ($howSort
                ? ' (edits by ' . htmlentities($uName_1, ENT_COMPAT, 'UTF-8') . ': ' . $edits . ')'
                : '');
            $url = "//$wikihost/w/index.php?title=" . urlencode($pageTitle);
            print "<li><a href=\"$url\">" . htmlentities($pageTitle, ENT_COMPAT, 'UTF-8') . "</a>$editMsg</li>";
        }
        
        $db->close();
        print "</ol>";
    }
?>			</div><!-- end content -->
			<div class="visualClear"></div>

	</div>
	</div>
	<div id="column-one">
	<div id="p-cactions" class="portlet">
		<h3>Visite</h3>
		<div class="pBody">
			<ul>
				 <li id="ca-nstab-project" class="selected"><a href="<? echo $_SERVER['PHP_SELF']; ?>" title="The tool [t]" accesskey="t">tool</a></li>
				 <li id="ca-source"><a href="//github.com/pietrodn/intersect-contribs/blob/master/index.php" title="See the source code of this tool [s]" accesskey="s">source</a></li>
			</ul>
		</div>
	</div>

	<div class="portlet" id="p-logo">
		<a style="background-image: url(//wikitech.wikimedia.org/w/images/thumb/6/60/Wikimedia_labs_logo.svg/120px-Wikimedia_labs_logo.svg.png);" href="https://wikitech.wikimedia.org" title="Wikimedia Tool Labs" accesskey="w"></a>
	</div>
	<div class='generated-sidebar portlet' id='p-navigation'>
		<h3>Navigation</h3>
		<div class='pBody'>
			<ul>
				<li id="n-pietrodn"><a href="//wikitech.wikimedia.org/wiki/User:Pietrodn">Pietrodn</a></li>
				<li id="n-svn"><a href="//github.com/pietrodn/intersect-contribs">Git repository</a></li>
			</ul>
		</div>
	</div>
	
	<div class='generated-sidebar portlet' id='p-tools'>

		<h3>Tools</h3>
		<div class='pBody'>
			<ul>
				<li id="t-intersectcontribs"><a href="/intersect-contribs">Intersect Contribs</a></li>
				<li id="t-sectionlinks"><a href="/section-links">Section Links</a></li>
			</ul>
		</div>
	</div>
	</div>
		<div class="visualClear"></div>
		<div id="footer">
		<div id="f-copyrightico">
<!-- Creative Commons License -->
<a href="//www.gnu.org/licenses/gpl.html">
<img alt="CC-GNU GPL 2.0" src="images/cc-GPL-a.png" height="50" /></a>
<!-- /Creative Commons License -->
</div>
			<div id="f-poweredbyico"><a href="http://validator.w3.org/check?uri=referer"><img src="images/valid-xhtml10.png" alt="Valid XHTML 1.0 Strict" height="31" width="88" /></a></div>
		<ul id="f-list">
			<li id="about"><a href="//wikitech.wikimedia.org/wiki/User:Pietrodn" title="User:Pietrodn">About Pietrodn</a></li>
			<li id="email"><a href="mailto:pietrodn@toolserver.org" title="Mail">e-mail</a></li>
		</ul>
	</div>
</div>
</body></html>