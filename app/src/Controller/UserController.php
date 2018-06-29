<?php


namespace Controller;

use Form\ChangePasswordType;
use Repository\ElementsRepository;
use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\HttpFoundation\Request;
use Repository\UserRepository;
use Symfony\Component\Security\Core\User\User;

/**
 * Class UserController
 */
class UserController implements ControllerProviderInterface
{

    /**
     * @param Application $app
     *
     * @return mixed
     */
    public function connect(Application $app)
    {
        $controller = $app['controllers_factory'];

        $controller->match('/edit', [$this, 'editAction'])
                   ->method('GET|POST')
                   ->bind('user_edit_self');

        $controller->match('/delete', [$this, 'deleteAction'])
                   ->method('GET|POST')
                   ->bind('user_delete_self');

        return $controller;
    }

    /**
     * @param Application $app
     * @param Request     $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function editAction(Application $app, Request $request)
    {

        $userRepository = new UserRepository($app['db']);
        $username = $this->getUsername($app);
        $userId = $this->getUserId($app, $username);

        $newUser = [];

        $form = $app['form.factory']->createBuilder(ChangePasswordType::class)->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            if ($data['password'] !== $data['checkPassword']) {
                $app['session']->getFlashBag()->add(
                    'messages',
                    [
                        'type' => 'danger',
                        'message' => 'message.passwords_not_match',
                    ]
                );

                return $app->redirect($app['url_generator']->generate('user_edit_self'), 301);
            }

            $newUser['password'] = $app['security.encoder.bcrypt']->encodePassword($data['password'], '');

            $userRepository->updateUserData($userId, $newUser);

            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'success',
                    'message' => 'message.user_updated',
                ]
            );
        }

        return $app['twig']->render(
            'user/manager.html.twig',
            [
                'editedUserName' => $username,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @param Application $app
     * @param Request     $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction(Application $app, Request $request)
    {

        $userRepository = new UserRepository($app['db']);
        $elementsRepository  = new ElementsRepository($app['db']);

        $username = $this->getUsername($app);
        $userId = $this->getUserId($app, $username);

        $user = $userRepository->findUserById($userId);

        $form = $app['form.factory']->createBuilder(FormType::class, $user)->add('id', HiddenType::class)->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $connectedProducts = $elementsRepository->findAllUsersProducts($userId);

            foreach ($connectedProducts as $connectedProduct) {
                $elementsRepository->delete($connectedProduct);
            }
            $userRepository->deleteUser($userId);



            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'success',
                    'message' => 'message.user_successfully_deleted',
                ]
            );

            return $app->redirect(
                $app['url_generator']->generate('auth_logout'),
                301
            );
        }

        return $app['twig']->render(
            'user/delete.html.twig',
            [
                'form' => $form->createView(),
            ]
        );
    }


    /**
     * @param Application $app
     *
     * @return mixed
     */
    private function getUsername(Application $app)
    {

        $token = $app['security.token_storage']->getToken();

        if (null !== $token) {
            $user = $token->getUsername();
        }

        return $user;
    }

    /**
     * @param Application $app
     * @param $username
     *
     * @return mixed
     */
    private function getUserId(Application $app, $username)
    {

        $userRepository = new UserRepository($app['db']);

        $userId = $userRepository->getUserByLogin($username);

        return $userId['id'];
    }
}
