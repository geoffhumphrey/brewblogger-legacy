<?php   
   function showerror()
   {
      die("Error " . mysqli_errno() . " : " . mysqli_error($brewing));
   }

   function mysqlclean($array, $index, $maxlength, $connection)
   {
     if (isset($array["{$index}"]))
     {
        $input = substr($array["{$index}"], 0, $maxlength);
        $input = mysqli_real_escape_string($input, $connection);
        return ($input);
     }
     return NULL;
   }

   function shellclean($array, $index, $maxlength)
   {
     if (isset($array["{$index}"]))
     {
       $input = substr($array["{$index}"], 0, $maxlength);
       $input = EscapeShellCmd($input);
       return ($input);
     }
     return NULL;
   }
?>
