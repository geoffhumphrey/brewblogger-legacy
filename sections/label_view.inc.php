<?php require_once('../Connections/config.php'); ?>
<?php
mysqli_select_db($brewing,$database_brewing);
$query_theme = "SELECT * FROM brewingcss";
$theme = mysqli_query($brewing,$query_theme) or die(mysqli_error($brewing));
$row_theme = mysqli_fetch_assoc($theme);
$totalRows_theme = mysqli_num_rows($theme);
?>
<html> 
<head> 
 <title>BrewBlogger Label Image</title> 
 <link href="../css/<?php echo $row_pref['theme']; ?>" rel="stylesheet" type="text/css">
 <script language="javascript">
   var arrTemp=self.location.href.split("?"); 
   var picUrl = (arrTemp.length>0)?arrTemp[1]:""; 
   var NS = (navigator.appName=="Netscape")?true:false; 

     function FitPic() { 
       iWidth = (NS)?window.innerWidth:document.body.clientWidth; 
       iHeight = (NS)?window.innerHeight:document.body.clientHeight; 
       iWidth = document.images[0].width - iWidth; 
       iHeight = document.images[0].height - iHeight; 
       window.resizeBy(iWidth+30, iHeight+60); 
       self.focus(); 
     }; 

self.focus() 
self.moveTo(25,25) 

</script> 
</head>
<body onload='FitPic();'> 
<div id="labelImageLarge">
<script language='javascript'>document.write( "<img src='" + picUrl + "' border=0 class=\"bdr1_black\">" ); </script>
</div>
<div id="footerInclude">
<a class="text_9" href="javascript:self.close()">Close</a>
</div>
</body> 
</html>