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
			<p>This tool shows all the pages of a Wikimedia Project which were edited by User 1 <i>and</i> User 2.<br />
			It can be useful in order to discover sockpuppets.</p>
			<p>Please note that intersecting edits of users with large contribution histories can lead to poor performance.</p>

<form id="ListaForm" action="<? echo $_SERVER['PHP_SELF']; ?>" method="get">
<fieldset>
<table id="FormTable">
<tr><td>
<label id="wikiDb"><b>Project</b>:
<select name="wikiDb">
<?php

    ts_projectchooser((isset($_GET['wikiDb']) ? $_GET['wikiDb'] : NULL), $allWikis); // $allWikis passed by reference!

?>
</select></label>
<label><b>User 1</b>:</label>
<input type="text" size="20" name="firstUser" value="<?
	if(isset($_GET['firstUser']))
		print htmlentities($_GET['firstUser'], ENT_QUOTES, 'UTF-8');
?>"/><br />
<label><b>User 2</b>:</label>
<input type="text" size="20" name="secondUser" value="<?
	if(isset($_GET['secondUser']))
		print htmlentities($_GET['secondUser'], ENT_QUOTES, 'UTF-8');
?>"/>
</td><td>
<label><b>Sort</b> by:</label><br />
<input type="radio" name="sort" value="0" <? print (empty($_GET['sort']) || $_GET['sort'] == 0 ? 'checked="checked"' : '') ?> /><label>Namespace, alphabetical</label><br />
<input type="radio" name="sort" value="1" <? print (isset($_GET['sort']) && $_GET['sort'] == 1 ? 'checked="checked"' : '') ?> /><label>Edits of user 1</label><br />
<input type="radio" name="sort" value="2" <? print (isset($_GET['sort']) && $_GET['sort'] == 2 ? 'checked="checked"' : '') ?>  /><label>Edits of user 2</label>
</td>
</tr>
</table>
</fieldset>
<input id="SubmitButton" type="submit" value="Submit" />
</form>
<?php
    if(empty($_GET['wikiDb']) and empty($_GET['firstUser']) and empty($_GET['secondUser']))
        echo "";
    else if(empty($_GET['wikiDb']) or empty($_GET['firstUser']) or empty($_GET['secondUser']))
        printError('Some parameters are missing.');
    else if(!in_array($_GET['wikiDb'], $wikiProjects))
        printError('You tried to select a non-existent wiki!');
    else
    {
    	$wikiDb = $_GET['wikiDb'];
        $db_host = $wikiDb . '.labsdb';
        if($DEBUG) {
            $db_host = 'localhost';
            $wikiDb = 'itwikibooks_p';
        }
        
        $db = mysql_connect($db_host, $db_user, $db_password);
        if ($db == FALSE)
            die ("Can't log into MySQL.");
        
        mysql_select_db($wikiDb . '_p', $db)
            or die("Can't select the database.");
        
        $uName_1 = mysql_real_escape_string($_GET['firstUser']);
        $uName_2 = mysql_real_escape_string($_GET['secondUser']);
        $sort = intval(mysql_real_escape_string($_GET['sort']));
        $howSort = 0;
        if ($sort >= 0 && $sort <= 2)
        {
            $howSort = $sort;
        }
        
        if($howSort == 2) {
        	/* Switches users */
        	$swap = $uName_1;
        	$uName_1 = $uName_2;
        	$uName_2 = $swap;
        	$howSort = 1;
        }
        
        $wikihost = getWikiHost($wikiDb . '_p');
        $nsArray = getNamespacesForDb($wikiDb);
        
        $query = "SELECT page_title, page_namespace, page_id" . ($howSort ? ", COUNT(rev_id) AS eCount" : "") .
        " FROM revision, page
        WHERE rev_user_text LIKE \"$uName_1\"
        AND page_id=rev_page
        AND page_id IN (
        	SELECT DISTINCT rev_page FROM revision
        	WHERE rev_user_text LIKE \"$uName_2\"
        )
        GROUP BY page_id
        ORDER BY " . ($howSort ? "eCount DESC, " : "") . "page_namespace, page_title;";
        
        $res = mysql_query($query, $db) or die (mysql_error());
        // $count = mysql_num_rows($res);
        
        print "<ol id=\"PageList\">";
        while($i = mysql_fetch_assoc($res))
        {
            $curPageName = $i['page_title'];
            $curPageNamespace = $i['page_namespace'];
            
            if($howSort)
            	$edits = $i['eCount'];
            
            $curPageNamespaceName = $nsArray[$curPageNamespace];
            // If not ns0, adds namespace
            $pageTitle = ($curPageNamespaceName
                ? $curPageNamespaceName . ":" . $curPageName
                : $curPageName);
            
            $editMsg = ($howSort
                ? ' (edits by ' . htmlentities($uName_1) . ': ' . $edits . ')'
                : '');
            $url = "//$wikihost/w/index.php?title=" . urlencode($pageTitle);
            print "<li><a href=\"$url\">$pageTitle</a>$editMsg</li>";
        }
        
        mysql_close($db);
        print "</ol>";
    }
?>			</div>			<!-- end content -->
						<div class="visualClear"></div>

	</div>
		</div>
		<div id="column-one">
	<div id="p-cactions" class="portlet">
		<h3>Visite</h3>
		<div class="pBody">
			<ul>
	
				 <li id="ca-nstab-project" class="selected"><a href="<? echo $_SERVER['PHP_SELF']; ?>" title="The tool [t]" accesskey="t">tool</a></li>

				 <li id="ca-source"><a href="showCode.php?file=<?php ts_print_scriptname() ?>" title="See the source code of this tool [s]" accesskey="s">source</a></li>
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
				<li id="n-pietrodn"><a href="index.php" title="Pietrodn [p]" accesskey="p">Pietrodn</a></li>
				<li id="n-svn"><a href="https://fisheye.toolserver.org/browse/pietrodn/">SVN repository</a></li>
			</ul>
		</div>
	</div>
	
	<div class='generated-sidebar portlet' id='p-tools'>

		<h3>Tools</h3>
		<div class='pBody'>
			<ul>
				<li id="t-intersectcontribs"><a href="/intersect-contribs">Intersect Contribs</a></li>
				<!--
				<li id="t-ecfinder"><a href="ecfinder.php">Enzyme EC Finder</a></li>
				<li id="t-sectionlinks"><a href="sectionLinks.php">Section Links</a></li>
				-->
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
				<li id="about"><a href="//meta.wikimedia.org/wiki/User:Pietrodn" title="User:Pietrodn">About Pietrodn</a></li>
				<li id="email"><a href="mailto:pietrodn@toolserver.org" title="Mail">e-mail</a></li>
			</ul>
		</div>

</div>
</body></html>