<?php

namespace jtl\Connector\Gambio\Util;

use jtl\Connector\Core\Database\Mysql;

class CacheHelper
{
    protected $db;
    protected $queryValuesPart;
    
    public function __construct()
    {
        $this->db = Mysql::getInstance();
    }
    
    public function rebuildProductCategoryCache()
    {
        $productId = null;
        $categoryIds = [];
        
        $results = $this->db->query("SELECT categories_id, products_id FROM products_to_categories ORDER BY products_id");
        foreach ($results as $result) {
            if ($productId === null) {
                $categoryIds[] = $result['categories_id'];
                $productId = $result['products_id'];
                continue;
            }
            
            $this->addQueryValuesPart($productId, $categoryIds);
            $this->writeCategoriesIndex();
            
            $productId = $result['products_id'];
            $categoryIds = [$result['categories_id']];
        }
        
        if ($productId !== null) {
            $this->addQueryValuesPart($productId, $categoryIds);
            $this->writeCategoriesIndex(true);
        }
    }
    
    public function getCategoriesParentsArray($p_categoryId)
    {
        static $categoryParents;
        
        $c_categoryId = (int)$p_categoryId;
        
        if ($categoryParents === null) {
            $categoryParents = [];
        } elseif (array_key_exists($c_categoryId, $categoryParents)) {
            return $categoryParents[$c_categoryId];
        }
        
        $outputArray = [];
        
        if ($c_categoryId === 0) {
            //categories_id is root and has no parents. return empty array.
            return $outputArray;
        }
        
        //get category's status and parent_id
        $result = $this->db->query(sprintf(
            "SELECT categories_status, parent_id FROM categories WHERE categories_id = %s",
            $c_categoryId
        ));
        
        if ($result[0]['categories_status'] === '0') {
            //cancel recursion with false on inactive category
            return false;
        }
        
        $parentId = $result[0]['parent_id'];
        $outputArray[] = $parentId;
        
        if ($parentId !== '0') {
            //get more parents, if category is not root
            $parentIds = $this->getCategoriesParentsArray($parentId);
            if ($parentIds === false) {
                //cancel recursion with false on inactive category
                return false;
            }
            //merge category's parent tree to categories_id
            $outputArray = array_merge($outputArray, $parentIds);
        }
        
        $categoryParents[$c_categoryId] = $outputArray;
        
        return $outputArray;
    }
    
    protected function addQueryValuesPart($p_productsId, array $p_categoryIds)
    {
        $categoryIds = [];
        
        foreach ($p_categoryIds as $categoryId) {
            $t_parent_id_array = $this->getCategoriesParentsArray($categoryId);
            
            if ($t_parent_id_array !== false) {
                $categoryIds[] = $categoryId;
                $categoryIds = array_merge($categoryIds, $t_parent_id_array);
            }
        }
        
        sort($categoryIds); //sort array for cleaning
        $categoryIds = array_unique($categoryIds); //delete doubled categories_ids
        $categoryIds = array_values($categoryIds); //close key gaps after deleting duplicates
        
        //build index string
        $categoriesIndex = '';
        foreach ($categoryIds as $categoryId) {
            $categoriesIndex .= sprintf("-%s-", (int)$categoryId);
        }
        
        $this->queryValuesPart .= sprintf('(%s,"%s")',
            (int)$p_productsId,
            $categoriesIndex
        );
    }
    
    protected function writeCategoriesIndex($forceWrite = false)
    {
        if ($this->queryValuesPart !== '' && $forceWrite) {
            //save built index
            $this->db->query(sprintf("REPLACE INTO `categories_index` (`products_id`, `categories_index`) VALUES %s",
                substr($this->queryValuesPart, 0, -1)
            ));
            
            $this->queryValuesPart = '';
        }
    }
}