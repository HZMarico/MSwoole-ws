<?php
/**
 * Sqlite数据库驱动
 */
namespace DB\Builder;

use DB\Builder;

class Sqlite extends Builder
{

    /**
     * limit
     * @access public
     * @return string
     */
    public function parseLimit($limit)
    {
        $limitStr = '';
        if (!empty($limit)) {
            $limit = explode(',', $limit);
            if (count($limit) > 1) {
                $limitStr .= ' LIMIT ' . $limit[1] . ' OFFSET ' . $limit[0] . ' ';
            } else {
                $limitStr .= ' LIMIT ' . $limit[0] . ' ';
            }
        }
        return $limitStr;
    }

    /**
     * 随机排序
     * @access protected
     * @return string
     */
    protected function parseRand()
    {
        return 'RANDOM()';
    }

    /**
     * 字段和表名处理
     * @access protected
     * @param string $key
     * @param array  $options
     * @return string
     */
    protected function parseKey($key, $options = [])
    {
        $key = trim($key);
        if (strpos($key, '.')) {
            list($table, $key) = explode('.', $key, 2);
            if ('__TABLE__' == $table) {
                $table = $this->query->getTable();
            }
            if (isset($options['alias'][$table])) {
                $table = $options['alias'][$table];
            }
        }
        if (isset($table)) {
            $key = $table . '.' . $key;
        }
        return $key;
    }
}
