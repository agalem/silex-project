<?php

namespace Repository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;

/**
 * Class ListsRepository
 * @package Repository
 */
class ListsRepository
{

    /**
     * @var Connection
     */
    protected $db;

    /**
     * ListsRepository constructor.
     *
     * @param Connection $db
     */
    public function __construct(Connection $db)
    {
        $this->db = $db;
        $this->elementsRepository = new ElementsRepository($db);
        $this->userRepository = new UserRepository($db);
    }

    /**
     * @param $userId
     *
     * @return mixed
     */
    public function findAll($userId)
    {
        $queryBuilder = $this->queryAll();
        $queryBuilder->where('l.createdBy = :userId')
                     ->setParameter(':userId', $userId, \PDO::PARAM_INT);

        return $queryBuilder->execute()->fetchAll();
    }

    /**
     * @param $id
     * @param $userId
     *
     * @return mixed
     */
    public function findOneById($id, $userId)
    {
        $queryBuilder = $this->queryAll();
        $queryBuilder->where('l.id = :id AND l.createdBy = :userId')
                     ->setParameter(':id', $id, \PDO::PARAM_INT)
                     ->setParameter(':userId', $userId, \PDO::PARAM_INT);
        $result = $queryBuilder->execute()->fetch();


        return $result;
    }

    /**
     * @param $name
     * @param $userId
     *
     * @return mixed
     */
    public function findOneByName($name, $userId)
    {
        $queryBuilder = $this->queryAll();
        $queryBuilder->where('l.name = :name AND l.createdBy = :userId')
                     ->setParameter(':name', $name, \PDO::PARAM_STR)
                     ->setParameter(':userId', $userId, \PDO::PARAM_INT);
        $result = $queryBuilder->execute()->fetch();


        return $result;
    }

    /**
     * @param $list
     * @param $userId
     *
     * @throws DBALException
     */
    public function save($list, $userId)
    {
        $this->db->beginTransaction();

        try {
            $currentDateTime = new \DateTime();
            $list['modifiedAt'] = $currentDateTime->format('Y-m-d H:i:s');
            $list['createdBy'] = $userId;
            if (isset($list['id']) && ctype_digit((string) $list['id'])) {
                $listId = $list['id'];
                unset($list['id']);

                $this->db->update('lists', $list, ['id' => $listId]);
            } else {
                $list['createdAt'] = $currentDateTime->format('Y-m-d H:i:s');
                $this->db->insert('lists', $list);
            }
            $this->db->commit();
        } catch (DBALException $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * @param $listId
     */
    public function updateModiefiedDate($listId)
    {
        $currentDateTime = new \DateTime();
        $list['modifiedAt'] = $currentDateTime->format('Y-m-d H:i:s');
        $this->db->update('lists', $list, ['id' => $listId]);
    }

    /**
     * @param $list
     */
    public function delete($list)
    {
        $linkedElementsIds = $this->findLinkedElementsIds($list['id']);

        $this->db->delete('lists', ['id' => $list['id']]);
        $this->removeLinkedElements($list['id']);
    }

    /**
     * @param $listId
     *
     * @return mixed
     */
    public function getCurrentSpendings($listId)
    {

        $elementsIds = $this->findLinkedElementsIds($listId);

        $queryBuilder = $this->db->createQueryBuilder();
        $queryBuilder->select('SUM(e.finalValue) AS finalValue')
                     ->from('elements', 'e')
                     ->where('e.id IN (:ids) AND e.isBought = 1')
                     ->setParameter(':ids', $elementsIds, \Doctrine\DBAL\Connection::PARAM_INT_ARRAY);

        $result =  $queryBuilder->execute()->fetch();

        return $result['finalValue'];
    }

    /**
     * @param $listId
     *
     * @return array
     */
    public function findLinkedElements($listId)
    {
        $elementsIds = $this->findLinkedElementsIds($listId);

        return is_array($elementsIds)
            ? $this->elementsRepository->findById($elementsIds)
            : [];
    }

    /**
     * @param $elementId
     *
     * @return mixed
     */
    public function getConnectedList($elementId)
    {
        $queryBuilder = $this->db->createQueryBuilder();

        $queryBuilder->select('el.list_id')
                     ->from('elements_lists', 'el')
                     ->where('el.element_id = :elementId')
                     ->setParameter(':elementId', $elementId);

        $result = $queryBuilder->execute()->fetch();

        return $result;
    }

    /**
     * @param $name
     * @param $userId
     *
     * @return mixed
     */
    public function findForUniqueness($name, $userId)
    {


        $queryBuilder = $this->queryAll();
        $queryBuilder->where('l.name = :name and l.createdBy = :userId')
                    ->setParameter(':name', $name, \PDO::PARAM_STR)
                    ->setParameter(':userId', $userId, \PDO::PARAM_INT);

        return $queryBuilder->execute()->fetchAll();
    }

    /**
     * @param $listId
     *
     * @return array
     */
    protected function findLinkedElementsIds($listId)
    {
        $queryBuilder = $this->db->createQueryBuilder()
            ->select('el.element_id')
            ->from('elements_lists', 'el')
            ->where('el.list_id = :listId')
            ->setParameter(':listId', $listId, \PDO::PARAM_INT);
        $result = $queryBuilder->execute()->fetchAll();

        return isset($result) ? array_column($result, 'element_id') : [];
    }

    /**
     * @param $listId
     *
     * @throws DBALException
     */
    protected function removeLinkedElements($listId)
    {

        $this->db->beginTransaction();

        try {
            $this->db->delete('elements_lists', ['list_id' => $listId]);

            $elementsIds = $this->findLinkedElementsIds($listId);

            foreach ($elementsIds as $elementId) {
                $this->db->delete('elements', ['id' => $elementId]);
            }

            $this->db->commit();
        } catch (DBALException $exception) {
            throw $exception;
        }
    }

    /**
     * @return $this
     */
    protected function queryAll()
    {
        $queryBuilder = $this->db->createQueryBuilder();

        return $queryBuilder->select('l.id', 'l.name', 'l.maxCost', 'l.createdBy')
            ->from('lists', 'l');
    }
}
