<?php
/** *****************************************
	@file	mooyoul.take.a.REST.php
	@date	2013/08/21
	@author	이무열 (Prescott)
	@brief	PHP에서 RESTful한 서비스 (e.g: API 서비스) 를 빠르고 쉽게 구현할 수 있도록 하는 프레임워크입니다.
	
	
	@mainpage   메인 페이지
	@section intro 소개
		- 소개      : take a REST 개발문서입니다.
	@section developer 개발자
        - 이무열 (Moo Yeol, Lee) / a.k.a Prescott
	@section   Program 프로그램명
  - 프로그램명  :   take a REST
  - 프로그램내용    :   RESTful Service Framework
		@section info 개발목적
         - 미디어동글 마이크로사이트 개발
		@section advenced 추가정보
         - 글머리는 '-' 태그를 사용하면 되며
         - 탭으로 들여쓸경우 하위 항목이 된다.
         -# 번호매기기는 '-#' 방식으로 할수 있다.
         -# 위와 같이 탭으로 들여쓸경우 하위 항목이 된다.
         -# 두번째 하위 항목
         -이런식으로 그림을 넣을수도 있다.
           \image html gom.jpg

  @section  INOUTPUT    입출력자료
  - INPUT           :   없음.
  - OUTPUT      :   미디어동글 마이크로 사이트
  @section  CREATEINFO      작성정보
  - 작성자      :   이무열
  - 작성일      :   2012/10/15
  @section  MODIFYINFO      수정정보
  - 수정자/수정일   : 수정내역
  - 이무열/2012.10.15    :   신규


********************************************/



	/**
		@brief		결과문 없는 쿼리를 실행하고 성공여부를 반환합니다.
		@author		이무열 (Moo Yeol, Lee)
		@date		2012/10/15
		@see		mysql_connect
		@see		mysql_select_db
		@see		mysql_query
		@see		mysql_error
		@see		mysql_close
		@version	1.0.1 (Stable)
		@exception	die (mysql_error()); MySQL 데이터베이스 선택 실패시
		@exception	die (mysql_error()."<br />ERROR QUERY : ".$query) MySQL 쿼리 실행 실패시
		@param		$query	MySQL Query String.
		@return		bool 성공여부
		@section  CREATEINFO      작성정보
			- 작성자      :   이무열
			- 작성일      :   2012/10/15
		@section  MODIFYINFO      수정정보
			- 수정자/수정일   : 수정내역
			- 이무열/2012.10.15    :   신규
	*/
    interface iRoute
    {
        public static function get($routePath = '/', $responseContentType = 'json', $callback);
        public static function post($routePath = '/', $responseContentType = 'json', $callback);
        public static function put($routePath = '/', $responseContentType = 'json', $callback);
        public static function delete($routePath = '/', $responseContentType = 'json', $callback);
    }


    class Route implements iRoute
    {
        private static $ContentType = array(
            'text' => 'text/plain',
            'html' => 'text/html',
            'json' => 'application/json',
            'xml' => 'application/xml'
        );

        public static function get($routePath = '/', $responseContentType = 'text', $callback = null)
        {
            ExceptionManager::debug('you called Route::get method.');

            if( $_SERVER['REQUEST_METHOD'] != 'GET' )
                return false;

            ExceptionManager::debug('Request Method Check OK');

            if( !is_callable($callback) )
            {
                ExceptionManager::warn('No callback specified. or check callback is callable function, NOT string, array, object, etc. ');
                return false;
            }

            ExceptionManager::debug('Callback check OK');


            if( strpos($routePath, '*') !== false) // use wildcard match
            {
                ExceptionManager::debug('i think you are using wildcard');
                $routeWildcardMatches = array();
                //$routeWildcardRegExp = '/'.str_replace('*', '([^\\/]+)', str_replace('/', '\\/', preg_replace('/[\\*]+/', '*', $routePath))).'/i';
                $routeWildcardRegExp = '/^'.str_replace('*', '(.+)', str_replace('/', '\\/', preg_replace('/[\\*]+/', '*', $routePath))).'/i';
                ExceptionManager::debug($routeWildcardRegExp);
                if( preg_match($routeWildcardRegExp, $_SERVER['PATH_INFO'], $routeWildcardMatches) !== 1 )
                    return false;
                else
                {
                    ExceptionManager::debug('wildcard check OK');

                    array_shift($routeWildcardMatches);
                    for($i = 0 ; $i < count($routeWildcardMatches) ; $i++)
                    {
                        $routeWildcardMatches[$i] = preg_replace('/^[\\/]+/i', '', $routeWildcardMatches[$i]);
                        $routeWildcardMatches[$i] = preg_replace('/[\\/]+$/i', '', $routeWildcardMatches[$i]);
                        if($routeWildcardMatches[$i] === '')
                            unset($routeWildcardMatches[$i]);
                    }

                    self::finalize($responseContentType, $callback($routeWildcardMatches));
                    return true;
                }
            }
            else
            {
                if( strpos($_SERVER['PATH_INFO'], $routePath) !== 0)
                    return false;

                if( (substr($_SERVER['PATH_INFO'], -1) == '/' ? substr($_SERVER['PATH_INFO'], 0, strlen($_SERVER['PATH_INFO']) - 1) : $_SERVER['PATH_INFO'])
                    !=
                    (substr($routePath, -1) == '/' ? substr($routePath, 0, strlen($routePath) - 1) : $routePath) )
                    return false;
                else
                {
                    self::finalize($responseContentType, $callback());
                    return true;
                }
            }
        }

        public static function post($routePath = '/', $responseContentType = 'text', $callback = null)
        {
            if( $_SERVER['REQUEST_METHOD'] != 'POST' )
                return false;

            if( !is_callable($callback) )
            {
                ExceptionManager::warn('No callback specified. or check callback is callable function, NOT string, array, object, etc. ');
                return false;
            }

            if( strpos($_SERVER['PATH_INFO'], $routePath) !== 0)
                return false;

            if( (substr($_SERVER['PATH_INFO'], -1) == '/' ? substr($_SERVER['PATH_INFO'], 0, strlen($_SERVER['PATH_INFO']) - 1) : $_SERVER['PATH_INFO'])
                !=
                (substr($routePath, -1) == '/' ? substr($routePath, 0, strlen($routePath) - 1) : $routePath) )
                return false;
            else
            {
                self::finalize($responseContentType, $callback());
                return true;
            }
        }


        public static function put($routePath = '/', $responseContentType = 'text', $callback = null)
        {
            if( $_SERVER['REQUEST_METHOD'] != 'PUT' )
                return false;

            if( !is_callable($callback) )
            {
                ExceptionManager::warn('No callback specified. or check callback is callable function, NOT string, array, object, etc. ');
                return false;
            }

            if( strpos($_SERVER['PATH_INFO'], $routePath) !== 0)
                return false;

            if( (substr($_SERVER['PATH_INFO'], -1) == '/' ? substr($_SERVER['PATH_INFO'], 0, strlen($_SERVER['PATH_INFO']) - 1) : $_SERVER['PATH_INFO'])
                !=
                (substr($routePath, -1) == '/' ? substr($routePath, 0, strlen($routePath) - 1) : $routePath) )
                return false;
            else
            {
                self::finalize($responseContentType, $callback());
                return true;
            }
        }

        public static function delete($routePath = '/', $responseContentType = 'text', $callback = null)
        {
            if( $_SERVER['REQUEST_METHOD'] != 'DELETE' )
                return false;

            if( !is_callable($callback) )
            {
                ExceptionManager::warn('No callback specified. or check callback is callable function, NOT string, array, object, etc. ');
                return false;
            }

            if( strpos($_SERVER['PATH_INFO'], $routePath) !== 0)
                return false;

            if( (substr($_SERVER['PATH_INFO'], -1) == '/' ? substr($_SERVER['PATH_INFO'], 0, strlen($_SERVER['PATH_INFO']) - 1) : $_SERVER['PATH_INFO'])
                !=
                (substr($routePath, -1) == '/' ? substr($routePath, 0, strlen($routePath) - 1) : $routePath) )
                return false;
            else
            {
                self::finalize($responseContentType, $callback());
                return true;
            }
        }

        public static function exception($responseContentType = 'text', $callback = null)
        {
            if( !is_callable($callback) )
            {
                ExceptionManager::warn('No callback specified. or check callback is callable function, NOT string, array, object, etc. ');
                return false;
            }

            self::finalize($responseContentType, $callback());
            return true;
        }

        private static function finalize($responseContentType = '', $responseBody = '')
        {
            if( isset(self::$ContentType[ $responseContentType ]) )
                header('Content-Type: '.self::$ContentType[ $responseContentType ]);


            echo $responseBody;
            exit;
        }
    }


    class ExceptionManager
    {
        public static function warn($message)
        {
            trigger_error(date('[Y-m-d H:i:s]').' Warning: '.$message, E_USER_WARNING);
        }

        public static function error($message)
        {
            trigger_error(date('[Y-m-d H:i:s]').' Error: '.$message, E_USER_ERROR);
            exit();
        }

        public static function notice($message)
        {
            trigger_error(date('[Y-m-d H:i:s]').' Notice: '.$message, E_USER_WARNING);
        }

        public static function debug($message)
        {
            if(defined('MOOYOUL_DEBUG'))
                echo date('[Y-m-d H:i:s]').' Debug: '.$message.'<br />';
        }
    }