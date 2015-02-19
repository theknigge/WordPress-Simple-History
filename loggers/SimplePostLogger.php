<?php

/*
    - vid start av app: login, körs titt som tätt
    - XMLRPC_REQUEST": true
    do_action( 'xmlrpc_call', 'wp.editPost' );

         * All built-in XML-RPC methods use the action xmlrpc_call, with a parameter
         * equal to the method's name, e.g., wp.getUsersBlogs, wp.newPost, etc.
        do_action( 'xmlrpc_call', 'wp.getUsersBlogs' );    
*/    
    
    

		/**
		 * Fires after a new category has been successfully created via XML-RPC.
		 *
		 * @since 3.4.0
		 *
		 * @param int   $cat_id ID of the new category.
		 * @param array $args   An array of new category arguments.
		 */
#		do_action( 'xmlrpc_call_success_wp_newCategory', $cat_id, $args );


			/**
			 * Fires after a category has been successfully deleted via XML-RPC.
			 *
			 * @since 3.4.0
			 *
			 * @param int   $category_id ID of the deleted category.
			 * @param array $args        An array of arguments to delete the category.
			 */
#			do_action( 'xmlrpc_call_success_wp_deleteCategory', $category_id, $args );


			/**
			 * Fires after a comment has been successfully deleted via XML-RPC.
			 *
			 * @since 3.4.0
			 *
			 * @param int   $comment_ID ID of the deleted comment.
			 * @param array $args       An array of arguments to delete the comment.
			 */
#			do_action( 'xmlrpc_call_success_wp_deleteComment', $comment_ID, $args );


		/**
		 * Fires after a comment has been successfully updated via XML-RPC.
		 *
		 * @since 3.4.0
		 *
		 * @param int   $comment_ID ID of the updated comment.
		 * @param array $args       An array of arguments to update the comment.
		 */
#		do_action( 'xmlrpc_call_success_wp_editComment', $comment_ID, $args );


		/**
		 * Fires after a new comment has been successfully created via XML-RPC.
		 *
		 * @since 3.4.0
		 *
		 * @param int   $comment_ID ID of the new comment.
		 * @param array $args       An array of new comment arguments.
		 */
#		do_action( 'xmlrpc_call_success_wp_newComment', $comment_ID, $args );



		/**
		 * Fires after a new attachment has been added via the XML-RPC MovableType API.
		 *
		 * @since 3.4.0
		 *
		 * @param int   $id   ID of the new attachment.
		 * @param array $args An array of arguments to add the attachment.
		 */
#		do_action( 'xmlrpc_call_success_mw_newMediaObject', $id, $args );



/**
 * Logs changes to posts and pages, including custom post types
 */
class SimplePostLogger extends SimpleLogger
{

	// The logger slug. Defaulting to the class name is nice and logical I think
	public $slug = __CLASS__;

	public function loaded() {

		add_action("admin_init", array($this, "on_admin_init"));

		$this->add_xml_rpc_hooks();

	}

	/**
	 * Filters to XML RPC calls needs to be added early, admin_init is to late
	 */
	function add_xml_rpc_hooks() {

		// Debug: log all XML-RPC requests
		
		add_action("xmlrpc_call", function($method) {
			SimpleLogger()->debug("XML-RPC call for method '{method}'", array("method" => $method));
		}, 10, 1);
		

		add_action('xmlrpc_call_success_blogger_newPost', array($this, "on_xmlrpc_newPost"), 10, 2);
		add_action('xmlrpc_call_success_mw_newPost', array($this, "on_xmlrpc_newPost"), 10,2 );

		add_action('xmlrpc_call_success_blogger_editPost', array($this, "on_xmlrpc_editPost"), 10, 2);
		add_action('xmlrpc_call_success_mw_editPost', array($this, "on_xmlrpc_editPost"), 10, 2);

		add_action('xmlrpc_call_success_blogger_deletePost', array($this, "on_xmlrpc_deletePost"), 10, 2);
		add_action('xmlrpc_call_success_wp_deletePage', array($this, "on_xmlrpc_deletePost"), 10, 2);
		
		// detect "raw" calls
		// wp.deletePost' because mv_deletePost does not exist
		// use raw xmlrpc method calls to filter instead of the above?
		add_action("xmlrpc_call", array($this, "on_xmlrpc_call"), 10, 1);

	}

	function on_xmlrpc_call($method) {
		
		$sh = $GLOBALS["simple_history"];

		if ( "wp.deletePost" == $method ) {

			#if ( isset( $_SERVER["REQUEST_METHOD"] ) && $_SERVER["REQUEST_METHOD"] == "POST") {

				$raw_post_data = file_get_contents("php://input");
				$context["wp.deletePost.xmldata"] = $sh->json_encode( $raw_post_data );
				#$context["wp.deletePost.xmldata_decoded"] = $sh->json_encode( xmlrpc_decode( $raw_post_data, 'UTF-8' ) );

				$message = new IXR_Message( $raw_post_data );
				if ( $message->parse() ) {
					
					$context["wp.deletePost.xmlrpc_message"] = $sh->json_encode( $message );
					$context["wp.deletePost.xmlrpc_message.message"] = $sh->json_encode( $message->messageType );
					$context["wp.deletePost.xmlrpc_message.messageParams"] = $sh->json_encode( $message->params );

				}

				
			#}

			/*
			"<?xml version=\"1.0\"?><methodCall><methodName>wp.deletePost<\/methodName><params><param><value><i4>0<\/i4><\/value><\/param><param><value><string><\/string><\/value><\/param><param><value><string><\/string><\/value><\/param><param><value><i4>6750<\/i4><\/value><\/param><\/params><\/methodCall>"
			*/

			SimpleLogger()->info("hey there wp.deletePost", $context);

		}

	}


	/**
	 * Get array with information about this logger
	 *
	 * @return array
	 */
	function getInfo() {

		$arr_info = array(
			"name" => "Post Logger",
			"description" => "Logs the creation and modification of posts and pages",
			"capability" => "edit_pages",
			"messages" => array(
				'post_created' => __('Created {post_type} "{post_title}"', 'simple-history'),
				'post_updated' => __('Updated {post_type} "{post_title}"', 'simple-history'),
				'post_restored' => __('Restored {post_type} "{post_title}" from trash', 'simple-history'),
				'post_deleted' => __('Deleted {post_type} "{post_title}"', 'simple-history'),
				'post_trashed' => __('Moved {post_type} "{post_title}" to the trash', 'simple-history')
			),
			"labels" => array(
				"search" => array(
					"label" => _x("Posts & Pages", "Post logger: search", "simple-history"),
					"options" => array(
						_x("Posts created", "Post logger: search", "simple-history") => array(
							"post_created"
						),
						_x("Posts updated", "Post logger: search", "simple-history") => array(
							"post_updated"
						),
						_x("Posts trashed", "Post logger: search", "simple-history") => array(
							"post_trashed"
						),
						_x("Posts deleted", "Post logger: search", "simple-history") => array(
							"post_deleted"
						),
						_x("Posts restored", "Post logger: search", "simple-history") => array(
							"post_restored"
						),
					)
				) // end search array
			) // end labels

		);

		return $arr_info;

	}

	function on_admin_init() {

		add_action("transition_post_status", array($this, "on_transition_post_status"), 10, 3);
		add_action("delete_post", array($this, "on_delete_post"));
		add_action("untrash_post", array($this, "on_untrash_post"));

	}

	/**
	 * Fires after a post has been successfully deleted via the XML-RPC Blogger API.
	 *
	 * @since 2.0.21
	 *
	 * @param int   $post_ID ID of the deleted post.
	 * @param array $args    An array of arguments to delete the post.
	 */
	function on_xmlrpc_deletePost($post_ID, $args) {

		$post = get_post( $post_ID );

		$context = array(
			"post_id" => $post->ID,
			"post_type" => get_post_type( $post ),
			"post_title" => get_the_title( $post )
		);

		$this->infoMessage( "post_deleted", $context );

	}

	/**
	 * Fires after a post has been successfully updated via the XML-RPC API.
	 *
	 * @since 2.0.21
	 *
	 * @param int   $post_ID ID of the updated post.
	 * @param array $args    An array of arguments for the post to edit.
	 */
	function on_xmlrpc_editPost($post_ID, $args) {

		$post = get_post( $post_ID );

		$context = array(
			"post_id" => $post->ID,
			"post_type" => get_post_type( $post ),
			"post_title" => get_the_title( $post )
		);

		$this->infoMessage( "post_updated", $context );

	}

	/**
	 * Fires after a new post has been successfully created via the XML-RPC API.
	 *
	 * @since 2.0.21
	 *
	 * @param int   $post_ID ID of the new post.
	 * @param array $args    An array of new post arguments.
	 */
	function on_xmlrpc_newPost($post_ID, $args) {

		$post = get_post( $post_ID );

		$context = array(
			"post_id" => $post->ID,
			"post_type" => get_post_type( $post ),
			"post_title" => get_the_title( $post )
		);

		$this->infoMessage( "post_created", $context );

	}

	/**
	 * Called when a post is restored from the trash
	 */
	function on_untrash_post($post_id) {

		$post = get_post( $post_id );

		$this->infoMessage(
			"post_restored",
			array(
				"post_id" => $post_id,
				"post_type" => get_post_type( $post ),
				"post_title" => get_the_title( $post )
			)
		);

	}

	/**
	 * Called when a post is deleted from the trash
	 */
	function on_delete_post($post_id) {

		$post = get_post($post_id);

		if ( wp_is_post_revision($post_id) ) {
			return;
		}

		if ( $post->post_status === "auto-draft" || $post->post_status === "inherit" ) {
			return;
		}

		if ( "nav_menu_item" == get_post_type( $post ) ) {
			return;
		}

		$this->infoMessage(
			"post_deleted",
			array(
				"post_id" => $post_id,
				"post_type" => get_post_type($post),
				"post_title" => get_the_title($post)
			)
		);

	}


	/**
	  * Fired when a post has changed status
	  */
	function on_transition_post_status($new_status, $old_status, $post) {

		// Don't log revisions
		if ( wp_is_post_revision( $post ) ) {
			return;
		}

		// Don't log nav_menu_updates
		/*
		$post_types = get_post_types();
		Array
		(
		    [post] => post
		    [page] => page
		    [attachment] => attachment
		    [revision] => revision
		    [nav_menu_item] => nav_menu_item
		    [texts] => texts
		    [products] => products
		    [book] => book
		)
		*/
		if ( "nav_menu_item" == get_post_type( $post ) ) {
			return;
		}

		/*
		From new to auto-draft <- ignore
		From new to inherit <- ignore
		From auto-draft to draft <- page/post created
		From draft to draft
		From draft to pending
		From pending to publish
		From pending to trash
		From something to publish = post published
		if not from & to = same, then user has changed something
		*/

		$context = array(
			"post_id" => $post->ID,
			"post_type" => get_post_type($post),
			"post_title" => get_the_title($post),
			"post_new_status" => $new_status,
			"post_old_status" => $old_status
		);

		if ($old_status == "auto-draft" && ($new_status != "auto-draft" && $new_status != "inherit")) {

			// Post created
			$this->infoMessage( "post_created", $context );

		} elseif ($new_status == "auto-draft" || ($old_status == "new" && $new_status == "inherit")) {

			// Post was automagically saved by WordPress
			return;

		} elseif ($new_status == "trash") {

			// Post trashed
			$this->infoMessage( "post_trashed", $context );

		} else {

			// Post updated
			$this->infoMessage( "post_updated", $context );

		}

	}

	/**
	 * Modify plain output to inlcude link to post
	 */
	public function getLogRowPlainTextOutput($row) {

		$context = $row->context;
		$post_id = $context["post_id"];

		// Default to original log message
		$message = $row->message;

		// Check if post still is available
		// It wil return a WP_Post Object if post still is in system
		// If post is deleted from trash (not just moved there), then null is returned
		$post = get_post( $post_id );
		$post_is_available = is_a($post, "WP_Post");

		#sf_d($post_is_available, '$post_is_available');
		#sf_d($message_key, '$message_key');

		$message_key = isset($context["_message_key"]) ? $context["_message_key"] : null;

		// Try to get singular name
		$post_type_obj = get_post_type_object( $context["post_type"] );
		if ( ! is_null( $post_type_obj ) ) {

			if ( ! empty ($post_type_obj->labels->singular_name) ) {
				$context["post_type"] = strtolower( $post_type_obj->labels->singular_name );
			}

		}

		// If post is not available any longer then we can't link to it, so keep plain message then
		if ( $post_is_available ) {

			if ( "post_updated" == $message_key ) {

				$message = __('Updated {post_type} <a href="{edit_link}">"{post_title}"</a>', "simple-history");

			} else if ( "post_deleted" == $message_key ) {

				$message = __('Deleted {post_type} "{post_title}"', 'simple-history');

			} else if ( "post_created" == $message_key ) {

				$message = __('Created {post_type} <a href="{edit_link}">"{post_title}"</a>', "simple-history");

			} else if ( "post_trashed" == $message_key ) {

				// while in trash we can still get actions to delete or restore if we follow the edit link
				$message = __('Moved {post_type} <a href="{edit_link}">"{post_title}"</a> to the trash', "simple-history");

			}

		} // post still available

		$context["post_type"] = esc_html( $context["post_type"] );
		$context["post_title"] = esc_html( $context["post_title"] );
		$context["edit_link"] = get_edit_post_link( $post_id );

		return $this->interpolate($message, $context);

	}

}
