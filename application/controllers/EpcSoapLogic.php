<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class EpcSoapLogic extends CI_Controller {
    function __construct() {   
        $this->CI = & get_instance();
    }
    function postEpcOrderInterface($username, $password, $requester) {
        
            
            $configration = $this->CI->config->item('wsdlconf');

            if (!($username == $configration['username'] && $password == $configration['password'] && ($requester == "CDMS" || $requester == "SFA" ))) {
                return array("result" => FALSE, "error" => "Authentication Fail!!");
            }

            if (!($configration['ordersend'] == $requester)) {
                if ($configration['ordersend'] == "ALL") {
                    // No Action required
                } else {
                    return array("result" => FALSE, "error" => "No New Orders Available EPC to " . $requester);
                }
            }
            $this->CI->load->database();
            $this->CI->db->trans_start();
//            /* order From EPC */
            $this->CI->db->select(' o.id,o.order_number,
                b.name AS brand_name,
                address.house_no,
                address.apartment_name,                
                address.street_details,
                address.landmark_details,
                address.area_details,
                address.city,
                address.pin_code,
                mc_distributor.sfa_mc_distributor_id,
                au_cust.first_name,
                au_cust.last_name,
                profile_cust.phone_number,od.part_number,od.quantity');
            $this->CI->db->from('gm_orderpart as o');
            $this->CI->db->join('gm_brandvertical AS b', 'o.brand_vertical_id = b.id', 'left');
            $this->CI->db->join('gm_user_address_details AS address', 'address.id = o.user_address_id', 'left');
            $this->CI->db->join('auth_user AS au_cust', 'address.user_id = au_cust.id', 'left');
            $this->CI->db->join('gm_userprofile AS profile_cust', 'au_cust.id = profile_cust.user_id', 'left');
            $this->CI->db->join('gm_sfa_mc_distributor AS mc_distributor', 'o.distributor_id = mc_distributor.id', 'left');
            $this->CI->db->join('gm_orderpart_details AS od', 'o.id = od.order_id', 'left');
            if ($requester == "CDMS")
                $this->CI->db->where('o.send_to_cdms', 'pending');
            if ($requester == "SFA")
                $this->CI->db->where('o.send_to_sfa', 'pending');

            $query = $this->CI->db->get();
            $details = ($query->num_rows() > 0) ? $query->result_array() : FALSE;
            $raw_order = $order = $log_mark = array();
            if ($details) {
                foreach ($details as $key => $value) {
                    $raw_order[$value['order_number']]['id'] = $value['id'];
                    $raw_order[$value['order_number']]['brand_vertical'] = $value['brand_name'];
                    $raw_order[$value['order_number']]['order_type'] = "user"; // user/ mechanic / retailer / distributor /dealer
                    $raw_order[$value['order_number']]['dealer_code'] = $value['sfa_mc_distributor_id'];
                    $raw_order[$value['order_number']]['epc_order_no'] = $value['order_number'];
                    $raw_order[$value['order_number']]['customer_name'] = $value['first_name'] . " " . $value['last_name'];
                    $raw_order[$value['order_number']]['phone_number'] = $value['phone_number'];
                    $raw_order[$value['order_number']]['house_no'] = $value['house_no'];
                    $raw_order[$value['order_number']]['apartment_name'] = $value['apartment_name'];
                    $raw_order[$value['order_number']]['street_detail'] = $value['street_details'];
                    $raw_order[$value['order_number']]['landmark_details'] = $value['landmark_details'];
                    $raw_order[$value['order_number']]['area_details'] = $value['area_details'];
                    $raw_order[$value['order_number']]['city'] = $value['city'];
                    $raw_order[$value['order_number']]['pin_code'] = $value['pin_code'];
                    $raw_order[$value['order_number']]['order_parts'][$key]['part_number'] = $value['part_number'];
                    $raw_order[$value['order_number']]['order_parts'][$key]['part_quantity'] = $value['quantity'];
                }


                /* prepare array for sending order */
                $i = 0;
                foreach ($raw_order as $key => $value) {
                    $log_mark[$i]['id'] = $value['id'];
                    if ($requester == "CDMS")
                    {
                        $log_mark[$i]['send_to_cdms'] = "sent";
                        $log_mark[$i]['send_to_cdms_date'] = date('Y-m-d H:i:s');;
                    }
                    if ($requester == "SFA")
                    {
                        $log_mark[$i]['send_to_sfa'] = "sent";
                        $log_mark[$i]['send_to_sfa_date'] = date('Y-m-d H:i:s');;
                    }

                    $order[$i]['brand_vertical'] = $value['brand_vertical'];
                    $order[$i]['order_type'] = $value['order_type'];
                    $order[$i]['dealer_code'] = $value['dealer_code'];
                    $order[$i]['epc_order_no'] = $value['epc_order_no'];
                    $order[$i]['customer_name'] = $value['customer_name'];
                    $order[$i]['phone_number'] = $value['phone_number'];
                    $order[$i]['house_no'] = $value['house_no'];
                    $order[$i]['apartment_name'] = $value['apartment_name'];
                    $order[$i]['street_detail'] = $value['street_detail'];
                    $order[$i]['landmark_details'] = $value['landmark_details'];
                    $order[$i]['area_details'] = $value['area_details'];
                    $order[$i]['city'] = $value['city'];
                    $order[$i]['pin_code'] = $value['pin_code'];
                    $j = 0;
                    foreach ($value['order_parts'] as $key_op => $value_op) {
                        $order[$i]['order_parts'][$j]['part_number'] = $value_op['part_number'];
                        $order[$i]['order_parts'][$j]['part_quantity'] = $value_op['part_quantity'];
                        $j++;
                    }
                    $i++;
                }

                /* mark sent data as sent to system */
                try {
                   /* $this->CI->db->update_batch('gm_orderpart', $log_mark, 'id');
                    $error = $this->CI->db->error();
                    if ($error['code'] != 0) {
                        throw new Exception('no data returned ' . $error['message']);
                    }*/

                    $op['result'] = TRUE;
                    $op['OrderArray'] = $order;
                } catch (Exception $e) {
                    $op['result'] = FALSE;
                    $op['error'] = $e->getMessage();
                }
            } else {
                $op['result'] = FALSE;
                $op['error'] = "No New Orders Available";
            }

            /* Mark orders sent to cdms */

            $this->CI->db->trans_complete();
            if ($this->CI->db->trans_status() === FALSE) { // error
                $op['result'] = FALSE;
                $op['error'] = "No New Orders Available";
            }

            return $op;
        }
}

