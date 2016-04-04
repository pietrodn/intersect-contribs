<?php

// Possible values for $howSort
define("SORT_ALPHANUM", 0); // sort by namespace, page name
define("SORT_EDITS", 1); // sort by number of edits of first user

/**
* Intersects the contributions of two or more users.
*
* @param  Database $db       Database object ot query (see Database.class.php)
* @param  Array $users    Array of users to intersect
* @param  int $howSort  How to sort the results (see details above)
* @param  int $nsFilter Which namespace to filter (FALSE for all namespaces).
* @return Array           Array of results.
*/
function intersectContribs($db, $users, $howSort, $nsFilter) {

    /* Intersection and ordering are done directly by the database.
    *revision_userindex*: indexed view of the revision table (more performant) */

    $revTable = REVISION_OPTIMIZED;	/* revision or revision_userindex (see config.php) */
    $query = "SELECT page_title, page_namespace"
    . ($howSort == SORT_EDITS ? ", COUNT(page_id) AS eCount" : "") .
    " FROM $revTable, page
    WHERE rev_user_text LIKE \"" . $users[0] . "\""
    . ($nsFilter === FALSE ? "" : " AND page_namespace LIKE $nsFilter ") .
    " AND page_id=rev_page";

    // Intersection clauses for users after the first.
    for($i=1; $i<count($users); $i++) {
        $query .= " AND page_id IN (
        SELECT DISTINCT rev_page FROM $revTable
        WHERE rev_user_text LIKE \"" . $users[$i] . "\""
        . ($nsFilter === FALSE ? "" : " AND page_namespace LIKE $nsFilter ") .
        ") ";
    }
    
    $query .= "GROUP BY page_id
    ORDER BY " . ($howSort == SORT_EDITS ? "eCount DESC, " : "") . "page_namespace, page_title;";

    return $db->query($query);
}
?>
