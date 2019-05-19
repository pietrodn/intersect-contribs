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
  $users = array_map(array($db, "escape"), $users);

  $editCountField = ($howSort == SORT_EDITS ? ", COUNT(page_id) AS eCount" : "");
  $namespaceClause = ($nsFilter === FALSE ? "" : " AND page_namespace = $nsFilter ");
  $intersectionClause = "";

  for($i=1; $i<count($users); $i++) {
      $intersectionClause .=
<<<EOL
AND page_id IN (
  SELECT rev_page FROM revision_userindex
  JOIN actor ON actor_id = rev_actor
  WHERE actor_name = "$users[$i]"
)
EOL;
  }

  $orderbyFields = ($howSort == SORT_EDITS ? "eCount DESC, page_namespace, page_title" : "page_namespace, page_title");

  $query =
<<<EOL
SELECT page_title, page_namespace $editCountField
FROM page
JOIN revision_userindex ON page_id = rev_page
JOIN actor ON actor_id = rev_actor
WHERE actor_name = "$users[0]"
$namespaceClause
$intersectionClause

GROUP BY page_id
ORDER BY $orderbyFields
;
EOL;

    return $db->query($query);
}
?>
