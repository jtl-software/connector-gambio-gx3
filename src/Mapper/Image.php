<?php

namespace jtl\Connector\Gambio\Mapper;

use jtl\Connector\Core\Database\IDatabase;
use jtl\Connector\Core\Result\Mysql;
use jtl\Connector\Drawing\ImageRelationType;
use jtl\Connector\Gambio\Util\ShopVersion;
use jtl\Connector\Model\Image as ImageModel;
use Nette\Utils\Strings;
use stdClass;

class Image extends AbstractMapper
{
    protected $mapperConfig = [
        "table" => "products_images",
        "identity" => "getId",
        "mapPull" => [
            "id" => "image_id",
            "relationType" => "type",
            "foreignKey" => "foreignKey",
            "remoteUrl" => null,
            "sort" => "image_nr",
            "name" => "image_name",
            "i18ns" => "ImageI18n|addI18n|true"
        ]
    ];

    private $thumbConfig;

    /**
     * @param IDatabase $db
     * @param array $shopConfig
     * @param stdClass $connectorConfig
     * @throws \Exception
     */
    public function __construct(IDatabase $db, array $shopConfig, \stdClass $connectorConfig)
    {
        parent::__construct($db, $shopConfig, $connectorConfig);

        $this->thumbConfig = [
            'info' => [
                $this->shopConfig['settings']['PRODUCT_IMAGE_INFO_WIDTH'],
                $this->shopConfig['settings']['PRODUCT_IMAGE_INFO_HEIGHT']
            ],
            'popup' => [
                $this->shopConfig['settings']['PRODUCT_IMAGE_POPUP_WIDTH'],
                $this->shopConfig['settings']['PRODUCT_IMAGE_POPUP_HEIGHT']
            ],
            'thumbnails' => [
                $this->shopConfig['settings']['PRODUCT_IMAGE_THUMBNAIL_WIDTH'],
                $this->shopConfig['settings']['PRODUCT_IMAGE_THUMBNAIL_HEIGHT']
            ],
            'gallery' => [
                86,
                86
            ]
        ];
    }

    /**
     * @param null $data
     * @param null $limit
     * @return array
     * @throws \Exception
     */
    public function pull($data = null, $limit = null): array
    {
        $result = [];

        $query = 'SELECT p.image_id, p.image_name, p.products_id foreignKey, "product" type, (p.image_nr + 1) image_nr
            FROM products_images p
            LEFT JOIN jtl_connector_link_image l ON p.image_id = l.endpoint_id
            WHERE l.host_id IS NULL';
        $defaultQuery = 'SELECT CONCAT("pID_",p.products_id) image_id, p.products_image image_name, p.products_id foreignKey, 1 image_nr, "product" type
            FROM products p
            LEFT JOIN jtl_connector_link_image l ON CONCAT("pID_",p.products_id) = l.endpoint_id
            WHERE l.host_id IS NULL && p.products_image IS NOT NULL && p.products_image != ""';
        $combisQuery = $this->getCombiQuery();
        $categoriesQuery = 'SELECT CONCAT("cID_",p.categories_id) image_id, p.categories_image as image_name, p.categories_id foreignKey, "category" type, 1 image_nr
            FROM categories p
            LEFT JOIN jtl_connector_link_image l ON CONCAT("cID_",p.categories_id) = l.endpoint_id
            WHERE l.host_id IS NULL && p.categories_image IS NOT NULL && p.categories_image != ""';
        $manufacturersQuery = 'SELECT CONCAT("mID_",m.manufacturers_id) image_id, m.manufacturers_image as image_name, m.manufacturers_id foreignKey, "manufacturer" type, 1 image_nr
            FROM manufacturers m
            LEFT JOIN jtl_connector_link_image l ON CONCAT("mID_",m.manufacturers_id) = l.endpoint_id
            WHERE l.host_id IS NULL && m.manufacturers_image IS NOT NULL && m.manufacturers_image != ""';

        $dbResult = $this->db->query($query);
        $dbResultDefault = $this->db->query($defaultQuery);
        $dbResultCombis = $this->db->query($combisQuery);
        $dbResultCategories = $this->db->query($categoriesQuery);
        $dbResultManufacturers = $this->db->query($manufacturersQuery);

        $dbResult = array_merge($dbResult, $dbResultDefault, $dbResultCombis, $dbResultCategories, $dbResultManufacturers);

        $current = array_slice($dbResult, 0, $limit);

        foreach ($current as $modelData) {
            $model = $this->generateModel($modelData);

            $result[] = $model;
        }

        return $result;
    }

    /**
     * @return string
     * @throws \Exception
     */
    private function getCombiQuery()
    {
        if (ShopVersion::isGreaterOrEqual('4.1')) {
            $combisQuery = 'SELECT CONCAT("vID_",p.products_properties_combis_id) image_id, pli.product_image_list_image_local_path as image_name, CONCAT(p.products_id, "_", p.products_properties_combis_id) foreignKey, 1 image_nr, "product" type
                        FROM products_properties_combis p
                        LEFT JOIN product_image_list_combi plc ON p.products_properties_combis_id = plc.products_properties_combis_id
                        LEFT JOIN product_image_list_image pli ON plc.product_image_list_id = pli.product_image_list_id
                        LEFT JOIN jtl_connector_link_image l ON CONCAT("vID_",p.products_properties_combis_id) = l.endpoint_id
                        WHERE l.host_id IS NULL AND pli.product_image_list_image_local_path IS NOT NULL';
        } else {
            $combisQuery = 'SELECT CONCAT("vID_",p.products_properties_combis_id) image_id, p.combi_image as image_name, CONCAT(p.products_id, "_", p.products_properties_combis_id) foreignKey, 1 image_nr, "product" type
            FROM products_properties_combis p
            LEFT JOIN jtl_connector_link_image l ON CONCAT("vID_",p.products_properties_combis_id) = l.endpoint_id
            WHERE l.host_id IS NULL && p.combi_image IS NOT NULL && p.combi_image != ""';
        }

        return $combisQuery;
    }

    /**
     * @param object $data
     * @param stdClass|null $dbObj
     * @return object
     * @throws \Exception
     */
    public function push($data, $dbObj = null)
    {
        if (get_class($data) === ImageModel::class && $data->getForeignKey()->getEndpoint() !== '') {

            switch ($data->getRelationType()) {
                case ImageRelationType::TYPE_CATEGORY:
                case ImageRelationType::TYPE_MANUFACTURER:

                    $imageId = self::extractImageId($data->getForeignKey()->getEndpoint());

                    $indexMappings = [
                        ImageRelationType::TYPE_CATEGORY => 'categories',
                        ImageRelationType::TYPE_MANUFACTURER => 'manufacturers',
                    ];

                    $subject = $indexMappings[$data->getRelationType()];

                    $oldImage = null;
                    $sql = sprintf('SELECT %s_image FROM %s WHERE %s_id = %d', $subject, $subject, $subject, $imageId);
                    $oldImageResult = $this->db->query($sql);

                    $imageIndex = sprintf('%s_image', $subject);
                    if (isset($oldImageResult[0][$imageIndex]) && $oldImageResult[0][$imageIndex] !== '') {
                        $oldImage = $oldImageResult[0][$imageIndex];

                        $oldImageFilePath = $this->createImageFilePath($oldImage, $data->getRelationType());
                        if (file_exists($oldImageFilePath)) {
                            @unlink($oldImageFilePath);
                        }
                    }

                    $imgFileName = $this->generateImageName($data);
                    $imageFilePath = $this->createImageFilePath($imgFileName, $data->getRelationType());

                    if (!rename($data->getFilename(), $imageFilePath)) {
                        throw new \Exception('Cannot move uploaded image file');
                    }

                    $relatedObject = new \stdClass();
                    $relatedObject->{$imageIndex} = $imgFileName;
                    if ($data->getRelationType() === ImageRelationType::TYPE_MANUFACTURER) {
                        $relatedObject->{$imageIndex} = sprintf('%s/%s', $subject, $imgFileName);
                    }

                    $this->db->updateRow($relatedObject, $subject, sprintf('%s_id', $subject), $imageId);

                    $endpoint = sprintf('%sID_%d', $subject[0], $imageId);
                    $data->getId()->setEndpoint($endpoint);

                    break;

                case ImageRelationType::TYPE_PRODUCT:

                    $imageId = self::extractImageId($data->getId()->getEndpoint());

                    $productId = $data->getForeignKey()->getEndpoint();

                    if (Product::isVariationChild($productId)) {
                        if ($data->getSort() == 1) {
                            $this->delete($data);

                            $combiId = explode('_', $productId);
                            $combiId = $combiId[1];

                            if (!empty($combiId)) {
                                $imgFileName = $this->generateImageName($data);

                                if (ShopVersion::isGreaterOrEqual('4.1')) {
                                    $imagePath = $this->createImageFilePath($imgFileName, ImageRelationType::TYPE_PRODUCT);
                                    if (!rename($data->getFilename(), $imagePath)) {
                                        throw new \Exception('Cannot move uploaded image file');
                                    }

                                    $listName = 'combination-list-' . $combiId;
                                    $imageList = $this->db->query(
                                        sprintf('SELECT product_image_list_id 
                                            FROM product_image_list 
                                            WHERE product_image_list_name = "%s"', $listName)
                                    );
                                    $imageListId = $imageList[0]['product_image_list_id'] ?? null;
                                    if ($imageListId === null) {
                                        $obj = new \stdClass();
                                        $obj->product_image_list_name = $listName;
                                        $imageList = $this->db->insertRow($obj, 'product_image_list');

                                        if ($imageList instanceof Mysql && $imageList->getErrno() === 0) {
                                            $imageListId = $imageList->getKey();
                                        }
                                    }

                                    if (!is_null($imageListId)) {
                                        $obj = new \stdClass();
                                        $obj->product_image_list_id = $imageListId;
                                        $obj->product_image_list_image_local_path = $imagePath;
                                        $listImage = $this->db->deleteInsertRow($obj, 'product_image_list_image', 'product_image_list_id', $imageListId);
                                        $imageListImageId = $listImage->getKey();

                                        $obj = new \stdClass();
                                        $obj->products_properties_combis_id = $combiId;
                                        $obj->product_image_list_id = $imageListId;
                                        $this->db->deleteInsertRow($obj, 'product_image_list_combi', 'products_properties_combis_id', $combiId);

                                        $i18ns = $data->getI18ns();
                                        if (empty($i18ns)) {
                                            $defaultLanguageId = $this->configHelper->getDefaultLanguage();
                                            $this->saveCombiI18n($imageListImageId, 'title', $imgFileName, $defaultLanguageId);
                                            $this->saveCombiI18n($imageListImageId, 'alt_title', '', $defaultLanguageId);
                                        } else {
                                            foreach ($i18ns as $imageI18n) {
                                                $languageId = $this->locale2id($imageI18n->getLanguageISO());
                                                $this->saveCombiI18n($imageListImageId, 'title', $imgFileName, $languageId);
                                                $this->saveCombiI18n($imageListImageId, 'alt_title', $imageI18n->getAltText(), $languageId);
                                            }
                                        }

                                        $this->generateThumbs($imgFileName);
                                    }
                                } else {
                                    if (!rename($data->getFilename(), $this->shopConfig['shop']['path'] . 'images/product_images/properties_combis_images/' . $imgFileName)) {
                                        throw new \Exception('Cannot move uploaded image file');
                                    }

                                    $combisObj = new \stdClass();
                                    $combisObj->combi_image = $imgFileName;
                                    $this->db->updateRow($combisObj, 'products_properties_combis', 'products_properties_combis_id', $combiId);
                                }

                                $this->db->query(sprintf('INSERT INTO jtl_connector_link_image SET host_id="%s", endpoint_id="vID_%s"', $data->getId()->getHost(), $combiId));
                            }
                        }
                    } else {

                        if (!empty($imageId)) {
                            $prevImgQuery = $this->db->query(sprintf('SELECT image_name FROM products_images WHERE image_id = "%s"', $imageId));
                            if (count($prevImgQuery) > 0) {
                                $prevImage = $prevImgQuery[0]['image_name'];
                            }

                            $this->removeProductImageAndThumbnails($prevImage);

                            $this->db->query(sprintf('DELETE FROM products_images WHERE image_id="%s"', $imageId));
                            if ($data->getSort() > 1) {
                                $this->db->query(sprintf('DELETE FROM gm_prd_img_alt WHERE image_id="%s"', $imageId));
                            }
                        }

                        if ($data->getSort() == 1) {
                            $oldImage = $this->db->query(sprintf('SELECT products_image as image_name FROM products WHERE products_id = "%s"', $productId));
                        } else {
                            $oldImage = $this->db->query(sprintf('SELECT image_name FROM products_images WHERE products_id = "%s" && image_nr=%s', $productId, ($data->getSort() - 1)));
                        }

                        if (count($oldImage) > 0) {
                            $oldImage = $oldImage[0]['image_name'] ?? null;
                            if (!empty($oldImage)) {
                                $originalImage = $this->createImageFilePath($oldImage, ImageRelationType::TYPE_PRODUCT);
                                if (file_exists($originalImage)) {
                                    unlink($originalImage);
                                }
                            }
                        }

                        $imgFileName = $this->generateImageName($data);
                        $imagePath = $this->createImageFilePath($imgFileName, ImageRelationType::TYPE_PRODUCT);
                        if (!rename($data->getFilename(), $imagePath)) {
                            throw new \Exception('Cannot move uploaded image file');
                        }

                        $this->generateThumbs($imgFileName, $oldImage);

                        if ($data->getSort() == 1) {
                            $productsObj = new \stdClass();
                            $productsObj->products_image = $imgFileName;
                            $this->db->updateRow($productsObj, 'products', 'products_id', $productId);
                            $data->getId()->setEndpoint('pID_' . $productId);

                            foreach ($data->getI18ns() as $i18n) {
                                $updateImgAltQuery = sprintf('UPDATE products_description SET gm_alt_text="%s" WHERE products_id="%s" && language_id=%s',
                                    $i18n->getAltText(),
                                    $productId,
                                    $this->locale2id($i18n->getLanguageISO())
                                );
                                $this->db->query($updateImgAltQuery);
                            }
                        } else {
                            $imgObj = new \stdClass();
                            $imgObj->products_id = $productId;
                            $imgObj->image_name = $imgFileName;
                            $imgObj->image_nr = ($data->getSort() - 1);
                            $newIdQuery = $this->db->deleteInsertRow($imgObj, 'products_images', ['image_nr', 'products_id'], [$imgObj->image_nr, $imgObj->products_id]);
                            $data->getId()->setEndpoint($newIdQuery->getKey());
                            foreach ($data->getI18ns() as $i18n) {
                                $updateImgAltQuery = sprintf('INSERT INTO gm_prd_img_alt SET gm_alt_text="%s", products_id="%s", image_id="%s", language_id=%s',
                                    $i18n->getAltText(),
                                    $imgObj->products_id,
                                    $data->getId()->getEndpoint(),
                                    $this->locale2id($i18n->getLanguageISO())
                                );
                                $this->db->query($updateImgAltQuery);
                            }
                        }

                        $this->db->query(sprintf('DELETE FROM jtl_connector_link_image WHERE host_id=%s', $data->getId()->getHost()));
                        $this->db->query(sprintf('INSERT INTO jtl_connector_link_image SET host_id="%s", endpoint_id="%s"', $data->getId()->getHost(), $data->getId()->getEndpoint()));
                    }

                    break;
            }

        }

        return $data;
    }

    public function removeProductImageAndThumbnails($prevImage)
    {
        if (!empty($prevImage)) {
            $original = $this->createImageFilePath($prevImage, ImageRelationType::TYPE_PRODUCT);
            if (file_exists($original)) {
                unlink($original);
            }
            foreach ($this->thumbConfig as $folder => $sizes) {
                $thumbnail = $this->shopConfig['shop']['path'] . $this->shopConfig['img'][$folder] . $prevImage;
                if (file_exists($thumbnail)) {
                    unlink($thumbnail);
                }
            }
        }
    }

    /**
     * @param $imageListImageId
     * @param $type
     * @param $value
     * @param $languageId
     */
    protected function saveCombiI18n($imageListImageId, $type, $value, $languageId)
    {
        $obj = new \stdClass();
        $obj->product_image_list_image_id = $imageListImageId;
        $obj->product_image_list_image_text_type = $type;
        $obj->product_image_list_image_text_value = $value;
        $obj->language_id = $languageId;
        $this->db->insertRow($obj, 'product_image_list_image_text');
    }

    /**
     * @param object $data
     * @return object
     * @throws \Exception
     */
    public function delete($data)
    {
        if (get_class($data) === ImageModel::class && $data->getForeignKey()->getEndpoint() !== '') {

            $imageId = self::extractImageId($data->getId()->getEndpoint());

            switch ($data->getRelationType()) {
                case ImageRelationType::TYPE_CATEGORY:
                case ImageRelationType::TYPE_MANUFACTURER:

                    $indexMappings = [
                        ImageRelationType::TYPE_CATEGORY => 'categories',
                        ImageRelationType::TYPE_MANUFACTURER => 'manufacturers',
                    ];

                    $fkId = $data->getForeignKey()->getEndpoint();
                    $subject = $indexMappings[$data->getRelationType()];

                    $oldImage = $this->db->query(sprintf('SELECT %s_image as oldImage FROM categories WHERE categories_id = "%s"', $subject, $fkId));
                    $oldImage = $oldImage[0]['oldImage'] ?? null;

                    if (!is_null($oldImage)) {
                        $oldImagePath = $this->createImageFilePath($oldImage, $subject);
                        if (file_exists($oldImagePath)) {
                            unlink($oldImagePath);
                        }

                        $relatedObj = new \stdClass();
                        $relatedObj->{sprintf('%s_image', $subject)} = null;
                        $this->db->updateRow($relatedObj, $subject, sprintf('%s_id', $subject), $fkId);

                    }
                    break;
                case ImageRelationType::TYPE_PRODUCT:
                    if ($data->getSort() == 1 && Product::isVariationChild($data->getForeignKey()->getEndpoint())) {
                        $combiId = explode('_', $data->getForeignKey()->getEndpoint());
                        $combiId = $combiId[1];

                        if (!empty($combiId)) {
                            if (ShopVersion::isGreaterOrEqual('4.1')) {
                                $oldCImage = $this->db->query(sprintf('
                                                SELECT pli.product_image_list_image_local_path AS combi_image, 
                                                       pli.product_image_list_id AS list_id,
                                                       pli.product_image_list_image_id AS image_id                                                    
                                                FROM product_image_list_combi plc
                                                LEFT JOIN product_image_list_image pli ON plc.product_image_list_id = pli.product_image_list_id
                                                WHERE products_properties_combis_id = %s', $combiId));
                                $oldCImage = $oldCImage[0]['combi_image'] ?? null;
                                $oldImageId = $oldCImage[0]['image_id'] ?? null;

                                if (!is_null($oldCImage)) {
                                    $combisObj = new \stdClass();
                                    $this->db->deleteRow($combisObj, 'product_image_list_image', 'product_image_list_image_id', $oldImageId);
                                    $this->db->deleteRow($combisObj, 'product_image_list_combi', 'products_properties_combis_id', $combiId);
                                    $this->db->deleteRow($combisObj, 'product_image_list_image_text', 'product_image_list_image_id', $oldImageId);

                                    @unlink($this->shopConfig['shop']['path'] . $oldCImage);
                                    $path = explode('/', $oldCImage);
                                }
                            } else {
                                $oldCImage = $this->db->query('SELECT combi_image FROM products_properties_combis WHERE products_properties_combis_id = "' . $combiId . '"');
                                $oldCImage = $oldCImage[0]['combi_image'];

                                if (isset($oldCImage)) {
                                    @unlink($this->shopConfig['shop']['path'] . 'images/product_images/properties_combis_images/' . $oldCImage);
                                }

                                $combisObj = new \stdClass();
                                $combisObj->combi_image = null;

                                $this->db->updateRow(
                                    $combisObj,
                                    'products_properties_combis',
                                    'products_properties_combis_id',
                                    $combiId
                                );
                            }
                        }
                    } else {
                        if (!empty($imageId)) {

                            $prevImgQuery = $this->db->query(sprintf('SELECT image_name FROM products_images WHERE image_id = "%s"', $imageId));
                            if (count($prevImgQuery) > 0) {
                                $prevImage = $prevImgQuery[0]['image_name'];
                            }

                            $this->removeProductImageAndThumbnails($prevImage);
                            $this->db->query(sprintf('DELETE FROM products_images WHERE image_id="%s"', $imageId));

                            if ($data->getSort() === 1) {
                                $productsObj = new \stdClass();
                                $productsObj->products_image = null;
                                $this->db->updateRow($productsObj, 'products', 'products_id', $data->getForeignKey()->getEndpoint());
                            } else {
                                $this->db->query(sprintf('DELETE FROM gm_prd_img_alt WHERE image_id="%s"', $imageId));
                                $this->db->query('DELETE FROM products_images WHERE image_id="' . $data->getId()->getEndpoint() . '"');
                            }
                        }
                    }

                    break;
            }

            $this->db->query('DELETE FROM jtl_connector_link_image WHERE endpoint_id="' . $data->getId()->getEndpoint() . '"');
        }

        return $data;
    }

    /**
     * @param string $endpointId
     * @return string
     */
    public static function extractImageId(string $endpointId): string
    {
        $id = explode('_', $endpointId);
        return count($id) === 1 ? $id[0] : $id[1];
    }

    public function statistic(): int
    {
        $totalImages = 0;

        $productQuery = $this->db->query("
            SELECT p.*
            FROM (
                SELECT CONCAT('pID_',p.products_id) as imgId
                FROM products p
                WHERE p.products_image IS NOT NULL && p.products_image != ''
            ) p
            LEFT JOIN jtl_connector_link_image l ON p.imgId = l.endpoint_id
            WHERE l.host_id IS NULL
        ");

        if (ShopVersion::isGreaterOrEqual('4.1')) {
            $combiQuery = $this->db->query('SELECT CONCAT("vID_",p.products_properties_combis_id) image_id, pli.product_image_list_image_local_path as image_name, CONCAT(p.products_id, "_", p.products_properties_combis_id) foreignKey, 1 image_nr, "product" type
                        FROM products_properties_combis p
                        LEFT JOIN product_image_list_combi plc ON p.products_properties_combis_id = plc.products_properties_combis_id
                        LEFT JOIN product_image_list_image pli ON plc.product_image_list_id = pli.product_image_list_id
                        LEFT JOIN jtl_connector_link_image l ON CONCAT("vID_",p.products_properties_combis_id) = l.endpoint_id
                        WHERE l.host_id IS NULL AND pli.product_image_list_image_local_path IS NOT NULL');
        } else {
            $combiQuery = $this->db->query("
                SELECT p.*
                FROM (
                    SELECT CONCAT('vID_',p.products_properties_combis_id) as imgId
                    FROM products_properties_combis p
                    WHERE p.combi_image IS NOT NULL && p.combi_image != ''
                ) p
                LEFT JOIN jtl_connector_link_image l ON p.imgId = l.endpoint_id
                WHERE l.host_id IS NULL
            ");
        }

        $categoryQuery = $this->db->query("
            SELECT c.*
            FROM (
                SELECT CONCAT('cID_',c.categories_id) as imgId
                FROM categories c
                WHERE c.categories_image IS NOT NULL && c.categories_image != ''
            ) c
            LEFT JOIN jtl_connector_link_image l ON c.imgId = l.endpoint_id
            WHERE l.host_id IS NULL
        ");

        $manufacturersQuery = $this->db->query("
            SELECT m.*
            FROM (
                SELECT CONCAT('mID_',m.manufacturers_id) as imgId
                FROM manufacturers m
                WHERE m.manufacturers_image IS NOT NULL && m.manufacturers_image != ''
            ) m
            LEFT JOIN jtl_connector_link_image l ON m.imgId = l.endpoint_id
            WHERE l.host_id IS NULL
        ");

        $imageQuery = $this->db->query("
            SELECT i.* FROM products_images i
            LEFT JOIN jtl_connector_link_image l ON i.image_id = l.endpoint_id
            WHERE l.host_id IS NULL
        ");

        $totalImages += count($productQuery);
        $totalImages += count($combiQuery);
        $totalImages += count($categoryQuery);
        $totalImages += count($manufacturersQuery);
        $totalImages += count($imageQuery);

        return $totalImages;
    }

    protected function remoteUrl($data)
    {
        if ($data['type'] == ImageRelationType::TYPE_CATEGORY) {
            return $this->shopConfig['shop']['fullUrl'] . 'images/categories/' . $data['image_name'];
        } elseif ($data['type'] == ImageRelationType::TYPE_MANUFACTURER) {
            return $this->shopConfig['shop']['fullUrl'] . 'images/' . $data['image_name'];
        } else {
            if (strpos($data['image_id'], 'vID_') !== false) {
                if (ShopVersion::isGreaterOrEqual('4.1')) {
                    return $this->shopConfig['shop']['fullUrl'] . $data['image_name'];
                } else {
                    return $this->shopConfig['shop']['fullUrl'] . 'images/product_images/properties_combis_images/' . $data['image_name'];
                }
            } else {
                return $this->shopConfig['shop']['fullUrl'] . $this->shopConfig['img']['original'] . $data['image_name'];
            }
        }
    }

    private function generateThumbs($fileName, $oldImage = null)
    {
        $imgInfo = getimagesize($this->shopConfig['shop']['path'] . $this->shopConfig['img']['original'] . $fileName);

        switch ($imgInfo[2]) {
            case 1:
                $image = imagecreatefromgif($this->shopConfig['shop']['path'] . $this->shopConfig['img']['original'] . $fileName);
                break;
            case 2:
                $image = imagecreatefromjpeg($this->shopConfig['shop']['path'] . $this->shopConfig['img']['original'] . $fileName);
                break;
            case 3:
                $image = imagecreatefrompng($this->shopConfig['shop']['path'] . $this->shopConfig['img']['original'] . $fileName);
                break;
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $original_aspect = $width / $height;

        foreach ($this->thumbConfig as $folder => $sizes) {
            if (!empty($oldImage)) {
                unlink($this->shopConfig['shop']['path'] . $this->shopConfig['img'][$folder] . $oldImage);
            }

            $thumb_width = $sizes[0];
            $thumb_height = $sizes[1];

            $new_width = $thumb_width;
            $new_height = round($new_width * ($height / $width));
            $new_x = 0;
            $new_y = round(($thumb_height - $new_height) / 2);

            if ($this->connectorConfig->thumbs === 'fill') {
                $next = $new_height < $thumb_height;
            } else {
                $next = $new_height > $thumb_height;
            }

            if ($next) {
                $new_height = $thumb_height;
                $new_width = round($new_height * ($width / $height));
                $new_x = round(($thumb_width - $new_width) / 2);
                $new_y = 0;
            }

            $thumb = imagecreatetruecolor($new_width, $new_height);
            imagefill($thumb, 0, 0, imagecolorallocate($thumb, 255, 255, 255));
            if ($imgInfo[2] == 1 || $imgInfo[2] == 3) {
                imagealphablending($thumb, false);
                imagesavealpha($thumb, true);
                $transparent = imagecolorallocatealpha($thumb, 255, 255, 255, 127);
                imagefilledrectangle($thumb, 0, 0, $thumb_width, $thumb_height, $transparent);
            }

            imagecopyresampled(
                $thumb,
                $image,
                0,
                0,
                0,
                0,
                $new_width,
                $new_height,
                $width,
                $height
            );

            switch ($imgInfo[2]) {
                case 1:
                    imagegif($thumb, $this->shopConfig['shop']['path'] . $this->shopConfig['img'][$folder] . $fileName);
                    break;
                case 2:
                    imagejpeg($thumb, $this->shopConfig['shop']['path'] . $this->shopConfig['img'][$folder] . $fileName);
                    break;
                case 3:
                    imagepng($thumb, $this->shopConfig['shop']['path'] . $this->shopConfig['img'][$folder] . $fileName);
                    break;
            }
        }
    }


    /**
     * @param string $imageName
     * @param string $relationType
     * @return string
     * @throws \Exception
     */
    protected function createImageFilePath(string $imageName, string $relationType): string
    {
        $imagesPath = $this->shopConfig['img']['original'];
        switch ($relationType) {
            case ImageRelationType::TYPE_CATEGORY:
                $imagesPath = 'images/categories';
                break;
            case ImageRelationType::TYPE_MANUFACTURER:
                $imagesPath = 'images/manufacturers';
                break;
        }

        $directoryName = sprintf('%s/%s', rtrim($this->shopConfig['shop']['path'], '/'), trim($imagesPath, '/'));
        if (!file_exists($directoryName) && !mkdir($directoryName, 0755, true)) {
            throw new \Exception(sprintf('Cannot create directory %s', $directoryName));
        }

        return sprintf('%s/%s', $directoryName, $imageName);
    }

    /**
     * @param ImageModel $jtlImage
     * @return string
     * @throws \Exception
     */
    protected function generateImageName(ImageModel $jtlImage): string
    {
        $suffix = '';
        $i = 1;

        $info = pathinfo($jtlImage->getFilename());
        $extension = $info['extension'] ?? null;
        $filename = $info['filename'] ?? null;

        $name = !empty($jtlImage->getName()) ? $jtlImage->getName() : $filename;

        do {
            $imageName = sprintf('%s.%s', Strings::webalize(sprintf('%s%s', $name, $suffix)), $extension);
            $imageSavePath = $this->createImageFilePath($imageName, $jtlImage->getRelationType());
            $suffix = sprintf('-%s', $i++);
        } while (file_exists($imageSavePath));

        return $imageName;
    }
}
