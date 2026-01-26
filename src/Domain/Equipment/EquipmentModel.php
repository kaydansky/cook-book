<?php

namespace Cookbook\Domain\Equipment;

use Cookbook\DB\Database;
use Cookbook\Picture\Picture;

/**
 * Description of IngredientModel
 *
 * @author AlexK
 */
class EquipmentModel
{
    
    protected $db;
    protected $imgPath = PATH_IMAGES . 'equipment/';
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
    
    public function createEquipment($new_equipment, $new_manufacturer = null, $new_description = null)
    {
        $imgId = null;
        
        if (isset($_FILES['new_equipment_image'])) {
            if (is_uploaded_file($_FILES['new_equipment_image']['tmp_name'])) {
                $this->picture->path = $this->imgPath;
                $this->picture->file = $_FILES['new_equipment_image']['tmp_name'];
                $imgId = $this->picture->uploadImage();
            }
        }

        $this->db->exec('SET NAMES \'utf8\'');
        $this->db->insert(
                'equipment', 
                [
                    'equipment' => $new_equipment,
                    'supplier' => $new_manufacturer,
                    'description' => $new_description,
                    'image_filename' => $imgId
                ]
        );
        
        return $this->db->getLastInsertId();
    }
    
    public function getEquipmentList()
    {   
        $this->db->exec('SET NAMES \'utf8\'');
        
        return $this->db->select('SELECT * FROM equipment ORDER BY equipment');
    }
    
    public function getEquipment($id)
    {
        $this->db->exec('SET NAMES \'utf8\'');
        
        return $this->db->selectRow('SELECT * FROM equipment WHERE equipment_id = ?', [$id]);
    }
    
    public function updateEquipment(
            $update_equipment_id, 
            $update_equipment = null, 
            $update_manufacturer = null, 
            $update_description = null,
            $remove_image = null)
    {
        $equipment = $this->getEquipment($update_equipment_id);
        $imgId = $equipment['image_filename'];
        
        if (isset($_FILES['update_equipment_image'])) {
            if (is_uploaded_file($_FILES['update_equipment_image']['tmp_name'])) {
                $this->picture->path = $this->imgPath;
                $this->picture->file = $_FILES['update_equipment_image']['tmp_name'];
                $imgId = $this->picture->uploadImage();
                $this->picture->file = $equipment['image_filename'];
                $this->picture->deleteImages();
            }
        }
        
        if ($remove_image) {
            $this->picture->path = $this->imgPath;
            $this->picture->file = $equipment['image_filename'];
            $this->picture->deleteImages();
            $imgId = null;
        }

        $this->db->exec('SET NAMES \'utf8\'');
        $this->db->update(
                'equipment',
                [
                    'equipment' => $update_equipment,
                    'supplier' => $update_manufacturer,
                    'description' => $update_description,
                    'image_filename' => $imgId
                ],
                [
                    'equipment_id' => $update_equipment_id
                ]
        );
    }
    
    public function deleteEquipment($id)
    {
        $equipment = $this->getEquipment($id);
        $this->picture->path = $this->imgPath;
        $this->picture->file = $equipment['image_filename'];
        $this->picture->deleteImages();
        $this->db->delete('equipment', ['equipment_id' => $id]);
    }
    
}
