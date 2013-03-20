<?php 
/**
* Games Model
*
* @category     Application_Model
* @package      Games
* @author       Juan Bouquet <juanpbouquet@gmail.com>
*/


class Application_Model_Games {
    
    /** 
    * Advanced game search with pagination option
    *
    * @param   array      Array with custom search options
    * @param   bool       True to use Zend_Paginator
    * @return   object    Depending on paginator option, returns
    *                   Zend_Paginator instance or
    *                   Zend_Db_Rowset containing matching elements.      
    */    
    public function getGames($options = array(), $use_paginator = false) {
        $select = $this->_gamestable->select();

        if(isset($options['limit']))
        {
            $select->limit($options['limit']);
        }

        if(isset($options['page']))
        {
            $page = $options['page'];
        }
        else
        {
            $page = 1;
        }

        if(isset($options['namefilter']))
        {
            echo $options['namefilter'];

            $parts = explode('#', $options['namefilter']);

            $select->from('games','games.*,  MATCH(games.name) AGAINST(\''.implode(' ', $parts).'\') as relevance');
            $select->where('MATCH(games.name) AGAINST(\''.implode(' ', $parts).'\') > ?', 0);
            $select->order('relevance DESC');
        }
        else
        {
            $select->from('games');
        }

        if(isset($options['moderatepriority']))
        {
            switch($options['moderatepriority'])
            {
                case 'sLink':
                    $select->setIntegritycheck(false);
                    $select->join('whitelabel_products', 'whitelabel_products.game_id = games.id', array());
                    break;

                case 'boxArt':
                    $select->setIntegritycheck(false);
                    $select->joinLeft('resources', 'resources.game_id = games.id AND resources.type_id = '.RESOURCE_TYPE_BOXART, array('source'));
                    $select->join('whitelabel_products', 'whitelabel_products.game_id = games.id', array());
                    $select->where('resources.source IS NULL');
                    break;

                case 'ytVideos':
                    $select->setIntegritycheck(false);
                    $select->joinLeft('resources', 'resources.game_id = games.id AND resources.type_id = '.RESOURCE_TYPE_YT_LINK, array('source'));
                    $select->join('whitelabel_products', 'whitelabel_products.game_id = games.id', array());
                    $select->where('resources.source IS NULL');                
                    break;

                case 'bDrops':
                    $select->setIntegritycheck(false);
                    $select->joinLeft('resources', 'resources.game_id = games.id AND resources.type_id = '.RESOURCE_TYPE_BACKDROPS, array('source'));
                    $select->join('whitelabel_products', 'whitelabel_products.game_id = games.id', array());
                    $select->where('resources.source IS NULL');                
                    break;
            }
        }


        if(isset($options['orderby']))
        {
            $select->order($options['orderby']);
        }

        if(isset($options['status']))
        {
            $select->where('games.status = ?', $options['status']);
        }

        $select->where('games.status NOT IN (?)', array(STATUS_DELETED));

        if(isset($options['platform_id']))
        {
            $select->where('platform_id = ?', $options['platform_id']);
        }

        if($use_paginator)
        {

            $paginator = Zend_Paginator::factory($select);


            if(isset($options['pagesize']))
            {
                $paginator->setItemCountPerPage($options['pagesize']);
            }

            $paginator->setCurrentPageNumber($page);

            return $paginator;
        }
        else
        {
            $results = $this->_gamestable->fetchAll($select);

            return $results;
        }
    }

