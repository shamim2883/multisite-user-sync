<?php
/*
Plugin Name: Multisite User and Role Sync
Plugin URI: https://shamimbiplob.wordpress.com/contact-us/
Description: Multisite User Sync will automatically synchronise users and their roles to all sites in multisite. Roles of users will be same on every site. If the role is changed on one site it will also synchronise to all site. If new user/site created it will also add to all site/users. If a secondary role is assigned to a user via the user role plugin, it will be added on every site. <b>Adding additional roles to users network-wide requires WP 4.3 or later.</b>
<br/>
<br/>The menu item "Users" is also removed from everywhere but the main site.
Version: 1.2
Author: Shamim, Torsten Liebig
Author URI: https://shamimbiplob.wordpress.com/contact-us/, http://www.allthingswordpress.de
Text Domain: mu-sunc
License: GPLv2 or later
*/

/**
 * Create a new site
 * Loop through all users and add them to the new blog
 *
 * @param  INT $blog_id - New blog ID
 *
 * @return void
 */
add_action( 'wpmu_new_blog', 'mus_add_all_users_to_new_site' );
function mus_add_all_users_to_new_site($blog_id)
{
    global $wpdb;

    // Query all blogs from multi-site install
    $blogids = $wpdb->get_col("SELECT blog_id FROM ".$wpdb->base_prefix."blogs");

    foreach($blogids as $blogid)
    {
        $users = get_users( array('blog_id' => $blogid) );

        if(!empty($users))
        {
            foreach($users as $user)
            {
                if(!empty($user->roles))
                {
                    foreach($user->roles as $role)
                    {
                        add_user_to_blog($blog_id, $user->ID, $role );
                    }
                } else {
                    add_user_to_blog($blog_id, $user->ID, get_blog_option($blog_id, 'default_role') );
                }
            }
        }
    }
}


/**
 * Add a new user to all other sites
 *
 * @param  INT $user_id - New User ID
 *
 * @return void
 */
add_action( 'user_register', 'mus_add_new_user_to_all_sites' );
function mus_add_new_user_to_all_sites( $user_id )
{
    global $wpdb;
	
	if( !is_multisite() )
        return;

    // Query all blogs from multi-site install
    $blogids = $wpdb->get_col("SELECT blog_id FROM ".$wpdb->base_prefix."blogs");
    $user = new WP_User( $user_id );

    if(!empty($blogids))
    {
        foreach($blogids as $blogid)
        {
			if(!empty($user->roles))
              {
            	foreach($user->roles as $role)
            	{
                	add_user_to_blog($blogid, $user_id, $role );
				}
            }
			else
				{
					add_user_to_blog($blogid, $user_id, 'subscriber' );
				}
        }
    }

}

/**
 * Assign user Role to all site when change in one site
 *
 * @param  INT $user_id
 * @param  STRING $role - New role
 * @param  ARRAY $old_roles - Old roles
 *
 * @return void
 */
add_action( 'set_user_role', 'mus_set_user_role_on_all_sites', 10, 2 );
function mus_set_user_role_on_all_sites( $user_id, $role )
{
    global $wpdb;
	
	if( !is_multisite() )
        return;

    // Query all blogs from multi-site install
    $blogids = $wpdb->get_col("SELECT blog_id FROM ".$wpdb->base_prefix."blogs");

    if(!empty($blogids))
    {
        $original_blog_id = get_current_blog_id();

        foreach($blogids as $blogid)
        {
            // Work with another site
            switch_to_blog($blogid);
            // Grab all user info and update role as in main site
            $site_user = get_user_by('id', $user_id);

            remove_action( 'set_user_role', 'mus_set_user_role_on_all_sites'); // avoid recursion
            $site_user->set_role($role);
			add_action( 'set_user_role', 'mus_set_user_role_on_all_sites', 10, 2 );

          }
        // Back to original main site
        switch_to_blog($original_blog_id);
    }
}



/**
 * Assign additional user Role to all sites when added in one site
 *
 * @param  INT $user_id
 * @param  STRING $role - New role
 *
 * @return void
 */
add_action( 'add_user_role', 'mus_add_new_user_role_to_all_sites', 10, 2 );
function mus_add_new_user_role_to_all_sites( $user_id, $role )
{
    global $wpdb;
    
    if( !is_multisite() )
        return;

    // Query all blogs from multi-site install
    $blogids = $wpdb->get_col("SELECT blog_id FROM ".$wpdb->base_prefix."blogs");

    if(!empty($blogids))
    {
        $original_blog_id = get_current_blog_id();

        foreach($blogids as $blogid)
        {
            // Work with another site
            switch_to_blog($blogid);
            // Grab all user info and update role as in main site
            $site_user = get_user_by('id', $user_id);

            remove_action( 'add_user_role', 'mus_add_new_user_role_to_all_sites'); // avoid recursion
            $site_user->add_role($role);
			add_action( 'add_user_role', 'mus_add_new_user_role_to_all_sites', 10, 2 );

          }
        // Back to original main site
        switch_to_blog($original_blog_id);
    }
}



/**
 * Remove user Role from all sites when removed from one site
 *
 * @param  INT $user_id
 * @param  STRING $role - New role
 *
 * @return void
 */
add_action( 'remove_user_role', 'mus_remove_user_role_from_all_sites', 10, 2 );
function mus_remove_user_role_from_all_sites( $user_id, $role )
{
    global $wpdb;
    
    if( !is_multisite() )
        return;

    // Query all blogs from multi-site install
    $blogids = $wpdb->get_col("SELECT blog_id FROM ".$wpdb->base_prefix."blogs");

    if(!empty($blogids))
    {
        $original_blog_id = get_current_blog_id();

        foreach($blogids as $blogid)
        {
            // Work with another site
            switch_to_blog($blogid);
            // Grab all user info and update role as in main site
            $site_user = get_user_by('id', $user_id);

            remove_action( 'remove_user_role', 'mus_remove_user_role_from_all_sites'); // avoid recursion
            $site_user->remove_role($role);
			add_action( 'remove_user_role', 'mus_remove_user_role_from_all_sites', 10, 2 );

          }
        // Back to original main site
        switch_to_blog($original_blog_id);
    }
}




/**
 * Remove secondary user roles when user rights are updated via User Role Editor plugin
 *
 * @return void
 */
add_action( 'current_screen', 'mus_remove_secondary_roles_ure', 10 );
function mus_remove_secondary_roles_ure() {

    global $wpdb;
    
    if( !is_multisite() )
        return false;

    if (!isset($_POST['user_id'])) {
        return false;
    }

    if (!current_user_can('edit_user', $_POST['user_id'])) {
        return false;
    }
    if ( !isset($_GET) OR empty($_GET) OR !isset($_GET['page']) OR $_GET['page'] !== 'users-user-role-editor.php' ) {
        return false;
    }

    if ( !isset($_POST) ) {
        return false;
    }

    if ( isset($_POST) AND !isset($_POST["ure_nonce"]) ) {
        return false;
    }




    $available_roles = get_editable_roles();

    $ure_primary_role = $_POST['primary_role'];

    $ure_secondary_roles = preg_grep('/^wp_role_(.+)/', array_keys($_POST));
    foreach ( $ure_secondary_roles as $key => $role_name ) :
        $role_name = str_replace("wp_role_", "", $role_name);

        if (array_key_exists($role_name, $available_roles)) :
            $ure_secondary_roles[$role_name] = $available_roles[$role_name];
            unset($ure_secondary_roles[$key]);
        else :
            unset($ure_secondary_roles[$key]);
        endif;
    endforeach;
    $additional_roles = array_merge( array($ure_primary_role => $available_roles[$ure_primary_role]), $ure_secondary_roles );
    $additional_roles = array_filter($additional_roles);

    $removed_roles = array_diff_key($available_roles, $additional_roles);


    // by calling $user->remove_role, this is replicated to all subsites via add_action (above);
    // Query all blogs from multi-site install
    $blogids = $wpdb->get_col("SELECT blog_id FROM ".$wpdb->base_prefix."blogs");

    if(!empty($blogids))
    {
        $original_blog_id = get_current_blog_id();

        foreach($blogids as $blogid)
        {
            // Work with another site
            switch_to_blog($blogid);
            // Grab all user info and update role as in main site
            $site_user = get_user_by('id', $_POST['user_id']);
                foreach ($removed_roles as $role_name => $role) {
                    remove_action( 'remove_user_role', 'mus_remove_user_role_from_all_sites'); // avoid recursion
                    $site_user->remove_role($role_name);
                }
          }
        // Back to original main site
        switch_to_blog($original_blog_id);
    }


    return true;        
}







// Remove user menu from sub sites to ensure user roles are only set via the main site
add_action( 'admin_menu', 'mus_remove_user_menu_from_backend' );
function mus_remove_user_menu_from_backend(){

    // do not remove menu from the main site
    if ( is_main_site() ) return false; 

    remove_submenu_page( 'users.php', 'users.php' );
    remove_submenu_page( 'users.php', 'user-new.php' );
}
