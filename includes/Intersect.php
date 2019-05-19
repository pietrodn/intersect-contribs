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
      SELECT page_id, COUNT(rev_id) AS eCount
      FROM page
      JOIN revision ON page_id = rev_page
      JOIN actor ON actor_id = rev_actor
      WHERE actor_name IN ($user_list)
      $namespace_clause

      GROUP BY page_id
      HAVING COUNT(DISTINCT actor_id)=$n_users
    ) AS page2
    ON page2.page_id = page.page_id
    ORDER BY $order_fields
    ;
EOT;

    return $db->query($query);
}
?>
