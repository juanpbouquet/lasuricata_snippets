<?php 
/**
* Resource Type Row Class (Zend ORM Extension)
*
* @category     Application_Model
* @package      Db
* @subpackage   Resource_Type
* @author       Juan Bouquet <juanpbouquet@gmail.com>
*/

class Application_Model_Db_Resource_Type extends Zend_Db_Table_Row_Abstract
{
    /** 
    * Gets Resource_Types that depends on this one.
    *
    * @return   object   Zend_Db_Rowset containing Application_Model_Db_Resource_Type objects 
    */

    public function getChildren() {
        return $this->findDependentRowset('Application_Model_Db_Resources_Types', 'Children');
    }

    /** 
    * Retriveves float aspect ratio and converts it to division (xx:xx)
    *
    * @return   string   Aspect Ratio
    */
    public function getAspectRatio() {
        if($this->maxwidth == 0 || $this->aspectratio == 0)
        {
            return false;
        }
        $width = $this->maxwidth;
        $height = $this->maxwidth / $this->aspectratio;

        $divisor = $this->gcd($width, $height);

        return ($width / $divisor) . ':' . ($height / $divisor);
    }

    /** 
    * Greatest common divisor algorithm 
    *
    * @param    int      number #1
    * @param   int   number #2
    * @return   int   greatest common divisor
    */

    private function gcd($m,$n)
    {
        if ($n==0)
        {
             return $m;
            }
            else
            {
             return (gcd($n,($m%$n)));
        }
    }
} 
