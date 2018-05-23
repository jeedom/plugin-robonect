<?php
if (!isConnect()) {
	throw new Exception('{{401 - Accès non autorisé}}');
}
$eqLogics = robonect::byType('robonect');
$eqLogic = $eqLogics[0];
$lastpositionCMD = $eqLogic->getCmd(null, 'gpspos');
$battery = $eqLogic->getCmd(null, 'batterie')->execCmd();
$mowerStatus = $eqLogic->getCmd(null, 'status')->execCmd();
if (isset($_GET['start'])) {
	$start =$_GET['start'];
	$end = $_GET['end'];
	$result = history::all($lastpositionCMD->getId(),$start,$end);
} else {
	list($y,$m,$d) = explode('-', date('Y-m-d'));
	$start = mktime(0,0,0,$m,$d,$y);
	$start =date('Y-m-d H:i:s', $start);
	$end = date('Y-m-d H:i:s');
	$result = history::all($lastpositionCMD->getId(),$start,$end);
}
$currentPosition = explode(',',$lastpositionCMD->execCmd());
$apiGoogle = $eqLogic->getConfiguration('apiGoogle','');
$home = $eqLogic->getConfiguration('centerMap','');
if ($home ==''){
	$home = $lastpositionCMD->execCmd();
}
if ($apiGoogle == ''){
	throw new Exception(__('Il vous faut une clé Api Google Maps Widget pour avoir le panel de position', __FILE__));
}
$homePosition = explode(',',$home);
$text = 'Batterie : ' . $battery . '%. Statut : ' . $mowerStatus . '.';
sendVarToJS('APIGOOGLE', $apiGoogle);
?>
<html>
  <head>
    <style>
       #map {
        height: 800px;
        width: 100%;
       }
    </style>
  </head>
  <body>
   <div id="div_object">
        <legend style="height: 25px;">
            <span class="objectName"></span>
			<span style="font-size:0.6em"><?php echo $text ?></span>
            <span class="pull-right" style="font-size:0.8em">
                {{Du}} <input class="form-control input-sm in_datepicker" id='in_startDate' style="display : inline-block; width: 150px;" value='<?php echo $start ?>'/> {{au}}
                <input class="form-control input-sm in_datepicker" id='in_endDate' style="display : inline-block; width: 150px;" value='<?php echo $end ?>'/>
                <a class="btn btn-success btn-sm tooltips" id='bt_validChangeDate'>{{Ok}}</a>
                <a class="btn btn-warning btn-sm tooltips" id='bt_resetDate'>{{Reset}}</a>
            </span>
        </legend>
    </div>
    <div id="map"></div>
    <script>
      function initMap() {
        <?php
		echo "var HOME = {lat: " .$homePosition[0] . ", lng: " .$homePosition[1] . "};
        var map = new google.maps.Map(document.getElementById('map'), {
          zoom: 28,
          center: HOME,
		  mapTypeId: 'satellite'
        });";
		echo "var marker1 =new google.maps.Marker({
				position: {lat: " . $currentPosition[0] . ", lng: " . $currentPosition[1] . "},
				map: map,
				icon : {url :'/plugins/robonect/desktop/php/automower.png'}
				});
				";
		$count=3;
		foreach ($result as $line) {
			if ($count==3){
				$oldcoordinate=explode(',', $line->getValue());
				echo "var marker2 =new google.maps.Marker({
				position: {lat: " . $oldcoordinate[0] . ", lng: " . $oldcoordinate[1] . "},
				map: map,
				icon: {
        path: google.maps.SymbolPath.CIRCLE,
        fillColor: '#00F',
        fillOpacity: 0.6,
        strokeColor: '#00A',
        strokeOpacity: 0.5,
        strokeWeight: 1,
        scale: 4
    }
				});
				";
				$count+=1;
				continue;
			}
			$coordinate = explode(',', $line->getValue());
			if ($coordinate[0] != '') {
			echo "var marker" . $count . " =new google.maps.Marker({
          position: {lat: " . $coordinate[0] . ", lng: " . $coordinate[1] . "},
          map: map,
				icon: {
        path: google.maps.SymbolPath.CIRCLE,
        fillColor: '#00F',
        fillOpacity: 0.6,
        strokeColor: '#00A',
        strokeOpacity: 0.5,
        strokeWeight: 1,
        scale: 3
    }
        });
		";
		echo "var flightPath" . $count . " = new google.maps.Polyline({
          path: [
          {lat: " . $oldcoordinate[0] . ", lng: " . $oldcoordinate[1] . "},
          {lat: " . $coordinate[0] . ", lng: " . $coordinate[1] . "}
          ],
          geodesic: true,
          strokeColor: '#FF0000',
          strokeOpacity: 0.8,
          strokeWeight: 2
        });
		flightPath" . $count . ".setMap(map);
		";
		$oldcoordinate=$coordinate;
		$count+=1;
		}
		}
		?>
      }
	  function updateQueryStringParameter(uri, key, value, key2, value2) {
      var re = new RegExp("([?&])" + key + "=.*?(&|$)", "i");
      var separator = uri.indexOf('?') !== -1 ? "&" : "?";
      if (uri.match(re)) {
        uri = uri.replace(re, '$1' + key + "=" + value + '$2');
      }
      else {
        uri = uri + separator + key + "=" + value;
      }
	  var re = new RegExp("([?&])" + key2 + "=.*?(&|$)", "i");
      var separator = uri.indexOf('?') !== -1 ? "&" : "?";
      if (uri.match(re)) {
        return uri.replace(re, '$1' + key2 + "=" + value2 + '$2');
      }
      else {
        return uri + separator + key2 + "=" + value2;
      }
    }
	  $(".in_datepicker").datetimepicker({step : 30});
	   $('#bt_validChangeDate').on('click', function () {
  window.location.href = updateQueryStringParameter(window.location.href,'start',$("#in_startDate").value(),'end',$("#in_endDate").value());    
});
		 $('#bt_resetDate').on('click', function () {
  window.location = window.location.href.split("&start")[0];    
});
    </script>
	<?php
    echo '<script async defer
    src="https://maps.googleapis.com/maps/api/js?key='.$apiGoogle.'&callback=initMap"></script>'
	?>
  </body>
</html>