<?php if (!defined('BASE_PATH')) exit('No direct script access allowed');

class Securesend extends MFYU_VanillaModel {
        
    protected $_abstract = false;
    
    public function __construct()
    {
        parent::__construct();
    }
    
    public function get_by_url($url)
    {
        if(trim($url) == '')
        {
            return false;
        }
        
        $query = 'SELECT * FROM `' . $this->_table .'` WHERE `url` = :url';
        $statement = $this->db->prepare($query);
        $statement->bindParam(':url', $url);
        $statement->execute();
        
        if($statement->rowCount() == 0)
        {
            return false; // url doesn't exist bad times!
        }
        else
        {
            $result = $statement->fetch(PDO::FETCH_ASSOC); // get our next row
            
            if($result)
            {
                foreach($result as $field => $value)
                {
                    $this->$field = $value;
                }
                return true;
            }
            else
            {
                return false;
            }
        }
    }
    
    public function delete_expired()
    {
        
        $date = date('Y-m-d');
        $default_date = '0000-00-00'; // used for view-based storage
        
        // we'll hash over expired records to increase security before we delete them
        $hash_query = "UPDATE `" . $this->_table . "` SET `pass` = :randomhash, `url` = :randomurlhash WHERE `expiration_date` < :date AND `expiration_date` <> :defaultdate ";
        
        $this->load_helper('string');
        
        $random_hash = $this->mfyu_string->generate_random_string(32);
        $random_url_hash = $this->mfyu_string->generate_random_string(8);
        
        $hash_run_query = $this->db->prepare($hash_query);
        
        $hash_run_query->bindParam(':randomhash', $random_hash);
        $hash_run_query->bindParam(':randomurlhash', $random_url_hash);
        $hash_run_query->bindParam(':date', $date);
        $hash_run_query->bindParam(':defaultdate', $default_date);
        
        $hash_result = $hash_run_query->execute();
        
        // now just delete them
        $query = "DELETE FROM `" . $this->_table . "` WHERE `expiration_date` < :date AND `expiration_date` <> :defaultdate ";
        $run_query = $this->db->prepare($query);
        $run_query->bindParam(':date', $date);
        $run_query->bindParam(':defaultdate', $default_date);
        
        if($result = $run_query->execute())
        {
            return true;
        }
        else
        {
            return false;
        }
    }
    
    public function viewed()
    {
        $this->expiration_views--; // decrease our view count
        if($this->expiration_views == 0) // if we have 0 views left, delete this item!
        {
            $this->_delete_secure();
        }
        else // otherwise, just save it
        {
            $this->save();
        }
    }
    
    protected function _delete_secure() // hashes random information over the password before we delete it.
    {
        // we'll hash over expired records to increase security before we delete them
        $hash_query = "UPDATE `" . $this->_table . "` SET `pass` = :random-hash, `url` = :random-url-hash WHERE `id` = :id";
        
        $this->load_helper('string');
        
        $random_hash = $this->mfyu_string->generate_random_string(32);
        $random_url_hash = $this->mfyu_string->generate_random_string(8);
        
        $hash_run_query = $this->db->prepare($hash_query);
        
        $hash_run_query->bindParam(':random-hash', $random_hash);
        $hash_run_query->bindParam(':random-url-hash', $random_url_hash);
        $hash_run_query->bindParam(':id',  $this->id);
        
        $this->delete();
    }
    
    public function url_exists($url = '')
    {
        if($url == '')
        {
            $url = $this->url;
        }
        
        $query = 'SELECT * FROM `' . $this->_table .'` WHERE `url` = :url';
        $statement = $this->db->prepare($query);
        $statement->bindParam(':url', $url);
        $statement->execute();
        if($statement->rowCount() == 0)
        {
            return false; // url doesn't exist!
        }
        else
        {
            return true; // we already have a row
        }
    }
}
