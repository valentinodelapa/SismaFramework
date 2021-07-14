<?php

namespace Sisma\Core\BaseClasses;

use Sisma\Core\ObjectRelationalMapper\Adapter;
use Sisma\Core\BaseClasses\BaseEntity;
use Sisma\Core\Exceptions\ModelException;
use Sisma\Core\ProprietaryTypes\SismaCollection;
use Sisma\Core\ObjectRelationalMapper\Enumerators\OrmKeyword;
use Sisma\Core\ObjectRelationalMapper\Enumerators\OrmOperator;
use Sisma\Core\ObjectRelationalMapper\Enumerators\OrmType;

abstract class BaseModel
{

    protected ?Adapter $adapter = null;
    protected BaseEntity $entity;

    public function __construct(?Adapter $adapter = null)
    {
        if ($adapter instanceof Adapter) {
            $this->adapter = $adapter;
        } else {
            $this->adapter = Adapter::create(\Config\DATABASE_ADAPTER_TYPE, [
                        'database' => \Config\DATABASE_NAME,
                        'hostname' => \Config\DATABASE_HOST,
                        'password' => \Config\DATABASE_PASSWORD,
                        'port' => \Config\DATABASE_PORT,
                        'username' => \Config\DATABASE_USERNAME,
            ]);
        }
        $this->implementEmbeddedEntity();
    }

    abstract public function implementEmbeddedEntity(): void;

    public function getEmbeddedEntity(): BaseEntity
    {
        return $this->entity;
    }

    public function setEntityByData(array $data): void
    {
        $classProperty = get_class_vars(get_class($this->entity));
        $orderedData = array_intersect_key($data, $classProperty);
        foreach ($orderedData as $key => $value) {
            $this->entity->$key = $value;
        }
    }

    public function getEntityCollection(?array $order = null): SismaCollection
    {
        $class = get_class($this->entity);
        $query = $class::initQuery();
        $query->close();
        $result = $class::find($query);
        $collection = new SismaCollection;
        foreach ($result as $entity) {
            $collection->append($entity);
        }
        return $collection;
    }

    public function getEntityById(int $id): ?BaseEntity
    {
        $class = get_class($this->entity);
        $query = $class::initQuery();
        $query->setWhere();
        $query->appendCondition('id', OrmOperator::EQUAL(), OrmKeyword::PLACEHOLDER());
        $query->close();
        return $class::findFirst($query, [
                    $id,
                        ], [
                    OrmType::INTEGER(),
        ]);
    }

    public function saveEntityByData(array $data): void
    {
        $this->setEntityByData($data);
        if (!$this->entity->save()) {
            Throw new ModelException();
        }
    }

    public function deleteEntityById(int $id): bool
    {
        $class = get_class($this->entity);
        $query = $class::initQuery();
        $query->setWhere();
        $query->appendCondition('id', OrmOperator::EQUAL(), OrmKeyword::PLACEHOLDER());
        $query->close();
        return $class::deleteBatch($query, [
                    $id,
                        ], [
                    OrmType::INTEGER(),
        ]);
    }

    public function __destruct()
    {
        $this->adapter = null;
    }

}
