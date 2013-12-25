<?php

// Please don't edit this file! Copy it as config.php and then edit it.

// PRODUCTION configuration for WMF Tool Labs.

// Imports DB_USER, DB_PASSWORD credentials from external file.
require_once("../external_includes/mysql_pw.inc");

// Host and DB for meta_p.wiki table.
define('META_HOST', 'enwiki.labsdb');
define('META_DB', 'meta_p');

// If OVERRIDE_DB is true, intersect-contribs will use the following wiki host and DB.
define('OVERRIDE_DB', false);
#define('DEFAULT_HOST', 'localhost');
#define('DEFAULT_DB', 'itwikibooks_p');

// Revision table to use for queries.
define('REVISION_OPTIMIZED', 'revision_userindex');

?>