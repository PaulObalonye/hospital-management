<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Prescription extends CI_Controller {


	public function __construct()
	{
		parent::__construct();
		
		$this->load->model(array(
			'dashboard_doctor/prescription/insurance_model',
			'dashboard_doctor/prescription/medicine_model',
			'dashboard_doctor/prescription/case_study_model',
			'dashboard_doctor/prescription/prescription_model'
		));
		
		if ($this->session->userdata('isLogIn') == false) 
		redirect('login'); 

	}

	public function index(){
	    $data['module'] = display("prescription");
		$data['title'] = display('prescription_list');
		$role = $this->session->userdata('user_role');
		#-------------------------------#
		if($role==1){
			$data['prescription'] = $this->prescription_model->read_admin();
			$data['content'] = $this->load->view('prescription/prescription_admin_list',$data,true);
		}else{
			$data['prescription'] = $this->prescription_model->read();
			$data['content'] = $this->load->view('prescription/prescription',$data,true);
		}
		$this->load->view('layout/main_wrapper',$data);
	} 


 	public function create(){   
 		$data['module'] = display("prescription");
		$data['title'] = display('add_prescription');
		$appId = "A".$this->randStrGen(2, 7).$this->session->userdata('user_id');
		$data['appointment_id'] = (($this->input->get('aid')!=null)?$this->input->get('aid'):$appId);
		$data['patient_id'] = (($this->input->get('pid')!=null)?$this->input->get('pid'):null);
		#-------------------------------#
		$this->form_validation->set_rules('patient_id', display('patient_id') ,'required|max_length[30]');
		#-------------------------------#
		if ($this->form_validation->run() === true) 
		{
			#----------------------proccess of medicine----------------------#
			$medicine_name = $this->input->post('medicine_name');
			$medicine_type = $this->input->post('medicine_type');
			$medicine_instruction = $this->input->post('medicine_instruction');
			$medicine_days = $this->input->post('medicine_days');

			$medicine = array();
			if (!empty($medicine_name) && is_array($medicine_name) && sizeof($medicine_name) > 0) 
			{
				for ($i=0; $i < sizeof($medicine_name); $i++) { 
					$medicine[$i] = array(
						'name' => $medicine_name[$i],
						'type' => $medicine_type[$i],
						'instruction' => $medicine_instruction[$i],
						'days' => $medicine_days[$i],
					);
				}
			} 
			$medicine = json_encode($medicine); 
			#----------------------proccess of diagnosis----------------------#

			$diagnosis_name = $this->input->post('diagnosis_name');
			$diagnosis_instruction = $this->input->post('diagnosis_instruction');

			$diagnosis = array();
			if (!empty($diagnosis_name) && is_array($diagnosis_name) && sizeof($diagnosis_name) > 0) 
			{
				for ($i=0; $i < sizeof($diagnosis_name); $i++) { 
					$diagnosis[$i] = array(
						'name' 		  => $diagnosis_name[$i],
						'instruction' => $diagnosis_instruction[$i],
					);
				}
			} 
			$diagnosis = json_encode($diagnosis);  

			#----------------------proccess of data----------------------#  
			$preData = array(
				'appointment_id' => $this->input->post('appointment_id'),
				'patient_id'     => $this->input->post('patient_id'),
				'patient_type'   => $this->input->post('patient_type'),
				'doctor_id'      => $this->session->userdata('user_id'),
				'date'           => date('Y-m-d', strtotime($this->input->post('date'))),
				'chief_complain' => $this->input->post('chief_complain'),
				'insurance_id'   => $this->input->post('insurance_id'),
				'policy_no'      => $this->input->post('policy_no'),
				'blood_pressure' => $this->input->post('blood_pressure'),
				'weight' 		 => $this->input->post('weight'),
				'reference_by'   => $this->input->post('reference_by'),
				'medicine'       => $medicine,
				'diagnosis'      => $diagnosis,
				'visiting_fees'  => $this->input->post('visiting_fees'),
				'patient_notes'  => $this->input->post('patient_notes'),
			); 
			  
			if ($this->prescription_model->create($preData)) {
				// change appointment status
				$appoint['status'] = 0;
				$this->db->where('appointment_id', $preData['appointment_id'])
						 ->update('appointment', $appoint);
				#------------------------------#

			    #-----Chart Of Account Info----#
			   $p_name = $this->db->select('firstname,lastname')->from('patient')->where('patient_id',$preData['patient_id'])->get()->row();
			  
			   $c_acc=$preData['patient_id'].'-'.$p_name->firstname.'-'.$p_name->lastname;
		       $coatransactionInfo = $this->db->select('HeadCode')->from('acc_coa')->where('HeadName',$c_acc)->get()->row();
		       $COAID = $coatransactionInfo->HeadCode;

			   // patient cash in for credit 
			   $patientCashInCredit = array(
			      'VNo'         => $preData['appointment_id'],
			      'Vtype'       => 'Doctor Fee',
			      'VDate'       => date('Y-m-d'),
			      'COAID'       => $COAID,
			      'Narration'   => 'Patient Credit For Doctor Fee',
			      'Debit'       => 0,
			      'Credit'      => $preData['visiting_fees'],
			      'StoreID'     => 2,
			      'IsPosted'    => 1,
			      'CreateBy'    => $this->session->userdata('user_id'),
			      'CreateDate'  => date('Y-m-d H:i:s'),
			      'IsAppove'    => 1
		       ); 

		     //ACC receivable  Debit
		 	  $receivable = array(
			      'VNo'            => $preData['appointment_id'],
			      'Vtype'          => 'Doctor Fee',
			      'VDate'          => date('Y-m-d'),
			      'COAID'          => 1020302,
			      'Narration'      => 'Cash In Hand Debit For Doctor Fee',
			      'Debit'          => $preData['visiting_fees'],
			      'Credit'         => 0,
			      'IsPosted'       => 1,
			      'StoreID'        => 2,
			      'CreateBy'       => $this->session->userdata('user_id'),
			      'CreateDate'     => date('Y-m-d H:i:s'),
			      'IsAppove'       => 1
			    );
				// insert transaction
				$this->db->insert('acc_transaction',$patientCashInCredit);
			    $this->db->insert('acc_transaction',$receivable);
			    #--------------------------------#

				#set success message
				$this->session->set_flashdata('message', display('save_successfully'));
			} else {
				#set exception message
				$this->session->set_flashdata('exception',display('please_try_again'));
			}
			redirect('prescription/prescription/create');

		} else {

			$data['insurance_list'] = $this->insurance_model->insurance_list();
			$data['medicine_list'] = $this->medicine_model->medicine_list();
			$data['website'] = $this->prescription_model->website();
			$data['content'] = $this->load->view('dashboard_doctor/prescription/prescription_form',$data,true);
			$this->load->view('layout/main_wrapper',$data);
		} 
	}


	public function view($id = null) {
		$data['module'] = display("prescription");
		$data['title'] = display('prescription_information');
		$role = $this->session->userdata('user_role');
		#-------------------------------#
		$data['website'] = $this->prescription_model->website();
		if($role==1){
			$data['prescription'] = $this->prescription_model->single_view_admin($id); 
		    $data['content'] = $this->load->view('prescription/prescription_view',$data,true);
		}else{
			$data['prescription'] = $this->prescription_model->single_view($id);
			$data['insurance_list'] = $this->insurance_model->insurance_list();
			$data['medicine_list'] = $this->medicine_model->medicine_list();
			$data['content'] = $this->load->view('dashboard_doctor/prescription/prescription_view',$data,true);
		}
		
		$this->load->view('layout/main_wrapper',$data);
	}


	public function edit($id = null) { 
		$data['module'] = display("prescription");
		$data['title'] = display('edit_prescription');
		#-------------------------------#
		$this->form_validation->set_rules('patient_id', display('patient_id') ,'required|max_length[30]');
		#-------------------------------#
		if ($this->form_validation->run() === true) 
		{
			#----------------------proccess of medicine----------------------#
			$medicine_name = $this->input->post('medicine_name');
			$medicine_type = $this->input->post('medicine_type');
			$medicine_instruction = $this->input->post('medicine_instruction');
			$medicine_days = $this->input->post('medicine_days');

			$medicine = array();
			if (!empty($medicine_name) && is_array($medicine_name) && sizeof($medicine_name) > 0) 
			{
				for ($i=0; $i < sizeof($medicine_name); $i++) { 
					$medicine[$i] = array(
						'name' => $medicine_name[$i],
						'type' => $medicine_type[$i],
						'instruction' => $medicine_instruction[$i],
						'days' => $medicine_days[$i],
					);
				}
			} 
			$medicine = json_encode($medicine); 
			#----------------------proccess of diagnosis----------------------#

			$diagnosis_name = $this->input->post('diagnosis_name');
			$diagnosis_instruction = $this->input->post('diagnosis_instruction');

			$diagnosis = array();
			if (!empty($diagnosis_name) && is_array($diagnosis_name) && sizeof($diagnosis_name) > 0) 
			{
				for ($i=0; $i < sizeof($diagnosis_name); $i++) { 
					$diagnosis[$i] = array(
						'name' 		  => $diagnosis_name[$i],
						'instruction' => $diagnosis_instruction[$i],
					);
				}
			} 
			$diagnosis = json_encode($diagnosis);  

			#----------------------proccess of data----------------------#  
			$preData = array(
				'id'			 => $this->input->post('id'),
				'appointment_id'=> $this->input->post('appointment_id'),
				'patient_id'     => $this->input->post('patient_id'),
				'patient_type'   => $this->input->post('patient_type'),
				'doctor_id'      => $this->session->userdata('user_id'),
				'date'           => date('Y-m-d', strtotime($this->input->post('date'))),
				'chief_complain' => $this->input->post('chief_complain'),
				'insurance_id'   => $this->input->post('insurance_id'),
				'policy_no'      => $this->input->post('policy_no'),
				'blood_pressure' => $this->input->post('blood_pressure'),
				'weight' 		 => $this->input->post('weight'),
				'reference_by'   => $this->input->post('reference_by'),
				'medicine'       => $medicine,
				'diagnosis'      => $diagnosis,
				'visiting_fees'  => $this->input->post('visiting_fees'),
				'patient_notes'  => $this->input->post('patient_notes'),
			); 
			
			if ($this->prescription_model->update($preData)) { 
				#set success message
				$this->session->set_flashdata('message', display('update_successfully'));
			} else {
				#set exception message
				$this->session->set_flashdata('exception',display('please_try_again'));
			}
			redirect('dashboard_doctor/prescription/prescription/edit/'.$id);

		} else {
			$data['prescription'] = $this->prescription_model->single_view($id); 
			$data['insurance_list'] = $this->insurance_model->insurance_list();
			$data['medicine_list'] = $this->medicine_model->medicine_list();
			$data['website'] = $this->prescription_model->website();
			$data['content'] = $this->load->view('dashboard_doctor/prescription/prescription_edit',$data,true);
			$this->load->view('layout/main_wrapper',$data);
		} 		
	}
 

	public function delete($id = null) 
	{
		if ($this->prescription_model->delete($id)) 
		{
			#set success message
			$this->session->set_flashdata('message', display('delete_successfully'));
		} else {
			#set exception message
			$this->session->set_flashdata('exception', display('please_try_again'));
		}
		redirect('prescription/prescription');
	}


	//patient information
	public function patient()
	{
		$patient   = $this->prescription_model->patient();
		if ($patient->num_rows() > 0) {
			$data['status']        = true;
			$data['name']          = $patient->row()->firstname.' '.$patient->row()->lastname; 
			$data['sex']           = $patient->row()->sex;
			$data['date_of_birth'] = $patient->row()->date_of_birth;
		} else {
			$data['status'] = false;
		}
		echo json_encode($data);
	}
 
	// case study 
	public function case_study()
	{
		$patient_id = $this->input->post('patient_id');
		$data = $this->case_study_model->read_by_patient_id($patient_id);
		echo json_encode($data);
	}
 
    /*
    |----------------------------------------------
    |        id genaretor
    |----------------------------------------------     
    */
    public function randStrGen($mode = 2, $len = 7)
    {
        $result = "";
        if($mode == 1):
            $chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
        elseif($mode == 2):
            $chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        elseif($mode == 3):
            $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        elseif($mode == 4):
            $chars = "0123456789";
        endif;

        $charArray = str_split($chars);
        for($i = 1; $i <= $len; $i++) {
                $randItem = array_rand($charArray);
                $result .="".$charArray[$randItem];
        }
        return $result;
    } 
    /*
    |----------------------------------------------
    |         Ends of id genaretor
    |----------------------------------------------
    */ 
  
}

