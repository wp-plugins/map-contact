<?php
/*
	Plugin Name: Map Contact
	Description: Instantly create stylish and professional Contact Us Page with Map for any WordPress Theme!
	Author: Ryan Smith
    Plugin URI: http://wordpress.org/plugins/map-contact/
    Author URI: http://xantoo.com/
	Version: 3.0.3
 */

include(plugin_dir_path( __FILE__ )."includes/maps.php");

function shortcodeManagment($attributes){
    global $wpdb;

    $code = "<style>#map-contact h2 {margin-bottom:5px;} #map-contact h2,#map-contact div{font-family:Arial,Helvetica,sans-serif;}</style>";
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
                $iw = preg_replace("/<img[^>]+\>/i", "", $address["infoWindow"]);
                $map->addLocationPin($loc["lat"],$loc["lng"],$address["name"],"<div style='max-width:250px; padding-bottom:10px;'>".$iw."</div>");
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
            $contact = "";
            if (!empty($address["image"])) { $address["infoWindow"] = preg_replace("/".preg_quote("[IMAGE_URL]")."/i",$address["image"],$address["infoWindow"]); }

            if (!empty($address["email"])){
                $contact = '<button type="button" onclick="document.getElementById(\''.$address["id"].'_lightbox\').style.display=\'inline\';" >Contact by email</button>';

                if (strpos($_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"],"http")!==true) { $URL = "http://".$_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"]; } else { $URL = $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"]; }

                $form = "<form action='".get_admin_url()."admin-post.php?action=email&return=".base64_encode($URL)."' method='POST'>
                            <table style='padding-top:10px;'>
                            <input type='hidden' name='to' value='".$address["id"]."'>
                            <tr><td style='width:200px; border:0;'>Name:</td><td style='width:300px; border:0;'><input style='width:100%;' type='text' name='name'></td></tr>
                            <tr><td style='width:200px; border:0;'>Subject:</td><td style='width:300px; border:0;'><input style='width:100%;' type='text' name='subject'></td></tr>
                            <tr><td style='width:200px; border:0;'>Email:</td><td style='width:300px; border:0;'><input style='width:100%;' type='text' name='email'></td></tr>
                            <tr><td style='width:200px; border:0;'>Message</td><td style='max-width:300px; border:0;'><textarea style='max-width:100%; width:100%; max-height:106px;height: 106px;' name='message'></textarea></td></tr>
                            <tr><td style='width:200px; border:0;'></td><td style='width:300px; border:0;'><input style='float:right;' type='submit' name='send_email' value='Send Email'></td></tr>
                            </table></form>";

                $code .= '<div id="'.$address["id"].'_lightbox" style="z-index:99999; display:none;"class="lightbox">
                            <div id="'.$address["id"].'_contact" style="position: relative; width:530px; background:#fff; top: 25%; padding-right:10px; padding-left:15px; padding-bottom:10px;padding-top:10px; border-radius:5px; margin:0 auto;">
                                <img style="position:relative; float:right; padding-right:6px; padding-top:6px; cursor: pointer;" onclick="document.getElementById(\''.$address["id"].'_lightbox\').style.display=\'none\';" src="'.plugins_url( 'map-contact/images/close.png', dirname(__FILE__)).'">
                                <h2>'.$address["name"].'</h2>
                                '.$form.'
                            </div>
                          </div>';
            }
            $code .= "<div style='position:relative; float: left; margin-bottom:25px; margin-right:10px; min-height:100px; min-width:200px; max-width:250px; padding-left:20px;' id='".$address["id"]."_person' class='person_entry'>".$address["infoWindow"].$contact."</div>";
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

function sendEmailContact()
{
    global $wpdb;

    if (isset($_POST["send_email"]) && isset($_POST["to"]) && isset($_POST["message"]))
    {
        $returnEm = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."map_addresses WHERE id='".$_POST["to"]."'");
        $returnEm = get_object_vars($returnEm[0]);

        $to      = $returnEm["email"];
        $subject = $_POST["subject"]." - Map Contact";
        $message = $_POST["message"];
        $headers = 'From: '.$_POST["email"] . "\r\n";

        mail($to, $subject, $message, $headers);

        echo "Sending Email...";
        sleep(2);
        echo '<meta http-equiv="refresh" content="0; url='.base64_decode($_GET["return"]).'">';
    }
}

function editorButtons()
{
    if (wp_script_is('quicktags')){
        echo "<script type=\"text/javascript\">
            QTags.addButton( 'mc_shortcode', 'Map Contact Shortcode', '[map-contact map=\"true\" addressbook=\"true\" width=\"500px\" height=\"500px\"]', '', 'mc_shortcode', 'Map Contact Shortcode',  999);
        </script>";
    }
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
        dbDelta( "CREATE TABLE ".$wpdb->prefix."map_addresses (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `image` text NOT NULL,
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
            $wpdb->get_var("INSERT INTO ".$wpdb->prefix."map_addresses"." VALUES('','http://www.w3.org/html/logo/downloads/HTML5_Logo_256.png','Ryan Smith',\"<h2>Ryan Smith</h2><img src='[IMAGE_URL]'><div>Ryan, our lead developer on Map Contact is located in Greater London!</div>\",'ryan@xantoo.com','London')");
            $wpdb->get_var("INSERT INTO ".$wpdb->prefix."map_addresses"." VALUES('','http://a1.res.cloudinary.com/hvqqwrowv/image/asset/css3-65bdc13faee51df7f05b91f44414a80d.png','James Smith',\"<h2>James Smith</h2><img src='[IMAGE_URL]'><div>James, our lead marketer on Map Contact is located in Greater London!</div>\",'james@xantoo.com','London')");
        }
    }
}

function updatePlugin()
{
    global $wpdb;

    $result = $wpdb->get_var("SHOW COLUMNS FROM `wp_map_addresses` LIKE 'image'");

    if (!$result) { $wpdb->query("ALTER TABLE wp_map_addresses ADD COLUMN `image` text NOT NULL AFTER `ID`"); }
}

add_action("admin_post_email","sendEmailContact");
add_action( 'admin_print_footer_scripts', 'editorButtons' );
add_action( 'admin_menu', "addSettingsPages" );
add_shortcode( 'map-contact', 'shortcodeManagment' );
register_activation_hook( __FILE__, "pluginActivated");
add_action( 'admin_init', 'updatePlugin' );
?>