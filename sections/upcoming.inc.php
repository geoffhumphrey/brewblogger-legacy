<?php 
if ($row_upcoming['upcoming'] !="") { 
if ($row_pref['mode'] == "2") { 
	  	mysqli_select_db($brewing,$database_brewing);
		$query_user2 = sprintf("SELECT * FROM users WHERE user_name = '%s'", $row_log['brewBrewerID']);
		$user2 = mysqli_query($brewing,$query_user2) or die(mysqli_error($brewing));
		$row_user2 = mysqli_fetch_assoc($user2);
		$totalRows_user2 = mysqli_num_rows($user2);
		}
?>
<div id="sidebarWrapper">
  <div id="sidebarHeader"><span class="data_icon"><img src="<?php echo $imageSrc; ?>time.png" align="absmiddle"></span><span class="data"><?php if ($row_pref['mode'] == "2") echo $row_user2['realFirstName']."'s "; ?>Upcoming Brews</span></div>
    <div id="sidebarInnerWrapper" >
      <table>
	  <?php  
	  do { 
	    // Get brewer ids
			mysqli_select_db($brewing,$database_brewing);
			$query_brewerID = sprintf("SELECT * FROM recipes WHERE id = '%s'", $row_upcoming['upcomingRecipeID']);
			$brewerID = mysqli_query($brewing,$query_brewerID) or die(mysqli_error($brewing));
			$row_brewerID = mysqli_fetch_assoc($brewerID);
			$totalRows_brewerID = mysqli_num_rows($brewerID);
	  ?>
		  <tr>
    	     <td class="listLeftAlign"><?php if ($row_upcoming['upcomingRecipeID'] != "") { ?><a href="index.php?page=recipeDetail&filter=<?php echo $row_brewerID['brewBrewerID']; ?>&id=<?php echo $row_upcoming['upcomingRecipeID']; ?>"><?php } $str2 = $row_upcoming['upcoming'];  echo Truncate2($str2); if ($row_upcoming['upcomingRecipeID'] != "") echo "</a>"; ?></td>
			 <td class="listRightAlign"><?php if ($row_upcoming['upcomingDate'] != "")  { $date = $row_upcoming['upcomingDate']; $realdate = dateconvert2($date,3); echo $realdate; } else echo "&nbsp;"; ?></td>
		  </tr>
	  <?php } while ($row_upcoming = mysqli_fetch_assoc($upcoming)); ?>
	  </table>
     </div>
</div>
<?php } ?>

