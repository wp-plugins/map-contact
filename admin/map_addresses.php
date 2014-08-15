<?php
if( ! class_exists( 'WP_List_Table' ) ) { require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );}
class listTable extends WP_List_Table {
    var $data = array();

    function get_columns(){
        $columns = array(
            'id' => 'ID',
            'image'    => 'Image',
            'name'    => 'Name',
            'address'      => 'Address',
            'infowindow'      => 'Info Window HTML',
            'email'      => 'Email',
            'delete' => 'Delete'
        );
        return $columns;
    }

    function column_default( $item, $column_name ) {
        switch( $column_name ) {
            case 'id':
            case 'setting':
            case 'value':
                return $item[ $column_name ];
            default:
                return $item[ $column_name ];
        }
    }

    function prepare_items() {
        $per_page = 15;
        $current_page = $this->get_pagenum();
        $total_items = count($this->data);
        $this->set_pagination_args( array(
            'total_items' => $total_items,                  //WE have to calculate the total number of items
            'per_page'    => $per_page                     //WE have to determine how many items to show on a page
        ) );
        $this->items = array_slice($this->data,(($current_page-1)*$per_page),$per_page);
        $this->_column_headers = array($this->get_columns(), array(), array());
    }
}
echo '<link rel="stylesheet" type="text/css" href="'.plugins_url( 'includes/stylesheet.css' , dirname(__FILE__) ) .'" />';
?>

<div class="wrap">
    <h2>Map Contact Address Book</h2>
    <p><b>Add shortcode to page/post:</b> [map-contact map="true" addressbook="true" width="500px" height="500px"]</p>
    <div id="content">
        <?php
        global $wpdb;
        if ($_POST["submit"])
        {
            foreach ($_POST as $key => $value)
            {
                $element = explode("_",$key)[1];
                $id = explode("_",$key)[0];
                $row = $wpdb->get_var("SELECT $element FROM ".$wpdb->prefix."map_settings WHERE id='".$id."'");

                if (@mysql_fetch_array($result) !== false && !empty($id) && !empty($element))
                {
                    if ($element=="image")
                    {
                        if (!empty($value))
                        {
                            $wpdb->query("UPDATE ".$wpdb->prefix."map_addresses SET $element='".$value."' WHERE id='".$id."'");
                        }
                    }
                    else
                    {
                        $wpdb->query("UPDATE ".$wpdb->prefix."map_addresses SET $element='".$value."' WHERE id='".$id."'");
                    }
                }
            }

            echo "<div class='updated'> <p>Addresses have been updated!</p> </div>";
        }

        if ($_POST && !$_POST["submit"])
        {
            foreach ($_POST as $key => $value)
            {
                $del = explode("_",$key)[1];
                if ($del=="delete")
                {
                    $id = explode("_",$key)[0];
                    $wpdb->query("DELETE FROM ".$wpdb->prefix."map_addresses WHERE id='".$id."'");

                    echo "<div class='updated'> <p>Address has been deleted!</p> </div>";
                }
            }
        }

        $listTable = new listTable();

        if (strpos($_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"],"http")!==true) {
            $url = "http://".$_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
        }
        else {
            $url = $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
        }

        $addressess = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."map_addresses");
        $imported = false;
        foreach ($addressess as $address)
        {
            $image = "";
            $address = get_object_vars($address);

            if ($imported==false)
            {
                wp_enqueue_script('jquery');
                wp_enqueue_script('media-upload');
                wp_enqueue_script('thickbox');
                wp_register_script('my-upload', plugin_dir_url(__FILE__).'media_uploader.js', array('jquery','media-upload','thickbox'));
                wp_enqueue_script('my-upload');
                wp_enqueue_style('thickbox');
                $imported = true;
            }

            if (!empty($address["image"])) { $image = "<div style='width:100%; text-align:center;'><img src='".$address["image"]."' style='max-width:100%;' /></div>";}

            $image .= "<div style='text-align:center; width:100%;'><input type='hidden'  name='".$address["id"]."_image' value='' /><input type='button' style='width:100%;' class='upload-button' value='Change' /></div>";
            $listTable->data[] = array("id" => $address["id"],"image" => $image,"name" => "<input name='".$address["id"]."_"."name"."' type='text' style='width:100%;' value='".$address["name"]."'>","infowindow" => "<textarea name='".$address["id"]."_"."infowindow"."' style='max-width: 100%; max-height: 106px;width:100%; height:106px;'>".$address["infoWindow"]."</textarea>","address" => "<input name='".$address["id"]."_"."address"."' type='text' style='width:100%;' value='".$address["address"]."'>","email" => "<input name='".$address["id"]."_"."email"."' type='text' style='width:100%;' value='".$address["email"]."'>", "delete" => "<div style='height:106px; line-height:106px; text-align:center;'><form id='delete' action='".$URL."' method='POST'><input type='submit' value='Delete' name='".$address['id']."_delete'></form></div>");
        }

        $listTable->prepare_items();

        echo "
        <form action='".$URL."' method='POST'>";
        $listTable->display();
        submit_button('Update Address Book', 'submit', 'submit');
        echo "</form> ";

        ?>
    </div>
</div>