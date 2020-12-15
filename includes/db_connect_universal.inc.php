<?php
// Get server's PHP version
$phpVersion = phpversion();
//echo $phpVersion;

$currentPage = "http://".$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'];
if (!empty($_SERVER["QUERY_STRING"])) $currentPage .= "?".$_SERVER['QUERY_STRING'];
if (!empty($_SESSION["loginUsername"])) $loginUsername = $_SESSION["loginUsername"];
$loginUsername = $_SESSION["loginUsername"];

// Universal DB Connections
mysqli_select_db($brewing,$database_brewing);


// -----------------------------------------------------------------------------------------------
// Name

$query_name = "SELECT * FROM brewer";
$name = mysqli_query($brewing,$query_name) or die(mysqli_error($brewing));
$row_name = mysqli_fetch_assoc($name);
$totalRows_name = mysqli_num_rows($name);

// -----------------------------------------------------------------------------------------------
// Preferences

$query_pref = "SELECT * FROM preferences";
$pref = mysqli_query($brewing,$query_pref) or die(mysqli_error($brewing));
$row_pref = mysqli_fetch_assoc($pref);
$totalRows_pref = mysqli_num_rows($pref);

// -----------------------------------------------------------------------------------------------
// Theme

$query_theme = "SELECT * FROM brewingcss";
$theme = mysqli_query($brewing,$query_theme) or die(mysqli_error($brewing));
$row_theme = mysqli_fetch_assoc($theme);
$totalRows_theme = mysqli_num_rows($theme);

// -----------------------------------------------------------------------------------------------
// Alternating Color Choice

$query_colorChoose = sprintf("SELECT * FROM brewingcss WHERE theme='%s'", $row_pref['theme']);
$colorChoose = mysqli_query($brewing,$query_colorChoose) or die(mysqli_error($brewing));
$row_colorChoose = mysqli_fetch_assoc($colorChoose);
$totalRows_colorChoose = mysqli_num_rows($colorChoose);

// -----------------------------------------------------------------------------------------------
// User Info
if (isset($_SESSION["loginUsername"])) {
$query_user = sprintf("SELECT * FROM users WHERE user_name = '%s'", $_SESSION["loginUsername"]);
$user = mysqli_query($brewing,$query_user) or die(mysqli_error($brewing));
$row_user = mysqli_fetch_assoc($user);
$totalRows_user = mysqli_num_rows($user);
}

// -----------------------------------------------------------------------------------------------
// User Info

$query_user5 = sprintf("SELECT * FROM users WHERE user_name = '%s'", $filter);
$user5 = mysqli_query($brewing,$query_user5) or die(mysqli_error($brewing));
$row_user5 = mysqli_fetch_assoc($user5);
$totalRows_user5 = mysqli_num_rows($user5);

// -----------------------------------------------------------------------------------------------
// Generic Recipe Connection

$query_recipes = "SELECT * FROM recipes";
//if (($page == "admin") && ($row_pref['mode'] == "2")) $query_recipes .= " WHERE brewBrewerID = '$loginUsername'";
$query_recipes .= " ORDER BY brewName ASC";
$recipes = mysqli_query($brewing,$query_recipes) or die(mysqli_error($brewing));
$row_recipes = mysqli_fetch_assoc($recipes);
$totalRows_recipes = mysqli_num_rows($recipes);

// -----------------------------------------------------------------------------------------------
// Generic BrewBlog Connection

$query_brewBlogs = "SELECT * FROM brewing";
if (($page == "admin") && ($row_pref['mode'] == "2") && ($row_user['userLevel'] == "2")) $query_brewBlogs .= " WHERE brewBrewerID = '$loginUsername'";
$query_brewBlogs .= " ORDER BY brewName ASC";
$brewBlogs = mysqli_query($brewing,$query_brewBlogs) or die(mysqli_error($brewing));
$row_brewBlogs = mysqli_fetch_assoc($brewBlogs);
$totalRows_brewBlogs = mysqli_num_rows($brewBlogs);

// Generic Awards Connection

$query_awardGen = "SELECT * FROM awards";
if (($page == "admin") && ($row_pref['mode'] == "2")) $query_awardGen .= " WHERE brewBrewerID = '$loginUsername'";
$query_awardGen .= " ORDER BY awardBrewName ASC";
$awardGen = mysqli_query($brewing,$query_awardGen) or die(mysqli_error($brewing));
$row_awardGen = mysqli_fetch_assoc($awardGen);
$totalRows_awardGen = mysqli_num_rows($awardGen);

// -----------------------------------------------------------------------------------------------
// News

$query_newsGen = "SELECT * FROM news";
if (($page == "news") || ($page == $row_pref['home'])) $query_newsGen .= " WHERE newsPrivate='Y' ORDER BY newsDate DESC";
if ($page == "admin") $query_newsGen .= " WHERE newsPrivate='N' ORDER BY newsDate DESC";
$newsGen = mysqli_query($brewing,$query_newsGen) or die(mysqli_error($brewing));
$row_newsGen = mysqli_fetch_assoc($newsGen);
$totalRows_newsGen = mysqli_num_rows($newsGen);


?>
