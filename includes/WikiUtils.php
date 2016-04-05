<?php

require_once "Database.class.php";

/**
* Gets the domain name of a wiki from its database name.
* @param  String $wikidb Wiki DB name (e.g. "itwiki")
* @return String         Wiki domain (e.g. "it.wikipedia.org")
*/
function getWikiDomainFromDBName($wikidb)
{
    $db = Database::database(META_HOST, META_DB);
    $wikidbSQL = $db->escape($wikidb);

    $query = "SELECT url
    FROM wiki
    WHERE dbname LIKE \"$wikidbSQL\";";

    $res = $db->query($query);

    return preg_replace('#https?://#', '', $res[0]['url']);
}

/**
* Performs an API call to MediaWiki
* @param  String   $host   The host to which send the API call (e.g. "it.wikipedia.org")
* @param  Array    $data   Associative array of parameters and values.
* @return Array            Associative array with results.
*/
function mwApiCall($host, $data) {
    $conn = curl_init('https://' . $host . '/w/api.php?' . http_build_query($data));
    curl_setopt ($conn, CURLOPT_USERAGENT, "BimBot/1.0");
    curl_setopt($conn, CURLOPT_RETURNTRANSFER, True);
    $ser = curl_exec($conn);
    curl_close($conn);

    return json_decode($ser, True);
}

/**
* Gets the namespaces of a wiki via MediaWiki API.
* @param  String $host Wiki host (e.g. "it.wikipedia.org")
* @return Array       Namespaces (number => name)
*/
function getNamespaces($host)
{
    $unser = mwApiCall($host, array(
        "action" => "query",
        "meta" => "siteinfo",
        "siprop" => "namespaces",
        "format" => "json",
    ));
    $namespaces = $unser['query']['namespaces'];
    return array_column($namespaces, '*', 'id');
}

/**
* Gets a list of wiki projects.
* @return Array Arrays of arrays ["dbname" => "itwiki", "url" => "https://it.wikipedia.org/", ...]
*/
function getWikiProjects()
{
    $db = Database::database(META_HOST, META_DB);

    $query = "SELECT dbname, url
    FROM wiki
    ORDER BY url;";

    return $db->query($query);
}
?>
