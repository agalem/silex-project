<?php
/**
 * Created by PhpStorm.
 * User: agalempaszek
 * Date: 15.04.2018
 * Time: 20:35
 */
namespace Controller;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;

class HelloController implements ControllerProviderInterface {
	public function connect( Application $app ) {
		$controller = $app['controllers_factory'];
		$controller->get('/{name}', [$this, 'indexAction']);

		return $controller;
	}

	public function indexAction(Application $app, Request $request) {
		$name = $request->get('name', '');

		return $app['twig']->render('hello/index.html.twig', ['name' => $name]);
	}
}