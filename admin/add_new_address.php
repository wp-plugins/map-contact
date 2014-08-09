<div class="wrap">
    <h2>Map Contact Settings</h2>

    <div id="content">
        <?php
        global $wpdb;

        wp_enqueue_script('jquery');
        wp_enqueue_script('media-upload');
        wp_enqueue_script('thickbox');
        wp_register_script('my-upload', plugin_dir_url(__FILE__).'media_uploader.js', array('jquery','media-upload','thickbox'));
        wp_enqueue_script('my-upload');
        wp_enqueue_style('thickbox');

        if ($_POST["submit"])
        {
            $wpdb->query("INSERT INTO ".$wpdb->prefix."map_addresses VALUES('','".$_POST["image_location"]."','".trim($_POST["name"])."','".trim($_POST["infowindow"])."','".trim($_POST["email"])."','".trim($_POST["address"])."')");
            echo "<div class='updated'> <p>New address has been added!</p> </div>";
        }

        echo "
        <form action='".$URL."' method='POST'>
        <table style='padding-top:10px;'>
        <tr><td style='width:200px;'>Image:</td><td style='width:300px;'><input type='text'  name='image_location' value='' style='width:63.01%;' /><input type='button' class='upload-button' value='Upload Image' /></td></tr>
        <tr><td style='width:200px;'>Contact Name:</td><td style='width:300px;'><input style='width:100%;' type='text' name='name'></td></tr>
        <tr><td style='width:200px;'>Address:</td><td style='width:300px;'><input style='width:100%;' type='text' name='address'></td></tr>
        <tr><td style='width:200px;'>Info Window HTML:</td><td style='max-width:300px; width:300px;'><textarea style='max-width: 100%; max-height: 106px; width:100%; height: 106px;' name='infowindow'><h2>Your Name Here!</h2><img src='[IMAGE_URL]'><div>Your Info Window description here!</div></textarea></td></tr>
        <tr><td style='width:200px;'>Email:</td><td style='width:300px;'><input style='width:100%;' type='text' name='email'></td></tr>
        </table>";
        submit_button('Add Address', 'submit', 'submit');
        echo "</form>";

        ?>
    </div>
</div>