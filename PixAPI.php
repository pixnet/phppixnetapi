<?php

/**
 * PixAPIException 
 * 
 * @uses Exception
 * @author Shang-Rung Wang <ronnywang@gmail.com> 
 */
class PixAPIException extends Exception
{
}

/**
 * PixAPI 
 * 
 * @author Shang-Rung Wang <ronnywang@gmail.com> 
 */
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

    /**
     * user_get_account 取得已登入使用者的資料
     * 
     * @access public
     * @return array 使用者資料
     */
    public function user_get_account()
    {
	return json_decode($this->_http('http://emma.pixnet.cc/account'));
    }

    /**
     * blog_get_categories 取得目前部落格的分類
     * 
     * @access public
     * @return array 各部落格分類資料
     */
    public function blog_get_categories()
    {
	return json_decode($this->_http('http://emma.pixnet.cc/blog/categories'));
    }

    /**
     * blog_add_category 增加部落格分類
     * 
     * @param string $name 分類名稱
     * @param string $description 分類描述
     * @access public
     * @return int 部落格分類 id
     */
    public function blog_add_category($name, $description)
    {
	return json_decode($this->_http('http://emma.pixnet.cc/blog/categories', array('post_params' => array('name' => $name, 'description' => $description))))->category->id;
    }

    /**
     * blog_edit_category 編輯部落格分類(需要modify權限)
     * 
     * @param int $id 部落格分類 id
     * @param string $name 分類名稱
     * @param string $description 分類描述
     * @access public
     * @return void
     */
    public function blog_edit_category($id, $name, $description)
    {
	return json_decode($this->_http('http://emma.pixnet.cc/blog/categories/' . intval($id), array('post_params' => array('name' => $name, 'description' => $description))));
    }

    /**
     * blog_delete_category 刪除部落格分類(需要modify權限)
     * 
     * @param int $id 
     * @access public
     * @return void
     */
    public function blog_delete_category($id)
    {
	return json_decode($this->_http('http://emma.pixnet.cc/blog/categories/' . intval($id), array('method' => 'delete')));
    }

    /**
     * blog_get_articles 取得部落格文章列表
     * 
     * @param int $page 第幾頁，預設為第一頁
     * @param int $per_page 一頁有幾筆？預設 100 筆
     * @param int/null $category_id 部落格分類，null時表示全部
     * @access public
     * @return array 文章資料
     */
    public function blog_get_articles($page = 1, $per_page = 100, $category_id = null)
    {
	return json_decode($this->_http('http://emma.pixnet.cc/blog/articles', array('get_params' => array('page' => $page, 'per_page' => $per_page, 'category_id' => $category_id))));
    }

    /**
     * blog_get_article 取得單篇文章資料
     * 
     * @param int $article_id 
     * @access public
     * @return array 單篇文章資料 
     */
    public function blog_get_article($article_id)
    {
	return json_decode($this->_http('http://emma.pixnet.cc/blog/articles/' . intval($article_id)));
    }

    /**
     * blog_add_article 部落格新增文章
     * 
     * @param string $title 標題
     * @param string $body 內容
     * @param array $options   status 文章狀態, 1表示草稿, 2表示公開, 4表示隱藏
     *                         public_at => 公開時間, 這個表示文章的發表時間, 以 timestamp 的方式輸入, 預設為現在時間
     *                         category_id => 個人分類, 這個值是數字, 請先到 BlogCategory 裡找自己的分類列表, 預設是0
     *                         site_category_id => 站台分類, 這個值是數字, 預設是0
     *                         use_nl2br => 是否使用 nl2br, 預設是否
     *                         comment_perm => 可迴響權限. 0: 關閉迴響, 1: 開放所有人迴響, 2: 僅開放會員迴響, 3:開放好友迴響. 預設會看 Blog 整體設定
     *                         comment_hidden => 預設迴響狀態. 0: 公開, 1: 強制隱藏. 預設為0(公開)
     * @access public
     * @return int
     */
    public function blog_add_article($title, $body, $options = array())
    {
	$params = array('title' => $title, 'body' => $body);
	return json_decode($this->_http('http://emma.pixnet.cc/blog/articles', array('post_params' => array_merge($params, $options))))->article->id;

    }

    /**
     * blog_delete_article 刪除一篇部落格文章(需要modify權限)
     * 
     * @param int $article_id 
     * @access public
     * @return void
     */
    public function blog_delete_article($article_id)
    {
	return json_decode($this->_http('http://emma.pixnet.cc/blog/articles/' . intval($article_id), array('method' => 'delete')));
    }

    /**
     * __construct 
     * 
     * @param string $consumer_key 
     * @param string $consumer_secret 
     * @access public
     * @return void
     */
    public function __construct($consumer_key, $consumer_secret)
    {
	$this->_consumer_key = $consumer_key;
	$this->_consumer_secret = $consumer_secret;
    }

    /**
     * setToken 設定 token 和 secret ，在取得 access token 和操作需要驗證的動作前都要做這個
     * 
     * @param string $token 
     * @param string $secret 
     * @access public
     * @return void
     */
    public function setToken($token, $secret)
    {
	$this->_token = $token;
	$this->_secret = $secret;
    }

    /**
     * setRequestCallback 指定在 Authorization 之後要導回的網址
     * 
     * @param string $callback_url 
     * @access public
     * @return void
     */
    public function setRequestCallback($callback_url)
    {
	$this->_request_callback_url = $callback_url;
    }

    /**
     * _get_request_token 取得 Request Token ，若是已經取得過而且沒有過期的話就不會再取一次
     * 
     * @access protected
     * @return void
     */
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

    /**
     * getAuthURL 取得 Authorization 網址，
     * 此 function 會自動作 setToken($request_token, $request_token_secret) 的動作
     * 
     * @param string/null $callback_url 可以指定 Authorization 完後導到哪裡
     * @access public
     * @return string Authorization 網址
     */
    public function getAuthURL($callback_url = null)
    {
	if ($callback_url != $this->_request_callback_url) {
	    $this->_request_expire = null;
	    $this->_request_callback_url = $callback_url;
	}
	$this->_get_request_token();
	return $this->_request_auth_url;
    }

    /**
     * getAccessToken 取得 Access Token ，在使用此 function 前需要先呼叫 setToken($request_token, $request_token_secret)
     * 
     * @param string $verifier_token 在 Authorization 頁面成功認證後，回傳的 verifier_token
     * @access public
     * @return array(
     *            $access_token
     *            $access_token_secret
     *         )
     */
    public function getAccessToken($verifier_token)
    {
	$message = $this->_http(self::ACCESS_TOKEN_URL, array('oauth_params' => array('oauth_verifier' => $verifier_token)));
	$args = array();
	parse_str($message, $args);

	$this->_token = $args['oauth_token'];
	$this->_secret = $args['oauth_token_secret'];

	return array($this->_token, $this->_secret);
    }

    /**
     * getRequestTokenPair 取得 Request Token 
     * 此 function 會自動作 setToken($request_token, $request_token_secret) 的動作
     * 
     * @access public
     * @return void
     */
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
	if ($message->getResponseCode() !== 200) {
	    throw new PixAPIException($message->getBody(), $message->getResponseCode());
	}
	return $message->getBody();
    }
}

