<?php
/**
* Game Row Class (Zend ORM Extension)
*
* @category     Application_Model
* @package      Db
* @subpackage   Game
* @author       Juan Bouquet <juanpbouquet@gmail.com>
*/

class Application_Model_Db_Game extends Zend_Db_Table_Row_Abstract {

    protected $_resources = null;
    protected $_platform_name;

    /** 
    * Checks if a game is currently used by LaSuricata or other Co-branded
    *
    * @return   bool  true if game is in use
    */

    public function isUsed() {
        if(count($this->getOwners()))
            return true;

        if(count($this->getInterestedUsers()))
            return true;

        if(count($this->getSitesOffers()))
            return true;

        return false;

    }

    /** 
    * Gets game usage statistics
    *
    * @return   array  Array containing usage statistics by type
    */

    public function getUsage() {
        $data = array('ownlist'=>count($this->getOwners()),
                      'wishlist'=>count($this->getInterestedUsers()),
                      'sites'=>count($this->getSitesOffers())
                      );

        return $data;
    }


    /** 
    * Retrieves all co-branded products associated with current game
    *
    * @return   object  Zend_Db_Rowset containing co-branded product objects.
    */

    public function getSitesOffers() {
        $table = new Zend_Db_Table('whitelabel_products', 'id');
        $select = $table->select();
        $select->where('game_id = ?', $this->id);

        return $table->fetchAll($select);
    }

    /** 
    * Get unique tags associated with the game
    *
    * @return   object  Zend_Db_Rowset containing tags objects
    */

    public function getTags() {
        $table = new Zend_Db_Table('games_tags', 'id');
        $select = $table->select();
        $select->from('games_tags', array('tag'));
        $select->distinct();
        $select->where('game_id = ?', $this->id);

        return $table->fetchAll($select);
    }

    /** 
    * Associates a tag with game 
    *
    * @param    string   Tag string identifier
    * @return   object   Resulting Application_Model_Db_Tag object 
    */

    public function addTag($tag)
    {
        $table = new Zend_Db_Table('games_tags', 'id');
        $row = $table->createRow();
        $row->game_id = $this->id;
        $row->tag = trim($tag);
        $row->save();

        return $row;
    }

    /** 
    * Deletes tag association  
    *
    * @param    string   Tag string identifier
    * @return   bool     true if succeded, false on error
    */

    public function deleteTag($tag)
    {
        $db = Zend_Db_Table::getDefaultAdapter();

        $where = array();
        $where[] = $db->quoteInto('game_id = ?', $this->id);
        $where[] = $db->quoteInto('tag = ?', trim($tag));

        if($db->delete('games_tags', $where)) {
            return true;
        } else {
            return false;
        }
    }

    /** 
    * Retrieves absolute (canonical) URL of the game
    *
    * @return   string     URL
    */

    public function getLink() {
        $urlHelper = new Zend_View_Helper_Url();
        return CURRENT_PROTOCOL.CURRENT_DOMAIN.$urlHelper->url(array('platform_identifier'=>$this->getPlatformInfo()->identifier, 'game_identifier'=>$this->identifier),'gameDetail',true);
    }

    /** 
    * Increments by one the visit counter and saves the row. 
    */
    public function incrementViews()
    {
        $this->views++;
        $this->save();
    }

    /** 
    * Gets content rating information by type  
    *
    * @param    int     Content Rating type
    * @return   object  Application_Model_Db_Rating object
    */
    public function getRating($type)
    {
        switch($type){
            case RATING_TYPE_ESRB:
                return $this->findParentRow('Application_Model_Db_Ratings', 'RatingESRB');
                break;

            case RATING_TYPE_PEGI:
                return $this->findParentRow('Application_Model_Db_Ratings', 'RatingPEGI');
                break; 
        }
        
    }

    /** 
    * Gets all article (blog) entries associated with the game  
    *
    * @return   object     Zend_Db_Rowset containing Application_Model_Db_Article objects
    */
    public function getRelatedArticles() {
        $articles = new Application_Model_Articles();
        $return = $articles->getGameArticles($this->id, $this->platform_id);

        return $return;
    }

    /** 
    * Gets all user reviews associated with the game
    *
    * @return   object     Zend_Db_Rowset containing Application_Model_Db_UserRating objects.
    */

    public function getUserRatings() {
        $rdb = new Application_Model_Db_UserRatings();
        $select = $rdb->select()
                      ->where('game_id = ?', $this->id);

        return $rdb->fetchAll($select);
        
    }

    /** 
    * Get average rating score based on user reviews
    *
    * @return   float     Average rating score
    */
    public function getUserRating() {
        $rdb = new Application_Model_Db_UserRatings();
        $select = $rdb->select()
                      ->from('games_userratings', array('avgrating'=>new Zend_Db_Expr('AVG(rating_value)')))
                      ->where('game_id = ?', $this->id);

        $row = $rdb->fetchRow($select);
        if($row)
        {
            return number_format(round($row->avgrating,2),2);
        }
        else
        {
            return false;
        }
        
    }

    /** 
    * Gets users that have this game on their favorite list
    *
    * @return   object     Zend_Db_Rowset containing Application_Model_Db_User objects.
    */
    public function getInterestedUsers() {
        $users_db = new Application_Model_Db_Users();
        $select = $users_db->select();

        $select->setIntegrityCheck(false);
        $select->from('users');
        $select->join('users_wishlist_games', 'users_wishlist_games.user_id = users.id')
            ->where('users_wishlist_games.game_id = ?', $this->id);
        $rows = $users_db->fetchAll($select);

        return $rows;   
    }

    /** 
    * Gets users that have this game on their game list.  
    *
    * @return   object     Zend_Db_Rowset containing Application_Model_Db_User objects.
    */

    public function getOwners()
    {
        $users_db = new Application_Model_Db_Users();
        $select = $users_db->select();

        $select->setIntegrityCheck(false);
        $select->from('users');
        $select->join('users_ownlist_games', 'users_ownlist_games.user_id = users.id', array())
            ->where('users_ownlist_games.game_id = ?', $this->id);
        $rows = $users_db->fetchAll($select);

        return $rows;
    }

    /** 
    * Get users that have this game on their game list and registered PSN ID or GAMERTAG  
    *
    * @return   object     Zend_Db_Rowset containing Application_Model_Db_User objects.
    */
    public function getPlayers()
    {
        $users_db = new Application_Model_Db_Users();
        $select = $users_db->select();

        $select->setIntegrityCheck(false);
        $select->from('users');
        $select->join('users_ownlist_games', 'users_ownlist_games.user_id = users.id', array())
            ->where('users_ownlist_games.game_id = ?', $this->id)
            ->where('users.psn_id != ? OR users.live_gamertag != ?', '');
        $rows = $users_db->fetchAll($select);

        return $rows;
    }

    /** 
    * Gets amount of users that have this game for exchange 
    *
    * @return   int    amount of users
    */
    public function hasExchangeOption() {
        $offers_db = new Application_Model_Db_Offers();
        $select = $offers_db->select();
        $select->setIntegrityCheck(false);
        $select->from('users_ownlist_games', array())
               ->join('users', 'users_ownlist_games.user_id = users.id')
               ->where('users_ownlist_games.game_id = ?', $this->id)
               ->where('users_ownlist_games.exchange_enabled  = ?', 1)
               ->where('users.status = ?', STATUS_ACTIVE);
               
        $rows = $offers_db->fetchAll($select);
        
        if(count($rows)>0) {
            return count($rows);
        } else {
            return 0;
        }
    }

    /** 
    * Gets amount of users that have this game for sale.
    *
    * @return   int     amount of users
    */
    public function hasSaleOption() {
        $offers_db = new Application_Model_Db_Offers();
        $select = $offers_db->select();
        $select->setIntegrityCheck(false);
        $select->from('users_ownlist_games', array())
               ->join('users', 'users_ownlist_games.user_id = users.id')
               ->where('users_ownlist_games.game_id = ?', $this->id)
               ->where('users_ownlist_games.sale_enabled = ?', 1)
               ->where('users.status = ?', STATUS_ACTIVE);
               
        
        $rows = $offers_db->fetchAll($select);
        
        if(count($rows)>0) {
            return count($rows);
        } else {
            return 0;
        }
    }

    /** 
    * Gets highest price defined  
    *
    * @return   float     highest price
    * @return   bool     false if there aren't any sale option
    */
    public function getHigherPrice()
    {
        $offers_db = new Application_Model_Db_Offers();
        $select = $offers_db->select();
        $select->from('users_ownlist_games', array('price'=>new Zend_Db_Expr('MAX(sale_price)')))
               ->where('sale_enabled = ?', 1)
               ->where('game_id = ?', $this->id);
        
        $row = $offers_db->fetchRow($select);
        
        return $row->price;
    }

    /** 
    * Gets lowest price defined  
    *
    * @return   float     lowest price
    * @return   bool     false if there aren't any sale option
    */
    public function getLowerPrice()
    {
        $offers_db = new Application_Model_Db_Offers();
        $select = $offers_db->select(); 
        $select->from('users_ownlist_games', array('price'=>new Zend_Db_Expr('MIN(sale_price)')))
               ->where('sale_enabled = ?', 1)
               ->where('game_id = ?', $this->id);

        $row = $offers_db->fetchRow($select);
        
        return $row->price;
    }

    /** 
    * Gets url-safe identifier for permalink generation based on game's name.  
    * WARNING: It may be NOT-UNIQUE.
    *
    * @return   string     game identifier string
    */
    
    private function getIdentifier($str) {
        $delimiter = '-';
        $replace = array('\'');

        if (!empty($replace)) {
            $str = str_replace((array) $replace, ' ', $str);
        }

        $clean = iconv('UTF-8', 'ASCII//TRANSLIT', $str);
        $clean = preg_replace("/[^a-zA-Z0-9\/_|+ -]/", '', $clean);
        $clean = strtolower(trim($clean, '-'));
        $clean = preg_replace("/[\/_|+ -]+/", $delimiter, $clean);

        return $clean;
    }

    /** 
    * Fetchs (once) all image and video resources associated with this game and stores.
    *
    * @return   array     array containing Application_Model_Db_Resource objects 
    */
    private function _fetchResources(bool $force = null) {
        if ($this->_resources == null || $force == true) {

            $res = new Application_Model_Db_Resources();
            $resources = $this->findDependentRowset('Application_Model_Db_Resources',null, $res->select()->order('id DESC'));

            $this->_resources = array();

            foreach ($resources as $resource) {
                if ($resource->status != 9) {
                    $this->_resources[$resource->type_id][] = $resource;
                }
            }
        }

        return $this->_resources;
    }

    /** 
    * Gets parent Platform row  
    *
    * @return   object     Related Application_Model_Db_Platform object
    */
    public function getPlatformInfo() {
        $platform_info = $this->findParentRow('Application_Model_Db_Platforms');
        return $platform_info;
    }

    /** 
    * Gets all exchange or sale offers available  
    * WARNING: It gets disabled/banned users offers.
    *
    * @return   object     Zend_Db_Rowset containing all available offers
    */
    public function getOffers() {
        $offers = new Application_Model_Db_Offers();
        $select = $offers->select();
        $select->where('game_id = ?', $this->id);
        $select->where('(sale_enabled = ? OR sale_enabled = ?)', 1);

        return $offers->fetchAll($select);
    }


    /** 
    * Gets last exchange or sale offer published.  
    * WARNING: It gets disabled/banned users offers.
    *
    * @return   object     Application_Model_Db_Offer object.
    */ 
    public function getLastOffer() {
        
        $offers = new Application_Model_Db_Offers();
        $select = $offers->select();
        $select->where('game_id = ?', $this->id);
        $select->order('update_date DESC');
        $select->limit(1);

        return $offers->fetchRow($select);
    }   


    /** 
    * Gets parent Platform row  
    *
    * @return   object     Related Application_Model_Db_Category object
    */
    public function getCategoryInfo() {
        $category_info = $this->findParentRow('Application_Model_Db_Categories');
        return $category_info;
    }

    /** 
    * Gets all Boxart-type resources available for this game  
    *
    * @return   array      Array containing all boxarts (Application_Model_Db_Resource objects)
    */

    public function getAllBoxArt() {
        $resources = $this->_fetchResources(); {
            $resource_type = RESOURCE_TYPE_BOXART;
        }

        if (isset($resources[$resource_type])) {
            return $resources[$resource_type];
        } else {
            return array();
        }
    }

    /** 
    * Get main logo resource or a generic one if nothing found.
    *
    * @return   object      Application_Model_Db_Object
    */
    public function getBoxArt() {
        $resources = $this->_fetchResources(); {
            $resource_type = RESOURCE_TYPE_BOXART;
        }

        if (isset($resources[$resource_type][0])) {
            return $resources[$resource_type][0];
        } else {
            return $this->getGenericBoxArt();
        }
    }


    /** 
    * Gets all Splash-type resources available for this game  
    *
    * @return   array      Array containing all boxarts (Application_Model_Db_Resource objects)
    */
    public function getAllSplash() {
        $resources = $this->_fetchResources(); {
            $resource_type = RESOURCE_TYPE_SPLASH;
        }

        if (isset($resources[$resource_type])) {
            return $resources[$resource_type];
        } else {
            return array();
        }   
    }

    /** 
    * Get main splash resource or a generic one if nothing found.
    *
    * @return   object      Application_Model_Db_Resource
    */
    public function getSplash() {
        $resources = $this->_fetchResources(); {
            $resource_type = RESOURCE_TYPE_SPLASH;
        }

        if (isset($resources[$resource_type][0])) {
            return $resources[$resource_type][0];
        } else {
            return $this->getGenericSplash();
        }
    }

    /**
    * Creates a temporary Application_Model_Db_Resource item pointing to default boxart by platform
    *
    * @return   object      Application_Model_Db_Resource
    */

    public function getGenericBoxArt() {
        $resources = new Application_Model_Db_Resources();
        $result = $resources->createRow();
        
        $result->type_id = 1;
        $result->source = 'noboxart_'.$this->getPlatformInfo()->identifier.'.jpg';

        return $result;
    }

    /** 
    * Gets all game edit suggestions ordered by popularity
    *
    * @return   object      Application_Model_Db_GameSuggestion
    */
    public function getSuggestions()
    {
        $db = new Application_Model_Db_GameSuggestions();
        $select = $db->select();
        $select->from('games_suggestions');
        $select->where('game_id = ?', $this->id);
        $select->order('status DESC');
        $select->order('likes DESC');
        $select->order('dislikes ASC');

        $results = $db->fetchAll($select);

        return $results;
    }


    /** 
    * Creates a game suggestion for this game
    *
    * @param   object      Application_Model_Db_User of creating user
    * @param   array      Array containing suggested values
    * @return   void      
    */    
    public function createSuggestion(Application_Model_Db_User $user, $options)
    {
        if($this->status == STATUS_ACTIVE)
        {
            $db = new Application_Model_Db_GameSuggestions();
            $suggestion = $db->createRow();    
            
            $suggestion->game_id = $this->id;
            $suggestion->name = $options['name'];
            $suggestion->description = $options['description'];
            $suggestion->category = $options['category_id'];
            $suggestion->platform = $options['platform_id'];
            $suggestion->user_id = $user->id;
            $suggestion->date = date('Y-m-d H:i:s');
            $suggestion->status = STATUS_WAITING_CONFIRMATION;
            
            $suggestion->save();
            
        }
        else
        {
            if($options['name'] != '') {
                $this->name = $options['name'];
            }
            
            if($options['description'] != '') {
                $this->description = $options['description'];
            }
            
            if($options['category_id'] != '') {
                $this->category_id = $options['category_id'];
            }
            
            if($options['platform_id'] != '') {
                $this->platform_id = $options['platform_id'];
            }
            
            $this->status = STATUS_ACTIVE;
            
            $this->save();
        }
        
    }
    
    /** 
    * Zend's ORM extension to insert function to ensure game identifier is stored in DB. 
    */
    protected function _insert() {
        $name = $this->name;
        $identifier = $this->getIdentifier($name);
        $this->identifier = $identifier;
    }

    /** 
    * Zend's ORM extension to update function to ensure game identifier is stored in DB. 
    */
    protected function _update() {
        $name = $this->name;
        $identifier = $this->getIdentifier($name);
        $this->identifier = $identifier;
    }

}