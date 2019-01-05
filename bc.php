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

    public $myArray = [
        'before_url' => [
            'before_url' => [
                'before_url' => [
                    'before_url' => [
                        'before_url' => [
                            'before_url' => [
                                'before_url' => [


                                    'before_url' => [

                                    ],
                                    'has_buffer' => 0,
                                    'url_value' => '/test1/test111'


                                ],
                                'has_buffer' => 1, // ========
                                'url_value' => '/test1/test1'
                            ],
                            'has_buffer' => 0,
                            'url_value' => '/test2/test3'
                        ],
                        'has_buffer' => 0,
                        'url_value' => '/test2/test2'
                    ],
                    'has_buffer' => 0,
                    'url_value' => '/test2/test1'
                ],
                'has_buffer' => 0,
                'url_value' => '/test1/test3'
            ],
            'has_buffer' => 0,
            'url_value' => '/test1/test2'
        ],
        'has_buffer' => 0,
        'url_value' => '/test1/test1'
    ];

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
        $reset_result = false;

        if (self::does_need_reset($new_url)) {
            self::reset($_breadcrumb, $new_url);
            $reset_result = true;
        } // just for master urls

        if ($reset_result) {
            echo('whoops! problem in reset breadcrumb');
            return false;
        }

        var_dump(self::does_new_url_exist_in_current_breadcrumb($_breadcrumb, $new_url));
//        var_dump(self::make_new_breadcrumb($_breadcrumb, $new_url)); die();
        echo('<br>');
//        die();
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
        echo '*****RESET******';
        $buffer = $breadcrumb;
        $breadcrumb = [
            'has_buffer' => 1,
            'before_url' => $buffer,
            'url_value' => $new_url
        ];

        return self::update_session($breadcrumb);
    }


    protected static function update_session($array)
    {
        echo '<br>';
        echo '>>>>>>>>>>>>>>>>>>>>>>Passed Array (NEW)>>>>>>>>>>>>>>>>>>>';
        echo '<br>';
        $tt = $array;
        while ($tt['before_url']) {
            var_dump($tt);
            $tt = $tt['before_url'];
        }
        echo '<br>';
        echo '>>>>>>>>>>>>>>>>>>>>>>Old Session(Current)>>>>>>>>>>>>>>>>>>>';
        echo '<br>';
        $tt = $_SESSION[self::$target_part];
        while ($tt['before_url']) {
            var_dump($tt);
            $tt = $tt['before_url'];
        }
        echo '<br>';
        echo '>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>';
        echo '<br>';

        return $_SESSION[self::$target_part] = $array;
    }


    protected static function get_session($new_url)
    {
        $session = Yii::$app->session;

        // check if a session is already open
        if ($session->isActive)
            $session->open();// open a session

        if (empty($session[self::$target_part])) {
            echo 'empty session for breadcrumb';
            self::update_session([
                'has_buffer' => 0,
                'before_url' => [],
                'url_value' => $new_url
            ]);
        }


        return $session[self::$target_part];
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
//        print_r($breadcrumb); die();
//        $flag = '123';

        while (!empty($copy_breadcrumb)) {
            ++$step;

            if ($copy_breadcrumb['url_value'] == $new_url){
//                $flag = 'here';
                break; //goto target_place;
            }

            $copy_breadcrumb = $copy_breadcrumb['before_url'];
        }

//        print_r($flag); die();
//        $tt = self::find_and_break_breadcrumb_chain($breadcrumb, $step);
        $tt = self::handleNestedElement($breadcrumb, $step,[]);

        print_r($tt); die();

        return self::update_session($tt);
    }

//deleted
    protected static function find_and_break_breadcrumb_chain(&$array, $step)
    {

        $keys = '';
        while($step){
            $keys .= "['before_url']";
            --$step;
        }

        $keys = explode('][', trim($keys, '[]'));

        $cpy_array = &$array;

        foreach ($keys as $key) {
            if (!array_key_exists($key, $cpy_array)) {
//                echo $key;
//                echo $cpy_array;
//                die('die');
//                $cpy_array[$key] = [];
            }
//            $cpy_array = &$cpy_array[$key];
        }
        $cpy_array = [];
//        print_r($array); die();
        return $cpy_array;

//        if (empty( $array ))
//            die('smth went wrong in find broken bc');
//
//        if ($step==0)//  !$step
//        {
//            $array['before_url'] = [];
//            return $array;
//        }
//        else
//            return self::find_and_break_breadcrumb_chain($array['before_url'], --$step);

    }

    public static function handleNestedElement(array &$array, $step, $value = null)
    {
        $tmp = &$array;
        $keys = '';

        while($step){
            $keys .= "['before_url']";
            --$step;
        }

        $keys = explode('][', trim($keys, '[]'));

        while (count($keys) > 0) {
            $key = array_shift($keys);
            if (! is_array($tmp)) {
                if (is_null($value)) {
                    return null;
                } else {
                    $tmp = [];
                }
            }
            if (! isset($tmp[$key]) && is_null($value)) {
                return null;
            }
            $tmp = &$tmp[$key];
        }
        if (is_null($value)) {
            return $tmp;
        } else {
            $tmp = $value;
            return true;
        }
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

//        print_r($breadcrumb); die();
        do {
            $_breadcrumb[] = $breadcrumb['url_value'];
            $breadcrumb = $breadcrumb['before_url'];
        } while (!empty($breadcrumb));

        return $_breadcrumb;
    }


}