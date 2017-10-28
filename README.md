# Metabox generator
Generate Wordpress Metaboxes

Include metabox.php in your theme function file 


Add this code to function

<pre>$arg = array(
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
new WPDocs_Custom_Meta_Box($arg);</pre>
