<?php
/*
Plugin Name: WP Theme Update
Plugin URI: http://wordpress.org/plugins/wp-theme-update/
Version: 1.1.0
*/

//Insert 'Custom Update URI' as a field in the top of your style.css

//Adding new header param to the stylesheet meta information
add_action( 'extra_theme_headers', 'custom_theme_updater_url_header' );
function custom_theme_updater_url_header( $headers ) {
    $headers['Custom Update URI'] = 'Custom Update URI';
    return $headers;
}


//This function requests and parse the url specified in the stylesheet header
add_filter('site_transient_update_themes', 'custom_theme_updater_searcher');
function custom_theme_updater_searcher($data) {
	
	//Getting installed themes
	$themes = wp_get_themes();
	
	//Looping through themes
	foreach ( (array) $themes as $theme_title => $theme ) {
		
		//URL defined in the style.css
		$update_url = $theme->get('Custom Update URI');
		
		//Theme stylesheet path
		$theme_id = $theme->stylesheet;
		
		//Stops if there isn't custom url to update
		if( !$update_url )
			continue;
		
		//Theme version
		$version = $theme->version;
		
		$update_file_path = WP_CONTENT_DIR . '/themes/' . $theme_id . '/update.php';
		
		if( file_exists($update_file_path) )
			include_once($update_file_path);
		else
			error_log('update.php Not Found.');
		
		$request_options = array('sslverify' => false, 'timeout' => 10);
		
		//If needed some adjust to the request URL (e.g. Login info)
		$url = apply_filters( 'custom_theme_updater_request_url', $update_url, $theme_id );

		if(!is_string($url)) {
			$_url = (object) $url;
			$url = $_url->url;
			if(isset($_url->headers))
				$request_options['headers'] = $_url->headers;
		}
		
		//Getting data from cache
		// Note: WP transients fail if key is long than 45 characters
		$request_data = get_transient( md5( $update_url ) ); 
		
		//If there isn't any information in cache...
		if( empty( $request_data ) ) {
			
			//Requesting information about available versions
			$request = wp_remote_get($url, $request_options);
			
			//Some error happened in the request
			if(is_wp_error($request)) {
				$data->request[$theme_id]['error'] = 'Error response from ' . $update_url;
				error_log('Error response from ' . $update_url . ' => ' . print_r($request, true));
				continue;
			}
			
			//Parsing the request information
			$request_data = apply_filters('custom_theme_updater_parse_request', $request, $theme_id, $update_url, $version);
			
			//If the requested information have not the right information
			if( !isset($request_data->version) || !isset($request_data->package) ) {
				error_log('Invalid Upgrade Object');
				continue;
			}
			
			//If the requested information have some error...
			if( isset( $request_data->error ) && !empty( $request_data->error ) ) {
				$data->request[$theme_id]['error'] = $request_data->error;
				error_log($request_data->error);
				continue;
			}
			
			//Caching the data
			set_transient( md5( $update_url ), $request_data, 60 );
		}
		
		//If there is a new version, ...
		if( version_compare( $version, $request_data->version, '<' ) ) {
			
			//... the new version data is appended to the response object
			$data->response[$theme_id] = array(
				'new_version'	=> $request_data->version,
				'url'			=> $update_url,
				'package'		=> $request_data->package
			);
			
		}
		
	}
	
	return $data;
}

//This is a copy of WP_Upgrader::download_package
//But here you can 'patch' the download url
add_filter('upgrader_pre_download', 'custom_theme_updater_download_package', 10, 3);
function custom_theme_updater_download_package( $false, $url, $instance ) {
	
	if ( ! preg_match('!^(http|https|ftp)://!i', $url) && file_exists($url) ) //Local file or remote?
		return $url; //must be a local file..

	if ( empty($url) )
		return new WP_Error('no_package', $instance->strings['no_package']);

	$instance->skin->feedback('downloading_package', $url);
	
	//Here start my change
	$theme = @$instance->skin->theme_info->template;
	
	if(!isset($theme))
		$theme = @$instance->skin->theme;
	
	
	$url = apply_filters('custom_theme_updater_download_url', $url, $theme);
	
	
	$download_file = theme_update_download_url($url);
	//Here end my change

	if ( is_wp_error($download_file) )
		return new WP_Error('download_failed', $instance->strings['download_failed'], $download_file->get_error_message());

	return $download_file;
	
}

function theme_update_download_url( $url, $timeout = 300 ) {
	//WARNING: The file is not automatically deleted, The script must unlink() the file.
	if ( ! $url )
		return new WP_Error('http_no_url', __('Invalid URL Provided.'));

	$request_options = array( 'timeout' => $timeout, 'stream' => true );


	if(!is_string($url)) {
		$_url = (object) $url;
		$url = $_url->url;
		if(isset($_url->headers))
			$request_options['headers'] = $_url->headers;
	}

	$tmpfname = wp_tempnam($url);
	if ( ! $tmpfname )
		return new WP_Error('http_no_file', __('Could not create Temporary file.'));

	$request_options['filename'] = $tmpfname;

	$response = wp_safe_remote_get( $url, $request_options );

	if ( is_wp_error( $response ) ) {
		unlink( $tmpfname );
		return $response;
	}

	if ( 200 != wp_remote_retrieve_response_code( $response ) ){
		unlink( $tmpfname );
		return new WP_Error( 'http_404', trim( wp_remote_retrieve_response_message( $response ) ) );
	}

	$content_md5 = wp_remote_retrieve_header( $response, 'content-md5' );
	if ( $content_md5 ) {
		$md5_check = verify_file_md5( $tmpfname, $content_md5 );
		if ( is_wp_error( $md5_check ) ) {
			unlink( $tmpfname );
			return $md5_check;
		}
	}

	return $tmpfname;
}

add_filter('upgrader_source_selection', 'rename_folder_name', 10, 3);
function rename_folder_name($folder, $path, $instance) {
	
	$unzipped_folder = basename($folder);
	
	$theme_name = @$instance->skin->theme_info->template;
	
	if(!isset($theme_name))
		$theme_name = @$instance->skin->theme;
	
	//If not a theme that is been updated
	if(!isset($instance->skin->theme_info) && !isset($instance->skin->theme))
		return $folder;
	
	if($theme_name != $unzipped_folder) {
		
		$new_folder = str_replace($unzipped_folder, $theme_name, $folder);
		
		$new_path = pathinfo($new_folder);
		$new_path = $new_path['dirname'];
		
		//Fallback if the str_replace fails
		if($new_path != $path) {
			$theme_folder = explode('/', $folder);
		
			$folder_name = array_pop($theme_folder);
			$slash = '';
			
			if(empty($folder_name)) {
				$folder_name = array_pop($theme_folder);
				$slash = '/';
			}
			
			if($folder_name != $theme_name) {
				array_push($theme_folder, $theme_name);
				$new_folder = implode('/', $theme_folder);
				$new_folder .= $slash;
				rename($folder, $new_folder);
				$folder = $new_folder;
			}
		}
		else {
			rename($folder, $new_folder);
			$folder = $new_folder;
		}
	}
	
	return $folder;
	
}