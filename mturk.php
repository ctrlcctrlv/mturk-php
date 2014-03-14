<?php
class MechanicalTurk {
    // Define convenient names for system qualifications.
    const P_SUBMITTED = '00000000000000000000';
    const P_ABANDONED = '00000000000000000070';
    const P_RETURNED = '000000000000000000E0';
    const P_APPROVED = '000000000000000000L0';
    const P_REJECTED = '000000000000000000S0';
    const N_APPROVED = '00000000000000000040';
    const LOCALE = '00000000000000000071';
    const ADULT = '00000000000000000060';
    const S_MASTERS = '2ARFPLSP75KLA8M8DH1HTEQVJT3SY6';
    const MASTERS = '2F1QJWKUDD8XADTFD2Q0G6UTO95ALH';
    const S_CATMASTERS = '2F1KVCNHMVHV8E9PBUB2A4J79LU20F';
    const CATMASTERS = '2NDP2L92HECWY8NS8H3CK0CP5L9GHO';
    const S_PHOTOMASTERS = '2TGBB6BFMFFOM08IBMAFGGESC1UWJX';
    const PHOTOMASTERS = '21VZU98JHSTLZ5BPP4A9NOBJEK3DPG';

    public $config;
    
    public function __construct(){
        $config = json_decode(@file_get_contents('mturkconfig.json'));

        if (!$config) {
            throw new Exception('No valid config file found and config not passed to constructor');
        } else {
            $this->config = $config;
        }
    }

    private function generate_timestamp() {
        return date('c');
    }

    private function generate_signature($service, $operation, $timestamp) {
        $str = $service.$operation.$timestamp;
        $hmac = pack('H*', sha1((str_pad($this->config->aws_secret_key, 64, chr(0x00)) ^ (str_repeat(chr(0x5c), 64))) .
                pack('H*', sha1((str_pad($this->config->aws_secret_key, 64, chr(0x00)) ^ (str_repeat(chr(0x36), 64))) . $str))));
        return base64_encode($hmac);
    }

    private function flatten($obj) {
        if (is_array($obj)) {
            if (isset($obj[0])) {
                array_unshift($obj, 'deleteme');
                unset($obj[0]);
            }
            $iter = $obj;
        } else {    
            return array('' => $obj);
        }

        $rv = array();
        foreach ($iter as $k => $v) {
            foreach ($this->flatten($v) as $ik => $iv) {
                is_int($ik) ? $ik+=1 : false;
                $f = $ik ? '%s.%s' : '%s%s';
                $s = sprintf($f, $k, $ik);
                $rv[$s] = $iv;
            }
        }
        return $rv;
    }

    public function request($operation, $args = array()) {
        if ($this->config->use_sandbox) {
            $url = 'https://mechanicalturk.sandbox.amazonaws.com/?Service=AWSMechanicalTurkRequester';
        } else {
            $url = 'https://mechanicalturk.amazonaws.com/?Service=AWSMechanicalTurkRequester';
        }

        $timestamp = $this->generate_timestamp();
        $signature = $this->generate_signature('AWSMechanicalTurkRequester', $operation, $timestamp);
        $args = array_merge($args, ['Operation' => $operation, 'AWSAccessKeyId' => $this->config->aws_key, 
                                    'Signature' => $signature, 'Timestamp' => $timestamp, 'Version' => '2012-03-25']);

        $params = http_build_query($this->flatten($args));

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->config->verify_mturk_ssl);
        $xml = curl_exec($ch);
        $info = curl_getinfo($ch); 
        if(curl_errno($ch)) {
            throw new Exception('Curl returned an error: ' . curl_error($ch));
        }

        $simplexml = new SimpleXMLElement($xml);

        return json_decode(json_encode((array)$simplexml, true));
    }

    // From http://stackoverflow.com/a/3975706/1901658
    public static function get_response_element($array, $needle) {
        $iterator = new RecursiveArrayIterator($array);
        $recursive = new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::SELF_FIRST);
        foreach ($recursive as $key => $value) {
            if ($key === $needle) {
                return $value;
            }
        }
    }

    public static function is_valid($request) {
        return MechanicalTurk::get_response_element($request, 'IsValid') === 'True';
    }
}
