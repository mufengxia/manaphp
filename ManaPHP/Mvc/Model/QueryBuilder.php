<?php

namespace ManaPHP\Mvc\Model {

    use ManaPHP\Component;
    use ManaPHP\Db\ConditionParser;
    use ManaPHP\Di;
    use ManaPHP\Utility\Text;

    /**
     * ManaPHP\Mvc\Model\Query\Builder
     *
     * Helps to create SQL queries using an OO interface
     *
     *<code>
     *$resultset = $this->modelsManager->createBuilder()
     *   ->from('Robots')
     *   ->join('RobotsParts')
     *   ->limit(20)
     *   ->orderBy('Robots.name')
     *   ->getQuery()
     *   ->execute();
     *</code>
     *
     * @property \ManaPHP\CacheInterface $modelsCache
     */
    class QueryBuilder extends Component implements QueryBuilderInterface
    {
        protected $_columns;

        /**
         * @var array
         */
        protected $_models = [];

        /**
         * @var array
         */
        protected $_joins = [];

        /**
         * @var array
         */
        protected $_conditions = [];

        protected $_group;

        /**
         * @var array
         */
        protected $_having;

        protected $_order;

        /**
         * @var int
         */
        protected $_limit;

        /**
         * @var int
         */
        protected $_offset;

        protected $_forUpdate;

        protected $_sharedLock;

        /**
         * @var array
         */
        protected $_bind = [];

        /**
         * @var bool
         */
        protected $_distinct;

        protected $_hiddenParamNumber;

        protected $_union = [];

        /**
         * \ManaPHP\Mvc\Model\Query\Builder constructor
         *
         *<code>
         * $params = array(
         *    'models'     => array('Users'),
         *    'columns'    => array('id', 'name', 'status'),
         *    'conditions' => array(
         *        array(
         *            "created > :min: AND created < :max:",
         *            array("min" => '2013-01-01',   'max' => '2015-01-01'),
         *            array("min" => PDO::PARAM_STR, 'max' => PDO::PARAM_STR),
         *        ),
         *    ),
         *    // or 'conditions' => "created > '2013-01-01' AND created < '2015-01-01'",
         *    'group'      => array('id', 'name'),
         *    'having'     => "name = 'lily'",
         *    'order'      => array('name', 'id'),
         *    'limit'      => 20,
         *    'offset'     => 20,
         *    // or 'limit' => array(20, 20),
         *);
         *$queryBuilder = new \ManaPHP\Mvc\Model\Query\Builder($params);
         *</code>
         *
         * @param array|string $params
         *
         * @throws \ManaPHP\Mvc\Model\Exception
         */
        public function __construct($params = null)
        {
            parent::__construct();

            if (is_string($params)) {
                $params = [$params];
            }

            if (isset($params[0])) {
                $this->_conditions = $params[0];
            } elseif (isset($params['conditions'])) {
                $this->_conditions = $params['conditions'];
            } else {
                $this->_conditions = $params;
                $params = [];
            }

            if (isset($params['bind'])) {
                $this->_bind = array_merge($this->_bind, $params['bind']);
            }

            if (isset($params['distinct'])) {
                $this->_distinct = $params['distinct'];
            }

            if (isset($params['models'])) {
                $this->_models = $params['models'];
            }

            if (isset($params['columns'])) {
                $this->_columns = $params['columns'];
            }

            if (isset($params['joins'])) {
                $this->_joins = $params['joins'];
            }

            if (isset($params['group'])) {
                $this->_group = $params['group'];
            }

            if (isset($params['having'])) {
                $this->_having = $params['having'];
            }

            if (isset($params['order'])) {
                $this->_order = $params['order'];
            }

            if (isset($params['limit'])) {
                if (is_array($params['limit'])) {
                    throw new Exception('limit not support array format: ' . $params['limit']);
                } else {
                    $this->_limit = $params['limit'];
                }
            }

            if (isset($params['offset'])) {
                $this->_offset = $params['offset'];
            }

            if (isset($params['for_update'])) {
                $this->_forUpdate = $params['for_update'];
            }

            if (isset($params['shared_lock'])) {
                $this->_sharedLock = $params['shared_lock'];
            }
        }

        /**
         * Sets SELECT DISTINCT / SELECT ALL flag
         *
         * @param bool $distinct
         *
         * @return static
         */
        public function distinct($distinct)
        {
            $this->_distinct = $distinct;

            return $this;
        }

        /**
         * Sets the columns to be queried
         *
         *<code>
         *    $builder->columns(array('id', 'name'));
         *</code>
         *
         * @param string|array $columns
         *
         * @return static
         */
        public function columns($columns)
        {
            $this->_columns = $columns;

            return $this;
        }

        /**
         * Sets the models who makes part of the query
         *
         *<code>
         *    $builder->from('Robots');
         *    $builder->from(array('Robots', 'RobotsParts'));
         *</code>
         *
         * @param string|array $models
         *
         * @return static
         */
        public function from($models)
        {
            $this->_models = [$models];

            return $this;
        }

        /**
         * Add a model to take part of the query
         *
         *<code>
         *    $builder->addFrom('Robots', 'r');
         *</code>
         *
         * @param string|\ManaPHP\Mvc\Model\QueryBuilderInterface $model
         * @param string                                          $alias
         *
         * @return static
         */
        public function addFrom($model, $alias = null)
        {
            if (is_string($alias)) {
                $this->_models[$alias] = $model;
            } else {
                $this->_models[] = $model;
            }

            return $this;
        }

        /**
         * Adds a join to the query
         *
         *<code>
         *    $builder->join('Robots');
         *    $builder->join('Robots', 'r.id = RobotsParts.robots_id');
         *    $builder->join('Robots', 'r.id = RobotsParts.robots_id', 'r');
         *    $builder->join('Robots', 'r.id = RobotsParts.robots_id', 'r', 'LEFT');
         *</code>
         *
         * @param string|\ManaPHP\Mvc\Model\QueryBuilderInterface $model
         * @param string                                          $conditions
         * @param string                                          $alias
         * @param string                                          $type
         *
         * @return static
         */
        public function join($model, $conditions = null, $alias = null, $type = null)
        {
            $this->_joins[] = [$model, $conditions, $alias, $type];

            return $this;
        }

        /**
         * Adds a INNER join to the query
         *
         *<code>
         *    $builder->innerJoin('Robots');
         *    $builder->innerJoin('Robots', 'r.id = RobotsParts.robots_id');
         *    $builder->innerJoin('Robots', 'r.id = RobotsParts.robots_id', 'r');
         *</code>
         *
         * @param string|\ManaPHP\Mvc\Model\QueryBuilderInterface $model
         * @param string                                          $conditions
         * @param string                                          $alias
         *
         * @return static
         */
        public function innerJoin($model, $conditions = null, $alias = null)
        {
            $this->_joins[] = [$model, $conditions, $alias, 'INNER'];

            return $this;
        }

        /**
         * Adds a LEFT join to the query
         *
         *<code>
         *    $builder->leftJoin('Robots', 'r.id = RobotsParts.robots_id', 'r');
         *</code>
         *
         * @param string|\ManaPHP\Mvc\Model\QueryBuilderInterface $model
         * @param string                                          $conditions
         * @param string                                          $alias
         *
         * @return static
         */
        public function leftJoin($model, $conditions = null, $alias = null)
        {
            $this->_joins[] = [$model, $conditions, $alias, 'LEFT'];

            return $this;
        }

        /**
         * Adds a RIGHT join to the query
         *
         *<code>
         *    $builder->rightJoin('Robots', 'r.id = RobotsParts.robots_id', 'r');
         *</code>
         *
         * @param string|\ManaPHP\Mvc\Model\QueryBuilderInterface $model
         * @param string                                          $conditions
         * @param string                                          $alias
         *
         * @return static
         */
        public function rightJoin($model, $conditions = null, $alias = null)
        {
            $this->_joins[] = [$model, $conditions, $alias, 'RIGHT'];

            return $this;
        }

        /**
         * Sets the query conditions
         *
         *<code>
         *    $builder->where('name = "Peter"');
         *    $builder->where('name = :name: AND id > :id:', array('name' => 'Peter', 'id' => 100));
         *</code>
         *
         * @param string $conditions
         * @param array  $bind
         *
         * @return static
         */
        public function where($conditions, $bind = null)
        {
            return $this->andWhere($conditions, $bind);
        }

        /**
         * Appends a condition to the current conditions using a AND operator
         *
         *<code>
         *    $builder->andWhere('name = "Peter"');
         *    $builder->andWhere('name = :name: AND id > :id:', array('name' => 'Peter', 'id' => 100));
         *</code>
         *
         * @param string $conditions
         * @param array  $bind
         *
         * @return static
         */
        public function andWhere($conditions, $bind = null)
        {
            if (is_scalar($bind)) {
                $conditions = trim($conditions);

                if (!Text::contains($conditions, ' ')) {
                    $conditions .= ' =';
                }

                list($column) = explode(' ', $conditions);
                $column = str_replace('.', '_', $column);
                /** @noinspection CascadeStringReplacementInspection */
                $column = str_replace(['`', '[', ']'], '', $column);

                $conditions = $conditions . ' :' . $column;
                $bind = [$column => $bind];
            }

            $this->_conditions[] = $conditions;

            if ($bind !== null) {
                $this->_bind = array_merge($this->_bind, $bind);
            }

            return $this;
        }

        /**
         * Appends a BETWEEN condition to the current conditions
         *
         *<code>
         *    $builder->betweenWhere('price', 100.25, 200.50);
         *</code>
         *
         * @param string $expr
         * @param mixed  $min
         * @param mixed  $max
         *
         * @return static
         */
        public function betweenWhere($expr, $min, $max)
        {
            $minKey = 'ABP' . $this->_hiddenParamNumber++;
            $maxKey = 'ABP' . $this->_hiddenParamNumber++;

            $this->andWhere("$expr BETWEEN :$minKey AND :$maxKey", [$minKey => $min, $maxKey => $max]);

            return $this;
        }

        /**
         * Appends a NOT BETWEEN condition to the current conditions
         *
         *<code>
         *    $builder->notBetweenWhere('price', 100.25, 200.50);
         *</code>
         *
         * @param string $expr
         * @param mixed  $min
         * @param mixed  $max
         *
         * @return static
         */
        public function notBetweenWhere($expr, $min, $max)
        {
            $minKey = 'ABP' . $this->_hiddenParamNumber++;
            $maxKey = 'ABP' . $this->_hiddenParamNumber++;

            $this->andWhere("$expr NOT BETWEEN :$minKey AND :$maxKey", [$minKey => $min, $maxKey => $max]);

            return $this;
        }

        /**
         * Appends an IN condition to the current conditions
         *
         *<code>
         *    $builder->inWhere('id', [1, 2, 3]);
         *</code>
         *
         * @param string                                         $expr
         * @param array|\ManaPHP\Mvc\Model\QueryBuilderInterface $values
         *
         * @return static
         * @throws \ManaPHP\Mvc\Model\Exception
         */
        public function inWhere($expr, $values)
        {
            if ($values instanceof QueryBuilderInterface) {
                $this->andWhere($expr . ' IN (' . $values->getSql() . ')');
                $this->_bind = array_merge($this->_bind, $values->getBind());
            } else {
                if (count($values) === 0) {
                    $this->andWhere('FALSE');

                    return $this;
                }

                $bind = [];
                $bindKeys = [];

                foreach ($values as $value) {
                    $key = 'ABP' . $this->_hiddenParamNumber++;
                    $bindKeys[] = ":$key";
                    $bind[$key] = $value;
                }

                $this->andWhere($expr . ' IN (' . implode(', ', $bindKeys) . ')', $bind);
            }

            return $this;
        }

        /**
         * Appends a NOT IN condition to the current conditions
         *
         *<code>
         *    $builder->notInWhere('id', [1, 2, 3]);
         *</code>
         *
         * @param string                                         $expr
         * @param array|\ManaPHP\Mvc\Model\QueryBuilderInterface $values
         *
         * @return static
         * @throws \ManaPHP\Mvc\Model\Exception
         */
        public function notInWhere($expr, $values)
        {
            if ($values instanceof QueryBuilderInterface) {
                $this->andWhere($expr . ' NOT IN (' . $values->getSql() . ')');
                $this->_bind = array_merge($this->_bind, $values->getBind());
            } else {
                if (count($values) === 0) {
                    return $this;
                }

                $bind = [];
                $bindKeys = [];

                foreach ($values as $value) {
                    $key = 'ABP' . $this->_hiddenParamNumber++;
                    $bindKeys[] = ':' . $key;
                    $bind[$key] = $value;
                }
                $this->andWhere($expr . ' NOT IN (' . implode(', ', $bindKeys) . ')', $bind);
            }
            return $this;
        }

        /**
         * Sets a ORDER BY condition clause
         *
         *<code>
         *    $builder->orderBy('Robots.name');
         *    $builder->orderBy(array('1', 'Robots.name'));
         *</code>
         *
         * @param string $orderBy
         *
         * @return static
         */
        public function orderBy($orderBy)
        {
            $this->_order = $orderBy;

            return $this;
        }

        /**
         * Sets a HAVING condition clause. You need to escape SQL reserved words using [ and ] delimiters
         *
         *<code>
         *    $builder->having('SUM(Robots.price) > 0');
         *</code>
         *
         * @param string $having
         * @param array  $bind
         *
         * @return static
         */
        public function having($having, $bind = null)
        {
            if ($this->_having === null) {
                $this->_having = [$having];
            } else {
                $this->_having[] = $having;
            }

            if ($bind !== null) {
                $this->_bind = array_merge($this->_bind, $bind);
            }

            return $this;
        }

        /**
         * Sets a LIMIT clause, optionally a offset clause
         *
         *<code>
         *    $builder->limit(100);
         *    $builder->limit(100, 20);
         *</code>
         *
         * @param int $limit
         * @param int $offset
         *
         * @return static
         */
        public function limit($limit, $offset = null)
        {
            $this->_limit = $limit;
            if ($offset !== null) {
                $this->_offset = $offset;
            }

            return $this;
        }

        /**
         * @param int $size
         * @param int $current
         *
         * @return static
         */
        public function page($size, $current = null)
        {
            $current = $current ? max(1, $current) : 1;

            $this->_limit = $size;
            $this->_offset = ($current - 1) * $size;

            return $this;
        }

        /**
         * Sets a GROUP BY clause
         *
         *<code>
         *    $builder->groupBy(array('Robots.name'));
         *</code>
         *
         * @param string $group
         *
         * @return static
         */
        public function groupBy($group)
        {
            $this->_group = $group;

            return $this;
        }

        protected function _getUnionSql()
        {
            $unions = [];

            /**
             * @var \ManaPHP\Mvc\Model\QueryBuilder $builder
             */
            foreach ($this->_union['builders'] as $builder) {
                $unions[] = '(' . $builder->getSql() . ')';

                /** @noinspection SlowArrayOperationsInLoopInspection */
                $this->_bind = array_merge($this->_bind, $builder->getBind());
            }

            $sql = implode(' ' . $this->_union['type'] . ' ', $unions);

            /**
             * Process order clause
             */
            if ($this->_order !== null) {
                if (is_array($this->_order)) {
                    $sql .= ' ORDER BY ' . implode(', ', $this->_order);
                } else {
                    $sql .= ' ORDER BY ' . $this->_order;
                }
            }

            /**
             * Process limit parameters
             */
            if ($this->_limit !== null) {
                $limit = $this->_limit;
                if (is_int($limit) || (is_string($limit) && ((string)((int)$limit))) === $limit) {
                    $sql .= ' LIMIT ' . $limit;
                } else {
                    throw new Exception('limit is invalid: ' . $limit);
                }
            }

            $this->_models[] = $builder->getModels()[0];

            return $sql;
        }

        /**
         * Returns a SQL statement built based on the builder parameters
         *
         * @return string
         * @throws \ManaPHP\Mvc\Model\Exception|\ManaPHP\Db\ConditionParser\Exception
         */
        public function getSql()
        {
            if (count($this->_union) !== 0) {
                return $this->_getUnionSql();
            }

            if (count($this->_models) === 0) {
                throw new Exception('At least one model is required to build the query');
            }

            /**
             * Generate SQL for SELECT
             */
            $sql = 'SELECT ';

            /**
             * Generate SQL for DISTINCT
             */
            if ($this->_distinct) {
                $sql .= 'DISTINCT ';
            }

            /**
             * Generate SQL for columns
             */
            if ($this->_columns !== null) {
                if (is_array($this->_columns)) {
                    $sql .= implode(', ', $this->_columns);
                } else {
                    $sql .= preg_replace('/(\s+)/', ' ', $this->_columns);
                }
            } else {
                if (count($this->_models) === 1) {
                    $sql .= '*';
                } else {
                    $selectedColumns = [];
                    foreach ($this->_models as $alias => $model) {
                        if (is_int($alias)) {
                            $selectedColumns[] = '[' . $model . '].*';
                        } else {
                            $selectedColumns[] = '`' . $alias . '`.*';
                        }
                    }
                    $sql .= implode(', ', $selectedColumns);
                }
            }

            /**
             *  generate for FROM
             */
            $selectedModels = [];
            foreach ($this->_models as $alias => $model) {
                if ($model instanceof QueryBuilderInterface) {
                    if (is_int($alias)) {
                        throw new Exception('When using SubQuery, you must assign an alias to it.');
                    }

                    $selectedModels[] = '(' . $model->getSql() . ') AS `' . $alias . '`';
                    /** @noinspection SlowArrayOperationsInLoopInspection */
                    $this->_bind = array_merge($this->_bind, $model->getBind());
                } else {
                    if (is_string($alias)) {
                        $selectedModels[] = '[' . $model . '] AS `' . $alias . '`';
                    } else {
                        $selectedModels[] = '[' . $model . ']';
                    }
                }
            }
            $sql .= ' FROM ' . implode(', ', $selectedModels);

            /**
             *  Join multiple models
             */

            foreach ($this->_joins as $join) {
                list($joinModel, $joinCondition, $joinAlias, $joinType) = $join;
                if ($joinAlias !== null) {
                    $this->_models[$joinAlias] = $joinModel;
                } else {
                    $this->_models[] = $joinModel;
                }

                if ($joinType !== null) {
                    $sql .= ' ' . $joinType;
                }

                if ($joinModel instanceof QueryBuilderInterface) {
                    $sql .= ' JOIN (' . $joinModel->getSql() . ')';
                    /** @noinspection SlowArrayOperationsInLoopInspection */
                    $this->_bind = array_merge($this->_bind, $joinModel->getBind());
                    if ($joinAlias === null) {
                        throw new Exception('When using SubQuery, you must assign an alias to it.');
                    }
                } else {
                    $sql .= ' JOIN [' . $joinModel . ']';
                }

                if ($joinAlias !== null) {
                    $sql .= ' AS `' . $joinAlias . '`';
                }

                if ($joinCondition) {
                    $sql .= ' ON ' . $joinCondition;
                }
            }

            $conditions = (new ConditionParser())->parse($this->_conditions, $conditionBind);
            if ($conditions !== '') {
                $sql .= ' WHERE ' . $conditions;
            }

            $this->_bind = array_merge($this->_bind, $conditionBind);

            /**
             * Process group parameters
             */
            if ($this->_group !== null) {
                $sql .= ' GROUP BY ' . $this->_group;
            }

            /**
             * Process having parameters
             */
            if ($this->_having !== null) {
                if (count($this->_having) === 1) {
                    $sql .= ' HAVING ' . $this->_having[0];
                } else {
                    $sql .= ' HAVING (' . implode(' AND ', $this->_having) . ')';
                }
            }

            /**
             * Process order clause
             */
            if ($this->_order !== null) {
                if (is_array($this->_order)) {
                    $sql .= ' ORDER BY ' . implode(', ', $this->_order);
                } else {
                    $sql .= ' ORDER BY ' . $this->_order;
                }
            }

            /**
             * Process limit parameters
             */
            if ($this->_limit !== null) {
                $limit = $this->_limit;
                if (is_int($limit) || (is_string($limit) && ((string)((int)$limit))) === $limit) {
                    $sql .= ' LIMIT ' . $limit;
                } else {
                    throw new Exception('limit is invalid: ' . $limit);
                }
            }

            if ($this->_offset !== null) {
                $offset = $this->_offset;
                if (is_int($offset) || (is_string($offset) && ((string)((int)$offset))) === $offset) {
                    $sql .= ' OFFSET ' . $offset;
                } else {
                    throw new Exception('offset is invalid: ' . $offset);
                }
            }

            //compatible with other SQL syntax
            $replaces = [];
            foreach ($this->_bind as $key => $value) {
                $replaces[':' . $key . ':'] = ':' . $key;
            }

            $sql = strtr($sql, $replaces);

            foreach ($this->_models as $model) {
                if (!$model instanceof QueryBuilderInterface) {
                    $sql = str_replace('[' . $model . ']', '`' . $this->modelsManager->getModelSource($model) . '`', $sql);
                }
            }

            return $sql;
        }

        public function getBind()
        {
            return $this->_bind;
        }

        /**
         * Set default bind parameters
         *
         * @param array   $bind
         * @param boolean $merge
         *
         * @return static
         */
        public function setBind($bind, $merge = true)
        {
            $this->_bind = $merge ? array_merge($this->_bind, $bind) : $bind;

            return $this;
        }

        /**
         * @return array
         */
        public function getModels()
        {
            return $this->_models;
        }

        /**
         * @param array $cacheOptions
         *
         * @return array
         * @throws \ManaPHP\Mvc\Model\Exception|\ManaPHP\Db\ConditionParser\Exception|\ManaPHP\Di\Exception
         */
        public function execute($cacheOptions = null)
        {
            $sql = $this->getSql();

            if ($cacheOptions !== null) {
                if (!is_array($cacheOptions)) {
                    $cacheOptions = ['ttl' => $cacheOptions];
                }

                if (!isset($cacheOptions['key'])) {
                    $cacheOptions['key'] = 'Models/' . $sql . serialize($this->_bind);
                }

                $result = $this->modelsCache->get($cacheOptions['key']);
                if ($result !== false) {
                    return $result;
                }
            }

            try {
                $result = $this->modelsManager
                    ->getReadConnection(end($this->_models))
                    ->fetchAll($sql, $this->_bind);
            } catch (\Exception $e) {
                throw new Exception($e->getMessage() . ':' . $sql);
            }

            if ($cacheOptions !== null) {
                $this->modelsCache->set($cacheOptions['key'], $result, $cacheOptions['ttl']);
            }

            return $result;
        }

        /**
         * @param int $rowCount
         *
         * @return static
         * @throws \ManaPHP\Mvc\Model\Exception|\ManaPHP\Db\ConditionParser\Exception|\ManaPHP\Di\Exception
         */
        protected function _getTotalRows(&$rowCount)
        {
            if (count($this->_union) !== 0) {
                throw new Exception('Union query is not support to get total rows');
            }

            $this->_columns = 'COUNT(*) as row_count';
            $this->_limit = null;
            $this->_offset = null;

            $sql = $this->getSql();

            try {
                $result = $this->modelsManager
                    ->getReadConnection(end($this->_models))
                    ->fetchOne($sql, $this->_bind);

                /** @noinspection CallableParameterUseCaseInTypeContextInspection */
                $rowCount = $result['row_count'];
            } catch (\Exception $e) {
                throw new Exception($e->getMessage() . ':' . $sql);
            }

            return $this;
        }

        /**build the query and execute it.
         *
         * @param int   $totalRows
         * @param array $cacheOptions
         *
         * @return array
         * @throws \ManaPHP\Mvc\Model\Exception|\ManaPHP\Db\ConditionParser\Exception|\ManaPHP\Di\Exception
         */
        public function executeEx(&$totalRows, $cacheOptions = null)
        {
            $copy = clone $this;

            $sql = $this->getSql();

            if ($cacheOptions !== null) {
                if (!is_array($cacheOptions)) {
                    $cacheOptions = ['ttl' => $cacheOptions];
                }

                if (!isset($cacheOptions['key'])) {
                    $cacheOptions['key'] = 'Models/' . $sql . serialize($this->_bind);
                }

                $result = $this->modelsCache->get($cacheOptions['key']);

                if ($result !== false) {
                    /** @noinspection CallableParameterUseCaseInTypeContextInspection */
                    $totalRows = $result['totalRows'];
                    return $result['data'];
                }
            }

            try {
                $result = $this->modelsManager
                    ->getReadConnection(end($this->_models))
                    ->fetchAll($sql, $this->_bind);
            } catch (\Exception $e) {
                throw new Exception($e->getMessage() . ':' . $sql);
            }

            if (!$this->_limit) {
                $totalRows = count($result);
            } else {
                if (count($result) % $this->_limit === 0) {
                    $copy->_getTotalRows($totalRows);
                } else {
                    $totalRows = $this->_offset + count($result, $cacheOptions);
                }
            }

            if ($cacheOptions !== null) {
                $this->modelsCache->set($cacheOptions['key'], ['data' => $result, 'totalRows' => $totalRows], $cacheOptions['ttl']);
            }

            return $result;
        }

        /**
         * @param \ManaPHP\Mvc\Model\QueryBuilderInterface[] $builders
         *
         * @return static
         */
        public function unionAll($builders)
        {
            $this->_union = ['type' => 'UNION ALL', 'builders' => $builders];

            return $this;
        }

        /**
         * @param \ManaPHP\Mvc\Model\QueryBuilderInterface[] $builders
         *
         * @return static
         */
        public function unionDistinct($builders)
        {
            $this->_union = ['type' => 'UNION DISTINCT', 'builders' => $builders];

            return $this;
        }
    }
}
