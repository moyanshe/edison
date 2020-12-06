<?php

/**
 * file: BaseDataModel.php
 * author: songxinkai@zuoyebang.com
 * date: 2020-08-26
 * brief:
 */
abstract class Service_Data_BaseDataModel extends Service_Data_BaseData
{
    use Wxwork_Util_SingleInstance;

    abstract function dao();

    /**
     * @param array $properties
     * @param array $options
     * @return Service_Homepage_BaseModelObject
     */
    abstract function createObject($properties = [], $options = []);

    /**
     * @param $primaryId
     * @return Service_Homepage_BaseModelObject|null
     */
    public function load($primaryId)
    {
        $primaryKey = $this->createObject()->primaryKey();
        $where = [$primaryKey => $primaryId];
        $ret = $this->dao()->getRecordByConds($where, $this->dao()->getArrAllFields());
        if (!$ret) {
            return null;
        }

        return $this->createObject($ret, ['isFromDB' => true]);
    }

    /**
     * @param $primaryIds
     * @return array|Service_Homepage_BaseModelObject[]
     */
    public function loadMulti($primaryIds) {

        $primaryKey = $this->createObject()->primaryKey();
        $arrConds = [
            $this->dao()->getInParams($primaryKey, $primaryIds),
        ];
        $ret = $this->dao()->getListByConds($arrConds, $this->dao()->getArrAllFields(), null);
        if (!$ret) {
            return [];
        }

        return array_map(function($item){
            return $this->createObject($item, ['isFromDB' => true]);
        }, $ret);
    }

    public function loadWith($where, $arrAppends = NULL)
    {
        $ret = $this->dao()->getRecordByConds($where, $this->dao()->getArrAllFields(), NULL, $arrAppends);
        if (!$ret) {
            return null;
        }
        return $this->createObject($ret, ['isFromDB' => true]);
    }

    public function loadMultiWith($where, $arrAppends = NULL)
    {
        $ret = $this->dao()->getListByConds($where, $this->dao()->getArrAllFields(), NULL, $arrAppends);
        if (!$ret) {
            return [];
        }

        return array_map(function($item){
            return $this->createObject($item, ['isFromDB' => true]);
        }, $ret);
    }

    /**
     * @param Service_Homepage_BaseModelObject $object
     * @return bool
     */
    public function save(Service_Homepage_BaseModelObject $object)
    {
        if (!$object->isFromDB()) {
            if ((bool)$this->dao()->insertRecords($object->getProperties())) {
                $primaryKey = $object->primaryKey();
                //todo 此处获取插入后的主键ID有并发隐患
                if (is_null($object->$primaryKey)) {
                    $object->$primaryKey = $this->dao()->getInsertId();
                }
                return true;
            }

            return false;
        }

        if ($object->isFromDB() && !empty($object->getModifiedProperties())) {
            $primaryKey = $object->primaryKey();
            $where = [$primaryKey => $object->$primaryKey];
            return (bool)$this->dao()->updateByConds($where, $object->getModifiedProperties());
        }
        
        return true;
    }
}