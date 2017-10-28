/**
 * Register a meta box using a class.
 */
class WPDocs_Custom_Meta_Box {
   protected $name = '';
   protected $slug = '';
   protected  $post_type = array();
   protected  $fields = array();
   /**
    * Constructor.
    */
   public function __construct($arg) {
      if ( is_admin() ) {
         $this->name = $arg['meta_name'];
         $this->slug = $arg['meta_slug'];
         $this->post_type = $arg['post_type'];
         $this->fields = $arg['fields'];
         add_action( 'load-post.php',     array( $this, 'init_metabox' ) );
         add_action( 'load-post-new.php', array( $this, 'init_metabox' ) );
      }

   }

   /**
    * Meta box initialization.
    */
   public function init_metabox() {
      add_action( 'add_meta_boxes', array( $this, 'add_metabox'  )        );

      add_action('pre_post_update', array( $this, 'dashboard_validation' ), 99, 2);

      add_action('admin_notices', array( $this, 'admin_notice_handler') );


      add_action( 'save_post',      array( $this, 'save_metabox' ), 10, 2 );
   }

   /**
    * Adds the meta box.
    */
   public function add_metabox() {
      add_meta_box(
         $this->name, __( $this->name, 'textdomain' ),
         array( $this, 'render_metabox' ),
         $this->post_type,
         'advanced',
         'default'
      );

   }

   /**
    * Renders the meta box.
    */
   public function render_metabox( $post ) {
      // Add nonce for security and authentication.
      wp_nonce_field( $this->slug . '_nonce_action', $this->slug . '_nonce' );

      if(!empty($this->fields)){

         foreach($this->fields as $field) {
            $field_name = $this->slug .'_' . $field['name'];
            $field_value = get_post_meta($post->ID, $field_name, true);

            echo "<p>";
            echo '<label style="display: block;font-size: 11pt;text-transform: capitalize;">'.$field['name'].'</label>';
            if($field['type']=='textarea'){

               echo '<textarea style="width: 100%;" name="'. $field_name . '">'.$field_value.'</textarea>';
            } else if($field['type']=='media') {

               $url =$field_value;
               $url = html_entity_decode($url, ENT_QUOTES);
               ?>
               <input id="<?=$field_name?>" name="<?=$field_name?>" type="text" value="<?php echo $url;?>"  style="width:400px;" />
               <input id="<?=$field_name?>_upl_button" type="button" value="Upload <?=$field['name']?>" />
               <br/><img src="<?php echo $url;?>" style="width:200px;" id="<?=$field_name?>_picsrc" />
               <script>
                  jQuery(document).ready( function( $ ) {
                     jQuery('#<?=$field_name?>_upl_button').click(function() {

                        window.send_to_editor = function(html) {
                           imgurl = jQuery(html).attr('src')
                           jQuery('#<?=$field_name?>').val(imgurl);
                           jQuery('#<?=$field_name?>_picsrc').attr("src",imgurl);
                           tb_remove();
                        }
                        formfield = jQuery('#<?=$field_name?>').attr('name');
                        tb_show( '', 'media-upload.php?type=image&amp;TB_iframe=true' );
                        return false;
                     });
                  });
               </script>
               <?php

            } else {
               echo '<input style="width:100%;" type="text" name="'. $field_name . '" value="'.$field_value.'">';
            }
            echo "</p>";


         }

      }

   }

   /**
    * Handles saving the meta box.
    *
    * @param int     $post_id Post ID.
    * @param WP_Post $post    Post object.
    * @return null
    */
   public function save_metabox( $post_id, $post ) {
      // Add nonce for security and authentication.
      $nonce_name   = isset( $_POST[$this->slug . '_nonce'] ) ? $_POST[$this->slug . '_nonce'] : '';
      $nonce_action = $this->slug . '_nonce_action';

      // Check if nonce is set.
      if ( ! isset( $nonce_name ) ) {
         return;
      }

      // Check if nonce is valid.
      if ( ! wp_verify_nonce( $nonce_name, $nonce_action ) ) {
         return;
      }

      // Check if user has permissions to save data.
      if ( ! current_user_can( 'edit_post', $post_id ) ) {
         return;
      }

      // Check if not an autosave.
      if ( wp_is_post_autosave( $post_id ) ) {
         return;
      }

      // Check if not a revision.
      if ( wp_is_post_revision( $post_id ) ) {
         return;
      }
      //print_r($_POST); die;

      if(!empty($this->fields)){

         foreach($this->fields as $field) {
            $field_name = $this->slug .'_' . $field['name'];
            $field_value = get_post_meta($post_id, $field_name, true);
            //$field_value = htmlentities($field_value, ENT_QUOTES);
            if(!isset($field_value)) {
               add_post_meta($post_id, $field_name, htmlentities($_POST[$field_name], ENT_QUOTES));
            } else {
               update_post_meta($post_id, $field_name, htmlentities($_POST[$field_name], ENT_QUOTES));
            }
         }
      }



   }



   public function dashboard_validation($post_id)
   {

      if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
         return $post_id;
      }

      if (!isset($_POST['post_type'])) {
         return $post_id;
      }

      if (!current_user_can('edit_posts', $post_id)) {
         return $post_id;
      }

      $errors = array();


      $post_type = get_post_type($post_id);
      if(!empty($this->fields)) {

         foreach ($this->fields as $field) {
            $field_name = $this->slug .'_' . $field['name'];
            $field_value = $_POST[$field_name];

            if (trim($field_value) == '' && $field['required']==true) {
               $errors[] = new WP_Error($field. '_validation_msg', 'Please enter title', 'error');
            } else if($field['validate_type']=='phone' && !preg_match('/^[0-9]{10}+$/', $field_value) && $field_value!='') {
               $errors[] = new WP_Error($field. '_validation_msg', 'Please enter valid phone number', 'error');
            }else if($field['validate_type']=='email' && !filter_var($field_value, FILTER_VALIDATE_EMAIL) && $field_value!='') {
               $errors[] = new WP_Error($field. '_validation_msg', 'Please enter valid email', 'error');
            }
         }
      }


      if (!empty($errors)) {
         add_user_meta(get_current_user_id(), 'admin_notices', $errors, true);
         $url = admin_url('post.php?post=' . $post_id) . '&action=edit';
         wp_redirect($url);
         exit;
      }
      return $_POST;
   }

   public function admin_notice_handler()
   {
      $user_id = get_current_user_id();
      $admin_notices = get_user_meta($user_id, 'admin_notices', true);

      if (!empty($admin_notices)) {
         $html = '';

         if (is_wp_error($admin_notices[0])) {

            delete_user_meta($user_id, 'admin_notices');

            foreach ($admin_notices AS $notice) {

               $msgs = $notice->get_error_messages();

               if (!empty($msgs)) {
                  $msg_type = $notice->get_error_data();
                  if (!empty($notice_type)) {
                     $html .= '<div class="' . $msg_type . '">';
                  } else {
                     $html .= '<div class="error">';
                     ?>
                     <style>
                        /*#title, #cypher-profile-work-parent, #radio-cypher-profile-work-categorydiv, #radio-cypher-profile-categorydiv, #radio-articles-categorydiv,
                        #radio-festival-categorydiv, #radio-boombox-categorydiv {
                           border: 1px solid red !important;
                        }*/
                     </style>
                  <?php }

                  foreach ($msgs as $msg) {
                     $html .= '<p><strong>' . $msg . '</strong></p>';
                  }
                  $html .= '</div>';
               }
            }
         }

         echo $html;
      }
   }
}
 
new WPDocs_Custom_Meta_Box();
/*$arg = array(
   "meta_slug"=>"your_slug", // add meta box slug
   "meta_name"=>"your meta box name", // add meta box name
   "post_type"=>array("post_type"), // add post type where to show meta box
   "fields" => array(
      //array("name"=>"name", "type"=>"text", "required"=>false, "validate_type"=>"name"),
      array("name"=>"name", "type"=>"text", "required"=>false), //fields
      array("name"=>"phone", "type"=>"text", "required"=>false),//fields
      array("name"=>"email", "type"=>"text", "required"=>false)//fields
   )

);
new WPDocs_Custom_Meta_Box($arg);*/
