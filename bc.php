<?php
/**
 * Created by PhpStorm.
 * User: Sedhossein
 * Date: 03/01/2019
 * Time: 10:16 AM
 */

namespace app\components\traits;

use Yii;

/**
 * @property array $data
 *
 * @author Sedhossein <seyedhosein77@gmail.com>
 * @since 2.0 | v2(dorhato.com)
 */
trait Breadcrumb
{

    // *shh* main URLs that We Reset The BreadCrumb Session In Bellow Positions
    private static $master_urls = [
        'home' => '/',
        'search' => '/search',
        'exhibition' => '/exhibition/all',
        'contact_us' => '/site/contactus',
        'help' => '/site/help',
        'faq' => '/site/faq',
        'about_us' => '/site/aboutus',
        'rule' => '/site/rule',
    ];

    private static $target_part = 'breadcrumb';


    public static function add()
    {
        return self::add_to_breadcrumb();
    }


    public static function add_to_breadcrumb()
    {
        $new_url = self::get_new_url();
        $_breadcrumb = self::get_session($new_url);

        if (self::does_need_reset($new_url)) {
            self::reset($_breadcrumb, $new_url);
            echo('whoops! problem in reset breadcrumb');
            return false;
        }

        return (self::does_new_url_exist_in_current_breadcrumb($_breadcrumb, $new_url)) ?
            self::make_new_broken_breadcrumb($_breadcrumb, $new_url) : self::make_new_breadcrumb($_breadcrumb, $new_url);
    }


    protected static function does_need_reset($new_url)
    {
        foreach (self::$master_urls as $master_url)
            if ($master_url == $new_url)
                return true;

        return false;
    }


    public static function reset($breadcrumb, $new_url)
    {
        $buffer = $breadcrumb;
        $breadcrumb = [
            'has_buffer' => 1,
            'before_url' => $buffer,
            'url_value' => $new_url
        ];

        return self::update_session($breadcrumb);
    }


    protected static function update_session($new_bc_array)
    {
        $_SESSION[self::$target_part] = $new_bc_array;
        return $_SESSION[self::$target_part];
    }


    protected static function get_session($new_url)
    {
        $session = Yii::$app->session;

        // check if a session is already open
        if ($session->isActive)
            $session->open();// open a session

        $breadcrumb_session_value = isset($session[self::$target_part])?$session[self::$target_part]:[];

        if (!$breadcrumb_session_value) {
            echo 'empty session for breadcrumb';
            $breadcrumb_session_value = self::update_session([
                'has_buffer' => 0,
                'before_url' => [],
                'url_value' => $new_url
            ]);
        }

        return $breadcrumb_session_value;
    }


    protected static function does_new_url_exist_in_current_breadcrumb(&$breadcrumb, $new_url)
    {
        if ($breadcrumb['url_value'] == $new_url)
            return true;

        if (empty($breadcrumb['before_url']))
            return false;

        return self::does_new_url_exist_in_current_breadcrumb($breadcrumb['before_url'], $new_url);
    }


    protected static function get_new_url()
    {
        return parse_url($_SERVER['REQUEST_URI'])['path'];
    }


    protected static function make_new_broken_breadcrumb($breadcrumb, $new_url)
    {
        $step = 0;
        $copy_breadcrumb = $breadcrumb;

        while (!empty($copy_breadcrumb)) {
            ++$step;

            if ($copy_breadcrumb['url_value'] == $new_url)
                break; //goto target_place;

            $copy_breadcrumb = $copy_breadcrumb['before_url'];
        }


        self::handleNestedElement($breadcrumb, $step, []); //update

        return self::update_session($breadcrumb);
    }



    public static function handleNestedElement(array &$array, $step, $value = null)
    {
        $tmp = &$array;//copy breadcrumb

        // make keys
        $keys = '';
        while ($step) {
            $keys .= "[before_url]";
            --$step;
        }
        $keys = explode('][', trim($keys, '[]'));
        // EO make keys

        while (count($keys) > 0) {
            $key = array_shift($keys);

            if (!is_array($tmp)) {
                if (is_null($value)) {
                    return null;
                } else {
                    $tmp = [];
                }
            }

            if (!isset($tmp[$key]) && is_null($value)) {
                return null;
            }

            $tmp = &$tmp[$key];
        }

        if (is_null($value))
            return $tmp;


        $tmp = $value;
        return true;

    }


    protected static function get_child_before_url($array)
    {
        if (empty($array)) // empty array!
            return [];

        return $array['before_url'];
    }


    protected static function make_new_breadcrumb($breadcrumb, $new_url)
    {
        $_new_breadcrumb = self::find_last_position_and_make_new_breadcrumb($breadcrumb, $new_url);

        if (empty($_new_breadcrumb))
            die('whoops! smth went wrong in recursion place');

        return self::update_session($_new_breadcrumb);
    }


    protected static function find_last_position_and_make_new_breadcrumb($breadcrumb, $new_url)
    {
        $new_breadcrumb = [];
        $_before_values = $breadcrumb['before_url'];

        echo ' @@@ ';
        echo $breadcrumb['url_value'] . ' - ';

        if (empty($_before_values)) {
            $new_breadcrumb['has_buffer'] = 0;
            $new_breadcrumb['url_value'] = $new_url;
            $new_breadcrumb['before_url'] = [];

            $breadcrumb['before_url'] = $new_breadcrumb;

            return $breadcrumb; // seccussful
        }

        return [
            'has_buffer' => 0,
            'url_value' => $breadcrumb['url_value'],
            'before_url' => self::find_last_position_and_make_new_breadcrumb($breadcrumb['before_url'], $new_url)
        ];
    }


    public static function get()
    {
        return self::get_breadcrumb();
    }


    protected static function get_breadcrumb()
    {
        $new_url = self::get_new_url();
        $breadcrumb = self::get_session($new_url);

        $_breadcrumb = self::get_before_urls_value($breadcrumb);

        return $_breadcrumb;
    }


    protected static function get_before_urls_value($breadcrumb)
    {
        $_breadcrumb = [];

        if (empty($breadcrumb['url_value']))//
            return [];

        do {
            $_breadcrumb[] = $breadcrumb['url_value'];
            $breadcrumb = $breadcrumb['before_url'];
        } while (!empty($breadcrumb));

        return $_breadcrumb;
    }

    public static function ()
    {

    }

//
//
//    //deleted
//    protected static function find_and_break_breadcrumb_chain(&$array, $step)
//    {
//
//        $keys = '';
//        while ($step) {
//            $keys .= "['before_url']";
//            --$step;
//        }
//
//        $keys = explode('][', trim($keys, '[]'));
//
//        $cpy_array = &$array;
//
//        foreach ($keys as $key) {
//            if (!array_key_exists($key, $cpy_array)) {
////                echo $key;
////                echo $cpy_array;
////                die('die');
////                $cpy_array[$key] = [];
//            }
////            $cpy_array = &$cpy_array[$key];
//        }
//        $cpy_array = [];
////        print_r($array); die();
//        return $cpy_array;
//
////        if (empty( $array ))
////            die('smth went wrong in find broken bc');
////
////        if ($step==0)//  !$step
////        {
////            $array['before_url'] = [];
////            return $array;
////        }
////        else
////            return self::find_and_break_breadcrumb_chain($array['before_url'], --$step);
//
//    }

}