<?php

namespace Repository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;

class ListsRepository {

	protected $db;

	public function __construct(Connection $db) {
		$this->db = $db;
		$this->elementsRepository = new ElementsRepository($db);
	}

	public function findAll() {
		$queryBuilder = $this->queryAll();

		return $queryBuilder->execute()->fetchAll();
	}

	public function findOneById($id) {
		$queryBuilder = $this->queryAll();
		$queryBuilder->where('l.id = :id')
		             ->setParameter(':id', $id, \PDO::PARAM_INT);
		$result = $queryBuilder->execute()->fetch();


		return $result;
	}

	public function save($list) {
		$this->db->beginTransaction();

		try {
			$currentDateTime = new \DateTime();
			$list['modifiedAt'] = $currentDateTime->format('Y-m-d H:i:s');

			if(isset($list['id']) && ctype_digit((string) $list['id'])) {
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


	public function updateModiefiedDate($listId) {
		$currentDateTime = new \DateTime();
		$list['modifiedAt'] = $currentDateTime->format('Y-m-d H:i:s');
		$this->db->update('lists', $list, ['id' => $listId]);
	}

	public function delete($list) {
		$this->db->delete('lists', ['id' => $list['id']]);
		$this->removeLinkedElements($list['id']);
	}

	public function getCurrentSpendings($listId) {

		$elementsIds = $this->findLinkedElementsIds($listId);

		$queryBuilder = $this->db->createQueryBuilder();
		$queryBuilder->select('SUM(e.finalValue) AS finalValue')
		             ->from('elements', 'e')
		             ->where('e.id IN (:ids)')
		             ->setParameter(':ids', $elementsIds, \Doctrine\DBAL\Connection::PARAM_INT_ARRAY);

		$result =  $queryBuilder->execute()->fetch();
		return $result['finalValue'];
	}

	public function findLinkedElements($listId)
	{
		$elementsIds = $this->findLinkedElementsIds($listId);

		return is_array($elementsIds)
			? $this->elementsRepository->findById($elementsIds)
			: [];
	}

	public function getConnectedList($elementId) {
		$queryBuilder = $this->db->createQueryBuilder();

		$queryBuilder->select('el.list_id')
		             ->from('elements_lists', 'el')
		             ->where('el.element_id = :elementId')
		             ->setParameter(':elementId', $elementId);

		$result = $queryBuilder->execute()->fetch();
		return $result;
	}

	protected function findLinkedElementsIds($listId) {
		$queryBuilder = $this->db->createQueryBuilder()
			->select('el.element_id')
			->from('elements_lists', 'el')
			->where('el.list_id = :listId')
			->setParameter(':listId', $listId, \PDO::PARAM_INT);
		$result = $queryBuilder->execute()->fetchAll();

		return isset($result) ? array_column($result, 'element_id') : [];
	}

	protected function removeLinkedElements($listId) {
		return $this->db->delete('elements_lists', ['list_id' => $listId]);
	}

	protected function queryAll() {
		$queryBuilder = $this->db->createQueryBuilder();

		return $queryBuilder->select('l.id', 'l.name', 'l.maxCost')
			->from('lists', 'l');
	}

}