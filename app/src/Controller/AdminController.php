<?php
/**
 * Created by PhpStorm.
 * User: agalempaszek
 * Date: 13.06.2018
 * Time: 22:52
 */

namespace Controller;

use Form\ChangePasswordType;
use Form\AccountType;
use Repository\ElementsRepository;
use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Repository\UserRepository;

/**
 * Class AdminController
 * @package Controller
 */
class AdminController implements ControllerProviderInterface
{

    /**
     * @param Application $app
     *
     * @return mixed
     */
    public function connect(Application $app)
    {
        $controller = $app['controllers_factory'];

        $controller->get('/', [$this, 'managerAction'])
                   ->bind('admin_manager');

        $controller->match('/{id}/edit', [$this, 'editAction'])
                   ->method('GET|POST')
                   ->assert('id', '[1-9]\d*')
                   ->bind('user_edit');

        $controller->match('/{id}/delete', [$this, 'deleteAction'])
                   ->method('GET|POST')
                   ->assert('id', '[1-9]\d*')
                   ->bind('user_delete');

        $controller->match('/add', [$this, 'addAction'])
                   ->method('POST|GET')
                   ->bind('admin_add');

        return $controller;
    }

    /**
     * @param Application $app
     *
     * @return mixed
     */
    public function managerAction(Application $app)
    {

        $userRepository =  new UserRepository($app['db']);


        return $app['twig']->render(
            'admin/admin.html.twig',
            ['users' => $userRepository->findAllUsers()]
        );
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

        $userRepository = new UserRepository($app['db']);
        $user = $userRepository->findUserById($id);

        if (!$user) {
            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'warning',
                    'message' => 'message.record_not_found',
                ]
            );

            return $app->redirect($app['url_generator']->generate('admin_manager'));
        }


        $form = $app['form.factory']->createBuilder(ChangePasswordType::class)->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newPassword = $form->getData();
            $newPassword['password'] = $app['security.encoder.bcrypt']->encodePassword($newPassword['password'], '');
            $userRepository->changePassword($id, $newPassword);
            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'success',
                    'message' => 'message.password_changed',
                ]
            );
        }

        return $app['twig']->render(
            'admin/edit.html.twig',
            [
                'editedUser' => $user,
                'form' => $form->createView(),
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

        $userRepository = new UserRepository($app['db']);
        $elementsRepository = new ElementsRepository($app['db']);
        $user = $userRepository->findUserById($id);

        if (!$user) {
            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'warning',
                    'message' => 'message.record_not_found',
                ]
            );

            return $app->redirect($app['url_generator']->generate('admin_manager'));
        }

        $form = $app['form.factory']->createBuilder(FormType::class, $user)->add('id', HiddenType::class)->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $connectedProducts = $elementsRepository->findAllUsersProducts($id);

            foreach ($connectedProducts as $connectedProduct) {
                $elementsRepository->delete($connectedProduct);
            }
            $userRepository->deleteUser($id);


            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'success',
                    'message' => 'message.user_successfully_deleted',
                ]
            );

            return $app->redirect(
                $app['url_generator']->generate('admin_manager'),
                301
            );
        }

        return $app['twig']->render(
            'admin/delete.html.twig',
            [
                'form' => $form->createView(),
                'deletedUser' => $user,
            ]
        );
    }

    /**
     * @param Application $app
     * @param Request     $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function addAction(Application $app, Request $request)
    {

        $userRepository = new UserRepository($app['db']);

        $newAdmin = [];

        $form = $app['form.factory']->createBuilder(AccountType::class, $newAdmin)->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newAdmin = $form->getData();

            $ifExists = $userRepository->getUserByLogin($newAdmin['login']);
            $ifExists = is_array($ifExists);

            if ($ifExists == true) {
                $app['session']->getFlashBag()->add(
                    'messages',
                    [
                        'type' => 'danger',
                        'message' => 'message.username_exists',
                    ]
                );

                return $app->redirect($app['url_generator']->generate('admin_add'), 301);
            }

            $newAdmin['password'] = $app['security.encoder.bcrypt']->encodePassword($newAdmin['password'], '');
            $newAdmin['role_id'] = '1';

            $userRepository->save($newAdmin);

            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'success',
                    'message' => 'message.admin_created',
                ]
            );

            return $app->redirect($app['url_generator']->generate('admin_manager'), 301);
        }

        return $app['twig']->render(
            'admin/create.html.twig',
            [
                'user' => $newAdmin,
                'form' => $form->createView(),
            ]
        );
    }
}
