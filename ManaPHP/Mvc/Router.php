<?php

namespace ManaPHP\Mvc {

    use ManaPHP\Component;
    use ManaPHP\Mvc\Router\Exception;
    use ManaPHP\Mvc\Router\Group;

    /**
     * ManaPHP\Mvc\Router
     *
     * <p>ManaPHP\Mvc\Router is the standard framework router. Routing is the
     * process of taking a URI endpoint (that part of the URI which comes after the base URL) and
     * decomposing it into parameters to determine which module, controller, and
     * action of that controller should receive the request</p>
     *
     *<code>
     *
     *    $router = new ManaPHP\Mvc\Router();
     *
     *  $router->add(
     *        "/documentation/{chapter}/{name}.{type:[a-z]+}",
     *        array(
     *            "controller" => "documentation",
     *            "action"     => "show"
     *        )
     *    );
     *
     *    $router->handle();
     *
     *    echo $router->getControllerName();
     *</code>
     *
     */
    class Router extends Component implements RouterInterface
    {
        /**
         * @var string
         */
        protected $_module = null;

        /**
         * @var string
         */
        protected $_controller = null;

        /**
         * @var string
         */
        protected $_action = null;

        /**
         * @var array
         */
        protected $_params = [];

        /**
         * @var \ManaPHP\Mvc\Router\GroupInterface[]
         */
        protected $_groups=[];

        /**
         * @var \ManaPHP\Mvc\Router\GroupInterface
         */
        protected $_defaultGroup=null;
        /**
         * @var \ManaPHP\Mvc\Router\RouteInterface
         */
        protected $_matchedRoute = null;

        /**
         * @var boolean
         */
        protected $_wasMatched = false;

        /**
         * @var string
         */
        protected $_defaultController = 'Index';

        /**
         * @var string
         */
        protected $_defaultAction = 'index';

        /**
         * @var array
         */
        protected $_defaultParams = [];

        /**
         * @var boolean
         */
        protected $_removeExtraSlashes = false;


        /**
         * ManaPHP\Mvc\Router constructor
         *
         * @param boolean $defaultRoutes
         * @throws \ManaPHP\Mvc\Router\Exception
         */
        public function __construct($defaultRoutes = true)
        {
            if ($defaultRoutes) {
                $group=new Group();
				
                $group->add('/');
                $group->add('/:controller/?');
                $group->add('/:controller/:action/?');
                $group->add('/:controller/:action/:params');

                $this->_defaultGroup =$group;
            }
        }

       
        /**
         * Get rewrite info. This info is read from $_GET['_url']. This returns '/' if the rewrite information cannot be read
         *
         * @return string
         * @throws \ManaPHP\Mvc\Router\Exception
         */
        public function getRewriteUri()
        {
            if (!isset($_GET['_url'])) {
                if ($_SERVER['SCRIPT_NAME'] === '/index.php') {
                    $real_url = '/';
                } else {
                    throw new Exception('--$_GET["_url"] not set, may be .htaccess has incorrect config.');
                }
            } else {
                $real_url = $_GET['_url'];
            }

            return $real_url === '' ? '/' : $real_url;
        }


        /**
         * Set whether router must remove the extra slashes in the handled routes
         *
         * @param boolean $remove
         * @return static
         */
        public function removeExtraSlashes($remove)
        {
            $this->_removeExtraSlashes = $remove;
            return $this;
        }


        /**
         * @param string $uri
         * @param \ManaPHP\Mvc\Router\RouteInterface[] $routes
         * @param array $parts
         * @return bool
         */
        protected function _findMatchedRoute($uri, $routes, &$parts){
            $parts = [];

            /**
             * Routes are traversed in reversed order
             */
            for ($i = count($routes) - 1; $i >= 0; $i--) {
                $route = $routes[$i];

                if ($route->isMatched($uri, $matches)) {
                    $paths = $route->getPaths();
                    $parts = $paths;

                    if (is_array($matches)) {
                        foreach ($matches as $k => $v) {
                            if (is_string($k)) {
                                $paths[$k] = $v;
                            }
                        }
                        $parts = $paths;

                        foreach ($paths as $part => $position) {
                            if (is_int($position) && isset($matches[$position])) {
                                $parts[$part] = $matches[$position];
                            }
                        }
                    }

                    return true;
                }
            }

            return false;
        }

        /**
         * Handles routing information received from the rewrite engine
         *
         *<code>
         * //Read the info from the rewrite engine
         * $router->handle();
         *
         * //Manually passing an URL
         * $router->handle('/posts/edit/1');
         *</code>
         *
         * @param string $uri
         * @return boolean
         * @throws \ManaPHP\Mvc\Router\Exception
         */
        public function handle($uri = null)
        {
            $uri = ($uri === null || $uri === '') ? $this->getRewriteUri() : $uri;

            if ($this->_removeExtraSlashes && $uri !== '/') {
                $handle_uri = rtrim($uri, '/');
            } else {
                $handle_uri = $uri;
            }

            $this->fireEvent('router:beforeCheckRoutes', $this);

            $module=null;
            $route_found=false;
            foreach($this->_groups as $module=>$group){
                $route_found=$this->_findMatchedRoute($handle_uri,$group->getRoutes(),$parts);
                if($route_found){
                    break;
                }
            }

            if(!$route_found){
                $module=null;
                $route_found=$this->_findMatchedRoute($handle_uri,$this->_defaultGroup->getRoutes(),$parts);
            }
            $this->_wasMatched = $route_found;

            if ($route_found) {

                $this->_module=$module;
                $this->_controller = $this->_defaultController;
                $this->_action = $this->_defaultAction;
                $this->_params = $this->_defaultParams;

                if (isset($parts['module'])) {
                    if (!is_numeric($parts['module'])) {
                        $this->_module = $parts['module'];
                    }
                    unset($parts['module']);
                }

                if (isset($parts['controller'])) {
                    if (!is_numeric($parts['controller'])) {
                        $this->_controller = $parts['controller'];
                    }
                    unset($parts['controller']);
                }

                if (isset($parts['action'])) {
                    if (!is_numeric($parts['action'])) {
                        $this->_action = $parts['action'];
                    }
                    unset($parts['action']);
                }

                $params = [];
                if (isset($parts['params'])) {
                    if (is_string($parts['params'])) {
                        $params_str = trim($parts['params'], '/');
                        if ($params_str !== '') {
                            $params = explode('/', $params_str);
                        }
                    }

                    unset($parts['params']);
                }

                $this->_params = array_merge($params, $parts);
            }

            $this->fireEvent('router:afterCheckRoutes', $this);

            return $route_found;
        }


        /**
         * Mounts a group of routes in the router
         *
         * @param string $module
         * @param \ManaPHP\Mvc\Router\GroupInterface $group
         * @return static
         */
        public function mount($module, $group)
        {
            $this->_groups[$module] = $group;

            return $this;
        }


        /**
         * Returns the processed module name
         *
         * @return string
         */
        public function getModuleName()
        {
            return $this->_module;
        }

        /**
         * @param string $str
         * @return string
         */
        protected function _camelize($str)
        {
            if(strpos($str,'_') !==false){
                $parts = explode('_', $str);
                foreach ($parts as $k => $v) {
                    $parts[$k] = ucfirst($v);
                }

                return implode('', $parts);
            }else{
               return ucfirst($str);
            }
        }

        /**
         * Returns the processed controller name
         *
         * @return string
         */
        public function getControllerName()
        {
            return $this->_camelize($this->_controller);
        }

        /**
         * Returns the processed action name
         *
         * @return string
         */
        public function getActionName()
        {
            return lcfirst($this->_action);
        }


        /**
         * Returns the processed parameters
         *
         * @return array
         */
        public function getParams()
        {
            return $this->_params;
        }


        /**
         * Returns the route that matches the handled URI
         *
         * @return \ManaPHP\Mvc\Router\RouteInterface
         */
        public function getMatchedRoute()
        {
            return $this->_matchedRoute;
        }

        /**
         * Checks if the router matches any of the defined routes
         *
         * @return bool
         */
        public function wasMatched()
        {
            return $this->_wasMatched;
        }
    }
}
