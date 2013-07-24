<?php

// Username & password
require_once("../external_includes/mysql_pw.inc");

/*	Imports $wikiProjects array.
	Ugly hack: wiki list taken from /etc/hosts  */
require_once("wikiProjects.php");

// Gets the wiki host name (e.g. it.wikipedia.org), given the db name (e.g. itwiki).
function getWikiHost($wikidb)
{
    $db = new mysqli(TOOLSDB_HOST, DB_USER, DB_PASSWORD, PERSONAL_DB);
    if ($db == FALSE)
        die ("MySQL error.");
    $wikidbSQL = $db->real_escape_string($wikidb) . '_p';
    $query = "SELECT domain FROM wiki WHERE dbname LIKE \"$wikidbSQL\";";
    $res = $db->query($query);
    $row = $res->fetch_assoc();
    $db->close();
    
    return $row['domain'];
}

// Fetches namespace names for a specific project.
function getNamespacesForDb($wikiDbName)
{   
    $db = new mysqli(TOOLSDB_HOST, DB_USER, DB_PASSWORD, PERSONAL_DB);
    if ($db == FALSE)
        die ("MySQL error.");
    $wikiDbName = $db->real_escape_string($wikiDbName) . '_p';
    
    // Select all namespaces in that wiki.
    $query = "SELECT ns_id, ns_name FROM namespace WHERE dbname LIKE \"$wikiDbName\";";
    $res = $db->query($query);
    $namespaces = array(); // Associative array: ns_id => ns_name
    while ($riga = $res->fetch_assoc()) {
        $namespaces[$riga["ns_id"]] = $riga["ns_name"];
    }
    $db->close();
    
    return $namespaces;
}

// Prints <option> entries in order to choose a project.
function projectChooser($selectedPj = NULL)
{
	global $wikiProjects;
	
    // Dummy option
    if(!isset($selectedPj))
        print "<option value=\"\" disabled selected>select a wiki</option>";
    else
        print "<option disabled value=\"\">select a wiki</option>";
    
    foreach($wikiProjects as $dbname) {
    	// If the project was selected in the previous query, remember it.
        $selected = ($selectedPj == $dbname ? ' selected' : '');
        print "<option value=\"$dbname\"$selected>$dbname</option>\n";
    }
}

// Prints an error message in red.
function printError($err)
{
    $err = htmlspecialchars($err, ENT_NOQUOTES | ENT_HTML5, 'UTF-8');
    echo "<p class=\"error\">$err</p>";
}
?>