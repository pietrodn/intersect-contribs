<?php
/**
* Prints a list of WMF wikis as an <option> list (for the dropdown <select> project chooser).
* @param  String $defaultPj database name of the project initially selected (default: none).
*/
function projectChooser($defaultPj = NULL)
{
    $res = getWikiProjects();

    // Dummy option
    if(!isset($defaultPj)) {
        print "<option value=\"\" disabled selected>select a wiki</option>";
    } else {
        print "<option disabled value=\"\">select a wiki</option>";
    }

    foreach($res as $row)
    {
        $dbname = $row['dbname'];
        if(empty($row['url'])) {
            continue;
        }

        $visiblename = preg_replace('#https?://#', '', $row['url']);
        // If the project was selected in the previous query, remember it.
        $selected = ($defaultPj == $dbname ? ' selected' : '');
        print "<option value=\"$dbname\"$selected >$visiblename</option>\n";
    }
}

/**
* Outputs a list of pages. The list of pages must be provided as an array of associative arrays:
* [
* 	"page_title" => "Italy",
* 	"page_namespace" => 0,
* 	"eCount" => 3,
* ]
* @param  [type] $pages    Array of pages.
* @param  [type] $wikihost The hostname of the wiki (e.g. "it.wikipedia.org")
* @param  [type] $isSorted  Whether the results are sorted by edits.
* @param  [type] $uName_1  The user whose edit count is used for sorting.
*/
function printPageList($pages, $wikihost, $isSorted, $uName_1 = NULL)
{
    /* Gets namespaces */
    $namespaces = getNamespaces($wikihost);

    if(count($pages) !== 0) {
        // Printing output.
        echo '<div class="alert alert-success">';
        echo count($pages) . ' results found.';
        echo '</div>';
        print "<ol id=\"PageList\">";
        foreach($pages as $i) {
            // Prints an entry for each page

            $curPageName = $i['page_title'];
            $curPageNamespace = $i['page_namespace'];

            // Number of edits, if needed.
            if($isSorted) {
                $edits = $i['eCount'];
            }

            $curPageNamespaceName = $namespaces[$curPageNamespace];
            // If not ns0, adds namespace prefix.
            $pageTitle = ($curPageNamespaceName
            ? $curPageNamespaceName . ":" . $curPageName
            : $curPageName);

            // Number of times user 1 (or 2, switched before) edited this page
            $editMsg = ($isSorted
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
}

/**
* Prints an error message in red.
* @param  String $err The error message
*/
function printError($err)
{
    $err = htmlspecialchars($err, ENT_NOQUOTES, 'UTF-8');
    echo '<div class="alert alert-danger">';
    echo $err;
    echo '</div>';
}
?>
