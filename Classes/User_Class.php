<?php
/**
* User Row Class (Zend ORM Extension)
*
* @category     Application_Model
* @package      Db
* @subpackage   User
* @author       Juan Bouquet <juanpbouquet@gmail.com>
*/
class Application_Model_Db_User extends Zend_Db_Table_Row_Abstract {


    /** 
    * Merges current user games and ratings with a previously created temporary user
    *
    * @param   int     temporary user id
    * @return   bool    true if success  
    */  

    public function mergeData($temp_id)
    {
        $users = new Application_Model_Users();
        $temp_user = $users->getUserById($temp_id);

        if($temp_user->status != STATUS_TEMP)
        {
            return false;
        }

        $tables_to_migrate = array('users_ownlist_games', 'users_wishlist_games', 'games_userratings');
        
        foreach($tables_to_migrate as $table)
        {
            $table_db = new Zend_Db_Table($table, 'id');
            
            $data = array('user_id' => $this->id);
            $where = $table_db->getAdapter()->quoteInto('user_id = ?', $temp_id);

            $table_db->update($data, $where);
        }

        return true;

    }

    /** 
    * Checks if the user already rated a game
    *
    * @param   int      game's id
    * @return   bool    true if already rated      
    */  
    public function hasRated($game_id)
    {
        $rating = new Application_Model_UserRatings();
        return $rating->hasRated($this->id, $game_id);
    }

    /** 
    * Stores rate value publishing the story on Facebook if share is enabled
    *
    * @param   int      game's id
    * @param   float      score given
    * @return   void      
    */  
    public function rateGame($game_id, $score)
    {
        $urdb = new Application_Model_Db_UserRatings();

        $games = new Application_Model_Games();
        $game_info = $games->getGameById($game_id);

        $ratingrow = $this->hasRated($game_id);

        if(!$ratingrow)
        {
            $ratingrow = $urdb->createRow();
            $ratingrow->user_id = $this->id;
            $ratingrow->game_id = $game_id; 
        }

        $ratingrow->datetime = date('Y-m-d H:i:s');

        if(!is_null($score))
            $ratingrow->rating_value = $score;

        $ratingrow->save();

        if($this->facebook_share == 1)
        {
            $this->facebookPublish('rate', array(
                            'title'=>'',
                            'userscore'=>$score,
                            'finalscore'=>$game_info->getUserRating(),
                            'usercount'=>count($game_info->getUserRatings()),
                            'game'=>$game_info->getLink()));    
        }
        
    }

    /** 
    * Publishes a custom open-graph action on user's storyline.
    *
    * @param   string       open graph registered action
    * @param   array      parameters required by open graph action
    * @return   array       facebook's api response      
    * @return   false       if user's facebook token is not set   
    */  
    public function facebookPublish($og_action, $data)
    {

        if($this->facebook_token != '')
        {            
            $access = array(
                'appId' => FACEBOOK_APP_ID,
                'secret' => FACEBOOK_SECRET,
            );

            $fb = new Facebook_OAuth_API($access);
            $fb->setAccessToken($this->facebook_token);

            $uid = $fb->getUser();

            $data['access_token'] = $this->facebook_token;

            $response = $fb->api('/'.$fb->getUser().'/lasuricatacom:'.$og_action, 'POST', $data);
            
            return $response;
        }
        else
        {
            return false;
        }
    }

    /** 
    * Creates a publish action on user Facebook's storyline
    *
    * @param   int       published game id
    * @param   bool      true to publish as explicitly shared story
    */  
    public function publishGameToFb($game_id, $explicitly_shared = false)
    {
        $offer = $this->checkGame('ownlist', $game_id, false);
        
        $games = new Application_Model_Games();
        
        $game_info = $games->getGameById($game_id);

        $facebook_post_data = array('game'=>$game_info->getLink(),
                                    'sale_enabled'=>$offer['sale_enabled'],
                                    'exchange_enabled'=>$offer['exchange_enabled'],
                                    );

        if($offer['sale_enabled'])
        {
            $facebook_post_data['sale_pitch'] = 'En venta a $'.$offer['sale_price'];
            $facebook_post_data['sale_price'] = $offer['sale_price'];
        }

        if($offer['exchange_enabled'])
        {
            $facebook_post_data['exchange_pitch'] = 'Acepto Canjes!';
        }

        if($offer['game_condition'])
        {
            $facebook_post_data['condition_pitch'] = $this->getConditionText($offer['game_condition']);
        }

        if($offer['game_region'])
        {
            $facebook_post_data['region_pitch'] = $this->getRegionName($offer['game_region'], $game_info->platform_id);
        }

        if($explicitly_shared)
        {
            $facebook_post_data['fb:explicitly_shared'] = true;    
        }

        $publish_data = $this->facebookPublish('publish',$facebook_post_data);

        if($explicitly_shared)
        {
            $offer->facebook_explicit_story = $publish_data['id'];
        }
        else
        {
            $offer->facebook_story = $publish_data['id'];    
        }
        
        $offer->save();

        return true;
    }

    /** 
    * Updates user's password encoding it first.
    *
    * @param   string   new password       
    */  
    public function setPassword($password) {
        $this->password = $this->encodePassword($password);
    }

    /** 
    * Resets password to a randomly generated one and sends it by email
    */  
    public function resetPassword() {
        $newpassword = substr(md5(microtime()),0,8);
        $this->setPassword($newpassword);
        $this->save();

        $email_view = new Zend_View();
        $email_view->setScriptPath(PATH_VIEWS_EMAIL);

        $url = $email_view->serverUrl() . '/activar/' . $this->id . '/' . $this->hash;

        $email_view->username = $this->username;
        $email_view->password = $newpassword;

        $email_body = $email_view->render('passwordreminder.phtml');
        $email_body_html = $email_view->render('passwordreminder_html.phtml');

        $mail = new Zend_Mail('UTF-8');
        $mail->setBodyText($email_body);
        $mail->setBodyHtml($email_body_html);
        $mail->setFrom('admin@lasuricata.com', 'LaSuricata.com');
        $mail->addTo($this->email, $this->username);
        $mail->setSubject('Restableciste tu password en LaSuricata.com');
        $mail->send();
    }

    /** 
    * Hashes password using SHA1
    *
    * @param   string    password   
    * @return   string   password hash   
    */  
    private function encodePassword($password) {
        $encoded_password = sha1($password);

        return $encoded_password;
    }

}

