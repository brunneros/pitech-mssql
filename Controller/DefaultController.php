<?php

namespace Pitech\MssqlBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('PitechMssqlBundle:Default:index.html.twig', array('name' => $name));
    }
}
