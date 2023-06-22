<?php
function enqueue_parent_theme_style()
{
    wp_enqueue_style('parent-style', get_template_directory_uri() . '/style.css');
}
add_action('wp_enqueue_scripts', 'enqueue_parent_theme_style');
function ajax_load_posts() {
  check_ajax_referer('ajax-pagination-nonce', 'nonce');

  $page = $_POST['page'];

  // Query posts using WP_Query or any other method you prefer
  $args = array(
    'post_type' => 'cb_university',
    'posts_per_page' => 5,
    'paged' => $page
  );
  $query = new WP_Query($args);

  ob_start();

  if ($query->have_posts()) {
    while ($query->have_posts()) {
      $query->the_post();
      // Output the HTML for each post
      get_template_part('content', 'post');
    }
    wp_reset_postdata();
  }

  $response = array(
    'posts' => ob_get_clean(),
    'max_pages' => $query->max_num_pages
  );

  wp_send_json_success($response);
}
add_action('wp_ajax_ajax_load_posts', 'ajax_load_posts');
add_action('wp_ajax_nopriv_ajax_load_posts', 'ajax_load_posts');



// Search filter start
add_action('wp_ajax_nopriv_search_action', 'searchdata_fetch');
add_action('wp_ajax_search_action', 'searchdata_fetch');
function searchdata_fetch(){    
    $search = (!empty($_POST['search_university']) )? sanitize_text_field($_POST['search_university']) : ''; 
    $university = (!empty($_POST['university']) )? sanitize_text_field($_POST['university']) : '';


    
    // load more pending    
    //$no_post = (!empty($_POST['number']) )? sanitize_text_field($_POST['number']) : '';   
    //$no_post = (int)$no_post;    
    if( ( !empty($search) ) && ( !empty($university) )  ){                  
        $args = array(
            'post_type' => array('cb_university'),
            'posts_per_page' => -1,
            'order' => 'ASC',
            's' => $search,
            'tax_query'=> array(
              array(
              'taxonomy'  => 'university_type',
              'field'     => 'term_id',
              'terms'     => array($university)
              )
            )
        );
        $getPosts = new WP_Query($args);        
        // echo "<pre>";
        // print_r($getPosts);
        // echo "</pre>";
        // exit();
    }else if( ( !empty($search) )  ){
                                                  
        $args = array(
            'post_type' => array('cb_university'),
            'posts_per_page' => -1,
            'order' => 'ASC',
            's' => $search,

            // 'tax_query'=> array(
            //   // 'relation' => 'OR',
            //   array(
            //   'taxonomy'  => 'university_type',
            //   'field'     => 'term_id',
            //   'terms'     => array($university)
            //   )
            // )
        );
        $getPosts = new WP_Query($args);
        // echo "<pre>";
        // echo "single";
        // print_r($getPosts);
        // echo "</pre>";
        // exit();

    }else if( ( !empty($university) )  ){

       $args = array(
            'post_type' => array('cb_university'),
            'posts_per_page' => -1,
            'order' => 'ASC',            
            'tax_query'=> array(          
              array(
              'taxonomy'  => 'university_type',
              'field'     => 'term_id',
              'terms'     => array($university)
              )
            )
        );
        $getPosts = new WP_Query($args);
        // echo "<pre>";
        // echo "single";
        // print_r($getPosts);
        // echo "</pre>";
        // exit();


  }else{
        $result = [
        'status' => 'error',        
        'msg' => ( 'No Data found!! ' ),        
        ];
        wp_send_json($result);
        wp_die(); 
    }
   
    $post_count = $getPosts->post_count;

    

    if($post_count == 0) {
        $result = [
        'status' => 'error',        
        'msg' => ( 'No Result Found!!' ),        
        ];
        wp_send_json($result);
        wp_die();    
    }

    $posts = [];
     if ( $getPosts->have_posts() ) { 
          while ($getPosts->have_posts()) {
            $getPosts->the_post();                       
            $posts[] = array(
                'title' => get_the_title(),
                'permalink' => get_permalink(),                
                'thumbnail' => get_the_post_thumbnail_url(),
                'post_count' => $post_count,
                // 'cricos_code' => get_field('cricos_code'),
                // 'website_url' => get_field('website_url'),                 
            );
        }
    }

    $result = [
        'status' => 'success',
        'response_type' => 'get posts',
        'msg' => 'results',        
        'data' => $posts,              
    ];



    // echo "<pre>";
    // print_r($result);
    // echo "</pre>";
    // exit();

    wp_send_json($result);
    wp_die();    
}

// search title


// custom post meta start

//How to fetch at frontent : 
//global $post;
//echo get_post_meta($post->ID, 'custom_input',true); 

add_action('admin_init','custom_metabox');
function custom_metabox(){
    // post / blog add  field
    add_meta_box("custom_metabox_01","Custom Metabox", "custom_metabox_field","post", "normal", "low");
    // assign to custom post type cb_university add  field
    add_meta_box("custom_metabox_01","Custom Metabox", "custom_metabox_field","cb_university", "normal", "low");
}
function custom_metabox_field(){
    global $post;
    $data = get_post_custom( $post->ID );
    $val = isset( $data['custom_input'] )? esc_attr( $data['custom_input'][0] ) : "no values";
    echo '<input type="text" name="custom_input" id="custom_input" value="'.$val.'" >';
}
add_action('save_post', 'save_detail');
function save_detail($post_id){
    
    if( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ){
        return $post_id;
    }    
    
    if( !current_user_can( 'edit_post', $post_id ) ){
        return $post_id;
    }    
    
    if( isset($_POST['custom_input']) ){
        update_post_meta( $post_id, 'custom_input', $_POST['custom_input'] );
    }
}
// custom post meta end