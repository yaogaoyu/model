<?php
/**
 * 定义模型集合组件。
 *
 * @author    Snakevil Zen <zsnakevil@gmail.com>
 * @copyright © 2014 SZen.in
 * @license   LGPL-3.0+
 */

namespace Zen\Model;

use Zen\Core;

/**
 * 模型集合组件。
 *
 * @package Zen\Model
 * @version 0.1.0
 * @since   0.1.0
 */
abstract class Set extends Core\Component implements ISet
{
    /**
     * 模型组件类名。
     *
     * @var string
     */
    const MODEL_CLASS = 'Zen\Model\Model';

    /**
     * 集合内实体数量。
     *
     * @var int
     */
    protected $quantity;

    /**
     * 数据访问对象组件实例。
     *
     * @internal
     *
     * @var IDao
     */
    protected $dao;

    /**
     * 统计集合内实体数量。
     *
     * @return int
     */
    final public function count()
    {
        if (-1 == $this->quantity) {
            $this->quantity = $this->dao->count($this->conditions, $this->limit, $this->offset);
        }

        return $this->quantity;
    }

    /**
     * 集合遍历指针。
     *
     * @var int
     */
    protected $cursor;

    /**
     * 集合内实体表。
     *
     * @var IModel[]
     */
    protected $items;

    /**
     * 获取当前指向地实体。
     *
     * @return IModel|null
     */
    final public function current()
    {
        if ($this->valid()) {
            return $this->items[$this->cursor];
        }
    }

    /**
     * 获取当前遍历指针值。
     *
     * @return int|null
     */
    final public function key()
    {
        if ($this->valid()) {
            return $this->cursor;
        }
    }

    /**
     * 指向下一个实体。
     *
     * @return void
     */
    final public function next()
    {
        $this->cursor++;
    }

    /**
     * 过滤条件集。
     *
     * @internal
     *
     * @var array[]
     */
    protected $conditions;

    /**
     * 排序条件集。
     *
     * @internal
     *
     * @var array[]
     */
    protected $orders;

    /**
     * 截取起始位置。
     *
     * @internal
     *
     * @var int
     */
    protected $offset;

    /**
     * 截取最大数量。
     *
     * @internal
     *
     * @var int
     */
    protected $limit;

    /**
     * 重置遍历指针。
     *
     * @return void
     */
    final public function rewind()
    {
        if (-1 == $this->cursor) {
            $c_new = array(static::MODEL_CLASS, 'loadFromAttributes');
            foreach ($this->dao->query($this->conditions, $this->orders, $this->limit, $this->offset) as $ii) {
                $this->items[] = call_user_func($c_new, $ii);
            }
            if (-1 == $this->quantity) {
                $this->quantity = count($this->items);
            }
            $this->conditions = array();
            $this->offset =
            $this->limit = 0;
        }
        $this->cursor = 0;
    }

    /**
     * 判断当前是否指向有效地实体。
     *
     * @return bool
     */
    final public function valid()
    {
        if (-1 == $this->cursor) {
            $this->rewind();
        }

        return 0 <= $this->cursor && $this->cursor < $this->quantity;
    }

    /**
     * {@inheritdoc}
     *
     * @return array[]
     */
    final public function toArray()
    {
        $this->valid();
        $a_ret = array();
        foreach ($this->items as $ii) {
            $a_ret[] = $ii->toArray();
        }

        return $a_ret;
    }

    /**
     * {@inheritdoc}
     *
     * @return self
     */
    final public static function all()
    {
        return new static;
    }

    /**
     * 构造函数
     */
    final protected function __construct()
    {
        $this->items = array();
        $this->quantity =
        $this->cursor = -1;
        $this->conditions = array();
        $this->orders = array();
        $this->limit =
        $this->offset = 0;
        $this->dao = $this->newDao();
    }

    /**
     * 创建数据访问对象组件实例。
     *
     * @return IDao
     */
    abstract protected function newDao();

    /**
     * 按条件过滤。
     *
     * @param  string $attribute 属性名
     * @param  mixed  $value     期望值
     * @param  string $op        使用地操作符
     * @return self
     */
    final protected function filter($attribute, $value, $op)
    {
        if (-1 == $this->cursor) {
            if ($this->offset || $this->limit) {
                $this->rewind();
            } else {
                if (!isset($this->conditions[$attribute])) {
                    $this->conditions[$attribute] = array();
                }
                $this->conditions[$attribute][] = array($op, $value);

                return $this;
            }
        }
        $o_clone = new static;
        /** @var $ii Model **/
        foreach ($this->items as $ii) {
            if ($ii->assert($attribute, $value, $op)) {
                $o_clone->items[] = $ii;
            }
        }
        $o_clone->quantity = count($o_clone->items);
        $o_clone->cursor = 0;

        return $o_clone;
    }

    /**
     * {@inheritdoc}
     *
     * @param  string $attribute 属性名
     * @param  string $value     期望值
     * @return self
     */
    final public function filterEq($attribute, $value)
    {
        return $this->filter($attribute, $value, self::OP_EQ);
    }

    /**
     * {@inheritdoc}
     *
     * @param  string   $attribute 属性名
     * @param  string[] $value     期望值
     * @return self
     */
    final public function filterIn($attribute, $value)
    {
        if (!is_array($value)) {
            $value = array($value);
        }

        return $this->filter($attribute, $value, self::OP_IN);
    }

    /**
     * {@inheritdoc}
     *
     * @param  string $attribute 属性名
     * @param  number $value     期望值
     * @return self
     */
    final public function filterGt($attribute, $value)
    {
        return $this->filter($attribute, $value, self::OP_GT);
    }

    /**
     * {@inheritdoc}
     *
     * @param  string $attribute 属性名
     * @param  number $value     期望值
     * @return self
     */
    final public function filterLt($attribute, $value)
    {
        return $this->filter($attribute, $value, self::OP_LT);
    }

    /**
     * {@inheritdoc}
     *
     * @param  string $attribute 属性名
     * @param  number $min       最小值
     * @param  number $max       最大值
     * @return self
     */
    public function filterBetween($attribute, $min, $max)
    {
        return $this->filter($attribute, array($min, $max), self::OP_BT);
    }

    /**
     * {@inheritdoc}
     *
     * @param  string $attribute 属性名
     * @param  string $pattern   模式
     * @return self
     */
    public function filterLike($attribute, $pattern)
    {
        return $this->filter($attribute, $pattern, self::OP_LK);
    }

    /**
     * {@inheritdoc}
     *
     * @param  string $attribute 属性名
     * @param  string $value     期望值
     * @return self
     */
    final public function excludeEq($attribute, $value)
    {
        return $this->filter($attribute, $value, self::OP_NE);
    }

    /**
     * {@inheritdoc}
     *
     * @param  string   $attribute 属性名
     * @param  string[] $value     期望值
     * @return self
     */
    final public function excludeIn($attribute, $value)
    {
        if (!is_array($value)) {
            $value = array($value);
        }

        return $this->filter($attribute, $value, self::OP_NI);
    }

    /**
     * {@inheritdoc}
     *
     * @param  string $attribute 属性名
     * @param  number $value     期望值
     * @return self
     */
    final public function excludeGt($attribute, $value)
    {
        return $this->filter($attribute, $value, self::OP_LE);
    }

    /**
     * {@inheritdoc}
     *
     * @param  string $attribute 属性名
     * @param  number $value     期望值
     * @return self
     */
    final public function excludeLt($attribute, $value)
    {
        return $this->filter($attribute, $value, self::OP_GE);
    }

    /**
     * {@inheritdoc}
     *
     * @param  string $attribute 属性名
     * @param  number $min       最小值
     * @param  number $max       最大值
     * @return self
     */
    public function excludeBetween($attribute, $min, $max)
    {
        return $this->filter($attribute, array($min, $max), self::OP_NB);
    }

    /**
     * {@inheritdoc}
     *
     * @param  string $attribute 属性名
     * @param  string $pattern   模式
     * @return self
     */
    public function excludeLike($attribute, $pattern)
    {
        return $this->filter($attribute, $pattern, self::OP_NL);
    }

    /**
     * {@inheritdoc}
     *
     * @param  string $attribute 属性名
     * @param  bool   $ascading  可选。是否正向排序
     * @return self
     */
    final public function sortBy($attribute, $ascading = true)
    {
        $this->orders[$attribute] = $ascading;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param  int  $size   数量限制
     * @param  int  $offset 可选。起始位置
     * @return self
     */
    final public function crop($size, $offset = 0)
    {
        if (-1 == $this->cursor) {
            if ($this->offset || $this->limit) {
                $this->rewind();
            } else {
                $this->offset = $offset;
                $this->limit = $size;
                $this->quantity = -1;

                return $this;
            }
        }
        $o_clone = new static;
        $o_clone->items = array_slice($this->items, $offset, $size);
        $o_clone->quantity = count($o_clone->items);
        $o_clone->cursor = 0;

        return $o_clone;
    }
}
