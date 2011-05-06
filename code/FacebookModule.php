<?php
class FacebookModule {
	//The facebook graph_api id of the application
	protected static $application_id = false;
	//API key of the application
	private static $api_key = false;
	//The application secret key
	private static $app_secret = false;
	/*The token for managing pages of a particular account.
	This can be used to get the actual access token of page.*/
	private static $page_account_token = false;
	/* The Graph API ID of the page to be managed. */
	private static $page_id = false;
	/* If true facebook module is enabled */
	private static $enabled = false;

	/**
	 * @return Facebook Returns a facebook application object from the facebook PHP SDK.
	 */
	public static function getApp() {
		//check for required config variables.
		if (empty(self::$application_id) || empty(self::$app_secret)) {
			throw new Exception('The facebook module is missing configuration for application_id and app_secret. These need to be set in the sites config file.');
		}

		$config = array(
			'appId' => self::$application_id,
			'secret' => self::$app_secret
		);

		//create a new Facebook Application object.
		$fb_app = new Facebook($config);

		return $fb_app;
	}

	public static function getAuthorizationCode() {
		$site_config = SiteConfig::current_site_config();
	$auth_code = $site_config->facebook_account_authorization_code;
		if(empty($auth_code)) {
			throw new Exception('An authorization code was not found.
				The administrator of the website needs to set this by logging into facebook and visiting :'
					.FacebookModule_Controller::getAuthorizeUrl());
		}
		return $auth_code;
	}
	/**
	 * @$authorization_code string The authorization code returned by the facebook url callback.
	 *
	 * If the user presses Allow, your app is authorized. The OAuth Dialog will redirect (via HTTP 302) the user's browser to the URL you passed in the redirect_uri parameter with an authorization code:
	 * e.g. http://YOUR_URL?code=A_CODE_GENERATED_BY_SERVER
	 *
	 * @return string A token which allows graph API access to the facebook page.
	 */
	private static function getAccountAccessToken() {
		$fb_app = self::getApp();
		$authorization_code = self::getAuthorizationCode();
		$params = array(
			'client_id' => self::$application_id,
			'client_secret' => self::$app_secret,
			'code' => $authorization_code,
			'redirect_uri' => FacebookModule_Controller::getRedirectUri(),
		);
		/*
		* If your app is successfully authenticated
		* and the authorization code from the user is valid,
		* the authorization server will return the access token
		*
		* https://graph.facebook.com/oauth/access_token?
		* client_id=YOUR_APP_ID&redirect_uri=YOUR_URL&
		* client_secret=YOUR_APP_SECRET&code=THE_CODE_FROM_ABOVE
		*/
		$url = 'https://graph.facebook.com/oauth/access_token?';
		foreach($params as $key => &$param) {
			$url .= $key.'='.$param.'&';
		}
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);

		curl_setopt_array($ch, Facebook::$CURL_OPTS);
		$response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		if($httpCode > 200)

		$response = $fb_app->api('/oauth/access_token', 'GET', $params);

		$matches = array();
		if(preg_match('/access_token\=/',$response)) {
			$token = preg_replace('/access_token\=/', '', $response);
		}
		
		return $token;
	}
	
	/**
	 * @return string A token which allows graph API access to the facebook page.
	 */
	private static function getPageToken() {
		$fb_app = self::getApp();
		
		$params = array('access_token' => self::getAccountAccessToken(),
			'scope' => 'publish_stream, offline_access');
		$response = $fb_app->api('/me/accounts','GET',$params);

		foreach($response['data'] as &$graph_object) {
			if($graph_object['id'] == self::$page_id) {
				return $graph_object['access_token'];
			}
		}
		throw new Exception('No page found for the facebook current page id.');
		return false;

	}

	/**
	 *
	 * @param string $string The content of the wall post, usually a url of short message.
	 * @param string $params an array of values for the facebook post. Arguments can be: message, picture, link, name, caption, description, source
	 * @return bool
	 */
	public static function updateStatus($params) {
		if(!self::$enabled) {
			return false;
		}
		$fb_app = self::getApp();
		$params['access_token'] = self::getPageToken();

		$response = $fb_app->api('/'.self::$page_id.'/feed','POST', $params);
		if(isset($response['id']) && !empty($response['id']))
			return true;
		else
			return false;
	}

	/**
	 *
	 * @return string the Graph API Id of the application
	 */
	function getAppId() {
		return self::$application_id;
	}
	/**
	 * @param string $api_key The api key of the application.
	 */
	public static function set_api_key($api_key) {
		self::$api_key = $api_key;
	}
	/**
	 * @param string $app_id The Graph API ID of the application.
	 */
	public static function set_application_id($app_id) {
		self::$application_id = $app_id;
	}

	/**
	 * @param string $app_secret Secret key of the facebook application. Used in request signatures.
	 */
	public static function set_app_secret($app_secret) {
		self::$app_secret = $app_secret;
	}

	/**
	 * @param string $page_account_token The token from the pages account.
	 * To get this token use the following URL:
	 * https://www.facebook.com/dialog/oauth?client_id=YOUR_APP_ID&redirect_uri=YOUR_CALLBACK_URL&scope=manage_pages,publish_stream,offline_access&response_type=token
	 *
	 */
	public static function set_page_token($page_account_token) {
		self::$page_account_token = $page_account_token;
	}

	/**
	 *  Enable the module 
	 */
	public static function enable() {
		self::$enabled = true;
	}

	public static function disable() {
		self::$enabled = false;
	}
	/**
	 * @param string $page_id Graph API ID of the page.
	 */
	public static function set_page_id($page_id) {
		self::$page_id = $page_id;
	}
}

class FacebookModule_Controller extends Page_Controller {
	public static function getRedirectUri() {
		$redirect_uri = 'http://'.$_SERVER['HTTP_HOST'].'/FacebookModule_Controller/setAccountAuthorization';
		return $redirect_uri;
	}

	/*
	 * Visiting the url for this method will send the user
	 * to authorize the facebook application for their pages.
	 */
	function authorizeAccess() {
		$url = self::getAuthorizeUrl();
		Director::redirect($url);
	}

	public static function getAuthorizeUrl() {
		if(!Permission::check('ADMIN')) {
			Director::redirect('/admin/');
		}
		/*
		 * TODO Avoid requesting permission to publish on the users wall
		 * by making a second request requesting publish permission for the actual page.
		 */
		$perms = 'manage_pages,offline_access,publish_stream';
		$redirect_uri = self::getRedirectUri();
		$url = 'https://www.facebook.com/dialog/oauth?client_id='.FacebookModule::getAppId()
			.'&redirect_uri='.$redirect_uri.'&scope='.$perms;
		return $url;
	}

	function testPost() {
		FacebookModule::updateStatus('http://google.com');
	}

	function setAccountAuthorization() {
		$url_parts = parse_url($_SERVER['HTTP_REFERER']);
		if(!preg_match('/facebook\.com/',$url_parts['host'])) {
			throw new Exception('Request blocked. Http referer is not facebook.com');
		}

		$authorization_code = $_GET['code'];

		//save to db
		$site_config = SiteConfig::current_site_config();
		$site_config->facebook_account_authorization_code = $authorization_code;
		$site_config->write();
		Director::redirect('/admin');
	}
}


class FacebookModuleSiteConfig extends DataObjectDecorator {
    function extraStatics() {
        return array(
            'db' => array(
                'facebook_account_authorization_code' => 'text'
			)
		);
	}


	public function updateCMSFields(FieldSet &$fields) {
        $fields->addFieldToTab("Root.Main", new LiteralField('authorize_facebook_app', '<div class="field"><div class="middleColumn"><label class="left" for="Form_EditForm_Theme">Facebook API</label><a href="'.FacebookModule_Controller::getAuthorizeUrl().'">Click to authorize this website with facebook. This will allow updates to be posted on your wall.</a></div></div>'));
	}
}
