<?php

namespace jtl\Connector\Gambio\Mapper;

use jtl\Connector\Gambio\Controller\DefaultController;
use jtl\Connector\Gambio\Util\CategoryIndexHelper;

class Category extends \jtl\Connector\Gambio\Mapper\AbstractMapper
{
    protected $mapperConfig = [
        "table"    => "categories",
        "query"    => "SELECT c.* FROM categories c
            LEFT JOIN jtl_connector_link_category l ON c.categories_id = l.endpoint_id
            WHERE l.host_id IS NULL",
        "where"    => "categories_id",
        "identity" => "getId",
        "mapPull"  => [
            "id"               => "categories_id",
            "parentCategoryId" => null,
            "sort"             => "sort_order",
            "level"            => "level",
            "i18ns"            => "CategoryI18n|addI18n",
            "invisibilities"   => "CategoryInvisibility|addInvisibility",
            "attributes"       => "CategoryAttr|addAttribute",
            "isActive"         => null,
        ],
        "mapPush"  => [
            "categories_id"                             => "id",
            "parent_id"                                 => null,
            "sort_order"                                => "sort",
            //"CategoryI18n|addI18n"                      => "i18ns",
            "CategoryInvisibility|addInvisibility|true" => "invisibilities",
            "CategoryAttr|addAttribute|true"            => "attributes",
            "last_modified"                             => null,
        ],
    ];
    
    private $tree = [];
    private static $idCache = [];
    
    protected function last_modified($data)
    {
        return date('Y-m-d H:m:i', time());
    }
    
    protected function parentCategoryId($data)
    {
        return $this->replaceZero($data['parent_id']);
    }
    
    protected function parent_id($data)
    {
        return empty($data->getParentCategoryId()->getEndpoint()) ? 0 : $data->getParentCategoryId()->getEndpoint();
    }
    
    protected function isActive($data)
    {
        $results = $this->db->query(sprintf(
            '
              SELECT categories_status
              FROM categories
              WHERE categories_id="%s"',
            $data['categories_id']
        ));
    
        if (!empty($results)) {
            return $results[0]["categories_status"];
        }
    
        return '';
    }
    
    public function pull($parent = null, $limit = null): array
    {
        $this->tree = [];
        
        $this->getChildren(null, 0, $limit);
        
        usort($this->tree, function ($a, $b) {
            return $a['level'] - $b['level'];
        });
        
        $pulledQuery = $this->db->query('SELECT endpoint_id FROM jtl_connector_link_category');
        $pulled = [];
        
        foreach ($pulledQuery as $pCat) {
            $pulled[] = $pCat['endpoint_id'];
        }
        
        $resultCount = 0;
        $result = [];
        
        foreach ($this->tree as $category) {
            if ($resultCount >= $limit) {
                break;
            }
            
            if (in_array($category['categories_id'], $pulled) === false) {
                $result[] = $this->generateModel($category);
                
                $resultCount++;
            }
        }
        
        return $result;
    }
    
    public function push($data, $dbObj = null)
    {
        if (isset(static::$idCache[$data->getParentCategoryId()->getHost()])) {
            $data->getParentCategoryId()->setEndpoint(static::$idCache[$data->getParentCategoryId()->getHost()]);
        }
    
        $dbObj = new \stdClass();
        $result = parent::push($data, $dbObj);

        (new CategoryI18n($this->db, $this->shopConfig, $this->connectorConfig))->push($data, new \stdClass());
        return $result;
    }
    
    public function pushDone($model, $dbObj)
    {
        (new CategoryIndexHelper())->rebuildProductCategoryCache();
        
        static::$idCache[$model->getId()->getHost()] = $model->getId()->getEndpoint();
        $this->db->query('UPDATE categories SET date_added="' . date(
            'Y-m-d H:m:i',
            time()
        ) . '" WHERE categories_id=' . $model->getId()->getEndpoint() . ' && date_added IS NULL');
        DefaultController::resetCache();
    }
    
    private function getChildren($ids, $level, $limit)
    {
        if (is_null($ids)) {
            $sql = 'c.parent_id=0';
        } else {
            $sql = 'c.parent_id IN (' . implode(',', $ids) . ')';
        }
        
        $children = $this->db->query('SELECT c.* FROM categories c
            WHERE ' . $sql);
        
        if (count($children) > 0) {
            $ids = [];
            
            foreach ($children as $child) {
                $ids[] = $child['categories_id'];
                
                $child['level'] = $level;
                $this->tree[] = $child;
            }
            
            $this->getChildren($ids, $level + 1, $limit);
        }
    }
    
    public function delete($data)
    {
        $id = $data->getId()->getEndpoint();
        
        if (!empty($id) && $id != '') {
            try {
                $this->db->query('DELETE FROM categories WHERE categories_id=' . $data->getId()->getEndpoint());
                $this->db->query('DELETE FROM categories_description WHERE categories_id=' . $data->getId()->getEndpoint());
                $this->db->query('DELETE FROM products_to_categories WHERE categories_id=' . $data->getId()->getEndpoint());
                
                //$this->db->query('DELETE FROM jtl_connector_link WHERE type=1 && endpointId="'.$data->getId()->getEndpoint().'"');
            } catch (\Exception $e) {
            }
        }
        
        DefaultController::resetCache();
        
        return $data;
    }
}
