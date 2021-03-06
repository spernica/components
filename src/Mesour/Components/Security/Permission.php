<?php
/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

namespace Mesour\Components\Security;

use Mesour;


/**
 * Access control list (ACL) functionality and privileges management.
 *
 * This solution is mostly based on Zend_Acl (c) Zend Technologies USA Inc. (http://www.zend.com), new BSD license
 *
 * @copyright  Copyright (c) 2005, 2007 Zend Technologies USA Inc.
 */
class Permission implements IAuthorizator
{

	/** @var array  Role storage */
	private $roles = [];

	/** @var array  Resource storage */
	private $resources = [];

	/** @var array  Access Control List rules; whitelist (deny everything to all) by default */
	private $rules = [
		'allResources' => [
			'allRoles' => [
				'allPrivileges' => [
					'type' => self::DENY,
					'assert' => null,
				],
				'byPrivilege' => [],
			],
			'byRole' => [],
		],
		'byResource' => [],
	];
	/** @var mixed */
	private $queriedRole, $queriedResource;

	/********************* roles ****************d*g**/
	/**
	 * Adds a Role to the list. The most recently added parent
	 * takes precedence over parents that were previously added.
	 * @param  string
	 * @param  string|array
	 * @throws Mesour\InvalidStateException
	 * @return self
	 */
	public function addRole($role, $parents = null)
	{
		$this->checkRole($role, false);
		if (isset($this->roles[$role])) {
			throw new Mesour\InvalidStateException("Role '$role' already exists in the list.");
		}
		$roleParents = [];
		if ($parents !== null) {
			if (!is_array($parents)) {
				$parents = [$parents];
			}
			foreach ($parents as $parent) {
				$this->checkRole($parent);
				$roleParents[$parent] = true;
				$this->roles[$parent]['children'][$role] = true;
			}
		}
		$this->roles[$role] = [
			'parents' => $roleParents,
			'children' => [],
		];
		return $this;
	}

	/**
	 * Returns TRUE if the Role exists in the list.
	 * @param  string
	 * @return bool
	 */
	public function hasRole($role)
	{
		$this->checkRole($role, false);
		return isset($this->roles[$role]);
	}

	/**
	 * Checks whether Role is valid and exists in the list.
	 * @param  string
	 * @param  bool
	 * @throws Mesour\InvalidArgumentException
	 * @throws Mesour\InvalidStateException
	 * @return void
	 */
	private function checkRole($role, $need = true)
	{
		if (!is_string($role) || $role === '') {
			throw new Mesour\InvalidArgumentException('Role must be a non-empty string.');
		} elseif ($need && !isset($this->roles[$role])) {
			throw new Mesour\InvalidStateException("Role '$role' does not exist.");
		}
	}

	/**
	 * Returns all Roles.
	 * @return array
	 */
	public function getRoles()
	{
		return array_keys($this->roles);
	}

	/**
	 * Returns existing Role's parents ordered by ascending priority.
	 * @param  string
	 * @return array
	 */
	public function getRoleParents($role)
	{
		$this->checkRole($role);
		return array_keys($this->roles[$role]['parents']);
	}

	/**
	 * Returns TRUE if $role inherits from $inherit. If $onlyParents is TRUE,
	 * then $role must inherit directly from $inherit.
	 * @param  string
	 * @param  string
	 * @param  bool
	 * @throws Mesour\InvalidStateException
	 * @return bool
	 */
	public function roleInheritsFrom($role, $inherit, $onlyParents = false)
	{
		$this->checkRole($role);
		$this->checkRole($inherit);
		$inherits = isset($this->roles[$role]['parents'][$inherit]);
		if ($inherits || $onlyParents) {
			return $inherits;
		}
		foreach ($this->roles[$role]['parents'] as $parent => $foo) {
			if ($this->roleInheritsFrom($parent, $inherit)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Removes the Role from the list.
	 *
	 * @param  string
	 * @throws Mesour\InvalidStateException
	 * @return self
	 */
	public function removeRole($role)
	{
		$this->checkRole($role);
		foreach ($this->roles[$role]['children'] as $child => $foo) {
			unset($this->roles[$child]['parents'][$role]);
		}
		foreach ($this->roles[$role]['parents'] as $parent => $foo) {
			unset($this->roles[$parent]['children'][$role]);
		}
		unset($this->roles[$role]);
		foreach ($this->rules['allResources']['byRole'] as $roleCurrent => $rules) {
			if ($role === $roleCurrent) {
				unset($this->rules['allResources']['byRole'][$roleCurrent]);
			}
		}
		foreach ($this->rules['byResource'] as $resourceCurrent => $visitor) {
			if (isset($visitor['byRole'])) {
				foreach ($visitor['byRole'] as $roleCurrent => $rules) {
					if ($role === $roleCurrent) {
						unset($this->rules['byResource'][$resourceCurrent]['byRole'][$roleCurrent]);
					}
				}
			}
		}
		return $this;
	}

	/**
	 * Removes all Roles from the list.
	 *
	 * @return self
	 */
	public function removeAllRoles()
	{
		$this->roles = [];
		foreach ($this->rules['allResources']['byRole'] as $roleCurrent => $rules) {
			unset($this->rules['allResources']['byRole'][$roleCurrent]);
		}
		foreach ($this->rules['byResource'] as $resourceCurrent => $visitor) {
			foreach ($visitor['byRole'] as $roleCurrent => $rules) {
				unset($this->rules['byResource'][$resourceCurrent]['byRole'][$roleCurrent]);
			}
		}
		return $this;
	}
	/********************* resources ****************d*g**/
	/**
	 * Adds a Resource having an identifier unique to the list.
	 *
	 * @param  string
	 * @param  string
	 * @throws Mesour\InvalidArgumentException
	 * @throws Mesour\InvalidStateException
	 * @return self
	 */
	public function addResource($resource, $parent = null)
	{
		$this->checkResource($resource, false);
		if (isset($this->resources[$resource])) {
			throw new Mesour\InvalidStateException("Resource '$resource' already exists in the list.");
		}
		if ($parent !== null) {
			$this->checkResource($parent);
			$this->resources[$parent]['children'][$resource] = true;
		}
		$this->resources[$resource] = [
			'parent' => $parent,
			'children' => [],
		];
		return $this;
	}

	/**
	 * Returns TRUE if the Resource exists in the list.
	 * @param  string
	 * @return bool
	 */
	public function hasResource($resource)
	{
		$this->checkResource($resource, false);
		return isset($this->resources[$resource]);
	}

	/**
	 * Checks whether Resource is valid and exists in the list.
	 * @param  string
	 * @param  bool
	 * @throws Mesour\InvalidStateException
	 * @throws Mesour\InvalidArgumentException
	 * @return void
	 */
	private function checkResource($resource, $need = true)
	{
		if (!is_string($resource) || $resource === '') {
			throw new Mesour\InvalidArgumentException('Resource must be a non-empty string.');
		} elseif ($need && !isset($this->resources[$resource])) {
			throw new Mesour\InvalidStateException("Resource '$resource' does not exist.");
		}
	}

	/**
	 * Returns all Resources.
	 * @return array
	 */
	public function getResources()
	{
		return array_keys($this->resources);
	}

	/**
	 * Returns TRUE if $resource inherits from $inherit. If $onlyParents is TRUE,
	 * then $resource must inherit directly from $inherit.
	 *
	 * @param  string
	 * @param  string
	 * @param  bool
	 * @throws Mesour\InvalidStateException
	 * @return bool
	 */
	public function resourceInheritsFrom($resource, $inherit, $onlyParent = false)
	{
		$this->checkResource($resource);
		$this->checkResource($inherit);
		if ($this->resources[$resource]['parent'] === null) {
			return false;
		}
		$parent = $this->resources[$resource]['parent'];
		if ($inherit === $parent) {
			return true;
		} elseif ($onlyParent) {
			return false;
		}
		while ($this->resources[$parent]['parent'] !== null) {
			$parent = $this->resources[$parent]['parent'];
			if ($inherit === $parent) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Removes a Resource and all of its children.
	 *
	 * @param  string
	 * @throws Mesour\InvalidStateException
	 * @return self
	 */
	public function removeResource($resource)
	{
		$this->checkResource($resource);
		$parent = $this->resources[$resource]['parent'];
		if ($parent !== null) {
			unset($this->resources[$parent]['children'][$resource]);
		}
		$removed = [$resource];
		foreach ($this->resources[$resource]['children'] as $child => $foo) {
			$this->removeResource($child);
			$removed[] = $child;
		}
		foreach ($removed as $resourceRemoved) {
			foreach ($this->rules['byResource'] as $resourceCurrent => $rules) {
				if ($resourceRemoved === $resourceCurrent) {
					unset($this->rules['byResource'][$resourceCurrent]);
				}
			}
		}
		unset($this->resources[$resource]);
		return $this;
	}

	/**
	 * Removes all Resources.
	 * @return self
	 */
	public function removeAllResources()
	{
		foreach ($this->resources as $resource => $foo) {
			foreach ($this->rules['byResource'] as $resourceCurrent => $rules) {
				if ($resource === $resourceCurrent) {
					unset($this->rules['byResource'][$resourceCurrent]);
				}
			}
		}
		$this->resources = [];
		return $this;
	}
	/********************* defining rules ****************d*g**/
	/**
	 * Allows one or more Roles access to [certain $privileges upon] the specified Resource(s).
	 * If $assertion is provided, then it must return TRUE in order for rule to apply.
	 *
	 * @param  string|array|Permission::ALL $roles roles
	 * @param  string|array|Permission::ALL $resources resources
	 * @param  string|array|Permission::ALL $privileges privileges
	 * @param  callable $assertion assertion
	 * @return self
	 */
	public function allow($roles = self::ALL, $resources = self::ALL, $privileges = self::ALL, $assertion = null)
	{
		$this->setRule(true, self::ALLOW, $roles, $resources, $privileges, $assertion);
		return $this;
	}

	/**
	 * Denies one or more Roles access to [certain $privileges upon] the specified Resource(s).
	 * If $assertion is provided, then it must return TRUE in order for rule to apply.
	 *
	 * @param  string|array|Permission::ALL $roles roles
	 * @param  string|array|Permission::ALL $resources resources
	 * @param  string|array|Permission::ALL $privileges privileges
	 * @param  callable $assertion assertion
	 * @return self
	 */
	public function deny($roles = self::ALL, $resources = self::ALL, $privileges = self::ALL, $assertion = null)
	{
		$this->setRule(true, self::DENY, $roles, $resources, $privileges, $assertion);
		return $this;
	}

	/**
	 * Removes "allow" permissions from the list in the context of the given Roles, Resources, and privileges.
	 *
	 * @param  string|array|Permission::ALL  roles
	 * @param  string|array|Permission::ALL  resources
	 * @param  string|array|Permission::ALL  privileges
	 * @return self
	 */
	public function removeAllow($roles = self::ALL, $resources = self::ALL, $privileges = self::ALL)
	{
		$this->setRule(false, self::ALLOW, $roles, $resources, $privileges);
		return $this;
	}

	/**
	 * Removes "deny" restrictions from the list in the context of the given Roles, Resources, and privileges.
	 *
	 * @param  string|array|Permission::ALL  roles
	 * @param  string|array|Permission::ALL  resources
	 * @param  string|array|Permission::ALL  privileges
	 * @return self
	 */
	public function removeDeny($roles = self::ALL, $resources = self::ALL, $privileges = self::ALL)
	{
		$this->setRule(false, self::DENY, $roles, $resources, $privileges);
		return $this;
	}

	/**
	 * Performs operations on Access Control List rules.
	 * @param  bool $toAdd operation add?
	 * @param  bool $type type
	 * @param  string|array|Permission::ALL $roles roles
	 * @param  string|array|Permission::ALL $resources resources
	 * @param  string|array|Permission::ALL $privileges privileges
	 * @param  callable $assertion assertion
	 * @throws Mesour\InvalidStateException
	 * @return self
	 */
	protected function setRule($toAdd, $type, $roles, $resources, $privileges, $assertion = null)
	{
		// ensure that all specified Roles exist; normalize input to array of Roles or NULL
		if ($roles === self::ALL) {
			$roles = [self::ALL];
		} else {
			if (!is_array($roles)) {
				$roles = [$roles];
			}
			foreach ($roles as $role) {
				$this->checkRole($role);
			}
		}
		// ensure that all specified Resources exist; normalize input to array of Resources or NULL
		if ($resources === self::ALL) {
			$resources = [self::ALL];
		} else {
			if (!is_array($resources)) {
				$resources = [$resources];
			}
			foreach ($resources as $resource) {
				$this->checkResource($resource);
			}
		}
		// normalize privileges to array
		if ($privileges === self::ALL) {
			$privileges = [];
		} elseif (!is_array($privileges)) {
			$privileges = [$privileges];
		}
		if ($toAdd) { // add to the rules
			foreach ($resources as $resource) {
				foreach ($roles as $role) {
					$rules = &$this->getRules($resource, $role, true);
					if (count($privileges) === 0) {
						$rules['allPrivileges']['type'] = $type;
						$rules['allPrivileges']['assert'] = $assertion;
						if (!isset($rules['byPrivilege'])) {
							$rules['byPrivilege'] = [];
						}
					} else {
						foreach ($privileges as $privilege) {
							$rules['byPrivilege'][$privilege]['type'] = $type;
							$rules['byPrivilege'][$privilege]['assert'] = $assertion;
						}
					}
				}
			}
		} else { // remove from the rules
			foreach ($resources as $resource) {
				foreach ($roles as $role) {
					$rules = &$this->getRules($resource, $role);
					if ($rules === null) {
						continue;
					}
					if (count($privileges) === 0) {
						if ($resource === self::ALL && $role === self::ALL) {
							if ($type === $rules['allPrivileges']['type']) {
								$rules = [
									'allPrivileges' => [
										'type' => self::DENY,
										'assert' => null,
									],
									'byPrivilege' => [],
								];
							}
							continue;
						}
						if ($type === $rules['allPrivileges']['type']) {
							unset($rules['allPrivileges']);
						}
					} else {
						foreach ($privileges as $privilege) {
							if (isset($rules['byPrivilege'][$privilege]) &&
								$type === $rules['byPrivilege'][$privilege]['type']
							) {
								unset($rules['byPrivilege'][$privilege]);
							}
						}
					}
				}
			}
		}
		return $this;
	}
	/********************* querying the ACL ****************d*g**/
	/**
	 * Returns TRUE if and only if the Role has access to [certain $privileges upon] the Resource.
	 *
	 * This method checks Role inheritance using a depth-first traversal of the Role list.
	 * The highest priority parent (i.e., the parent most recently added) is checked first,
	 * and its respective parents are checked similarly before the lower-priority parents of
	 * the Role are checked.
	 *
	 * @param  string|Permission::ALL|Permission::ALLOW|Permission::DENY  role
	 * @param  string|Permission::ALL|Permission::ALLOW|Permission::DENY  resource
	 * @param  string|Permission::ALL|Permission::ALLOW|Permission::DENY  privilege
	 * @throws Mesour\InvalidStateException
	 * @return bool
	 */
	public function isAllowed($role = self::ALL, $resource = self::ALL, $privilege = self::ALL)
	{
		$this->queriedRole = $role;
		if ($role !== self::ALL) {
			$this->checkRole($role);
		}
		$this->queriedResource = $resource;
		if ($resource !== self::ALL) {
			$this->checkResource($resource);
		}
		do {
			// depth-first search on $role if it is not 'allRoles' pseudo-parent
			if ($role !== null && null !== ($result = $this->searchRolePrivileges($privilege === self::ALL, $role, $resource, $privilege))) {
				break;
			}
			if ($privilege === self::ALL) {
				if ($rules = $this->getRules($resource, self::ALL)) { // look for rule on 'allRoles' psuedo-parent
					foreach ($rules['byPrivilege'] as $privilege => $rule) {
						if (self::DENY === ($result = $this->getRuleType($resource, null, $privilege))) {
							break 2;
						}
					}
					if (null !== ($result = $this->getRuleType($resource, null, null))) {
						break;
					}
				}
			} else {
				if (null !== ($result = $this->getRuleType($resource, null, $privilege))) { // look for rule on 'allRoles' pseudo-parent
					break;
				} elseif (null !== ($result = $this->getRuleType($resource, null, null))) {
					break;
				}
			}
			$resource = $this->resources[$resource]['parent']; // try next Resource
		} while (true);
		$this->queriedRole = $this->queriedResource = null;
		return $result;
	}

	/**
	 * Returns real currently queried Role. Use by assertion.
	 * @return mixed
	 */
	public function getQueriedRole()
	{
		return $this->queriedRole;
	}

	/**
	 * Returns real currently queried Resource. Use by assertion.
	 * @return mixed
	 */
	public function getQueriedResource()
	{
		return $this->queriedResource;
	}
	/********************* internals ****************d*g**/
	/**
	 * Performs a depth-first search of the Role DAG, starting at $role, in order to find a rule
	 * allowing/denying $role access to a/all $privilege upon $resource.
	 * @param  bool $all all (true) or one?
	 * @param  string $role
	 * @param  string $resource
	 * @param  string $privilege only for one
	 * @return mixed  NULL if no applicable rule is found, otherwise returns ALLOW or DENY
	 */
	private function searchRolePrivileges($all, $role, $resource, $privilege)
	{
		$dfs = [
			'visited' => [],
			'stack' => [$role],
		];
		while (null !== ($role = array_pop($dfs['stack']))) {
			if (isset($dfs['visited'][$role])) {
				continue;
			}
			if ($all) {
				if ($rules = $this->getRules($resource, $role)) {
					foreach ($rules['byPrivilege'] as $privilege2 => $rule) {
						if (self::DENY === $this->getRuleType($resource, $role, $privilege2)) {
							return self::DENY;
						}
					}
					if (null !== ($type = $this->getRuleType($resource, $role, null))) {
						return $type;
					}
				}
			} else {
				if (null !== ($type = $this->getRuleType($resource, $role, $privilege))) {
					return $type;
				} elseif (null !== ($type = $this->getRuleType($resource, $role, null))) {
					return $type;
				}
			}
			$dfs['visited'][$role] = true;
			foreach ($this->roles[$role]['parents'] as $roleParent => $foo) {
				$dfs['stack'][] = $roleParent;
			}
		}
		return null;
	}

	/**
	 * Returns the rule type associated with the specified Resource, Role, and privilege.
	 * @param  string|Permission::ALL
	 * @param  string|Permission::ALL
	 * @param  string|Permission::ALL
	 * @return mixed  NULL if a rule does not exist or assertion fails, otherwise returns ALLOW or DENY
	 */
	private function getRuleType($resource, $role, $privilege)
	{
		if (!$rules = $this->getRules($resource, $role)) {
			return null;
		}
		if ($privilege === self::ALL) {
			if (isset($rules['allPrivileges'])) {
				$rule = $rules['allPrivileges'];
			} else {
				return null;
			}
		} elseif (!isset($rules['byPrivilege'][$privilege])) {
			return null;
		} else {
			$rule = $rules['byPrivilege'][$privilege];
		}
		if ($rule['assert'] === null || Mesour\Components\Utils\Helpers::invokeArgs($rule['assert'], [$this, $role, $resource, $privilege])) {
			return $rule['type'];
		} elseif ($resource !== self::ALL || $role !== self::ALL || $privilege !== self::ALL) {
			return null;
		} elseif (self::ALLOW === $rule['type']) {
			return self::DENY;
		} else {
			return self::ALLOW;
		}
	}

	/**
	 * Returns the rules associated with a Resource and a Role, or NULL if no such rules exist.
	 * If the $create parameter is TRUE, then a rule set is first created and then returned to the caller.
	 * @param  string|Permission::ALL $resource
	 * @param  string|Permission::ALL $role
	 * @param  bool $create
	 * @return array|NULL
	 */
	private function &getRules($resource, $role, $create = false)
	{
		$null = null;
		if ($resource === self::ALL) {
			$visitor = &$this->rules['allResources'];
		} else {
			if (!isset($this->rules['byResource'][$resource])) {
				if (!$create) {
					return $null;
				}
				$this->rules['byResource'][$resource] = [];
			}
			$visitor = &$this->rules['byResource'][$resource];
		}
		if ($role === self::ALL) {
			if (!isset($visitor['allRoles'])) {
				if (!$create) {
					return $null;
				}
				$visitor['allRoles']['byPrivilege'] = [];
			}
			return $visitor['allRoles'];
		}
		if (!isset($visitor['byRole'][$role])) {
			if (!$create) {
				return $null;
			}
			$visitor['byRole'][$role]['byPrivilege'] = [];
		}
		return $visitor['byRole'][$role];
	}

}