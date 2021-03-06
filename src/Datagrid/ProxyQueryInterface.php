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

/**
 * Used by the Datagrid to build the query.
 *
 * @author Thomas Rabaix <thomas.rabaix@sonata-project.org>
 */
interface ProxyQueryInterface
{
    /**
     * @param mixed[] $args
     *
     * @return mixed
     */
    public function __call(string $name, array $args);

    /**
     * @param array<string, mixed> $params
     *
     * @return mixed
     */
    public function execute(array $params = [], ?int $hydrationMode = null);

    /**
     * @param mixed[] $parentAssociationMappings
     * @param mixed[] $fieldMapping
     */
    public function setSortBy(array $parentAssociationMappings, array $fieldMapping): self;

    public function getSortBy(): ?string;

    public function setSortOrder(string $sortOrder): self;

    public function getSortOrder(): ?string;

    /**
     * NEXT_MAJOR: Remove this method.
     *
     * @deprecated since sonata-project/admin-bundle 3.x, to be removed in 4.0.
     */
    public function getSingleScalarResult(): ?int;

    public function setFirstResult(?int $firstResult): self;

    public function getFirstResult(): ?int;

    public function setMaxResults(?int $maxResults): self;

    public function getMaxResults(): ?int;

    public function getUniqueParameterId(): int;

    /**
     * Join entities from the given association mappings and return the last alias created.
     *
     * @param mixed[] $associationMappings
     */
    public function entityJoin(array $associationMappings): string;
}
