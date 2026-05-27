<?php
/**
 * CategoryTransclude Extension의 매직 워드(Magic Word) 파일입니다.
 * 한국어와 영어에서 파서 함수를 호출할 때 사용할 별칭을 정의합니다.
 */

$magicWords = [];

/**
 * 영어 매직 워드 설정
 */
$magicWords['en'] = [
	'categorytransclude' => [ 0, 'categorytransclude' ],
];

/**
 * 한국어 매직 워드 설정 ('분류삽입'으로도 호출 가능)
 */
$magicWords['ko'] = [
	'categorytransclude' => [ 0, 'categorytransclude', '분류삽입' ],
];
