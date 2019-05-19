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

    $namespace_clause = ($nsFilter === FALSE ? "" : " WHERE page_namespace = $nsFilter ");
    $order_fields = ($howSort == SORT_EDITS ? "eCount DESC, " : "") . "page_namespace, page_title";

    $user_list = implode(", ", $users);

    $query = <<<EOT
    SELECT page_title, page_namespace, eCount
    FROM page
    JOIN (
      SELECT page_id, COUNT(rev_id) AS eCount
      FROM page
      JOIN revision ON page_id=rev_page
      JOIN (
        SELECT DISTINCT actor_id
        FROM actor
        LEFT JOIN user ON user_id=actor_user
        WHERE IFNULL(user_name, actor_name) IN ($user_list)
      ) act
      ON act.actor_id = revision.rev_actor
      $namespace_clause
      GROUP BY page_id
      HAVING COUNT(DISTINCT act.actor_id)=2
    ) AS page2
    ON page2.page_id = page.page_id
    ORDER BY $order_fields
    ;
EOT;

    return $db->query($query);
}
?>
