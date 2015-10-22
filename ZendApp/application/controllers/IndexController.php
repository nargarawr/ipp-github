<?php

class IndexController extends BaseController {

    public function init() {
        parent::init();
        $this->view->hasTopBar = false;
        $this->view->hasNavBar = false;
        $this->view->isExternal = true;
    }

    public function indexAction() {
        $this->view->icons = array(
            (object) array (
                'name' => 'Twitter',
                'link' => 'https://twitter.com/nargarawr',
                'icon' => 'fa-twitter-square'
            ),
            (object) array (
                'name' => 'Google+',
                'link' => 'https://plus.google.com/u/0/109494126284664485608/posts',
                'icon' => 'fa-google-plus-square'
            ),
            (object) array (
                'name' => 'Github',
                'link' => 'https://github.com/nargarawr',
                'icon' => 'fa-github-square'
            ),
            (object) array (
                'name' => 'Facebook',
                'link' => 'https://www.facebook.com/pestilencexp',
                'icon' => 'fa-facebook-square'
            ),
            (object) array (
                'name' => 'Youtube',
                'link' => 'https://www.youtube.com/channel/UCYabux6zkylFKDUDwqj8LAQ',
                'icon' => 'fa-youtube-square'
            ),
            (object) array (
                'name' => 'LinkedIn',
                'link' => 'https://www.linkedin.com/profile/view?id=426941604',
                'icon' => 'fa-linkedin-square'
            ),
            (object) array (
                'name' => 'Email',
                'link' => 'mailto:cxk01u@googlemail.com',
                'icon' => 'fa-envelope'
            )
        );

    }
}
