<?php
/**
 * This file is part of the PHPLucidFrame library.
 * SchemaManager manages your database schema.
 *
 * @package     PHPLucidFrame\Core
 * @since       PHPLucidFrame v 1.14.0
 * @copyright   Copyright (c), PHPLucidFrame.
 * @author      Sithu K. <cithukyaw@gmail.com>
 * @link        http://phplucidframe.com
 * @license     http://www.opensource.org/licenses/mit-license.php MIT License
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE
 */

namespace LucidFrame\Core;

/**
 * Schema Manager
 */
class SchemaManager
{
    /** @var array The schema definition */
    protected $schema = array();
    /** @var string The database driver; currently it allows "mysql" only */
    private $driver = 'mysql';
    /** @var array The global schema options */
    private $defaultOptions;
    /** @var array The data types for each db driver */
    private static $dataTypes = array(
        'mysql' => array(
            'smallint'  => 'SMALLINT',
            'int'       => 'INT',
            'integer'   => 'INT',
            'bigint'    => 'BIGINT',
            'decimal'   => 'NUMERIC',
            'float'     => 'DOUBLE',
            # For decimal and float
            # length => array(p, s) where p is the precision and s is the scale
            # The precision represents the number of significant digits that are stored for values, and
            # the scale represents the number of digits that can be stored following the decimal point.
            'string'    => 'VARCHAR',
            'char'      => 'CHAR',
            'binary'    => 'VARBINARY',
            'text'      => 'TEXT',
            'blob'      => 'BLOB',
            'array'     => 'TEXT',
            'json'      => 'TEXT',
            # For text, blob, array and json
            # length => tiny, medium or long
            # tiny for TINYTEXT, medium for MEDIUMTEXT, long for LONGTEXT
            # if no length is specified, default to TEXT
            'boolean'   => 'TINYINT', # TINYINT(1)
            'date'      => 'DATE',
            'datetime'  => 'DATETIME',
            'time'      => 'TIME',
        ),
    );
    /** @var array The relational database relationships */
    private static $relationships = array('1:m', 'm:1', 'm:m', '1:1');
    /** @var string The namespace for the database */
    private $dbNamespace = 'default';
    /** @var array The array of generated SQL statements */
    private $sqlStatements = array();

    /**
     * Constructor
     * @param array $schema The array of schema definition
     */
    public function __construct($schema = array())
    {
        $this->defaultOptions = array(
            'timestamps'    => true,
            'constraints'   => true,
            'charset'       => 'utf8',
            'collate'       => 'utf8_general_ci',
            'engine'        => 'InnoDB',
        );

        $this->setSchema($schema);
    }

    /**
     * Setter for the property `schema`
     * @param  array $schema The array of schema definition
     * @return object SchemaManager
     */
    public function setSchema($schema)
    {
        if (!is_array($schema)) {
            $schema = array(
                '_options' => $this->defaultOptions
            );
        }

        $this->schema = $schema;

        return $this;
    }

    /**
     * Getter for the property `schema`
     * @return array The array of schema definition
     */
    public function getSchema()
    {
        return $this->schema;
    }

    /**
     * Setter for the property `driver`
     * Currently driver allows mysql only, that's why this method is private
     * @param  string $driver Database driver
     * @return object SchemaManager
     */
    private function setDriver($driver)
    {
        $this->driver = $driver;

        return $this;
    }

    /**
     * Getter for the property `driver`
     * @return string
     */
    public function getDriver()
    {
        return $this->driver;
    }

    /**
     * Get default field type for primary key
     * @return array Array of field type options
     */
    private function getPKDefaultType()
    {
        return array(
            'type'      => 'int',
            'autoinc'   => true,
            'null'      => false,
            'unsigned'  => true
        );
    }

    /**
     * Get relationship options with defaults
     * @param  array  $relation The relationship options
     * @param  string $fkTable The FK table
     * @return array  The relationship options with defaults
     */
    private function getRelationOptions($relation, $fkTable = '')
    {
        if (!isset($relation['name'])) {
            $relation['name'] = $fkTable.'_id';
        }

        return $relation + array(
            'unique'  => false,
            'default' => null,
            'cascade' => false
        );
    }

    /**
     * Get field statement for CREATE TABLE
     * @param  string   $field        The field name
     * @param  array    $definition   SchemaManager field definition
     * @param  string   $collate      The collation for the field; if it is null, db collation is used
     * @return string   The field statement
     */
    public function getFieldStatement($field, $definition, $collate = null)
    {
        $type = $this->getVendorFieldType($definition);
        if ($type === null) {
            return '';
        }

        $statement = "`{$field}` {$type}";

        $length = $this->getFieldLength($definition);
        if ($length) {
            $statement .= "($length)";
        }

        if (in_array($definition['type'], array('string', 'char', 'text', 'array', 'json'))) {
            # COLLATE for text fields
            $statement .= ' COLLATE ';
            $statement .= $collate ? $collate : $this->schema['_options']['collate'];
        }

        if (isset($definition['unsigned'])) {
            # unsigned
            $statement .= ' unsigned';
        }

        if (isset($definition['null'])) {
            # true: DEFAULT NULL
            # false: NOT NULL
            $statement .= $definition['null'] ? ' DEFAULT NULL' : ' NOT NULL';
        }

        if (isset($definition['default'])) {
            $statement .= sprintf(" DEFAULT '%d'", (int) $definition['default']);
        }

        if (isset($definition['autoinc']) && $definition['autoinc']) {
            # AUTO_INCREMENT
            $statement .= ' AUTO_INCREMENT';
        }

        return $statement;
    }

    /**
     * Get field type
     * @param  array  $definition SchemaManager field definition
     * @return string The underlying db field type
     */
    public function getVendorFieldType(&$definition)
    {
        if (!isset(self::$dataTypes[$this->driver][$definition['type']])) {
            # if no data type is defined
            return null;
        }

        $type = self::$dataTypes[$this->driver][$definition['type']];

        if (in_array($definition['type'], array('text', 'blob', 'array', 'json'))) {
            if (isset($definition['length']) && in_array($definition['length'], array('tiny', 'medium', 'long'))) {
                return strtoupper($definition['length']).$type;
            } else {
                return $definition['type'] == 'blob' ? self::$dataTypes[$this->driver]['blob'] : self::$dataTypes[$this->driver]['text'];
            }
        }

        if ($definition['type'] == 'boolean') {
            # if type is boolean, force unsigned, not null and default 0
            $definition['unsigned'] = true;
            $definition['null']     = false;
            if (!isset($definition['default'])) {
                $definition['default'] = false;
            }
        }

        return $type;
    }

    /**
     * Get field length
     * @param  array    $definition SchemaManager field definition
     * @return integer  The field length
     */
    public function getFieldLength(&$definition)
    {
        $type = $definition['type'];

        if ($type == 'string' || $type == 'char') {
            $length = 255;
        } elseif ($type == 'int' || $type == 'integer') {
            $length = 11;
        } elseif ($type === 'boolean') {
            $length = 1;
        } elseif (in_array($type, array('text', 'blob', 'array', 'json'))) {
            $length = 0;
        } elseif ($type == 'decimal' || $type == 'float') {
            $length = isset($definition['length']) ? $definition['length'] : 0;
            $length = is_array($length) ? "$length[0], $length[1]" : '0, 0';
        } else {
            $length = 0;
        }

        if (isset($definition['length']) && is_numeric($definition['length'])) {
            $length = $definition['length'];
        }

        return $length;
    }

    /**
     * Get foreign key schema definition
     * @param  string $table    The table where the FK field will be added
     * @param  string $fkTable  The reference table name
     * @param  array  $relation The relationship definition
     * @return array Foreign key schema definition
     */
    protected function getFKField($table, $fkTable, $relation)
    {
        $field = $relation['name'];
        $pkFields = $this->schema['_options']['pk'];

        if (isset($pkFields[$fkTable][$field])) {
            $fkField = $pkFields[$fkTable][$field];
        } else {
            $keys = array_keys($pkFields[$fkTable]);
            $firstPKField = array_shift($keys);
            $fkField = $pkFields[$fkTable][$firstPKField];
        }

        if (isset($fkField['autoinc'])) {
            unset($fkField['autoinc']);
        }

        if ($relation['unique']) {
            $fkField['unique'] = true;
        }

        if ($relation['default'] === null) {
            $fkField['null'] = true;
        } else {
            $fkField['default'] = $relation['default'];
            $fkField['null'] = false;
        }

        return $fkField;
    }

    /**
     * Get foreign key constraint definition
     * @param  string $table    The table where the FK field will be added
     * @param  string $fkTable  The reference table name
     * @param  array  $relation The relationship definition
     * @param  array  $schema   The whole schema definition
     * @return array|null Foreign key constraint definition
     */
    protected function getFKConstraint($table, $fkTable, $relation, $schema = array())
    {
        if ($this->schema['_options']['constraints']) {
            $pkFields   = $this->schema['_options']['pk'];
            $field      = $relation['name'];
            $refField   = $field;

            if (!isset($pkFields[$fkTable][$refField])) {
                $refField = 'id';
            }

            if ($relation['cascade'] === true) {
                $cascade = 'CASCADE';
            } elseif ($relation['cascade'] === null) {
                $cascade = 'SET NULL';
            } else {
                $cascade = 'RESTRICT';
            }

            return array(
                'name'              => 'FK_' . strtoupper(_randomCode(15)),
                'fields'            => $field,
                'reference_table'   => $fkTable,
                'reference_fields'  => $refField,
                'on_delete'         => $cascade,
                'on_update'         => 'NO ACTION'
            );
        } else {
            return null;
        }
    }

    /**
     * Process schema
     * @return boolean TRUE for success; FALSE for failure
     */
    private function load()
    {
        $options = $this->getOptions();

        $schema = $this->schema;
        unset($schema['_options']);

        if (count($schema) == 0) {
            return false;
        }

        # Populate primary key fields
        $this->populatePrimaryKeys($schema);
        # Add ManyToMany tables to the schema
        $constraints = $this->populatePivots($schema);

        $pkFields = $this->getPrimaryKeys();

        $sql = array();
        $sql[] = 'SET FOREIGN_KEY_CHECKS=0;';

        # Create each table
        foreach ($schema as $table => $def) {
            $fullTableName = db_table($table); # The full table name with prefix
            $createSql = $this->createTableStatement($table, $schema, $pkFields, $constraints);
            if ($createSql) {
                $sql[] = '--';
                $sql[] = '-- Table structure for table `'.$fullTableName.'`';
                $sql[] = '--';
                $sql[] = "DROP TABLE IF EXISTS `{$fullTableName}`;";
                $sql[] = $createSql;
            }
        }

        # Generate FK constraints
        $constraintSql = $this->createConstraintStatements($constraints);
        if ($constraintSql) {
            $sql = array_merge($sql, $constraintSql);
        }

        $sql[] = 'SET FOREIGN_KEY_CHECKS=1;';

        $this->sqlStatements = $sql;

        $schema['_options'] = $this->schema['_options'];
        $this->schema = $schema;

        return true;
    }

    /**
     * Check if the schema is parsed and fully loaded
     * @return boolean TRUE/FALSE
     */
    public function isLoaded()
    {
        return isset($this->schema['_options']['pk']);
    }

    /**
     * Export the built schema definition into a file
     * @param  string $dbNamespace The namespace for the database
     * @param  boolean $backup Create a backup file or not
     * @return boolean TRUE for success; FALSE for failure
     */
    public function build($dbNamespace = null, $backup = false)
    {
        if (!$this->isLoaded()) {
            $this->load();
        }

        if ($dbNamespace === null) {
            $dbNamespace = $this->dbNamespace;
        }

        $builtSchema = str_replace('  ', '    ', var_export($this->schema, true));
        $builtSchema = preg_replace('/\s+\\n/', "\n", $builtSchema);
        $builtSchema = preg_replace('/=>\\n/', "=>", $builtSchema);
        $builtSchema = preg_replace('/=>\s+/', "=> ", $builtSchema);

        $content = "<?php\n\n";
        $content .= "return ";
        $content .= $builtSchema;
        $content .= ";\n";

        $result = file_put_contents(DB.'build'._DS_.'schema.'.$dbNamespace.'.inc', $content);
        if ($result) {
            if ($backup) {
                copy(DB.'build'._DS_.'schema.'.$dbNamespace.'.inc', DB.'build'._DS_.'~schema.'.$dbNamespace.'.inc');
            }

            return true;
        } else {
            return false;
        }
    }

    /**
     * Import schema to the database
     * @param  string $dbNamespace The namespace for the database
     * @return boolean TRUE for success; FALSE for failure
     */
    public function import($dbNamespace = null)
    {
        if ($dbNamespace === null) {
            $dbNamespace = $this->dbNamespace;
        }

        $this->build($dbNamespace);

        if (!count($this->sqlStatements)) {
            return false;
        }

        if ($this->dbNamespace !== $dbNamespace) {
            db_switch($dbNamespace);
        }

        $error = false;
        foreach ($this->sqlStatements as $sql) {
            if (!db_query($sql)) {
                $error = true;
                break;
            }
        }

        if ($this->dbNamespace !== $dbNamespace) {
            // back to default db
            db_switch($this->dbNamespace);
        }

        $this->build($dbNamespace, true);

        return !$error;
    }

    /**
     * Export sql dump file
     * @param  string $dbNamespace The namespace for the database
     * @return boolean TRUE for success; FALSE for failure
     */
    public function export($dbNamespace = null)
    {
        if ($dbNamespace === null) {
            $dbNamespace = $this->dbNamespace;
        }

        $this->build($dbNamespace);

        if (!count($this->sqlStatements)) {
            return false;
        }

        $dump = "--\n"
            ."-- Generated by PHPLucidFrame "._version()."\n"
            ."-- ".date('r')."\n"
            ."--\n\n"
            .implode("\n", $this->sqlStatements);

        return file_put_contents(DB.'generated'._DS_.'schema.'.$dbNamespace.'.sql', $dump) ? true : false;
    }

    /**
     * Check if the table exists
     * @param  string $table The table name
     * @return boolean TRUE if the table exists, otherwise FALSE
     */
    public function hasTable($table)
    {
        if (!$this->isLoaded()) {
            return false;
        }

        $table = db_table($table);

        return isset($this->schema[$table]);
    }

    /**
     * Check if a field exists
     * @param  string $table The table name
     * @param  string $field The field name
     * @return boolean TRUE if the table exists, otherwise FALSE
     */
    public function hasField($table, $field)
    {
        if (!$this->isLoaded()) {
            return false;
        }

        $table = db_table($table);

        return isset($this->schema[$table][$field]);
    }

    /**
     * Check if the table has the timestamp fields or not
     * @param  string $table The table name without prefix
     * @return boolean TRUE if the table has the timestamp fields, otherwise FALSE
     */
    public function hasTimestamps($table)
    {
        if (!$this->isLoaded()) {
            return false;
        }

        $table = db_table($table);

        return (isset($this->schema[$table]['options']['timestamps']) && $this->schema[$table]['options']['timestamps']) ? true : false;
    }

    /**
     * Check if the table has the slug field or not
     * @param  string $table The table name without prefix
     * @return boolean TRUE if the table has the slug field, otherwise FALSE
     */
    public function hasSlug($table)
    {
        if (!$this->isLoaded()) {
            return false;
        }

        $table = db_table($table);

        return isset($this->schema[$table]['slug']) ? true : false;
    }

    /**
     * Get data type of the field
     * @param  string $table The table name
     * @param  string $field The field name in the table
     * @return string The data type or null if there is no field
     */
    public function getFieldType($table, $field)
    {
        $table = db_table($table);

        if ($this->hasField($table, $field)) {
            return $this->schema[$table][$field]['type'];
        }

        return null;
    }

    /**
     * Get schema options if it is defined
     * otherwise return the default options
     *
     * @return array
     */
    protected function getOptions()
    {
        if (isset($this->schema['_options'])) {
            $options = $this->schema['_options'] + $this->defaultOptions;
        } else {
            $options = $this->defaultOptions;
        }

        return $options;
    }

    /**
     * Get table options if it is defined
     * otherwise return the default options
     *
     * @param array $tableDef The table definition
     * @return array
     */
    protected function getTableOptions($tableDef)
    {
        $options = $this->getOptions();

        if (isset($options['pk'])) {
            unset($options['pk']);
        }

        if (isset($options['fkConstraints'])) {
            unset($options['fkConstraints']);
        }

        if (isset($tableDef['options'])) {
            $tableDef['options'] += $options;
        } else {
            $tableDef['options'] = $options;
        }

        return $tableDef['options'];
    }

    /**
     * Populate primary keys acccording to the schema defined
     * @param  array $schema The database schema
     * @return array
     */
    public function populatePrimaryKeys(&$schema)
    {
        # Populate primary key fields
        $pkFields = array();
        foreach ($schema as $table => $def) {
            $fullTableName = db_table($table);
            $def['options'] = $this->getTableOptions($def);

            if ($def['options']['timestamps']) {
                $def['created'] = array('type' => 'datetime', 'null' => true);
                $def['updated'] = array('type' => 'datetime', 'null' => true);
                $def['deleted'] = array('type' => 'datetime', 'null' => true);
            }

            $schema[$table] = $def;

            # PK Field(s)
            $pkFields[$table] = array();
            if (isset($def['options']['pk'])) {
                foreach ($def['options']['pk'] as $pk) {
                    if (isset($def[$pk])) {
                        # user-defined PK field type
                        $pkFields[$table][$pk] = $def[$pk];
                    } else {
                        # default PK field type
                        $pkFields[$table][$pk] = $this->getPKDefaultType();
                    }
                }
            } else {
                $pkFields[$table]['id'] = $this->getPKDefaultType();
            }
        }

        $this->setPrimaryKeys($pkFields);

        return $pkFields;
    }

    /**
     * Populate pivot tables (joint tables fo many-to-many relationship) into the schema
     * @param  array $schema The database schema
     * @return array Array of constraints
     */
    public function populatePivots(&$schema)
    {
        $constraints = array();
        $pkFields = $this->getPrimaryKeys();

        $manyToMany = array_filter($schema, function($def) {
            return isset($def['m:m']) ? true : false;
        });

        foreach ($manyToMany as $table => $def) {
            foreach ($def['m:m'] as $fkTable => $joint) {
                if (!empty($joint['table']) && isset($schema[$joint['table']])) {
                    # if the joint table has already been defined
                    continue;
                }

                if (isset($schema[$table.'_to_'.$fkTable]) || isset($schema[$fkTable.'_to_'.$table])) {
                    # if the joint table has already been defined
                    continue;
                }

                if (isset($schema[$fkTable]['m:m'][$table])) {
                    if (empty($joint['table']) && !empty($schema[$fkTable]['m:m'][$table]['table'])) {
                        $joint['table'] = $schema[$fkTable]['m:m'][$table]['table'];
                    }

                    # table1_to_table2
                    $jointTable = !empty($joint['table']) ? $joint['table'] : $table.'_to_'.$fkTable;
                    $schema[$jointTable]['options'] = array(
                        'pk' => array(),
                        'timestamps' => false, # no need timestamp fields for many-to-many table
                        'm:m' => true
                    ) + $this->defaultOptions;

                    # table1.field
                    $relation = $this->getRelationOptions($joint, $table);
                    $field = $relation['name'];
                    $schema[$jointTable][$field] = $this->getFKField($fkTable, $table, $relation);
                    $schema[$jointTable][$field]['null'] = false;
                    $schema[$jointTable]['options']['pk'][] = $field;
                    $pkFields[$jointTable][$field] = $schema[$jointTable][$field];
                    # Get FK constraints
                    $constraint = $this->getFKConstraint($jointTable, $table, $relation, $schema);
                    if ($constraint) {
                        $constraints[$jointTable][$field] = $constraint;
                    }

                    # table2.field
                    $relation = $this->getRelationOptions($schema[$fkTable]['m:m'][$table], $fkTable);
                    $field = $relation['name'];
                    $schema[$jointTable][$field] = $this->getFKField($table, $fkTable, $relation);
                    $schema[$jointTable][$field]['null'] = false;
                    $schema[$jointTable]['options']['pk'][] = $field;
                    $pkFields[$jointTable][$field] = $schema[$jointTable][$field];
                    # Get FK constraints
                    $constraint = $this->getFKConstraint($jointTable, $fkTable, $relation, $schema);
                    if ($constraint) {
                        $constraints[$jointTable][$field] = $constraint;
                    }
                }
            }
        }

        $this->setPrimaryKeys($pkFields);
        $this->setConstraints($constraints);

        return $constraints;
    }

    /**
     * Generate CREATE TABLE SQL
     * @param  string   $table      The new table name
     * @param  array    $schema     The database schema
     * @return string
     */
    public function createTableStatement($table, &$schema, &$pkFields, &$constraints)
    {
        if (!isset($schema[$table])) {
            return null;
        }

        $def            = $schema[$table]; # The table definition
        $fullTableName  = db_table($table); # The full table name with prefix
        $fkFields       = array(); # Populate foreign key fields

        # OneToMany
        if (isset($def['m:1']) && is_array($def['m:1'])) {
            foreach ($def['m:1'] as $fkTable) {
                if (isset($schema[$fkTable]['1:m'][$table])) {
                    $relation = $this->getRelationOptions($schema[$fkTable]['1:m'][$table], $fkTable);
                    $field = $relation['name'];
                    # Get FK field definition
                    $fkFields[$field] = $this->getFKField($table, $fkTable, $relation);
                    # Get FK constraints
                    $constraint = $this->getFKConstraint($table, $fkTable, $relation, $schema);
                    if ($constraint) {
                        $constraints[$table][$field] = $constraint;
                    }
                }
            }
        }

        # OneToOne
        if (isset($def['1:1']) && is_array($def['1:1'])) {
            foreach ($def['1:1'] as $fkTable => $fk) {
                $relation = $this->getRelationOptions($fk, $fkTable);
                $field = $relation['name'];
                # Get FK field definition
                $fkFields[$field] = $this->getFKField($table, $fkTable, $relation);
                # Get FK constraints
                $constraint = $this->getFKConstraint($table, $fkTable, $relation, $schema);
                if ($constraint) {
                    $constraints[$table][$field] = $constraint;
                }
            }
        }

        $this->setConstraints($constraints);

        $def = array_merge($pkFields[$table], $fkFields, $def);
        $schema[$table] = $def;

        # ManyToMany table FK indexes
        if (isset($def['options']['m:m']) && $def['options']['m:m']) {
            $jointTable = $table;
            foreach ($schema[$jointTable] as $field => $rule) {
                if ($field == 'options') {
                    continue;
                }
                $fkFields[$field] = $rule;
            }
        }

        $options = $this->getTableOptions($def);
        $def['options'] = $options;

        # CREATE TABLE Statement
        $sql = "CREATE TABLE IF NOT EXISTS `{$fullTableName}` (\n";

        # loop the fields
        $autoinc = false;
        foreach ($def as $name => $rule) {
            # Skip for relationship and option definitions
            if (in_array($name, self::$relationships) || $name == 'options') {
                continue;
            }

            $sql .= '  '.$this->getFieldStatement($name, $rule, $this->getTableCollation($name, $schema)).",\n";

            # if there is any unique index
            if (isset($rule['unique']) && $rule['unique']) {
                $fkFields[$name] = $rule;
            }

            if (isset($rule['autoinc']) && $rule['autoinc']) {
                $autoinc = true;
            }
        }

        # Indexes
        if (count($fkFields)) {
            foreach (array_keys($fkFields) as $name) {
                if (isset($fkFields[$name]['unique']) && $fkFields[$name]['unique']) {
                    $sql .= '  UNIQUE KEY';
                } else {
                    $sql .= '  KEY';
                }
                $sql .= " `IDX_$name` (`$name`),\n";
            }
        }

        # Primay key indexes
        if (isset($pkFields[$table])) {
            $sql .= '  PRIMARY KEY (`'.implode('`,`', array_keys($pkFields[$table])).'`)'."\n";
        }

        $sql .= ')';
        $sql .= ' ENGINE='.$options['engine'];
        $sql .= ' DEFAULT CHARSET='.$options['charset'];
        $sql .= ' COLLATE='.$options['collate'];

        if ($autoinc) {
            $sql .= ' AUTO_INCREMENT=1';
        }

        $sql .= ";\n";

        return $sql;
    }

    /**
     * Generate foreign key constraints SQL statements
     * @param  array $constraints Array of populated constraints
     * @return array Array of SQL statements
     */
    public function createConstraintStatements($constraints = null)
    {
        if ($constraints === null) {
            $constraints = $this->getConstraints();
        }

        $options = $this->getOptions();
        $sql = array();
        # FK constraints
        if ($options['constraints']) {
            foreach ($constraints as $table => $constraint) {
                $fullTableName = db_table($table);
                $sql[] = '--';
                $sql[] = '-- Constraints for table `'.$fullTableName.'`';
                $sql[] = '--';

                $constraintSql = "ALTER TABLE `{$fullTableName}`\n";
                $statement = array();
                foreach ($constraint as $field => $rule) {
                    $statement[] = "  ADD CONSTRAINT `{$rule['name']}` FOREIGN KEY (`{$rule['fields']}`)"
                        . " REFERENCES `{$rule['reference_table']}` (`{$rule['reference_fields']}`)"
                        . " ON DELETE {$rule['on_delete']}"
                        . " ON UPDATE {$rule['on_update']}";
                }
                $constraintSql .= implode(",\n", $statement) . ";\n";
                $sql[] = $constraintSql;
            }
        }

        return count($sql) ? $sql : null;
    }

    /**
     * Generate DROP foreign key constraints SQL statements
     * @param  array $constraints Array of populated constraints
     * @return array Array of SQL statements
     */
    public function dropConstraintStatements($constraints = null)
    {
        if ($constraints === null) {
            $constraints = $this->getConstraints();
        }

        $options = $this->getOptions();
        $sql = array();
        # FK constraints
        if ($options['constraints']) {
            foreach ($constraints as $table => $constraint) {
                $fullTableName = db_table($table);
                $constraintSql = "ALTER TABLE `{$fullTableName}`\n";

                $drop = array();
                foreach ($constraint as $field => $rule) {
                    $drop[] = " DROP FOREIGN KEY `{$rule['name']}`";
                }

                $constraintSql .= implode(",\n", $drop) . ';';
                $sql[] = $constraintSql;
            }
        }

        return count($sql) ? $sql : null;
    }

    /**
     * Set the populated primary keys into the schema database options
     * @param  array $pkFields Array of primary keys
     * @return void
     */
    public function setPrimaryKeys($pkFields)
    {
        $this->schema['_options']['pk'] = $pkFields;
    }

    /**
     * Get the populated primary keys from the schema database options
     * @param  array $schema The schema definition
     * @return array Array of primary keys
     */
    public function getPrimaryKeys($schema = null)
    {
        if ($schema === null) {
            $schema = $this->schema;
        }

        return !empty($schema['_options']['pk']) ? $schema['_options']['pk'] : array();
    }

    /**
     * Set the populated foreign key constraints into the schema database options
     * @param  array $constraints Array of FK constraints
     * @return void
     */
    public function setConstraints($constraints)
    {
        $this->schema['_options']['fkConstraints'] = $constraints;
    }

    /**
     * Get the populated foreign key constraints from the schema database options
     * @param  array $schema The schema definition
     * @return array Array of FK constraints
     */
    public function getConstraints($schema = null)
    {
        if ($schema === null) {
            $schema = $this->schema;
        }

        return !empty($schema['_options']['fkConstraints']) ? $schema['_options']['fkConstraints'] : array();
    }

    /**
     * Return table collation from the schema definition
     * @param string $table The table name
     * @param array  $schema The schema definition (optional)
     * @return string
     */
    public function getTableCollation($table, $schema = null)
    {
        if ($schema === null) {
            $schema = $this->schema;
        }

        return isset($schema[$table]['options']['collate']) ? $schema[$table]['options']['collate'] : null;
    }
}
