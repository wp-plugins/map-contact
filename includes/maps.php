<?php
class MapsAPI
{
    public $mapElement = "";
    private $apiKey = "";
    private $mapCenter = array();
    private $markerImage = array();
    private $markers = array();
    public  $mapOptions = array(
        "zoom" => 4,
        "minZoom" => 0,
        "maxZoom" => 20,
        "zoomControl" => true,
        "zoomControlOptions" => array("style:google.maps.ZoomControlStyle.DEFAULT"),
        "mapTypeId" => "google.maps.MapTypeId.ROADMAP",
        "scrollwheel" => true,
        "panControl" => true,
        "mapTypeControl" => true,
        "scaleControl" => true,
        "streetViewControl" => true,
        "overviewMapControl" => true,
        "rotateControl" => true
    );

    public function __construct($mapElement,$apiKey = "",$markerURL = "",$markerWidth = "",$markerHeight = "")
    {
        $this->mapElement = $mapElement;

        if (!empty($markerURL) && !empty($markerWidth) && !empty($markerHeight))
        {
            $this->markerImage["URL"] = $markerURL;
            $this->markerImage["width"] = $markerWidth;
            $this->markerImage["height"] = $markerHeight;
        }

        if (!empty($apiKey))
        {
            $this->apiKey = $apiKey;
        }
    }

    public function changeMapOptions($option,$value)
    {
        if (isset($this->mapOptions[$option]))
        {
            $this->mapOptions[$option] = $value;
        }
    }

    public function setMapCenter($x,$y)
    {
        if (!empty($x) && !empty($y))
        {
            $this->mapCenter["x"] = $x;
            $this->mapCenter["y"] = $y;
        }
    }

    public function convertLongLat($long,$lat)
    {
        $array = array();
        if (!empty($this->apiKey)) { $api = "key=".$this->apiKey."&"; }

        $ret = $this->curlRequest("https://maps.googleapis.com/maps/api/geocode/json?".@$api."latlng=".trim($long).",".trim($lat));
        if (!is_array(@get_object_vars(json_decode($ret)))) {return $ret; }

        $components = @get_object_vars(@get_object_vars(json_decode($ret))["results"][0])["address_components"];

        foreach ($components as $component)
        {
            $component = get_object_vars($component);

            $array[$component["types"][0]] = trim($component["long_name"]);
        }

        return $array;
    }

    public function convertAddress($address)
    {
        if (!empty($this->apiKey)) { $api = "key=".$this->apiKey."&"; }

        $ret = $this->curlRequest("https://maps.googleapis.com/maps/api/geocode/json?".@$api."address=".urlencode($address));
        if (!is_array(@get_object_vars(json_decode($ret)))) {return $ret; }

        $components = @get_object_vars(@get_object_vars(json_decode($ret))["results"][0])["geometry"];
        $components = @get_object_vars(@get_object_vars($components)["location"]);

        return array("lat" => @$components["lat"], "lng" => @$components["lng"]);
    }

    public function addLocationPin($x,$y,$alt = "",$infoWindow = "",$clickListenerJS = "")
    {
        if (strip_tags($infoWindow)==$infoWindow && !empty($infoWindow)) {
            $infoWindow = "<p>".$infoWindow."</p>";
        }

        $this->markers[] = array(
            "x" => $x,
            "y" => $y,
            "alt" => $alt,
            "infoWindow" => $infoWindow,
            "onclick" => $clickListenerJS
        );
    }

    public function generateMap()
    {
        $optionsCode = "";

        if (!empty($this->apiKey)) { $api = "key=".$this->apiKey."&"; }

        $map = '<script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?'.@$api.'sensor=false"></script>';
        $map .= "\n".'<style>#'.$this->mapElement.' img{ max-width: inherit; } .'.$this->mapElement.' img{ max-width: inherit; }</style>';

        foreach ($this->mapOptions as $key => $value)
        {
            if (is_array($value)) {
                $optionsCode .= $key.": { "."".implode("\",\"",$value)."},";
            }
            elseif (is_bool($value)) {
                $bool = ($value) ? 'true' : 'false';
                $optionsCode .= $key.": "."".$bool.",";
            }
            else {
                $optionsCode .= $key.": "."$value,";
            }
            $optionsCode .= " ";
        }

        $id = 0;
        $markersCode = "";
        $markerImgCode = "";
        if (!empty($this->markerImage["URL"]) && !empty($this->markerImage["width"]) && !empty($this->markerImage["height"]))
        {
            $markersCode .= 'var image = new google.maps.MarkerImage(\''.$this->markerImage["URL"].'\', null, null, null, new google.maps.Size('.$this->markerImage["width"].','.$this->markerImage["height"].'));';
            $markerImgCode = 'icon:image,';
        }

        if (!empty($this->markers))
        {
            foreach ($this->markers as $marker)
            {
                $id++;

                $markersCode .= 'var marker_'.$id.' = new google.maps.Marker({ position: new google.maps.LatLng('.$marker["x"].','.$marker["y"].'), '.$markerImgCode.' map: map, title: \''.$marker["alt"].'\' });';

                if (!empty($marker["onclick"])) {
                    $markersCode .= 'google.maps.event.addListener(marker_'.$id.', \'click\', function() { '.$marker["onclick"].' });';
                }
                else{
                    $markersCode .= 'var infowindow_'.$id.' = new google.maps.InfoWindow({ content:"'."<div style='overflow-y:hidden; padding-left:15px; padding-top:6px; padding-right:15px;'>".$marker["infoWindow"]."</div>".'" });
                    google.maps.event.addListener(marker_'.$id.', \'click\', function() { infowindow_'.$id.'.open(map,marker_'.$id.'); }); ';
                }
            }
        }
        else
        {
            $markersCode = "";
        }

        if (empty($this->mapCenter["x"]) || empty($this->mapCenter["y"]))
        {
            if (!empty($this->markers[0]["x"]) && !empty($this->markers[0]["y"]))
            {
                $x = $this->markers[0]["x"];
                $y = $this->markers[0]["y"];
            }
            else
            {
                $x = "54.868522";
                $y = "-4.811122";
            }
        }
        else{
            $x = $this->mapCenter["x"];
            $y = $this->mapCenter["y"];
        }

        $map .= '
        <script>
	        function initialise() {
		        var mapOptions = {
		            center: new google.maps.LatLng('.$x.','.$y.'),
		            '.rtrim($optionsCode, ",").'
		        }
		        var map = new google.maps.Map(document.getElementById(\''.$this->mapElement.'\'), mapOptions);

                '.$markersCode.'
            }

            google.maps.event.addDomListener(window, \'load\', initialise);
        </script>
        ';

        $map = preg_replace('/\t+/', '', $map);
        $map = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $map);
        $map = str_replace("    ","",$map);
        $map = trim($map);

        return $map;
    }

    public function curlRequest($URL)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $URL);
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/36.0.1985.125 Safari/537.36");
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $output = curl_exec($ch);
        curl_close($ch);

        if (!is_array($output)) { $ret = @get_object_vars(json_decode($output));}

        if (!empty($ret["error_message"])) {
            return $ret["error_message"];
        }

        return $output;
    }
}
?>