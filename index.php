<?php

// https://twitter.com/snippetbucket

include_once('openerp.class.php');

print "<pre/>\nOpenERP PHP connector : It support version 6 and 7++ <br/>\n Author : Tejas Tank, Tejas.tank.mca@gmail.com\n";
print "~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~\n\n";

$rpc = new OpenERP();

print("========================================================================\n");
print("Testing login\n");
print("========================================================================\n");
$x = $rpc->login("admin", "a", "mobile_client", "http://127.0.0.1:8069/xmlrpc/");

print_r($x);
print("\n");
print("\n");
#echo $rpc->create( array('name'=>'teja22s', 'code'=> "bakbak"), "res.country");

//echo $rpc->create( array('name'=>'teja22s', 'login'=> "bakbak"), "res.users");

//print_r($rpc->get_fields('sale.order'));

//print_r($rpc->get_default_values('sale.order'));

print("========================================================================\n");
print("Testing read\n");
print("========================================================================\n");
$data = $rpc->read(array(1,2), array(), "product.product");
print_r($data);
print("\n");
print("\n");

print("========================================================================\n");
print("Testing search read\n");
print("========================================================================\n");
$data = $rpc->searchread(array('|',array("name","like","HDD"),array("name","like","ML")), "product.product", array("name_template"));
// $data = $rpc->searchread(array(array("name","like","HDD")), "product.product");
print_r($data);
print("\n");
print("\n");

#$data = $rpc->searchread(  array(array('name','=','Service')),  "product.product");  // CORRECT

//$data = $rpc->searchread(  array(array('email','!=','')),  "res.partner");  // CORRECT

##$data = $rpc->read(array(1,2,3,4,5,6,7,8,9), array(), "res.users");
##foreach($data as $p){
##    echo "{$p[name]},{$p[phone]},{$p[email]} {$p[login]} {$p[password]}<br/>";
##}

/////print $partners = $x->unlink(array(19), "res.partner");

?>
