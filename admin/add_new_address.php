<div class="wrap">
    <h2>Map Contact Settings</h2>

    <div id="content">
        <?php
        global $wpdb;

        if ($_POST["submit"])
        {
            $wpdb->query("INSERT INTO ".$wpdb->prefix."map_addresses VALUES('','".trim($_POST["name"])."','".trim($_POST["infowindow"])."','".trim($_POST["email"])."','".trim($_POST["address"])."')");
            echo "<div class='updated'> <p>New address has been added!</p> </div>";
        }

        echo "
        <form action='".$URL."' method='POST'>
        <table style='padding-top:10px;'>
        <tr><td style='width:200px;'>Contact Name:</td><td style='width:300px;'><input style='width:100%;' type='text' name='name'></td></tr>
        <tr><td style='width:200px;'>Address:</td><td style='width:300px;'><input style='width:100%;' type='text' name='address'></td></tr>
        <tr><td style='width:200px;'>Info Window HTML:</td><td style='max-width:300px; width:300px;'><textarea style='max-width: 100%; max-height: 106px; width:100%; height: 106px;' name='infowindow'></textarea></td></tr>
        <tr><td style='width:200px;'>Email:</td><td style='width:300px;'><input style='width:100%;' type='text' name='email'></td></tr>
        </table>";
        submit_button('Add Address', 'submit', 'submit');
        echo "</form>";
        ?>
    </div>
</div>