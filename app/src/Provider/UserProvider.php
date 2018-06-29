<?php

namespace Provider;

use Doctrine\DBAL\Connection;
use Repository\UserRepository;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\User;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;

/**
 * Class UserProvider
 */
class UserProvider implements UserProviderInterface
{

    /**
     * @var Connection
     */
    protected $db;

    /**
     * UserProvider constructor.
     *
     * @param Connection $db
     */
    public function __construct(Connection $db)
    {
        $this->db = $db;
    }


    /**
     * @param string $login
     *
     * @return User
     */
    public function loadUserByUsername($login)
    {
        $userRepository = new UserRepository($this->db);
        $user = $userRepository->loadUserByLogin($login);

        return new User(
            $user['login'],
            $user['password'],
            $user['roles'],
            true,
            true,
            true,
            true
        );
    }

    /**
     * @param UserInterface $user
     *
     * @return User
     */
    public function refreshUser(UserInterface $user)
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(
                sprintf(
                    'Instances of "%s" are not supported.',
                    get_class($user)
                )
            );
        }

        return $this->loadUserByUsername($user->getUsername());
    }

    /**
     * @param string $class
     *
     * @return bool
     */
    public function supportsClass($class)
    {
        return $class === 'Symfony\Component\Security\Core\User\User';
    }
}
