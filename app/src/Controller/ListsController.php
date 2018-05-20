<?php

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

class ListsController implements ControllerProviderInterface {

	public function connect( Application $app ) {
		$controller = $app['controllers_factory'];

		$controller->get('/', [$this, 'indexAction'])->bind('lists_index');

		$controller->get('/{id}', [$this, 'viewAction'])
		           ->assert('id', '[1-9]\d*' )
		           ->bind('lists_view');

		$controller->get('/manager', [$this, 'managerAction'])->bind('lists_manager');

		$controller->match('/add', [$this, 'addAction'])
		           ->method('POST|GET')
		           ->bind('list_add');

		$controller->match('{id}/add', [$this, 'addElementAction'])
		           ->method('POST|GET')
		           ->assert('id', '[1-9]\d*' )
		           ->bind('element_add');

		$controller->match('/{id}/edit', [$this, 'editAction'])
		           ->method('GET|POST')
		           ->assert('id', '[1-9]\d*' )
		           ->bind('list_edit');

		$controller->match('/{id}/delete', [$this, 'deleteAction'])
		           ->method('GET|POST')
		           ->assert('id', '[1-9]\d*' )
		           ->bind('list_delete');

		return $controller;
	}

	public function indexAction(Application $app) {
		$listsRepository = new ListsRepository($app['db']);

		return $app['twig']->render(
			'lists/index.html.twig',
			['lists' => $listsRepository->findAll()]
		);
	}

	public function viewAction(Application $app, $id) {
		$listsRepository = new ListsRepository($app['db']);
		$list = $listsRepository->findOneById($id);

		if(!$list) {
			$app['session']->getFlashBag()->add(
				'messages',
				[
					'type' => 'warning',
					'message' => 'message.record_not_found',
				]
			);

			return $app->redirect($app['url_generator']->generate('lists_index'));
		}

		return $app['twig']->render(
			'lists/view.html.twig',
			[
				'lists' => $listsRepository->findAll(),
				'activeList' => $listsRepository->findOneById($id),
				'products' => $listsRepository->findLinkedElements($id),
			]
		);
	}

	public function managerAction(Application $app) {
		$listRepository = new ListsRepository($app['db']);

		return $app['twig']->render(
			'lists/manager.html.twig',
			[
				'lists' => $listRepository->findAll(),
			]
		);
	}

	public function addAction(Application $app, Request $request) {
		$listsRepository = new ListsRepository($app['db']);

		$list = [];

		$form = $app['form.factory']->createBuilder(ListType::class, $list, ['elements_repository' => new ElementsRepository($app['db'])])->getForm();
		$form->handleRequest($request);

		if($form->isSubmitted() && $form->isValid()) {
			$listsRepository->save($form->getData());

			$app['session']->getFlashBag()->add(
				'messages',
				[
					'type' => 'success',
					'message' => 'message.element_successfully_added',
				]
			);

			return $app->redirect($app['url_generator']->generate('lists_index'), 301);
		}

		return $app['twig']->render(
			'lists/add.html.twig',
			[
				'newList' => $list,
				'form' => $form->createView(),
				'lists' => $listsRepository->findAll(),
			]
		);
	}

	public function editAction(Application $app, $id, Request $request) {
		$listsRepository = new ListsRepository($app['db']);
		$list = $listsRepository->findOneById($id);

		if(!$list) {
			$app['session']->getFlashBag()->add(
				'messages',
				[
					'type' => 'warning',
					'message' => 'message.record_not_found',
				]
			);

			return $app->redirect($app['url_generator']->generate('lists_index'));
		}

		$form = $app['form.factory']->createBuilder(ListType::class, $list, ['elements_repository' => new ElementsRepository($app['db'])])->getForm();
		$form->handleRequest($request);

		if($form->isSubmitted() && $form->isValid()){
			$listsRepository->save($form->getData());

			$app['session']->getFlashBag()->add(
				'messages',
				[
					'type' => 'success',
					'message' => 'message.element_successfully_edited',
				]
			);

			return $app->redirect($app['url_generator']->generate('lists_view', array('id' => $id)), 301);
		}

		return $app['twig']->render(
			'lists/edit.html.twig',
			[
				'editedList' => $list,
				'form' => $form->createView(),
				'lists' => $listsRepository->findAll(),
				'products' => $listsRepository->findLinkedElements($id),
			]
		);
	}

	public function deleteAction(Application $app, $id, Request $request) {
		$listsRepository = new ListsRepository($app['db']);
		$list = $listsRepository->findOneById($id);

		if(!$list) {
			$app['session']->getFlashBag()->add(
				'messages',
				[
					'type' => 'warning',
					'message' => 'message.record_not_found',
				]
			);

			return $app->redirect($app['url_generator']->generate('lists_index'));
		}

		$form = $app['form.factory']->createBuilder(FormType::class, $list)->add('id', HiddenType::class)->getForm();
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			$listsRepository->delete($form->getData());

			$app['session']->getFlashBag()->add(
				'messages',
				[
					'type' => 'success',
					'message' => 'message.element_successfully_deleted',
				]
			);

			return $app->redirect(
				$app['url_generator']->generate('lists_index'),
				301
			);
		}

		return $app['twig']->render(
			'lists/delete.html.twig',
			[
				'deletedList' => $list,
				'form' => $form->createView(),
				'lists' => $listsRepository->findAll(),
			]
		);
	}

	public function addElementAction(Application $app, $id, Request $request) {
		$elementsRepository = new ElementsRepository($app['db']);
		$listsRepository = new ListsRepository($app['db']);

		$list = $listsRepository->findOneById($id);
		$element = [];

		if(!$list) {
			$app['session']->getFlashBag()->add(
				'messages',
				[
					'type' => 'warning',
					'message' => 'message.record_not_found',
				]
			);

			return $app->redirect($app['url_generator']->generate('lists_manager'));
		}

		$form = $app['form.factory']->createBuilder(ElementType::class, $element)->getForm();
		$form->handleRequest($request);

		if($form->isSubmitted() && $form->isValid()) {

			$listsRepository->updateModiefiedDate($id);
			$elementsRepository->save($id, $form->getData());

			$app['session']->getFlashBag()->add(
				'messages',
				[
					'type' => 'success',
					'message' => 'message.element_successfully_added',
				]
			);

			return $app->redirect($app['url_generator']->generate('list_edit', array('id' => $id)), 301);
		}

		return $app['twig']->render(
			'elements/add.html.twig',
			[
				'newElement' => $element,
				'form' => $form->createView(),
				'lists' => $listsRepository->findAll(),
				'editedList' => $listsRepository->findOneById($id),
			]
		);
	}
}