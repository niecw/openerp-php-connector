<?php
/* OpenERP PHP connection script. Under GPL V3 , All Rights Are Reserverd , tejas.tank.mca@gmail.com
 *
 * @Author : Tejas L Tank.,             https://twitter.com/snippetbucket
 * @Email : tejas.tank.mca@gmail.com
 * @Country : India
 * @Date : 14 Feb 2011
 * @License : GPL V3
 * @Contact : www.facebook.com/tejaskumar.tank or www.linkedin.com/profile/view?id=48881854
 *
 *
 * OpenERP XML-RPC connections methods are db, common, object , report , wizard
 *
 *
 *
 *
 */
include("xmlrpc-3.0.1/lib/xmlrpc.inc");

class OpenERP {

    public $server = "http://localhost:8069/xmlrpc/";
    public $database = "";
    public $uid = "";           /**  @uid = once user succesful login then this will asign the user id **/
    public $username = "";      /** @username = general name of user which require to login at openerp server **/
    public $password = "";      /** @password = password require to login at openerp server **/
    public $verifyPeer = true;  /** @verifyPeer = Whether or not server SSL certificate should be verified **/
    public $verifyHost = 2;     /** @verifyHost = Level of TLS/SSL certificate verification. 0 - No verification, 1 - verify existence, 2 - also verify domain name.  See http://gggeek.github.io/phpxmlrpc/doc-2/ch07s03.html for the latest  **/
    public $charset = 'UTF-8';  /** @charset = The charset used.  PHP XML-RPC library defaults to ISO-8859-1 which is not DBCS **/
    public $debug = false;      /** @debug = Turn debug on or off **/

    public function login($username, $password, $database, $server, $verifyPeer=true, $verifyHost=2, $charset='UTF-8', $debug=False) {
        $GLOBALS['xmlrpc_internalencoding'] = $charset;

        if ($server) {
            $this->server = $server;
        }
        if ($database) {
            $this->database = $database;
        }
        if ($username) {
            $this->username = $username;
        }
        if ($password) {
            $this->password = $password;
        }
        $this->verifyPeer = $verifyPeer;
        $this->verifyHost = $verifyHost;
        $this->debug = $debug;

        try {
            $sock = $this->getNewClient(null, 'common');

            $msg = new xmlrpcmsg('login');
            $msg->addParam(new xmlrpcval($this->database, "string"));
            $msg->addParam(new xmlrpcval($this->username, "string"));
            $msg->addParam(new xmlrpcval($this->password, "string"));

            $resp = $sock->send($msg);

            if($resp->faultCode()){
                throw new Exception($resp->faultString());
            }
        } catch (Exception $e) {
            echo 'Caught exception: ',  $e->getMessage(), "\n";
            return -1;
        }

        $this->uid = $resp->value()->me['int'];
        if ( $this->uid ) {
            return $this->uid; //* userid of succesful login person *//
        } else {
            return -1; //** if userid not exists , username or password wrong.. */
        }
    }

    public function search($values, $model_name, $offset=0, $max=40, $order="id DESC") {
        $domains = array();

        try {
            $client = $this->getNewClient('phpvals');

            $msg = new xmlrpcmsg('execute');
            $msg->addParam(new xmlrpcval($this->database, "string"));  //* database name */
            $msg->addParam(new xmlrpcval($this->uid, "int")); /* useid */
            $msg->addParam(new xmlrpcval($this->password, "string"));/** password */
            $msg->addParam(new xmlrpcval($model_name, "string"));/** model name where operation will held * */
            $msg->addParam(new xmlrpcval("search", "string"));/** method which u like to execute */

            foreach($values as $x){
                if(!empty($x)){
                        if (is_array($x) && (3 == count($x))) {
                            array_push( $domains,  new xmlrpcval(
                                                                array(  new xmlrpcval($x[0], "string" ),
                                                                         new xmlrpcval( $x[1], "string" ),
                                                                         new xmlrpcval( $x[2], xmlrpc_get_type($x[2]) )
                                                                      ),
                                                                      "array"
                                                               )
                                     );
                        } elseif (is_string($x) && (1 == strlen($x))) {
                            array_push($domains,  new xmlrpcval($x, "string"));
                        } else {
                            throw new Exception("Unrecognized search parameter");
                        }
                }
            }

            $msg->addParam(new xmlrpcval($domains, "array")); /* SEARCH DOMAIN */
            $msg->addParam(new xmlrpcval($offset, "int")); /* OFFSET, START FROM */
            $msg->addParam(new xmlrpcval($max, "int")); /* MAX RECORD LIMITS */
            $msg->addParam(new xmlrpcval($order, "string"));

            $resp = $client->send($msg);

            if($resp->faultCode()){
                /* if no record is found */
                throw new Exception($resp->faultString());
            }
        } catch (Exception $e) {
            echo 'Caught exception: ',  $e->getMessage(), "\n";
            return -1;
        }

        return $resp->value();  /* Return the found record Database IDs */
    }

    public function searchread($values, $model_name, $fields=array(), $offset=0, $max=10, $order = "id DESC", $context=array()) {

        $resp = $this->search($values, $model_name, $offset, $max, $order);
        if($resp != -1){
            return $this->read($resp, $fields, $model_name, $context);
        }

        return -1;
    }

    public function create($values, $model_name) {

        try {
            $client = $this->getNewClient('phpvals');

            foreach($values as &$v) {
                $v = new xmlrpcval( $v, xmlrpc_get_type($v) );
            }

            $msg = new xmlrpcmsg('execute');
            $msg->addParam(new xmlrpcval($this->database, "string"));  //* database name */
            $msg->addParam(new xmlrpcval($this->uid, "int")); /* useid */
            $msg->addParam(new xmlrpcval($this->password, "string"));/** password */
            $msg->addParam(new xmlrpcval($model_name, "string"));/** model name where operation will held * */
            $msg->addParam(new xmlrpcval("create", "string"));/** method which u like to execute */
            $msg->addParam(new xmlrpcval($values, "struct"));/** parameters of the methods with values....  */

            $resp = $client->send($msg);

            if($resp->faultCode()){
                /* if the record is not created  */
                throw new Exception($resp->faultString());
            }
        } catch (Exception $e) {
            echo 'Caught exception: ',  $e->getMessage(), "\n";
            return -1;
        }

        return $resp->value();  /* return new generated id of record */
    }

    public function write($ids, $values, $model_name) {
        try {
            $client = $this->getNewClient('phpvals');

            // As per https://github.com/sylwit/openerp-php-connector/commit/d5deae376d398d660d15fbeaf1cd704eaf594814
            $this->convertIds($ids);

            foreach($values as &$v) {
                $v = new xmlrpcval( $v, xmlrpc_get_type($v) );
            }

            $msg = new xmlrpcmsg('execute');
            $msg->addParam(new xmlrpcval($this->database, "string"));  //* database name */
            $msg->addParam(new xmlrpcval($this->uid, "int")); /* useid */
            $msg->addParam(new xmlrpcval($this->password, "string"));/** password */
            $msg->addParam(new xmlrpcval($model_name, "string"));/** model name where operation will held * */
            $msg->addParam(new xmlrpcval("write", "string"));/** method which u like to execute */
            $msg->addParam(new xmlrpcval($ids, "array"));/** ids of record which to be updting..   this array must be xmlrpcval array */
            $msg->addParam(new xmlrpcval($values, "struct"));/** parameters of the methods with values....  */
            $resp = $client->send($msg);

            if($resp->faultCode()){
                /* if the record is not created  */
                throw new Exception($resp->faultString());
            }
        } catch (Exception $e) {
            echo 'Caught exception: ',  $e->getMessage(), "\n";
            return -1;
        }

        return $resp->value();  /* return new generated id of record */
    }

    public function read($ids, $fields, $model_name, $context=array() ) {
        try {
            $client = $this->getNewClient('phpvals');

            // As per https://github.com/sylwit/openerp-php-connector/commit/d5deae376d398d660d15fbeaf1cd704eaf594814
            $this->convertIds($ids);

            foreach($fields as &$field) {
               $field = new xmlrpcval( $field, "string");
            }

            $msg = new xmlrpcmsg('execute');
            $msg->addParam(new xmlrpcval($this->database, "string"));  //* database name */
            $msg->addParam(new xmlrpcval($this->uid, "int")); /* useid */
            $msg->addParam(new xmlrpcval($this->password, "string"));/** password */
            $msg->addParam(new xmlrpcval($model_name, "string"));/** model name where operation will held * */
            $msg->addParam(new xmlrpcval("read", "string"));/** method which u like to execute */
            $msg->addParam(new xmlrpcval($ids, "array"));/** ids of record which to be updting..   this array must be xmlrpcval array */
            $msg->addParam(new xmlrpcval($fields, "array"));/** parameters of the methods with values....  */

            if(!empty($context)){
                $msg->addParam(new xmlrpcval(array("lang" => new xmlrpcval("nl_NL", "string"),'pricelist'=>new xmlrpcval($context['pricelist'], xmlrpc_get_type($context['pricelist']) )) , "struct"));
            }

            $resp = $client->send($msg);

            if($resp->faultCode()){
                /* if the record is not writable or not existing the ids or not having permissions  */
                throw new Exception($resp->faultString());
            }
        } catch (Exception $e) {
            echo 'Caught exception: ',  $e->getMessage(), "\n";
            return -1;
        }

        return $resp->value();  /* return an array of read field values for the given Database IDs of the model */
    }

    public function unlink($ids , $model_name) {
        try {
            $client = $this->getNewClient('phpvals');

            // As per https://github.com/sylwit/openerp-php-connector/commit/d5deae376d398d660d15fbeaf1cd704eaf594814
            $this->convertIds($ids);

            $msg = new xmlrpcmsg('execute');
            $msg->addParam(new xmlrpcval($this->database, "string"));  //* database name */
            $msg->addParam(new xmlrpcval($this->uid, "int")); /* useid */
            $msg->addParam(new xmlrpcval($this->password, "string"));/** password */
            $msg->addParam(new xmlrpcval($model_name, "string"));/** model name where operation will held * */
            $msg->addParam(new xmlrpcval("unlink", "string"));/** method which u like to execute */
            $msg->addParam(new xmlrpcval($ids, "array"));/** ids of record which to be updting..   this array must be xmlrpcval array */
            $resp = $client->send($msg);

            if($resp->faultCode()){
                /* if the record is not writable or not existing the ids or not having permissions  */
                throw new Exception($resp->faultString());
            }
        } catch (Exception $e) {
            echo 'Caught exception: ',  $e->getMessage(), "\n";
            return -1;
        }

        return $resp->value();  /* returns True if the unlink is successful and False if not */
    }


    public function price_get($ids, $product_id, $qty, $partner_id) {
        try {
            $client = $this->getNewClient('phpvals');

            // As per https://github.com/sylwit/openerp-php-connector/commit/d5deae376d398d660d15fbeaf1cd704eaf594814
            $this->convertIds($ids);

            $msg = new xmlrpcmsg('execute');
            $msg->addParam(new xmlrpcval($this->database, "string"));  //* database name */
            $msg->addParam(new xmlrpcval($this->uid, "int")); /* useid */
            $msg->addParam(new xmlrpcval($this->password, "string"));/** password */
            $msg->addParam(new xmlrpcval('product.pricelist', "string"));/** model name where operation will held * */
            $msg->addParam(new xmlrpcval("price_get", "string"));/** method which u like to execute */
            $msg->addParam(new xmlrpcval($ids, "array"));/** ids of record which to be updting..   this array must be xmlrpcval array */
            $msg->addParam(new xmlrpcval($product_id, "int"));
            $msg->addParam(new xmlrpcval($qty, xmlrpc_get_type($qty)  ));
            $msg->addParam(new xmlrpcval($partner_id, "int"));

            $resp = $client->send($msg);

            if($resp->faultCode()){
                /* if the product is not found or user does not have read permissions */
                throw new Exception($resp->faultString());
            }
        } catch (Exception $e) {
            echo 'Caught exception: ',  $e->getMessage(), "\n";
            return -1;
        }

        return $resp->value();  /* returns True if the unlink is successful and False if not */
    }

    public function get_fields($model){
        try {
            $client = $this->getNewClient('phpvals');

            $msg = new xmlrpcmsg('execute');
            $msg->addParam(new xmlrpcval($this->database, "string"));  //* database name */
            $msg->addParam(new xmlrpcval($this->uid, "int")); /* useid */
            $msg->addParam(new xmlrpcval($this->password, "string"));/** password */
            $msg->addParam(new xmlrpcval($model, "string"));/** model name where operation will held * */
            $msg->addParam(new xmlrpcval("fields_get", "string"));/** method which u like to execute */
            $resp = $client->send($msg);

            if($resp->faultCode()){
                /* if the model is not found */
                throw new Exception($resp->faultString());
            }
        } catch (Exception $e) {
            echo 'Caught exception: ',  $e->getMessage(), "\n";
            return -1;
        }

        return $resp->value();  /* returns True if the unlink is successful and False if not */
    }

    public function get_default_values($model){
        try {
            $values = $this->get_fields($model);

            $columns = array_keys($values);
            $array_temp = array();
            foreach($columns as $column){
                array_push($array_temp, new xmlrpcval($column, "string"));
            }

            $msg = new xmlrpcmsg('execute');
            $msg->addParam(new xmlrpcval($this->database, "string"));  //* database name */
            $msg->addParam(new xmlrpcval($this->uid, "int")); /* useid */
            $msg->addParam(new xmlrpcval($this->password, "string"));/** password */
            $msg->addParam(new xmlrpcval($model, "string"));/** model name where operation will held * */
            $msg->addParam(new xmlrpcval("default_get", "string"));/** method which u like to execute */
            $msg->addParam(new xmlrpcval($array_temp, "array"));

            $resp = $client->send($msg);

            if($resp->faultCode()){
                /* if the model is not found */
                throw new Exception($resp->faultString());
            }
        } catch (Exception $e) {
            echo 'Caught exception: ',  $e->getMessage(), "\n";
            return -1;
        }

        return $resp->value();  /* returns True if the unlink is successful and False if not */
    }

    public function button_click($model, $method, $record_ids){
        try {
            $client = $this->getNewClient('phpvals');

            $nval = array();
            $msg = new xmlrpcmsg('execute');
            $msg->addParam(new xmlrpcval($this->database, "string"));  //* database name */
            $msg->addParam(new xmlrpcval($this->uid, "int")); /* useid */
            $msg->addParam(new xmlrpcval($this->password, "string"));/** password */
            $msg->addParam(new xmlrpcval($model, "string"));/** model name where operation will held * */
            $msg->addParam(new xmlrpcval($method, "string"));/** method which u like to execute */
            $msg->addParam(new xmlrpcval($record_id, "int"));/** parameters of the methods with values....  */

            $resp = $client->send($msg);

            if($resp->faultCode()){
                throw new Exception($resp->faultString());
            }
        } catch (Exception $e) {
            echo 'Caught exception: ',  $e->getMessage(), "\n";
            return -1;
        }

        return $resp->value();  /* returns True if the unlink is successful and False if not */
    }

    public function workflow($model, $method, $record_id) {
        try {
            $client = $this->getNewClient('phpvals');

            $msg = new xmlrpcmsg('exec_workflow');
            $msg->addParam(new xmlrpcval($this->database, "string"));  //* database name */
            $msg->addParam(new xmlrpcval($this->uid, "int")); /* useid */
            $msg->addParam(new xmlrpcval($this->password, "string"));/** password */
            $msg->addParam(new xmlrpcval($model, "string"));/** model name where operation will held * */
            $msg->addParam(new xmlrpcval($method, "string"));/** method which u like to execute */
            $msg->addParam(new xmlrpcval($record_id, "int"));/** parameters of the methods with values....  */

            $resp = $client->send($msg);
            if($resp->faultCode()){
                throw new Exception($resp->faultString());
            }
        } catch (Exception $e) {
            echo 'Caught exception: ',  $e->getMessage(), "\n";
            return -1;
        }

        return $resp->value();  /* returns True if the unlink is successful and False if not */
    }

    protected function getNewClient($ret_type=null, $path="object") {
        try {
            $client = new xmlrpc_client($this->server.$path);
            $client->setDebug($this->debug ? 1 : 0);
            $client->setSSLVerifyHost($this->verifyHost);
            $client->setSSLVerifyPeer($this->verifyPeer);
            $client->return_type = (null == $ret_type) ? 'xmlrpcval' : $ret_type;
        } catch (Exception $e) {
            throw $e;
        }

        return $client;
    }

    // As per https://github.com/sylwit/openerp-php-connector/commit/d5deae376d398d660d15fbeaf1cd704eaf594814
    // Combined with https://github.com/tejastank/openerp-php-connector/commit/0ecde4874632093203cedad0907cdd9d309c5772 to cater for non-array IDs
    protected function convertIds(&$ids){
        if (is_array($ids)){
            foreach ($ids as &$id){
                $id = new xmlrpcval($id, "int");
            }
        } elseif (is_int($ids)) {
            $ids = array(new xmlrpcval($ids, "int"));
        } else {
            // The value od $ids cannot be processed
        }

       return true;
    }
}
?>
