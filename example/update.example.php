<?php
//You need to put this file inside yout theme folder and rename it to 'update.php'

// Assuming that you added this line to style.css: Custom Update URI: http://example.com/

// And your versions URL is http://example.com/version.json

// And this url has the following code: {'1.0.1': 'http://example.com/download/1.0.1.zip'}

//You don't need to use all these filters

add_filter('custom_theme_updater_request_url', 'theme_updater_url', 10, 2);
//Here you receive the url parameter that you specified in the style.css
function theme_updater_url($url, $id) {
	if($id == 'MY_THEME_ID_OR_FOLDER') {
		//Some change necessary to the URL to make the request
		// $url .= 'versions.json?hashlogin=123';
	}
	return $url;
}


add_filter('custom_theme_updater_parse_request', 'theme_updater_custom_parser', 10, 5);
//Here you receive the request object with the data of your list of versions
function theme_updater_custom_parser($data, $id, $url, $version) {
	if($id == 'MY_THEME_ID_OR_FOLDER') {
		//Here you can parse your response to check if there is a new version
		
		// $data = (array) json_decode($data['body']);
		
		// $versions = array_keys($data);
		
		// $versions = array_pop($versions);
		
		// $data = $data[$versions];
		
		// $url = $data;
		
		//You need to return an object with the new version and the url of the zip to download
		return (object) array(
			'version' => $nova_versao,
			'package' => $url
		);
	}
	return $dados;
}


add_filter('custom_theme_updater_download_url', 'download_custom_url_update', 10, 2);
//Here you receive the url just before the download
function download_custom_url_update($url, $id) {
	if($id == 'MY_THEME_ID_OR_FOLDER') {
		
		//If you need to do some change in the zip url do start the download, here is the place
		
		// $url .= '?hashlogin=123';
		
	}
	return $url;
}


