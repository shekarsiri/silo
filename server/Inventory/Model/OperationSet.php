<?php

namespace Silo\Inventory\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="operation_set")
 */
class OperationSet
{
    /**
     * @var int
     *
     * @ORM\Column(name="operation_set_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="user_id")
     */
    private $user;

    /**
     * @ORM\ManyToMany(targetEntity="Silo\Inventory\Model\Operation", cascade={"persist"})
     * @ORM\JoinTable(name="operation_set_operations",
     *      joinColumns={@ORM\JoinColumn(name="operation_set_id", referencedColumnName="operation_set_id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="operation_id", referencedColumnName="operation_id")}
     *      )
     */
    private $operations;

    /**
     * @ORM\Column(name="value", type="json_array", nullable=true)
     */
    private $value;

    public function __construct(User $user = null, $value = null)
    {
        $this->user = $user;
        $this->value = $value;
        $this->operations = new ArrayCollection();
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return 'OperationSet:'.$this->id;
    }

    /**
     * @return mixed
     */
    public function getTypes()
    {
        $typeMap = [];
        foreach ($this->operations as $operation) {
            $t = $operation->getType();
            /** @var Operation $operation */
            $typeMap[$t] = isset($typeMap[$t]) ? $typeMap[$t] + 1 : 0;
        }

        return array_keys($typeMap);
    }

    /**
     * @return Operation[]
     */
    public function getOperations()
    {
        return $this->operations->toArray();
    }

    public function add(Operation $operation)
    {
        $operation->addOperationSet($this);
        $this->operations->add($operation);
    }

    public function remove(Operation $operation)
    {
        $operation->removeOperationSet($this);
        $this->operations->removeElement($operation);
    }

    public function isEmpty()
    {
        return $this->operations->count() == 0;
    }

    public function clear()
    {
        $this->operations->clear();
    }

    public function merge(self $set)
    {
        foreach($set->getOperations() as $operation) {
            $set->remove($operation);
            $this->add($operation);
        }
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    public function getItemCount()
    {
        $sum = function($a, $b){return $a+$b;};
        return array_reduce(array_map(function(Operation $operation)use($sum){
            array_reduce(array_map(function(Batch $batch){
                    return $batch->getQuantity();
            }, $operation->getBatches()->toArray()), $sum);
        }, $this->operations->toArray()), $sum);
    }

    public function getBatches()
    {
        $batches = new BatchCollection();

        foreach ($this->operations->toArray() as $operation) {
            $batches->merge($operation->getBatches());
        }

        return $batches;
    }

    /**
     * @param mixed $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }
}
