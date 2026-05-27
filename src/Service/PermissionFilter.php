<?php

namespace MediaWiki\Extension\CategoryTransclude\Service;

use MediaWiki\Permissions\PermissionManager;
use MediaWiki\Title\Title;
use MediaWiki\User\UserIdentity;

/**
 * 사용자가 특정 문서에 대해 읽기 권한을 가지고 있는지 검사하고 필터링하는 서비스 클래스입니다.
 */
class PermissionFilter {
	private PermissionManager $permissionManager;

	/**
	 * @param PermissionManager $permissionManager
	 */
	public function __construct( PermissionManager $permissionManager ) {
		$this->permissionManager = $permissionManager;
	}

	/**
	 * 사용자가 지정된 타이틀(문서)을 읽을 수 있는지 확인합니다.
	 *
	 * @param UserIdentity $user 검사할 미디어위키 UserIdentity 객체
	 * @param Title $title 검사할 미디어위키 Title 객체
	 * @return bool 읽기 권한이 있으면 true, 없으면 false
	 */
	public function canRead( UserIdentity $user, Title $title ): bool {
		return $this->permissionManager->userCan( 'read', $user, $title );
	}
}
