<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class AuthController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->call->model('UserModel');
    }

    public function login()
    {
        $data['error'] = '';
        $data['notification'] = $this->session->flashdata('notification');

        if ($this->form_validation->submitted()) {
            $username = trim($this->io->post('username'));
            $password = $this->io->post('password');
            $user = $this->UserModel->find_by_username($username);

            if (!empty($user) && password_verify($password, $user['password'])) {
                $this->session->regenerate_on_login();
                $this->session->set_userdata([
                    'id' => $user['id'],
                    'user_id' => $user['id'],
                    'username' => $user['username'],
                    'user_role' => $user['role']
                ]);
                header('Location: ' . site_url('/products'));
                exit;
            }

            $data['error'] = 'Invalid username or password.';
        }

        $this->call->view('auth/login', $data);
    }

    public function register()
    {
        $data['error'] = '';

        if ($this->form_validation->submitted()) {
            $username = trim($this->io->post('username'));
            $email = trim($this->io->post('email'));
            $password = $this->io->post('password');

            if ($username === '' || $email === '' || $password === '') {
                $data['error'] = 'Username, email, and password are required.';
            } elseif (strlen($username) > 100 || strlen($email) > 150) {
                $data['error'] = 'Username or email is too long.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $data['error'] = 'Enter a valid email address.';
            } else {
                $existing = $this->db->raw(
                    'SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1',
                    [$username, $email]
                )->fetch(PDO::FETCH_ASSOC);

                if ($existing) {
                    $data['error'] = 'Username or email already exists.';
                } else {
                    $this->db->raw(
                        'INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)',
                        [$username, $email, password_hash($password, PASSWORD_BCRYPT), 'user']
                    );
                    $this->session->set_flashdata('notification', 'Account created. You can now log in.');
                    header('Location: ' . site_url('/login'));
                    exit;
                }
            }
        }

        $this->call->view('auth/register', $data);
    }

    public function logout()
    {
        $this->session->sess_destroy();
        header('Location: ' . site_url('/login'));
        exit;
    }

}