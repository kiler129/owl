<?php
declare(strict_types=1);

namespace noFlash\Owl\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends Controller
{
    public function test()
    {
        return new Response('<body><h1>Hello World!</h1></body>');
    }
}
