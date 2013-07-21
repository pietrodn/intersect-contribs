<?php
// WMF Labs and home computer have different settings. I need to switch between them using the $DEBUG variable.
$DEBUG=(strpos($_SERVER['SERVER_NAME'], 'tools.wmflabs.org') === FALSE ? true : false);
if($DEBUG)
        require_once("../../external_includes/mysql_pw.inc");
else
        require_once("../external_includes/mysql_pw.inc");

/* Imports $wikiProjects array  */
require_once("wikiProjects.php");
            
function ts_print_scriptname()
{
    $filePath = $_SERVER['SCRIPT_NAME'];
    $break = explode('/', $filePath);
    $fileName = $break[count($break) - 1];
    echo $fileName;
}

// This gets the wiki host name (e.g. it.wikipedia.org), given the db name (e.g. itwiki).
function getWikiHost($wikidb)
{
    global $DEBUG, $db_user, $db_password;
    $db_host = 'tools-db';
    if($DEBUG)
    {
        $db_host = 'localhost';
    }
    $db = mysql_connect($db_host, $db_user, $db_password, TRUE);
    if ($db == FALSE)
        die ("Can't log into MySQL.");
    mysql_select_db('p50380g50557__wiki', $db)
        or die("Can't select the database.");
    $wikidbSQL = mysql_real_escape_string($wikidb);
    $query = "SELECT domain FROM wiki WHERE dbname LIKE \"$wikidbSQL\";";
    $res = mysql_query($query, $db);
    $row = mysql_fetch_assoc($res);
    mysql_close($db);
    return $row['domain'];
}

// This gets namespace names.
function getNamespacesForDb($wikiDbName)
{
    global $DEBUG, $db_user, $db_password;
    $db_host = 'tools-db';
    if($DEBUG)
    {
        $db_host = 'localhost';
    }
    $db = mysql_connect($db_host, $db_user, $db_password, TRUE);
    if ($db == FALSE)
        die ("Can't log into MySQL.");
    mysql_select_db('p50380g50557__wiki', $db)
        or die("Can't select the database.");
    $wikiDbName = mysql_real_escape_string($wikiDbName) . '_p';
    // Select all namespaces in that wiki.
    $query = "SELECT ns_id, ns_name FROM namespace WHERE dbname LIKE \"$wikiDbName\";";
    $res = mysql_query($query, $db);
    $namespaces = array(); // Associative array: ns_id => ns_name
    while ($riga = mysql_fetch_assoc($res)) {
        $namespaces[$riga["ns_id"]] = $riga["ns_name"];
    }
    mysql_close($db);
    return $namespaces;
}

// Prints <select> entries in order to choose a project and generates a list of all wikis to check user input.
function ts_projectchooser($defaultPj = NULL, &$allWikis)
{
	global $wikiProjects;
    // Dummy option
    if(!isset($defaultPj))
        print "<option value=\"\" disabled selected=\"selected\">select a wiki</option>";
    else
        print "<option disabled value=\"\">select a wiki</option>";
    
    foreach($wikiProjects as $dbname)
    {
        $selected = ""; // If it was selected in the previous query, remember it.
        if($defaultPj == $dbname) $selected = " selected=\"selected\"";
        print "<option value=\"$dbname\"$selected>$dbname</option>\n";
    }
}

function printError($err)
{
    $err = htmlspecialchars($err, ENT_NOQUOTES);
    echo "<p class=\"error\">$err</p>";
}
?>