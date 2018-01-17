<?php
if (!defined('_CAN_LOAD_FILES_'))
    exit;

class BlocktopmenuOverride extends Blocktopmenu
{
    protected function generateCategoriesMenu($categories, $is_children = 0)
    {
        $html = '';

        foreach ($categories as $key => $category)
        {
            if (isset($category['children']) && !empty($category['children']))
            {
                $cat = new Category($category['id_category']);
                $link = Tools::HtmlEntitiesUTF8($cat->getLink());

                $html .= '<li'.((isset($this->page_name) && $this->page_name == 'category'
                    && (int)Tools::getValue('id_category') == (int)$category['id_category']) ? ' class="sfHoverForce"' : '').'>';
                $html .= '<a href="'.$link.'" title="'.$category['name'].'">'.$category['name'].'</a>';

                if ($category['level_depth'] < 2)
                {
                    $html .= '<ul>';
                    $html .= $this->generateCategoriesMenu($category['children'], 1);

                    $html .= '</ul>';
                }

                $html .= '</li>';
            }
        }

        return $html;
    }
}
