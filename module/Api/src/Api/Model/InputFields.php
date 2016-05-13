<?php

namespace Api\Model;

use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Predicate\Expression;

class InputFields extends AbstractModel {
    
    protected static $properties = array(
        'field_id',
        '_id',
        'sort',        
        'type',
        'locale',
        'name',
        'short',
        'content',
        'created',
        'updated',
        'active',
        'website_id',
    );
    
    protected static $primaryKey = 'field_id';
    
    protected static $tableName = 'input_fields';
    
    public function getList($param)
    {
        if (empty($param['locale'])) {
            $param['locale'] = \Application\Module::getConfig('general.default_locale');
        }
        $sql = new Sql(self::getDb());
        $select = $sql->select()
            ->from(static::$tableName)  
            ->join(               
                array(
                    'input_field_locales' => 
                    $sql->select()
                        ->from('input_field_locales')
                        ->where("locale = ". self::quote($param['locale']))
                ),                    
                static::$tableName . '.field_id = input_field_locales.field_id',
                array(
                    'locale', 
                    'name', 
                    'short',
                ),
                \Zend\Db\Sql\Select::JOIN_LEFT    
            );
        
        if (isset($param['active']) && $param['active'] !== '') {            
            $select->where(static::$tableName . '.active = '. $param['active']);  
        }
        if (!empty($param['website_id'])) {            
            $select->where(static::$tableName . '.website_id = '. self::quote($param['website_id']));  
        }
        if (!empty($param['name'])) {
            $select->where(new Expression("input_field_locales.name LIKE '%{$param['name']}%'"));
        }
        if (!empty($param['limit'])) {
            $select->limit($param['limit']);
            if (!empty($param['page'])) {
                $select->offset(static::getOffset($param['page'], $param['limit']));
            }
        }
        if (!empty($param['sort'])) {
            preg_match("/(name|sort)-(asc|desc)+/", $param['sort'], $match);
            if (count($match) == 3) {
                switch ($match[1]) {
                    case 'name':
                        $select->order("input_field_locales.{$match[1]} " . $match[2]);
                        break;
                    case 'sort':
                        $select->order(static::$tableName . '.' . $match[1] . ' ' . $match[2]);
                        break;
                }                
            }            
        } else {
            $select->order(static::$tableName . '.sort ASC');
        }         
        $selectString = $sql->getSqlStringForSqlObject($select);
        return array(
            'count' => static::count($selectString),
            'limit' => $param['limit'],
            'data' => static::toArray(static::selectQuery($selectString)), 
        );
    }
    
    public function getAll($param) {
        if (empty($param['locale'])) {
            $param['locale'] = \Application\Module::getConfig('general.default_locale');
        }
        $sql = new Sql(self::getDb());
        $select = $sql->select()
            ->from(static::$tableName)  
            ->columns(array(                
                'field_id', 
                'type', 
                '_id', 
                'sort'
            ))
            ->join(               
                array(
                    'input_field_locales' => 
                    $sql->select()
                        ->from('input_field_locales')
                        ->where("locale = ". self::quote($param['locale']))
                ),                    
                static::$tableName . '.field_id = input_field_locales.field_id',
                array(
                    'locale', 
                    'name', 
                    'short',
                ),
                \Zend\Db\Sql\Select::JOIN_LEFT    
            )
            ->where(static::$tableName . '.active = 1')     
            ->order('sort');
        if (!empty($param['website_id'])) {            
            $select->where(static::$tableName . '.website_id = '. self::quote($param['website_id']));  
        }
        if (!empty($param['field_id'])) {      
            if (is_array($param['field_id'])) {
                $param['field_id'] = implode(',', $param['field_id']);
            }
            $select->where(static::$tableName . '.field_id IN ('. $param['field_id'] . ')');  
        }
        return self::response(
            static::selectQuery($sql->getSqlStringForSqlObject($select)), 
            self::RETURN_TYPE_ALL
        );        
    }    
    
    public function add($param)
    {
        $_id = mongo_id();  // input_fields._id
        
        $values = array(
            '_id' => $_id,
            'sort' => self::max(array('field' => 'sort')) + 1,
            'website_id' => $param['website_id'],
        );
        if (isset($param['type'])) {
            $values['type'] = $param['type'];
        }
        if ($id = self::insert($values)) {
            $localeValues = array(
                'field_id' => $id,
                'locale' => \Application\Module::getConfig('general.default_locale')
            );
            if (isset($param['name'])) {
                $localeValues['name'] = $param['name'];
            } 
            if (isset($param['short'])) {
                $localeValues['short'] = $param['short'];
            } 
            if (isset($param['content'])) {
                $localeValues['content'] = $param['content'];
            }
            self::$tableName = 'input_field_locales';
            self::insert($localeValues);
            return $_id;
        }        
        return false;
    }

    public function updateInfo($param)
    {
        $self = self::find(
            array(            
                'where' => array('_id' => $param['_id'])
            ),
            self::RETURN_TYPE_ONE
        );   
        if (empty($self)) {
            self::errorNotExist('_id');
            return false;
        }        
        $set = array();
        if (isset($param['type'])) {
            $set['type'] = $param['type'];
        }
        if (isset($param['sort'])) {
            $set['sort'] = $param['sort'];
        }
        if (isset($param['website_id'])) {
            $set['website_id'] = $param['website_id'];
        }
        if (self::update(
            array(
                'set' => $set,
                'where' => array(
                    '_id' => $param['_id']
                ),
            )
        )) {
            $locales = \Application\Module::getConfig('general.locales');
            if (count($locales) == 1) {
                $param['locale'] = array_keys($locales)[0];
                self::addUpdateLocale($param);
            }
            return true;
        }
        return false;
    }

    public function addUpdateLocale($param)
    {
        $detail = self::getDetail(array(
            '_id' => $param['_id'],
            'locale' => $param['locale'],
        ));
        if (empty($detail)) {
            self::errorNotExist('_id');
            return false;
        }
        
        static::$tableName = 'input_field_locales';
        $values = array();
        if (isset($param['name'])) {
            $values['name'] = $param['name'];
        } 
        if (isset($param['short'])) {
            $values['short'] = $param['short'];
        } 
        if (isset($param['content'])) {
            $values['content'] = $param['content'];
        }
        if (empty($detail['locale'])) {
            $values['field_id'] = $detail['field_id'];
            $values['locale'] = $param['locale'];
            return self::insert($values);
        }
        return self::update(
            array(
                'set' => $values,
                'where' => array(
                    'field_id' => $detail['field_id'],
                    'locale' => $param['locale'],
                ),
            )
        );
    }

    public function getDetail($param)
    {
        if (empty($param['locale'])) {
            $param['locale'] = \Application\Module::getConfig('general.default_locale');
        }
        $sql = new Sql(self::getDb());
        $select = $sql->select()
            ->from(static::$tableName)  
            ->columns(array(                
                'field_id', 
                '_id', 
                'type', 
                'sort'
            ))
            ->join(               
                array(
                    'input_field_locales' => 
                    $sql->select()
                        ->from('input_field_locales')
                        ->where("locale = ". self::quote($param['locale']))
                ),                    
                static::$tableName . '.field_id = input_field_locales.field_id',
                array(
                    'locale', 
                    'name', 
                    'short',
                ),
                \Zend\Db\Sql\Select::JOIN_LEFT    
            )
            ->where("_id = ". self::quote($param['_id']));    
        if (!empty($param['website_id'])) {            
            $select->where(static::$tableName . '.website_id = '. self::quote($param['website_id']));  
        }
        $row = self::response(
            static::selectQuery($sql->getSqlStringForSqlObject($select)), 
            self::RETURN_TYPE_ONE
        );
        if ($row) {
            $inputOptions = new InputOptions();
            $row['options'] = $inputOptions->getAll(array(
                'field_id' => $row['field_id'],
                'locale' => $param['locale'],
            ));
        }
        return $row;  
    }
    
    public function updateSort($param) {  
        parent::$primaryKey = self::$primaryKey;
        parent::$properties = self::$properties;
        return parent::updateSort($param);
    }
    
    public function save($param) {
        if (empty($param['locale'])) {
            $param['locale'] = \Application\Module::getConfig('general.default_locale');
        }
        if (is_array(self::$primaryKey)) {
            self::errorParamInvalid();
            return false;
        }
        if (empty($param['name'])) {
            self::errorParamInvalid();
            return false;
        }
        $param['name'] = \Zend\Json\Decoder::decode($param['name'], \Zend\Json\Json::TYPE_ARRAY);        
        $values = array();
        foreach ($param['name'] as $id => $name) {
            $values[] = array(
                'field_id' => $id,
                'locale' => $param['locale'],
                'name' => $name
            ); 
        }
        if (self::updateSort($param)) {
            self::$tableName = 'input_field_locales';        
            return self::batchInsert(
                $values,
                array(
                    'name' => new Expression('VALUES(`name`)'),
                ),
                false
            );
        }
        return false; 
    }
    
}
