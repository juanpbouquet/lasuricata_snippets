<?php 
/**
* Resource Row Class (Zend ORM Extension)
*
* @category     Application_Model
* @package      Db
* @subpackage   Resource
* @author       Juan Bouquet <juanpbouquet@gmail.com>
*/

class Application_Model_Db_Resource extends Zend_Db_Table_Row_Abstract
{       
    /** 
    * Re scales the image using GenX Thumblib based on a specific resource-type options.
    *
    * @param   string      resource type identifier
    * @param   int         custom cropping start point on x
    * @param   int         custom cropping start point on y
    * @param   int         custom cropping final width
    * @param   int         custom cropping final height
    * @return   void
    */

    public function reScale($identifier, $startx = 0, $starty = 0, $width = 0, $height = 0)
    {
        $resources = new Application_Model_Resources();
        $typeinfo = $resources->getTypeInfo($identifier);

        $imageoptions = array('jpegQuality' => $typeinfo->jpeg_quality);
        $cropped = GenX_Thumblib_PhpThumbFactory::create($this->getOriginal(), $imageoptions);

        if($startx && $starty && $width && $height)
        {
            $cropped->crop($startx, $starty, $width, $height);    
        }
        

        if($typeinfo->aspectratio != 0)
        {
            $height = round($typeinfo->maxwidth / $typeinfo->aspectratio);    
        }
        else
        {
            $height = 0;
        }
        

        if($height > 0)
        {
            $cropped->adaptiveResize($typeinfo->maxwidth, $height);
        }
        else
        {
            $cropped->resize($typeinfo->maxwidth);
        }


        if(!file_exists(RESOURCES_PATH . trim($typeinfo->path)))
        {
            mkdir(RESOURCES_PATH . trim($typeinfo->path), 0777, true);
        }

        $child_path = RESOURCES_PATH . trim($typeinfo->path) . $this->source;

        $cropped->save($child_path);

    }

    /** 
    * Gets original version of the resource (for cropped/child resource-types)
    *
    * @return   string     absolute path of the original resource
    */
    public function getOriginal() {
     
        $path = RESOURCES_PATH;

        if($this->getType()->parent == 0) {
            $path .= $this->getType()->path . $this->source;
        } else {
            $path .= $this->getType()->getParent()->path . $this->source;
        }
        
        return $path;

    }

    /** 
    * Gets resource type information
    *
    * @return   object     parent Application_Model_Db_Resource_Type object
    */
    public function getType()
    {
        if(!$this->id)
        {
           $typedb = new Application_Model_Db_Resources_Types();
           $result = $typedb->find($this->type_id);
           return $result->current(); 
        }   
        else
        {
            return $this->findParentRow('Application_Model_Db_Resources_Types', 'Type');
        }
    }

    /** 
    * Zend's ORM extension to insert function to store creation date on DB. 
    */
    protected function _insert()
    {
        $this->created_at = date('Y-m-d H:i:s');
        $this->status = 0;
    }
    
    /** 
    * Overrides Zend's ORM delete method to avoid physical delete from the DB.
    *
    * @return   void
    */
    public function delete()
    {
        $this->status = 9;
        $this->save();
    }
}
