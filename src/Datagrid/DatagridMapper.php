<?php

declare(strict_types=1);

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\AdminBundle\Datagrid;

use Sonata\AdminBundle\Admin\AdminInterface;
use Sonata\AdminBundle\Admin\FieldDescriptionInterface;
use Sonata\AdminBundle\Builder\DatagridBuilderInterface;
use Sonata\AdminBundle\Filter\FilterInterface;
use Sonata\AdminBundle\Mapper\BaseMapper;

/**
 * This class is use to simulate the Form API.
 *
 * @final since sonata-project/admin-bundle 3.52
 *
 * @author Thomas Rabaix <thomas.rabaix@sonata-project.org>
 */
class DatagridMapper extends BaseMapper
{
    /**
     * @var DatagridInterface
     */
    protected $datagrid;

    /**
     * @var DatagridBuilderInterface
     */
    protected $builder;

    public function __construct(
        DatagridBuilderInterface $datagridBuilder,
        DatagridInterface $datagrid,
        AdminInterface $admin
    ) {
        parent::__construct($datagridBuilder, $admin);
        $this->datagrid = $datagrid;
    }

    /**
     * @param FieldDescriptionInterface|string $name
     * @param array<string, mixed>             $filterOptions
     * @param array<string, mixed>|null        $fieldOptions
     * @param array<string, mixed>             $fieldDescriptionOptions
     *
     * @throws \LogicException
     */
    public function add(
        $name,
        ?string $type = null,
        array $filterOptions = [],
        ?string $fieldType = null,
        ?array $fieldOptions = null,
        array $fieldDescriptionOptions = []
    ): self {
        if (null !== $fieldOptions) {
            $filterOptions['field_options'] = $fieldOptions;
        }

        if (null !== $fieldType) {
            $filterOptions['field_type'] = $fieldType;
        }

        if ($name instanceof FieldDescriptionInterface) {
            $fieldDescription = $name;
            $fieldDescription->mergeOptions($filterOptions);
        } elseif (\is_string($name)) {
            if ($this->admin->hasFilterFieldDescription($name)) {
                throw new \LogicException(sprintf(
                    'Duplicate field name "%s" in datagrid mapper. Names should be unique.',
                    $name
                ));
            }

            if (!isset($filterOptions['field_name'])) {
                $filterOptions['field_name'] = substr(strrchr('.'.$name, '.'), 1);
            }

            $fieldDescription = $this->admin->getModelManager()->getNewFieldDescriptionInstance(
                $this->admin->getClass(),
                $name,
                array_merge($filterOptions, $fieldDescriptionOptions)
            );
        } else {
            throw new \TypeError(
                'Unknown field name in datagrid mapper.'
                .' Field name should be either of FieldDescriptionInterface interface or string.'
            );
        }

        if (!isset($fieldDescriptionOptions['role']) || $this->admin->isGranted($fieldDescriptionOptions['role'])) {
            // add the field with the DatagridBuilder
            $this->builder->addFilter($this->datagrid, $type, $fieldDescription, $this->admin);
        }

        return $this;
    }

    public function get(string $name): FilterInterface
    {
        return $this->datagrid->getFilter($name);
    }

    public function has(string $key): bool
    {
        return $this->datagrid->hasFilter($key);
    }

    final public function keys(): array
    {
        return array_keys($this->datagrid->getFilters());
    }

    public function remove(string $key): self
    {
        $this->admin->removeFilterFieldDescription($key);
        $this->datagrid->removeFilter($key);

        return $this;
    }

    public function reorder(array $keys): self
    {
        $this->datagrid->reorderFilters($keys);

        return $this;
    }
}
