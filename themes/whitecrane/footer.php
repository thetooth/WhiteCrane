<?php
$dew->acp ();
?>
</div>
<div id="footer">
<div style="float: left">&copy;<?php
echo date ( "Y", time () ) . " " . $dew->_config ['site_title'] . ".";
$time_end = microtime ( true );
$time = $time_end - $time_start;
echo "Page processed in $time seconds " . (memory_get_usage () / 1000) . "kb\n";
?>
  </div>
<div style="float: right; text-align: right">Powered by <a
	href="http://dev.ameoto.com/whitecrane"><strong>WhiteCrane</strong></a></div>
</div>
<!-- Thanks for not stealing ^_^ -->
</body>
</html>