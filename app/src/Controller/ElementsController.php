<?php
/**
 * Created by PhpStorm.
 * User: agalempaszek
 * Date: 20.05.2018
 * Time: 18:12
 */

namespace Controller;

use Form\ElementType;
use Form\ListType;
use Repository\ElementsRepository;
use Repository\ListsRepository;
use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

class ElementsController implements ControllerProviderInterface {

	public function connect( Application $app ) {
		$controller = $app['controllers_factory'];

		$controller->match('/{id}/edit', [$this, 'editAction'])
		           ->method('GET|POST')
		           ->assert('id', '[1-9]\d*' )
		           ->bind('element_edit');

		$controller->match('/{id}/buy', [$this, 'buyAction'])
		           ->method('GET|POST')
		           ->assert('id', '[1-9]\d*' )
		           ->bind('element_buy');

		$controller->match('/{id}/delete', [$this, 'deleteAction'])
		           ->method('POST|GET')
		           ->assert('id', '[1-9]\d*' )
		           ->bind('element_delete');

		return $controller;
	}

	public function editAction(Application $app, $id, Request $request) {

		$elementsRepository = new ElementsRepository($app['db']);
		$listsRepository = new ListsRepository($app['db']);
		$element = $elementsRepository->findOneById($id);
		$connectedList = $listsRepository->getConnectedList($id);
		$listId = $connectedList['list_id'];

		if(!$element) {
			$app['session']->getFlashBag()->add(
				'messages',
				[
					'type' => 'warning',
					'message' => 'message.record_not_found',
				]
			);

			return $app->redirect($app['url_generator']->generate('list_edit', array('id' => $listId)));
		}

		$form = $app['form.factory']->createBuilder(ElementType::class, $element)->getForm();
		$form->handleRequest($request);

		if($form->isSubmitted() && $form->isValid()){
			$elementsRepository->save($listId, $form->getData());

			$app['session']->getFlashBag()->add(
				'messages',
				[
					'type' => 'success',
					'message' => 'message.element_successfully_edited',
				]
			);

			return $app->redirect($app['url_generator']->generate('list_edit', array('id' => $listId)), 301);
		}

		return $app['twig']->render(
			'elements/edit.html.twig',
			[
				'editedElement' => $element,
				'form' => $form->createView(),
				'lists' => $listsRepository->findAll(),
			]
		);
	}

	public function buyAction(Application $app, $id, Request $request) {

		$elementsRepository = new ElementsRepository($app['db']);
		$listsRepository = new ListsRepository($app['db']);
		$element = $elementsRepository->findOneById($id);
		$connectedList = $listsRepository->getConnectedList($id);
		$listId = $connectedList['list_id'];

		if(!$element) {
			$app['session']->getFlashBag()->add(
				'messages',
				[
					'type' => 'warning',
					'message' => 'message.record_not_found',
				]
			);

			return $app->redirect($app['url_generator']->generate('lists_view', array('id' => $listId)));
		}

		$form = $app['form.factory']->createBuilder(ElementType::class, $element)->getForm();
		$form->handleRequest($request);

		if($form->isSubmitted() && $form->isValid()){
			$elementsRepository->buy($form->getData());

			$app['session']->getFlashBag()->add(
				'messages',
				[
					'type' => 'success',
					'message' => 'message.element_successfully_edited',
				]
			);

			return $app->redirect($app['url_generator']->generate('lists_view', array('id' => $listId)), 301);
		}

		return $app['twig']->render(
			'elements/buy.html.twig',
			[
				'editedElement' => $element,
				'form' => $form->createView(),
				'lists' => $listsRepository->findAll(),
				'previousList' => $listId,
			]
		);
	}


	public function deleteAction(Application $app, $id, Request $request) {
		$elementsRepository = new ElementsRepository($app['db']);
		$element = $elementsRepository->findOneById($id);
		$listsRepository = new ListsRepository($app['db']);
		$connectedList = $listsRepository->getConnectedList($id);
		$listId = $connectedList['list_id'];

		if(!$element) {
			$app['session']->getFlashBag()->add(
				'messages',
				[
					'type' => 'warning',
					'message' => 'message.record_not_found',
				]
			);

			return $app->redirect($app['url_generator']->generate('list_edit', array('id' => $listId)));
		}

		$form = $app['form.factory']->createBuilder(FormType::class, $element)->add('id', HiddenType::class)->getForm();
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			$elementsRepository->delete($form->getData());

			$app['session']->getFlashBag()->add(
				'messages',
				[
					'type' => 'success',
					'message' => 'message.element_successfully_deleted',
				]
			);

			return $app->redirect($app['url_generator']->generate('list_edit', array('id' => $listId)), 301);
		}

		return $app['twig']->render(
			'elements/delete.html.twig',
			[
				'deletedElement' => $element,
				'form' => $form->createView(),
				'lists' => $listsRepository->findAll(),
			]
		);
	}

}