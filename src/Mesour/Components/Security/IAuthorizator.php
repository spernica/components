<?php
/**
 * This file is part of the Mesour components (http://components.mesour.com)
 *
 * Copyright (c) 2017 Matouš Němec (http://mesour.com)
 *
 * For full licence and copyright please view the file licence.md in root of this project
 */

namespace Mesour\Components\Security;

/**
 * @author Matouš Němec <http://mesour.com>
 */
interface IAuthorizator
{

	/** Set type: all */
	const ALL = null;

	/** Permission type: allow */
	const ALLOW = true;

	/** Permission type: deny */
	const DENY = false;

	/**
	 * Performs a role-based authorization.
	 * @param string|array|IAuthorizator::ALL|IAuthorizator::ALLOW|IAuthorizator::DENY $role
	 * @param string|array|IAuthorizator::ALL|IAuthorizator::ALLOW|IAuthorizator::DENY $resource
	 * @param string|array|IAuthorizator::ALL|IAuthorizator::ALLOW|IAuthorizator::DENY $privilege
	 * @return bool
	 */
	public function isAllowed($role, $resource, $privilege);

}
