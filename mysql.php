<?php
  $mysql_host = "db-master.macallister.grass-valley.ca.us";
  $mysql_slave_host = "db-master.macallister.grass-valley.ca.us";
  $mysql_user = "rings";
  $mysql_pass = "anyoneandeveryone";
  $mysql_db   = "rings";

  $mysql_dsn = "mysql://$mysql_user:$mysql_pass@"                        
                ."$pride_host/$mysql_db";
  $mysql_read_dsn = "mysql://$mysql_user:$mysql_pass@"                   
                ."$mysql_slave_host/$mysql_db";                    

  $ring_doc_root = '/mac/www/rings';
?>