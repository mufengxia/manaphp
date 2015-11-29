<?php 

namespace ManaPHP\Http {

	/**
	 * ManaPHP\Http\RequestInterface initializer
	 */
	
	interface RequestInterface {

		/**
		 * Gets a variable from the $_REQUEST superglobal applying filters if needed
		 *
		 * @param string $name
		 * @param string|array $filters
		 * @param mixed $defaultValue
 		 * @param boolean $notAllowEmpty
		 * @return mixed
		 */
		public function get($name=null, $filters=null, $defaultValue=null,$notAllowEmpty = false);

		/**
		 * Gets variable from $_GET superglobal applying filters if needed
		 *
		 * @param string $name
		 * @param string|array $filters
		 * @param mixed $defaultValue
		 * @param boolean $notAllowEmpty
		 * @return mixed
		 */
		public function getGet($name=null, $filters=null, $defaultValue=null,$notAllowEmpty = false);


		/**
		 * Gets a variable from the $_POST superglobal applying filters if needed
		 *
		 * @param string $name
		 * @param string|array $filters
		 * @param mixed $defaultValue
		 * @param boolean $notAllowEmpty
		 * @return mixed
		 */
		public function getPost($name=null, $filters=null, $defaultValue=null,$notAllowEmpty = false);


		/**
		 * Gets a variable from put request
		 *
		 *<code>
		 *	$userEmail = $request->getPut("user_email");
		 *
		 *	$userEmail = $request->getPut("user_email", "email");
		 *</code>
		 *
		 * @param string $name
		 * @param string|array $filters
		 * @param mixed $defaultValue
		 * @param boolean $notAllowEmpty
		 * @return mixed
		 */
		public function getPut($name=null, $filters=null, $defaultValue=null,$notAllowEmpty = false);


		/**
		 * Gets variable from $_GET superglobal applying filters if needed
		 *
		 * @param string $name
		 * @param string|array $filters
		 * @param mixed $defaultValue
	 	 * @param boolean $notAllowEmpty
		 * @return mixed
		 */
		public function getQuery($name=null, $filters=null, $defaultValue=null,$notAllowEmpty = false);

		/**
		 * Checks whether $_SERVER superglobal has certain index
		 *
		 * @param string $name
		 * @return boolean
		 */
		public function has($name);


		/**
		 * Checks whether $_GET superglobal has certain index
		 *
		 * @param string $name
		 * @return boolean
		 */
		public function hasGet($name);


		/**
		 * Checks whether $_POST superglobal has certain index
		 *
		 * @param string $name
		 * @return boolean
		 */
		public function hasPost($name);


		/**
		 * Checks whether has certain index
		 *
		 * @param string $name
		 * @return boolean
		 */
		public function hasPut($name);


		/**
		 * Checks whether $_GET superglobal has certain index
		 *
		 * @param string $name
		 * @return boolean
		 */
		public function hasQuery($name);


		/**
		 * Gets HTTP schema (http/https)
		 *
		 * @return string
		 */
		public function getScheme();


		/**
		 * Checks whether request has been made using ajax. Checks if $_SERVER['HTTP_X_REQUESTED_WITH']=='XMLHttpRequest'
		 *
		 * @return boolean
		 */
		public function isAjax();

		/**
		 * Checks whether request has been made using any secure layer
		 *
		 * @return boolean
		 */
		public function isSecureRequest();


		/**
		 * Gets HTTP raw request body
		 *
		 * @return string
		 */
		public function getRawBody();


		/**
		 * Gets most possibly client IPv4 Address. This methods search in $_SERVER['REMOTE_ADDR'] and optionally in $_SERVER['HTTP_X_FORWARDED_FOR']
		 *
		 * @param boolean $trustForwardedHeader
		 * @return string
		 */
		public function getClientAddress($trustForwardedHeader=false);


		/**
		 * Gets HTTP user agent used to made the request
		 *
		 * @return string
		 */
		public function getUserAgent();


		/**
		 * Checks whether HTTP method is POST. if $_SERVER['REQUEST_METHOD']=='POST'
		 *
		 * @return boolean
		 */
		public function isPost();


		/**
		 *
		 * Checks whether HTTP method is GET. if $_SERVER['REQUEST_METHOD']=='GET'
		 *
		 * @return boolean
		 */
		public function isGet();


		/**
		 * Checks whether HTTP method is PUT. if $_SERVER['REQUEST_METHOD']=='PUT'
		 *
		 * @return boolean
		 */
		public function isPut();


		/**
		 * Checks whether HTTP method is HEAD. if $_SERVER['REQUEST_METHOD']=='HEAD'
		 *
		 * @return boolean
		 */
		public function isHead();


		/**
		 * Checks whether HTTP method is DELETE. if $_SERVER['REQUEST_METHOD']=='DELETE'
		 *
		 * @return boolean
		 */
		public function isDelete();


		/**
		 * Checks whether HTTP method is OPTIONS. if $_SERVER['REQUEST_METHOD']=='OPTIONS'
		 *
		 * @return boolean
		 */
		public function isOptions();

		/**
		 * Checks whether HTTP method is PATCH. if $_SERVER['REQUEST_METHOD']=='PATCH'
		 *
		 * @return boolean
		 */
		public function isPatch();

		/**
		 * Checks whether request include attached files
		 *
		 * @param boolean $onlySuccessful
		 * @return boolean
		 */
		public function hasFiles($onlySuccessful=false);


		/**
		 * Gets attached files as \ManaPHP\Http\Request\FileInterface compatible instances
		 *
		 * @param boolean $onlySuccessful
		 * @return \ManaPHP\Http\Request\FileInterface[]
		 */
		public function getUploadedFiles($onlySuccessful=false);


		/**
		 * Gets web page that refers active request. ie: http://www.google.com
		 *
		 * @return string
		 */
		public function getHTTPReferer();
	}
}