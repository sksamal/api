<?php
date_default_timezone_set('America/Chicago');
$date1=date_create("03/15/2019");
$date2=date_create("01/01/".date('Y'));
//$date2=date_create("01/01/2019");
//$date1=date_create("2019-03-15");
//$date2=date_create("2019-01-01");
$diff=date_diff($date1,$date2);
print $date1->format('m/d/y')." ".$date2->format('m/d/Y');
print $diff->format("%a")+1;
?>
