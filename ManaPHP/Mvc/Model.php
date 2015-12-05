<?php 

namespace ManaPHP\Mvc {

	use ManaPHP\Di;
	use ManaPHP\Mvc\Model\Exception;
	use \ManaPHP\Mvc\Model\ResultInterface;
	use \ManaPHP\Di\InjectionAwareInterface;

	/**
	 * ManaPHP\Mvc\Model
	 *
	 * <p>ManaPHP\Mvc\Model connects business objects and database tables to create
	 * a persistable domain model where logic and data are presented in one wrapping.
	 * It‘s an implementation of the object-relational mapping (ORM).</p>
	 *
	 * <p>A model represents the information (data) of the application and the rules to manipulate that data.
	 * Models are primarily used for managing the rules of interaction with a corresponding database table.
	 * In most cases, each table in your database will correspond to one model in your application.
	 * The bulk of your application’s business logic will be concentrated in the models.</p>
	 *
	 * <p>ManaPHP\Mvc\Model is the first ORM written in C-language for PHP, giving to developers high performance
	 * when interacting with databases while is also easy to use.</p>
	 *
	 * <code>
	 *
	 * $robot = new Robots();
	 * $robot->type = 'mechanical';
	 * $robot->name = 'Astro Boy';
	 * $robot->year = 1952;
	 * if ($robot->save() == false) {
	 *  echo "Umh, We can store robots: ";
	 *  foreach ($robot->getMessages() as $message) {
	 *    echo $message;
	 *  }
	 * } else {
	 *  echo "Great, a new robot was saved successfully!";
	 * }
	 * </code>
	 *
	 */
	
	abstract class Model implements ModelInterface, ResultInterface, InjectionAwareInterface, \Serializable {

		const DIRTY_STATE_PERSISTENT = 0;

		const DIRTY_STATE_TRANSIENT = 1;

		const DIRTY_STATE_DETACHED = 2;

		protected $_dependencyInjector;

		/**
		 * @var \ManaPHP\Mvc\Model\ManagerInterface
		 */
		protected $_modelsManager;

		protected $_modelsMetaData;

		protected $_dirtyState;

		protected $_uniqueKey;

		protected $_uniqueParams;

		protected $_uniqueTypes;


		/**
		 * @var array
		 */
		protected $_snapshot;

		/**
		 * \ManaPHP\Mvc\Model constructor
		 *
		 * @param \ManaPHP\DiInterface $dependencyInjector
		 * @param \ManaPHP\Mvc\Model\ManagerInterface $modelsManager
		 */
		final public function __construct($dependencyInjector=null, $modelsManager=null){
			$this->_dependencyInjector=$dependencyInjector===null?Di::getDefault():$dependencyInjector;
			$this->_modelsManager=$modelsManager ===null?$this->_dependencyInjector->getShared('modelsManager'):$modelsManager;

			/**
			 * The manager always initializes the object
			 */
			$this->_modelsManager->initialize($this);

			/**
			 * This allows the developer to execute initialization stuff every time an instance is created
			 */
			if(method_exists($this,'onConstruct')){
				$this->onConstruct();
			}
		}


		/**
		 * Sets the dependency injection container
		 *
		 * @param \ManaPHP\DiInterface $dependencyInjector
		 * @return \ManaPHP\Mvc\ModelInterface
		 */
		public function setDI($dependencyInjector){
			$this->_dependencyInjector =$dependencyInjector;
			return $this;
		}


		/**
		 * Returns the dependency injection container
		 *
		 * @return \ManaPHP\DiInterface
		 */
		public function getDI(){
			return $this->_dependencyInjector;
		}


		/**
		 * Sets a custom events manager
		 *
		 * @param \ManaPHP\Events\ManagerInterface $eventsManager
		 * @return \ManaPHP\Mvc\ModelInterface
		 */
		protected function setEventsManager($eventsManager){
			$this->_modelsManager->setCustomEventsManager($this,$eventsManager);
			return $this;
		}


		/**
		 * Returns the custom events manager
		 *
		 * @return \ManaPHP\Events\ManagerInterface
		 */
		protected function getEventsManager(){
			return $this->_modelsManager->getCustomEventsManager($this);
		}


		/**
		 * Returns the models meta-data service related to the entity instance
		 *
		 * @return \ManaPHP\Mvc\Model\MetaDataInterface
		 */
		public function getModelsMetaData(){
			if(!is_object($this->_modelsMetaData)){
				$this->_modelsMetaData =$this->_dependencyInjector->getShared('modelsMetadata');
			}

			return $this->_modelsMetaData;
		}


		/**
		 * Returns the models manager related to the entity instance
		 *
		 * @return \ManaPHP\Mvc\Model\ManagerInterface
		 */
		public function getModelsManager(){
			return $this->_modelsManager;
		}


		/**
		 * Sets table name which model should be mapped
		 *
		 * @param string $source
		 * @return $this
		 */
		protected function setSource($source){
			$this->_modelsManager->setModelSource($this,$source);
			return $this;
		}


		/**
		 * Returns table name mapped in the model
		 *
		 * @return string
		 */
		public function getSource(){
			return $this->_modelsManager->getModelSource($this);
		}


		/**
		 * Sets schema name where table mapped is located
		 *
		 * @param string $schema
		 * @return $this
		 */
		protected function setSchema($schema){
			$this->_modelsManager->setModelSchema($this,$schema);
			return $this;
		}


		/**
		 * Returns schema name where table mapped is located
		 *
		 * @return string
		 */
		public function getSchema(){
			return $this->_modelsManager->getModelSchema($this);
		}


		/**
		 * Sets the DependencyInjection connection service name
		 *
		 * @param string $connectionService
		 * @return $this
		 */
		public function setConnectionService($connectionService){
			$this->_modelsManager->setConnectionService($this,$connectionService);
			return $this;
		}


		/**
		 * Sets the DependencyInjection connection service name used to read data
		 *
		 * @param string $connectionService
		 * @return $this
		 */
		public function setReadConnectionService($connectionService){
			$this->_modelsManager->setReadConnectionService($this,$connectionService);
			return $this;
		}


		/**
		 * Sets the DependencyInjection connection service name used to write data
		 *
		 * @param string $connectionService
		 * @return $this
		 */
		public function setWriteConnectionService($connectionService){
			$this->_modelsManager->setWriteConnectionService($this,$connectionService);
			return $this;
		}


		/**
		 * Returns the DependencyInjection connection service name used to read data related the model
		 *
		 * @return string
		 */
		public function getReadConnectionService(){
			return $this->_modelsManager->getReadConnectionService($this);
		}


		/**
		 * Returns the DependencyInjection connection service name used to write data related to the model
		 *
		 * @return string
		 */
		public function getWriteConnectionService(){
			return $this->_modelsManager->getWriteConnectionService($this);
		}


		/**
		 * Sets the dirty state of the object using one of the DIRTY_STATE_* constants
		 *
		 * @param int $dirtyState
		 * @return $this
		 */
		public function setDirtyState($dirtyState){
			$this->_dirtyState =$dirtyState;
			return $this;
		}


		/**
		 * Returns one of the DIRTY_STATE_* constants telling if the record exists in the database or not
		 *
		 * @return int
		 */
		public function getDirtyState(){
			return $this->_dirtyState;
		}


		/**
		 * Gets the connection used to read data for the model
		 *
		 * @return \ManaPHP\Db\AdapterInterface
		 */
		public function getReadConnection(){
			return $this->_modelsManager->getReadConnection($this);
		}


		/**
		 * Gets the connection used to write data to the model
		 *
		 * @return \ManaPHP\Db\AdapterInterface
		 */
		public function getWriteConnection(){
			return $this->_modelsManager->getWriteConnection($this);
		}


		/**
		 * Assigns values to a model from an array
		 *
		 *<code>
		 *$robot->assign(array(
		 *  'type' => 'mechanical',
		 *  'name' => 'Astro Boy',
		 *  'year' => 1952
		 *));
		 *</code>
		 *
		 * @param array $data
		 * @param array $columnMap
		 * @param array $whiteList
		 * @return $this
		 * @throws \ManaPHP\Mvc\Model\Exception
		 */
		public function assign($data, $columnMap=null,$whiteList=null){
			if(is_array($columnMap)){
				$dataMapped=[];
				foreach($data as $k=>$v){
					if(isset($columnMap[$k])){
						$dataMapped[$columnMap[$k]]=$v;
					}
				}
			}else{
				$dataMapped=$data;
			}

			if(count($dataMapped) ===0){
				return $this;
			}

			$metaData =$this->getModelsMetaData();
			foreach($metaData->getAttributes($this) as $attributeField){
				if(isset($dataMapped[$attributeField])){
					if(is_array($whiteList) &&!in_array($attributeField,$whiteList,true)){
						continue;
					}
					$this->{$attributeField}=$dataMapped[$attributeField];
				}
			}

			return $this;
		}


		/**
		 * Allows to query a set of records that match the specified conditions
		 *
		 * <code>
		 *
		 * //How many robots are there?
		 * $robots = Robots::find();
		 * echo "There are ", count($robots), "\n";
		 *
		 * //How many mechanical robots are there?
		 * $robots = Robots::find("type='mechanical'");
		 * echo "There are ", count($robots), "\n";
		 *
		 * //Get and print virtual robots ordered by name
		 * $robots = Robots::find(array("type='virtual'", "order" => "name"));
		 * foreach ($robots as $robot) {
		 *	   echo $robot->name, "\n";
		 * }
		 *
		 * //Get first 100 virtual robots ordered by name
		 * $robots = Robots::find(array("type='virtual'", "order" => "name", "limit" => 100));
		 * foreach ($robots as $robot) {
		 *	   echo $robot->name, "\n";
		 * }
		 * </code>
		 *
		 * @param 	array $parameters
		 * @return  \ManaPHP\Mvc\Model\ResultsetInterface
		 */
		public static function find($parameters=null){
			/**
			 * @var \ManaPHP\Mvc\Model\ManagerInterface $modelsManager
			 */
			$dependencyInjector=Di::getDefault();
			$modelsManager =$dependencyInjector->getShared('modelsManager');

			if(is_array($parameters)){
				$params =$parameters;
			}elseif($parameters===null){
				$params=[];
			}else{
				$params=[];
				$params[]=$parameters;
			}

			$builder =$modelsManager->createBuilder($params);
			$builder->from(get_called_class());

			$query =$builder->getQuery();

			if(isset($params['bind'])){
				if(is_array($params['bind'])){
					$query->setBindParams($params['bind'],true);
				}
			}

			if(isset($params['cache'])){
				$query->cache($params['cache']);
			}

			$resultset=$query->execute();

			if(is_object($resultset)){
				if(isset($params['hydration'])){
					$resultset->setHydrateMode($params['hydration']);
				}
			}

			return $resultset;
		}


		/**
		 * Allows to query the first record that match the specified conditions
		 *
		 * <code>
		 *
		 * //What's the first robot in robots table?
		 * $robot = Robots::findFirst();
		 * echo "The robot name is ", $robot->name;
		 *
		 * //What's the first mechanical robot in robots table?
		 * $robot = Robots::findFirst("type='mechanical'");
		 * echo "The first mechanical robot name is ", $robot->name;
		 *
		 * //Get first virtual robot ordered by name
		 * $robot = Robots::findFirst(array("type='virtual'", "order" => "name"));
		 * echo "The first virtual robot name is ", $robot->name;
		 *
		 * </code>
		 *
		 * @param array $parameters
		 * @return \ManaPHP\Mvc\Model
		 */
		public static function findFirst($parameters=null){
			/**
			 * @var \ManaPHP\Mvc\Model\ManagerInterface $modelsManager
			 */
			$dependencyInjector=Di::getDefault();
			$modelsManager =$dependencyInjector->getShared('modelsManager');

			if(is_array($parameters)){
				$params =$parameters;
			}elseif($parameters===null){
				$params=[];
			}else{
				$params=[];
				$params[]=$parameters;
			}

			$builder =$modelsManager->createBuilder($params);
			$builder->from(get_called_class());
			$builder->limit(1);

			$query =$builder->getQuery();

			if(isset($params['bind'])){
				if(is_array($params['bind'])){
					$query->setBindParams($params['bind'],true);
				}
			}

			if(isset($params['cache'])){
				$query->cache($params['cache']);
			}

			$query->setUniqueRow(true);

			return $query->execute();
		}


		/**
		 * Create a criteria for a specific model
		 *
		 * @param \ManaPHP\DiInterface $dependencyInjector
		 * @return \ManaPHP\Mvc\Model\Criteria
		 */
		public static function query($dependencyInjector=null){
			if(!is_object($dependencyInjector)){
				$dependencyInjector=Di::getDefault();
			}

			$criteria=$dependencyInjector->get('ManaPHP\Mvc\Model\Criteria');
			$criteria->setModelName(get_called_class());

			return $criteria;
		}


		/**
		 * Checks if the current record already exists or not
		 *
		 * @param \ManaPHP\Mvc\Model\MetadataInterface $metaData
		 * @param \ManaPHP\Db\AdapterInterface $connection
		 * @return boolean
		 */
		protected function _exists($metaData,$connection){
			if($this->_uniqueKey ===null){
				$primaryKeys=$metaData->getPrimaryKeyAttributes($this);
				if(count($primaryKeys) ===0){
					return false;
				}

				$numberEmpty=0;
				$uniqueParams=[];
				$wherePk=[];

				foreach($primaryKeys as $attributeField){
					$value =null;
					if(isset($this->{$attributeField})){
						$value=$this->{$attributeField};

						if($value ===null ||$value===''){
							$numberEmpty++;
						}

						$uniqueParams[]=$value;
					}else{
						$uniqueParams[]=null;
						$numberEmpty++;
					}

					$wherePk[]=$connection->escapeIdentifier($attributeField).' = ?';
				}

				if($numberEmpty ===count($primaryKeys)){
					return false;
				}

				$joinWhere=implode(' AND ', $wherePk);

				$this->_uniqueKey=$joinWhere;
				$this->_uniqueParams=$uniqueParams;
			}

			if($this->_dirtyState ===self::DIRTY_STATE_PERSISTENT){
				return true;
			}

			$schema =$this->getSchema();
			$source=$this->getSource();
			if($schema !=='' &&$schema !==null){
				$table =[$schema, $source];
			}else{
				$table=$source;
			}

			$num =$connection->fetchOne('SELECT COUNT(*) as rowcount'.
						' FROM '. $connection->escapeIdentifier($table).
						' WHERE '. $this->_uniqueKey,
							null, $this->_uniqueParams);

			if(isset($num['rowcount'])){
				$this->_dirtyState =self::DIRTY_STATE_PERSISTENT;
				return true;
			}else{
				$this->_dirtyState =self::DIRTY_STATE_TRANSIENT;
				return false;
			}
		}


		/**
		 * Generate a PHQL SELECT statement for an aggregate
		 *
		 * @param string $function
		 * @param string $alias
		 * @param array $parameters
		 * @return \ManaPHP\Mvc\Model\ResultsetInterface
		 */
		protected static function _groupResult($function, $alias, $parameters){
			$dependencyInjector =Di::getDefault();
			/**
 			 * @var \ManaPHP\Mvc\Model\ManagerInterface $modelsManager
			 */
			$modelsManager =$dependencyInjector->getShared('modelsManager');

			if(is_array($parameters)){
				$params =$parameters;
			}elseif($parameters===null){
				$params=[];
			}else{
				$params=[];
				$params[]=$parameters;
			}

			if(!isset($params['column'])){
				$params['column']='*';
			}

			if(isset($params['distinct'])){
				$columns =$function .'(DISTINCT '.$params['distinct'].') AS '.$alias;
			}else{
				if(isset($params['group'])){
					$columns =$params['group'].', '.$function.'('.$params['group'].') AS '  .$alias;
				}else{
					$columns =$function.'('.$params['column'].') AS '.$alias;
				}
			}

			$builder =$modelsManager->createBuilder($params);
			$builder->columns($columns);
			$builder->from(get_called_class());

			$query =$builder->getQuery();

			if(isset($params['cache'])){
				$query->cache($params['cache']);
			}

			if(isset($params['bind'])){
				$resultset =$query->execute([$params['bind']]);
			}else{
				$resultset=$query->execute();
			}

			/**
			 * Return the full resultset if the query is grouped
			 */
			if(isset($params['group'])){
				return $resultset;
			}


			/**
			 * Return only the value in the first result
			 */

			$firstRow=$resultset->getFirst();
			return $firstRow->{$alias};
		}


		/**
		 * Allows to count how many records match the specified conditions
		 *
		 * <code>
		 *
		 * //How many robots are there?
		 * $number = Robots::count();
		 * echo "There are ", $number, "\n";
		 *
		 * //How many mechanical robots are there?
		 * $number = Robots::count("type='mechanical'");
		 * echo "There are ", $number, " mechanical robots\n";
		 *
		 * </code>
		 *
		 * @param array $parameters
		 * @return int
		 */
		public static function count($parameters=null){
			$result =self::_groupResult('COUNT','rowcount',$parameters);
			if(is_string($result)){
				return (int)$result;
			}else{
				return $result;
			}
		}


		/**
		 * Allows to calculate a summatory on a column that match the specified conditions
		 *
		 * <code>
		 *
		 * //How much are all robots?
		 * $sum = Robots::sum(array('column' => 'price'));
		 * echo "The total price of robots is ", $sum, "\n";
		 *
		 * //How much are mechanical robots?
		 * $sum = Robots::sum(array("type='mechanical'", 'column' => 'price'));
		 * echo "The total price of mechanical robots is  ", $sum, "\n";
		 *
		 * </code>
		 *
		 * @param array $parameters
		 * @return double
		 */
		public static function sum($parameters=null){
			return self::_groupResult('SUM','sumatory',$parameters);
		}


		/**
		 * Allows to get the maximum value of a column that match the specified conditions
		 *
		 * <code>
		 *
		 * //What is the maximum robot id?
		 * $id = Robots::maximum(array('column' => 'id'));
		 * echo "The maximum robot id is: ", $id, "\n";
		 *
		 * //What is the maximum id of mechanical robots?
		 * $sum = Robots::maximum(array("type='mechanical'", 'column' => 'id'));
		 * echo "The maximum robot id of mechanical robots is ", $id, "\n";
		 *
		 * </code>
		 *
		 * @param array $parameters
		 * @return mixed
		 */
		public static function maximum($parameters=null){
			return self::_groupResult('MAX','maximum',$parameters);
		}


		/**
		 * Allows to get the minimum value of a column that match the specified conditions
		 *
		 * <code>
		 *
		 * //What is the minimum robot id?
		 * $id = Robots::minimum(array('column' => 'id'));
		 * echo "The minimum robot id is: ", $id;
		 *
		 * //What is the minimum id of mechanical robots?
		 * $sum = Robots::minimum(array("type='mechanical'", 'column' => 'id'));
		 * echo "The minimum robot id of mechanical robots is ", $id;
		 *
		 * </code>
		 *
		 * @param array $parameters
		 * @return mixed
		 */
		public static function minimum($parameters=null){
			return self::_groupResult('MIN', 'minimum', $parameters);
		}


		/**
		 * Allows to calculate the average value on a column matching the specified conditions
		 *
		 * <code>
		 *
		 * //What's the average price of robots?
		 * $average = Robots::average(array('column' => 'price'));
		 * echo "The average price is ", $average, "\n";
		 *
		 * //What's the average price of mechanical robots?
		 * $average = Robots::average(array("type='mechanical'", 'column' => 'price'));
		 * echo "The average price of mechanical robots is ", $average, "\n";
		 *
		 * </code>
		 *
		 * @param array $parameters
		 * @return double
		 */
		public static function average($parameters=null){
			return self::_groupResult('AVG','average',$parameters);
		}


		/**
		 * Fires an event, implicitly calls behaviors and listeners in the events manager are notified
		 *
		 * @param string $eventName
		 * @return boolean
		 */
		public function fireEvent($eventName){
			if(method_exists($this,$eventName)){
				$this->{$eventName}();
			}

			return $this->_modelsManager->notifyEvent($eventName,$this);
		}


		/**
		 * Fires an internal event that cancels the operation
		 *
		 * @param string $eventName
		 * @return bool
		 */
		public function fireEventCancel($eventName){
			if(method_exists($this,$eventName)){
				return $this->{$eventName}();
			}else{
				return null;
			}
		}
		/**
		 * Executes internal hooks before save a record
		 *
		 * @param boolean $exists
		 * @return boolean
		 */
		protected function _preSave($exists){
			if($this->fireEventCancel('beforeSave') ===false){
				return false;
			}

			if($exists){
				if($this->fireEventCancel('beforeUpdate') ===false){
					return false;
				}
			}else{
				if($this->fireEventCancel('beforeCreate') ===false){
					return false;
				}
			}

			return true;
		}

		/**
		 * Executes internal events after save a record
		 *
		 * @param boolean $exists
		 */
		protected function _postSave($exists){
			if($exists){
				$this->fireEvent('afterUpdate');
			}else{
				$this->fireEvent('afterCreate');
			}

			$this->fireEvent('afterSave');
		}


		/**
		 * Sends a pre-build INSERT SQL statement to the relational database system
		 *
		 * @param \ManaPHP\Mvc\Model\MetadataInterface $metaData
		 * @param \ManaPHP\Db\AdapterInterface $connection
		 * @return boolean
		 * @throws \ManaPHP\Mvc\Model\Exception
		 */
		protected function _doLowInsert($metaData,$connection){
			$schema=$this->getSchema();
			$source =$this->getSource();
			if($schema !==null){
				$table=[$schema,$source];
			}else{
				$table =$source;
			}
			$attributes=$metaData->getAttributes($this);

			$fields=[];
			$values=[];
			foreach($attributes as $attributeField){
				if($this->{$attributeField} !==null){
					$fields[]=$attributeField;
					$values[]=$this->{$attributeField};
				}
			}

			if(count($fields) ===0){
				throw new Exception('Unable to insert into ' . $table . ' without data');
			}

			$success =$connection->insert($table,$values,$fields);
			if($success ===true){
				$this->refresh();
			}

			return $success;
		}


		/**
		 * Sends a pre-build UPDATE SQL statement to the relational database system
		 *
		 * @param \ManaPHP\Mvc\Model\MetadataInterface $metaData
		 * @param \ManaPHP\Db\AdapterInterface $connection
		 * @return boolean
		 */
		protected function _doLowUpdate($metaData,$connection){
			$schema=$this->getSchema();
			$source =$this->getSource();
			if($schema !==null){
				$table=[$schema,$source];
			}else{
				$table =$source;
			}

			$nonPrimary=$metaData->getNonPrimaryKeyAttributes($this);

			$fields=[];
			$values=[];
			foreach($nonPrimary as $attributeField){
				if($this->{$attributeField} !==null){
					if(!is_array($this->_snapshot)
					||$this->{$attributeField} !==$this->_snapshot[$attributeField]){
							$fields[]=$attributeField;
							$values[]=$this->{$attributeField};
					}
				}
			}

			if(count($fields) ===0){
				return true;
			}

			$uniqueKey=[];
			$uniqueParams=[];
			return $connection->update($table,$fields,$values,
				['conditions'=>$uniqueKey,
					'bind'=>$uniqueParams]);
		}


		/**
		 * Inserts or updates a model instance. Returning true on success or false otherwise.
		 *
		 *<code>
		 *	//Creating a new robot
		 *	$robot = new Robots();
		 *	$robot->type = 'mechanical';
		 *	$robot->name = 'Astro Boy';
		 *	$robot->year = 1952;
		 *	$robot->save();
		 *
		 *	//Updating a robot name
		 *	$robot = Robots::findFirst("id=100");
		 *	$robot->name = "Biomass";
		 *	$robot->save();
		 *</code>
		 *
		 * @param array $data
		 * @param array $whiteList
		 * @return boolean
		 * @throws \ManaPHP\Mvc\Model\Exception
		 */
		public function save($data=null, $whiteList=null){
			if(is_array($data) && count($data) >0){
				$this->assign($data,null,$whiteList);
			}

			$metaData =$this->getModelsMetaData();

			$exists =$this->_exists($metaData, $this->getReadConnection());
			if($this->_preSave($exists) ===false){
				throw new Exception('save failed');
			}

			if($exists){
				$success =$this->_doLowUpdate($metaData,$this->getWriteConnection());
			}else{
				$success=$this->_doLowInsert($metaData,$this->getWriteConnection());
			}

			if($success){
				$this->_dirtyState =self::DIRTY_STATE_PERSISTENT;
			}

			return $success;
		}


		/**
		 * Inserts a model instance. If the instance already exists in the persistance it will throw an exception
		 * Returning true on success or false otherwise.
		 *
		 *<code>
		 *	//Creating a new robot
		 *	$robot = new Robots();
		 *	$robot->type = 'mechanical';
		 *	$robot->name = 'Astro Boy';
		 *	$robot->year = 1952;
		 *	$robot->create();
		 *
		 *  //Passing an array to create
		 *  $robot = new Robots();
		 *  $robot->create(array(
		 *      'type' => 'mechanical',
		 *      'name' => 'Astroy Boy',
		 *      'year' => 1952
		 *  ));
		 *</code>
		 *
		 * @param array $data
		 * @param array $whiteList
		 * @return boolean
		 * @throws \ManaPHP\Mvc\Model\Exception
		 */
		public function create($data=null, $whiteList=null){
			$metaData =$this->getModelsMetaData();

			if($this->_exists($metaData,$this->getReadConnection())){
				throw new Exception('Record cannot be created because it already exists');
			}

			return $this->save($data,$whiteList);
		}


		/**
		 * Updates a model instance. If the instance does n't exist in the persistance it will throw an exception
		 * Returning true on success or false otherwise.
		 *
		 *<code>
		 *	//Updating a robot name
		 *	$robot = Robots::findFirst("id=100");
		 *	$robot->name = "Biomass";
		 *	$robot->update();
		 *</code>
		 *
		 * @param array $data
		 * @param array $whiteList
		 * @return boolean
		 * @throws \ManaPHP\Mvc\Model\Exception
		 */
		public function update($data=null, $whiteList=null){
			$metaData =$this->getModelsMetaData();
			if(!$this->_exists($metaData, $this->getReadConnection())){
				throw new Exception('Record cannot be updated because it does not exist');
			}

			return $this->save($data, $whiteList);
		}


		/**
		 * Deletes a model instance. Returning true on success or false otherwise.
		 *
		 * <code>
		 *$robot = Robots::findFirst("id=100");
		 *$robot->delete();
		 *
		 *foreach (Robots::find("type = 'mechanical'") as $robot) {
		 *   $robot->delete();
		 *}
		 * </code>
		 *
		 * @return boolean
		 * @throws \ManaPHP\Mvc\Model\Exception
		 */
		public function delete(){
			$metaData =$this->getModelsMetaData();
			$writeConnection=$this->getWriteConnection();
			$primaryKeys=$metaData->getPrimaryKeyAttributes($this);

			if(count($primaryKeys) ===0){
				throw new Exception('A primary key must be defined in the model in order to perform the operation');
			}

			if($this->fireEventCancel('beforeDelete') ===false){
				return false;
			}

			$bindParams=[];
			$conditions=[];
			foreach($primaryKeys as $attributeField){

				/**
				 * If the attribute is currently set in the object add it to the conditions
				 */
				if(!isset($this->{$attributeField})){
					throw new Exception("Cannot delete the record because the primary key attribute: '" . $attributeField . "' wasn't set");
				}

				$bindKey=':'.$attributeField;
				$bindParams[$bindKey]=$this->{$attributeField};
				$conditions =$writeConnection->escapeIdentifier($attributeField).' ='.$bindKey;
			}

			$schema =$this->getSchema();
			$source =$this->getSource();
			if($schema !==null){
				$table=[$schema,$source];
			}else{
				$table=$source;
			}

			$success =$writeConnection->delete($table,implode(' AND ',$conditions),$bindParams);

			if($success ===true){
				$this->fireEvent('afterDelete');
			}

			return $success;
		}

		/**
		 * Serializes the object ignoring connections, services, related objects or static properties
		 *
		 * @return string
		 */
		public function serialize(){
			return serialize($this->toArray());
		}


		/**
		 * Unserializes the object from a serialized string
		 *
		 * @param string $data
		 */
		public function unserialize($data){
			$attributes=unserialize($data);
			if(is_array($attributes)){
				$this->_modelsManager=Di::getDefault()->getShared('modelsManager');

				$this->_modelsManager->initialize($this);

				foreach($attributes as $k=>$v){
					$this->{$k}=$v;
				}
			}
		}

		/**
		 * Returns a simple representation of the object that can be used with var_dump
		 *
		 *<code>
		 * var_dump($robot->dump());
		 *</code>
		 *
		 * @return array
		 */
		public function dump(){
			return get_object_vars($this);
		}


		/**
		 * Returns the instance as an array representation
		 *
		 *<code>
		 * print_r($robot->toArray());
		 *</code>
		 *
		 * @param array $columns
		 * @return array
		 */
		public function toArray($columns=null){
			$data =[];
			$metaData =$this->getModelsMetaData();

			foreach($metaData->getAttributes($this) as $attributeField){
				if(is_array($columns) && !in_array($attributeField, $columns,true)){
					continue;
				}

				$data[$attributeField]=isset($this->{$attributeField})?$this->{$attributeField}:null;
			}

			return $data;
		}


		/**
		 * Enables/disables options in the ORM
		 * Available options:
		 * events                — Enables/Disables globally the internal events
		 *
		 * @param array $options
		 */
		public static function setup($options){ }
	}
}
