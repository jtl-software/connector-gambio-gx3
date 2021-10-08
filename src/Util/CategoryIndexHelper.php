<?php

namespace jtl\Connector\Gambio\Util;

use jtl\Connector\Core\Database\Mysql;

class CategoryIndexHelper
{
    /**
     * @var Mysql
     */
    protected $db;

    /**
     * @var string[]
     */
    protected $queryValuesPart = [];

    /**
     * CategoryIndexHelper constructor.
     */
    public function __construct()
    {
        $this->db = Mysql::getInstance();
    }

    /**
     *
     */
    public function rebuildProductCategoryCache()
    {
        $categoryIdsByProduct = [];
        $results = $this->db->query("SELECT categories_id, products_id FROM products_to_categories ORDER BY products_id");
        foreach ($results as $result) {
            $categoryIdsByProduct[$result['products_id']][] = $result['categories_id'];
        }

        foreach ($categoryIdsByProduct as $productId => $categoryIds) {
            $this->addQueryValuesPart($productId, ...$categoryIds);
        }

        $this->writeCategoriesIndex();
    }

    /**
     * @param int $categoryId
     * @return array|bool|mixed
     */
    public function getCategoriesParentsArray(int $categoryId)
    {
        static $categoryParents;

        if ($categoryParents === null) {
            $categoryParents = [];
        }

        if (array_key_exists($categoryId, $categoryParents)) {
            return $categoryParents[$categoryId];
        }

        $outputArray = [];

        if ($categoryId === 0) {
            //categories_id is root and has no parents. return empty array.
            return $outputArray;
        }

        //get category's status and parent_id
        $result = $this->db->query(sprintf(
            "SELECT categories_status, parent_id FROM categories WHERE categories_id = %s",
            $categoryId
        ));

        if ($result[0]['categories_status'] === '0') {
            //cancel recursion with false on inactive category
            return false;
        }

        $parentId = $result[0]['parent_id'] ?? null;
        $outputArray[] = $parentId;

        if (!in_array($parentId, ['0', null], true)) {
            //get more parents, if category is not root
            $parentIds = $this->getCategoriesParentsArray($parentId);
            if ($parentIds === false) {
                //cancel recursion with false on inactive category
                return false;
            }
            //merge category's parent tree to categories_id
            $outputArray = array_merge($outputArray, $parentIds);
        }

        $categoryParents[$categoryId] = $outputArray;

        return $outputArray;
    }

    /**
     * @param int $productId
     * @param int ...$productsCategoryIds
     */
    protected function addQueryValuesPart(int $productId, int ...$productsCategoryIds): void
    {
        $categoryIds = [];

        foreach ($productsCategoryIds as $categoryId) {
            $parentIdArray = $this->getCategoriesParentsArray($categoryId);

            if ($parentIdArray !== false) {
                $categoryIds[] = $categoryId;
                $categoryIds = array_merge($categoryIds, $parentIdArray);
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

        $this->queryValuesPart[] = sprintf(
            '(%s,"%s")',
            $productId,
            $categoriesIndex
        );
    }

    /**
     *
     */
    protected function writeCategoriesIndex(): void
    {
        if (!empty($this->queryValuesPart)) {
            //save built index
            foreach (array_chunk($this->queryValuesPart, 500) as $part) {
                $this->db->query(sprintf(
                    "REPLACE INTO `categories_index` (`products_id`, `categories_index`) VALUES %s",
                    implode(',', $part)
                ));
            }

            $this->queryValuesPart = [];
        }
    }
}
