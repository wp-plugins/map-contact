<?php
/*
	Plugin Name: Map Contact
	Description: Instantly create stylish and professional Contact Us Page with Map for any WordPress Theme!
	Author: Ryan Smith
    Plugin URI: http://xantoo.com/
    Author URI: http://xantoo.com/
	Version: 2 BETA
 */

include(plugin_dir_path( __FILE__ )."includes/maps.php");

function shortcodeManagment($attributes){
    global $wpdb;

    if (isset($_POST["send_email"]) && isset($_POST["eto"]) && isset($_POST["emessage"]))
    {

        $to      = $_POST["eto"];
        $subject = $_POST["esubject"]." - Map Contact";
        $message = $_POST["emessage"];
        $headers = 'From: '.$_POST["email"] . "\r\n";

        mail($to, $subject, $message, $headers);
    }

    $code = "<style>a{} #map-contact h2 {margin-bottom:5px;} #map-contact h2,#map-contact div{font-family:Arial,Helvetica,sans-serif;}</style>";
    if ($attributes["map"]=="true" || !isset($attributes["map"]))
    {
        $options = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."map_settings");
        $map = new MapsAPI("map-canvas",get_object_vars($options[0])["value"]);

        foreach ($options as $option) {
            $option = get_object_vars($option);
            if (@unserialize($option["value"])!==false) { $option["value"] = unserialize($option["value"]); }
            $map->changeMapOptions($option["setting"],$option["value"]);
        }

        $addresses = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."map_addresses");

        $pins = array();
        foreach ($addresses as $address) {
            $address = get_object_vars($address);
            $loc = $map->convertAddress($address["address"]);

            if (!empty($loc["lat"]) && !empty($loc["lng"]))
            {
                foreach ($pins as $pin)
                {
                    if ($pin["lat"]==$loc["lat"] && $pin["lng"]==$loc["lng"])
                    {
                        if (rand(1,2)==1) { $loc["lat"] = $loc["lat"] + rand(1,100)/500; } else { $loc["lat"] = $loc["lat"] - rand(1,100)/500; }

                        if (rand(1,2)==1) { $loc["lng"] = $loc["lng"] - rand(1,100)/500; } else { $loc["lng"] = $loc["lng"] + rand(1,100)/500; }
                    }
                }

                $pins[] = array("lat" => $loc["lat"],"lng" => $loc["lng"]);

                $address["infoWindow"] = trim(preg_replace('/\s\s+/', ' ', $address["infoWindow"]));
                $map->addLocationPin($loc["lat"],$loc["lng"],$address["name"],"<div style='max-width:250px; padding-bottom:10px;'>".preg_replace("/<img[^>]+\>/i", "", $address["infoWindow"])."</div>");
            }
        }

        if (isset($attributes["width"])) { $width = $attributes["width"]; } else { $width = "500px"; }
        if (isset($attributes["height"])) { $height = $attributes["height"]; } else { $height = "500px"; }

        $code .= $map->generateMap();
        $code .= "<div id='map-canvas' style='width:".$width."; height:".$height.";'></div>";
    }

    if ($attributes["addressbook"]=="true" || !isset($attributes["addressbook"]))
    {
        $code .= '<link rel="stylesheet" type="text/css" href="'.plugins_url( 'map-contact/includes/stylesheet.css' , dirname(__FILE__) ) .'" />';
        $code .= "<div id='addressBook' style='padding-top:10px;'>";
        $addresses = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."map_addresses");

        foreach ($addresses as $address) {
            $address = get_object_vars($address);
            if (!empty($address["email"])){
                $contact = '<button type="button" onclick="document.getElementById(\''.$address["id"].'_lightbox\').style.display=\'inline\';" >Contact by email</button>';

                $form = "<form method='POST'>
                            <table style='padding-top:10px;'>
                            <input type='hidden' name='eto' value='".$address["email"]."'>
                            <tr><td style='width:200px; border:0;'>Name:</td><td style='width:300px; border:0;'><input style='width:100%;' type='text' name='ename'></td></tr>
                            <tr><td style='width:200px; border:0;'>Subject:</td><td style='width:300px; border:0;'><input style='width:100%;' type='text' name='esubject'></td></tr>
                            <tr><td style='width:200px; border:0;'>Email:</td><td style='width:300px; border:0;'><input style='width:100%;' type='text' name='email'></td></tr>
                            <tr><td style='width:200px; border:0;'>Message</td><td style='max-width:300px; border:0;'><textarea style='max-width:100%; width:100%; max-height:106px;height: 106px;' name='emessage'></textarea></td></tr>
                            <tr><td style='width:200px; border:0;'></td><td style='width:300px; border:0;'><input style='float:right;' type='submit' name='send_email' value='Send Email'></td></tr>
                            </table>";
                $code .= '<div id="'.$address["id"].'_lightbox" style="z-index:99999; display:none;"class="lightbox">
                                <div id="'.$address["id"].'_contact" style="position: relative; width:530px; background:#fff; top: 25%; padding-right:10px; padding-left:15px; padding-bottom:10px;padding-top:10px; border-radius:5px; margin:0 auto;">
                                    <img style="position:relative; float:right; padding-right:6px; padding-top:6px; cursor: pointer;" onclick="document.getElementById(\''.$address["id"].'_lightbox\').style.display=\'none\';" src="'.plugins_url( 'map-contact/images/close.png', dirname(__FILE__)).'">
                                    <h2>'.$address["name"].'</h2>
                                    '.$form.'
                                </div>
                             </div>';
            }
            $code .= "<div style='position:relative; float: left; margin-bottom:25px; margin-right:10px; min-height:100px; min-width:200px; max-width:250px; padding-left:20px;' id='".$count."_person' class='person_entry'>".$img.$address["infoWindow"].$contact."</div>";
        }
        $code .= "</div>";
    }

    return "<div id='map-contact'>".$code."</div>";
}

function addSettingsPages() {
    add_menu_page("Map Settings", "Map Settings", 'manage_options', 'map_settings', "settingsPage");
    add_submenu_page("map_settings",'Address Book','Address Book','manage_options','map_addressess',"addressBook");
    add_submenu_page("map_settings",'Add New Address','Add New Contact','manage_options','add_new_address',"newAddress");
}

function settingsPage()
{
    include(plugin_dir_path( __FILE__ )."admin/map_settings.php");
}

function addressBook()
{
    include(plugin_dir_path( __FILE__ )."admin/map_addresses.php");
}

function newAddress()
{
    include(plugin_dir_path( __FILE__ )."admin/add_new_address.php");
}

function pluginActivated() {
    global $wpdb;
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

    if (!$wpdb->get_var( "SELECT * FROM ".$wpdb->prefix."map_settings" ))
    {
        $query = dbDelta( "CREATE TABLE ".$wpdb->prefix."map_settings (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `setting` text NOT NULL,
        `value` text NOT NULL,
         PRIMARY KEY (`id`))"
        );

        if ($wpdb->get_var("SHOW TABLES LIKE '".$wpdb->prefix."map_settings'") != $wpdb->prefix."map_settings")
        {
            echo "Unable to create table '".$wpdb->prefix."map_settings"."'!";
            exit;
        }
        else
        {
            $maps = new MapsAPI("");
            $wpdb->get_var("INSERT INTO ".$wpdb->prefix."map_settings"." VALUES('','API Key','')");

            foreach ($maps->mapOptions as $option => $value)
            {
                if (is_array($value)) { $value = serialize($value); }
                if (is_bool($value)) { $value = ($value) ? 'true' : 'false'; }

                $wpdb->get_var("INSERT INTO ".$wpdb->prefix."map_settings"." VALUES('','".$option."','".$value."')");
            }
        }

    }

    if (!$wpdb->get_var( "SELECT * FROM ".$wpdb->prefix."map_addresses" ))
    {
        $query = dbDelta( "CREATE TABLE ".$wpdb->prefix."map_addresses (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` text NOT NULL,
        `infoWindow` text NOT NULL,
        `email` text NOT NULL,
        `address` text NOT NULL,
        PRIMARY KEY (`id`))"
        );

        if ($wpdb->get_var("SHOW TABLES LIKE '".$wpdb->prefix."map_addresses'") != $wpdb->prefix."map_addresses")
        {
            echo "Unable to create table '".$wpdb->prefix."map_addresses"."'!";
            exit;
        }
        else
        {
            $wpdb->get_var("INSERT INTO ".$wpdb->prefix."map_addresses"." VALUES('','Ryan Smith','<h2>Ryan Smith</h2> <img src=\'http://www.w3.org/html/logo/downloads/HTML5_Logo_256.png\' style=\'width: 35% !important; float: left !important; text-align: center; margin-right:14px; background: #FFFFFF !important; border: 1px solid #DBDBDB !important; padding: 2px !important; border-radius: 200px !important; -moz-border-radius: 200px !important; -webkit-border-radius: 200px !important; box-sizing: border-box !important; -moz-box-sizing: border-box !important; -webkit-box-sizing: border-box !important;\'><div>Ryan, our lead developer on Map Contact is located in Greater London!</div>','ryan@xantoo.com','London')");
            $wpdb->get_var("INSERT INTO ".$wpdb->prefix."map_addresses"." VALUES('','James Smith','<h2>James Smith</h2><img src=\'http://a1.res.cloudinary.com/hvqqwrowv/image/asset/css3-65bdc13faee51df7f05b91f44414a80d.png\' style=\'width: 35% !important; float: left !important; text-align: center; margin-right:14px; background: #FFFFFF !important; border: 1px solid #DBDBDB !important; padding: 2px !important; border-radius: 200px !important; -moz-border-radius: 200px !important; -webkit-border-radius: 200px !important; box-sizing: border-box !important; -moz-box-sizing: border-box !important; -webkit-box-sizing: border-box !important;\'><div>James, our lead marketer on Map Contact is located in Greater London!</div>','james@xantoo.com','London')");
        }
    }
}

add_action( 'admin_menu', "addSettingsPages" );
add_shortcode( 'map-contact', 'shortcodeManagment' );
register_activation_hook( __FILE__, "pluginActivated");
?>