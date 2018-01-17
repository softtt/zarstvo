<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 05.03.16
 * Time: 1:46
 */
class ph_simpleblog_searchsearchModuleFrontController extends ModuleFrontController
{
    public function __construct()
    {
        parent::__construct();
        $this->context = Context::getContext();

        // Connect and initiate phpmorphy
        require_once(_PS_MODULE_DIR_.'/ph_simpleblog_search/vendor/phpmorphy/src/common.php');
        $dir = _PS_MODULE_DIR_.'/ph_simpleblog_search/vendor/phpmorphy/dicts';
        $lang = 'ru_RU';
        $opts = ['storage' => PHPMORPHY_STORAGE_FILE];
        try {
            $this->morphy = new phpMorphy($dir, $lang, $opts);
        } catch(phpMorphy_Exception $e) {
            return null;
        }
    }

    public function initContent()
    {
        if (Tools::getValue('search_blog_query')) {
            require_once _PS_MODULE_DIR_.'ph_simpleblog/models/SimpleBlogPost.php';

            $query = Tools::getValue('search_blog_query');
            $page = Tools::getValue('search_blog_page');
            if (!$page)
                $page=1;

            $posts = $this->getResultPost($query, Configuration::get('PH_BLOG_POSTS_PER_PAGE'),$page);
            $pagination = $posts[1];
            $total_posts = $posts[2];
            $posts = $posts[0];

            if (!$posts)
                unset($posts);

            parent::initContent();
            $this->context->smarty->assign(
                array(
                    'blog_search_query' => Tools::getValue('search_blog_query'),
                    'posts' => $posts,
                    'worker2_link'    => $this->context->link->getModuleLink('ph_simpleblog_search', 'search'),
                    'page' => $page,
                    'pagination' => $pagination,
                    'total_posts_found' => $total_posts,
                )
            );
            $this->setTemplate('results.tpl');
        }
        else {
            parent::initContent();
            $this->setTemplate('form.tpl');
        }

    }

    /**
     * Generates search phrase.
     *
     * Generates search phrase with pseudoroots for given request with phpmorphy and returns generated request.
     *
     * @param string $query Requestred search query.
     *
     * @return string Generated search string.
     */
    public function genSearchWords($query)
    {
        preg_match_all('/([a-zа-яё]+)/ui', mb_strtoupper($query, "UTF-8"), $search_words);
        $words = $this->morphy->getPseudoRoot($search_words[1], $type = 'IGNORE_PREDICT');
        $s_words = [];

        foreach ($words as $k => $w) {
            if (!$w) {
                $w[0] = $k;
            }
            if (mb_strlen($w[0], "UTF-8") > 2) {
                $s_words[] = $w[0];
            }
        }

        $request = implode('* ', $s_words) . '*';
        return $request;
    }

    /**
     * Makes search for given query and parameters.
     *
     * Extracts results matching with given query and for given quantity search page number.
     *
     * @see SearchController::genSearchWords Generate search phrase.
     * @param string $query Search query phrase.
     * @param int $quantity Set search results amount for loading.
     * @param int $page Set search page number.
     *
     * @return array of Blog Posts
     */

    public function getResultPost($query, $quantity=10, $page=1)
    {
        $request = $this->genSearchWords($query);

        if ($page)
            $paged = ((int)$page-1)*(int)$quantity;
        else
            $paged = 0;

        $sql = new DbQuery();
        $sql->select('(SELECT COUNT(*) FROM ps_simpleblog_post_lang WHERE MATCH (title, content) AGAINST("'.$request.'" IN BOOLEAN MODE) + match(title, content) against ("'.$query.'") ) AS count, id_simpleblog_post');
        $sql->from('simpleblog_post_lang');
        $sql->where('MATCH (title, content) AGAINST("'.$request.'" IN BOOLEAN MODE) + match(title, content) against ("'.$query.'")');
        $sql->limit($quantity,$paged); // offset for pagination


        $posts = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql); // get IDs matching query, relevancy assigns later in getPosts
        $total_posts = $posts[0]['count'];

        if ($total_posts>$quantity) {
            $pagination['last'] = (int)ceil($total_posts/$quantity);
            $pagination['current'] = $page;

            $pagination['left_sibling'] = $pagination['current'] - 1 > 1 ? $pagination['current'] - 1 : false;
            $pagination['left_left_sibling'] = $pagination['current'] - 2 > 1 ? $pagination['current'] - 2 : false;
            $pagination['before_left_sibling'] = $pagination['current'] - 3 > 1 ? true: false;
            $pagination['right_sibling'] = $pagination['current'] + 1 < $pagination['last'] ? $pagination['current'] + 1 : false;
            $pagination['right_right_sibling'] = $pagination['current'] + 2 < $pagination['last'] ? $pagination['current'] + 2 : false;
            if ($pagination['right_right_sibling'])
                $pagination['after_right_sibling'] = $pagination['current'] + 3 < $pagination['last'] ? true : false;
            else
                $pagination['after_right_sibling'] = false;
        }
        else {
            $pagination = false;        // do not paginate
        }

        $id_lang = $this->context->language->id;
        $ids = array();

        foreach ($posts as $no=> $val) {
            /// get posts by id
            $ids[] = $val['id_simpleblog_post'];

        }
        #assign relevancy
        $order = 'match(l.title) against ("'.$request.'" IN BOOLEAN MODE) desc, match(l.content) against ("'.$request.'" IN BOOLEAN MODE) desc, match(l.title,l.content) against ("'.$query.'" IN BOOLEAN MODE)';

        $blog_posts = SimpleBlogPost::getPosts($id_lang,$quantity,null,null,true,$order,false,null,false,false,null,'in',$ids);

        $blog_posts_pagination[0] = $blog_posts;
        $blog_posts_pagination[1] = $pagination;
        $blog_posts_pagination[2] = $total_posts;

        return $blog_posts_pagination;

    }
}