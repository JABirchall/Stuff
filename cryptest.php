<?php

class Crypt{
    
    private $key;
    private $key_size;
    private $raw_key;
    private $resource;
    private $iv_size;
    private $iv;
    private $algorithms;
    private $algorithm;
    private $modes;
    private $mode;
    private $base64;
    
    public function __construct($params){
        # make sure mcrypt is loaded
        if(!extension_loaded('mcrypt')){
            throw new Exception('mcrypt extension is required for this script');
            return false;
        }
        # make sure key is supplied
        if(!array_key_exists('key', $params) || !isset($params['key']) || (isset($params['key']) && !$params['key'])){
            $error = 'key is a required parameter. see Crypt::listOptions()';
            throw new Exception($error);
            return false;
        }
        # set params
        $this->raw_key = $params['key'];
        $this->algorithms = mcrypt_list_algorithms();
        $this->modes = mcrypt_list_modes();
        # check availables
        if(!count($this->algorithms)){
            throw new Exception('there are no available algorithms for mcrypt');
            return false;
        }
        if(!count($this->modes)){
             throw new Exception('there are no available modes for mcrypt');
             return false;
        }
        # algorithm
        $this->algorithm = $this->algorithms[0];        
        if(array_key_exists('algorithm', $params) && in_array($params['algorithm'], $this->algorithms)){
            $this->algorithm  = $params['algorithm'];
        }
        # mode
        $this->mode = $this->modes[0];
        if(array_key_exists('mode', $params) && in_array($params['mode'], $this->modes)){
            $this->mode = $params['mode'];
        }
        # base 64 encoding
        $this->base64 = true;
        if(array_key_exists('base64', $params) && !$params['base64']){
            $this->base64 = false;
        }
        
        return $this->start();
    }

    private function initialize(){
        return mcrypt_generic_init($this->resource, $this->key, $this->iv);
    }
    
    private function start(){
        $this->resource  = mcrypt_module_open($this->algorithm, '', $this->mode, '');
        $this->key_size  = mcrypt_enc_get_key_size($this->resource);
        $this->key       = substr($this->raw_key, 0, $this->key_size);
        $this->iv_size   = mcrypt_enc_get_iv_size($this->resource);
        $this->iv        = mcrypt_create_iv($this->iv_size, MCRYPT_RAND);
        return $this->initialize();
    }
    
    public function encrypt($data){
        $this->initialize();
        $encrypted = mcrypt_generic($this->resource, $data);
        return ($this->base64) ? base64_encode($encrypted) : $encrypted;
    }
    
    public function decrypt($data){
        $this->initialize();
        $data = ($this->base64) ? base64_decode($data) : $data;
        return mdecrypt_generic($this->resource, $data);
    }
    
    public function close(){
        try{
            mcrypt_generic_deinit($this->resource);
            mcrypt_module_close($this->resource);
        }catch(Exception $e){

        }
    }
    
    public function listModes(){
        return $this->modes;
    }
    
    public function listAlgorithms(){
        return $this->algorithms;
    }
    
    public function listKeysize(){
        return $this->key_size;
    }
    
    public function getMode(){
        return $this->mode;
    }
    
    public function getAlgorithm(){
        return $this->algorithm;
    }

    public function getBase64Encoding(){
        return $this->base64;
    }
    
    public function setMode($mode){
        $this->mode = (in_array($mode, $this->modes)) ? $mode : $this->mode;
        $this->close();
        $this->start();
        return $this->mode;
    }
    
    public function setAlgorithm($algorithm){
        $this->algorithm = (in_array($algorithm, $this->algorithms)) ? $algorithm : $this->algorithm;
        $this->close();
        $this->start();
        return $this->algorithm;
    }

    public function setBase64Encoding($base64){
        $this->base64 = ($base64) ? true : false;
        return $this->base64;
    }
    

    public static function listOptions(){
      $options = "key        => string - (required) no default resized to fit appropriate key size
				mode       => must be a result of mcrypt_list_modes() - (optional) default: first result from mcrypt_list_modes()
				algorithm  => must be a result of mcrypt_list_algorithms() - (optional) default: first result from mcrypt_list_algorithms()
				base64     => bool sets encoding of input/output to base 64 - (optional) default: true";
      return $options;
    }

    public static function modes(){
        # shorthand static function
        if(!extension_loaded('mcrypt')){
            throw new Exception('mcrypt extension is required for this script');
            return false;
        }
        return mcrypt_list_modes();
    }

    public static function algorithms(){
        # shorthand static function
        if(!extension_loaded('mcrypt')){
            throw new Exception('mcrypt extension is required for this script');
            return false;
        }
        return mcrypt_list_algorithms();
    }

    public static function extensionLoaded(){
        # shorthand static function
        return (extension_loaded('mcrypt')) ? true : false;
    }
}

$n = "\n";


$options = array(
    'key'       => 'herp derp gerp lerp', # required
    'mode'      => 'cfb',                 # optional
    'algorithm' => 'blowfish',            # optional
    'base64'    => false                   # optional default
);

$crypt = new Crypt($options);

$data = $crypt->encrypt('TOP SECRET blah blah blah');
echo bin2hex($data),$n; # 13Tt9Omi1uDsWlraXzuHUW6i2O1cySZ6U5dOO7FatCI= 
echo $crypt->decrypt($data),$n; # TOP SECRET blah blah blah
echo $crypt->getMode(),$n; # ecb
echo $crypt->getAlgorithm(),$n; # blowfish
echo $crypt->getBase64Encoding(),$n; # 1
$crypt->close(); # Close

print_r(Crypt::algorithms());
print_r(Crypt::modes());