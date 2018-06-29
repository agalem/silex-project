<?php

namespace Repository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;

/**
 * Class ElementsRepository
 * @package Repository
 */
class ElementsRepository
{

    /**
     * @var Connection
     */
    protected $db;

    /**
     * ElementsRepository constructor.
     *
     * @param Connection $db
     */
    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    /**
     * @param $userId
     *
     * @return array
     */
    public function findAll($userId)
    {
        $queryBuilder = $this->queryAll();
        $queryBuilder->where('u.createdBy = :userId')
                     ->setParameter(':userId', $userId, \PDO::PARAM_INT);

        return $queryBuilder->execute()->fetchAll();
    }

    /**
     * @param $id
     * @param $userId
     *
     * @return array|mixed
     */
    public function findOneById($id, $userId)
    {
        $queryBuilder = $this->queryAll();
        $queryBuilder->where('e.id = :id AND e.createdBy = :userId')
                     ->setParameter(':id', $id, \PDO::PARAM_INT)
                     ->setParameter(':userId', $userId, \PDO::PARAM_INT);
        $result = $queryBuilder->execute()->fetch();

        return !$result ? [] : $result;
    }

    /**
     * @param $name
     *
     * @return array|mixed
     */
    public function findOneByName($name)
    {
        $queryBuilder = $this->queryAll();

        $queryBuilder->where('e.name = :name')
                     ->setParameter(':name', $name, \PDO::PARAM_STR);
        $result = $queryBuilder->execute()->fetch();

        return !$result ? [] : $result;
    }

    /**
     * @param $ids
     *
     * @return array
     */
    public function findById($ids)
    {
        $queryBuilder = $this->queryAll();
        $queryBuilder->where('e.id IN (:ids)')
                     ->setParameter(':ids', $ids, \Doctrine\DBAL\Connection::PARAM_INT_ARRAY);

        return $queryBuilder->execute()->fetchAll();
    }

    /**
     * @param $userId
     *
     * @return mixed
     */
    public function findAllUsersProducts($userId)
    {
        $queryBuilder = $this->queryAll();
        $queryBuilder->where('e.createdBy = :userId')
                    ->setParameter(':userId', $userId, \PDO::PARAM_INT);

        return $queryBuilder->execute()->fetchAll();
    }

    /**
     * @param $listId
     * @param $element
     * @param $userId
     *
     * @throws DBALException
     */
    public function save($listId, $element, $userId)
    {
        $this->db->beginTransaction();

        try {
            $currentDateTime = new \DateTime();
            $element['modifiedAt'] = $currentDateTime->format('Y-m-d H:i:s');
            $element['finalValue'] = $element['value']*$element['quantity'];
            $element['isBought'] = 0;
            $element['createdBy'] = $userId;
            if (isset($element['id']) && ctype_digit((string) $element['id'])) {
                $elementId = $element['id'];
                unset($element['id']);
                $this->removeLinkedElements($elementId);
                $this->addLinkedElements($listId, $elementId);
                $this->db->update('elements', $element, ['id' => $elementId]);
            } else {
                $element['createdAt'] = $currentDateTime->format('Y-m-d H:i:s');
                $element['finalValue'] = 0;
                $this->db->insert('elements', $element);
                $elementId = $this->db->lastInsertId();
                $this->addLinkedElements($listId, $elementId);
            }
            $this->db->commit();
        } catch (DBALException $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * @param $element
     */
    public function delete($element)
    {
        $this->removeLinkedElements($element['id']);
        $this->db->delete('elements', ['id' => $element['id']]);
    }

    /**
     * @param $element
     *
     * @throws DBALException
     */
    public function buy($element)
    {
        $this->db->beginTransaction();

        try {
            $currentDateTime = new \DateTime();
            $element['modifiedAt'] = $currentDateTime->format('Y-m-d H:i:s');
            $element['isBought'] = 1;
            $element['finalValue'] = $element['value']*$element['quantity'];
            if (isset($element['id']) && ctype_digit((string) $element['id'])) {
                $elementId = $element['id'];
                unset($element['id']);
                $this->db->update('elements', $element, ['id' => $elementId]);
            } else {
                $element['createdAt'] = $currentDateTime->format('Y-m-d H:i:s');
                $this->db->insert('elements', $element);
            }
            $this->db->commit();
        } catch (DBALException $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * @param $elementId
     *
     * @return int
     */
    protected function removeLinkedElements($elementId)
    {
        return $this->db->delete('elements_lists', ['element_id' => $elementId]);
    }


    /**
     * @param $listId
     * @param $elementsIds
     */
    protected function addLinkedElements($listId, $elementsIds)
    {
        if (!is_array($elementsIds)) {
            $elementsIds = [$elementsIds];
        }
        foreach ($elementsIds as $elementId) {
            $this->db->insert(
                'elements_lists',
                [
                    'element_id' => $elementId,
                    'list_id' => $listId,
                ]
            );
        }
    }


    /**
     * @return $this
     */
    protected function queryAll()
    {
        $queryBuilder = $this->db->createQueryBuilder();

        return $queryBuilder->select('e.id', 'e.name', 'e.value', 'e.quantity', 'e.isBought', 'e.createdBy', 'e.isItem')
                            ->from('elements', 'e');
    }
}
