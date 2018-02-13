<?php

namespace CanalTP\SamEcoreApplicationManagerBundle\Permission;

use CanalTP\SamEcoreApplicationManagerBundle\Permission\BusinessPermission;

abstract class AbstractBusinessPermissionModule implements BusinessPermissionModuleInterface
{
    protected $permissions;
    protected $permissionsObject;

    public function __construct(array $permissions)
    {

        $this->permissions = $permissions;
    }

    public function getNumberPermissions()
    {
        return count($this->permissions);
    }

    private function createBusinessPermission($label, $description, $key)
    {
        $businessPermission = new BusinessPermission();
        $businessPermission->setName($label);
        $businessPermission->setDescription($description);
        $businessPermission->setId($key);

        return ($businessPermission);
    }

    public function getPermissions()
    {
        if (null !== $this->permissionsObject) {
            return $this->permissionsObject;
        }

        $permissions = array();
        foreach ($this->permissions as $key => $permission) {
            $permissions[] = $this->createBusinessPermission(
                $permission['label'],
                $permission['description'],
                $key
            );
        }

        $this->permissionsObject = $permissions;

        return $this->permissionsObject;
    }

    public function getId()
    {
        return null;
    }

    public function getName()
    {
        return '';
    }
}
