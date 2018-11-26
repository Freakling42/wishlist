<?php
/*
Plugin Name: Woocommerce Wishlists
Plugin URI: 
Description: This plugin makes you able to create wishlists and share them with your friends.
Version: 1.0.0
Author: Tinna Domino
Author URI: http://madebydomino.dk/
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class TD_wishlists
{

    private static $instance;

    function __construct() {
        define('TD_WISHLISTS_VERSION', '1.0.0' );
        define('TD_WISHLISTS_DIR', dirname( __FILE__ ) );
        define('TD_WISHLISTS_URL', plugins_url( '', __FILE__ ) );
        define('TD_WISHLISTS_BASENAME', plugin_basename( __FILE__ ) );
        define('TD_WISHLISTS_PREFIX', 'td_wishlists_');

        add_action( 'wp_ajax_TD_saveWishList', array( $this,'TD_saveWishList') );
        add_action( 'wp_ajax_nopriv_TD_saveWishList', array( $this,'TD_saveWishList') );
        
        add_action('init' , array($this, 'init'));
        
        add_shortcode('TDWishlistpage', array($this, 'TD_Wishlistpage') );
    }

    /**
     * Initialize
     */
    function init() {
//            $this->TD_createWishlistTables();      

        add_action('wp_enqueue_scripts', array($this, 'TD_wishlists_scripts'));    
    }


    public static function instance() {
        if ( ! isset( self::$instance ) ) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    // Enable scripts
    function TD_wishlists_scripts() {
        wp_enqueue_script('jquery');
        wp_enqueue_style(TD_WISHLISTS_PREFIX.'style', plugins_url( '/assets/css/style.css', __FILE__ ));    
        wp_enqueue_script(TD_WISHLISTS_PREFIX.'ajax-script', plugin_dir_url(__FILE__).'assets/js/custom.js', array('jquery'), 1.0);
        wp_localize_script(TD_WISHLISTS_PREFIX.'ajax-script', 'ajax_object', array('ajaxurl' => admin_url('admin-ajax.php'))); // setting ajaxurl
    }  
    
    // Settings
    function TD_wishlists_settings() {
    }
    
    function skypim_MyShop_load_settings_page() {
    }

    function skypim_MyShop_load_settings() {
    }    
    
    function TD_wishlists_save_settings() {
    }    

    // Settings page HTML
    function TD_wishlists_settings_page() {
    ?>
        <div class="TDsettingsWrapper">
            <h2>Hello world</h2>

        <?php
            if ('true' == esc_attr( $_GET['updated'])) {
                echo '<div class="updateAnnouncement"><p>Updated</p></div>';
            }
        ?>
            <form method="post" action="<?php admin_url('admin.php?page=td-wishlists-settings'); ?>">
                <?php
                wp_nonce_field("td-wishlists-settings-action", "td-wishlists-settings-page");
                include(TD_WISHLISTS_DIR . '/settingstemplate.php' );
                submit_button();
                ?>
                <p class="submit" style="clear: both;">
                    <input type="hidden" name="td-wishlists-settings-submit" value="Y" />
                </p>

            </form>
        </div>
    <?php
    }
    
    // Create new table in woo for storing wishlists
    function TD_createWishlistTables() {  
        global $wpdb;

        // Create wishlist table to store the wishlists the user creates
        $WishlistTableName = $wpdb->prefix . "TD_wishlists";
        $WordpressUsersTable = $wpdb->prefix . "users";
        
        $WishlistSql = "
        CREATE TABLE IF NOT EXISTS " . $WishlistTableName . " (
            wishlistid BIGINT unsigned not null auto_increment,
            userid  BIGINT(20) unsigned,
            wishlistname VARCHAR(255),
            PRIMARY KEY (wishlistid),
            FOREIGN KEY (userid) REFERENCES $WordpressUsersTable(ID)
        ) DEFAULT CHARSET=utf8";
        
        // Create wishlistitems table to store the item the user assign to the list
        $WishlistItemsTableName = $wpdb->prefix . "TD_wishlist_items";
        $WishlistItemsSql = "
        CREATE TABLE IF NOT EXISTS " . $WishlistItemsTableName . " (
            wishlistitemid BIGINT unsigned not null auto_increment,
            wishlist  BIGINT unsigned,
            itemid  INT unsigned,
            itemqty  INT unsigned,
            PRIMARY KEY (wishlistitemid),
            FOREIGN KEY (wishlist) REFERENCES $WishlistTableName(wishlistid)
        ) DEFAULT CHARSET=utf8";        
        
        
        $wpdb->query( $WishlistSql );
        $wpdb->query( $WishlistItemsSql );
    }  
    
    
    /**
    * add shortcode [TDWishlistpage]
    */
    function TD_Wishlistpage(){   
        $outputHTML = '';;
        
        //check if user is logged in
        if ( is_user_logged_in() ){
            $outputHTML .= '<div id="wishlistWrapper">';
            $outputHTML .= '<input type="text" name="wishlistname" id="TDwishlistname" value="">';
            $outputHTML .= '<input type="submit" value="Submit" onclick="td_saveWishList()">';
            $outputHTML .= '</div';
           
            $wishlists = $this->TD_getWishLists();
            
            $resultrecord;
            
            foreach($wishlists as $wishlist) {
                $resultrecord = $wishlist;
            }       
            
        } else {
            $outputHTML .= 'You need to be logged in to use wishlists';
        }
        
        
        //write html to screen
        echo $outputHTML;
    }    


    /**
    * Get currentuser wishlists
    */    
    function TD_getWishLists(){
        global $wpdb;
        
        $WishlistTableName = $wpdb->prefix . "TD_wishlists";
        $userID = get_current_user_id();

        $Sql = "SELECT wishlistname FROM " . $WishlistTableName . " WHERE userid = " . $userID . "";
        $result = $wpdb->get_var($Sql);

        return $result;        
    }


    /**
    * Insert wishlists
    */     
    function TD_insertWishLists($listname){
        global $wpdb;

        $WishlistTableName = $wpdb->prefix . "TD_wishlists";
        $userID = get_current_user_id();

        $Values = array();
        $Values['userid'] = $userID;
        $Values['wishlistname'] = $listname;

        $TypeArray = array();
        $TypeArray[] = '%d';
        $TypeArray[] = '%s';


        $CheckExistSql = "SELECT count(*) FROM " . $WishlistTableName . "";
        $CheckIfExists = $wpdb->get_var($CheckExistSql);

        if ($CheckIfExists == 0)  {
            $this->TD_createWishlistTables();
            $wpdb->insert($WishlistTableName, $Values, $TypeArray);
        } else {
            $wpdb->insert($WishlistTableName, $Values, $TypeArray);
        }
 
        $result = $wpdb->insert_id;
        
        return $result;
    }   
    
    function TD_saveWishList(){    
        $listname = $_POST['NewListName'];       
        $result = $this->TD_insertWishLists($listname);
        
        echo $result;
        die(); // stop executing script
    }    
    
    
}

// register_activation_hook( __FILE__, array( 'TD_wishlists', 'TD_wishlists_activate' ) );

function TD_wishlists() {
    return TD_wishlists::instance();
}

$TDWishlists = TD_wishlists();
