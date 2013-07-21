<?php
$admitted_files = array(
	'index.php',
	'pietrodnUtils.php',
	'showCode.php',
	'wikiProjects.php',
);

$path = $_GET['file'];
if(in_array($path, $admitted_files)) {
	highlight_file($path);
} else {
	echo "Access denied!";
}

?>