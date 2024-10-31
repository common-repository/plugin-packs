<?php
/*
Plugin Name: Plugin Packs
Plugin URI: http://horia.me/the-wordpress-plugin-packs/
Description: Allows bundled plugin installs
Version: 0.1
Author: Horia Dragomir
Author URI: http://hdragomir.com/
*/

add_action('admin_menu', 'plugin_packs_page');


function plugin_packs_page(){

    if( function_exists( 'add_submenu_page' ) )
        add_submenu_page('plugins.php', __('Plugin Packs'), __('Plugin Packs'), 'install_plugins', 'plugin-packs', 'wpp_admin_page' );
}


function wpp_admin_page(){

    $pack = isset( $_GET['install'] ) ? $_GET['install'] : null;
    if( $pack )
        return wpp_process_pack( $pack );

    ?>
    <div class="wrap">
        <div id="icon-plugins" class="icon32"><br/></div>
        <h2>Plugin Packs</h2>
        <ul id="plugin-packs-list">
            <?php foreach( wpp_get_packs_list() as $packname ): ?>
                <li><a href="<?php echo $_SERVER['REQUEST_URI']; ?>&amp;install=<?php
                    echo urlencode( $packname ); ?>">Install Pack <?php echo $packname; ?></a></li>
            <?php endforeach; ?>
        </ul>

    </div>
    <?php

}


function wpp_process_pack( $file_id ){

    $plugins = wpp_get_plugin_pack_data( $file_id );

    include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
    include_once ABSPATH . 'wp-admin/includes/plugin-install.php';

    foreach( $plugins as $plugin ){
        $api = plugins_api('plugin_information', array('slug' => $plugin,
                                                       'fields' => array('sections' => false) ) );

        if ( is_wp_error($api) ){
            echo 'Error while trying to install ', $plugin, '<br />because: ', implode( ', ', ( $api->get_error_messages() ) );
            continue;
        }

        $title = sprintf( __('Installing Plugin: %s'), $api->name . ' ' . $api->version );
        $nonce = 'install-plugin_' . $plugin;
        $url = 'update.php?action=install-plugin&plugin=' . $plugin;
        $type = 'web';

        $upgrader = new Plugin_Upgrader( new Plugin_Installer_Skin( compact('title', 'url', 'nonce', 'plugin', 'api') ) );
        $upgrader->install($api->download_link);


    }
    ?>
    <div class="wrap"><h2>Package Processed <a href="<?php echo admin_url( 'plugins.php?page=plugin-packs' ); ?>">&laquo; Back</a></h2></div>
    <?php

}


function wpp_get_plugin_pack_data( $file_id ){
    
    return file( wpp_get_pack_filename( $file_id ) );
}


function wpp_get_pack_filename( $file_id ){
    
    return sprintf( wpp_get_packs_dir() .'%s.wpp', $file_id );
}


function wpp_get_packs_dir(){
    
    return realpath( dirname( __FILE__ ) ) . DIRECTORY_SEPARATOR . 'packs' . DIRECTORY_SEPARATOR;
}


function wpp_get_packs_list(){
    
    $dir = dir( wpp_get_packs_dir() );
    $packs_list = array();
    while( $file = $dir->read() )
        if( ! ( '.' == $file or '..' == $file or false === strpos( $file, '.wpp' ) ) )
            $packs_list[] = basename( $file, '.wpp' );
    return $packs_list;
}
