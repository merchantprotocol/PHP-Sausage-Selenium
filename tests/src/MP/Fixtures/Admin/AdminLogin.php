<?php

namespace MP\Fixtures\Admin;

trait AdminLogin
{
    /**
     * Login into admin area
     *
     * 1. Move to admin area
     * 2. Set username and password
     * 3. Submit
     */
    public function adminLogin()
    {
        $this->url($this->adminUrl);
        $adminUser = $this->getTestConfig()->getValue('admin_user');

        $this->byId('username')->value($adminUser['login']);
        $this->byId('login')->value($adminUser['password']);

        $this->byId('loginForm')->submit();
    }

    /**
     * Logout from admin area
     */
    public function adminLogout()
    {
        $this->url($this->adminUrl . '/index/logout');
    }
}