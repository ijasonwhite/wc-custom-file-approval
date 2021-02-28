<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$classname = 'editor_attachment';

if (!class_exists($classname)) {
    class editor_attachment
    {

        public function __construct($filetypes = 'application/pdf', $boxtitle = 'Box Title', $pagetype = 'shop_order', $position = 'side')
        {
            //Constructor
            add_action('init', array(&$this, 'init'));
            if (is_admin()) {
                add_action('admin_init', array(&$this, 'admin_init'));
            }
            $this->uuid = uniqid();
            $this->input_id = 'editor_' . uniqid();
            $this->title = $boxtitle;
            $this->pagetype = $pagetype;
            $this->position = $position;
            $this->file_types = explode(',', $filetypes);
        }

        /**
         * FUNCTIONS
         */
        public function add_meta_boxes()
        {
            add_meta_box(
                $this->uuid,
                $this->title,
                array(&$this, 'editor_attachment'),
                $this->pagetype,
                $this->position
            );
        } //

        public function editor_attachment()
        {

            wp_nonce_field(plugin_basename(__FILE__), '_wp_customer_file_nonce');
            $file = (get_post_meta(get_the_ID(), '_wp_customer_file', true));


            $approved = (get_post_meta(get_the_ID(), '_wp_customer_file_approved', true));
            if ($approved != 1) {

                ?>
                <div style="
            text-align:center;
            padding:10px;
            background-color: #fff2f2;
            border: solid 1px #ff8585;
            margin: 5px;
            ">Waiting Approval
                </div>
                <?php
            } else {
                ?>
                <div style="
            text-align:center;
            padding:10px;
            background-color: #f2fff8;
            border: solid 1px #54aa8e;
            margin: 5px;
            ">Customer Approved
                </div>
                <?php

            }


            if (is_array($file)) {
                ?>
                <strong>Attached File:</strong>
                <a href="<?php echo($file['url']); ?>" target="_blank"><?php echo basename($file['file']); ?></a>
                <hr>
                <img style="max-width: 100%;" src="<?php echo($file['url']); ?>">
                <hr>
                <?php
            }
            $html = '<div style="position:relative"><label  style=" background-color: #006799;
    color: white;
    padding: 0.5rem;
    font-family: sans-serif;
    border-radius: 0.3rem;
    cursor: pointer;
    margin-top: 1rem;
    min-width: calc(100% - 15px);
    display: block;
    text-align: center;" for="' . $this->input_id . '" class="xbutton xbutton-primary xbutton-large"><span class="dashicons dashicons-upload"></span> Upload Proof<input style="display:none" type="file" id="' . $this->input_id . '" name="_wp_customer_file" value="" size="25" accept="' . implode(',', $this->file_types) . '" /></label></div>';
            echo $html;

        } //

        public function update_edit_form()
        {
            echo ' enctype="multipart/form-data"';
        } //

        public function save_custom_meta_data($id)
        {

            /* --- security verification --- */
            //if(!wp_verify_nonce($_POST['_wp_customer_file_nonce'], plugin_basename(__FILE__))) {
            // return $id;
            //} // end if



            if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
                return $id;
            } // end if

            if ('page' == @$_POST['post_type']) {
                if (!current_user_can('edit_page', $id)) {
                    return $id;
                } // end if
            } else {
                if (!current_user_can('edit_page', $id)) {
                    return $id;
                } // end if
            } // end if
            /* - end security verification - */

            // Make sure the file array isn't empty
            if (!empty($_FILES['_wp_customer_file']['name'])) {


                // Get the file type of the upload
                $arr_file_type = wp_check_filetype(basename($_FILES['_wp_customer_file']['name']));
                $uploaded_type = $arr_file_type['type'];


                // Check if the type is supported. If not, throw an error.
                if (in_array($uploaded_type, $this->file_types)) {

                    // Use the WordPress API to upload the file
                    $upload = wp_upload_bits($_FILES['_wp_customer_file']['name'], null, file_get_contents($_FILES['_wp_customer_file']['tmp_name']));

                    if (isset($upload['error']) && $upload['error'] != 0) {
                        wp_die('There was an error uploading your file. The error is: ' . $upload['error']);
                    } else {

                        $post_id = get_the_ID();
                        $order = new WC_Order($post_id);
                        $order->update_status('wc-waiting-approval', '', true);
                        add_post_meta($id, '_wp_customer_file', $upload);
                        update_post_meta($id, '_wp_customer_file', $upload);

                        add_post_meta($id, '_wp_customer_file_approved', '0');
                        update_post_meta($id, '_wp_customer_file_approved', '0');


                        $order->add_order_note('File for approval added <a target="_blank" href="' . $upload['url'] . '">' . basename($upload['url']) . '</a>', true);

                        //send email
                        if (isset($_POST['post_type']) && 'shop_order' == $_POST['post_type']) {
                            // its ok


                            ///// SEND EMAIL HERE
                        } else {
                            return; // its not ok
                        }


                    } // end if/else

                } else {
                    wp_die("The file type that you've uploaded is not a supported type.");
                } // end if/else

            } else { // no files

                $post_id = get_the_ID();
                $order = new WC_Order($post_id);
                //catch admin changing status to approved
                if ($_REQUEST['order_status'] == 'wc-customer-approved') {
                    update_post_meta($id, '_wp_customer_file_approved', '1');
                    add_post_meta($id, '_wp_customer_file_approved_date', current_time('timestamp'));
                    update_post_meta($id, '_wp_customer_file_approved_date', current_time('timestamp'));
                    $order->add_order_note('Proof approved for customer by admin.', true);
                }
                if ($_REQUEST['order_status'] == 'wc-waiting-approval') {
                    update_post_meta($id, '_wp_customer_file_approved', '0');
                    add_post_meta($id, '_wp_customer_file_approved_date', '');
                    update_post_meta($id, '_wp_customer_file_approved_date', '');

                    $order->add_order_note('Proof de-approved by admin', true);
                }



                // end if
            }

        } //





    public function wcea_register_order_status()
        {
            register_post_status('wc-waiting-approval', array(
                'label' => _x('Waiting Customer Approval', 'Order status', 'woocommerce'),
                'public' => true,
                'exclude_from_search' => false,
                'show_in_admin_all_list' => true,
                'show_in_admin_status_list' => true,
                'label_count' => _n_noop('Waiting Approval <span class="count">(%s)</span>', 'Waiting Approval<span class="count">(%s)</span>', 'woocommerce')
            ));

            register_post_status('wc-customer-approved', array(
                'label' => _x('Customer Approved', 'Order status', 'woocommerce'),
                'public' => true,
                'exclude_from_search' => false,
                'show_in_admin_all_list' => true,
                'show_in_admin_status_list' => true,
                'label_count' => _n_noop('Approve <span class="count">(%s)</span>', 'Approved<span class="count">(%s)</span>', 'woocommerce')
            ));
        }


// Register in wc_order_statuses.
    public function wcea_order_statuses($order_statuses)
        {
            $order_statuses['wc-waiting-approval'] = _x('Waiting Customer Approval', 'Order status', 'woocommerce');
            $order_statuses['wc-customer-approved'] = _x('Customer Approved', 'Order status', 'woocommerce');
            return $order_statuses;
        }




        public function init()
        {
            /**
             * ALWAYS RUN
             */
            //ADD META BOX
            add_action('add_meta_boxes', array(&$this, 'add_meta_boxes'));
            //UPDATE EDIT FORM
            add_action('post_edit_form_tag', array(&$this, 'update_edit_form'));
            //
            add_action('save_post', array(&$this, 'save_custom_meta_data'));
            //
            add_action('init', array(&$this, 'wcea_register_order_status'));
            //
            add_filter('wc_order_statuses', array(&$this, 'wcea_order_statuses'));

        }


        public function admin_init()
        {
            /**
             * ONLY RUN IF ADMIN
             */
            global $pagenow; // Check if in edito             
            if ($pagenow == 'post-new.php' || $pagenow == 'post.php' || $pagenow == 'edit.php') {


            }
        }


    }
}


// define the woocommerce_view_order callback
/*function action_woocommerce_view_order($array , $int)
{
    // make action magic happen here...

    echo '<h1>HELLO</h1>';
}
*/

add_action( 'woocommerce_view_order', 'before_woocommerce_order_details', 5 );
function before_woocommerce_order_details($order_id){
    $order = new WC_Order($order_id);



    if ( $order->get_status() == 'waiting-approval') {

        $file = (get_post_meta($order_id, '_wp_customer_file', true));
        if (isset($_REQUEST['action'] ) && $_REQUEST['action'] == 'Approve' ) {
            update_post_meta($order_id, '_wp_customer_file_approved', '1');
            add_post_meta($order_id, '_wp_customer_file_approved_date', current_time('timestamp'));
            update_post_meta($order_id, '_wp_customer_file_approved_date', current_time('timestamp'));
            $order->add_order_note('Proof approved by customer. <a target="_blank" href="' . $file['url'] . '">' . basename($file['url']) . '</a>', true);
            $order->update_status('wc-customer-approved', '', true);
        }

    ?>

        <a name="wcfa"></a><form class="wcfa-block order<?php echo $order_id?>" method="post">
            <legend>You need to Approve this.</legend>
                    <?php wp_nonce_field(plugin_basename(__FILE__), 'wp_wcfa_approval_nonce'); ?>
            <div>


                <img style="max-width: 100%;" src="<?php echo($file['url']); ?>" alt="">
             </div>

<div>
                <input type="submit" name="action" value="Approve">

            </div>
        </form>
<?php

    }

}

// add the action
//add_action('woocommerce_view_order', 'action_woocommerce_view_order', 10, 1);




if (class_exists('editor_attachment'))
    $GLOBALS['editor_attachment'] = new editor_attachment('image/png,image/jpg,image/jpeg,image/jiff', 'File for Approval', 'shop_order');
