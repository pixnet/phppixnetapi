<?php

class PixAPI
{
    const REQUEST_TOKEN_URL = 'http://emma.pixnet.cc/oauth/request_token';
    const ACCESS_TOKEN_URL = 'http://emma.pixnet.cc/oauth/access_token';

    protected $_consumer_key;
    protected $_consumer_secret;

    protected $_request_auth_url = null;
    protected $_request_expire = null;
    protected $_request_callback_url = null;

    protected $_token = null;
    protected $_secret = null;

    public function user_get_account()
    {
	return json_decode($this->_http('http://emma.pixnet.cc/account'));
    }

    public function blog_get_categories()
    {
	return json_decode($this->_http('http://emma.pixnet.cc/blog/categories'));
    }

    public function blog_add_category($name, $description)
    {
	return json_decode($this->_http('http://emma.pixnet.cc/blog/categories', array('post_params' => array('name' => $name, 'description' => $description))));
    }

    public function blog_edit_category($id, $name, $description)
    {
	return json_decode($this->_http('http://emma.pixnet.cc/blog/categories/' . intval($id), array('post_params' => array('name' => $name, 'description' => $description))));
    }

    public function blog_delete_category($id)
    {
	return json_decode($this->_http('http://emma.pixnet.cc/blog/categories/' . intval($id), array('method' => 'delete')));
    }

    public function blog_get_articles($page = 1, $per_page = 100, $category_id = null)
    {
	return json_decode($this->_http('http://emma.pixnet.cc/blog/articles', array('get_params' => array('page' => $page, 'per_page' => $per_page, 'category_id' => $category_id))));
    }

    public function blog_get_article($article_id)
    {
	return json_decode($this->_http('http://emma.pixnet.cc/blog/articles/' . intval($article_id)));
    }

    public function blog_add_article($title, $body, $options = array())
    {
	$params = array('title' => $title, 'body' => $body);
	return json_decode($this->_http('http://emma.pixnet.cc/blog/articles', array('post_params' => array_merge($params, $options))));

    }

    public function blog_delete_article($article_id)
    {
	return json_decode($this->_http('http://emma.pixnet.cc/blog/articles/' . intval($article_id), array('method' => 'delete')));
    }

    public function __construct($consumer_key, $consumer_secret)
    {
	$this->_consumer_key = $consumer_key;
	$this->_consumer_secret = $consumer_secret;
    }

    public function setToken($token, $secret)
    {
	$this->_token = $token;
	$this->_secret = $secret;
    }

    public function setRequestCallback($callback_url)
    {
	$this->_request_callback_url = $callback_url;
    }

    protected function _get_request_token()
    {
	if (!is_null($this->_request_expire) and time() < $this->_request_expire) {
	    return;
	}

	if (is_null($this->_request_callback_url)) {
	    $message = $this->_http(self::REQUEST_TOKEN_URL);
	} else {
	    $message = $this->_http(self::REQUEST_TOKEN_URL, array('oauth_params' => array('oauth_callback' => $this->_request_callback_url)));
	}
	$args = array();
	parse_str($message, $args);

	$this->_token = $args['oauth_token'];
	$this->_secret = $args['oauth_token_secret'];
	$this->_request_expire = time() + $args['oauth_expires_in'];
	$this->_request_auth_url = $args['xoauth_request_auth_url'];
    }

    public function getAuthURL($callback_url = null)
    {
	if ($callback_url != $this->_request_callback_url) {
	    $this->_request_expire = null;
	    $this->_request_callback_url = $callback_url;
	}
	$this->_get_request_token();
	return $this->_request_auth_url;
    }

    public function getAccessToken($verifier_token)
    {
	$message = $this->_http(self::ACCESS_TOKEN_URL, array('oauth_params' => array('oauth_verifier' => $verifier_token)));
	$args = array();
	parse_str($message, $args);

	$this->_token = $args['oauth_token'];
	$this->_secret = $args['oauth_token_secret'];

	return array($this->_token, $this->_secret);
    }

    public function getRequestTokenPair()
    {
	$this->_get_request_token();
	return array($this->_token, $this->_secret);
    }

    /**
     * _http 
     * 
     * @param mixed $url 
     * @param array $options 
     *		method: get/post/delete, 
     *		get_params: array(), 
     *		post_params: array(), 
     *		files:array(),
     *		oauth_params: array()
     * @access protected
     * @return void
     */
    protected function _http($url, $options = array())
    {
	// Oauth 認證部分
	$oauth_args = array();
	$oauth_args['oauth_version'] = '1.0';
	$oauth_args['oauth_nonce'] = md5(uniqid());
	$oauth_args['oauth_timestamp'] = time();
	$oauth_args['oauth_consumer_key'] = $this->_consumer_key;
	if (!is_null($this->_token)) {
	    $oauth_args['oauth_token'] = $this->_token;
	}
	$oauth_args['oauth_signature_method'] = 'HMAC-SHA1';

	if (isset($options['oauth_params'])) {
	    foreach ($options['oauth_params'] as $key => $value) {
		$oauth_args[$key] = $value;
	    }
	}

	// METHOD 部分
	$parts = array();
	if (isset($options['method'])) {
	    $parts[] = strtoupper($options['method']);
	} elseif (isset($options['post_params']) or isset($options['files'])) {
	    $parts[] = 'POST';
	} else {
	    $parts[] = 'GET';
	}
	$parts[] = urlencode($url);

	// 如果有指定 get_params, 直接補在網址後面
	if (isset($options['get_params'])) {
	    if (false !== strpos('?', $url)) {
		$url .= '&';
	    } else {
		$url .= '?';
	    }
	    $url .= http_build_query($options['get_params']);
	}

	if (isset($options['post_params'])) {
	    foreach ($options['post_params'] as $key => $value) {
		if (is_null($value)) unset($options['post_params'][$key]);
	    }
	}

	if (isset($options['get_params'])) {
	    foreach ($options['get_params'] as $key => $value) {
		if (is_null($value)) unset($options['get_params'][$key]);
	    }
	}
	// 參數部分
	$args = isset($options['post_params']) ? array_merge($options['post_params'], $oauth_args) : $oauth_args;
	$args = isset($options['get_params']) ? array_merge($options['get_params'], $args) : $args;
	ksort($args);
	$args_parts = array();
	foreach ($args as $key => $value) {
	    $args_parts[] = urlencode($key) . '=' . urlencode($value);
	}
	$parts[] = urlencode(implode('&', $args_parts));

	$base_string = implode('&', $parts);

	// 產生 oauth_signature
	$key_parts = array(
	    urlencode($this->_consumer_secret),
	    is_null($this->_secret) ? '' : urlencode($this->_secret)
	);
	$key = implode('&', $key_parts);
	$oauth_args['oauth_signature'] = base64_encode(hash_hmac('sha1', $base_string, $key, true));

	$oauth_header = 'OAuth ';
	$first = true;
	foreach ($oauth_args as $k => $v) {
	    if (substr($k, 0, 5) != "oauth") continue;
	    $oauth_header .= ($first ? '' : ',') . urlencode($k) . '="' . urlencode($v) . '"';
	    $first = false;
	}

	if (isset($options['method'])) {
	    $method_map = array('get' => HttpRequest::METH_GET, 'head' => HttpRequest::METH_HEAD, 'post' => HttpRequest::METH_POST, 'put' => HttpRequest::METH_PUT, 'delete' => HttpRequest::METH_DELETE);

	    $request = new HttpRequest($url, $method_map[strtolower($options['method'])]);
	} elseif (isset($options['post_params']) or isset($options['files'])) {
	    $request = new HttpRequest($url, HttpRequest::METH_POST);
	} else {
	    $request = new HttpRequest($url, HttpRequest::METH_GET);
	}
	$request->setHeaders(array('Authorization' => $oauth_header));
	if (isset($options['post_params'])) {
	    $request->setPostFields($options['post_params']);
	}
	if (isset($options['files'])) {
	    foreach ($options['files'] as $name => $file) {
		$request->addPostFile($name, $file);
	    }
	}
	$message = $request->send();
	return $message->getBody();
    }
}

