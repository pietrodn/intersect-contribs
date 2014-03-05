<?php

// Imports configuration
require_once("config.php");

/* 	$wikidb: wiki database name (e.g. "enwiki")
	Returns: host name (e.g. "en.wikipedia.org")
*/
function getWikiHost($wikidb)
{
    $db = new mysqli(META_HOST, DB_USER, DB_PASSWORD, META_DB);
    if ($db == FALSE) {
        return false;
    }
    $wikidbSQL = $db->real_escape_string($wikidb);
    $query = "SELECT url
    	FROM wiki
    	WHERE dbname LIKE \"$wikidbSQL\";";
    $res = $db->query($query);
    $row = $res->fetch_assoc();
    $db->close();
    
    return preg_replace('#https?://#', '', $row['url']);
}

/* 	Gets the namespaces via MediaWiki API.
	$wikiHost: wiki domain (e.g. "en.wikipedia.org")
	Returns: associative array of namespaces (id => name).
*/
function getNamespacesAPI($wikiHost)
{
	$conn = curl_init('https://' . $wikiHost .
		'/w/api.php?action=query&meta=siteinfo&siprop=namespaces&format=php');
	curl_setopt ($conn, CURLOPT_USERAGENT, "BimBot/1.0");
	curl_setopt($conn, CURLOPT_RETURNTRANSFER, True);
	$ser = curl_exec($conn);
	curl_close($conn);
	
	$unser = unserialize($ser);
	$namespaces = $unser['query']['namespaces'];
	
	$ns = array();
	foreach($namespaces as $i => $val) {
		$ns[$i] = $val['*'];
	}
		
	return $ns;

}

/*	Prints a list of WMF wiki as an <option> list (for the dropdown <select> project chooser).
	$defaultPj: database name of the project initially selected (default: none).
*/
function projectChooser($defaultPj = NULL)
{   
    $db = new mysqli(META_HOST, DB_USER, DB_PASSWORD, META_DB);
    if ($db == FALSE)
        die ("Can't log into MySQL.");
    
    $query = "SELECT dbname, url
    	FROM wiki
    	ORDER BY url;";
    	
    $res = $db->query($query);
    // Dummy option
    if(!isset($defaultPj))
        print "<option value=\"\" disabled selected>select a wiki</option>";
    else
        print "<option disabled value=\"\">select a wiki</option>";
    
    while($row = $res->fetch_assoc())
    {
        $dbname = $row['dbname'];
        if(empty($row['url']))
        	continue;
        
        $visiblename = preg_replace('#https?://#', '', $row['url']);
        // If the project was selected in the previous query, remember it.
        $selected = ($defaultPj == $dbname ? ' selected' : '');
        print "<option value=\"$dbname\"$selected >$visiblename</option>\n";
    }
    $db->close();
}

// Prints an error message in red.
function printError($err)
{
    $err = htmlspecialchars($err, ENT_NOQUOTES, 'UTF-8');
    echo '<div class="alert alert-danger">';
    echo $err;
    echo '</div>';
}
?>