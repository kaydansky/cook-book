<?php

namespace Cookbook\Domain\China;

use Cookbook\{DB\Database, Picture\Picture};

/**
 * Description of IngredientModel
 *
 * @author AlexK
 */
class ChinaModel
{
    
    protected $db;
    protected $imgPath = PATH_IMAGES . 'china/';
    protected $picture;
    protected $auth;

    public function __construct(Picture $picture)
    {
        $this->db = Database::dsn();
        $this->picture = $picture;
    }
    
    public function inject($auth)
    {
        $this->auth = $auth;
    }

    public function getAutoComplete($term)
    {
        $this->db->exec('SET NAMES \'utf8\'');

        return $this->db->select('SELECT china_id, china_name FROM china WHERE china_name LIKE ? ORDER BY china_name', ["%$term%"]);
    }
    
    public function createChina($new_china_name, $new_manufacturer = null)
    {
        $imgId = null;
        
        if (isset($_FILES['new_china_image'])) {
            if (is_uploaded_file($_FILES['new_china_image']['tmp_name'])) {
                $this->picture->path = $this->imgPath;
                $this->picture->file = $_FILES['new_china_image']['tmp_name'];
                $imgId = $this->picture->uploadImage();
            }
        }

        $this->db->exec('SET NAMES \'utf8\'');
        $this->db->insert(
                'china', 
                [
                    'china_name' => $new_china_name,
                    'manufacturer' => $new_manufacturer,
                    'image_filename' => $imgId
                ]
        );
        
        return $this->db->getLastInsertId();
    }
    
    public function getChinaList()
    {   
        $this->db->exec('SET NAMES \'utf8\'');
        
        return $this->db->select('SELECT * FROM china ORDER BY china_name');
    }
    
    public function getChina($id)
    {
        $this->db->exec('SET NAMES \'utf8\'');
        
        return $this->db->selectRow('SELECT * FROM china WHERE china_id = ?', [$id]);
    }
    
    public function updateChina(
            $update_china_id, 
            $update_china_name = null, 
            $update_manufacturer = null, 
            $remove_image = null)
    {
        $china = $this->getChina($update_china_id);
        $imgId = $china['image_filename'];
        
        if (isset($_FILES['update_china_image'])) {
            if (is_uploaded_file($_FILES['update_china_image']['tmp_name'])) {
                $this->picture->path = $this->imgPath;
                $this->picture->file = $_FILES['update_china_image']['tmp_name'];
                $imgId = $this->picture->uploadImage();
                $this->picture->file = $china['image_filename'];
                $this->picture->deleteImages();
            }
        }
        
        if ($remove_image) {
            $this->picture->path = $this->imgPath;
            $this->picture->file = $china['image_filename'];
            $this->picture->deleteImages();
            $imgId = null;
        }

        $this->db->exec('SET NAMES \'utf8\'');
        $this->db->update(
                'china',
                [
                    'china_name' => $update_china_name,
                    'manufacturer' => $update_manufacturer,
                    'image_filename' => $imgId
                ],
                [
                    'china_id' => $update_china_id
                ]
        );
    }
    
    public function deleteChina($id)
    {
        $china = $this->getChina($id);
        $this->picture->path = $this->imgPath;
        $this->picture->file = $china['image_filename'];
        $this->picture->deleteImages();
        $this->db->delete('china', ['china_id' => $id]);
    }
    
}
