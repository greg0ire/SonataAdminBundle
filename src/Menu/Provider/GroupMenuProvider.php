<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\AdminBundle\Menu\Provider;

use Knp\Menu\FactoryInterface;
use Knp\Menu\Provider\MenuProviderInterface;
use Sonata\AdminBundle\Admin\Pool;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Menu provider based on group options.
 *
 * @author Alexandru Furculita <alex@furculita.net>
 */
class GroupMenuProvider implements MenuProviderInterface
{
    /**
     * @var FactoryInterface
     */
    private $menuFactory;

    /**
     * @var Pool
     */
    private $pool;

    /**
     * @var AuthorizationCheckerInterface
     */
    private $checker;

    /**
     * NEXT_MAJOR: Remove default value null of $checker.
     *
     * @param AuthorizationCheckerInterface|null $checker
     */
    public function __construct(FactoryInterface $menuFactory, Pool $pool, $checker = null)
    {
        $this->menuFactory = $menuFactory;
        $this->pool = $pool;

        /*
         * NEXT_MAJOR: Remove this if blocks.
         * NEXT_MAJOR: Move AuthorizationCheckerInterface check to method signature.
         */
        if (null === $checker) {
            @trigger_error(
                'Passing no 3rd argument is deprecated since version 3.10 and will be mandatory in 4.0.
                Pass Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface as 3rd argument.',
                E_USER_DEPRECATED
            );
        } elseif (!$checker instanceof AuthorizationCheckerInterface) {
            throw new \InvalidArgumentException(
                'Argument 3 must be an instance of \Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface'
            );
        }

        $this->checker = $checker;
    }

    /**
     * Retrieves the menu based on the group options.
     *
     * @param string $name
     *
     * @throws \InvalidArgumentException if the menu does not exists
     *
     * @return \Knp\Menu\ItemInterface
     */
    public function get($name, array $options = [])
    {
        $group = $options['group'];

        $menuItem = $this->menuFactory->createItem($options['name']);

        if (empty($group['on_top']) || false === $group['on_top']) {
            foreach ($group['items'] as $item) {
                $item = $this->generateMenuItem($item, $group);

                if (null !== $item) {
                    $menuItem->addChild($item);
                }
            }

            if (false === $menuItem->hasChildren()) {
                $menuItem->setDisplay(false);
            } elseif (!empty($group['keep_open'])) {
                $menuItem->setAttribute('class', 'keep-open');
                $menuItem->setExtra('keep_open', $group['keep_open']);
            }
        } elseif (1 === count($group['items'])) {
            $item = $this->generateMenuItem($group['items'][0], $group);

            if (null !== $item) {
                $menuItem = $item;
                $menuItem->setExtra('on_top', $group['on_top']);
            } else {
                $menuItem->setDisplay(false);
            }
        }
        $menuItem->setLabel($group['label']);

        return $menuItem;
    }

    /**
     * Checks whether a menu exists in this provider.
     *
     * @param string $name
     *
     * @return bool
     */
    public function has($name, array $options = [])
    {
        return 'sonata_group_menu' === $name;
    }

    /**
     * @return ItemInterface|null
     */
    private function generateMenuItem(array $item, array $group)
    {
        if (isset($item['admin']) && !empty($item['admin'])) {
            $admin = $this->pool->getInstance($item['admin']);

            // skip menu item if no `list` url is available or user doesn't have the LIST access rights
            if (!$admin->hasRoute('list') || !$admin->hasAccess('list')) {
                return null;
            }

            $options = $admin->generateMenuUrl('list', [], $item['route_absolute']);
            $options['extras'] = [
                'label_catalogue' => $admin->getTranslationDomain(),
                'admin' => $admin,
            ];

            return $this->menuFactory->createItem($admin->getLabel(), $options);
        }

        //NEXT_MAJOR: Remove if statement of null checker.
        if (null !== $this->checker) {
            if ((!empty($item['roles']) && !$this->checker->isGranted($item['roles']))
                || (!empty($group['roles']) && !$this->checker->isGranted($group['roles']))
            ) {
                return null;
            }
        }

        return $this->menuFactory->createItem($item['label'], [
            'route' => $item['route'],
            'routeParameters' => $item['route_params'],
            'routeAbsolute' => $item['route_absolute'],
            'extras' => [
                'label_catalogue' => $group['label_catalogue'],
            ],
        ]);
    }
}
