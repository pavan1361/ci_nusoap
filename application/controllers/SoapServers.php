<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
require_once 'EpcSoapLogic.php';

/**
 * Description of SoapServer
 *
 * @author pavaningalkar
 */
//put your code here
class SoapServers extends CI_Controller {

    function __construct() {
        parent::__construct();
        $this->load->library("nuSoap_lib"); //load the library here  
        ob_end_clean();        
    }
    
    

    public function index() {
        $op = array();
        $this->nusoap_server = new soap_server();

        $this->nusoap_server->configureWSDL("EpcSoap", base_url()."SoapServers?wsdl",base_url()."SoapServers");
        //WSDL Schema
        $this->nusoap_server->wsdl->addComplexType('orderDtlArray','complexType', 'struct', 'all', '',
                array( 
                'part_number' => array('name' => 'part_number',
                'type' => 'xsd:string'),
            'part_quantity' => array('name' => 'part_quantity',
                'type' => 'xsd:string'))
        );        
        $this->nusoap_server->wsdl->addComplexType(
                'OrderDetailsArray', 'complexType', 'array', '', 'SOAP-ENC:Array',array() ,array(
                    array('ref'=>'SOAP-ENC:arrayType', 'wsdl:arrayType'=>'tns:orderDtlArray[]')),'tns:orderDtlArray'                                
                
        );

        $this->nusoap_server->wsdl->addComplexType(
                'OrderArray', 'complexType', 'struct', 'all', '', array(
            'order_type' => array('name' => 'order_type',
                'type' => 'xsd:string'),
            'brand_vertical' => array('name' => 'brand_vertical',
                'type' => 'xsd:string'),
            'dealer_code' => array('name' => 'dealer_code',
                'type' => 'xsd:string'),
            'epc_order_no' => array('name' => 'epc_order_no',
                'type' => 'xsd:string'),
            'customer_name' => array('name' => 'customer_name',
                'type' => 'xsd:string'),
            'phone_number' => array('name' => 'phone_number',
                'type' => 'xsd:string'),
            'house_no' => array('name' => 'house_no',
                'type' => 'xsd:string'),
            'apartment_name' => array('name' => 'apartment_name',
                'type' => 'xsd:string'),
            'street_detail' => array('name' => 'street_detail',
                'type' => 'xsd:string'),
            'landmark_details' => array('name' => 'landmark_details',
                'type' => 'xsd:string'),
            'area_details' => array('name' => 'area_details',
                'type' => 'xsd:string'),
            'city' => array('name' => 'city',
                'type' => 'xsd:string'),
            'pin_code' => array('name' => 'pin_code',
                'type' => 'xsd:string'),
            'order_parts' => array('name' => 'order_parts',
                'type' => 'tns:OrderDetailsArray')
                )
        );

        $this->nusoap_server->wsdl->addComplexType(
                'OrderData', 'complexType', 'array', '', 'SOAP-ENC:Array', array(), array(
            array('ref' => 'SOAP-ENC:arrayType',
                'wsdl:arrayType' => 'tns:OrderArray[]')
                ), 'tns:OrderArray'
        );
        
        
        
        
        /*register precess */
        $this->nusoap_server->register('postEpcOrderInterface', array('UserName' => 'xsd:string', 'Password' => 'xsd:string','Requester'=> 'xsd:string'), array('result' => 'xsd:bool', 'OrderArray' => 'tns:OrderData', 'error' => 'xsd:string'), base_url().'SoapServers', '', 'rpc', 'encoded', 'Order created by dealer group and non-dealer group will flow from  EPC to Bajaj-CDMS.');
        

        function postEpcOrderInterface($username, $password, $requester) {
            $logic = new EpcSoapLogic();
           return $logic->postEpcOrderInterface($username, $password, $requester);
        }
        
        $this->nusoap_server->service(file_get_contents("php://input")); //shows the standard info about service
    }   
    

}

