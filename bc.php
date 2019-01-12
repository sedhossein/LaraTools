<?php
/**
 * Created by PhpStorm.
 * User: Sedhossein
 * Date: 03/01/2019
 * Time: 10:16 AM
 */


/**
 *  BC : BreadCrumb
 * @property array $data
 *
 * @author Sedhossein <seyedhosein77@gmail.com>
 * @since 2.0 | v2(dorhato.com)
 */
trait Breadcrumb
{

    /**
     *  main URLs that We Reset The BreadCrumb Session In Bellow Positions
     * @var array
     */
    private static $master_urls = [
        'home' => [
            'url' => '/?',
            'title' => 'صفحه اصلی'
        ],
        '_search' => [ // expired route
            'url' => '/product/ads?',
            'title' => 'آگهی ها'
        ],
        'contact_us' => [
            'url' => '/site/contactus?',
            'title' => 'تماس با ما'
        ],
        'help' => [
            'url' => '/site/help?',
            'title' => 'راهنمایی سایت'
        ],
        'faq' => [
            'url' => '/site/faq?',
            'title' => 'سوالات متداول'
        ],
        'about_us' => [
            'url' => '/site/aboutus?',
            'title' => 'درباره ما'
        ],
        'rule' => [
            'url' => '/site/rule?',
            'title' => 'قوانین'
        ],
        'search' => [
            'url' => '/search?',
            'title' => 'صفحه آگهی ها'
        ],
        'exhibition' => [
            'url' => '/exhibition/all?',
            'title' => 'نمایشگاه ها'
        ],
        'news' => [
            'url' => '/news?',
            'title' => 'اخبار و دانستنی ها'
        ],
    ];


    /**
     *  The Session Name
     * @var string
     */
    private static $target_part = 'breadcrumb';


    /**
     * Get Title And Add Call add_to_breadcrumb
     *  note: just for geting more comfortable scene
     * @param $title
     * @return mixed
     */
    public static function add($title)
    {
        return self::add_to_breadcrumb($title);
    }


    /**
     * Get Title And Add new URL Request ( with passed $title ) To Current Breadcrumb
     * @param $title
     * @return array
     */
    public static function add_to_breadcrumb($title)
    {
        $new_url = self::get_new_url();
        $_breadcrumb = self::get_session($new_url, $title);

        // *shh* Check Does New Breadcrumb is Member of Master URLs
        if (self::does_need_reset($new_url)) {
            self::reset( $new_url, $title);
            return [];
        }

        // *shh*  check Is New Breadcrumb now Exist In Breadcrumb or Not,
        // If Now Its Exist, We Could Delete The Newer Breadcrumbs until The New Url($new_url) and Call ( make_new_broken_breadcrumb() )
        // And If Its The New BC, We Add It In The Last Child Of 'before_url' nested arrays. Calling ( make_new_breadcrumb() )
        return (self::does_new_url_exist_in_current_breadcrumb($_breadcrumb, $new_url)) ?
            self::make_new_broken_breadcrumb($_breadcrumb, $new_url) : self::make_new_breadcrumb($_breadcrumb, $new_url, $title);
    }


    /**
     *  Check $new_url Is The Member of Master URLs Or Not
     * @param $new_url
     * @return bool
     */
    protected static function does_need_reset($new_url)
    {
        foreach (self::$master_urls as $master_url)
            if ($master_url['url'] == $new_url)
                return true;  // find the url in $master_url

        return false;
    }


    /**
     *  Reset The BC And Make New BC And Exchange It With Old BC
     *  note: it has usage for master URLs;
     * @param $new_url
     * @param string $title
     * @return mixed
     */
    public static function reset($new_url, $title = '')
    {
        return self::update_session([
            'has_buffer' => 0,//1
            'before_url' => [],//$breadcrumb;,//todo : call (use) it for back button in browser
            'url_value' => $new_url,// or $master_url['url']
            'title' => $title
        ]);
    }


    /**
     *  Update Session And Replace The New BC On Old BC
     * @param $new_bc_array
     * @return mixed
     */
    protected static function update_session($new_bc_array)
    {
        return $_SESSION[self::$target_part] = $new_bc_array;
    }


    /**
     *  Return BC Session
     *  If Its Not Exist Any BC Session, This Method Create One
     *  And Set It,Then Return New Session For Request
     * @param $new_url
     * @param string $title
     * @return array
     */
    protected static function get_session($new_url, $title = 'اتوگالری مهدی')//todo
    {
        $session = Yii::$app->session; // get session with yii2 syntax

        // check if a session is already open
        if ($session->isActive)
            $session->open();// open a session

        $breadcrumb_session_value = isset($session[self::$target_part]) ? $session[self::$target_part] : []; // get BC session

        if (!$breadcrumb_session_value) { // Check Current BC Session values,if its empty, Create One in Bellow
            $breadcrumb_session_value = self::update_session([
                'has_buffer' => 0,
                'before_url' => [],
                'url_value' => $new_url,
                'title' => $title
            ]);
        }

        return $breadcrumb_session_value;
    }


    /**
     *  Check does_new_url_exist_in_current_breadcrumb :)
     * @param $breadcrumb
     * @param $new_url
     * @return bool
     */
    protected static function does_new_url_exist_in_current_breadcrumb($breadcrumb, $new_url)
    {
        if ($breadcrumb['url_value'] == $new_url)
            return true;

        if (empty($breadcrumb['before_url']) || $breadcrumb['has_buffer'])
            return false;


        return self::does_new_url_exist_in_current_breadcrumb($breadcrumb['before_url'], $new_url);
    }


    /**
     *  Make And Return Requested URL Value with The GET Params
     * @return string
     */
    protected static function get_new_url()
    {
        return parse_url($_SERVER['REQUEST_URI'])['path'] . '?' . $_SERVER['QUERY_STRING'];
    }


    /**
     *  Call After Existing An Old BC In Session
     *  And make_new_broken_breadcrumb ^^
     * @param $breadcrumb
     * @param $new_url
     * @return mixed
     */
    protected static function make_new_broken_breadcrumb($breadcrumb, $new_url)
    {
        $step = 0;
        $copy_breadcrumb = $breadcrumb;

        while (!empty($copy_breadcrumb) && !$copy_breadcrumb['has_buffer']) {
            ++$step;

            if ($copy_breadcrumb['url_value'] == $new_url)
                break;// break the

            $copy_breadcrumb = $copy_breadcrumb['before_url'];
        }

        self::handleNestedElement($breadcrumb, $step, []); //update

        return self::update_session($breadcrumb);
    }


    /**
     * *shh*
     *  This Method Get The Pointer Of Main Array and The Step Of Inner Key Index,
     *  And Replace The Passed $value Ii It.
     *  note: to Use This Method in [a][b][c] inner indexes, You Can Change The First Lines Of Code
     *  (make keys) Part And Find The Keys In There, And customize it. (;
     * @param array $array
     * @param $step
     * @param null $value
     * @return array|bool|mixed|null
     */
    public static function handleNestedElement(array &$array, $step, $value = null)
    {
        $tmp = &$array;//copy breadcrumb

        // (make keys)
        $keys = '';
        while ($step) {
            $keys .= "[before_url]";
            --$step;
        }
        $keys = explode('][', trim($keys, '[]'));
        // EO (make keys)

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


    /**
     *  Return The Before URL value (child)
     * @param $array
     * @return array
     */
    protected static function get_child_before_url($array)
    {
        if (empty($array)) // empty array!
            return [];

        return $array['before_url'];
    }


    /**
     *  Main Function To make_new_breadcrumb And Add $new_url
     *  in End Of The Last BC( Last Inner Nested Array )
     * @param $breadcrumb
     * @param $new_url
     * @param $title
     * @return mixed
     */
    protected static function make_new_breadcrumb($breadcrumb, $new_url, $title)
    {
        $_new_breadcrumb = self::find_last_position_and_make_new_breadcrumb($breadcrumb, $new_url, $title);

        if (empty($_new_breadcrumb))
            die('whoops! smth went wrong in recursion place');

        return self::update_session($_new_breadcrumb);
    }


    /**
     * Add New BC In End Of The(newest) Nested $_SESSION['breadcrumb']
     * @param $breadcrumb
     * @param $new_url
     * @param $title
     * @return array
     */
    protected static function find_last_position_and_make_new_breadcrumb($breadcrumb, $new_url, $title)
    {
        $new_breadcrumb = [];
        $_before_values = $breadcrumb['before_url'];

        if (empty($_before_values) || $new_breadcrumb['has_buffer']) {
            $new_breadcrumb['has_buffer'] = 0;
            $new_breadcrumb['url_value'] = $new_url;
            $new_breadcrumb['before_url'] = [];
            $new_breadcrumb['title'] = $title;

            $breadcrumb['before_url'] = $new_breadcrumb;

            return $breadcrumb; // seccussful
        }

        return [
            'has_buffer' => 0,
            'url_value' => $breadcrumb['url_value'],
            'title' => $breadcrumb['title'],
            'before_url' => self::find_last_position_and_make_new_breadcrumb($breadcrumb['before_url'], $new_url, $title)
        ];
    }


    /**
     *  Return The Current BCs
     * @return mixed
     */
    public static function get()
    {
        return self::get_breadcrumb();
    }


    /**
     *  Main Function To Get The Current BCs
     * @return mixed
     */
    protected static function get_breadcrumb()
    {
        $new_url = self::get_new_url();
        $breadcrumb = self::get_session($new_url);

        return self::get_before_urls_value($breadcrumb);
    }


    /**
     *  Return Final BCs Value {title,url} Array
     * @param $breadcrumb
     * @return array
     */
    protected static function get_before_urls_value($breadcrumb)
    {
        $_breadcrumb = [];

        if (empty($breadcrumb['url_value']))
            return [];

        if ($breadcrumb['has_buffer'])
            return [
                'url' => $breadcrumb['url_value'],
                'title' => $breadcrumb['title']
            ];


        do {
            if ($breadcrumb['url_value'])
                $_breadcrumb[] = [
                    'url' => $breadcrumb['url_value'],
                    'title' => $breadcrumb['title']
                ];
            $breadcrumb = $breadcrumb['before_url'];
        } while (!empty($breadcrumb) && !$breadcrumb['has_buffer']);//

        return $_breadcrumb;
    }


    /**
     * ...
     * @param $breadcrumb
     * @param $new_url
     * @return bool
     */
    public function is_familiar_url_with_old_ones($breadcrumb, $new_url)
    {
        foreach (self::$master_urls as $master_url)
            if ($master_url['url'] == $new_url)
                return true;
    }


    /**
     *  *shh* generate current breadcrumb(exist in session);
     *  Two way are exist :
     *  1.use default yii2 breadcrumb widget ( Enable Way == Current Code)
     *  2.generate customize HTML code here (in php) and echo in view side
     * @param $_this
     * @return bool
     */
    public static function generate(&$_this)
    {
        $bc_array = self::get();
//        echo '<a href="'.$bc_array[0]['url'].'"> test</a> ';
//        print_r($bc_array); die();
        $bc_len = count($bc_array) - 1;
        $step = 0;
        $_this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'صفحه اصلی'), 'url' => Url::home()];

        foreach ($bc_array as $bc) {

            if ($step == $bc_len) {
                $_this->params['breadcrumbs'][] = $bc['title'];
                break;
            }
            $_this->params['breadcrumbs'][] = ['label' => Yii::t('app', $bc['title']), 'url' => $bc['url']];

            ++$step;
        }

        return true;//'done'


// *shh*
//   make HTML code and print(echo) in view side with just call bellow line
//  ``` <?= Breadcrumb::generate($this); ? >  ```
//        $breadcrumb_html_code = '<div class="breadcrumbs">';
//        $breadcrumb_html_code .= '<li><a href="/"><span class="fa fa-home"></span> صفحه اصلی</a></li>';
//        $bc_array = self::get();
//        $bc_len = count($bc_array)-1;
//        $step = 0;
//        $_this->params['breadcrumbs'][] = ['label'=> Yii::t('app', 'صفحه اصلی') , 'url'=>Url::home() ];
//
//        foreach ($bc_array as $bc){
//
//            if ( $step == $bc_len ){
//                $breadcrumb_html_code .= '<span class=" ">'.$bc['title'].'</span>';
//                break;
//            }
//            $breadcrumb_html_code .= '<li><a href="'.$bc['url'].'"><span class=" ">'.$bc['title'].'</span></a></li>';
//
//            ++$step;
//        }
//        $breadcrumb_html_code .= '</div>';
//        return $breadcrumb_html_code;
    }


}