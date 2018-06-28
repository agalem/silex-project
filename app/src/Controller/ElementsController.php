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
use Repository\UserRepository;
use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

/**
 * Class ElementsController
 * @package Controller
 */
class ElementsController implements ControllerProviderInterface
{

    public function connect(Application $app)
    {
        $controller = $app['controllers_factory'];

        $controller->match('/{id}/edit', [$this, 'editAction'])
                   ->method('GET|POST')
                   ->assert('id', '[1-9]\d*')
                   ->bind('element_edit');

        $controller->match('/{id}/buy', [$this, 'buyAction'])
                   ->method('GET|POST')
                   ->assert('id', '[1-9]\d*')
                   ->bind('element_buy');

        $controller->match('/{id}/delete', [$this, 'deleteAction'])
                   ->method('POST|GET')
                   ->assert('id', '[1-9]\d*')
                   ->bind('element_delete');

        return $controller;
    }

    /**
     * @param Application $app
     * @param $id
     * @param Request     $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function editAction(Application $app, $id, Request $request)
    {

        $userId = $this->getUserId($app);
        $userRole = $this->getUserRole($app, $userId);

        if ($userRole[0] == 'ROLE_ADMIN') {
            return $app->redirect($app['url_generator']->generate('admin_manager'));
        }

        $elementsRepository = new ElementsRepository($app['db']);
        $listsRepository = new ListsRepository($app['db']);
        $element = $elementsRepository->findOneById($id, $userId);
        $connectedList = $listsRepository->getConnectedList($id);
        $listId = $connectedList['list_id'];

        if (!$element) {
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

        if ($form->isSubmitted() && $form->isValid()) {

        	$data = $form->getData();

            $elementsRepository->save($listId, $data, $userId);

            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'success',
                    'message' => 'message.element_successfully_edited',
                ]
            );

            //return $app->redirect($app['url_generator']->generate('list_edit', array('id' => $listId)), 301);
        }

        return $app['twig']->render(
            'elements/edit.html.twig',
            [
                'editedElement' => $element,
                'form' => $form->createView(),
                'lists' => $listsRepository->findAll($userId),
            ]
        );
    }

    /**
     * @param Application $app
     * @param $id
     * @param Request     $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function buyAction(Application $app, $id, Request $request)
    {

        $userId = $this->getUserId($app);
        $userRole = $this->getUserRole($app, $userId);

        if ($userRole[0] == 'ROLE_ADMIN') {
            return $app->redirect($app['url_generator']->generate('admin_manager'));
        }

        $elementsRepository = new ElementsRepository($app['db']);
        $listsRepository = new ListsRepository($app['db']);
        $element = $elementsRepository->findOneById($id, $userId);
        $connectedList = $listsRepository->getConnectedList($id);
        $listId = $connectedList['list_id'];

        if (!$element) {
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

        if ($form->isSubmitted() && $form->isValid()) {
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
                'lists' => $listsRepository->findAll($userId),
                'previousList' => $listId,
            ]
        );
    }


    /**
     * @param Application $app
     * @param $id
     * @param Request     $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction(Application $app, $id, Request $request)
    {

        $userId = $this->getUserId($app);
        $userRole = $this->getUserRole($app, $userId);

        if ($userRole[0] == 'ROLE_ADMIN') {
            return $app->redirect($app['url_generator']->generate('admin_manager'));
        }

        $elementsRepository = new ElementsRepository($app['db']);
        $element = $elementsRepository->findOneById($id, $userId);
        $listsRepository = new ListsRepository($app['db']);
        $connectedList = $listsRepository->getConnectedList($id);
        $listId = $connectedList['list_id'];

        if (!$element) {
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
                'lists' => $listsRepository->findAll($userId),
            ]
        );
    }

    /**
     * @param Application $app
     *
     * @return mixed
     */
    protected function getUserId(Application $app)
    {

        $token = $app['security.token_storage']->getToken();

        if (null !== $token) {
            $username = $token->getUsername();

            $userRepository = new UserRepository($app['db']);
            $user = $userRepository->getUserId($username);

            return $user['id'];
        }
    }

    /**
     * @param Application $app
     * @param $userId
     *
     * @return array
     */
    protected function getUserRole(Application $app, $userId)
    {

        $userRepository = new UserRepository($app['db']);
        $userRole = $userRepository->getUserRoles($userId);

        return $userRole;
    }
}
