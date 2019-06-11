<?php

// Possible values for $howSort
define("SORT_ALPHANUM", 0); // sort by namespace, page name
define("SORT_EDITS", 1); // sort by number of edits of all users

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
    // Sanitizing
    $users = array_map(function ($u) use ($db) { return '"' . $db->escape($u) . '"'; }, $users);

    $namespace_clause = ($nsFilter === FALSE ? "" : "AND page_namespace = $nsFilter");
    $order_fields = ($howSort == SORT_EDITS ? "eCount DESC, " : "") . "page_namespace, page_title";

    $user_list = implode(", ", $users);
    $n_users = count($users);

    $query = <<<EOT
    SELECT page_title, page_namespace, eCount
    FROM page
    JOIN (
      SELECT rev_page, COUNT(rev_id) AS eCount
      FROM revision_userindex
      JOIN actor_revision ON actor_id = rev_actor
      WHERE actor_name IN ($user_list)
      $namespace_clause

      GROUP BY rev_page
      HAVING COUNT(DISTINCT actor_id)=$n_users
    ) AS page2
    ON rev_page = page_id
    ORDER BY $order_fields
    ;
EOT;

    return $db->query($query);
}
?>
