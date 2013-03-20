<?php
/**
* Resource Model
*
* @category     Application_Model
* @package      Db
* @subpackage   Resource
* @author       Juan Bouquet <juanpbouquet@gmail.com>
*/

class Application_Model_Resources
{
    /** 
    * Creates a resource item based on the identifier and generates all versions for the same content.
    *
    * @param   string      Parent resource type identifier
    * @param   string       Temporary file path
    * @param   array       Resource information to be stored on database
    * @return   object    Resulting Application_Model_Db_Resource object   
    */  
    public function addResource($identifier, $file, $options)
    {
        $imageoptions = array('jpegQuality' => 95);
        $thumb = GenX_Thumblib_PhpThumbFactory::create($file, $imageoptions);
        $md5 = md5($thumb->getImageAsString());
    
        $filename = $md5.'.jpg';

        $typeinfo = $this->getTypeInfo($identifier);

        $options['source'] = $filename;

        $original_path = PATH_RESOURCES . trim($typeinfo->path);

        if(!file_exists($original_path))
        {
            mkdir($original_path, 0777, true);
        }

        $image_info = $thumb->getCurrentDimensions();
        $image_info['source'] = $filename;
        $image_info['game_id'] = (isset($options['game_id'])) ? $options['game_id'] : 0;
        $image_info['user_id'] = $options['user_id'];

        $image_info['product_id'] = (isset($options['product_id']))?$options['product_id']:0;
        $image_info['site_id'] = (isset($options['site_id']))?$options['site_id']:0;
        
        $image_info['type_id'] = $typeinfo->id;
    
        if($typeinfo->maxwidth != 0)
        {
            if($typeinfo->aspectratio == 0)
            {
                $thumb->resize($typeinfo->maxwidth);
            }
            else
            {
                $height = round($typeinfo->maxwidth / $typeinfo->aspectratio);
                $thumb->adaptiveResize($typeinfo->maxwidth, $height);
            }
        }

        $thumb->save($original_path.$filename);

        $children = $typeinfo->getChildren();

        foreach($children as $imageinfo)
        {
    
            $childthumb = GenX_Thumblib_PhpThumbFactory::create($original_path.$filename, $imageoptions);
            if($imageinfo->maxwidth != 0)
            {
                if($imageinfo->aspectratio == 0)
                {
                    $childthumb->resize($imageinfo->maxwidth);
                }
                else
                {
                    $height = round($imageinfo->maxwidth / $imageinfo->aspectratio);
                    $childthumb->adaptiveResize($imageinfo->maxwidth, $height);
                }
            }
            $child_path = PATH_RESOURCES . trim($imageinfo->path);

            if(!file_exists($child_path))
            {
                mkdir($child_path, 0777, true);
            }
            $childthumb->save($child_path.$filename);
        }
    
        $resource = $this->_resourcetable->createRow();
        $resource->setFromArray($image_info);
        $resource->save();
    
        return $resource;
    }

    
}

?>
