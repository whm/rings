<!-- ################# Navigation Sidebar header ################ -->

<div class="maintNav">
     <a href="/rings/index.php"><img
       src="/rings-images/icon-home.png"
       alt="Pick a New Ring"
       border="0"></a>
     <br/>
     <h3 class="nav">Pictures</h3>
     <ul class="nav">
<?php
  // Administrator only actions ---------------------------------------
  if ($ring_user_priv == 'ADMINISTRATOR') {
?>
     <a href="picture_load.php"><li class="nav">Load</li></a>
     <a href="picture_reload.php"><li class="nav">Re-Load</li></a>
     <a href="picture_maint.php"><li class="nav">Maintenance</li></a>
<?php
  }
  // -----------------------------------------------------------------
?>
     <a href="picture_update.php"><li class="nav">Update</li></a>
     <a href="picture_sort.php"><li class="nav">Sort</li></a>
     <a href="picture_sort.php?in_new=1"><li class="nav">Sort New</li></a>
     
     </ul>
     <h3 class="nav">People</h3>
     <ul class="nav">
     <a href="people_search.php"><li class="nav">Find</li></a>
<?php
  // Administrator only actions ---------------------------------------
  if ($ring_user_priv == 'ADMINISTRATOR') {
?>
     <a href="people_maint.php"><li class="nav">Maint</li></a>
     </ul>
<?php
  }
  // -----------------------------------------------------------------
?>
     <h3 class="nav">Groups</h3>
     <ul class="nav">
     <a href="groups.php"><li class="nav">List</li></a>
<?php
  // Administrator only actions ---------------------------------------
  if ($ring_user_priv == 'ADMINISTRATOR') {
?>
     <a href="group_maint.php"><li class="nav">Maint</li></a>
     </ul>
<?php
  }
  // -----------------------------------------------------------------
?>
</div>
<div id="maintContent">
<div align="center">
<h2><?php print $thisTitle;?></h2>

<!-- ################# Navigation Sidebar header ################ -->
