<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class EpcClient extends CI_Controller {

    function __construct() {
        parent::__construct();
        $this->load->library("nuSoap_lib"); //load the library here
        ob_end_clean();
        $this->load->database();
        $this->load->model("Common_model");
    }

    public function get_dealers() {
        $configration = $this->config->item('wsdlconf');        
        $proxyhost = $proxyport = $proxyusername = $proxypassword = "";
        
        $client = new nusoap_client('InterfaceWebService.WSDL', 'wsdl', $proxyhost, $proxyport, $proxyusername, $proxypassword, 600, 600);
        $err = $client->getError();
        if ($err) {
            /* Constructor error */
            $err .=" \n";
            file_put_contents($configration['log_path'].'wsdl_error_' . date("j.n.Y") . '.log', $err, FILE_APPEND);
        }

        $request = array('UserName' => 'balcrtest.espl', 'Password' => '123');
        $result = $client->call('PullEPCDealerRegister', array('request' => $request), '', '', false, true);
        var_dump($result,true);
        if ($client->fault) {
            /* error logging */
            $err .=" \n";
            file_put_contents($configration['log_path'].'wsdl_error_' . date("j.n.Y") . '.log', $err, FILE_APPEND);
        } else {
            // Check for errors
            $err = $client->getError();
            if ($err) {
                $err .=" \n";
                file_put_contents($configration['log_path'].'wsdl_error_' . date("j.n.Y") . '.log', $err, FILE_APPEND);
            } else {
                // Display the result
                $responce = $result['PullEPCDealerRegisterResult'];
                /* make entry in gm_cdms_dealer_master */
                $request_master['is_request_successful'] = (boolean) $responce['IsRequestSuccessful'];
                $request_master['error_code'] = $responce['ErrorCode'];
                $request_master['request_key'] = $responce['RequestKey'];
                $request_master['server_processing_ticks'] = $responce['ServerProcessingTicks'];
                $request_master['processing_server'] = $responce['ProcessingServer'];
                $request_master['created_date'] = date("Y-m-d h:i:s");
                $request_master['is_data_receive'] = FALSE;
                $master_id = $this->Common_model->insert_info('gm_cdms_dealer_master', $request_master);

                if ($responce['IsRequestSuccessful']) {
                    if ($responce['EPCDealerRegister']) {
                        $dealers_data = $responce['EPCDealerRegister']['EPCDealerRegister'];
                        if ($master_id) {
                            $receive_dealer_info = array();
                            foreach ($dealers_data as $key => $value) {
                                $receive_dealer_info[$key]['gm_cdms_dealer_master_id'] = $master_id;
                                $receive_dealer_info[$key]['brand_vertical'] = $value['BrandVertical'];
                                $receive_dealer_info[$key]['dealer_code'] = $value['DealerCode'];
                                $receive_dealer_info[$key]['dealer_name'] = $value['DealerName'];
                                $receive_dealer_info[$key]['email'] = $value['Email'];
                                $receive_dealer_info[$key]['mobile1'] = $value['Mobile1'];
                                $receive_dealer_info[$key]['mobile2'] = $value['Mobile2'];
                                $receive_dealer_info[$key]['shop_address'] = $value['ShopAddress'];
                                $receive_dealer_info[$key]['city'] = $value['City'];
                                $receive_dealer_info[$key]['state'] = $value['State'];
                                $receive_dealer_info[$key]['pin_code'] = $value['PinCode'];
                                $receive_dealer_info[$key]['latitude'] = $value['Latitude'];
                                $receive_dealer_info[$key]['longitude'] = $value['Longitude'];
                            }
                            $this->db->insert_batch('gm_cdms_dealer_data', $receive_dealer_info);
                        }

                        $this->Common_model->update_info('gm_cdms_dealer_master', array('is_data_receive' => TRUE), array('id' => $master_id));
                    } else {
                        file_put_contents($configration['log_path'].'wsdl_log' . date("j.n.Y") . '.log', " --- \n No Data --".date("Y-m-d h:i:s")." \n", FILE_APPEND);
                    }
                } else {
                    file_put_contents($configration['log_path'].'wsdl_log' . date("j.n.Y") . '.log', " --- \n No Data  --".date("Y-m-d h:i:s")." \n", FILE_APPEND);
                }
            }
        }
    }

}

