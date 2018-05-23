<?php

namespace Repository;


use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;

class ElementsRepository {

	protected $db;

	public function __construct( Connection $db ) {
		$this->db = $db;
	}

	public function findAll() {
		$queryBuilder = $this->queryAll();

		return $queryBuilder->execute()->fetchAll();
	}


	public function findOneById( $id ) {
		$queryBuilder = $this->queryAll();
		$queryBuilder->where( 'e.id = :id' )
		             ->setParameter( ':id', $id, \PDO::PARAM_INT );
		$result = $queryBuilder->execute()->fetch();

		return !$result ? [] : $result;
	}

	public function findOneByName($name) {
		$queryBuilder = $this->queryAll();

		$queryBuilder->where('e.name = :name')
		             ->setParameter(':name', $name, \PDO::PARAM_STR);
		$result = $queryBuilder->execute()->fetch();

		return !$result ? [] : $result;
	}

	public function findById($ids) {
		$queryBuilder = $this->queryAll();
		$queryBuilder->where('e.id IN (:ids)')
		             ->setParameter(':ids', $ids, \Doctrine\DBAL\Connection::PARAM_INT_ARRAY);

		return $queryBuilder->execute()->fetchAll();
	}

	public function save($listId, $element)
	{
		$this->db->beginTransaction();

		try {
			$currentDateTime = new \DateTime();
			$element['modifiedAt'] = $currentDateTime->format('Y-m-d H:i:s');
			$element['finalValue'] = $element['value']*$element['quantity'];
			$element['isBought'] = 0;
			if(isset($element['id']) && ctype_digit((string) $element['id'])) {
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

	public function delete($element) {
		$this->removeLinkedElements($element['id']);
		$this->db->delete('elements', ['id' => $element['id']]);
	}

	public function buy($element) {
		$this->db->beginTransaction();

		try {
			$currentDateTime = new \DateTime();
			$element['modifiedAt'] = $currentDateTime->format('Y-m-d H:i:s');
			$element['isBought'] = 1;
			$element['finalValue'] = $element['value']*$element['quantity'];
			if(isset($element['id']) && ctype_digit((string) $element['id'])) {
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

	protected function removeLinkedElements($elementId) {
		return $this->db->delete('elements_lists', ['element_id' => $elementId]);
	}

	protected function addLinkedElements($listId, $elementsIds) {
		if(!is_array($elementsIds)) {
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

	protected function queryAll() {
		$queryBuilder = $this->db->createQueryBuilder();

		return $queryBuilder->select('e.id', 'e.name', 'e.value', 'e.quantity', 'e.isBought')
		                    ->from('elements', 'e');
	}
}