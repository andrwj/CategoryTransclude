<?php

namespace MediaWiki\Extension\CategoryTransclude\Repository;

use MediaWiki\Extension\CategoryTransclude\Model\CategoryMember;
use MediaWiki\Extension\CategoryTransclude\Model\CategoryTranscludeParams;
use MediaWiki\Title\TitleFactory;
use Wikimedia\Rdbms\ILoadBalancer;
use MediaWiki\MediaWikiServices;

/**
 * categorylinks 테이블과 page 테이블을 조회하여 카테고리 멤버 목록을 가져오는 리포지토리 클래스입니다.
 */
class CategoryMemberRepository {
	private ILoadBalancer $loadBalancer;
	private TitleFactory $titleFactory;

	/**
	 * @param ILoadBalancer $loadBalancer
	 * @param TitleFactory $titleFactory
	 */
	public function __construct( ILoadBalancer $loadBalancer, TitleFactory $titleFactory ) {
		$this->loadBalancer = $loadBalancer;
		$this->titleFactory = $titleFactory;
	}

	/**
	 * 파라미터 조건에 일치하는 카테고리 멤버 목록을 DB에서 조회하여 반환합니다.
	 *
	 * @param CategoryTranscludeParams $params 파서 매개변수 DTO
	 * @return CategoryMember[]
	 */
	public function findMembers( CategoryTranscludeParams $params ): array {
		$dbr = $this->loadBalancer->getConnection( DB_REPLICA );
		$categoryDbKey = $params->categoryTitle->getDBkey();

		// MediaWiki 1.45 스키마: categorylinks.cl_to 컬럼이 삭제됨.
		// 코어(ApiQueryCategoryMembers)와 동일하게 linktarget 테이블을 통해
		// 카테고리를 조회합니다.
		// 참고: includes/api/ApiQueryCategoryMembers.php L94-100
		$queryBuilder = $dbr->newSelectQueryBuilder()
			->select( [
				'page_id',
				'page_namespace',
				'page_title',
				'page_is_redirect',
				'cl_sortkey',
				'cl_timestamp',
				'cl_type',
			] )
			->from( 'page' )
			->join( 'categorylinks', null, 'cl_from = page_id' )
			->join( 'linktarget', null, 'cl_target_id = lt_id' )
			->where( [
				'lt_namespace' => NS_CATEGORY,
				'lt_title'    => $categoryDbKey,
			] );

		// 2. 카테고리 멤버 cl_type 필터 적용 (page, file, subcat)
		if ( !empty( $params->types ) ) {
			$queryBuilder->andWhere( [ 'cl_type' => $params->types ] );
		}

		// 3. 네임스페이스 포함 필터 적용
		if ( $params->namespaces !== null ) {
			$queryBuilder->andWhere( [ 'page_namespace' => $params->namespaces ] );
		}

		// 4. 네임스페이스 제외 필터 적용
		if ( !empty( $params->excludeNamespaces ) ) {
			$queryBuilder->andWhere(
				'page_namespace NOT IN (' . $dbr->makeList( $params->excludeNamespaces ) . ')'
			);
		}

		// 5. 정렬 기준 설정
		$direction = ( strtoupper( $params->direction ) === 'DESC' ) ? 'DESC' : 'ASC';
		switch ( $params->order ) {
			case 'title':
				$sortField = 'page_title';
				break;
			case 'timestamp':
				$sortField = 'cl_timestamp';
				break;
			case 'pageid':
				$sortField = 'page_id';
				break;
			case 'sortkey':
			default:
				$sortField = 'cl_sortkey';
				break;
		}

		// 6. 정렬·제한 적용 후 쿼리 실행
		$queryBuilder
			->orderBy( "{$sortField} {$direction}" )
			->orderBy( "cl_from {$direction}" )
			->limit( $params->limit )
			->caller( __METHOD__ );

		$res = $queryBuilder->fetchResultSet();

		$members = [];
		foreach ( $res as $row ) {
			$member = new CategoryMember();
			$member->pageId = (int)$row->page_id;
			$member->namespace = (int)$row->page_namespace;
			$member->dbKey = (string)$row->page_title;
			$member->sortKey = (string)$row->cl_sortkey;
			$member->type = (string)$row->cl_type;
			$member->timestamp = $row->cl_timestamp ? (string)$row->cl_timestamp : null;
			$member->isRedirect = (bool)$row->page_is_redirect;

			// 미디어위키 Title을 생성해 정규화된 Prefixed Text 획득
			$title = $this->titleFactory->newFromRow( $row );
			if ( $title ) {
				$member->prefixedText = $title->getPrefixedText();
			} else {
				$member->prefixedText = $member->dbKey;
			}

			// 7. 리다이렉트(Redirect) 처리
			if ( $member->isRedirect ) {
				if ( $params->redirectMode === 'skip' ) {
					// skip 모드: 리다이렉트 문서는 목록에서 생략
					continue;
				} elseif ( $params->redirectMode === 'follow' && $title ) {
					// follow 모드: 리다이렉트 대상을 추적하여 실제 문서 정보로 대체
					// RedirectLookup 서비스를 통해 리다이렉트 타겟 조회
					// DB 부하 및 무한 루프 방지를 위해 1레벨만 추적
					$redirectLookup = MediaWikiServices::getInstance()->getRedirectLookup();
					$targetLinkTarget = $redirectLookup->getRedirectTarget( $title );
					if ( $targetLinkTarget ) {
						$targetTitle = $this->titleFactory->castFromLinkTarget( $targetLinkTarget );
						if ( $targetTitle && $targetTitle->exists() ) {
							$member->pageId = $targetTitle->getArticleID();
							$member->namespace = $targetTitle->getNamespace();
							$member->dbKey = $targetTitle->getDBkey();
							$member->prefixedText = $targetTitle->getPrefixedText();
							$member->isRedirect = false; // 타겟 문서는 리다이렉트가 아님으로 취급
						}
					}
				}
			}

			$members[] = $member;
		}

		return $members;
	}
}
