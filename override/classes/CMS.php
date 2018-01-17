<?php


class CMS extends CMSCore
{
    public static function getCMSPages($id_lang = null, $id_cms_category = null, $active = true, $id_shop = null)
    {
        $sql = new DbQuery();
        $sql->select('*');
        $sql->from('cms', 'c');
        if ($id_lang)
            $sql->innerJoin('cms_lang', 'l', 'c.id_cms = l.id_cms AND l.id_lang = '.(int)$id_lang);

        if ($id_shop)
            $sql->innerJoin('cms_shop', 'cs', 'c.id_cms = cs.id_cms AND cs.id_shop = '.(int)$id_shop);

        if ($active)
            $sql->where('c.active = 1');

        if ($id_cms_category)
            $sql->where('c.id_cms_category = '.(int)$id_cms_category);

        $sql->orderBy('position DESC');
        

        return Db::getInstance()->executeS($sql);
    }
}