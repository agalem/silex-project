<?php
/**
 * User repository
 */

namespace Repository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;

/**
 * Class UserRepository.
 */
class UserRepository
{

	protected $db;

	public function __construct(Connection $db)
	{
		$this->db = $db;
	}


	public function loadUserByLogin($login)
	{
		try {
			$user = $this->getUserByLogin($login);

			if (!$user || !count($user)) {
				throw new UsernameNotFoundException(
					sprintf('Username "%s" does not exist.', $login)
				);
			}

			$roles = $this->getUserRoles($user['id']);

			if (!$roles || !count($roles)) {
				throw new UsernameNotFoundException(
					sprintf('Username "%s" does not exist.', $login)
				);
			}

			return [
				'login' => $user['login'],
				'password' => $user['password'],
				'roles' => $roles,
			];
		} catch (DBALException $exception) {
			throw new UsernameNotFoundException(
				sprintf('Username "%s" does not exist.', $login)
			);
		} catch (UsernameNotFoundException $exception) {
			throw $exception;
		}
	}


	public function getUserByLogin($login)
	{
		try {
			$queryBuilder = $this->queryAll();
			$queryBuilder->where('u.login = :login')
			             ->setParameter(':login', $login, \PDO::PARAM_STR);

			return $queryBuilder->execute()->fetch();
		} catch (DBALException $exception) {
			return [];
		}
	}


	public function getUserRoles($userId)
	{
		$roles = [];

		try {
			$queryBuilder = $this->db->createQueryBuilder();
			$queryBuilder->select('r.name')
			             ->from('users', 'u')
			             ->innerJoin('u', 'user_roles', 'r', 'u.role_id = r.id')
			             ->where('u.id = :id')
			             ->setParameter(':id', $userId, \PDO::PARAM_INT);
			$result = $queryBuilder->execute()->fetchAll();

			if ($result) {
				$roles = array_column($result, 'name');
			}

			return $roles;
		} catch (DBALException $exception) {
			return $roles;
		}
	}


	public function save($user) {

		try {
				$this->db->insert('users', $user);

				$this->loadUserByLogin($user['login']);

		} catch (DBALException $exception) {
			return [];
		}


	}


	public function getUserId($username) {

		try {
			$queryBuilder = $this->db->createQueryBuilder();
			$queryBuilder->select('u.id')
			             ->from('users', 'u')
			             ->where('u.login = :login')
			             ->setParameter(':login', $username, \PDO::PARAM_STR);
			return $queryBuilder->execute()->fetch();
		} catch (DBALException $exception) {
			return [];
		}

	}

	public function findAllUsers() {

		try {
			$users = $this->queryAll();

			return $users->execute()->fetchAll();

		} catch (DBALException $exception) {

			return [];

		}

	}

	public function findUserById($id) {

		try {
			$queryBuilder = $this->queryAll();
			$queryBuilder->where('u.id = :id')
			             ->setParameter(':id', $id, \PDO::PARAM_INT);
			return $queryBuilder->execute()->fetch();
		} catch (DBALException $exception) {
			return [];
		}

	}

	public function changePassword($id, $password) {

		try {
			$user = $this->findUserById($id);

			if(!$user) {
				return [];
			}

			$this->db->update('users', $password, ['id' => $id]);

		} catch (DBALException $exception) {

			return [];

		}

	}

	private function queryAll() {

		try {

			$queryBuilder = $this->db->createQueryBuilder();
			$queryBuilder->select('u.id', 'u.login', 'u.password', 'u.role_id')
			             ->from('users', 'u');

			return $queryBuilder;

		} catch (DBALException $exception) {

			return [];

		}

	}

}
