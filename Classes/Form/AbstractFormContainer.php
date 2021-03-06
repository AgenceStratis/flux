<?php
namespace FluidTYPO3\Flux\Form;

/*
 * This file is part of the FluidTYPO3/Flux project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

/**
 * AbstractFormContainer
 */
abstract class AbstractFormContainer extends AbstractFormComponent implements ContainerInterface
{

    /**
     * @var FormInterface[]
     */
    protected $children;

    /**
     * @var string
     */
    protected $transform;

    /**
     * @var boolean
     */
    protected $inherit = true;

    /**
     * @var boolean
     */
    protected $inheritEmpty = false;

    /**
     * CONSTRUCTOR
     */
    public function __construct()
    {
        $this->children = new \SplObjectStorage();
    }

    /**
     * @param string $namespace
     * @param string $type
     * @param string $name
     * @param null $label
     * @return FormInterface
     */
    public function createComponent($namespace, $type, $name, $label = null)
    {
        $component = parent::createComponent($namespace, $type, $name, $label);
        $this->add($component);
        return $component;
    }

    /**
     * @param FormInterface $child
     * @return FormInterface
     */
    public function add(FormInterface $child)
    {
        if (false === $this->children->contains($child)) {
            $this->children->attach($child);
            $child->setParent($this);
        }
        return $this;
    }

    /**
     * @param array|\Traversable $children
     * @return FormInterface
     */
    public function addAll($children)
    {
        foreach ($children as $child) {
            $this->add($child);
        }
        return $this;
    }

    /**
     * @param FieldInterface|string $childName
     * @return FormInterface|FALSE
     */
    public function remove($childName)
    {
        foreach ($this->children as $child) {
            /** @var FieldInterface $child */
            $isMatchingInstance = ($childName instanceof FormInterface && $childName->getName() === $child->getName());
            $isMatchingName = ($childName === $child->getName());
            if (true === $isMatchingName || true === $isMatchingInstance) {
                $this->children->detach($child);
                $this->children->rewind();
                $child->setParent(null);
                return $child;
            }
        }
        return false;
    }

    /**
     * @param mixed $childOrChildName
     * @return boolean
     */
    public function has($childOrChildName)
    {
        $name = ($childOrChildName instanceof FormInterface) ? $childOrChildName->getName() : $childOrChildName;
        return (false !== $this->get($name));
    }

    /**
     * @param string $childName
     * @param boolean $recursive
     * @param string $requiredClass
     * @return FormInterface|FALSE
     */
    public function get($childName, $recursive = false, $requiredClass = null)
    {
        foreach ($this->children as $existingChild) {
            if ($childName === $existingChild->getName()
                && (!$requiredClass || $existingChild instanceof $requiredClass)
            ) {
                return $existingChild;
            }
            if (true === $recursive && true === $existingChild instanceof ContainerInterface) {
                $candidate = $existingChild->get($childName, $recursive);
                if (false !== $candidate) {
                    return $candidate;
                }
            }
        }
        return false;
    }

    /**
     * @return FormInterface|FALSE
     */
    public function last()
    {
        $asArray = iterator_to_array($this->children);
        $result = array_pop($asArray);
        return $result;
    }

    /**
     * @return boolean
     */
    public function hasChildren()
    {
        return 0 < $this->children->count();
    }

    /**
     * @param string $transform
     * @return ContainerInterface
     */
    public function setTransform($transform)
    {
        $this->transform = $transform;
        return $this;
    }

    /**
     * @return string
     */
    public function getTransform()
    {
        return $this->transform;
    }

    /**
     * @param array $structure
     * @return ContainerInterface
     */
    public function modify(array $structure)
    {
        if (true === isset($structure['fields'])) {
            foreach ((array) $structure['fields'] as $index => $fieldData) {
                $fieldName = true === isset($fieldData['name']) ? $fieldData['name'] : $index;
                // check if field already exists - if it does, modify it. If it does not, create it.
                if (true === $this->has($fieldName)) {
                    $field = $this->get($fieldName);
                } else {
                    $fieldType = true === isset($fieldData['type']) ? $fieldData['type'] : 'None';
                    $field = $this->createField($fieldType, $fieldName);
                }
                $field->modify($fieldData);
            }
        }
        return parent::modify($structure);
    }
}
