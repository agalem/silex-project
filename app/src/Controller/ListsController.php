<?php

namespace Controller;

use Form\ElementType;
use Form\ListType;
use Repository\ElementsRepository;
use Repository\ListsRepository;
use Repository\UserRepository;
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

		$userId = $this->getUserId($app);
		$userRole = $this->getUserRole($app, $userId);

		if($userRole[0] == 'ROLE_ADMIN') {
			return $app->redirect($app['url_generator']->generate('admin_manager'));
		}

		return $app['twig']->render(
			'lists/index.html.twig',
			['lists' => $listsRepository->findAll($userId)]
		);
	}

	public function viewAction(Application $app, $id) {

		$userId = $this->getUserId($app);
		$userRole = $this->getUserRole($app, $userId);

		if($userRole[0] == 'ROLE_ADMIN') {
			return $app->redirect($app['url_generator']->generate('admin_manager'));
		}

		$listsRepository = new ListsRepository($app['db']);
		$list = $listsRepository->findOneById($id, $userId);

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

		$currentSpendigs = $listsRepository->getCurrentSpendings($id);
		$activeList = $listsRepository->findOneById($id, $userId);
		$plannedSpendings = $activeList['maxCost'];

		if($plannedSpendings == null) {
			$spendPercent = 0;
		} else {
			$spendPercent = round($currentSpendigs / $plannedSpendings * 100);
		}

		if($spendPercent < 50) {
			$progressBarClass = 'bg-success text-light';
		} else if ($spendPercent >= 50 && $spendPercent < 80) {
			$progressBarClass = 'bg-warning text-dark';
		} else {
			$progressBarClass = 'bg-danger text-light';
		}

		return $app['twig']->render(
			'lists/view.html.twig',
			[
				'currentSpendings' => $listsRepository->getCurrentSpendings($id),
				'lists' => $listsRepository->findAll($userId),
				'activeList' => $listsRepository->findOneById($id, $userId),
				'products' => $listsRepository->findLinkedElements($id),
				'plannedSpendings' => $plannedSpendings,
				'spendPercent' => $spendPercent,
				'progressBarClass' => $progressBarClass,
			]
		);
	}

	public function managerAction(Application $app) {

		$userId = $this->getUserId($app);
		$userRole = $this->getUserRole($app, $userId);

		if($userRole[0] == 'ROLE_ADMIN') {
			return $app->redirect($app['url_generator']->generate('admin_manager'));
		}

		$listRepository = new ListsRepository($app['db']);

		return $app['twig']->render(
			'lists/manager.html.twig',
			[
				'lists' => $listRepository->findAll($userId),
			]
		);
	}

	public function addAction(Application $app, Request $request) {
		$userId = $this->getUserId($app);
		$userRole = $this->getUserRole($app, $userId);

		if($userRole[0] == 'ROLE_ADMIN') {
			return $app->redirect($app['url_generator']->generate('admin_manager'));
		}

		$listsRepository = new ListsRepository($app['db']);

		$list = [];

		$form = $app['form.factory']->createBuilder(ListType::class, $list)->getForm();
		$form->handleRequest($request);

		if($form->isSubmitted() && $form->isValid()) {
			$listsRepository->save($form->getData(), $userId);

			$app['session']->getFlashBag()->add(
				'messages',
				[
					'type' => 'success',
					'message' => 'message.element_successfully_added',
				]
			);

			return $app->redirect($app['url_generator']->generate('lists_manager'), 301);
		}

		return $app['twig']->render(
			'lists/add.html.twig',
			[
				'newList' => $list,
				'form' => $form->createView(),
				'lists' => $listsRepository->findAll($userId),
			]
		);
	}

	public function editAction(Application $app, $id, Request $request) {
		$userId = $this->getUserId($app);
		$userRole = $this->getUserRole($app, $userId);

		if($userRole[0] == 'ROLE_ADMIN') {
			return $app->redirect($app['url_generator']->generate('admin_manager'));
		}

		$listsRepository = new ListsRepository($app['db']);
		$list = $listsRepository->findOneById($id, $userId);
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
		$form = $app['form.factory']->createBuilder(ListType::class, $list)->getForm();
		$form->handleRequest($request);
		if($form->isSubmitted() && $form->isValid()){
			$listsRepository->save($form->getData(), $userId);
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
				'lists' => $listsRepository->findAll($userId),
				'products' => $listsRepository->findLinkedElements($id),
			]
		);
	}


	public function deleteAction(Application $app, $id, Request $request) {
		$userId = $this->getUserId($app);
		$userRole = $this->getUserRole($app, $userId);

		if($userRole[0] == 'ROLE_ADMIN') {
			return $app->redirect($app['url_generator']->generate('admin_manager'));
		}

		$listsRepository = new ListsRepository($app['db']);
		$list = $listsRepository->findOneById($id, $userId);

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
				'lists' => $listsRepository->findAll($userId),
			]
		);
	}

	public function addElementAction(Application $app, $id, Request $request) {
		$userId = $this->getUserId($app);
		$userRole = $this->getUserRole($app, $userId);

		if($userRole[0] == 'ROLE_ADMIN') {
			return $app->redirect($app['url_generator']->generate('admin_manager'));
		}

		$elementsRepository = new ElementsRepository($app['db']);
		$listsRepository = new ListsRepository($app['db']);

		$list = $listsRepository->findOneById($id, $userId);
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
			$elementsRepository->save($id, $form->getData(), $userId);

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
				'lists' => $listsRepository->findAll($userId),
				'editedList' => $listsRepository->findOneById($id, $userId),
			]
		);
	}

	protected function getUserId(Application $app) {

		$token = $app['security.token_storage']->getToken();

		if(null !== $token) {
			$username = $token->getUsername();

			$userRepository = new UserRepository($app['db']);
			$user = $userRepository->getUserId($username);
			return $user['id'];
		}

	}

	protected function getUserRole(Application $app, $userId) {

		$userRepository = new UserRepository($app['db']);
		$userRole = $userRepository->getUserRoles($userId);

		return $userRole;

	}
}