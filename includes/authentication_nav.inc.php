<?php
mysqli_select_db($brewing,$database_brewing);
$query_user = sprintf("SELECT * FROM users WHERE user_name = '%s'", $loginUsername);
$user = mysqli_query($brewing,$query_user) or die(mysqli_error($brewing));
$row_user = mysqli_fetch_assoc($user);
$totalRows_user = mysqli_num_rows($user);

function authenticateUserNav($connection, $username, $password)
{
  // Test the username and password parameters
  if (!isset($username) || !isset($password))
    return false;

  // Formulate the SQL find the user
  $query = "SELECT password FROM users WHERE user_name = '{$username}'
            AND password = '{$password}'";

  // Execute the query
  if (!$result = @ mysqli_query ($connection,$query))
    showerror();

  // Is the returned result exactly one row? If so, then we have found the user
  if (mysqli_num_rows($result) != 1)
    return false;
  else
    return true;
}

// Connects to a session and checks that the user has authenticated and that the remote IP address matches the address used to create the session.

function sessionAuthenticateNav()
{
include ('Connections/config.php');
mysqli_select_db($brewing,$database_brewing);
$query_prefs = "SELECT menuLogin, menuLogout FROM preferences";
$prefs = mysqli_query($brewing,$query_prefs) or die(mysqli_error($brewing));
$row_prefs = mysqli_fetch_assoc($prefs);

  // Check if the user hasn't logged in
  if (!isset($_SESSION["loginUsername"]))  { echo "<li><a href=\"index.php?page=login\">".$row_prefs['menuLogin']."</a></li>"; }
  if (isset($_SESSION["loginUsername"]))   { echo "<li><div class=\"menuBar\"><a class=\"menuButton\" href=\"admin/index.php\" onclick=\"admin/index.php\" onmouseover=\"buttonMouseover(event, 'publicMenu2')\">Admin</a></div></li><li><a href=\"includes/logout.inc.php\">".$row_prefs['menuLogout']."</a></li>"; }
}

?>
