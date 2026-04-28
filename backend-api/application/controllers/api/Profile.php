<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @property CI_Session $session
 * @property CI_Input $input
 * @property Profile_model $Profile_model
 * @property User_model $User_model
 * @property CI_Upload $upload
 * @property CI_DB_query_builder $db
 */
class Profile extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Profile_model');
        $this->load->library(['session', 'upload']);
        $this->load->helper(['url']);
    }

    public function index()
    {
        if ($this->input->method(TRUE) !== 'GET') {
            return $this->method_not_allowed();
        }

        $this->enforce_auth();
        $user_id = $this->require_login();

        $profile = $this->Profile_model->get_profile_by_user_id($user_id);

        return $this->json_response([
            'status' => true,
            'message' => 'Profile fetched successfully.',
            'data' => [
                'profile' => $profile
            ]
        ]);
    }

    public function save()
    {
        if ($this->input->method(TRUE) !== 'POST') {
            return $this->method_not_allowed();
        }

        $this->enforce_auth();
        $user_id = $this->require_login();
        $data = $this->get_json_input();

        $headline = trim((string) ($data['headline'] ?? ''));
        $biography = trim((string) ($data['biography'] ?? ''));
        $linkedin_url = trim((string) ($data['linkedin_url'] ?? ''));
        $current_job_title = trim((string) ($data['current_job_title'] ?? ''));
        $current_company = trim((string) ($data['current_company'] ?? ''));

        $errors = [];

        if ($linkedin_url !== '' && !filter_var($linkedin_url, FILTER_VALIDATE_URL)) {
            $errors['linkedin_url'] = 'A valid LinkedIn URL is required.';
        }

        if (!empty($errors)) {
            return $this->validation_error($errors);
        }

        $profile_data = [
            'headline' => $headline !== '' ? $headline : null,
            'biography' => $biography !== '' ? $biography : null,
            'linkedin_url' => $linkedin_url !== '' ? $linkedin_url : null,
            'current_job_title' => $current_job_title !== '' ? $current_job_title : null,
            'current_company' => $current_company !== '' ? $current_company : null
        ];

        $profile_id = $this->Profile_model->get_profile_id_by_user_id($user_id);

        if ($profile_id) {
            $this->Profile_model->update_profile_by_user_id($user_id, $profile_data);
        } else {
            $profile_data['user_id'] = $user_id;
            $profile_data['is_profile_complete'] = 0;
            $profile_id = $this->Profile_model->create_profile($profile_data);
        }

        $this->update_profile_completion($user_id);

        return $this->json_response([
            'status' => true,
            'message' => 'Profile saved successfully.',
            'data' => [
                'profile_id' => $profile_id
            ]
        ]);
    }

    public function add_degree()
    {
        if ($this->input->method(TRUE) !== 'POST') {
            return $this->method_not_allowed();
        }

        $this->enforce_auth();
        $profile_id = $this->require_profile_id();
        $data = $this->get_json_input();

        $degree_name = trim((string) ($data['degree_name'] ?? ''));
        $institution_name = trim((string) ($data['institution_name'] ?? ''));
        $degree_url = trim((string) ($data['degree_url'] ?? ''));
        $completion_date = trim((string) ($data['completion_date'] ?? ''));

        $errors = [];

        if ($degree_name === '') {
            $errors['degree_name'] = 'Degree name is required.';
        }

        if ($institution_name === '') {
            $errors['institution_name'] = 'Institution name is required.';
        }

        if ($degree_url !== '' && !filter_var($degree_url, FILTER_VALIDATE_URL)) {
            $errors['degree_url'] = 'A valid degree URL is required.';
        }

        if (!empty($errors)) {
            return $this->validation_error($errors);
        }

        $degree_id = $this->Profile_model->add_degree([
            'profile_id' => $profile_id,
            'degree_name' => $degree_name,
            'institution_name' => $institution_name,
            'degree_url' => $degree_url !== '' ? $degree_url : null,
            'completion_date' => $completion_date !== '' ? $completion_date : null
        ]);

        $this->update_profile_completion_by_profile_id($profile_id);

        return $this->json_response([
            'status' => true,
            'message' => 'Degree added successfully.',
            'data' => [
                'degree_id' => $degree_id
            ]
        ], 201);
    }

    public function update_degree($id)
    {
        if ($this->input->method(TRUE) !== 'PUT') {
            return $this->method_not_allowed();
        }

        $this->enforce_auth();
        $profile_id = $this->require_profile_id();
        $data = $this->get_json_input();

        $degree_name = trim((string) ($data['degree_name'] ?? ''));
        $institution_name = trim((string) ($data['institution_name'] ?? ''));
        $degree_url = trim((string) ($data['degree_url'] ?? ''));
        $completion_date = trim((string) ($data['completion_date'] ?? ''));

        $errors = [];

        if ($degree_name === '') {
            $errors['degree_name'] = 'Degree name is required.';
        }

        if ($institution_name === '') {
            $errors['institution_name'] = 'Institution name is required.';
        }

        if ($degree_url !== '' && !filter_var($degree_url, FILTER_VALIDATE_URL)) {
            $errors['degree_url'] = 'A valid degree URL is required.';
        }

        if (!empty($errors)) {
            return $this->validation_error($errors);
        }

        $this->Profile_model->update_degree((int) $id, $profile_id, [
            'degree_name' => $degree_name,
            'institution_name' => $institution_name,
            'degree_url' => $degree_url !== '' ? $degree_url : null,
            'completion_date' => $completion_date !== '' ? $completion_date : null
        ]);

        $this->update_profile_completion_by_profile_id($profile_id);

        return $this->json_response([
            'status' => true,
            'message' => 'Degree updated successfully.'
        ]);
    }

    public function delete_degree($id)
    {
        if ($this->input->method(TRUE) !== 'DELETE') {
            return $this->method_not_allowed();
        }

        $this->enforce_auth();
        $profile_id = $this->require_profile_id();

        $this->Profile_model->delete_degree((int) $id, $profile_id);
        $this->update_profile_completion_by_profile_id($profile_id);

        return $this->json_response([
            'status' => true,
            'message' => 'Degree deleted successfully.'
        ]);
    }

    public function add_certification()
    {
        if ($this->input->method(TRUE) !== 'POST') {
            return $this->method_not_allowed();
        }

        $this->enforce_auth();
        $profile_id = $this->require_profile_id();
        $data = $this->get_json_input();

        $certification_name = trim((string) ($data['certification_name'] ?? ''));
        $provider_name = trim((string) ($data['provider_name'] ?? ''));
        $certificate_url = trim((string) ($data['certificate_url'] ?? ''));
        $completion_date = trim((string) ($data['completion_date'] ?? ''));

        $errors = [];

        if ($certification_name === '') {
            $errors['certification_name'] = 'Certification name is required.';
        }

        if ($provider_name === '') {
            $errors['provider_name'] = 'Provider name is required.';
        }

        if ($certificate_url !== '' && !filter_var($certificate_url, FILTER_VALIDATE_URL)) {
            $errors['certificate_url'] = 'A valid certificate URL is required.';
        }

        if (!empty($errors)) {
            return $this->validation_error($errors);
        }

        $certification_id = $this->Profile_model->add_certification([
            'profile_id' => $profile_id,
            'certification_name' => $certification_name,
            'provider_name' => $provider_name,
            'certificate_url' => $certificate_url !== '' ? $certificate_url : null,
            'completion_date' => $completion_date !== '' ? $completion_date : null
        ]);

        $this->update_profile_completion_by_profile_id($profile_id);

        return $this->json_response([
            'status' => true,
            'message' => 'Certification added successfully.',
            'data' => [
                'certification_id' => $certification_id
            ]
        ], 201);
    }

    public function update_certification($id)
    {
        if ($this->input->method(TRUE) !== 'PUT') {
            return $this->method_not_allowed();
        }

        $this->enforce_auth();
        $profile_id = $this->require_profile_id();
        $data = $this->get_json_input();

        $certification_name = trim((string) ($data['certification_name'] ?? ''));
        $provider_name = trim((string) ($data['provider_name'] ?? ''));
        $certificate_url = trim((string) ($data['certificate_url'] ?? ''));
        $completion_date = trim((string) ($data['completion_date'] ?? ''));

        $errors = [];

        if ($certification_name === '') {
            $errors['certification_name'] = 'Certification name is required.';
        }

        if ($provider_name === '') {
            $errors['provider_name'] = 'Provider name is required.';
        }

        if ($certificate_url !== '' && !filter_var($certificate_url, FILTER_VALIDATE_URL)) {
            $errors['certificate_url'] = 'A valid certificate URL is required.';
        }

        if (!empty($errors)) {
            return $this->validation_error($errors);
        }

        $this->Profile_model->update_certification((int) $id, $profile_id, [
            'certification_name' => $certification_name,
            'provider_name' => $provider_name,
            'certificate_url' => $certificate_url !== '' ? $certificate_url : null,
            'completion_date' => $completion_date !== '' ? $completion_date : null
        ]);

        $this->update_profile_completion_by_profile_id($profile_id);

        return $this->json_response([
            'status' => true,
            'message' => 'Certification updated successfully.'
        ]);
    }

    public function delete_certification($id)
    {
        if ($this->input->method(TRUE) !== 'DELETE') {
            return $this->method_not_allowed();
        }

        $this->enforce_auth();
        $profile_id = $this->require_profile_id();

        $this->Profile_model->delete_certification((int) $id, $profile_id);
        $this->update_profile_completion_by_profile_id($profile_id);

        return $this->json_response([
            'status' => true,
            'message' => 'Certification deleted successfully.'
        ]);
    }

    public function add_license()
    {
        if ($this->input->method(TRUE) !== 'POST') {
            return $this->method_not_allowed();
        }

        $this->enforce_auth();
        $profile_id = $this->require_profile_id();
        $data = $this->get_json_input();

        $license_name = trim((string) ($data['license_name'] ?? ''));
        $awarding_body = trim((string) ($data['awarding_body'] ?? ''));
        $license_url = trim((string) ($data['license_url'] ?? ''));
        $completion_date = trim((string) ($data['completion_date'] ?? ''));

        $errors = [];

        if ($license_name === '') {
            $errors['license_name'] = 'License name is required.';
        }

        if ($awarding_body === '') {
            $errors['awarding_body'] = 'Awarding body is required.';
        }

        if ($license_url !== '' && !filter_var($license_url, FILTER_VALIDATE_URL)) {
            $errors['license_url'] = 'A valid license URL is required.';
        }

        if (!empty($errors)) {
            return $this->validation_error($errors);
        }

        $license_id = $this->Profile_model->add_license([
            'profile_id' => $profile_id,
            'license_name' => $license_name,
            'awarding_body' => $awarding_body,
            'license_url' => $license_url !== '' ? $license_url : null,
            'completion_date' => $completion_date !== '' ? $completion_date : null
        ]);

        $this->update_profile_completion_by_profile_id($profile_id);

        return $this->json_response([
            'status' => true,
            'message' => 'License added successfully.',
            'data' => [
                'license_id' => $license_id
            ]
        ], 201);
    }

    public function update_license($id)
    {
        if ($this->input->method(TRUE) !== 'PUT') {
            return $this->method_not_allowed();
        }

        $this->enforce_auth();
        $profile_id = $this->require_profile_id();
        $data = $this->get_json_input();

        $license_name = trim((string) ($data['license_name'] ?? ''));
        $awarding_body = trim((string) ($data['awarding_body'] ?? ''));
        $license_url = trim((string) ($data['license_url'] ?? ''));
        $completion_date = trim((string) ($data['completion_date'] ?? ''));

        $errors = [];

        if ($license_name === '') {
            $errors['license_name'] = 'License name is required.';
        }

        if ($awarding_body === '') {
            $errors['awarding_body'] = 'Awarding body is required.';
        }

        if ($license_url !== '' && !filter_var($license_url, FILTER_VALIDATE_URL)) {
            $errors['license_url'] = 'A valid license URL is required.';
        }

        if (!empty($errors)) {
            return $this->validation_error($errors);
        }

        $this->Profile_model->update_license((int) $id, $profile_id, [
            'license_name' => $license_name,
            'awarding_body' => $awarding_body,
            'license_url' => $license_url !== '' ? $license_url : null,
            'completion_date' => $completion_date !== '' ? $completion_date : null
        ]);

        $this->update_profile_completion_by_profile_id($profile_id);

        return $this->json_response([
            'status' => true,
            'message' => 'License updated successfully.'
        ]);
    }

    public function delete_license($id)
    {
        if ($this->input->method(TRUE) !== 'DELETE') {
            return $this->method_not_allowed();
        }

        $this->enforce_auth();
        $profile_id = $this->require_profile_id();

        $this->Profile_model->delete_license((int) $id, $profile_id);
        $this->update_profile_completion_by_profile_id($profile_id);

        return $this->json_response([
            'status' => true,
            'message' => 'License deleted successfully.'
        ]);
    }

    public function add_short_course()
    {
        if ($this->input->method(TRUE) !== 'POST') {
            return $this->method_not_allowed();
        }

        $this->enforce_auth();
        $profile_id = $this->require_profile_id();
        $data = $this->get_json_input();

        $course_name = trim((string) ($data['course_name'] ?? ''));
        $provider_name = trim((string) ($data['provider_name'] ?? ''));
        $course_url = trim((string) ($data['course_url'] ?? ''));
        $completion_date = trim((string) ($data['completion_date'] ?? ''));

        $errors = [];

        if ($course_name === '') {
            $errors['course_name'] = 'Course name is required.';
        }

        if ($provider_name === '') {
            $errors['provider_name'] = 'Provider name is required.';
        }

        if ($course_url !== '' && !filter_var($course_url, FILTER_VALIDATE_URL)) {
            $errors['course_url'] = 'A valid course URL is required.';
        }

        if (!empty($errors)) {
            return $this->validation_error($errors);
        }

        $short_course_id = $this->Profile_model->add_short_course([
            'profile_id' => $profile_id,
            'course_name' => $course_name,
            'provider_name' => $provider_name,
            'course_url' => $course_url !== '' ? $course_url : null,
            'completion_date' => $completion_date !== '' ? $completion_date : null
        ]);

        $this->update_profile_completion_by_profile_id($profile_id);

        return $this->json_response([
            'status' => true,
            'message' => 'Short course added successfully.',
            'data' => [
                'short_course_id' => $short_course_id
            ]
        ], 201);
    }

    public function update_short_course($id)
    {
        if ($this->input->method(TRUE) !== 'PUT') {
            return $this->method_not_allowed();
        }

        $this->enforce_auth();
        $profile_id = $this->require_profile_id();
        $data = $this->get_json_input();

        $course_name = trim((string) ($data['course_name'] ?? ''));
        $provider_name = trim((string) ($data['provider_name'] ?? ''));
        $course_url = trim((string) ($data['course_url'] ?? ''));
        $completion_date = trim((string) ($data['completion_date'] ?? ''));

        $errors = [];

        if ($course_name === '') {
            $errors['course_name'] = 'Course name is required.';
        }

        if ($provider_name === '') {
            $errors['provider_name'] = 'Provider name is required.';
        }

        if ($course_url !== '' && !filter_var($course_url, FILTER_VALIDATE_URL)) {
            $errors['course_url'] = 'A valid course URL is required.';
        }

        if (!empty($errors)) {
            return $this->validation_error($errors);
        }

        $this->Profile_model->update_short_course((int) $id, $profile_id, [
            'course_name' => $course_name,
            'provider_name' => $provider_name,
            'course_url' => $course_url !== '' ? $course_url : null,
            'completion_date' => $completion_date !== '' ? $completion_date : null
        ]);

        $this->update_profile_completion_by_profile_id($profile_id);

        return $this->json_response([
            'status' => true,
            'message' => 'Short course updated successfully.'
        ]);
    }

    public function delete_short_course($id)
    {
        if ($this->input->method(TRUE) !== 'DELETE') {
            return $this->method_not_allowed();
        }

        $this->enforce_auth();
        $profile_id = $this->require_profile_id();

        $this->Profile_model->delete_short_course((int) $id, $profile_id);
        $this->update_profile_completion_by_profile_id($profile_id);

        return $this->json_response([
            'status' => true,
            'message' => 'Short course deleted successfully.'
        ]);
    }

    public function add_employment()
    {
        if ($this->input->method(TRUE) !== 'POST') {
            return $this->method_not_allowed();
        }

        $this->enforce_auth();
        $profile_id = $this->require_profile_id();
        $data = $this->get_json_input();

        $job_title = trim((string) ($data['job_title'] ?? ''));
        $company_name = trim((string) ($data['company_name'] ?? ''));
        $start_date = trim((string) ($data['start_date'] ?? ''));
        $end_date = trim((string) ($data['end_date'] ?? ''));
        $description = trim((string) ($data['description'] ?? ''));
        $is_current = !empty($data['is_current']) ? 1 : 0;

        $errors = [];

        if ($job_title === '') {
            $errors['job_title'] = 'Job title is required.';
        }

        if ($company_name === '') {
            $errors['company_name'] = 'Company name is required.';
        }

        if ($start_date === '') {
            $errors['start_date'] = 'Start date is required.';
        }

        if ($is_current === 1 && $end_date !== '') {
            $errors['end_date'] = 'End date should be empty for a current job.';
        }

        if (!empty($errors)) {
            return $this->validation_error($errors);
        }

        if ($is_current === 1) {
            $this->Profile_model->clear_current_employment($profile_id);
        }

        $employment_id = $this->Profile_model->add_employment([
            'profile_id' => $profile_id,
            'job_title' => $job_title,
            'company_name' => $company_name,
            'start_date' => $start_date,
            'end_date' => $end_date !== '' ? $end_date : null,
            'is_current' => $is_current,
            'description' => $description !== '' ? $description : null
        ]);

        $this->update_profile_completion_by_profile_id($profile_id);

        return $this->json_response([
            'status' => true,
            'message' => 'Employment history added successfully.',
            'data' => [
                'employment_id' => $employment_id
            ]
        ], 201);
    }

    public function update_employment($id)
    {
        if ($this->input->method(TRUE) !== 'PUT') {
            return $this->method_not_allowed();
        }

        $this->enforce_auth();
        $profile_id = $this->require_profile_id();
        $data = $this->get_json_input();

        $job_title = trim((string) ($data['job_title'] ?? ''));
        $company_name = trim((string) ($data['company_name'] ?? ''));
        $start_date = trim((string) ($data['start_date'] ?? ''));
        $end_date = trim((string) ($data['end_date'] ?? ''));
        $description = trim((string) ($data['description'] ?? ''));
        $is_current = !empty($data['is_current']) ? 1 : 0;

        $errors = [];

        if ($job_title === '') {
            $errors['job_title'] = 'Job title is required.';
        }

        if ($company_name === '') {
            $errors['company_name'] = 'Company name is required.';
        }

        if ($start_date === '') {
            $errors['start_date'] = 'Start date is required.';
        }

        if ($is_current === 1 && $end_date !== '') {
            $errors['end_date'] = 'End date should be empty for a current job.';
        }

        if (!empty($errors)) {
            return $this->validation_error($errors);
        }

        if ($is_current === 1) {
            $this->Profile_model->clear_current_employment($profile_id, (int) $id);
        }

        $this->Profile_model->update_employment((int) $id, $profile_id, [
            'job_title' => $job_title,
            'company_name' => $company_name,
            'start_date' => $start_date,
            'end_date' => $end_date !== '' ? $end_date : null,
            'is_current' => $is_current,
            'description' => $description !== '' ? $description : null
        ]);

        $this->update_profile_completion_by_profile_id($profile_id);

        return $this->json_response([
            'status' => true,
            'message' => 'Employment history updated successfully.'
        ]);
    }

    public function delete_employment($id)
    {
        if ($this->input->method(TRUE) !== 'DELETE') {
            return $this->method_not_allowed();
        }

        $this->enforce_auth();
        $profile_id = $this->require_profile_id();

        $this->Profile_model->delete_employment((int) $id, $profile_id);
        $this->update_profile_completion_by_profile_id($profile_id);

        return $this->json_response([
            'status' => true,
            'message' => 'Employment history deleted successfully.'
        ]);
    }

    public function upload_image()
    {
        if ($this->input->method(TRUE) !== 'POST') {
            return $this->method_not_allowed();
        }

        $this->enforce_auth();
        $user_id = $this->require_login();
        $this->require_profile_id();

        if (empty($_FILES['profile_image']['name'])) {
            return $this->validation_error([
                'profile_image' => 'Profile image is required.'
            ]);
        }

        $upload_path = FCPATH . 'uploads/profile_images/';

        if (!is_dir($upload_path)) {
            mkdir($upload_path, 0755, true);
        }

        $config = [
            'upload_path' => $upload_path,
            'allowed_types' => 'jpg|jpeg|png|webp',
            'max_size' => 2048,
            'encrypt_name' => true
        ];

        $this->upload->initialize($config);

        if (!$this->upload->do_upload('profile_image')) {
            return $this->validation_error([
                'profile_image' => strip_tags($this->upload->display_errors('', ''))
            ]);
        }

        $uploaded = $this->upload->data();
        $relative_path = 'uploads/profile_images/' . $uploaded['file_name'];

        $this->Profile_model->update_profile_by_user_id($user_id, [
            'profile_image' => $relative_path
        ]);

        $this->update_profile_completion($user_id);

        return $this->json_response([
            'status' => true,
            'message' => 'Profile image uploaded successfully.',
            'data' => [
                'profile_image' => base_url($relative_path)
            ]
        ]);
    }

    private function enforce_auth()
    {
        $last_activity = (int) $this->session->userdata('last_activity');

        if ($last_activity > 0 && (time() - $last_activity) > 1800) {
            $login_log_id = (int) $this->session->userdata('login_log_id');

            if ($login_log_id > 0) {
                $this->load->model('User_model');
                $this->User_model->mark_logout_log($login_log_id);
            }

            $this->session->sess_destroy();
            $this->unauthorized('Session expired.');
            exit;
        }

        $this->session->set_userdata('last_activity', time());
    }

    private function require_profile_id()
    {
        $user_id = $this->require_login();
        $profile_id = $this->Profile_model->get_profile_id_by_user_id($user_id);

        if (!$profile_id) {
            $this->json_response([
                'status' => false,
                'message' => 'Please create your main profile first.'
            ], 400);
            exit;
        }

        return $profile_id;
    }

    private function update_profile_completion($user_id)
    {
        $profile = $this->Profile_model->get_profile_by_user_id($user_id);

        if (!$profile) {
            return;
        }

        $is_complete = (
            !empty($profile['biography']) &&
            !empty($profile['linkedin_url']) &&
            !empty($profile['degrees']) &&
            !empty($profile['employment_history'])
        ) ? 1 : 0;

        $this->Profile_model->update_profile_by_user_id($user_id, [
            'is_profile_complete' => $is_complete
        ]);
    }

    private function update_profile_completion_by_profile_id($profile_id)
    {
        $profile = $this->db->get_where('profiles', ['id' => $profile_id])->row_array();

        if ($profile) {
            $this->update_profile_completion((int) $profile['user_id']);
        }
    }
}