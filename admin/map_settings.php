<?php
if( ! class_exists( 'WP_List_Table' ) ) { require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );}
class listTable extends WP_List_Table {
    var $data = array();

    function get_columns(){
        $columns = array(
            'id' => 'ID',
            'setting'    => 'Setting',
            'value'      => 'Value'
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
    <h2>Map Contact Settings</h2>
    <div id="content">
        <?php
        global $wpdb;

        if ($_POST["submit"])
        {
            foreach ($_POST as $key => $value)
            {
                $key = str_replace("_"," ",$key);

                $row = $wpdb->get_var("SELECT * FROM ".$wpdb->prefix."map_settings WHERE setting='".str_replace("array_","",$key)."'");
                if (is_numeric($row))
                {
                    if (strpos($key,"array_")!==false)
                    {
                        $array = array();
                        foreach (explode("\n",$value."\n") as $item)
                        {
                            if (!empty($item)) { $array[] = $item; }
                        }

                        $value = serialize($array);
                    }
                    elseif ($value=="On" || $value=="Off")
                    {
                        if ($value=="On") { $value = "true"; } else { $value = "false"; }
                    }

                    $query = $wpdb->query("UPDATE ".$wpdb->prefix."map_settings SET value='".$value."' WHERE setting='".str_replace("array_","",$key)."'");
                }
            }

            echo "<div class='updated'> <p>Map settings have been updated!</p> </div>";
        }

        $listTable = new listTable();

        $settings = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."map_settings");

        foreach ($settings as $setting) {
            $value = "";
            $setting = get_object_vars($setting);
            if (@unserialize($setting["value"])!==false)
            {
                $setting["value"] = unserialize($setting["value"]);
                $text = "";

                foreach ($setting["value"] as $val)
                {
                    $text .= "\n".$val;
                }

                $value .= "<textarea style='max-width: 100%; max-height: 106px; width: 100%; height: 106px;' name='array_".$setting["setting"]."'>".$text."</textarea>";
            }
            elseif($setting["value"]=="true" || $setting["value"]=="false")
            {
                if (filter_var($setting["value"], FILTER_VALIDATE_BOOLEAN)=="true")
                {
                    $value = '<input type="radio" checked name="'.$setting["setting"].'" value="On">On<br>
                              <input type="radio" name="'.$setting["setting"].'" value="Off">Off';
                }
                else
                {
                    $value = '<input type="radio" name="'.$setting["setting"].'" value="On">On<br>
                              <input type="radio" checked name="'.$setting["setting"].'" value="Off">Off';
                }
            }
            else{
                $value = "<input name='".$setting["setting"]."' style='width:100%;' type='text' value='".$setting["value"]."'>";

            }

            $listTable->data[] = array("id" => $setting["id"],"setting" => $setting["setting"],"value" => $value);
        }

        $listTable->prepare_items();

        if (strpos($_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"],"http")!==true) {
            $url = "http://".$_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
        }
        else {
            $url = $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
        }

        echo "
        <form action='".$URL."' method='POST'>";
        $listTable->display();
        submit_button('Update Settings', 'submit', 'submit');
        echo "</form> ";
        ?>
    </div>
</div>